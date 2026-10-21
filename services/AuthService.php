<?php
// Service d'authentification : règles de la connexion. Pas de SQL direct, pas de HTML.

require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../models/Employe.php';
require_once __DIR__ . '/../services/JournalService.php';

class AuthService
{
    // Faux hash servant à garder un temps de réponse similaire quand l'e-mail n'existe pas
    private const HASH_FACTICE = '$2y$10$3aRm/oijEhdn34nVnL9Kx.NgXI5vsKy5tr3/E4pGtj/J3EcWXXmiq';

    private Utilisateur $utilisateurs;
    private Employe $employes;
    private JournalService $journalService;

    public function __construct(Utilisateur $utilisateurs ,Employe $employes , JournalService $journalService)
    {
        $this->utilisateurs = $utilisateurs;
        $this->employes = $employes;
        $this->journalService = $journalService;
    }

    /**
     * Tente une connexion.
     * Succès : ['ok' => true, 'role' => ..., 'doit_changer_mdp' => bool]
     * Échec  : ['ok' => false, 'message' => '...']
     */
    public function connecter(string $email, string $motDePasse): array
    {
        $email = trim($email);
        $user  = $this->utilisateurs->trouverParEmail($email);

        // Même message pour "e-mail inconnu" et "mot de passe faux"
        $hash = $user['mot_de_passe_hash'] ?? self::HASH_FACTICE;
        if ($user === null || !password_verify($motDePasse, $hash)) {
            return ['ok' => false, 'message' => 'Identifiants incorrects.'];
        }

        // Le mot de passe est bon : on peut dire pourquoi l'accès est refusé
        if ($user['statut_compte'] === 'en_attente') {
            return ['ok' => false, 'message' => "Votre compte n'est pas encore activé."];
        }
        if ($user['statut_compte'] !== 'actif') {
            return ['ok' => false, 'message' => 'Votre compte est désactivé.'];
        }

        

        // Ouverture de session
        session_regenerate_id(true);
        unset($_SESSION['csrf']);
        $_SESSION['utilisateur_id']   = (int) $user['id'];
        $_SESSION['role']             = $user['role'];
        $_SESSION['doit_changer_mdp'] = (bool) $user['doit_changer_mdp'];
        
        $employee = $this->employes->trouverParUtilisateurId((int) $user['id']);
        if ($employee !== null) {
            $_SESSION['nom_affiche'] = $employee['prenom'] . ' ' . $employee['nom'];
        } else {
            // Pas de dossier employé (cas du DG) : on affiche l'e-mail
            $_SESSION['nom_affiche'] = $user['email'];
        }

        $this->utilisateurs->majDerniereConnexion((int) $user['id']);
        $this->journalService->enregistrer($_SESSION['utilisateur_id'],'connexion','utilisateur',$_SESSION['utilisateur_id'],null,null);


        return [
            'ok'               => true,
            'role'             => $user['role'],
            'doit_changer_mdp' => (bool) $user['doit_changer_mdp'],
        ];
    }

    /** Ferme la session de l'utilisateur. */
    public function deconnecter(): void
    {
        if (isset($_SESSION['utilisateur_id'])) {
            $id = (int) $_SESSION['utilisateur_id'];
            $this->journalService->enregistrer($id, 'deconnexion', 'utilisateur', $id);
        }
    
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
