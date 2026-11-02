<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use InvalidArgumentException;

final class CalculateurJoursOuvrables
{
    private const PERIODES = ['MATIN', 'APRES_MIDI'];

    public static function calculer(
        string $dateDebut,
        string $dateFin,
        array $joursFeries = [],
        string $periodeDebut = 'MATIN',
        string $periodeFin = 'APRES_MIDI'
    ): string {
        $debut = self::lireDate($dateDebut);
        $fin = self::lireDate($dateFin);

        if ($debut > $fin) {
            throw new InvalidArgumentException('La date de debut doit preceder la date de fin.');
        }
        if ($debut->format('Y') !== $fin->format('Y')) {
            throw new InvalidArgumentException('Une demande ne peut pas traverser deux annees.');
        }
        if (!in_array($periodeDebut, self::PERIODES, true) || !in_array($periodeFin, self::PERIODES, true)) {
            throw new InvalidArgumentException('Periode de demi-journee invalide.');
        }
        if ($debut == $fin && $periodeDebut === 'APRES_MIDI' && $periodeFin === 'MATIN') {
            throw new InvalidArgumentException('Les periodes de la journee sont inversees.');
        }

        $feries = [];
        foreach ($joursFeries as $jourFerie) {
            $feries[self::lireDate($jourFerie)->format('Y-m-d')] = true;
        }

        $demiJournees = 0;
        for ($jour = $debut; $jour <= $fin; $jour = $jour->modify('+1 day')) {
            $cle = $jour->format('Y-m-d');
            if ((int) $jour->format('N') >= 6 || isset($feries[$cle])) {
                continue;
            }

            $matinInclus = $jour > $debut || $periodeDebut === 'MATIN';
            $apresMidiIncluse = $jour < $fin || $periodeFin === 'APRES_MIDI';
            $demiJournees += (int) $matinInclus + (int) $apresMidiIncluse;
        }

        if ($demiJournees === 0) {
            throw new InvalidArgumentException('La periode ne contient aucun jour ouvrable.');
        }

        return intdiv($demiJournees, 2) . (($demiJournees % 2) === 1 ? '.5' : '.0');
    }

    private static function lireDate(string $date): DateTimeImmutable
    {
        $valeur = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $erreurs = DateTimeImmutable::getLastErrors();

        if ($valeur === false || ($erreurs !== false && ($erreurs['warning_count'] > 0 || $erreurs['error_count'] > 0)) || $valeur->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Date invalide; format attendu: AAAA-MM-JJ.');
        }

        return $valeur;
    }
}