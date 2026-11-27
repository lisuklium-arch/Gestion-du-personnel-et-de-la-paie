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
            ->prepare
            ("SELECT e.id, e.matricule, e.nom, e.prenom, e.statut, e.photo_chemin, e.lieu_residence, d.nom AS departement, p.intitule AS poste
                FROM employe e
                JOIN departement d ON d.id = e.departement_id
                JOIN poste p       ON p.id = e.poste_id
                WHERE e.utilisateur_id = ?");


        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Retourne le dossier complet d'un employé, avec son rattachement
     * organisationnel. L'identifiant attendu est celui de `employe.id`.
     */
    public function trouver(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT e.id, e.utilisateur_id, u.email, u.role, e.matricule, e.nom, e.prenom,
                    e.date_naissance, e.telephone, e.lieu_residence, e.photo_chemin,
                    e.date_embauche, e.statut, e.departement_id, e.poste_id,
                    d.nom AS departement,
                    p.intitule AS poste,
                    d.chef_id,
                    chef.matricule AS chef_matricule,
                    chef.nom AS chef_nom,
                    chef.prenom AS chef_prenom
             FROM employe e
             JOIN utilisateur u ON u.id = e.utilisateur_id
             JOIN departement d ON d.id = e.departement_id
             JOIN poste p ON p.id = e.poste_id
             LEFT JOIN employe chef ON chef.id = d.chef_id
             WHERE e.id = :id"
        );
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function trouverParMatricule(string $matricule): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM employe WHERE matricule = :matricule');
        $stmt->execute([':matricule' => $matricule]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : $this->trouver((int) $id);
    }

    /**
     * Liste les membres actifs du département dirigé par l'employé indiqué.
     * Le chef lui-même est volontairement exclu de sa propre équipe.
     *
     * @return array<int, array<string, mixed>>
     */
    public function equipeDuChef(int $chefId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT e.id, e.utilisateur_id, e.matricule, e.nom, e.prenom,
                    e.telephone, e.photo_chemin, e.date_embauche,
                    e.departement_id, d.nom AS departement,
                    e.poste_id, p.intitule AS poste
             FROM departement d
             JOIN employe e ON e.departement_id = d.id
             JOIN poste p ON p.id = e.poste_id
             WHERE d.chef_id = :chef_id
               AND e.id <> :membre_id
               AND e.statut = 'actif'
             ORDER BY e.nom, e.prenom, e.id"
        );
        $stmt->execute([':chef_id' => $chefId, ':membre_id' => $chefId]);

        return $stmt->fetchAll();
    }

    /** Structure réelle destinée à l’organigramme, limitée aux dossiers actifs. */
    public function organigramme(): array
    {
        $departements = $this->pdo->query(
            "SELECT d.id, d.nom, d.parent_id, d.chef_id,
                    chef.id AS chef_employe_id, chef.prenom AS chef_prenom, chef.nom AS chef_nom,
                    chef.photo_chemin AS chef_photo, chef.matricule AS chef_matricule
             FROM departement d LEFT JOIN employe chef ON chef.id = d.chef_id AND chef.statut = 'actif'
             ORDER BY d.nom"
        )->fetchAll();
        $membres = $this->pdo->query(
            "SELECT e.id, e.departement_id, e.prenom, e.nom, e.matricule, e.photo_chemin, p.intitule AS poste
             FROM employe e JOIN poste p ON p.id = e.poste_id WHERE e.statut = 'actif' ORDER BY e.nom, e.prenom"
        )->fetchAll();
        $parDepartement = [];
        foreach ($membres as $membre) $parDepartement[(int) $membre['departement_id']][] = $membre;
        foreach ($departements as &$departement) {
            $liste = $parDepartement[(int) $departement['id']] ?? [];
            $departement['membres'] = array_values(array_filter($liste, fn(array $m) => (int) $m['id'] !== (int) ($departement['chef_id'] ?? 0)));
            $departement['effectif'] = count($liste);
        }
        unset($departement);
        return $departements;
    }

    public function matriculeExiste(string $matricule): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM employe WHERE matricule = :matricule LIMIT 1');
        $stmt->execute([':matricule' => $matricule]);
        return $stmt->fetchColumn() !== false;
    }

    public function matriculeExistePourAutre(string $matricule, int $employeId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM employe WHERE matricule = :matricule AND id <> :id LIMIT 1'
        );
        $stmt->execute([':matricule' => $matricule, ':id' => $employeId]);
        return $stmt->fetchColumn() !== false;
    }

    public function definirPhoto(int $employeId, string $cheminPrive): void
    {
        $stmt = $this->pdo->prepare('UPDATE employe SET photo_chemin = :chemin WHERE id = :id');
        $stmt->execute([':chemin' => $cheminPrive, ':id' => $employeId]);
    }

    public function creer(int $utilisateurId, array $d): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO employe
                (utilisateur_id, matricule, nom, prenom, date_naissance, telephone, lieu_residence, date_embauche, statut, departement_id, poste_id)
             VALUES
                (:utilisateur_id, :matricule, :nom, :prenom, :naissance, :telephone, :lieu_residence, :embauche, 'actif', :departement_id, :poste_id)"
        );
        $stmt->execute([
            ':utilisateur_id' => $utilisateurId,
            ':matricule'      => $d['matricule'],
            ':nom'            => $d['nom'],
            ':prenom'         => $d['prenom'],
            ':naissance'      => $d['naissance'],
            ':telephone'      => $d['telephone'],
            ':lieu_residence' => $d['lieu_residence'],
            ':embauche'       => $d['date_embauche'],
            ':departement_id' => $d['departement_id'],
            ':poste_id'       => $d['poste_id'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
