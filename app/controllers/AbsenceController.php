<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Absence;
use App\Models\Journal;
use DomainException;
use PDOException;

/** Le chef marque une absence. Ce n'est pas un pointage des présents. */
final class AbsenceController extends Controller
{
    public function index(): void
    {
        $this->page([]);
    }

    public function marquer(): void
    {
        $idEmploye = (int) $this->saisie($_POST['id_employe'] ?? '');
        $date = $this->dateIso($this->saisie($_POST['date_absence'] ?? ''));
        $motif = $this->saisie($_POST['motif'] ?? '');
        $justifiee = isset($_POST['justifiee']);
        if ($date === null) {
            $this->page(['date_absence' => 'Date invalide.']);
            return;
        }
        try {
            Absence::marquer(Auth::user()['id_utilisateur'], $idEmploye, $date, $motif, $justifiee);
        } catch (DomainException $e) {
            $this->page(['global' => $e->getMessage()]);
            return;
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            $this->page(['global' => 'Cette date est déjà marquée pour cet employé.']);
            return;
        }
        Journal::enregistrer(Auth::user()['id_utilisateur'], 'Marquage d\'une absence', 'absence', $idEmploye, $date, client_ip());
        Session::flash('success', $justifiee ? 'Absence justifiée enregistrée.' : 'Absence non justifiée enregistrée. La paie pourra la lire.');
        redirect(request_path());
    }

    /** @param array<string,string> $erreurs */
    private function page(array $erreurs): void
    {
        if ($erreurs !== []) {
            http_response_code(422);
        }
        $id = Auth::user()['id_utilisateur'];
        $this->render('conges/absences', [
            'titre' => 'Absences de l\'équipe',
            'equipe' => Absence::equipeDe($id),
            'absences' => Absence::recentesDuChef($id),
            'erreurs' => $erreurs,
            'v' => [
                'id_employe' => $this->saisie($_POST['id_employe'] ?? ''),
                'date_absence' => $this->saisie($_POST['date_absence'] ?? ''),
                'motif' => $this->saisie($_POST['motif'] ?? ''),
                'justifiee' => isset($_POST['justifiee']),
            ],
        ]);
    }
}
