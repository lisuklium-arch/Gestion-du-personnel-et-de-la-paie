<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;

final class MouvementSoldeConge
{
    public static function totauxParSolde(int $idSolde): array
    {
        if ($idSolde <= 0) {
            throw new InvalidArgumentException('Identifiant de solde invalide.');
        }

        return Database::one(
            'SELECT CAST(COALESCE(SUM(delta_acquis), 0) AS CHAR) AS jours_acquis,
                    CAST(COALESCE(SUM(delta_reserve), 0) AS CHAR) AS jours_reserves,
                    CAST(COALESCE(SUM(delta_pris), 0) AS CHAR) AS jours_pris
             FROM mouvement_solde_conge
             WHERE id_solde = :id_solde',
            ['id_solde' => $idSolde]
        ) ?? ['jours_acquis' => '0.0', 'jours_reserves' => '0.0', 'jours_pris' => '0.0'];
    }
}