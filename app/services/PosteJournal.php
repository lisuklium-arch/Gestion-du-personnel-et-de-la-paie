<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/** Décide si une référence est nouvelle, déjà passée à l'identique, ou en conflit. */
final class PosteJournal
{
    /**
     * @param array{id_solde:int,id_demande_conge:?int,type:string,delta_acquis:string,delta_reserve:string,delta_pris:string}|null $existant
     * @param array{delta_acquis:string,delta_reserve:string,delta_pris:string} $deltas
     */
    public static function decider(?array $existant, int $idSolde, ?int $idDemande, string $type, array $deltas): string
    {
        if ($existant === null) {
            return 'inserer';
        }
        $identique = $existant['id_solde'] === $idSolde
            && $existant['id_demande_conge'] === $idDemande
            && $existant['type'] === $type
            && $existant['delta_acquis'] === $deltas['delta_acquis']
            && $existant['delta_reserve'] === $deltas['delta_reserve']
            && $existant['delta_pris'] === $deltas['delta_pris'];
        if ($identique) {
            return 'deja';
        }
        throw new InvalidArgumentException('Référence de journal déjà utilisée pour un autre mouvement.');
    }
}
