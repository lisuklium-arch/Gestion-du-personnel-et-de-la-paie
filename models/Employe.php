<?php
    class Employe {

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function trouverParUtilisateurId(int $id): ?array {
        $stmt = $this
                ->pdo
                ->prepare
                ("SELECT e.id, e.matricule, e.nom, e.prenom, e.statut,d.nom AS departement, p.intitule AS poste
                FROM employe e
                JOIN departement d ON d.id = e.departement_id
                JOIN poste p       ON p.id = e.poste_id
                WHERE e.utilisateur_id = ?");
        

        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }



    }
