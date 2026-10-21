<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Contrats de travail (table contrat). Un employé a UN seul contrat en cours ; un nouveau contrat clôt le précédent. */
final class Contrat
{
    public static function enCours(): array
    {
        return Database::all('SELECT c.reference, c.type_contrat, c.date_debut, c.date_fin, c.salaire_base, e.id_employe, e.matricule, e.prenom, e.nom,
                DATEDIFF(c.date_fin, CURDATE()) AS jours
            FROM contrat c JOIN employe e ON e.id_employe = c.id_employe WHERE c.actif = 1 ORDER BY (c.date_fin IS NULL), c.date_fin, e.nom');
    }

    public static function actifDe(int $idEmploye): ?array
    {
        return Database::one('SELECT * FROM contrat WHERE id_employe = :e AND actif = 1', ['e' => $idEmploye]);
    }

    public static function creer(array $d, int $auteur, ?string $ip): string
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $veille = date('Y-m-d', strtotime($d['date_debut'] . ' -1 day'));
            Database::run('UPDATE contrat SET actif = 0, date_fin = CASE WHEN date_fin IS NULL OR date_fin > :v THEN :v2 ELSE date_fin END WHERE id_employe = :e AND actif = 1',
                ['v' => $veille, 'v2' => $veille, 'e' => $d['id_employe']]);
            $reference = sprintf('CT-%04d', 1 + (int) Database::value('SELECT COALESCE(MAX(CAST(SUBSTRING(reference, 4) AS UNSIGNED)), 0) FROM contrat'));
            Database::run('INSERT INTO contrat (id_employe, reference, type_contrat, date_debut, date_fin, salaire_base, duree_mensuelle_heures) VALUES (:e, :r, :t, :d, :f, :s, :h)',
                ['e' => $d['id_employe'], 'r' => $reference, 't' => $d['type_contrat'], 'd' => $d['date_debut'], 'f' => $d['date_fin'], 's' => $d['salaire_base'], 'h' => $d['duree']]);
            Journal::enregistrer($auteur, 'CREATION_CONTRAT', 'contrat', (int) $pdo->lastInsertId(), $reference, $ip);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return $reference;
    }
}
