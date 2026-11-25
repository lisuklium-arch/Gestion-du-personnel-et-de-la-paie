<?php

require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/MailService.php';
require_once __DIR__ . '/JournalService.php';
require_once __DIR__ . '/NotificationService.php';

class CompteService
{
    private Utilisateur $utilisateurs;
    private MailService $mailService;
    private JournalService $journalService;
    private ?NotificationService $notifications;

    public function __construct(
        Utilisateur $utilisateurs,
        MailService $mailService,
        JournalService $journalService,
        ?NotificationService $notifications = null
    ) {
        $this->notifications = $notifications;
        $this->utilisateurs = $utilisateurs;
        $this->mailService = $mailService;
        $this->journalService = $journalService;
    }

    public function creer(array $auteur, string $email, string $role): array
    {
        $email = trim($email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Adresse e-mail invalide.'];
        }
        if (!$this->peutCreerRole($auteur['role'], $role)) {
            return ['ok' => false, 'message' => "Vous n'êtes pas autorisé à créer un compte avec ce rôle."];
        }
        if ($this->utilisateurs->emailExiste($email)) {
            return ['ok' => false, 'message' => 'Cette adresse e-mail est déjà utilisée.'];
        }

        $auteurId = (int) $auteur['id'];

        try {
            if ($role === 'rh') {
                $hashInutilisable = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $id = $this->utilisateurs->creer($email, $hashInutilisable, 'rh', 'en_attente', true);
                $motDePasse = null;
            } else {
                $motDePasse = $this->genererMotDePasseTemporaire();
                $id = $this->utilisateurs->creer($email, password_hash($motDePasse, PASSWORD_DEFAULT), $role, 'actif', true, $auteurId);
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return ['ok' => false, 'message' => 'Cette adresse e-mail est déjà utilisée.'];
            }
            throw $e;
        }

        $this->journalService->enregistrer($auteurId, 'creation_compte', 'utilisateur', $id, null, [
            'email' => $email,
            'role' => $role,
            'statut' => $role === 'rh' ? 'en_attente' : 'actif',
        ]);

        if ($role !== 'rh') {
            $this->notifier($id, $role, 'compte_cree', 'Bienvenue sur PerPay',
                'Votre compte est actif. Vous pouvez consulter votre espace personnel.');
        }

        return [
            'ok' => true,
            'message' => $role === 'rh'
                ? 'Compte Responsable RH créé. Il doit être activé par le Directeur général.'
                : 'Compte créé et actif. Les accès vont être envoyés par e-mail.',
            'utilisateur_id' => $id,
            'statut' => $role === 'rh' ? 'en_attente' : 'actif',
            'mot_de_passe_temporaire' => $motDePasse,
        ];
    }

    public function envoyerAcces(int $utilisateurId, string $motDePasseTemporaire): bool
    {
        $user = $this->utilisateurs->trouverParId($utilisateurId);
        if ($user === null) {
            return false;
        }

        $html = '<p>Bonjour,</p>'
            . '<p>Votre compte PerPay a été créé.</p>'
            . '<p>Identifiant : <strong>' . htmlspecialchars($user['email']) . '</strong><br>'
            . 'Mot de passe temporaire : <strong>' . htmlspecialchars($motDePasseTemporaire) . '</strong></p>'
            . '<p>Vous devrez choisir un nouveau mot de passe à votre première connexion.</p>';

        return $this->mailService->envoyer($user['email'], 'PerPay : vos accès', $html);
    }

    public function activer(array $auteur, int $utilisateurId, ?string $commentaire = null): array
    {
        if ($auteur['role'] !== 'dg') {
            return ['ok' => false, 'message' => "Seul le Directeur général peut activer un compte Responsable RH."];
        }

        $cible = $this->utilisateurs->trouverParId($utilisateurId);
        if ($cible === null || $cible['role'] !== 'rh' || $cible['statut_compte'] !== 'en_attente') {
            return ['ok' => false, 'message' => "Ce compte n'est pas en attente d'activation."];
        }

        $motDePasse = $this->genererMotDePasseTemporaire();
        if (!$this->utilisateurs->valider($utilisateurId, (int) $auteur['id'], password_hash($motDePasse, PASSWORD_DEFAULT))) {
            return ['ok' => false, 'message' => "Ce compte n'est pas en attente d'activation."];
        }

        $this->journalService->enregistrer((int) $auteur['id'], 'activation_compte', 'utilisateur', $utilisateurId, null, [
            'statut' => 'actif',
            'commentaire' => $commentaire,
        ]);

        $envoye = $this->envoyerAcces($utilisateurId, $motDePasse);
        $this->notifier($utilisateurId, 'rh', 'compte_active', 'Votre compte a été activé',
            'Votre compte Responsable RH est actif. Vos accès vous ont été envoyés par e-mail.');

        return [
            'ok' => true,
            'message' => $envoye
                ? 'Compte activé. Les accès ont été envoyés par e-mail.'
                : "Compte activé, mais l'e-mail d'accès n'a pas pu être envoyé.",
        ];
    }

