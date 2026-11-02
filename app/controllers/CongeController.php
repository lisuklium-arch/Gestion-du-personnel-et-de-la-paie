<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Session;
use App\Models\Absence;
use App\Models\DemandeConge;
use App\Models\Employe;
use App\Services\CircuitConge;
use App\Services\DemandeCongeService;
use App\Services\SoldeService;
use DateTimeImmutable;
use DomainException;
use PDOException;

/** Circuit des congés : dépôt, avis du chef, décision RH, décision DG. */
final class CongeController extends Controller
{
    private const RETOUR = [
        'employe' => '/employe/conges',
        'chef' => '/chef/conges',
        'rh' => '/rh/conges',
        'dg' => '/direction/conges',
    ];

    private const VOIR = [
        'employe' => '/employe/conges/voir',
        'chef' => '/chef/conges/voir',
        'rh' => '/rh/conges/voir',
        'dg' => '/direction/conges/voir',
    ];

    public function mesConges(): void
    {
        $id = $this->idEmploye();
        $this->render('conges/liste', [
            'titre' => 'Mes congés',
            'intro' => 'Le dépôt réserve les jours qui décomptent le solde. Un refus ou une annulation les libère. L\'approbation les fait passer de réservés à pris, sans les débiter une seconde fois.',
            'demandes' => DemandeConge::deEmploye($id),
            'soldes' => Employe::soldes($id),
            'nouveau' => url('/employe/conges/nouvelle'),
            'voir' => self::VOIR['employe'],
            'montrerEmploye' => false,
        ]);
    }

    public function nouvelle(): void
    {
        $this->afficherFormulaire([
            'id_type_conge' => '',
            'date_debut' => '',
            'date_fin' => '',
            'periode_debut' => 'MATIN',
            'periode_fin' => 'APRES_MIDI',
            'motif' => '',
        ], []);
    }

    public function deposer(): void
    {
        $v = [
            'id_type_conge' => $this->saisie($_POST['id_type_conge'] ?? ''),
            'date_debut' => $this->saisie($_POST['date_debut'] ?? ''),
            'date_fin' => $this->saisie($_POST['date_fin'] ?? ''),
            'periode_debut' => $this->saisie($_POST['periode_debut'] ?? ''),
            'periode_fin' => $this->saisie($_POST['periode_fin'] ?? ''),
            'motif' => $this->saisie($_POST['motif'] ?? ''),
        ];
        $erreurs = $this->erreursSaisie($v);
        if ($erreurs !== []) {
            http_response_code(422);
            $this->afficherFormulaire($v, $erreurs);
            return;
        }
        try {
            $id = DemandeCongeService::deposer($this->idEmploye(), Auth::user()['id_utilisateur'], [
                'id_type_conge' => (int) $v['id_type_conge'],
                'date_debut' => $this->dateIso($v['date_debut']),
                'date_fin' => $this->dateIso($v['date_fin']),
                'periode_debut' => $v['periode_debut'],
                'periode_fin' => $v['periode_fin'],
                'motif' => $v['motif'],
            ], $this->fichier(), client_ip());
        } catch (DomainException $e) {
            http_response_code(422);
            $this->afficherFormulaire($v, ['global' => $e->getMessage()]);
            return;
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            http_response_code(422);
            $this->afficherFormulaire($v, ['global' => 'Cette demande n\'a pas pu être enregistrée. Rechargez la page et réessayez.']);
            return;
        }
        Session::flash('success', 'Demande enregistrée. Les jours qui décomptent le solde sont réservés jusqu\'à la décision.');
        redirect(self::VOIR['employe'] . '?id=' . $id);
    }

