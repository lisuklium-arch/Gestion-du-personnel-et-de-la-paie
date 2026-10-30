<?php

require_once __DIR__ . '/../models/Referentiel.php';
require_once __DIR__ . '/JournalService.php';

/** Règles de gestion des départements, postes et types de congé (création et modification, jamais de suppression). */
class ReferentielService
{
    private Referentiel $referentiel;
    private JournalService $journal;

    public function __construct(Referentiel $referentiel, JournalService $journal)
    {
        $this->referentiel = $referentiel;
        $this->journal = $journal;
    }

    private static function texte($v): string
    {
        return is_string($v) ? trim($v) : '';
    }

    private static function entierOuNul($v): ?int
    {
        $n = filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $n === false ? null : $n;
    }

    private static function echec(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }

    public function enregistrerDepartement(int $auteurId, array $d): array
    {
        $id = self::entierOuNul($d['id'] ?? '');
        $nom = self::texte($d['nom'] ?? '');
        $description = self::texte($d['description'] ?? '');
        $parentBrut = self::texte($d['parent_id'] ?? '');
        $parent = $parentBrut === '' ? null : self::entierOuNul($parentBrut);

        if ($nom === '' || mb_strlen($nom) > 100) {
            return self::echec('Le nom du département est obligatoire (100 caractères maximum).');
        }
        if (mb_strlen($description) > 255) {
            return self::echec('La description est trop longue (255 caractères maximum).');
        }
        $avant = null;
        if ($id !== null) {
            $avant = $this->referentiel->trouverDepartement($id);
            if ($avant === null) {
                return self::echec('Département introuvable.');
            }
        }
        if ($parentBrut !== '' && ($parent === null || $this->referentiel->trouverDepartement($parent) === null)) {
            return self::echec('Département parent introuvable.');
        }
        if ($parent !== null && $id !== null && $this->creeUnCycle($id, $parent)) {
            return self::echec('Un département ne peut pas dépendre de lui-même ni de l’un de ses sous-départements.');
        }
        if ($this->referentiel->nomDepartementPris($nom, $id ?? 0)) {
            return self::echec('Un département porte déjà ce nom.');
        }

        $desc = $description === '' ? null : $description;
        $apres = ['nom' => $nom, 'description' => $desc, 'parent_id' => $parent];

        if ($id === null) {
            $id = $this->referentiel->creerDepartement($nom, $desc, $parent);
            $this->journal->enregistrer($auteurId, 'creation_departement', 'departement', $id, null, $apres);
            return ['ok' => true, 'message' => 'Département « ' . $nom . ' » créé.', 'id' => $id];
        }

        $this->referentiel->modifierDepartement($id, $nom, $desc, $parent);
        $this->journal->enregistrer($auteurId, 'modification_departement', 'departement', $id,
            ['nom' => $avant['nom'], 'description' => $avant['description'], 'parent_id' => $avant['parent_id'] === null ? null : (int) $avant['parent_id']],
            $apres);
        return ['ok' => true, 'message' => 'Département « ' . $nom . ' » enregistré.', 'id' => $id];
    }

    /** Vrai si rattacher $id à $parent ferait une boucle (parent = lui-même ou l'un de ses descendants). */
    private function creeUnCycle(int $id, int $parent): bool
    {
        $courant = $parent;
        for ($i = 0; $i < 50 && $courant !== null; $i++) {
            if ($courant === $id) {
                return true;
            }
            $courant = $this->referentiel->parentDe($courant);
        }
        return false;
    }

