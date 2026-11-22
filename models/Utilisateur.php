<?php

class Utilisateur
{
    private const ROLES = ['employe', 'chef', 'rh', 'dg'];
    private const STATUTS = ['en_attente', 'actif', 'desactive'];

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function trouverParEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM utilisateur WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function trouverParId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM utilisateur WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function emailExiste(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM utilisateur WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        return $stmt->fetchColumn() !== false;
    }

    public function creer(
        string $email,
        string $motDePasseHash,
        string $role,
        string $statut = 'en_attente',
        bool $doitChangerMdp = true,
        ?int $valideParId = null
    ): int {
        if (!in_array($role, self::ROLES, true) || !in_array($statut, self::STATUTS, true)) {
            throw new InvalidArgumentException('Rôle ou statut de compte invalide.');
        }

        $dateValidation = $valideParId === null ? 'NULL' : 'NOW()';

        $stmt = $this->pdo->prepare(
            "INSERT INTO utilisateur
                (email, mot_de_passe_hash, role, statut_compte, doit_changer_mdp, valide_par_id, date_validation)
             VALUES
                (:email, :hash, :role, :statut, :doit_changer, :valide_par, $dateValidation)"
        );
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':hash', $motDePasseHash);
        $stmt->bindValue(':role', $role);
        $stmt->bindValue(':statut', $statut);
        $stmt->bindValue(':doit_changer', $doitChangerMdp ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':valide_par', $valideParId, $valideParId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }

    public function valider(int $id, int $valideurId, string $hashTemporaire): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE utilisateur
             SET statut_compte = 'actif',
                 mot_de_passe_hash = :hash,
                 doit_changer_mdp = 1,
                 valide_par_id = :valideur,
                 date_validation = NOW()
             WHERE id = :id AND statut_compte = 'en_attente'"
        );
        $stmt->execute([':hash' => $hashTemporaire, ':valideur' => $valideurId, ':id' => $id]);
        return $stmt->rowCount() === 1;
    }

    public function changerStatut(int $id, string $statut): bool
    {
        if (!in_array($statut, self::STATUTS, true)) {
            return false;
        }

        $stmt = $this->pdo->prepare('UPDATE utilisateur SET statut_compte = :statut WHERE id = :id');
        $stmt->execute([':statut' => $statut, ':id' => $id]);
        return $stmt->rowCount() === 1;
    }

    public function lister(?string $role = null, ?string $statut = null): array
    {
        $sql = 'SELECT u.id, u.email, u.role, u.statut_compte, u.derniere_connexion, u.date_validation,
                       e.prenom, e.nom, e.matricule
                FROM utilisateur u
                LEFT JOIN employe e ON e.utilisateur_id = u.id';
        $conditions = [];
        $params = [];

        if ($role !== null && $role !== '') {
            $conditions[] = 'u.role = :role';
            $params[':role'] = $role;
        }
        if ($statut !== null && $statut !== '') {
            $conditions[] = 'u.statut_compte = :statut';
            $params[':statut'] = $statut;
        }
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY u.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function rechercherComptes(array $f = []): array
    {
        $sql = "SELECT u.id, u.email, u.role, u.statut_compte, u.derniere_connexion, u.date_validation,
                       e.matricule, e.nom, e.prenom, e.statut AS statut_dossier,
                       e.departement_id, e.poste_id,
                       p.intitule AS poste, d.nom AS departement,
                       c.type AS type_contrat, c.date_fin,
                       j.date_heure AS cree_le, uc.email AS cree_par_email
                FROM utilisateur u
                LEFT JOIN employe e ON e.utilisateur_id = u.id
                LEFT JOIN poste p ON p.id = e.poste_id
                LEFT JOIN departement d ON d.id = e.departement_id
                LEFT JOIN contrat c ON c.employe_id = e.id AND c.statut = 'en_cours'
                LEFT JOIN journal_action j ON j.entite = 'utilisateur' AND j.entite_id = u.id AND j.action = 'creation_compte'
                LEFT JOIN utilisateur uc ON uc.id = j.utilisateur_id";
        $conditions = [];
        $params = [];

        if (!empty($f['avec_dossier'])) {
            $conditions[] = 'e.id IS NOT NULL';
        }
        if (!empty($f['roles'])) {
            $marques = [];
            foreach (array_values($f['roles']) as $i => $role) {
                $marques[] = ':role' . $i;
                $params[':role' . $i] = $role;
            }
            $conditions[] = 'u.role IN (' . implode(', ', $marques) . ')';
        }
        foreach (['departement_id' => 'e.departement_id', 'poste_id' => 'e.poste_id',
                  'statut_dossier' => 'e.statut', 'statut_compte' => 'u.statut_compte'] as $cle => $colonne) {
            if (isset($f[$cle]) && $f[$cle] !== '') {
                $conditions[] = $colonne . ' = :' . $cle;
                $params[':' . $cle] = $f[$cle];
            }
        }
        $termes = preg_split('/\s+/u', trim((string) ($f['q'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($termes as $i => $terme) {
            $motif = '%' . strtr($terme, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            $conditions[] = "(e.nom LIKE :q$i ESCAPE '!' OR e.prenom LIKE :q$i ESCAPE '!'
                              OR e.matricule LIKE :q$i ESCAPE '!' OR u.email LIKE :q$i ESCAPE '!')";
            $params[':q' . $i] = $motif;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY (u.statut_compte = 'en_attente') DESC, e.nom, e.prenom, u.id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function compterDossiers(): array
    {
        $comptes = ['actif' => 0, 'suspendu' => 0, 'sorti' => 0];
        foreach ($this->pdo->query('SELECT statut, COUNT(*) AS n FROM employe GROUP BY statut')->fetchAll() as $l) {
            $comptes[$l['statut']] = (int) $l['n'];
        }
        $comptes['total'] = array_sum($comptes);
        return $comptes;
    }

    public function etatSession(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.role, u.statut_compte, u.doit_changer_mdp, e.statut AS statut_dossier
             FROM utilisateur u
             LEFT JOIN employe e ON e.utilisateur_id = u.id
             WHERE u.id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function majDerniereConnexion(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE utilisateur SET derniere_connexion = NOW() WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function majMotDePasse(int $id, string $hash): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE utilisateur SET mot_de_passe_hash = :hash, doit_changer_mdp = 0 WHERE id = :id'
        );
        return $stmt->execute([':hash' => $hash, ':id' => $id]);
    }
}