    public function calcul(): void
    {
        $debut = $this->dateIso($this->saisie($_GET['date_debut'] ?? ''));
        $fin = $this->dateIso($this->saisie($_GET['date_fin'] ?? ''));
        $pd = $this->periode($this->saisie($_GET['periode_debut'] ?? ''));
        $pf = $this->periode($this->saisie($_GET['periode_fin'] ?? ''));
        try {
            $payload = ($debut === null || $fin === null || $pd === null || $pf === null)
                ? ['ok' => false, 'message' => 'Indiquez les dates et les demi-journées.']
                : ['ok' => true, 'jours' => DemandeCongeService::calculerJours($debut, $fin, $pd, $pf)];
        } catch (DomainException $e) {
            $payload = ['ok' => false, 'message' => $e->getMessage()];
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function voirEmploye(): void
    {
        $this->montrer('employe');
    }

    public function voirChef(): void
    {
        $this->montrer('chef');
    }

    public function voirRh(): void
    {
        $this->montrer('rh');
    }

    public function voirDg(): void
    {
        $this->montrer('dg');
    }

    public function calendrier(): void
    {
        $aujourd = new DateTimeImmutable('today');
        $mois = (int) $this->saisie($_GET['mois'] ?? '');
        $annee = (int) $this->saisie($_GET['annee'] ?? '');
        if ($mois < 1 || $mois > 12) {
            $mois = (int) $aujourd->format('n');
        }
        if ($annee < 2000 || $annee > 2100) {
            $annee = (int) $aujourd->format('Y');
        }
        $premier = new DateTimeImmutable(sprintf('%04d-%02d-01', $annee, $mois));
        $debut = $premier->format('Y-m-d');
        $fin = $premier->modify('last day of this month')->format('Y-m-d');
        $id = Auth::user()['id_utilisateur'];
        $cases = [];
        foreach (DemandeConge::planningChef($id, $debut, $fin) as $d) {
            $cur = new DateTimeImmutable(substr((string) $d['date_debut'], 0, 10));
            $stop = new DateTimeImmutable(substr((string) $d['date_fin'], 0, 10));
            $garde = 0;
            while ($cur <= $stop && $garde < 400) {
                $jour = $cur->format('Y-m-d');
                if ($jour >= $debut && $jour <= $fin) {
                    $precision = '';
                    if ($jour === substr((string) $d['date_debut'], 0, 10) && $d['periode_debut'] === 'APRES_MIDI') {
                        $precision = ' après-midi';
                    } elseif ($jour === substr((string) $d['date_fin'], 0, 10) && $d['periode_fin'] === 'MATIN') {
                        $precision = ' matin';
                    }
                    $cases[$jour][] = [
                        'texte' => $d['prenom'] . ' ' . $d['nom'] . $precision,
                        'attente' => $d['statut'] !== 'APPROUVE',
                        'href' => '/chef/conges/voir?id=' . (int) $d['id_demande_conge'],
                    ];
                }
                $cur = $cur->modify('+1 day');
                $garde++;
            }
        }
        foreach (Absence::duChefEntre($id, $debut, $fin) as $a) {
            $jour = substr((string) $a['date_absence'], 0, 10);
            $cases[$jour][] = [
                'texte' => $a['prenom'] . ' ' . $a['nom'] . ((int) $a['justifiee'] === 1 ? ' · absence justifiée' : ' · absence'),
                'attente' => false,
                'href' => null,
            ];
        }
        $moisFr = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        $precedent = $premier->modify('-1 month');
        $suivant = $premier->modify('+1 month');
        $this->render('conges/calendrier', [
            'titre' => 'Calendrier de l\'équipe',
            'libelle' => $moisFr[$mois] . ' ' . $annee,
            'semaines' => self::semaines($premier, $cases),
            'aujourdhui' => $aujourd->format('Y-m-d'),
            'precedent' => '/chef/calendrier?annee=' . $precedent->format('Y') . '&mois=' . $precedent->format('n'),
            'suivant' => '/chef/calendrier?annee=' . $suivant->format('Y') . '&mois=' . $suivant->format('n'),
        ]);
    }

    public function fileChef(): void
    {
        $this->render('conges/liste', [
            'titre' => 'Congés de l\'équipe',
            'intro' => 'Votre avis est consultatif : un avis défavorable ne refuse pas la demande et ne libère pas les jours. La décision appartient à la RH.',
            'demandes' => DemandeConge::deChef(Auth::user()['id_utilisateur']),
            'soldes' => null,
            'nouveau' => null,
            'voir' => self::VOIR['chef'],
            'montrerEmploye' => true,
        ]);
    }

    public function fileRh(): void
    {
        $this->render('conges/liste', [
            'titre' => 'Congés',
            'intro' => 'Une approbation transfère la réserve vers les jours pris. Un refus libère la réserve. Le congé d\'un responsable RH, s\'il est accepté ici, part ensuite chez le directeur général.',
            'demandes' => DemandeConge::toutes(),
            'soldes' => null,
            'nouveau' => null,
            'voir' => self::VOIR['rh'],
            'montrerEmploye' => true,
        ]);
    }

    public function fileDg(): void
    {
        $this->render('conges/liste', [
            'titre' => 'Congés des responsables RH',
            'intro' => 'Vous décidez les congés des responsables RH, après l\'instruction d\'un autre responsable RH. Vous ne décidez pas les congés du reste du personnel.',
            'demandes' => DemandeConge::desResponsablesRh(),
            'soldes' => null,
            'nouveau' => null,
            'voir' => self::VOIR['dg'],
            'montrerEmploye' => true,
        ]);
    }

    /** @param array<string, list<array{texte:string,attente:bool,href:?string}>> $cases @return list<list<array{date:?string,dansMois:bool,items:list<array{texte:string,attente:bool,href:?string}>}>> */
    private static function semaines(DateTimeImmutable $premier, array $cases): array
    {
        $grille = $premier->modify('-' . ((int) $premier->format('N') - 1) . ' days');
        $finMois = $premier->modify('last day of this month');
        $semaines = [];
        $garde = 0;
        while ($garde < 6 && ($garde === 0 || $grille <= $finMois)) {
            $semaine = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $grille->format('Y-m-d');
                $semaine[] = [
                    'date' => $date,
                    'dansMois' => $grille->format('m') === $premier->format('m'),
                    'items' => $cases[$date] ?? [],
                ];
                $grille = $grille->modify('+1 day');
            }
            $semaines[] = $semaine;
            $garde++;
        }
        return $semaines;
    }

    public function avis(): void
    {
        $this->executerDecision('chef', static function (int $id, string $decision, string $commentaire): void {
            DemandeCongeService::donnerAvis($id, Auth::user()['id_utilisateur'], $decision, $commentaire, client_ip());
        }, 'Avis enregistré. La demande est entre les mains de la RH.');
    }

    public function decisionRh(): void
    {
        $this->executerDecision('rh', static function (int $id, string $decision, string $commentaire): void {
            DemandeCongeService::deciderRh($id, Auth::user()['id_utilisateur'], $decision, $commentaire, client_ip());
        }, 'Décision enregistrée.');
    }

    public function decisionDg(): void
    {
        $this->executerDecision('dg', static function (int $id, string $decision, string $commentaire): void {
            DemandeCongeService::deciderDg($id, Auth::user()['id_utilisateur'], $decision, $commentaire, client_ip());
        }, 'Décision enregistrée.');
    }

    public function annuler(): void
    {
        $id = (int) $this->saisie($_POST['id'] ?? '');
        try {
            DemandeCongeService::annulerParEmploye($id, $this->idEmploye(), Auth::user()['id_utilisateur'], client_ip());
        } catch (DomainException $e) {
            Session::flash('danger', $e->getMessage());
            redirect(self::VOIR['employe'] . '?id=' . $id);
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            Session::flash('danger', 'Cette annulation a déjà été enregistrée.');
            redirect(self::VOIR['employe'] . '?id=' . $id);
        }
        Session::flash('success', 'Demande annulée.');
        redirect(self::RETOUR['employe']);
    }

    public function annulerRh(): void
    {
        $id = (int) $this->saisie($_POST['id'] ?? '');
        try {
            DemandeCongeService::annulerParRh($id, Auth::user()['id_utilisateur'], client_ip());
        } catch (DomainException $e) {
            Session::flash('danger', $e->getMessage());
            redirect(self::VOIR['rh'] . '?id=' . $id);
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            Session::flash('danger', 'Cette annulation a déjà été enregistrée.');
            redirect(self::VOIR['rh'] . '?id=' . $id);
        }
        Session::flash('success', 'Congé annulé et jours restitués.');
        redirect(self::RETOUR['rh']);
    }

    public function justificatif(): void
    {
        $demande = $this->demandeOu404();
        if (!DemandeConge::visible($demande, Auth::user())) {
            throw new HttpException(403);
        }
        $chemin = DemandeCongeService::cheminSecurise((string) ($demande['justificatif'] ?? ''));
        if ($chemin === null) {
            throw new HttpException(404);
        }
        $ext = pathinfo($chemin, PATHINFO_EXTENSION);
        $mime = match ($ext) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            default => 'image/jpeg',
        };
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="justificatif-' . (int) $demande['id_demande_conge'] . '.' . $ext . '"');
        header('Content-Length: ' . (string) filesize($chemin));
        readfile($chemin);
    }

