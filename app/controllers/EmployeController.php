<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Session;
use App\Models\Employe;

/** Gestion des employés par le Responsable RH : liste + recherche instantanée, création, dossier, modification. */
final class EmployeController extends Controller
{
    private const STATUTS = ['ACTIF', 'EN_CONGE', 'SUSPENDU', 'SORTI'];
    private const TYPES_CONTRAT = ['CDI', 'CDD', 'STAGE', 'AUTRE'];

    public function index(): void
    {
        $this->render('rh/employes/index', [
            'titre' => 'Employés', 'departements' => Employe::departements(), 'postes' => Employe::postes(),
            'scripts' => ['vendor/jquery/jquery.min.js', 'js/employes.js'],
        ]);
    }

    /** Répond en JSON à la recherche instantanée (appelée par js/employes.js). */
    public function recherche(): void
    {
        $statut = $this->texte($_GET['statut'] ?? '');
        $resultat = Employe::rechercher([
            'q'           => mb_substr($this->texte($_GET['q'] ?? ''), 0, 100),
            'departement' => (int) $this->texte($_GET['departement'] ?? ''),
            'poste'       => (int) $this->texte($_GET['poste'] ?? ''),
            'statut'      => in_array($statut, self::STATUTS, true) ? $statut : '',
        ], (int) $this->texte($_GET['page'] ?? '1'));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($resultat, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function nouveau(): void
    {
        $this->formulaire(null, ['type_contrat' => 'CDI', 'date_embauche' => date('Y-m-d')], []);
    }

    public function creer(): void
    {
        [$d, $erreurs] = $this->valider($_POST, true, null);
        if ($erreurs) {
            http_response_code(422);
            $this->formulaire(null, $d, $erreurs);
            return;
        }
        try {
            $r = Employe::creer($d, Auth::user()['id_utilisateur'], client_ip());
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {                      // e-mail pris entre-temps
                http_response_code(422);
                $this->formulaire(null, $d, ['email' => 'Cette adresse e-mail est déjà utilisée.']);
                return;
            }
            throw $e;
        }
        Session::flash('success', "Employé créé (matricule {$r['matricule']}). Mot de passe provisoire : {$r['mot_de_passe']} — à transmettre à l'employé (affiché une seule fois).");
        redirect('/rh/employes/voir?id=' . $r['id']);
    }

    public function voir(): void
    {
        $e = $this->employe();
        $this->render('rh/employes/voir', ['titre' => $e['prenom'] . ' ' . $e['nom'], 'e' => $e,
            'contrats' => Employe::contrats((int) $e['id_employe']), 'soldes' => Employe::soldes((int) $e['id_employe'])]);
    }

    public function modifier(): void
    {
        $e = $this->employe();
        $this->formulaire($e, $e, []);
    }

    public function mettreAJour(): void
    {
        $e = $this->employe();
        [$d, $erreurs] = $this->valider($_POST, false, $e);
        if ($erreurs) {
            http_response_code(422);
            $this->formulaire($e, $d, $erreurs);
            return;
        }
        Employe::mettreAJour($e, $d, Auth::user()['id_utilisateur'], client_ip());
        Session::flash('success', 'Les modifications ont été enregistrées.');
        redirect('/rh/employes/voir?id=' . $e['id_employe']);
    }

    // ---------------------------------------------------------------- outils

    private function employe(): array
    {
        $id = (int) $this->texte($_GET['id'] ?? '');
        $e = $id > 0 ? Employe::trouver($id) : null;
        if ($e === null) {
            throw new HttpException(404);
        }
        return $e;
    }

    private function texte(mixed $v): string
    {
        return is_string($v) ? trim($v) : '';           // ignore les valeurs tableau envoyées par un pirate
    }

    private function formulaire(?array $employe, array $valeurs, array $erreurs): void
    {
        $this->render('rh/employes/form', [
            'titre' => $employe ? 'Modifier un employé' : 'Nouvel employé', 'employe' => $employe, 'v' => $valeurs, 'erreurs' => $erreurs,
            'departements' => Employe::departements(), 'postes' => Employe::postes(), 'statuts' => self::STATUTS, 'typesContrat' => self::TYPES_CONTRAT,
        ]);
    }

    private function dateValide(string $s): ?string
    {
        $d = \DateTime::createFromFormat('!Y-m-d', $s);
        return ($d && $d->format('Y-m-d') === $s) ? $s : null;
    }

    /** Validation côté serveur. Renvoie [valeurs nettoyées, erreurs]. */
    private function valider(array $in, bool $creation, ?array $existant): array
    {
        $t = fn(string $k): string => $this->texte($in[$k] ?? '');
        $d = ['nom' => $t('nom'), 'prenom' => $t('prenom'), 'email' => mb_strtolower($t('email')), 'telephone' => $t('telephone'), 'adresse' => $t('adresse'),
              'date_naissance' => $t('date_naissance'), 'date_embauche' => $t('date_embauche'), 'id_departement' => (int) $t('id_departement'), 'id_poste' => (int) $t('id_poste'),
              'statut' => $t('statut'), 'type_contrat' => $t('type_contrat'), 'date_fin' => $t('date_fin'), 'salaire_base' => $t('salaire_base')];
        $err = [];
        $nomOk = '/^[\p{L}][\p{L}\p{M}\s\'’.-]{0,79}$/u';
        foreach (['nom' => 'Le nom', 'prenom' => 'Le prénom'] as $k => $lib) {
            if (!preg_match($nomOk, $d[$k])) { $err[$k] = "$lib est obligatoire (lettres, espaces, tiret ou apostrophe, 80 caractères maximum)."; }
        }
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL) || strlen($d['email']) > 150) {
            $err['email'] = 'Saisissez une adresse e-mail valide.';
        } elseif (Employe::emailPris($d['email'], $existant ? (int) $existant['id_utilisateur'] : null)) {
            $err['email'] = 'Cette adresse e-mail est déjà utilisée.';
        }
        if ($d['telephone'] !== '' && !preg_match('/^[0-9+() .-]{6,30}$/', $d['telephone'])) { $err['telephone'] = 'Numéro de téléphone invalide.'; }
        if (mb_strlen($d['adresse']) > 255) { $err['adresse'] = '255 caractères maximum.'; }
        if ($d['date_naissance'] !== '') {
            $n = $this->dateValide($d['date_naissance']);
            if ($n === null || $n > date('Y-m-d', strtotime('-16 years')) || $n < '1940-01-01') { $err['date_naissance'] = 'Date de naissance invalide (16 ans minimum).'; }
        }
        $emb = $this->dateValide($d['date_embauche']);
        if ($emb === null || $emb > date('Y-m-d', strtotime('+1 year'))) { $err['date_embauche'] = "Date d'embauche invalide."; }
        if (!Employe::departementExiste($d['id_departement'])) { $err['id_departement'] = 'Choisissez un département.'; }
        if (!Employe::posteExiste($d['id_poste'])) { $err['id_poste'] = 'Choisissez un poste.'; }

        $privilegie = $existant && in_array($existant['role'], ['RESPONSABLE_RH', 'DIRECTEUR_GENERAL'], true);
        if ($creation) {
            $d['statut'] = 'ACTIF';
            if (!in_array($d['type_contrat'], self::TYPES_CONTRAT, true)) { $err['type_contrat'] = 'Choisissez un type de contrat.'; }
            if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/', $d['salaire_base']) || (float) $d['salaire_base'] <= 0) { $err['salaire_base'] = 'Saisissez un montant supérieur à 0 (ex. 250000).'; }
            if ($d['type_contrat'] === 'CDI') { $d['date_fin'] = ''; }
            elseif (in_array($d['type_contrat'], ['CDD', 'STAGE'], true)) {
                $f = $this->dateValide($d['date_fin']);
                if ($f === null || ($emb !== null && $f <= $emb)) { $err['date_fin'] = "La date de fin est obligatoire et postérieure à l'embauche."; }
            } elseif ($d['date_fin'] !== '' && $this->dateValide($d['date_fin']) === null) { $err['date_fin'] = 'Date invalide.'; }
        } elseif ($privilegie) {
            $d['statut'] = $existant['statut'];                      // comptes RH/direction : statut géré par la direction
        } elseif (!in_array($d['statut'], self::STATUTS, true)) {
            $err['statut'] = 'Choisissez un statut.';
        }
        foreach (['date_naissance', 'telephone', 'adresse', 'date_fin'] as $k) {
            if ($d[$k] === '') { $d[$k] = null; }                    // champs facultatifs vides = NULL en base
        }
        return [$d, $err];
    }
}
