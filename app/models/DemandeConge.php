<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Lectures des demandes de congé. Les écritures passent par DemandeCongeService. */
final class DemandeConge
{
    private const SELECT = 'SELECT d.id_demande_conge, d.id_employe, d.id_type_conge, d.date_debut, d.date_fin,
            d.periode_debut, d.periode_fin, CAST(d.nb_jours_ouvres AS CHAR) AS nb_jours_ouvres,
            d.motif, d.justificatif, d.statut, d.date_depot,
            t.libelle AS type_libelle, t.code AS type_code, t.decompte_solde, t.justificatif_requis,
            e.nom, e.prenom, e.matricule, e.id_utilisateur, e.statut AS statut_employe,
            u.role AS role_demandeur,
            dep.nom AS departement, dep.id_chef,
            chef.id_utilisateur AS id_utilisateur_chef, uc.role AS role_chef
        FROM demande_conge d
        JOIN type_conge t ON t.id_type_conge = d.id_type_conge
        JOIN employe e ON e.id_employe = d.id_employe
        JOIN utilisateur u ON u.id_utilisateur = e.id_utilisateur
        JOIN departement dep ON dep.id_departement = e.id_departement
        LEFT JOIN employe chef ON chef.id_employe = dep.id_chef
        LEFT JOIN utilisateur uc ON uc.id_utilisateur = chef.id_utilisateur';

    public static function trouver(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        return Database::one(self::SELECT . ' WHERE d.id_demande_conge = :id', ['id' => $id]);
    }

    /** Ligne verrouillée pour une décision. À appeler dans une transaction. */
    public static function verrouiller(int $id): ?array
    {
        return Database::one(
            'SELECT id_demande_conge, id_employe, id_type_conge, date_debut, date_fin,
                    CAST(nb_jours_ouvres AS CHAR) AS nb_jours_ouvres, statut
             FROM demande_conge WHERE id_demande_conge = :id FOR UPDATE',
            ['id' => $id]
        );
    }

    public static function planningChef(int $idUtilisateur, string $debut, string $fin): array
    {
        return Database::all(
            self::SELECT . " WHERE chef.id_utilisateur = :u
                AND d.statut NOT IN ('REFUSE', 'ANNULE')
                AND d.date_debut <= :fin AND d.date_fin >= :debut
                ORDER BY e.nom, e.prenom, d.date_debut",
            ['u' => $idUtilisateur, 'debut' => $debut, 'fin' => $fin]
        );
    }

    public static function deEmploye(int $idEmploye): array
    {
        return Database::all(
            self::SELECT . ' WHERE d.id_employe = :id ORDER BY d.date_depot DESC LIMIT 100',
            ['id' => $idEmploye]
        );
    }

    public static function deChef(int $idUtilisateurChef): array
    {
        return Database::all(
            self::SELECT . " WHERE chef.id_utilisateur = :id
                ORDER BY FIELD(d.statut, 'EN_ATTENTE_AVIS', 'EN_ATTENTE_RH', 'EN_ATTENTE_DG', 'APPROUVE', 'REFUSE', 'ANNULE'), d.date_depot DESC
                LIMIT 100",
            ['id' => $idUtilisateurChef]
        );
    }

    public static function toutes(): array
    {
        return Database::all(
            self::SELECT . " ORDER BY FIELD(d.statut, 'EN_ATTENTE_RH', 'EN_ATTENTE_DG', 'EN_ATTENTE_AVIS', 'APPROUVE', 'REFUSE', 'ANNULE'), d.date_depot DESC
                LIMIT 200"
        );
    }

    public static function desResponsablesRh(): array
    {
        return Database::all(
            self::SELECT . " WHERE u.role = 'RESPONSABLE_RH'
                ORDER BY FIELD(d.statut, 'EN_ATTENTE_DG', 'EN_ATTENTE_RH', 'EN_ATTENTE_AVIS', 'APPROUVE', 'REFUSE', 'ANNULE'), d.date_depot DESC
                LIMIT 100"
        );
    }

