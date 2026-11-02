<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class PaieController extends Controller
{
    public function index(): void
    {
        $this->legacy('paie_rh.php');
    }

    public function voir(): void
    {
        $this->legacy('fiche_paie_detail_employe.php');
    }

    public function generer(): void
    {
        $this->legacy('paie_rh.php');
    }

    public function statut(): void
    {
        $this->legacy('paie_rh.php');
    }

    private function legacy(string $fichier): void
    {
        $chemin = APP_PATH . '/Paie/' . $fichier;
        if (!is_file($chemin)) {
            http_response_code(404);
            echo 'Page de paie introuvable.';
            return;
        }
        require $chemin;
    }
}
