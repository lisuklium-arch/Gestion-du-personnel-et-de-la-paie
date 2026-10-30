<?php

/** Départements, postes et types de congé : lectures et écritures SQL, sans règle métier. */
class Referentiel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ---- Départements

    public function listerDepartements(): array
    {
        return $this->pdo->query(
            'SELECT d.id, d.nom, d.description, d.parent_id, p.nom AS parent_nom, d.chef_id,
                    ec.prenom AS chef_prenom, ec.nom AS chef_nom,
                    (SELECT COUNT(*) FROM employe e WHERE e.departement_id = d.id) AS nb_employes
             FROM departement d
             LEFT JOIN departement p ON p.id = d.parent_id
             LEFT JOIN employe ec ON ec.id = d.chef_id
             ORDER BY d.nom'
        )->fetchAll();
    }

    public function trouverDepartement(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nom, description, parent_id, chef_id FROM departement WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $ligne = $stmt->fetch();
        return $ligne === false ? null : $ligne;
    }

    public function nomDepartementPris(string $nom, int $sauf = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM departement WHERE nom = :nom AND id <> :sauf');
        $stmt->execute([':nom' => $nom, ':sauf' => $sauf]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Département parent d'un département (null si racine ou inconnu). */
    public function parentDe(int $id): ?int
    {
        $stmt = $this->pdo->prepare('SELECT parent_id FROM departement WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $parent = $stmt->fetchColumn();
        return ($parent === false || $parent === null) ? null : (int) $parent;
    }

    public function creerDepartement(string $nom, ?string $description, ?int $parentId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO departement (nom, description, parent_id) VALUES (:nom, :description, :parent)'
        );
        $stmt->execute([':nom' => $nom, ':description' => $description, ':parent' => $parentId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function modifierDepartement(int $id, string $nom, ?string $description, ?int $parentId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE departement SET nom = :nom, description = :description, parent_id = :parent WHERE id = :id'
        );
        $stmt->execute([':nom' => $nom, ':description' => $description, ':parent' => $parentId, ':id' => $id]);
    }

    // ---- Postes

    public function listerPostes(): array
    {
        return $this->pdo->query(
            'SELECT p.id, p.intitule, p.description,
                    (SELECT COUNT(*) FROM employe e WHERE e.poste_id = p.id) AS nb_employes
             FROM poste p ORDER BY p.intitule'
        )->fetchAll();
    }

    public function trouverPoste(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, intitule, description FROM poste WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $ligne = $stmt->fetch();
        return $ligne === false ? null : $ligne;
    }

    public function intitulePris(string $intitule, int $sauf = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM poste WHERE intitule = :intitule AND id <> :sauf');
        $stmt->execute([':intitule' => $intitule, ':sauf' => $sauf]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function creerPoste(string $intitule, ?string $description): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO poste (intitule, description) VALUES (:intitule, :description)');
        $stmt->execute([':intitule' => $intitule, ':description' => $description]);
        return (int) $this->pdo->lastInsertId();
    }

    public function modifierPoste(int $id, string $intitule, ?string $description): void
    {
        $stmt = $this->pdo->prepare('UPDATE poste SET intitule = :intitule, description = :description WHERE id = :id');
        $stmt->execute([':intitule' => $intitule, ':description' => $description, ':id' => $id]);
    }

    // ---- Types de congé

    public function listerTypesConge(): array
    {
        return $this->pdo->query(
            'SELECT id, code, libelle, decompte_solde, droit_annuel_jours, justificatif_requis, actif
             FROM type_conge ORDER BY libelle'
        )->fetchAll();
    }

    public function trouverTypeConge(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code, libelle, decompte_solde, droit_annuel_jours, justificatif_requis, actif
             FROM type_conge WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $ligne = $stmt->fetch();
        return $ligne === false ? null : $ligne;
    }

    public function codeCongePris(string $code, int $sauf = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM type_conge WHERE code = :code AND id <> :sauf');
        $stmt->execute([':code' => $code, ':sauf' => $sauf]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function creerTypeConge(string $code, string $libelle, int $decompte, string $droit, int $justificatif, int $actif): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO type_conge (code, libelle, decompte_solde, droit_annuel_jours, justificatif_requis, actif)
             VALUES (:code, :libelle, :decompte, :droit, :justificatif, :actif)'
        );
        $stmt->execute([
            ':code' => $code, ':libelle' => $libelle, ':decompte' => $decompte,
            ':droit' => $droit, ':justificatif' => $justificatif, ':actif' => $actif,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** Le code n'est pas modifiable : il identifie le type dans les demandes et les soldes. */
    public function modifierTypeConge(int $id, string $libelle, int $decompte, string $droit, int $justificatif, int $actif): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE type_conge SET libelle = :libelle, decompte_solde = :decompte, droit_annuel_jours = :droit,
                    justificatif_requis = :justificatif, actif = :actif WHERE id = :id'
        );
        $stmt->execute([
            ':libelle' => $libelle, ':decompte' => $decompte, ':droit' => $droit,
            ':justificatif' => $justificatif, ':actif' => $actif, ':id' => $id,
        ]);
    }

    // ---- Suppressions et contrôles d'utilisation

    private function compter(string $sql, int $id): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    public function compterEmployesDepartement(int $id): int
    {
        return $this->compter('SELECT COUNT(*) FROM employe WHERE departement_id = :id', $id);
    }

    public function compterSousDepartements(int $id): int
    {
        return $this->compter('SELECT COUNT(*) FROM departement WHERE parent_id = :id', $id);
    }

    public function compterEmployesPoste(int $id): int
    {
        return $this->compter('SELECT COUNT(*) FROM employe WHERE poste_id = :id', $id);
    }

    public function compterRubriquesPoste(int $id): int
    {
        return $this->compter('SELECT COUNT(*) FROM poste_rubrique WHERE poste_id = :id', $id);
    }

    public function compterUsagesTypeConge(int $id): int
    {
        return $this->compter('SELECT COUNT(*) FROM solde_conge WHERE type_conge_id = :id', $id)
             + $this->compter('SELECT COUNT(*) FROM demande_conge WHERE type_conge_id = :id', $id);
    }

    public function supprimerDepartement(int $id): void
    {
        $this->pdo->prepare('DELETE FROM departement WHERE id = :id')->execute([':id' => $id]);
    }

    public function supprimerPoste(int $id): void
    {
        $this->pdo->prepare('DELETE FROM poste WHERE id = :id')->execute([':id' => $id]);
    }

    public function supprimerTypeConge(int $id): void
    {
        $this->pdo->prepare('DELETE FROM type_conge WHERE id = :id')->execute([':id' => $id]);
    }
}
