<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Postes (table poste) ; les primes liées au poste sont en lecture seule ici (gérées dans les paramètres de paie). */
final class Poste
{
    public static function tous(): array
    {
        $primes = [];
        foreach (Database::all('SELECT rp.id_poste, t.libelle, rp.montant FROM rubrique_poste rp JOIN type_rubrique t ON t.id_type_rubrique = rp.id_type_rubrique ORDER BY t.libelle') as $r) {
            $primes[$r['id_poste']][] = $r['libelle'] . ' ' . montant($r['montant']);
        }
        $postes = Database::all('SELECT p.id_poste, p.intitule, p.description, (SELECT COUNT(*) FROM employe e WHERE e.id_poste = p.id_poste) AS effectif FROM poste p ORDER BY p.intitule');
        foreach ($postes as &$p) { $p['primes'] = $primes[$p['id_poste']] ?? []; }
        return $postes;
    }

    public static function trouver(int $id): ?array
    {
        return Database::one('SELECT * FROM poste WHERE id_poste = :i', ['i' => $id]);
    }

    public static function intitulePris(string $intitule, int $sauf): bool
    {
        return (bool) Database::value('SELECT COUNT(*) FROM poste WHERE intitule = :n AND id_poste <> :i', ['n' => $intitule, 'i' => $sauf]);
    }

    public static function creer(array $d, int $auteur, ?string $ip): int
    {
        Database::run('INSERT INTO poste (intitule, description) VALUES (:n, :d)', ['n' => $d['intitule'], 'd' => $d['description']]);
        $id = (int) Database::pdo()->lastInsertId();
        Journal::enregistrer($auteur, 'CREATION_POSTE', 'poste', $id, $d['intitule'], $ip);
        return $id;
    }

    public static function mettreAJour(array $p, array $d, int $auteur, ?string $ip): void
    {
        Database::run('UPDATE poste SET intitule = :n, description = :d WHERE id_poste = :i', ['n' => $d['intitule'], 'd' => $d['description'], 'i' => $p['id_poste']]);
        Journal::enregistrer($auteur, 'MODIFICATION_POSTE', 'poste', (int) $p['id_poste'], $d['intitule'], $ip);
    }

    public static function supprimer(array $p, int $auteur, ?string $ip): ?string
    {
        $id = (int) $p['id_poste'];
        if ((int) Database::value('SELECT COUNT(*) FROM employe WHERE id_poste = :i', ['i' => $id]) > 0) { return 'Ce poste est occupé par des employés.'; }
        if ((int) Database::value('SELECT COUNT(*) FROM rubrique_poste WHERE id_poste = :i', ['i' => $id]) > 0) { return 'Ce poste a des primes associées : retirez-les d’abord.'; }
        Database::run('DELETE FROM poste WHERE id_poste = :i', ['i' => $id]);
        Journal::enregistrer($auteur, 'SUPPRESSION_POSTE', 'poste', $id, $p['intitule'], $ip);
        return null;
    }
}
