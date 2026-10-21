<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Départements (table departement) et désignation du chef. */
final class Departement
{
    public static function tous(): array
    {
        return Database::all("SELECT d.id_departement, d.code, d.nom, d.description, p.nom AS parent, CONCAT(c.prenom, ' ', c.nom) AS chef,
                (SELECT COUNT(*) FROM employe e WHERE e.id_departement = d.id_departement) AS effectif
            FROM departement d LEFT JOIN departement p ON p.id_departement = d.id_departement_parent LEFT JOIN employe c ON c.id_employe = d.id_chef ORDER BY d.nom");
    }

    public static function trouver(int $id): ?array
    {
        return Database::one('SELECT * FROM departement WHERE id_departement = :i', ['i' => $id]);
    }

    public static function codePris(string $code, int $sauf): bool
    {
        return (bool) Database::value('SELECT COUNT(*) FROM departement WHERE code = :c AND id_departement <> :i', ['c' => $code, 'i' => $sauf]);
    }

    /** Liste [id => nom] des départements pouvant servir de parent. */
    public static function pourParent(int $sauf): array
    {
        $r = [];
        foreach (Database::all('SELECT id_departement, nom FROM departement WHERE id_departement <> :i ORDER BY nom', ['i' => $sauf]) as $d) { $r[$d['id_departement']] = $d['nom']; }
        return $r;
    }

    /** Employés qui peuvent être chef de ce département (membres actifs, ni RH, ni chef d'un autre département). */
    public static function candidatsChef(int $id): array
    {
        $r = [];
        foreach (Database::all("SELECT e.id_employe, e.prenom, e.nom FROM employe e JOIN utilisateur u ON u.id_utilisateur = e.id_utilisateur
                WHERE e.id_departement = :d AND e.statut = 'ACTIF' AND u.role IN ('EMPLOYE', 'CHEF_DEPARTEMENT', 'DIRECTEUR_GENERAL')
                  AND NOT EXISTS (SELECT 1 FROM departement x WHERE x.id_chef = e.id_employe AND x.id_departement <> :d2) ORDER BY e.nom, e.prenom", ['d' => $id, 'd2' => $id]) as $e) {
            $r[$e['id_employe']] = $e['prenom'] . ' ' . $e['nom'];
        }
        return $r;
    }

    /** Mettre $parent comme parent de $id créerait-il une boucle (A parent de B parent de A) ? */
    public static function creeUnCycle(int $id, int $parent): bool
    {
        $courant = $parent;
        for ($i = 0; $courant > 0 && $i < 50; $i++) {
            if ($courant === $id) { return true; }
            $courant = (int) Database::value('SELECT id_departement_parent FROM departement WHERE id_departement = :i', ['i' => $courant]);
        }
        return false;
    }

    public static function creer(array $d, int $auteur, ?string $ip): int
    {
        Database::run('INSERT INTO departement (code, nom, description, id_departement_parent) VALUES (:c, :n, :d, :p)', ['c' => $d['code'], 'n' => $d['nom'], 'd' => $d['description'], 'p' => $d['parent']]);
        $id = (int) Database::pdo()->lastInsertId();
        Journal::enregistrer($auteur, 'CREATION_DEPARTEMENT', 'departement', $id, $d['code'], $ip);
        return $id;
    }

    /** Met à jour le département. Le profil des chefs suit la désignation : nouveau chef => « Chef de département », ancien chef => « Employé ». */
    public static function mettreAJour(array $dep, array $d, int $auteur, ?string $ip): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            Database::run('UPDATE departement SET code = :c, nom = :n, description = :d, id_departement_parent = :p, id_chef = :ch WHERE id_departement = :i',
                ['c' => $d['code'], 'n' => $d['nom'], 'd' => $d['description'], 'p' => $d['parent'], 'ch' => $d['chef'], 'i' => $dep['id_departement']]);
            $ancien = $dep['id_chef'] === null ? null : (int) $dep['id_chef'];
            if ($d['chef'] !== null) {
                Database::run("UPDATE utilisateur u JOIN employe e ON e.id_utilisateur = u.id_utilisateur SET u.role = 'CHEF_DEPARTEMENT' WHERE e.id_employe = :e AND u.role = 'EMPLOYE'", ['e' => $d['chef']]);
            }
            if ($ancien !== null && $ancien !== $d['chef']) {
                Database::run("UPDATE utilisateur u JOIN employe e ON e.id_utilisateur = u.id_utilisateur SET u.role = 'EMPLOYE'
                               WHERE e.id_employe = :e AND u.role = 'CHEF_DEPARTEMENT' AND NOT EXISTS (SELECT 1 FROM departement x WHERE x.id_chef = :e2)", ['e' => $ancien, 'e2' => $ancien]);
            }
            Journal::enregistrer($auteur, 'MODIFICATION_DEPARTEMENT', 'departement', (int) $dep['id_departement'], $d['code'], $ip);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Supprime si le département est vide. Renvoie un message d'erreur, ou null si c'est fait. */
    public static function supprimer(array $dep, int $auteur, ?string $ip): ?string
    {
        $id = (int) $dep['id_departement'];
        if ((int) Database::value('SELECT COUNT(*) FROM employe WHERE id_departement = :i', ['i' => $id]) > 0) { return 'Ce département contient encore des employés : déplacez-les d’abord.'; }
        if ((int) Database::value('SELECT COUNT(*) FROM departement WHERE id_departement_parent = :i', ['i' => $id]) > 0) { return 'Ce département a des sous-départements.'; }
        Database::run('DELETE FROM departement WHERE id_departement = :i', ['i' => $id]);
        Journal::enregistrer($auteur, 'SUPPRESSION_DEPARTEMENT', 'departement', $id, $dep['code'], $ip);
        return null;
    }
}
