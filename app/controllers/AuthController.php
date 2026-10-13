<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->render('auth/login', ['email' => '', 'erreur' => null], 'auth');
    }

    public function login(): void
    {
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $motDePasse = (string) ($_POST['password'] ?? '');

        // Validation côté serveur (le navigateur peut être contourné).
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150 || $motDePasse === '' || strlen($motDePasse) > 200) {
            $this->echec($email, 'Saisissez une adresse e-mail valide et votre mot de passe.');
            return;
        }

        $resultat = Auth::attempt($email, $motDePasse, client_ip());
        if (!$resultat['ok']) {
            $this->echec($email, $resultat['message']);
            return;
        }
        Session::flash('success', 'Connexion réussie.');
        redirect(Auth::home(Auth::role()));      // toujours vers l'espace du profil : pas de redirection libre
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('info', 'Vous êtes déconnecté.');
        redirect('/login');
    }

    private function echec(string $email, string $message): void
    {
        http_response_code(401);
        $this->render('auth/login', ['email' => $email, 'erreur' => $message], 'auth');
    }
}