    public static function contexteEmploye(int $idEmploye): ?array
    {
        if ($idEmploye <= 0) {
            return null;
        }
        return Database::one(
            'SELECT e.id_employe, e.id_utilisateur, e.statut, e.nom, e.prenom, e.matricule,
                    u.role AS role_demandeur, dep.id_chef, dep.nom AS departement,
                    chef.id_utilisateur AS id_utilisateur_chef, uc.role AS role_chef
             FROM employe e
             JOIN utilisateur u ON u.id_utilisateur = e.id_utilisateur
             JOIN departement dep ON dep.id_departement = e.id_departement
             LEFT JOIN employe chef ON chef.id_employe = dep.id_chef
             LEFT JOIN utilisateur uc ON uc.id_utilisateur = chef.id_utilisateur
             WHERE e.id_employe = :id',
            ['id' => $idEmploye]
        );
    }

    public static function typesActifs(): array
    {
        return Database::all(
            'SELECT id_type_conge, code, libelle, CAST(jours_par_an AS CHAR) AS jours_par_an,
                    decompte_solde, justificatif_requis
             FROM type_conge WHERE actif = 1 ORDER BY id_type_conge'
        );
    }

    public static function typeActif(int $id): ?array
    {
        return Database::one(
            'SELECT id_type_conge, code, libelle, decompte_solde, justificatif_requis
             FROM type_conge WHERE id_type_conge = :id AND actif = 1',
            ['id' => $id]
        );
    }

    /** @return list<array{date_debut:string,date_fin:string,periode_debut:string,periode_fin:string}> */
    public static function periodesOccupees(int $idEmploye): array
    {
        return Database::all(
            "SELECT date_debut, date_fin, periode_debut, periode_fin
             FROM demande_conge
             WHERE id_employe = :id AND statut NOT IN ('REFUSE', 'ANNULE')",
            ['id' => $idEmploye]
        );
    }

    /** @return list<int> */
    public static function responsablesRhSauf(int $idUtilisateur): array
    {
        return self::idsRole('RESPONSABLE_RH', $idUtilisateur);
    }

    /** @return list<int> */
    public static function directeurs(): array
    {
        return self::idsRole('DIRECTEUR_GENERAL', 0);
    }

    public static function validations(int $idDemande): array
    {
        return Database::all(
            'SELECT v.etape, v.decision, v.commentaire, v.date_validation, e.prenom, e.nom, u.role
             FROM validation v
             JOIN utilisateur u ON u.id_utilisateur = v.id_valideur
             JOIN employe e ON e.id_utilisateur = u.id_utilisateur
             WHERE v.id_demande_conge = :id
             ORDER BY v.date_validation, v.id_validation',
            ['id' => $idDemande]
        );
    }

    public static function mouvements(int $idDemande): array
    {
        return Database::all(
            'SELECT type_mouvement, CAST(delta_acquis AS CHAR) AS delta_acquis,
                    CAST(delta_reserve AS CHAR) AS delta_reserve, CAST(delta_pris AS CHAR) AS delta_pris,
                    reference_operation, motif, date_mouvement
             FROM mouvement_solde_conge
             WHERE id_demande_conge = :id
             ORDER BY date_mouvement, id_mouvement',
            ['id' => $idDemande]
        );
    }

    public static function visible(array $demande, array $user): bool
    {
        if ((int) $demande['id_utilisateur'] === (int) $user['id_utilisateur']) {
            return true;
        }
        return match ($user['role'] ?? '') {
            'RESPONSABLE_RH' => true,
            'CHEF_DEPARTEMENT' => (int) ($demande['id_utilisateur_chef'] ?? 0) === (int) $user['id_utilisateur'],
            'DIRECTEUR_GENERAL' => ($demande['role_demandeur'] ?? '') === 'RESPONSABLE_RH',
            default => false,
        };
    }

    /** @return list<int> */
    private static function idsRole(string $role, int $sauf): array
    {
        $lignes = Database::all(
            'SELECT id_utilisateur FROM utilisateur WHERE role = :role AND statut = \'ACTIF\' AND id_utilisateur <> :sauf',
            ['role' => $role, 'sauf' => $sauf]
        );
        return array_map(static fn (array $l): int => (int) $l['id_utilisateur'], $lignes);
    }
}
