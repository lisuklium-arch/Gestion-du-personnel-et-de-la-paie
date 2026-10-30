<?php
class Employe
{



    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function trouverParUtilisateurId(int $id): ?array
    {
        $stmt = $this
            ->pdo
            ->prepare("SELECT e.id, e.matricule, e.nom, e.prenom, e.statut,d.nom AS departement, p.intitule AS poste
                FROM employe e
                JOIN departement d ON d.id = e.departement_id
                JOIN poste p       ON p.id = e.poste_id
                WHERE e.utilisateur_id = ?");


        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function matriculeExiste(string $matricule): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM employe WHERE matricule = :matricule LIMIT 1');
        $stmt->execute([':matricule' => $matricule]);
        return $stmt->fetchColumn() !== false;
    }

    public function creer(int $utilisateurId, array $d): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO employe
            (utilisateur_id, matricule, nom, prenom, date_naissance, telephone, date_embauche, statut, departement_id, poste_id)
            VALUES
            (:utilisateur_id, :matricule, :nom, :prenom, :naissance, :telephone, :embauche, 'actif', :departement_id, :poste_id)"
        );
        $stmt->execute([
            ':utilisateur_id' => $utilisateurId,
            ':matricule' => $d['matricule'],
            ':nom' => $d['nom'],
            ':prenom' => $d['prenom'],
            ':naissance' => $d['naissance'],
            ':telephone' => $d['telephone'],
            ':embauche' => $d['date_embauche'],
            ':departement_id' => $d['departement_id'],
            ':poste_id' => $d['poste_id'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