    public function desactiver(array $auteur, int $utilisateurId): array
    {
        $cible = $this->cibleModifiable($auteur, $utilisateurId);
        if (isset($cible['ok'])) {
            return $cible;
        }
        if ($cible['statut_compte'] !== 'actif') {
            return ['ok' => false, 'message' => "Ce compte n'est pas actif."];
        }

        $this->utilisateurs->changerStatut($utilisateurId, 'desactive');
        $this->journalService->enregistrer((int) $auteur['id'], 'desactivation_compte', 'utilisateur', $utilisateurId,
            ['statut' => 'actif'], ['statut' => 'desactive']);

        return ['ok' => true, 'message' => 'Compte désactivé.'];
    }

    public function reactiver(array $auteur, int $utilisateurId): array
    {
        $cible = $this->cibleModifiable($auteur, $utilisateurId);
        if (isset($cible['ok'])) {
            return $cible;
        }
        if ($cible['statut_compte'] !== 'desactive' || $cible['date_validation'] === null) {
            return ['ok' => false, 'message' => "Ce compte ne peut pas être réactivé."];
        }

        $this->utilisateurs->changerStatut($utilisateurId, 'actif');
        $this->journalService->enregistrer((int) $auteur['id'], 'reactivation_compte', 'utilisateur', $utilisateurId,
            ['statut' => 'desactive'], ['statut' => 'actif']);

        $this->notifier($utilisateurId, (string) $cible['role'], 'compte_reactive', 'Votre compte a été réactivé',
            "Votre accès à l'application a été rétabli.");

        return ['ok' => true, 'message' => 'Compte réactivé.'];
    }

    public function lister(array $auteur, ?string $role = null, ?string $statut = null): array
    {
        if ($auteur['role'] === 'dg') {
            return $this->utilisateurs->lister($role, $statut);
        }

        $comptes = $this->utilisateurs->lister($role, $statut);
        return array_values(array_filter($comptes, fn($c) => in_array($c['role'], ['employe', 'chef'], true)));
    }

    private function peutCreerRole(string $roleAuteur, string $roleCree): bool
    {
        return match ($roleAuteur) {
            'dg' => in_array($roleCree, ['employe', 'chef', 'rh'], true),
            'rh' => in_array($roleCree, ['employe', 'chef'], true),
            default => false,
        };
    }

    private function cibleModifiable(array $auteur, int $utilisateurId): array
    {
        if ((int) $auteur['id'] === $utilisateurId) {
            return ['ok' => false, 'message' => 'Vous ne pouvez pas modifier votre propre compte.'];
        }

        $cible = $this->utilisateurs->trouverParId($utilisateurId);
        if ($cible === null || !$this->peutGererRole($auteur['role'], $cible['role'])) {
            return ['ok' => false, 'message' => "Vous n'êtes pas autorisé à gérer ce compte."];
        }
        return $cible;
    }

    private function notifier(int $destinataireId, string $role, string $type, string $titre, string $message): void
    {
        if ($this->notifications === null) {
            return;
        }
        $lien = function_exists('accueilPourRole') ? accueilPourRole($role) : null;
        $this->notifications->notifier($destinataireId, $type, $titre, $message, $lien);
    }

    public static function actionPossible(array $auteur, array $cible): ?string
    {
        if ((int) $auteur['id'] === (int) $cible['id'] || !self::peutGererRole($auteur['role'], $cible['role'])) {
            return null;
        }
        return match ($cible['statut_compte']) {
            'actif' => 'desactiver',
            'desactive' => $cible['date_validation'] !== null ? 'reactiver' : null,
            'en_attente' => ($auteur['role'] === 'dg' && $cible['role'] === 'rh') ? 'activer' : null,
            default => null,
        };
    }

    private static function peutGererRole(string $roleAuteur, string $roleCible): bool
    {
        return match ($roleAuteur) {
            'dg' => in_array($roleCible, ['employe', 'chef', 'rh'], true),
            'rh' => in_array($roleCible, ['employe', 'chef'], true),
            default => false,
        };
    }

    private function genererMotDePasseTemporaire(int $longueur = 10): string
    {
        $lettres = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
        $chiffres = '23456789';
        $tout = $lettres . $chiffres;

        $caracteres = [$lettres[random_int(0, strlen($lettres) - 1)], $chiffres[random_int(0, strlen($chiffres) - 1)]];
        for ($i = count($caracteres); $i < $longueur; $i++) {
            $caracteres[] = $tout[random_int(0, strlen($tout) - 1)];
        }
        for ($i = count($caracteres) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$caracteres[$i], $caracteres[$j]] = [$caracteres[$j], $caracteres[$i]];
        }
        return implode('', $caracteres);
    }
}
