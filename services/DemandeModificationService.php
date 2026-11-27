<?php

require_once __DIR__ . '/../models/DemandeModification.php';
require_once __DIR__ . '/../models/Employe.php';
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/JournalService.php';
require_once __DIR__ . '/NotificationService.php';

class DemandeModificationService
{
    private const CHAMPS = ['nom', 'prenom', 'date_naissance', 'telephone', 'lieu_residence'];

    public function __construct(
        private PDO $pdo,
        private DemandeModification $demandes,
        private Employe $employes,
        private Utilisateur $utilisateurs,
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
        if ($champ === 'lieu_residence') {
            $nouvelleValeur = preg_replace('/\s+/u', ' ', $nouvelleValeur) ?? '';
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
        $this->notifierRh($utilisateurId, $champ);

        return ['ok' => true, 'message' => 'Demande envoyée à la RH.', 'id' => $id];
    }

    public function traiter(int $rhId, int $demandeId, string $decision, ?string $motifRejet = null): array
    {
        if (!in_array($decision, ['acceptee', 'refusee'], true)) {
            return ['ok' => false, 'message' => 'Décision invalide.'];
        }
        $demande = $this->demandes->trouverEnAttente($demandeId);
        if ($demande === null || !in_array($demande['champ'], self::CHAMPS, true)) {
            return ['ok' => false, 'message' => 'Cette demande n’est plus disponible.'];
        }
        if ((int) $demande['utilisateur_id'] === $rhId) {
            return ['ok' => false, 'message' => 'Vous ne pouvez pas traiter votre propre demande de modification.'];
        }
        $motifRejet = is_string($motifRejet) ? trim($motifRejet) : '';
        if ($decision === 'refusee' && ($motifRejet === '' || mb_strlen($motifRejet) > 255)) {
            return ['ok' => false, 'message' => 'Le motif de refus est obligatoire et limité à 255 caractères.'];
        }

        $this->pdo->beginTransaction();
        try {
            if ($decision === 'acceptee') {
                $sql = 'UPDATE employe SET ' . $demande['champ'] . ' = :valeur WHERE id = :id';
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':valeur' => $demande['nouvelle_valeur'], ':id' => $demande['employe_id']]);
            }
            if (!$this->demandes->traiter($demandeId, $rhId, $decision, $decision === 'refusee' ? $motifRejet : null)) {
                throw new RuntimeException('La demande a déjà été traitée.');
            }
            $this->journal->enregistrer($rhId, 'traitement_demande_modification', 'demande_modification', $demandeId,
                ['statut' => 'en_attente'], ['statut' => $decision, 'champ' => $demande['champ'], 'motif_rejet' => $decision === 'refusee' ? $motifRejet : null]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'message' => $e->getMessage() === 'La demande a déjà été traitée.' ? $e->getMessage() : 'Le traitement de la demande a échoué.'];
        }

        $champ = self::libelleChamp($demande['champ']);
        $acceptee = $decision === 'acceptee';
        $this->notifications->notifier(
            (int) $demande['utilisateur_id'],
            'demande_modification_' . $decision,
            $acceptee ? 'Modification acceptée' : 'Modification refusée',
            $acceptee
                ? 'Votre demande de modification du champ « ' . $champ . ' » a été acceptée. Votre dossier a été mis à jour.'
                : 'Votre demande de modification du champ « ' . $champ . ' » a été refusée. Motif : ' . $motifRejet . '. Votre dossier reste inchangé.',
            '/views/employe/profil_employe.php'
        );

        return ['ok' => true, 'message' => $acceptee ? 'Demande acceptée et dossier mis à jour.' : 'Demande refusée.'];
    }

    private function validerValeur(string $champ, string $valeur, int $employeId): ?string
    {
        return match ($champ) {
            'nom', 'prenom' => ($valeur === '' || mb_strlen($valeur) > 100)
                ? 'Cette valeur est obligatoire et limitée à 100 caractères.' : null,
            'date_naissance' => !$this->estDateValide($valeur) || $valeur > date('Y-m-d')
                ? 'Date de naissance invalide.' : null,
            'telephone' => !preg_match('/^\+?[0-9 ]{8,20}$/', $valeur)
                ? 'Le numéro de téléphone est invalide.' : null,
            'lieu_residence' => (mb_strlen(preg_replace('/\s+/u', ' ', trim($valeur)) ?? '') < 3 || mb_strlen($valeur) > 255)
                ? 'Le lieu de résidence doit contenir entre 3 et 255 caractères.' : null,
            default => 'Champ invalide.',
        };
    }

    private function estDateValide(string $valeur): bool
    {
        $date = DateTime::createFromFormat('!Y-m-d', $valeur);
        return $date !== false && $date->format('Y-m-d') === $valeur;
    }

    private function notifierRh(int $demandeurId, string $champ): void
    {
        foreach ($this->utilisateurs->lister('rh', 'actif') as $rh) {
            if ((int) $rh['id'] === $demandeurId) {
                continue;
            }
            $this->notifications->notifier(
                (int) $rh['id'],
                'demande_modification_a_traiter',
                'Demande de modification à traiter',
                'Une demande concernant « ' . self::libelleChamp($champ) . ' » doit être traitée.',
                '/views/rh/demandes_rh.php?type=modifications'
            );
        }
    }

    public static function libelleChamp(string $champ): string
    {
        return ['nom' => 'nom', 'prenom' => 'prénom', 'date_naissance' => 'date de naissance', 'telephone' => 'téléphone', 'lieu_residence' => 'lieu de résidence'][$champ] ?? $champ;
    }
}