    private function montrer(string $espace): void
    {
        $demande = $this->demandeOu404();
        $this->autoriser($demande, $espace);
        $user = Auth::user();
        $propre = (int) $demande['id_utilisateur'] === (int) $user['id_utilisateur'];
        $action = null;
        if ($demande['statut'] === 'EN_ATTENTE_AVIS' && (int) ($demande['id_utilisateur_chef'] ?? 0) === (int) $user['id_utilisateur'] && !$propre) {
            $action = 'avis';
        } elseif ($demande['statut'] === 'EN_ATTENTE_RH' && $user['role'] === 'RESPONSABLE_RH' && !$propre) {
            $action = 'rh';
        } elseif ($demande['statut'] === 'EN_ATTENTE_DG' && $user['role'] === 'DIRECTEUR_GENERAL') {
            $action = 'dg';
        }
        $aujourdhui = date('Y-m-d');
        $this->render('conges/detail', [
            'titre' => 'Demande de congé',
            'd' => $demande,
            'validations' => DemandeConge::validations((int) $demande['id_demande_conge']),
            'mouvements' => DemandeConge::mouvements((int) $demande['id_demande_conge']),
            'prochaine' => CircuitConge::prochaine((string) $demande['statut'], $demande['role_demandeur'] === 'RESPONSABLE_RH'),
            'etapes' => CircuitConge::etapes(self::avisDe($demande), $demande['role_demandeur'] === 'RESPONSABLE_RH'),
            'justificatifOk' => DemandeCongeService::cheminSecurise((string) ($demande['justificatif'] ?? '')) !== null,
            'action' => $action,
            'peutAnnuler' => $propre && $espace !== 'dg' && in_array($demande['statut'], ['EN_ATTENTE_AVIS', 'EN_ATTENTE_RH', 'EN_ATTENTE_DG'], true),
            'peutAnnulerRh' => $espace === 'rh' && $demande['statut'] === 'APPROUVE' && substr((string) $demande['date_debut'], 0, 10) > $aujourdhui,
            'retour' => self::RETOUR[$espace],
            'espace' => $espace,
        ]);
    }

