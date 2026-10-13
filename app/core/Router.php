<?php
declare(strict_types=1);

namespace App\Core;

/** Associe l'adresse demandée à un contrôleur, après avoir vérifié connexion, profil et jeton CSRF. */
final class Router
{
    public function __construct(private array $routes)
    {
    }

    public function dispatch(string $methode, string $chemin): void
    {
        try {
            $cheminConnu = false;
            foreach ($this->routes as $route) {
                if ($route['path'] !== $chemin) {
                    continue;
                }
                $cheminConnu = true;
                if ($route['method'] !== $methode) {
                    continue;
                }
                $this->verifier($route);
                [$classe, $action] = explode('@', $route['action']);
                $nom = 'App\\Controllers\\' . $classe;
                (new $nom())->$action();
                return;
            }
            throw new HttpException($cheminConnu ? 405 : 404);
        } catch (HttpException $e) {
            $this->afficherErreur($e);
        }
    }

    /** Contrôles faits AVANT d'exécuter la page. */
    private function verifier(array $route): void
    {
        $middleware = $route['middleware'] ?? [];

        if (in_array('guest', $middleware, true) && Auth::check()) {
            redirect(Auth::home(Auth::role()));                 // déjà connecté : direction son espace
        }
        if (in_array('auth', $middleware, true) && !Auth::check()) {
            Session::flash('info', 'Connectez-vous pour continuer.');
            redirect('/login');
        }
        if (isset($route['roles']) && !Auth::allows($route['roles'])) {
            throw new HttpException(403);
        }
        if ($route['method'] === 'POST') {
            Csrf::check();                                      // TOUS les formulaires POST exigent le jeton CSRF
        }
    }

    private function afficherErreur(HttpException $e): void
    {
        http_response_code($e->status);
        $code = $e->status;
        [$titre, $message] = HttpException::MESSAGES[$code] ?? ['Erreur', 'Une erreur est survenue.'];
        $user = Auth::user();
        $flash = [];
        ob_start();
        require APP_PATH . '/views/errors/erreur.php';
        $content = ob_get_clean();
        require APP_PATH . '/views/layouts/auth.php';
    }
}
