<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\MouvementSoldeConge;
use InvalidArgumentException;

final class SoldeCongeService
{
    public static function resumer(int $idSolde): array
    {
        $totaux = MouvementSoldeConge::totauxParSolde($idSolde);

        return [
            'jours_acquis' => $totaux['jours_acquis'],
            'jours_reserves' => $totaux['jours_reserves'],
            'jours_pris' => $totaux['jours_pris'],
            'jours_disponibles' => self::calculerDisponible(
                $totaux['jours_acquis'],
                $totaux['jours_reserves'],
                $totaux['jours_pris']
            ),
        ];
    }

    public static function calculerDisponible(string $acquis, string $reserves, string $pris): string
    {
        return self::formaterDemiJournees(
            self::enDemiJournees($acquis)
            - self::enDemiJournees($reserves)
            - self::enDemiJournees($pris)
        );
    }

    private static function enDemiJournees(string $jours): int
    {
        if (!preg_match('/^(-?)(\d+)(?:\.(\d))?$/D', $jours, $parties)) {
            throw new InvalidArgumentException('Valeur de jours invalide.');
        }

        $fraction = $parties[3] ?? '0';
        if ($fraction !== '0' && $fraction !== '5') {
            throw new InvalidArgumentException('Les soldes doivent etre exprimes par demi-journee.');
        }

        $demiJournees = ((int) $parties[2] * 2) + ($fraction === '5' ? 1 : 0);
        return ($parties[1] ?? '') === '-' ? -$demiJournees : $demiJournees;
    }

    private static function formaterDemiJournees(int $demiJournees): string
    {
        $signe = $demiJournees < 0 ? '-' : '';
        $valeur = abs($demiJournees);
        return $signe . intdiv($valeur, 2) . (($valeur % 2) === 1 ? '.5' : '.0');
    }
}