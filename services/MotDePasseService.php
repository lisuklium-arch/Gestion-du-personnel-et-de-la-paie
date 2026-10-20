<?php

require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../models/JetonReinitialisation.php';
require_once __DIR__ . '/MailService.php';
require_once __DIR__ . '/JournalService.php';

class MotDePasseService
{
    private const VALIDITE_MINUTES      = 15;
    private const DELAI_RENVOI_SECONDES = 180;
    private const ESSAIS_MAX            = 5;
    private const FENETRE_ESSAIS_MIN    = 10;
    private const MDP_LONGUEUR_MIN      = 8;

    private const MESSAGE_NEUTRE = "Si ce compte existe, un code de vérification vient d'être envoyé. "
                                 . "Vous pourrez en demander un nouveau dans 3 minutes.";

    private Utilisateur $utilisateurs;
    private JetonReinitialisation $jetons;
    private MailService $mailService;
    private JournalService $journalService;

    public function __construct(
        Utilisateur $utilisateurs,
        JetonReinitialisation $jetons,
        MailService $mailService,
        JournalService $journalService
    ) {
        $this->utilisateurs   = $utilisateurs;
        $this->jetons         = $jetons;
        $this->mailService    = $mailService;
        $this->journalService = $journalService;
    }

    public function demanderCode(string $email): array
    {
        $reponse = ['ok' => true, 'message' => self::MESSAGE_NEUTRE];

        $user = $this->utilisateurs->trouverParEmail(trim($email));
        if ($user === null || $user['statut_compte'] !== 'actif') {
            return $reponse;
        }
        $id = (int) $user['id'];

        $restantes = $this->jetons->secondesRestantesDernier($id);
        if ($restantes !== null && $restantes > self::VALIDITE_MINUTES * 60 - self::DELAI_RENVOI_SECONDES) {
            return $reponse;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->jetons->invaliderActifs($id);
        $this->jetons->creer($id, $this->hasherCode($id, $code), self::VALIDITE_MINUTES);

        $this->mailService->envoyerCodeReinitialisation($user['email'], $code, self::VALIDITE_MINUTES);
        $this->journalService->enregistrer($id, 'demande_code_mdp', 'utilisateur', $id);

        return $reponse;
    }

    public function verifierCode(string $email, string $code): array
    {
        $code = trim($code);
        if (!preg_match('/^\d{6}$/', $code)) {
            return ['ok' => false, 'message' => 'Le code doit contenir 6 chiffres.'];
        }

        $invalide = ['ok' => false, 'message' => 'Code incorrect ou expiré.'];

        $user = $this->utilisateurs->trouverParEmail(trim($email));
        if ($user === null || $user['statut_compte'] !== 'actif') {
            return $invalide;
        }
        $id = (int) $user['id'];

        $echecs = $this->journalService->compterRecents($id, 'code_mdp_invalide', self::FENETRE_ESSAIS_MIN);
        if ($echecs >= self::ESSAIS_MAX) {
            return ['ok' => false, 'message' => 'Trop de tentatives. Demandez un nouveau code dans quelques minutes.'];
        }

        $jeton = $this->hasherCode($id, $code);
        if (!$this->jetons->estValable($id, $jeton)) {
            $this->journalService->enregistrer($id, 'code_mdp_invalide', 'utilisateur', $id);
            return $invalide;
        }

        return ['ok' => true, 'utilisateur_id' => $id, 'jeton' => $jeton];
    }

    public function changerMotDePasse(int $utilisateurId, string $jeton, string $nouveau, string $confirmation): array
    {
        if (strlen($nouveau) < self::MDP_LONGUEUR_MIN
            || !preg_match('/[A-Za-z]/', $nouveau)
            || !preg_match('/[0-9]/', $nouveau)) {
            return ['ok' => false, 'message' => 'Le mot de passe doit contenir au moins ' . self::MDP_LONGUEUR_MIN
                . ' caractères, avec au moins une lettre et un chiffre.'];
        }
        if ($nouveau !== $confirmation) {
            return ['ok' => false, 'message' => 'Les deux mots de passe ne correspondent pas.'];
        }

        if (!$this->jetons->consommer($utilisateurId, $jeton)) {
            return ['ok' => false, 'recommencer' => true, 'message' => 'Le code a expiré ou a déjà été utilisé. Recommencez la procédure.'];
        }

        $this->utilisateurs->majMotDePasse($utilisateurId, password_hash($nouveau, PASSWORD_DEFAULT));
        $this->jetons->invaliderActifs($utilisateurId);
        $this->journalService->enregistrer($utilisateurId, 'mdp_reinitialise', 'utilisateur', $utilisateurId);

        return ['ok' => true, 'message' => 'Votre mot de passe a été modifié. Vous pouvez vous connecter.'];
    }

    private function hasherCode(int $utilisateurId, string $code): string
    {
        return hash('sha256', $utilisateurId . ':' . $code);
    }
}
