<?php
declare(strict_types=1);

namespace App\Paie;

/** Table `rubrique_paie` : détail (primes / retenues) d'une fiche. Modifiable uniquement en brouillon. */
final class LignePaie extends Modele
{
    public const ORIGINES = ['POSTE', 'EMPLOYE', 'EXCEPTIONNELLE', 'HEURES_SUP', 'AVANCE'];

    public function ajouter(int $fichePaieId, ?int $rubriqueId, string $libelle, string $nature,
                            int $montant, string $origine, ?float $baseCalcul = null,
                            ?float $taux = null, ?string $justification = null): int
    {
        $this->verifierEnum($nature, RubriquePaie::NATURES, 'nature');
        $this->verifierEnum($origine, self::ORIGINES, 'origine');
        if ($origine === 'EXCEPTIONNELLE' && !$justification) {
            throw new \InvalidArgumentException('Une ligne exceptionnelle exige une justification.');
        }
        $this->exigerBrouillon($fichePaieId);

        $this->requete(
            'INSERT INTO rubrique_paie
                (id_fiche, id_type_rubrique, libelle, nature, base, taux, montant, origine, justification)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$fichePaieId, $rubriqueId, $libelle, $nature, $baseCalcul, $taux, $montant, $origine, $justification]
        );
        return (int) $this->pdo->lastInsertId();
    }

    public function supprimer(int $id): bool
    {
        $ligne = $this->requete('SELECT id_fiche FROM rubrique_paie WHERE id_rubrique = ?', [$id])->fetch();
        if (!$ligne) {
            return false;
        }
        $this->exigerBrouillon((int) $ligne['id_fiche']);
        return $this->requete('DELETE FROM rubrique_paie WHERE id_rubrique = ?', [$id])->rowCount() > 0;
    }

    public function listerParFiche(int $fichePaieId): array
    {
        return $this->requete(
            'SELECT * FROM rubrique_paie WHERE id_fiche = ? ORDER BY nature DESC, id_rubrique',
            [$fichePaieId]
        )->fetchAll();
    }

    /** @return array{primes:int, retenues:int} */
    public function totaux(int $fichePaieId): array
    {
        $r = $this->requete(
            "SELECT COALESCE(SUM(CASE WHEN nature = 'PRIME' THEN montant END),0) AS primes,
                    COALESCE(SUM(CASE WHEN nature = 'RETENUE' THEN montant END),0) AS retenues
               FROM rubrique_paie WHERE id_fiche = ?",
            [$fichePaieId]
        )->fetch();
        return ['primes' => (int) $r['primes'], 'retenues' => (int) $r['retenues']];
    }

    /** Correction du montant d'une ligne (fiche en brouillon). Passer par FichePaie::corrigerLigne pour recalculer. */
    public function corrigerMontant(int $id, int $montant, string $justification): bool
    {
        if ($montant < 0 || trim($justification) === '') {
            throw new \InvalidArgumentException('Montant >= 0 et justification obligatoires.');
        }
        $l = $this->requete('SELECT id_fiche FROM rubrique_paie WHERE id_rubrique = ?', [$id])->fetch();
        if (!$l) {
            return false;
        }
        $this->exigerBrouillon((int) $l['id_fiche']);
        return $this->requete('UPDATE rubrique_paie SET montant = ?, justification = ? WHERE id_rubrique = ?',
            [$montant, $justification, $id])->rowCount() > 0;
    }

    private function exigerBrouillon(int $fichePaieId): void
    {
        $statut = $this->requete('SELECT statut FROM fiche_paie WHERE id_fiche = ?', [$fichePaieId])->fetchColumn();
        if ($statut !== 'BROUILLON') {
            throw new \LogicException('La fiche doit être en brouillon pour modifier ses lignes.');
        }
    }
}
