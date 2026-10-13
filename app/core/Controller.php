<?php
declare(strict_types=1);

namespace App\Core;

/** Classe de base des contrôleurs : affichage d'une vue dans une mise en page. */
abstract class Controller
{
    /**
     * Affiche app/views/<vue>.php à l'intérieur de la mise en page app/views/layouts/<mise_en_page>.php.
     * Les clés de $donnees deviennent des variables dans la vue.
     */
    protected function render(string $vue, array $donnees = [], string $miseEnPage = 'main'): void
    {
        $flash = Session::pullFlash();
        $user = Auth::user();
        extract($donnees, EXTR_SKIP);
        ob_start();
        require APP_PATH . '/views/' . $vue . '.php';
        $content = ob_get_clean();
        require APP_PATH . '/views/layouts/' . $miseEnPage . '.php';
    }
}
