<?php
declare(strict_types=1);

namespace App\Paie;

/**
 * Table `heure_supplementaire`.
 * Cycle : saisie -> validee_chef -> integree (ou rejetee).
 */
final class HeureSupplementaire extends Modele
{
    /** Majoration par défaut en % (ex. 25 = +25 %). À remplacer par un ParametreSysteme si votre équipe en définit un. */
    public const MAJORATION_DEFAUT = 25.0;

    public function saisir(int $employeId, string $dateRealisation, float $nbHeures, ?string $motif = null): int
    {
        if ($nbHeures <= 0) {
            throw new \InvalidArgumentException('nb_heures doit être > 0.');
        }
        $this->requete(
            'INSERT INTO heure_supplementaire (id_employe, date_travail, nb_heures, code_majoration, taux_majoration, montant, motif, statut)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$employeId, $dateRealisation, $nbHeures, 'STD', self::MAJORATION_DEFAUT, 0, $motif, 'SAISIE']
        );
        return (int) $this->pdo->lastInsertId();
    }

    /** Valide la saisie et calcule le montant : nb_heures x taux horaire x (1 + majoration/100). */
    public function valider(int $id, int $validateurId, float $tauxMajoration = self::MAJORATION_DEFAUT): int
    {
        $hs = $this->trouverParId($id);
        if (!$hs || $hs['statut'] !== 'SAISIE') {
            throw new \LogicException("Heure supplémentaire #$id introuvable ou déjà traitée.");
        }
        $contrat = $this->requete(
            "SELECT salaire_base, duree_mensuelle_heures FROM contrat
              WHERE id_employe = ? AND actif = 1
              ORDER BY date_debut DESC LIMIT 1",
            [$hs['id_employe']]
        )->fetch();
        if (!$contrat) {
            throw new \LogicException('Aucun contrat en cours pour cet employé.');
        }
        $tauxHoraire = (float) $contrat['salaire_base'] / (float) $contrat['duree_mensuelle_heures'];
        $montant = (int) round((float) $hs['nb_heures'] * $tauxHoraire * (1 + $tauxMajoration / 100));

        $this->requete(
            "UPDATE heure_supplementaire
                SET statut = 'VALIDEE_CHEF', taux_majoration = ?, montant = ?
              WHERE id_heure_sup = ?",
            [$tauxMajoration, $montant, $id]
        );
        return $montant;
    }

    public function listerEnAttente(): array
    {
        return $this->requete(
            "SELECT h.*, e.nom, e.prenom FROM heure_supplementaire h
               JOIN employe e ON e.id_employe = h.id_employe
              WHERE h.statut = 'SAISIE' OR h.statut = 'VALIDEE_CHEF' ORDER BY h.date_travail DESC"
        )->fetchAll();
    }

    public function rejeter(int $id, int $validateurId): bool
    {
        return $this->requete(
            "UPDATE heure_supplementaire
                SET statut = 'REJETEE'
              WHERE id_heure_sup = ? AND statut = 'SAISIE'",
            [$id]
        )->rowCount() > 0;
    }

    public function trouverParId(int $id): ?array
    {
        return $this->requete('SELECT * FROM heure_supplementaire WHERE id_heure_sup = ?', [$id])->fetch() ?: null;
    }

    public function listerParEmploye(int $employeId, ?string $statut = null): array
    {
        $sql = 'SELECT * FROM heure_supplementaire WHERE id_employe = ?';
        $params = [$employeId];
        if ($statut !== null) {
            $sql .= ' AND statut = ?';
            $params[] = $statut;
        }
        return $this->requete($sql . ' ORDER BY date_travail DESC', $params)->fetchAll();
    }

    /** Heures validées par le chef, pas encore intégrées, réalisées dans la période. */
    public function listerAIntegrer(int $employeId, string $debut, string $fin): array
    {
        return $this->requete(
            "SELECT * FROM heure_supplementaire
              WHERE id_employe = ? AND statut = 'VALIDEE_CHEF' AND id_fiche IS NULL
                AND date_travail BETWEEN ? AND ?",
            [$employeId, $debut, $fin]
        )->fetchAll();
    }

    /** Employés des départements dirigés par cet utilisateur (chef de département). */
    public function employesDuChef(int $utilisateurId): array
    {
        return $this->requete(
            "SELECT e.id_employe AS id, e.matricule, e.nom, e.prenom
               FROM employe e
               JOIN departement d ON d.id_departement = e.id_departement
               JOIN employe c ON c.id_employe = d.id_chef
              WHERE c.id_utilisateur = ? AND e.statut = 'ACTIF' ORDER BY e.nom", [$utilisateurId]
        )->fetchAll();
    }

    public function listerPourChef(int $utilisateurId): array
    {
        return $this->requete(
            "SELECT h.*, e.nom, e.prenom
               FROM heure_supplementaire h
               JOIN employe e ON e.id_employe = h.id_employe
               JOIN departement d ON d.id_departement = e.id_departement
               JOIN employe c ON c.id_employe = d.id_chef
              WHERE c.id_utilisateur = ?
              ORDER BY h.statut = 'SAISIE' DESC, h.date_travail DESC LIMIT 100", [$utilisateurId]
        )->fetchAll();
    }

    /** Pour la RH : heures validées par les chefs, qui seront intégrées à la prochaine fiche. */
    public function listerARevoir(): array
    {
        return $this->requete(
            "SELECT h.*, e.nom, e.prenom FROM heure_supplementaire h
               JOIN employe e ON e.id_employe = h.id_employe
              WHERE h.statut = 'VALIDEE_CHEF' AND h.id_fiche IS NULL ORDER BY h.date_travail"
        )->fetchAll();
    }

    public function integrerDansFiche(int $id, int $fichePaieId): bool
    {
        return $this->requete(
            "UPDATE heure_supplementaire SET statut = 'INTEGREE', id_fiche = ?
              WHERE id_heure_sup = ? AND statut = 'VALIDEE_CHEF'",
            [$fichePaieId, $id]
        )->rowCount() > 0;
    }
}
