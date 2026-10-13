<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

/** Pages d'accueil par profil (le contenu réel sera ajouté dans les prochaines étapes). */
final class DashboardController extends Controller
{
    public function index(): void
    {
        redirect(Auth::home(Auth::role()));
    }

    public function employe(): void
    {
        $this->espace('Espace employé', 'Votre portail personnel : congés, fiches de paie et documents.');
    }

    public function chef(): void
    {
        $this->espace('Espace chef de département', "Avis sur les congés, heures supplémentaires et présence de votre équipe.");
    }

    public function rh(): void
    {
        $this->espace('Espace Responsable RH', 'Employés, contrats, congés, paie et documents.');
    }

    public function direction(): void
    {
        $this->espace('Espace direction', "Vue d'ensemble de l'organisation, comptes RH et journal des actions.");
    }

    private function espace(string $titre, string $intro): void
    {
        $menu = (require APP_PATH . '/config/menu.php')[Auth::role()] ?? [];
        $this->render('dashboard/accueil', ['titre' => $titre, 'intro' => $intro, 'menu' => $menu]);
    }
}
