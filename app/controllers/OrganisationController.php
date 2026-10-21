<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Session;
use App\Models\Contrat;
use App\Models\Departement;
use App\Models\Employe;
use App\Models\Poste;

/** Départements, postes et contrats (Responsable RH). */
final class OrganisationController extends Controller
{
    private const TYPES = ['CDI', 'CDD', 'STAGE', 'AUTRE'];

    public function index(): void
    {
        $this->render('rh/organisation/index', ['titre' => 'Départements, postes et contrats', 'departements' => Departement::tous(), 'postes' => Poste::tous(), 'contrats' => Contrat::enCours()]);
    }

    // ------------------------------------------------------------ départements
    public function departementNouveau(): void { $this->formDepartement(null, [], []); }

    public function departementCreer(): void
    {
        [$d, $err] = $this->validerDepartement($_POST, null);
        if ($err) { http_response_code(422); $this->formDepartement(null, $d, $err); return; }
        Departement::creer($d, Auth::user()['id_utilisateur'], client_ip());
        Session::flash('success', 'Département créé. Ajoutez-y des employés, puis désignez son chef en le modifiant.');
        redirect('/rh/organisation');
    }

    public function departementModifier(): void { $dep = $this->departement(); $this->formDepartement($dep, $dep, []); }

    public function departementMettreAJour(): void
    {
        $dep = $this->departement();
        [$d, $err] = $this->validerDepartement($_POST, $dep);
        if ($err) { http_response_code(422); $this->formDepartement($dep, $d, $err); return; }
        Departement::mettreAJour($dep, $d, Auth::user()['id_utilisateur'], client_ip());
        Session::flash('success', 'Département enregistré.');
        redirect('/rh/organisation');
    }

    public function departementSupprimer(): void
    {
        $message = Departement::supprimer($this->departement(), Auth::user()['id_utilisateur'], client_ip());
        Session::flash($message ? 'danger' : 'success', $message ?? 'Département supprimé.');
        redirect('/rh/organisation');
    }

    // ------------------------------------------------------------------ postes
    public function posteNouveau(): void { $this->formPoste(null, [], []); }

    public function posteCreer(): void
    {
        [$d, $err] = $this->validerPoste($_POST, null);
        if ($err) { http_response_code(422); $this->formPoste(null, $d, $err); return; }
        Poste::creer($d, Auth::user()['id_utilisateur'], client_ip());
        Session::flash('success', 'Poste créé.');
        redirect('/rh/organisation');
    }

    public function posteModifier(): void { $p = $this->poste(); $this->formPoste($p, $p, []); }

    public function posteMettreAJour(): void
    {
        $p = $this->poste();
        [$d, $err] = $this->validerPoste($_POST, $p);
        if ($err) { http_response_code(422); $this->formPoste($p, $d, $err); return; }
        Poste::mettreAJour($p, $d, Auth::user()['id_utilisateur'], client_ip());
        Session::flash('success', 'Poste enregistré.');
        redirect('/rh/organisation');
    }

    public function posteSupprimer(): void
    {
        $message = Poste::supprimer($this->poste(), Auth::user()['id_utilisateur'], client_ip());
        Session::flash($message ? 'danger' : 'success', $message ?? 'Poste supprimé.');
        redirect('/rh/organisation');
    }

    // ----------------------------------------------------------------- contrats
    public function contratNouveau(): void
    {
        $this->formContrat(['id_employe' => (string) (int) $this->saisie($_GET['employe'] ?? ''), 'type_contrat' => 'CDI', 'date_debut' => date('Y-m-d'), 'duree' => '173.33'], []);
    }

    public function contratCreer(): void
    {
        [$d, $err] = $this->validerContrat($_POST);
        if ($err) { http_response_code(422); $this->formContrat($d, $err); return; }
        $ref = Contrat::creer($d, Auth::user()['id_utilisateur'], client_ip());
        Session::flash('success', "Contrat $ref créé ; le précédent contrat en cours a été clôturé.");
        redirect('/rh/employes/voir?id=' . $d['id_employe']);
    }

    // ------------------------------------------------------------------- outils
    private function departement(): array
    {
        $id = (int) $this->saisie($_GET['id'] ?? '');
        return ($id > 0 ? Departement::trouver($id) : null) ?? throw new HttpException(404);
    }

    private function poste(): array
    {
        $id = (int) $this->saisie($_GET['id'] ?? '');
        return ($id > 0 ? Poste::trouver($id) : null) ?? throw new HttpException(404);
    }

    private function formDepartement(?array $dep, array $v, array $err): void
    {
        $id = $dep ? (int) $dep['id_departement'] : 0;
        $this->render('rh/organisation/departement_form', ['titre' => $dep ? 'Modifier le département' : 'Nouveau département', 'dep' => $dep, 'v' => $v, 'err' => $err,
            'parents' => Departement::pourParent($id), 'chefs' => $dep ? Departement::candidatsChef($id) : []]);
    }

    private function formPoste(?array $p, array $v, array $err): void
    {
        $this->render('rh/organisation/poste_form', ['titre' => $p ? 'Modifier le poste' : 'Nouveau poste', 'p' => $p, 'v' => $v, 'err' => $err]);
    }

    private function formContrat(array $v, array $err): void
    {
        $emps = [];
        foreach (Employe::pourSelection() as $e) { $emps[$e['id_employe']] = $e['matricule'] . ' — ' . $e['prenom'] . ' ' . $e['nom']; }
        $this->render('rh/organisation/contrat_form', ['titre' => 'Nouveau contrat', 'v' => $v, 'err' => $err, 'employes' => $emps, 'types' => array_combine(self::TYPES, self::TYPES)]);
    }

