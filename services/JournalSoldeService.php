<?php
/**
 * Journal append-only des mouvements de solde + synchronisation des colonnes solde_conge
 * (jours_acquis, jours_reserves, jours_pris) pour rester compatible avec le lot paie.
 */
class JournalSoldeService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function soldeOuvert(int $employeId, int $typeCongeId, int $annee): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM solde_conge WHERE employe_id = ? AND type_conge_id = ? AND annee = ? LIMIT 1'
        );
        $st->execute([$employeId, $typeCongeId, $annee]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function disponible(array $solde): float
    {
        $acquis = (float) $solde['jours_acquis'] + (float) ($solde['jours_reportes'] ?? 0);
        return $acquis - (float) ($solde['jours_reserves'] ?? 0) - (float) $solde['jours_pris'];
    }

    public function poster(
        int $soldeId,
        ?int $demandeId,
        int $utilisateurId,
        string $type,
        float $dAcquis,
        float $dReserve,
        float $dPris,
        string $reference,
        ?string $motif = null
    ): void {
        $st = $this->pdo->prepare(
            'INSERT INTO mouvement_solde_conge
              (solde_id, demande_conge_id, utilisateur_id, type_mouvement,
               delta_acquis, delta_reserve, delta_pris, reference_operation, motif)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $soldeId, $demandeId, $utilisateurId, $type,
            $dAcquis, $dReserve, $dPris, $reference, $motif,
        ]);
        $this->resynchroniser($soldeId);
    }

    public function resynchroniser(int $soldeId): void
    {
        $st = $this->pdo->prepare(
            'SELECT
                COALESCE(SUM(delta_acquis),0) AS a,
                COALESCE(SUM(delta_reserve),0) AS r,
                COALESCE(SUM(delta_pris),0) AS p
             FROM mouvement_solde_conge WHERE solde_id = ?'
        );
        $st->execute([$soldeId]);
        $s = $st->fetch() ?: ['a' => 0, 'r' => 0, 'p' => 0];
        $up = $this->pdo->prepare(
            'UPDATE solde_conge SET jours_acquis = ?, jours_reserves = ?, jours_pris = ? WHERE id = ?'
        );
        $up->execute([
            max(0, (float) $s['a']),
            max(0, (float) $s['r']),
            max(0, (float) $s['p']),
            $soldeId,
        ]);
    }
}
