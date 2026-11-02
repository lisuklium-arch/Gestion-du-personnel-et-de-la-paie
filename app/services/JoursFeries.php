<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use InvalidArgumentException;

/** Déplie les jours fériés, y compris ceux qui reviennent chaque année. */
final class JoursFeries
{
    /** @param list<array{date_ferie:string,recurrent:mixed}> $lignes */
    public static function dates(array $lignes, string $debut, string $fin): array
    {
        $depart = self::date($debut);
        $arrivee = self::date($fin);
        if ($depart > $arrivee) {
            throw new InvalidArgumentException('La date de début doit précéder la date de fin.');
        }

        $dates = [];
        $annees = range((int) $depart->format('Y'), (int) $arrivee->format('Y'));
        foreach ($lignes as $ligne) {
            $ferie = self::date((string) $ligne['date_ferie']);
            if ((int) $ligne['recurrent'] === 1) {
                foreach ($annees as $annee) {
                    $candidat = $ferie->setDate($annee, (int) $ferie->format('n'), (int) $ferie->format('j'));
                    if ($candidat->format('m-d') !== $ferie->format('m-d')) {
                        continue;
                    }
                    self::retenir($dates, $candidat->format('Y-m-d'), $debut, $fin);
                }
                continue;
            }
            self::retenir($dates, $ferie->format('Y-m-d'), $debut, $fin);
        }

        $liste = array_keys($dates);
        sort($liste);
        return $liste;
    }

    /** @param list<string> $feries */
    public static function estOuvrable(string $date, array $feries): bool
    {
        $jour = self::date($date);
        if ((int) $jour->format('N') >= 6) {
            return false;
        }
        return !in_array($jour->format('Y-m-d'), $feries, true);
    }

    /** @param array<string,true> $dates */
    private static function retenir(array &$dates, string $candidat, string $debut, string $fin): void
    {
        if ($candidat >= $debut && $candidat <= $fin) {
            $dates[$candidat] = true;
        }
    }

    private static function date(string $date): DateTimeImmutable
    {
        $valeur = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $erreurs = DateTimeImmutable::getLastErrors();
        if ($valeur === false || ($erreurs !== false && ($erreurs['warning_count'] > 0 || $erreurs['error_count'] > 0)) || $valeur->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Date invalide; format attendu: AAAA-MM-JJ.');
        }
        return $valeur;
    }
}