    private function validerDepartement(array $in, ?array $dep): array
    {
        $id = $dep ? (int) $dep['id_departement'] : 0;
        $d = ['code' => strtoupper($this->saisie($in['code'] ?? '')), 'nom' => $this->saisie($in['nom'] ?? ''), 'description' => $this->saisie($in['description'] ?? ''),
              'parent' => $this->saisie($in['parent'] ?? ''), 'chef' => $dep ? $this->saisie($in['chef'] ?? '') : ''];
        $err = [];
        if (!preg_match('/^[A-Z0-9_-]{2,20}$/', $d['code'])) { $err['code'] = 'Code obligatoire : 2 à 20 caractères (lettres, chiffres, tiret).'; }
        elseif (Departement::codePris($d['code'], $id)) { $err['code'] = 'Ce code est déjà utilisé.'; }
        if (mb_strlen($d['nom']) < 2 || mb_strlen($d['nom']) > 100) { $err['nom'] = 'Le nom est obligatoire (100 caractères maximum).'; }
        if (mb_strlen($d['description']) > 255) { $err['description'] = '255 caractères maximum.'; }
        $parent = (int) $d['parent'];
        if ($d['parent'] !== '' && ($parent === $id && $id > 0 || !Departement::trouver($parent))) { $err['parent'] = 'Département parent invalide.'; }
        elseif ($parent > 0 && Departement::creeUnCycle($id, $parent)) { $err['parent'] = 'Ce choix créerait une boucle dans l’organigramme.'; }
        $chef = (int) $d['chef'];
        if ($dep && $d['chef'] !== '' && !isset(Departement::candidatsChef($id)[$chef])) { $err['chef'] = 'Le chef doit être un employé actif de ce département (ni RH, ni chef d’un autre département).'; }
        $d['parent'] = $parent > 0 ? $parent : null;
        $d['chef'] = ($dep && $chef > 0) ? $chef : null;
        $d['description'] = $d['description'] === '' ? null : $d['description'];
        return [$d, $err];
    }

    private function validerPoste(array $in, ?array $p): array
    {
        $d = ['intitule' => $this->saisie($in['intitule'] ?? ''), 'description' => $this->saisie($in['description'] ?? '')];
        $err = [];
        if (mb_strlen($d['intitule']) < 2 || mb_strlen($d['intitule']) > 100) { $err['intitule'] = 'L’intitulé est obligatoire (100 caractères maximum).'; }
        elseif (Poste::intitulePris($d['intitule'], $p ? (int) $p['id_poste'] : 0)) { $err['intitule'] = 'Ce poste existe déjà.'; }
        if (mb_strlen($d['description']) > 255) { $err['description'] = '255 caractères maximum.'; }
        $d['description'] = $d['description'] === '' ? null : $d['description'];
        return [$d, $err];
    }

    private function validerContrat(array $in): array
    {
        $d = ['id_employe' => (int) $this->saisie($in['id_employe'] ?? ''), 'type_contrat' => $this->saisie($in['type_contrat'] ?? ''), 'date_debut' => $this->saisie($in['date_debut'] ?? ''),
              'date_fin' => $this->saisie($in['date_fin'] ?? ''), 'salaire_base' => $this->saisie($in['salaire_base'] ?? ''), 'duree' => $this->saisie($in['duree'] ?? '')];
        $err = [];
        $ok = array_column(Employe::pourSelection(), 'id_employe');
        if (!in_array((string) $d['id_employe'], array_map('strval', $ok), true)) { $err['id_employe'] = 'Choisissez un employé en poste.'; }
        if (!in_array($d['type_contrat'], self::TYPES, true)) { $err['type_contrat'] = 'Choisissez un type de contrat.'; }
        $debut = $this->dateIso($d['date_debut']);
        if ($debut === null || $debut < '2000-01-01' || $debut > date('Y-m-d', strtotime('+2 years'))) { $err['date_debut'] = 'Date de début invalide.'; }
        elseif (!isset($err['id_employe']) && ($actif = Contrat::actifDe($d['id_employe'])) && $debut <= $actif['date_debut']) { $err['date_debut'] = 'Le nouveau contrat doit commencer après le début du contrat en cours (' . date_fr($actif['date_debut']) . ').'; }
        if ($d['type_contrat'] === 'CDI') { $d['date_fin'] = ''; }
        elseif (in_array($d['type_contrat'], ['CDD', 'STAGE'], true)) {
            $fin = $this->dateIso($d['date_fin']);
            if ($fin === null || ($debut !== null && $fin <= $debut)) { $err['date_fin'] = 'La date de fin est obligatoire et postérieure au début.'; }
        } elseif ($d['date_fin'] !== '' && $this->dateIso($d['date_fin']) === null) { $err['date_fin'] = 'Date invalide.'; }
        if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/', $d['salaire_base']) || (float) $d['salaire_base'] <= 0) { $err['salaire_base'] = 'Saisissez un montant supérieur à 0 (ex. 250000).'; }
        if ($d['duree'] === '') { $d['duree'] = '173.33'; }
        if (!preg_match('/^\d{2,3}(\.\d{1,2})?$/', $d['duree']) || (float) $d['duree'] < 40 || (float) $d['duree'] > 260) { $err['duree'] = 'Durée mensuelle entre 40 et 260 heures.'; }
        $d['date_fin'] = $d['date_fin'] === '' ? null : $d['date_fin'];
        return [$d, $err];
    }
}
