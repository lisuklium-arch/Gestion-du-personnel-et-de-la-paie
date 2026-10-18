<?php

class Employe
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function trouver(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.*, u.email, d.nom AS departement, p.intitule AS poste
             FROM employe e
             JOIN utilisateur u ON u.id = e.utilisateur_id
             JOIN departement d ON d.id = e.departement_id
             JOIN poste p ON p.id = e.poste_id
             WHERE e.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function trouverParUtilisateurId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.id, e.matricule, e.nom, e.prenom, e.statut,
                    d.nom AS departement, p.intitule AS poste
             FROM employe e
             JOIN departement d ON d.id = e.departement_id
             JOIN poste p ON p.id = e.poste_id
             WHERE e.utilisateur_id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function equipeDuChef(int $chefId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.id, e.matricule, e.nom, e.prenom, e.statut,
                    e.photo_chemin, p.intitule AS poste, d.nom AS departement
             FROM departement d
             JOIN employe e ON e.departement_id = d.id
             JOIN poste p ON p.id = e.poste_id
             WHERE d.chef_id = :chef_id
             ORDER BY e.nom, e.prenom'
        );
        $stmt->execute(['chef_id' => $chefId]);
        return $stmt->fetchAll();
    }

    public function lister(?string $recherche = null): array
    {
        $sql = 'SELECT e.id, e.utilisateur_id, e.matricule, e.nom, e.prenom,
                       e.date_naissance, e.telephone, e.photo_chemin, e.date_embauche,
                       e.statut, e.departement_id, e.poste_id, u.email,
                       d.nom AS departement, p.intitule AS poste
                FROM employe e
                JOIN utilisateur u ON u.id = e.utilisateur_id
                JOIN departement d ON d.id = e.departement_id
                JOIN poste p ON p.id = e.poste_id';
        if ($recherche !== null && trim($recherche) !== '') {
            $stmt = $this->pdo->prepare(
                $sql . ' WHERE e.nom LIKE :q_nom OR e.prenom LIKE :q_prenom
                         OR e.matricule LIKE :q_matricule OR u.email LIKE :q_email
                         ORDER BY e.nom, e.prenom'
            );
            $terme = '%' . trim($recherche) . '%';
            $stmt->execute([
                'q_nom' => $terme,
                'q_prenom' => $terme,
                'q_matricule' => $terme,
                'q_email' => $terme,
            ]);
            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->query($sql . ' ORDER BY e.nom, e.prenom');
        return $stmt->fetchAll();
    }

    public function creerUtilisateurLie(string $email, string $motDePasseHash, string $role): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO utilisateur (email, mot_de_passe_hash, role, statut_compte)
             VALUES (:email, :mot_de_passe_hash, :role, \'actif\')'
        );
        $stmt->execute([
            'email' => $email,
            'mot_de_passe_hash' => $motDePasseHash,
            'role' => $role,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function modifierEmailUtilisateur(int $employeId, string $email): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE utilisateur u
             JOIN employe e ON e.utilisateur_id = u.id
             SET u.email = :email
             WHERE e.id = :employe_id'
        );
        return $stmt->execute(['email' => $email, 'employe_id' => $employeId]);
    }

    public function desactiverCompteLie(int $employeId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE utilisateur u
             JOIN employe e ON e.utilisateur_id = u.id
             SET u.statut_compte = \'desactive\'
             WHERE e.id = :employe_id'
        );
        return $stmt->execute(['employe_id' => $employeId]);
    }

    public function creer(array $employe): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO employe
                (utilisateur_id, matricule, nom, prenom, date_naissance, telephone,
                 photo_chemin, date_embauche, statut, departement_id, poste_id)
             VALUES
                (:utilisateur_id, :matricule, :nom, :prenom, :date_naissance, :telephone,
                 :photo_chemin, :date_embauche, :statut, :departement_id, :poste_id)'
        );
        $stmt->execute($employe);
        return (int) $this->pdo->lastInsertId();
    }

    public function modifier(int $id, array $employe): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE employe
             SET matricule = :matricule, nom = :nom, prenom = :prenom,
                 date_naissance = :date_naissance, telephone = :telephone,
                 photo_chemin = :photo_chemin, date_embauche = :date_embauche,
                 statut = :statut, departement_id = :departement_id, poste_id = :poste_id
             WHERE id = :id'
        );
        return $stmt->execute($employe + ['id' => $id]);
    }

    public function supprimer(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM employe WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
