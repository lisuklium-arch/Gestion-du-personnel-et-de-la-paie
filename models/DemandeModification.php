<?php

class DemandeModification
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function existeEnAttente(int $employeId, string $champ): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM demande_modification
             WHERE employe_id = :employe_id AND champ = :champ AND statut = 'en_attente' LIMIT 1"
        );
        $stmt->execute([':employe_id' => $employeId, ':champ' => $champ]);
        return $stmt->fetchColumn() !== false;
    }

    public function creer(int $employeId, string $champ, ?string $ancienneValeur, string $nouvelleValeur, ?string $motif): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO demande_modification (employe_id, champ, ancienne_valeur, nouvelle_valeur, motif)
             VALUES (:employe_id, :champ, :ancienne_valeur, :nouvelle_valeur, :motif)"
        );
        $stmt->execute([
            ':employe_id' => $employeId,
            ':champ' => $champ,
            ':ancienne_valeur' => $ancienneValeur,
            ':nouvelle_valeur' => $nouvelleValeur,
            ':motif' => $motif,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listerEnAttente(): array
    {
        return $this->pdo->query(
            "SELECT dm.id, dm.champ, dm.ancienne_valeur, dm.nouvelle_valeur, dm.motif, dm.date_demande,
                    e.id AS employe_id, e.matricule, e.nom, e.prenom, e.photo_chemin,
                    d.nom AS departement, p.intitule AS poste
             FROM demande_modification dm
             JOIN employe e ON e.id = dm.employe_id
             JOIN departement d ON d.id = e.departement_id
             JOIN poste p ON p.id = e.poste_id
             WHERE dm.statut = 'en_attente'
             ORDER BY dm.date_demande ASC, dm.id ASC"
        )->fetchAll();
    }

    public function trouverEnAttente(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT dm.id, dm.employe_id, dm.champ, dm.ancienne_valeur, dm.nouvelle_valeur, dm.motif, dm.motif_rejet,
                    e.utilisateur_id
             FROM demande_modification dm
             JOIN employe e ON e.id = dm.employe_id
             WHERE dm.id = :id AND dm.statut = 'en_attente'"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function traiter(int $id, int $traiteParId, string $statut, ?string $motifRejet = null): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE demande_modification
             SET statut = :statut, traite_par_id = :traite_par_id, motif_rejet = :motif_rejet, date_traitement = NOW()
             WHERE id = :id AND statut = 'en_attente'"
        );
        $stmt->execute([':statut' => $statut, ':traite_par_id' => $traiteParId, ':motif_rejet' => $motifRejet, ':id' => $id]);
        return $stmt->rowCount() === 1;
    }

    /** Historique du lieu de résidence, visible uniquement par le demandeur. */
    public function listerLieuResidencePourEmploye(int $employeId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT dm.id, dm.ancienne_valeur, dm.nouvelle_valeur, dm.motif, dm.motif_rejet,
                    dm.statut, dm.date_demande, dm.date_traitement,
                    u.email AS traite_par_email, et.prenom AS traite_par_prenom, et.nom AS traite_par_nom
             FROM demande_modification dm
             LEFT JOIN utilisateur u ON u.id = dm.traite_par_id
             LEFT JOIN employe et ON et.utilisateur_id = u.id
             WHERE dm.employe_id = :employe_id AND dm.champ = 'lieu_residence'
             ORDER BY dm.date_demande DESC, dm.id DESC"
        );
        $stmt->execute([':employe_id' => $employeId]);
        return $stmt->fetchAll();
    }
}