    public function enregistrerPoste(int $auteurId, array $d): array
    {
        $id = self::entierOuNul($d['id'] ?? '');
        $intitule = self::texte($d['intitule'] ?? '');
        $description = self::texte($d['description'] ?? '');

        if ($intitule === '' || mb_strlen($intitule) > 100) {
            return self::echec('L’intitulé du poste est obligatoire (100 caractères maximum).');
        }
        if (mb_strlen($description) > 255) {
            return self::echec('La description est trop longue (255 caractères maximum).');
        }
        $avant = null;
        if ($id !== null) {
            $avant = $this->referentiel->trouverPoste($id);
            if ($avant === null) {
                return self::echec('Poste introuvable.');
            }
        }
        if ($this->referentiel->intitulePris($intitule, $id ?? 0)) {
            return self::echec('Un poste porte déjà cet intitulé.');
        }

        $desc = $description === '' ? null : $description;
        $apres = ['intitule' => $intitule, 'description' => $desc];

        if ($id === null) {
            $id = $this->referentiel->creerPoste($intitule, $desc);
            $this->journal->enregistrer($auteurId, 'creation_poste', 'poste', $id, null, $apres);
            return ['ok' => true, 'message' => 'Poste « ' . $intitule . ' » créé.', 'id' => $id];
        }

        $this->referentiel->modifierPoste($id, $intitule, $desc);
        $this->journal->enregistrer($auteurId, 'modification_poste', 'poste', $id,
            ['intitule' => $avant['intitule'], 'description' => $avant['description']], $apres);
        return ['ok' => true, 'message' => 'Poste « ' . $intitule . ' » enregistré.', 'id' => $id];
    }

    public function enregistrerTypeConge(int $auteurId, array $d): array
    {
        $id = self::entierOuNul($d['id'] ?? '');
        $code = strtoupper(self::texte($d['code'] ?? ''));
        $libelle = self::texte($d['libelle'] ?? '');
        $droitBrut = str_replace(',', '.', self::texte($d['droit_annuel_jours'] ?? ''));

        if ($libelle === '' || mb_strlen($libelle) > 100) {
            return self::echec('Le libellé est obligatoire (100 caractères maximum).');
        }
        if (!preg_match('/^\d{1,2}(\.\d)?$/', $droitBrut) || (float) $droitBrut > 60 || fmod((float) $droitBrut * 2, 1.0) != 0.0) {
            return self::echec('Le droit annuel doit être compris entre 0 et 60 jours, par demi-journée.');
        }
        $droit = number_format((float) $droitBrut, 1, '.', '');
        $decompte = !empty($d['decompte_solde']) && $d['decompte_solde'] === '1' ? 1 : 0;
        $justificatif = !empty($d['justificatif_requis']) && $d['justificatif_requis'] === '1' ? 1 : 0;
        $actif = ($d['actif'] ?? '1') === '0' ? 0 : 1;

        $avant = null;
        if ($id !== null) {
            $avant = $this->referentiel->trouverTypeConge($id);
            if ($avant === null) {
                return self::echec('Type de congé introuvable.');
            }
            $code = $avant['code'];
        } elseif (!preg_match('/^[A-Z][A-Z0-9_]{1,29}$/', $code)) {
            return self::echec('Le code doit contenir 2 à 30 caractères : lettres majuscules, chiffres ou « _ », en commençant par une lettre.');
        } elseif ($this->referentiel->codeCongePris($code)) {
            return self::echec('Ce code est déjà utilisé.');
        }

        $apres = ['code' => $code, 'libelle' => $libelle, 'decompte_solde' => $decompte, 'droit_annuel_jours' => $droit,
                  'justificatif_requis' => $justificatif, 'actif' => $actif];

        if ($id === null) {
            $id = $this->referentiel->creerTypeConge($code, $libelle, $decompte, $droit, $justificatif, $actif);
            $this->journal->enregistrer($auteurId, 'creation_type_conge', 'type_conge', $id, null, $apres);
            return ['ok' => true, 'message' => 'Type de congé « ' . $libelle . ' » créé.', 'id' => $id];
        }

        $this->referentiel->modifierTypeConge($id, $libelle, $decompte, $droit, $justificatif, $actif);
        $this->journal->enregistrer($auteurId, 'modification_type_conge', 'type_conge', $id, [
            'code' => $avant['code'], 'libelle' => $avant['libelle'], 'decompte_solde' => (int) $avant['decompte_solde'],
            'droit_annuel_jours' => number_format((float) $avant['droit_annuel_jours'], 1, '.', ''),
            'justificatif_requis' => (int) $avant['justificatif_requis'], 'actif' => (int) $avant['actif'],
        ], $apres);
        return ['ok' => true, 'message' => 'Type de congé « ' . $libelle . ' » enregistré.', 'id' => $id];
    }

