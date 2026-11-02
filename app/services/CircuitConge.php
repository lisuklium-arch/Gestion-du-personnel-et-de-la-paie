<?php
declare(strict_types=1);

namespace App\Services;

use DomainException;
use InvalidArgumentException;

/**
 * Circuit des congés, sans base.
 * L'avis du chef, même défavorable, ne libère pas la réserve : la RH décide.
 * Un responsable RH ne tranche pas sa propre demande ; après un avis RH favorable, le DG décide.
 * L'avis du chef est sauté s'il est le demandeur, s'il n'existe pas,
 * ou s'il est le DG qui tranchera de toute façon le congé d'un RH.
 */
final class CircuitConge
{
    public static function avisRequis(bool $chefExiste, bool $demandeurEstLeChef, bool $chefEstDirecteur, bool $demandeurEstRh): bool
    {
        if (!$chefExiste || $demandeurEstLeChef) {
            return false;
        }
        return !($chefEstDirecteur && $demandeurEstRh);
    }

    public static function statutAuDepot(bool $avisRequis): string
    {
        return $avisRequis ? 'EN_ATTENTE_AVIS' : 'EN_ATTENTE_RH';
    }

    /** @return list<array{cle:string,libelle:string}> */
    public static function etapes(bool $avisRequis, bool $demandeurEstRh): array
    {
        $etapes = [];
        if ($avisRequis) {
            $etapes[] = ['cle' => 'AVIS_CHEF', 'libelle' => 'Avis du chef de département'];
        }
        $etapes[] = ['cle' => 'DECISION_RH', 'libelle' => $demandeurEstRh ? 'Instruction d\'un autre responsable RH' : 'Décision du responsable RH'];
        if ($demandeurEstRh) {
            $etapes[] = ['cle' => 'DECISION_DG', 'libelle' => 'Décision du directeur général'];
        }
        return $etapes;
    }

    public static function prochaine(string $statut, bool $demandeurEstRh): ?string
    {
        return match ($statut) {
            'EN_ATTENTE_AVIS' => 'Avis du chef de département',
            'EN_ATTENTE_RH' => $demandeurEstRh ? 'Instruction d\'un autre responsable RH' : 'Décision du responsable RH',
            'EN_ATTENTE_DG' => 'Décision du directeur général',
            default => null,
        };
    }

    /**
     * @return array{statut:string,mouvement:?string}
     */
    public static function transition(string $statut, string $etape, string $decision, bool $demandeurEstRh, bool $decompte): array
    {
        if (!in_array($decision, ['APPROUVE', 'REFUSE'], true)) {
            throw new InvalidArgumentException('Décision invalide.');
        }
        if ($statut === 'EN_ATTENTE_AVIS' && $etape === 'AVIS_CHEF') {
            return ['statut' => 'EN_ATTENTE_RH', 'mouvement' => null];
        }
        if ($statut === 'EN_ATTENTE_RH' && $etape === 'DECISION_RH') {
            if ($decision === 'REFUSE') {
                return ['statut' => 'REFUSE', 'mouvement' => $decompte ? 'LIBERATION' : null];
            }
            if ($demandeurEstRh) {
                return ['statut' => 'EN_ATTENTE_DG', 'mouvement' => null];
            }
            return ['statut' => 'APPROUVE', 'mouvement' => $decompte ? 'APPROBATION' : null];
        }
        if ($statut === 'EN_ATTENTE_DG' && $etape === 'DECISION_DG') {
            return $decision === 'REFUSE'
                ? ['statut' => 'REFUSE', 'mouvement' => $decompte ? 'LIBERATION' : null]
                : ['statut' => 'APPROUVE', 'mouvement' => $decompte ? 'APPROBATION' : null];
        }
        throw new DomainException('Cette demande n\'attend pas cette décision.');
    }

    /** @return array{statut:string,mouvement:?string} */
    public static function annulationEmploye(string $statut, bool $decompte): array
    {
        if (!in_array($statut, ['EN_ATTENTE_AVIS', 'EN_ATTENTE_RH', 'EN_ATTENTE_DG'], true)) {
            throw new DomainException('Cette demande ne peut plus être annulée.');
        }
        return ['statut' => 'ANNULE', 'mouvement' => $decompte ? 'LIBERATION' : null];
    }

    /** @return array{statut:string,mouvement:?string} */
    public static function annulationRh(string $statut, bool $decompte, bool $pasEncoreCommence): array
    {
        if ($statut !== 'APPROUVE' || !$pasEncoreCommence) {
            throw new DomainException('Seul un congé approuvé dont le premier jour n\'est pas encore arrivé peut être annulé.');
        }
        return ['statut' => 'ANNULE', 'mouvement' => $decompte ? 'RESTITUTION' : null];
    }
}
