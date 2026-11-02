<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DomainException;
use InvalidArgumentException;

/**
 * Contrat publié aux autres domaines.
 * joursOuvrables(debut, fin) et soldeDisponible(employeId, typeId, annee).
 */
final class SoldeService
{
    public static function joursOuvrables(
        string $debut,
        string $fin,
        string $periodeDebut = 'MATIN',
        string $periodeFin = 'APRES_MIDI'
    ): string {
        $feries = JoursFeries::dates(
            Database::all('SELECT date_ferie, recurrent FROM jour_ferie'),
            $debut,
            $fin
        );
        return CalculateurJoursOuvrables::calculer($debut, $fin, $feries, $periodeDebut, $periodeFin);
    }

    public static function soldeDisponible(int $idEmploye, int $idType, int $annee): string
    {
        if ($idEmploye <= 0 || $idType <= 0 || $annee < 2000) {
            throw new InvalidArgumentException('Employé, type ou année invalide.');
        }
        $type = Database::one(
            'SELECT decompte_solde FROM type_conge WHERE id_type_conge = :id AND actif = 1',
            ['id' => $idType]
        );
        if ($type === null) {
            throw new InvalidArgumentException('Type de congé introuvable.');
        }
        if ((int) $type['decompte_solde'] !== 1) {
            throw new DomainException('Ce type de congé ne décompte pas le solde.');
        }
        $solde = Database::one(
            'SELECT id_solde FROM solde_conge WHERE id_employe = :e AND id_type_conge = :t AND annee = :a',
            ['e' => $idEmploye, 't' => $idType, 'a' => $annee]
        );
        if ($solde === null) {
            return '0.0';
        }
        return SoldeCongeService::resumer((int) $solde['id_solde'])['jours_disponibles'];
    }
}
