<?php

class DemandeDocument
{
    public function __construct(private PDO $pdo)
    {
    }

    public function creer(int $employeId, string $type, ?string $motif): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO demande_document (employe_id, type, motif) VALUES (:employe_id, :type, :motif)');
        $stmt->execute([':employe_id' => $employeId, ':type' => $type, ':motif' => $motif]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listerPourEmploye(int $employeId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, type, motif, statut, date_demande FROM demande_document WHERE employe_id = :id ORDER BY date_demande DESC, id DESC');
        $stmt->execute([':id' => $employeId]);
        return $stmt->fetchAll();
    }

    public function listerEnAttente(): array
    {
        return $this->pdo->query(
            "SELECT dd.id, dd.type, dd.motif, dd.date_demande, e.id AS employe_id,
                    e.utilisateur_id, e.matricule, e.nom, e.prenom, e.photo_chemin, e.departement_id,
                    d.nom AS departement, p.intitule AS poste
             FROM demande_document dd
             JOIN employe e ON e.id = dd.employe_id
             JOIN departement d ON d.id = e.departement_id
             JOIN poste p ON p.id = e.poste_id
             WHERE dd.statut = 'en_attente'
             ORDER BY dd.date_demande ASC, dd.id ASC"
        )->fetchAll();
    }

    public function trouverEnAttente(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT dd.id, dd.employe_id, dd.type, dd.motif, e.utilisateur_id,
                    e.matricule, e.nom, e.prenom, e.date_embauche, d.nom AS departement, p.intitule AS poste
             FROM demande_document dd
             JOIN employe e ON e.id = dd.employe_id
             JOIN departement d ON d.id = e.departement_id
             JOIN poste p ON p.id = e.poste_id
             WHERE dd.id = :id AND dd.statut = 'en_attente'"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function traiter(int $id, int $traiteParId, string $statut, ?int $documentId = null): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE demande_document SET statut = :statut, document_id = :document_id, traite_par_id = :traite_par_id
             WHERE id = :id AND statut = 'en_attente'"
        );
        $stmt->execute([':statut' => $statut, ':document_id' => $documentId, ':traite_par_id' => $traiteParId, ':id' => $id]);
        return $stmt->rowCount() === 1;
    }
}
