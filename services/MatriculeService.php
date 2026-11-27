<?php

require_once __DIR__ . '/../models/Employe.php';
require_once __DIR__ . '/JournalService.php';

/** Gère les matricules système et leurs corrections administratives exceptionnelles. */
class MatriculeService
{
    public function __construct(private PDO $pdo, private Employe $employes, private JournalService $journal)
    {
    }

    /** À appeler dans la transaction de création déjà ouverte. */
    public function prochainDansTransaction(): string
    {
        if (!$this->pdo->inTransaction()) {
            throw new LogicException('La génération du matricule doit être transactionnelle.');
        }

        $numero = $this->verrouillerCompteur();
        while ($numero <= 9999) {
            $matricule = self::formater($numero);
            if (!$this->estReserve($matricule)) {
                $this->pdo->prepare('UPDATE compteur_matricule SET prochain_numero = :suivant WHERE id = 1')
                    ->execute([':suivant' => $numero + 1]);
                return $matricule;
            }
            $numero++;
        }
        throw new RuntimeException('La plage de matricules EMP-0001 à EMP-9999 est épuisée.');
    }

    /** Inscrit définitivement le matricule attribué dans son historique. */
    public function enregistrerAttributionDansTransaction(int $employeId, string $matricule, int $auteurId): void
    {
        if (!$this->pdo->inTransaction()) {
            throw new LogicException('L’attribution du matricule doit être transactionnelle.');
        }
        $stmt = $this->pdo->prepare(
            "INSERT INTO historique_matricule (employe_id, matricule, type_action, effectue_par_id)
             VALUES (:employe_id, :matricule, 'attribution_initiale', :auteur_id)"
        );
        $stmt->execute([':employe_id' => $employeId, ':matricule' => $matricule, ':auteur_id' => $auteurId]);
    }

    /** Correction réservée à la RH : l'ancien matricule demeure réservé dans l'historique. */
    public function corriger(int $rhId, int $employeId, string $nouveauMatricule): array
    {
        $nouveauMatricule = strtoupper(trim($nouveauMatricule));
        if (!self::formatValide($nouveauMatricule)) {
            return ['ok' => false, 'message' => 'Matricule invalide : le format attendu est EMP-0000.'];
        }

        $this->pdo->beginTransaction();
        try {
            $this->verrouillerCompteur();
            $employe = $this->employes->trouver($employeId);
            if ($employe === null) {
                throw new RuntimeException('Dossier employé introuvable.');
            }
            $ancienMatricule = (string) $employe['matricule'];
            if ($nouveauMatricule === $ancienMatricule) {
                throw new RuntimeException('Le nouveau matricule est identique au matricule actuel.');
            }
            if ($this->estReserve($nouveauMatricule)) {
                throw new RuntimeException('Ce matricule est déjà attribué ou réservé dans l’historique.');
            }

            $this->employes->mettreAJourMatricule($employeId, $nouveauMatricule);
            $stmt = $this->pdo->prepare(
                "INSERT INTO historique_matricule (employe_id, matricule, type_action, ancien_matricule, effectue_par_id)
                 VALUES (:employe_id, :matricule, 'correction_exceptionnelle', :ancien_matricule, :auteur_id)"
            );
            $stmt->execute([
                ':employe_id' => $employeId,
                ':matricule' => $nouveauMatricule,
                ':ancien_matricule' => $ancienMatricule,
                ':auteur_id' => $rhId,
            ]);
            $this->journal->enregistrer($rhId, 'correction_matricule', 'employe', $employeId,
                ['matricule' => $ancienMatricule], ['matricule' => $nouveauMatricule]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'message' => $e->getMessage() ?: 'La correction du matricule a échoué.'];
        }
        return ['ok' => true, 'message' => 'Matricule corrigé et historisé.', 'matricule' => $nouveauMatricule];
    }

    public static function formatValide(string $matricule): bool
    {
        return preg_match('/^EMP-[0-9]{4}$/', $matricule) === 1;
    }

    private static function formater(int $numero): string
    {
        return 'EMP-' . str_pad((string) $numero, 4, '0', STR_PAD_LEFT);
    }

    private function verrouillerCompteur(): int
    {
        $stmt = $this->pdo->query('SELECT prochain_numero FROM compteur_matricule WHERE id = 1 FOR UPDATE');
        $numero = $stmt->fetchColumn();
        if ($numero === false) {
            throw new RuntimeException('Le compteur de matricules est introuvable. Appliquez la migration du Domaine B.');
        }
        return (int) $numero;
    }

    private function estReserve(string $matricule): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM historique_matricule WHERE matricule = :matricule_historique
             UNION SELECT 1 FROM employe WHERE matricule = :matricule_employe LIMIT 1'
        );
        $stmt->execute([':matricule_historique' => $matricule, ':matricule_employe' => $matricule]);
        return $stmt->fetchColumn() !== false;
    }
}
