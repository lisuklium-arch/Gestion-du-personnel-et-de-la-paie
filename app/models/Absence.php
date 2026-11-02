<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\JoursFeries;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

/**
 * Absences marquées, pas un registre de présence.
 * Une date couverte par un congé approuvé, même d'une demi-journée, n'est pas une absence :
 * le chef ne peut pas la marquer, et la paie ne la compte pas.
 */
final class Absence
{
    /** @return list<string> dates ouvrables non justifiées du mois, format AAAA-MM-JJ */
    public static function nonJustifieesDuMois(int $idEmploye, int $mois, int $annee): array
    {
        if ($idEmploye <= 0 || $mois < 1 || $mois > 12 || $annee < 2000) {
            throw new InvalidArgumentException('Période ou employé invalide.');
        }
        $debut = sprintf('%04d-%02d-01', $annee, $mois);
        $fin = (new DateTimeImmutable($debut))->modify('last day of this month')->format('Y-m-d');
        $lignes = Database::all(
            'SELECT a.date_absence
             FROM absence a
             WHERE a.id_employe = :e
               AND a.date_absence BETWEEN :debut AND :fin
               AND a.justifiee = 0
               AND a.statut_justificatif <> \'ACCEPTE\'
               AND NOT EXISTS (
                   SELECT 1 FROM demande_conge d
                   WHERE d.id_employe = a.id_employe
                     AND d.statut = \'APPROUVE\'
                     AND a.date_absence BETWEEN d.date_debut AND d.date_fin
               )
             ORDER BY a.date_absence',
            ['e' => $idEmploye, 'debut' => $debut, 'fin' => $fin]
        );
        $feries = JoursFeries::dates(Database::all('SELECT date_ferie, recurrent FROM jour_ferie'), $debut, $fin);
        $dates = [];
        foreach ($lignes as $ligne) {
            $date = substr((string) $ligne['date_absence'], 0, 10);
            if (JoursFeries::estOuvrable($date, $feries)) {
                $dates[] = $date;
            }
        }
        return $dates;
    }

    /** @return list<array{id_employe:int|string,prenom:string,nom:string,matricule:string}> */
    public static function equipeDe(int $idUtilisateurChef): array
    {
        return Database::all(
            'SELECT e.id_employe, e.prenom, e.nom, e.matricule
             FROM employe e
             JOIN departement d ON d.id_departement = e.id_departement
             JOIN employe chef ON chef.id_employe = d.id_chef
             WHERE chef.id_utilisateur = :u AND e.id_employe <> chef.id_employe AND e.statut = \'ACTIF\'
             ORDER BY e.nom, e.prenom',
            ['u' => $idUtilisateurChef]
        );
    }

    public static function duChefEntre(int $idUtilisateurChef, string $debut, string $fin): array
    {
        return Database::all(
            'SELECT a.date_absence, a.justifiee, e.prenom, e.nom
             FROM absence a
             JOIN employe e ON e.id_employe = a.id_employe
             JOIN departement d ON d.id_departement = e.id_departement
             JOIN employe chef ON chef.id_employe = d.id_chef
             WHERE chef.id_utilisateur = :u AND a.date_absence BETWEEN :debut AND :fin
             ORDER BY a.date_absence, e.nom',
            ['u' => $idUtilisateurChef, 'debut' => $debut, 'fin' => $fin]
        );
    }

    public static function recentesDuChef(int $idUtilisateurChef): array
    {
        return Database::all(
            'SELECT a.date_absence, a.motif, a.justifiee, a.statut_justificatif, e.prenom, e.nom, e.matricule
             FROM absence a
             JOIN employe e ON e.id_employe = a.id_employe
             JOIN departement d ON d.id_departement = e.id_departement
             JOIN employe chef ON chef.id_employe = d.id_chef
             WHERE chef.id_utilisateur = :u
             ORDER BY a.date_absence DESC, a.id_absence DESC
             LIMIT 40',
            ['u' => $idUtilisateurChef]
        );
    }

    public static function marquer(int $idAuteur, int $idEmploye, string $date, string $motif, bool $justifiee): void
    {
        if ($idEmploye <= 0) {
            throw new DomainException('Choisissez un employé de votre équipe.');
        }
        $motif = trim($motif);
        if (mb_strlen($motif) > 255) {
            throw new DomainException('Le motif dépasse 255 caractères.');
        }
        if ($date > (new DateTimeImmutable('today'))->format('Y-m-d')) {
            throw new DomainException('Une absence ne se marque pas à l\'avance. Pour un jour futur, c\'est une demande de congé.');
        }
        $feries = JoursFeries::dates(Database::all('SELECT date_ferie, recurrent FROM jour_ferie'), $date, $date);
        if (!JoursFeries::estOuvrable($date, $feries)) {
            throw new DomainException('Cette date n\'est pas un jour ouvrable.');
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $employe = Database::one(
                'SELECT e.id_employe, e.statut, chef.id_utilisateur AS id_utilisateur_chef
                 FROM employe e
                 JOIN departement d ON d.id_departement = e.id_departement
                 LEFT JOIN employe chef ON chef.id_employe = d.id_chef
                 WHERE e.id_employe = :id FOR UPDATE',
                ['id' => $idEmploye]
            );
            if ($employe === null || (int) ($employe['id_utilisateur_chef'] ?? 0) !== $idAuteur || $employe['statut'] !== 'ACTIF') {
                throw new DomainException('Cet employé ne fait pas partie de votre équipe.');
            }
            $conge = Database::one(
                "SELECT id_demande_conge FROM demande_conge
                 WHERE id_employe = :e AND statut = 'APPROUVE' AND :d BETWEEN date_debut AND date_fin LIMIT 1",
                ['e' => $idEmploye, 'd' => $date]
            );
            if ($conge !== null) {
                throw new DomainException('Cette date est déjà couverte par un congé approuvé, même d\'une demi-journée.');
            }
            Database::run(
                'INSERT INTO absence (id_employe, date_absence, motif, justifiee, statut_justificatif, id_auteur)
                 VALUES (:e, :d, :m, :j, :s, :a)',
                [
                    'e' => $idEmploye,
                    'd' => $date,
                    'm' => $motif === '' ? null : $motif,
                    'j' => $justifiee ? 1 : 0,
                    's' => $justifiee ? 'ACCEPTE' : 'AUCUN',
                    'a' => $idAuteur,
                ]
            );
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