    private function afficherFormulaire(array $v, array $erreurs): void
    {
        $id = $this->idEmploye();
        $ctx = DemandeConge::contexteEmploye($id);
        if ($ctx === null) {
            throw new HttpException(403);
        }
        $types = DemandeConge::typesActifs();
        $annee = (int) date('Y');
        $soldes = [];
        $jsTypes = [];
        foreach ($types as $type) {
            $idType = (int) $type['id_type_conge'];
            $jsTypes[(string) $idType] = (int) $type['justificatif_requis'];
            if ((int) $type['decompte_solde'] === 1) {
                $soldes[(string) $idType] = SoldeService::soldeDisponible($id, $idType, $annee);
            }
        }
        $demandeurRh = $ctx['role_demandeur'] === 'RESPONSABLE_RH';
        $this->render('conges/formulaire', [
            'titre' => 'Nouvelle demande de congé',
            'v' => $v,
            'erreurs' => $erreurs,
            'types' => $types,
            'soldes' => Employe::soldes($id),
            'jsSoldes' => $soldes,
            'jsTypes' => $jsTypes,
            'annee' => $annee,
            'etapes' => CircuitConge::etapes(self::avisDe($ctx), $demandeurRh),
            'scripts' => ['js/conges.js'],
        ]);
    }