    private function supprimerAvecControle(callable $suppression): ?array
    {
        try {
            $suppression();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return self::echec('Suppression impossible : cet élément est encore utilisé.');
            }
            throw $e;
        }
        return null;
    }

    public function supprimerDepartement(int $auteurId, int $id): array
    {
        $d = $this->referentiel->trouverDepartement($id);
        if ($d === null) {
            return self::echec('Département introuvable.');
        }
        if ($this->referentiel->compterEmployesDepartement($id) > 0) {
            return self::echec('Suppression impossible : des employés sont rattachés à ce département.');
        }
        if ($this->referentiel->compterSousDepartements($id) > 0) {
            return self::echec('Suppression impossible : ce département a des sous-départements.');
        }
        if ($d['chef_id'] !== null) {
            return self::echec('Suppression impossible : un chef est désigné pour ce département.');
        }
        if ($erreur = $this->supprimerAvecControle(fn () => $this->referentiel->supprimerDepartement($id))) {
            return $erreur;
        }
        $this->journal->enregistrer($auteurId, 'suppression_departement', 'departement', $id,
            ['nom' => $d['nom'], 'description' => $d['description'], 'parent_id' => $d['parent_id'] === null ? null : (int) $d['parent_id']], null);
        return ['ok' => true, 'message' => 'Département « ' . $d['nom'] . ' » supprimé.'];
    }

    public function supprimerPoste(int $auteurId, int $id): array
    {
        $p = $this->referentiel->trouverPoste($id);
        if ($p === null) {
            return self::echec('Poste introuvable.');
        }
        if ($this->referentiel->compterEmployesPoste($id) > 0) {
            return self::echec('Suppression impossible : des employés occupent ce poste.');
        }
        if ($this->referentiel->compterRubriquesPoste($id) > 0) {
            return self::echec('Suppression impossible : des rubriques de paie sont rattachées à ce poste.');
        }
        if ($erreur = $this->supprimerAvecControle(fn () => $this->referentiel->supprimerPoste($id))) {
            return $erreur;
        }
        $this->journal->enregistrer($auteurId, 'suppression_poste', 'poste', $id,
            ['intitule' => $p['intitule'], 'description' => $p['description']], null);
        return ['ok' => true, 'message' => 'Poste « ' . $p['intitule'] . ' » supprimé.'];
    }

    public function supprimerTypeConge(int $auteurId, int $id): array
    {
        $t = $this->referentiel->trouverTypeConge($id);
        if ($t === null) {
            return self::echec('Type de congé introuvable.');
        }
        if ($this->referentiel->compterUsagesTypeConge($id) > 0) {
            return self::echec('Suppression impossible : ce type est utilisé dans des soldes ou des demandes. Désactivez-le plutôt.');
        }
        if ($erreur = $this->supprimerAvecControle(fn () => $this->referentiel->supprimerTypeConge($id))) {
            return $erreur;
        }
        $this->journal->enregistrer($auteurId, 'suppression_type_conge', 'type_conge', $id, [
            'code' => $t['code'], 'libelle' => $t['libelle'], 'decompte_solde' => (int) $t['decompte_solde'],
            'droit_annuel_jours' => number_format((float) $t['droit_annuel_jours'], 1, '.', ''),
            'justificatif_requis' => (int) $t['justificatif_requis'], 'actif' => (int) $t['actif'],
        ], null);
        return ['ok' => true, 'message' => 'Type de congé « ' . $t['libelle'] . ' » supprimé.'];
    }
}
