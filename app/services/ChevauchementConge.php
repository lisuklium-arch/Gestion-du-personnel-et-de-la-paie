<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;

/** Deux demandes se chevauchent si elles partagent au moins une demi-journée civile. */
final class ChevauchementConge
{
    public static function seChevauchent(
        string $debutA,
        string $finA,
        string $periodeDebutA,
        string $periodeFinA,
        string $debutB,
        string $finB,
        string $periodeDebutB,
        string $periodeFinB
    ): bool {
        if ($finA < $debutB || $finB < $debutA) {
            return false;
        }
        $jour = new DateTimeImmutable(max($debutA, $debutB));
        $dernier = new DateTimeImmutable(min($finA, $finB));
        for (; $jour <= $dernier; $jour = $jour->modify('+1 day')) {
            $cle = $jour->format('Y-m-d');
            $matin = self::contient($cle, $debutA, $finA, $periodeDebutA, $periodeFinA, 'MATIN')
                && self::contient($cle, $debutB, $finB, $periodeDebutB, $periodeFinB, 'MATIN');
            $apresMidi = self::contient($cle, $debutA, $finA, $periodeDebutA, $periodeFinA, 'APRES_MIDI')
                && self::contient($cle, $debutB, $finB, $periodeDebutB, $periodeFinB, 'APRES_MIDI');
            if ($matin || $apresMidi) {
                return true;
            }
        }
        return false;
    }

    private static function contient(string $jour, string $debut, string $fin, string $periodeDebut, string $periodeFin, string $demi): bool
    {
        if ($jour < $debut || $jour > $fin) {
            return false;
        }
        if ($demi === 'MATIN') {
            return $jour > $debut || $periodeDebut === 'MATIN';
        }
        return $jour < $fin || $periodeFin === 'APRES_MIDI';
    }
}