    /** @param callable(int,string,string):void $action */
    private function executerDecision(string $espace, callable $action, string $succes): void
    {
        $id = (int) $this->saisie($_POST['id'] ?? '');
        $decision = $this->saisie($_POST['decision'] ?? '');
        $commentaire = $this->saisie($_POST['commentaire'] ?? '');
        $retour = self::VOIR[$espace] . '?id=' . $id;
        if (!in_array($decision, ['APPROUVE', 'REFUSE'], true)) {
            Session::flash('danger', 'Choisissez une décision.');
            redirect($retour);
        }
        try {
            $action($id, $decision, $commentaire);
        } catch (DomainException $e) {
            Session::flash('danger', $e->getMessage());
            redirect($retour);
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            Session::flash('danger', 'Cette décision a déjà été enregistrée.');
            redirect($retour);
        }
        Session::flash('success', $succes);
        redirect($retour);
    }

    private function demandeOu404(): array
    {
        $id = (int) $this->saisie($_GET['id'] ?? $_POST['id'] ?? '');
        $demande = $id > 0 ? DemandeConge::trouver($id) : null;
        if ($demande === null) {
            throw new HttpException(404);
        }
        return $demande;
    }

    private function autoriser(array $demande, string $espace): void
    {
        $user = Auth::user();
        $propre = (int) $demande['id_utilisateur'] === (int) $user['id_utilisateur'];
        $ok = match ($espace) {
            'employe' => $propre,
            'chef' => $propre || (int) ($demande['id_utilisateur_chef'] ?? 0) === (int) $user['id_utilisateur'],
            'rh' => true,
            'dg' => $demande['role_demandeur'] === 'RESPONSABLE_RH',
            default => false,
        };
        if (!$ok) {
            throw new HttpException(403);
        }
    }

    private function idEmploye(): int
    {
        $id = (int) (Auth::user()['id_employe'] ?? 0);
        if ($id <= 0) {
            throw new HttpException(403);
        }
        return $id;
    }

    /** @return array<string,string> */
    private function erreursSaisie(array $v): array
    {
        $erreurs = [];
        if ((int) $v['id_type_conge'] <= 0) {
            $erreurs['id_type_conge'] = 'Choisissez un type de congé.';
        }
        if ($this->dateIso($v['date_debut']) === null) {
            $erreurs['date_debut'] = 'Date de début invalide.';
        }
        if ($this->dateIso($v['date_fin']) === null) {
            $erreurs['date_fin'] = 'Date de fin invalide.';
        }
        if ($this->periode($v['periode_debut']) === null || $this->periode($v['periode_fin']) === null) {
            $erreurs['periode'] = 'Indiquez le matin ou l\'après-midi.';
        }
        if ($v['motif'] === '' || mb_strlen($v['motif']) > 255) {
            $erreurs['motif'] = 'Le motif est obligatoire (255 caractères au plus).';
        }
        return $erreurs;
    }

    private function periode(string $valeur): ?string
    {
        return in_array($valeur, ['MATIN', 'APRES_MIDI'], true) ? $valeur : null;
    }

    /** @return array{tmp:string,taille:int,erreur:int}|null */
    private function fichier(): ?array
    {
        if (!isset($_FILES['justificatif']) || !is_array($_FILES['justificatif'])) {
            return null;
        }
        $fichier = $_FILES['justificatif'];
        if ((int) ($fichier['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return [
            'tmp' => is_string($fichier['tmp_name'] ?? null) ? $fichier['tmp_name'] : '',
            'taille' => (int) ($fichier['size'] ?? 0),
            'erreur' => (int) ($fichier['error'] ?? UPLOAD_ERR_NO_FILE),
        ];
    }

    private static function avisDe(array $contexte): bool
    {
        return CircuitConge::avisRequis(
            ($contexte['id_chef'] ?? null) !== null,
            (int) ($contexte['id_chef'] ?? 0) === (int) $contexte['id_employe'],
            ($contexte['role_chef'] ?? '') === 'DIRECTEUR_GENERAL',
            ($contexte['role_demandeur'] ?? '') === 'RESPONSABLE_RH'
        );
    }
}
