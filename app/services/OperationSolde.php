<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * Forme d'un mouvement et contrôle du stock, sans base de données.
 * L'approbation transfère la réserve vers le pris : elle ne débite pas une seconde fois.
 */
final class OperationSolde
{
    /** @param array{delta_acquis?:string,delta_reserve?:string,delta_pris?:string} $correction */
    public static function deltas(string $type, string $quantite = '0.0', array $correction = []): array
    {
        return match ($type) {
            'ACQUISITION' => self::ligne(QuantiteJours::positif($quantite), '0.0', '0.0'),
            'RESERVATION' => self::ligne('0.0', QuantiteJours::positif($quantite), '0.0'),
            'LIBERATION' => self::ligne('0.0', QuantiteJours::negatif(QuantiteJours::positif($quantite)), '0.0'),
            'APPROBATION' => self::approbation($quantite),
            'RESTITUTION' => self::ligne('0.0', '0.0', QuantiteJours::negatif(QuantiteJours::positif($quantite))),
            'CORRECTION' => self::correction($correction),
            default => throw new InvalidArgumentException('Type de mouvement inconnu.'),
        };
    }

    /** @param array{jours_acquis:string,jours_reserves:string,jours_pris:string} $totaux */
    public static function verifier(string $type, string $quantite, array $totaux, array $deltas): void
    {
        $acquis = QuantiteJours::depuisNombre($totaux['jours_acquis']);
        $reserves = QuantiteJours::depuisNombre($totaux['jours_reserves']);
        $pris = QuantiteJours::depuisNombre($totaux['jours_pris']);

        if ($type === 'RESERVATION') {
            $q = QuantiteJours::positif($quantite);
            if (QuantiteJours::comparer(QuantiteJours::disponible($acquis, $reserves, $pris), $q) < 0) {
                throw new SoldeInsuffisant('Solde insuffisant pour réserver ces jours.');
            }
        }
        if ($type === 'LIBERATION' || $type === 'APPROBATION') {
            $q = QuantiteJours::positif($quantite);
            if (QuantiteJours::comparer($reserves, $q) < 0) {
                throw new SoldeInsuffisant('Réserve insuffisante pour cette opération.');
            }
        }
        if ($type === 'RESTITUTION' && QuantiteJours::comparer($pris, QuantiteJours::positif($quantite)) < 0) {
            throw new SoldeInsuffisant('Jours pris insuffisants pour restituer.');
        }

        foreach (
            [
                'jours_acquis' => [$acquis, $deltas['delta_acquis']],
                'jours_reserves' => [$reserves, $deltas['delta_reserve']],
                'jours_pris' => [$pris, $deltas['delta_pris']],
            ] as [$stock, $delta]
        ) {
            if (QuantiteJours::comparer(QuantiteJours::ajouter($stock, $delta), '0.0') < 0) {
                throw new SoldeInsuffisant('Un stock de congé ne peut pas devenir négatif.');
            }
        }
    }

    private static function approbation(string $quantite): array
    {
        $q = QuantiteJours::positif($quantite);
        return self::ligne('0.0', QuantiteJours::negatif($q), $q);
    }

    /** @param array{delta_acquis?:string,delta_reserve?:string,delta_pris?:string} $correction */
    private static function correction(array $correction): array
    {
        $deltas = self::ligne(
            QuantiteJours::normaliser((string) ($correction['delta_acquis'] ?? '0.0')),
            QuantiteJours::normaliser((string) ($correction['delta_reserve'] ?? '0.0')),
            QuantiteJours::normaliser((string) ($correction['delta_pris'] ?? '0.0'))
        );
        if ($deltas['delta_acquis'] === '0.0' && $deltas['delta_reserve'] === '0.0' && $deltas['delta_pris'] === '0.0') {
            throw new InvalidArgumentException('Une correction doit modifier au moins un stock.');
        }
        return $deltas;
    }

    private static function ligne(string $acquis, string $reserve, string $pris): array
    {
        return [
            'delta_acquis' => $acquis,
            'delta_reserve' => $reserve,
            'delta_pris' => $pris,
        ];
    }
}
