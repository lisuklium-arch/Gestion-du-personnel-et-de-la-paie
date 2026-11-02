<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/** Jours de congé en demi-journées entières. Aucun arrondi silencieux. */
final class QuantiteJours
{
    public static function normaliser(string $jours): string
    {
        $jours = trim($jours);
        if (!preg_match('/^(-?)(\d+)(?:\.(\d))?$/D', $jours, $parties)) {
            throw new InvalidArgumentException('Valeur de jours invalide.');
        }
        $fraction = $parties[3] ?? '0';
        if ($fraction !== '0' && $fraction !== '5') {
            throw new InvalidArgumentException('Les soldes doivent être exprimés par demi-journée.');
        }
        $demi = ((int) $parties[2] * 2) + ($fraction === '5' ? 1 : 0);
        if (($parties[1] ?? '') === '-') {
            $demi = -$demi;
        }
        return self::formater($demi);
    }

    public static function depuisNombre(float|string $jours): string
    {
        if (is_string($jours)) {
            $jours = trim($jours);
            if ($jours === '' || !is_numeric($jours)) {
                throw new InvalidArgumentException('Valeur de jours invalide.');
            }
        }
        $valeur = (float) $jours;
        $demi = (int) round($valeur * 2);
        if (abs(($valeur * 2) - $demi) > 0.00001) {
            throw new InvalidArgumentException('Les soldes doivent être exprimés par demi-journée.');
        }
        return self::formater($demi);
    }

    public static function positif(string $jours): string
    {
        $normalise = self::normaliser($jours);
        if (self::enDemiJournees($normalise) <= 0) {
            throw new InvalidArgumentException('La quantité de jours doit être strictement positive.');
        }
        return $normalise;
    }

    public static function negatif(string $jours): string
    {
        return self::formater(-self::enDemiJournees(self::normaliser($jours)));
    }

    public static function ajouter(string $a, string $b): string
    {
        return self::formater(self::enDemiJournees($a) + self::enDemiJournees($b));
    }

    public static function comparer(string $a, string $b): int
    {
        return self::enDemiJournees($a) <=> self::enDemiJournees($b);
    }

    public static function disponible(string $acquis, string $reserves, string $pris): string
    {
        return self::formater(
            self::enDemiJournees($acquis) - self::enDemiJournees($reserves) - self::enDemiJournees($pris)
        );
    }

    public static function enDemiJournees(string $jours): int
    {
        $normalise = self::normaliser($jours);
        if (!preg_match('/^(-?)(\d+)(?:\.(\d))?$/D', $normalise, $parties)) {
            throw new InvalidArgumentException('Valeur de jours invalide.');
        }
        $demi = ((int) $parties[2] * 2) + (($parties[3] ?? '0') === '5' ? 1 : 0);
        return ($parties[1] ?? '') === '-' ? -$demi : $demi;
    }

    public static function formater(int $demiJournees): string
    {
        $signe = $demiJournees < 0 ? '-' : '';
        $valeur = abs($demiJournees);
        return $signe . intdiv($valeur, 2) . (($valeur % 2) === 1 ? '.5' : '.0');
    }
}
