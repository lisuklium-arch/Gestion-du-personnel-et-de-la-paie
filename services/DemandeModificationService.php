<?php

require_once __DIR__ . '/../models/DemandeModification.php';
require_once __DIR__ . '/../models/Employe.php';
require_once __DIR__ . '/JournalService.php';
require_once __DIR__ . '/NotificationService.php';

class DemandeModificationService
{
    private const CHAMPS = ['nom', 'prenom', 'matricule', 'date_naissance', 'telephone'];

    public function __construct(
        private PDO $pdo,
        private DemandeModification $demandes,
        private Employe $employes,
        private JournalService $journal,
        private NotificationService $notifications
    ) {
    }

    public function creerPourUtilisateur(int $utilisateurId, array $saisie): array
    {
        $employe = $this->employes->trouverParUtilisateurId($utilisateurId);
        if ($employe === null) {
            return ['ok' => false, 'message' => 'Aucun dossier employé n’est associé à ce compte.'];
        }

        $champ = is_string($saisie['champ'] ?? null) ? $saisie['champ'] : '';
        $nouvelleValeur = is_string($saisie['nouvelle_valeur'] ?? null) ? trim($saisie['nouvelle_valeur']) : '';
        $motif = is_string($saisie['motif'] ?? null) ? trim($saisie['motif']) : '';

        if (!in_array($champ, self::CHAMPS, true)) {
            return ['ok' => false, 'message' => 'Ce champ ne peut pas être modifié par ce formulaire.'];
        }
        if ($motif === '' || mb_strlen($motif) > 255) {
            return ['ok' => false, 'message' => 'Le motif est obligatoire et limité à 255 caractères.'];
        }

        $dossier = $this->employes->trouver((int) $employe['id']);
        $ancienneValeur = $dossier[$champ] ?? null;
        if ($nouvelleValeur === $ancienneValeur) {
            return ['ok' => false, 'message' => 'La nouvelle valeur est identique à celle déjà enregistrée.'];
        }
        if ($this->demandes->existeEnAttente((int) $employe['id'], $champ)) {
            return ['ok' => false, 'message' => 'Une demande de modification de ce champ est déjà en attente.'];
        }

        $erreur = $this->validerValeur($champ, $nouvelleValeur, (int) $employe['id']);
        if ($erreur !== null) {
            return ['ok' => false, 'message' => $erreur];
        }

        $id = $this->demandes->creer((int) $employe['id'], $champ, $ancienneValeur, $nouvelleValeur, $motif);
        $this->journal->enregistrer($utilisateurId, 'demande_modification', 'demande_modification', $id, null, [
            'champ' => $champ,
            'employe_id' => (int) $employe['id'],
        ]);

        return ['ok' => true, 'message' => 'Demande envoyée à la RH.', 'id' => $id];
    }

    public function traiter(int $rhId, int $demandeId, string $decision): array
    {
        if (!in_array($decision, ['acceptee', 'refusee'], true)) {
            return ['ok' => false, 'message' => 'Décision invalide.'];
        }
        $demande = $this->demandes->trouverEnAttente($demandeId);
        if ($demande === null || !in_array($demande['champ'], self::CHAMPS, true)) {
            return ['ok' => false, 'message' => 'Cette demande n’est plus disponible.'];
        }

        $this->pdo->beginTransaction();
        try {
            if ($decision === 'acceptee') {
                $sql = 'UPDATE employe SET ' . $demande['champ'] . ' = :valeur WHERE id = :id';
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':valeur' => $demande['nouvelle_valeur'], ':id' => $demande['employe_id']]);
            }
            if (!$this->demandes->traiter($demandeId, $rhId, $decision)) {
                throw new RuntimeException('La demande a déjà été traitée.');
            }
            $this->journal->enregistrer($rhId, 'traitement_demande_modification', 'demande_modification', $demandeId,
                ['statut' => 'en_attente'], ['statut' => $decision]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'message' => $e->getMessage() === 'La demande a déjà été traitée.' ? $e->getMessage() : 'Le traitement de la demande a échoué.'];
        }

        $champ = ['nom' => 'nom', 'prenom' => 'prénom', 'matricule' => 'matricule', 'date_naissance' => 'date de naissance', 'telephone' => 'téléphone'][$demande['champ']];
        $acceptee = $decision === 'acceptee';
        $this->notifications->notifier(
            (int) $demande['utilisateur_id'],
            'demande_modification_' . $decision,
            $acceptee ? 'Modification acceptée' : 'Modification refusée',
            $acceptee
                ? 'Votre demande de modification du champ « ' . $champ . ' » a été acceptée. Votre dossier a été mis à jour.'
                : 'Votre demande de modification du champ « ' . $champ . ' » a été refusée. Votre dossier reste inchangé.',
            '/views/employe/profil_employe.php'
        );

        return ['ok' => true, 'message' => $acceptee ? 'Demande acceptée et dossier mis à jour.' : 'Demande refusée.'];
    }

    private function validerValeur(string $champ, string $valeur, int $employeId): ?string
    {
        return match ($champ) {
            'nom', 'prenom' => ($valeur === '' || mb_strlen($valeur) > 100)
                ? 'Cette valeur est obligatoire et limitée à 100 caractères.' : null,
            'matricule' => !preg_match('/^EMP-[0-9]{4}$/', $valeur)
                ? 'Matricule invalide (format EMP-0000).'
                : ($this->employes->matriculeExistePourAutre($valeur, $employeId) ? 'Ce matricule est déjà utilisé.' : null),
            'date_naissance' => !$this->estDateValide($valeur) || $valeur > date('Y-m-d')
                ? 'Date de naissance invalide.' : null,
            'telephone' => !preg_match('/^\+?[0-9 ]{8,20}$/', $valeur)
                ? 'Le numéro de téléphone est invalide.' : null,
            default => 'Champ invalide.',
        };
    }

    private function estDateValide(string $valeur): bool
    {
        $date = DateTime::createFromFormat('!Y-m-d', $valeur);
        return $date !== false && $date->format('Y-m-d') === $valeur;
    }
}
