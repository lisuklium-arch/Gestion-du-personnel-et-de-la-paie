<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\HttpException;
use App\Models\DemandeRh;
use App\Models\Employe;
use App\Models\FichePaie;
use App\Models\Portail;
use App\Services\ExportDocument;

final class ExportController extends Controller
{
    public function ficheEmploye(): void
    {
        $idEmploye = (int) (Auth::user()['id_employe'] ?? 0);
        $fiche = FichePaie::trouver((int) $this->saisie($_GET['id'] ?? ''));
        if ($fiche === null || (int) $fiche['id_employe'] !== $idEmploye) {
            throw new HttpException(404);
        }
        $this->envoyerFiche($fiche, $this->format());
    }

    public function ficheRh(): void
    {
        $fiche = FichePaie::trouver((int) $this->saisie($_GET['id'] ?? ''));
        if ($fiche === null) {
            throw new HttpException(404);
        }
        $this->envoyerFiche($fiche, $this->format());
    }

    public function attestation(): void
    {
        $a = DemandeRh::attestation((int) $this->saisie($_GET['id'] ?? ''));
        if ($a === null) {
            throw new HttpException(404);
        }
        $user = Auth::user();
        $estRh = Auth::allows(['RESPONSABLE_RH']);
        $estProprio = (int) ($user['id_employe'] ?? 0) === (int) $a['id_employe'];
        if (!$estRh && !$estProprio) {
            throw new HttpException(403);
        }
        if ($a['statut'] !== 'ACCEPTEE' && !$estRh) {
            throw new HttpException(403);
        }
        $e = Employe::trouver((int) $a['id_employe']);
        $org = \App\Core\Config::get('app.name') ?: 'Organisation';
        $doc = [
            'titre' => (string) $a['type_attestation'],
            'sous_titre' => (string) $org,
            'meta' => [
                ['Référence', 'ATT-' . str_pad((string) $a['id_demande_attestation'], 5, '0', STR_PAD_LEFT)],
                ['Bénéficiaire', trim(($e['prenom'] ?? '') . ' ' . ($e['nom'] ?? ''))],
                ['Matricule', (string) ($e['matricule'] ?? '')],
                ['Poste', (string) ($e['poste'] ?? '')],
                ['Département', (string) ($e['departement'] ?? '')],
                ['Date de demande', date('d/m/Y', strtotime((string) $a['date_demande']))],
                ['Statut', (string) $a['statut']],
            ],
            'paragraphes' => [
                'La direction des ressources humaines certifie que la personne ci-dessus est employée au sein de l\'organisation.',
                $a['motif'] ? 'Motif de la demande : ' . $a['motif'] : 'Document établi à la demande de l\'intéressé(e).',
                'Fait pour servir et valoir ce que de droit.',
            ],
            'pied' => 'Document généré le ' . date('d/m/Y à H:i') . ' — ' . $org,
        ];
        ExportDocument::telecharger($doc, $this->format(), 'attestation_' . $a['id_demande_attestation']);
    }

    public function dossier(): void
    {
        $id = (int) (Auth::user()['id_employe'] ?? 0);
        $e = Employe::trouver($id);
        if ($e === null) {
            throw new HttpException(404);
        }
        $contrats = Employe::contrats($id);
        $soldes = Employe::soldes($id);
        $lignes = [];
        foreach ($soldes as $s) {
            $lignes[] = [
                (string) $s['libelle'],
                (string) $s['annee'],
                nombre($s['jours_acquis']),
                nombre($s['jours_reserves']),
                nombre($s['jours_pris']),
                nombre($s['jours_disponibles']),
            ];
        }
        $meta = [
            ['Nom', $e['prenom'] . ' ' . $e['nom']],
            ['Matricule', (string) $e['matricule']],
            ['Poste', (string) $e['poste']],
            ['Département', (string) $e['departement']],
            ['E-mail', (string) $e['email']],
            ['Embauche', date_fr($e['date_embauche'])],
        ];
        $paras = [];
        foreach ($contrats as $c) {
            $paras[] = 'Contrat ' . $c['reference'] . ' — ' . $c['type_contrat'] . ' — base ' . montant($c['salaire_base'])
                . ' — du ' . date_fr($c['date_debut']) . ((int) $c['actif'] === 1 ? ' (en cours)' : ' (terminé)');
        }
        $doc = [
            'titre' => 'Fiche employé',
            'sous_titre' => (string) (\App\Core\Config::get('app.name') ?: 'PerPay'),
            'meta' => $meta,
            'paragraphes' => $paras,
            'tableau' => [
                'entetes' => ['Type de congé', 'Année', 'Acquis', 'Réservé', 'Pris', 'Disponible'],
                'lignes' => $lignes,
            ],
            'pied' => 'Export du ' . date('d/m/Y H:i'),
        ];
        ExportDocument::telecharger($doc, $this->format(), 'dossier_' . $e['matricule']);
    }

    private function format(): string
    {
        $f = strtolower($this->saisie($_GET['format'] ?? 'pdf'));
        return in_array($f, ['pdf', 'docx'], true) ? $f : 'pdf';
    }

    /** @param array<string,mixed> $fiche */
    private function envoyerFiche(array $fiche, string $format): never
    {
        $rubriques = FichePaie::rubriques((int) $fiche['id_fiche']);
        $lignes = [];
        foreach ($rubriques as $r) {
            $lignes[] = [(string) $r['libelle'], (string) $r['nature'], montant($r['montant'])];
        }
        $doc = [
            'titre' => 'Bulletin de paie',
            'sous_titre' => mois_fr((int) $fiche['mois']) . ' ' . (int) $fiche['annee'],
            'meta' => [
                ['Employé', $fiche['prenom'] . ' ' . $fiche['nom']],
                ['Matricule', (string) $fiche['matricule']],
                ['Statut', (string) $fiche['statut']],
                ['Salaire brut', montant($fiche['salaire_brut'])],
                ['Total primes', montant($fiche['total_primes'])],
                ['Heures supplémentaires', montant($fiche['total_heures_sup'])],
                ['Retenues', montant($fiche['total_retenues'])],
                ['Net à payer', montant($fiche['salaire_net'])],
            ],
            'tableau' => [
                'entetes' => ['Libellé', 'Nature', 'Montant'],
                'lignes' => $lignes,
            ],
            'pied' => 'Document généré le ' . date('d/m/Y à H:i') . ' — usage interne',
        ];
        ExportDocument::telecharger($doc, $format, 'fiche_' . $fiche['matricule'] . '_' . $fiche['annee'] . '_' . $fiche['mois']);
    }
}
