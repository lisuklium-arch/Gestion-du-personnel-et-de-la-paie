<?php

require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/MailService.php';

class NotificationService
{
    private Notification $notifications;
    private Utilisateur $utilisateurs;
    private MailService $mailService;

    public function __construct(Notification $notifications, Utilisateur $utilisateurs, MailService $mailService)
    {
        $this->notifications = $notifications;
        $this->utilisateurs = $utilisateurs;
        $this->mailService = $mailService;
    }

    public function notifier(
        int $destinataireId,
        string $type,
        string $titre,
        string $message,
        ?string $lien = null,
        bool $parEmail = false
    ): void {
        $titre = mb_substr($titre, 0, 150);
        $message = mb_substr($message, 0, 500);
        $lien = self::lienInterneValide($lien);

        try {
            $id = $this->notifications->creer($destinataireId, $type, $titre, $message, $lien);

            if ($parEmail) {
                $this->envoyerParEmail($id, $destinataireId, $titre, $message);
            }
        } catch (PDOException $e) {
            error_log('Notification : ' . $e->getMessage());
        }
    }

    /** Seuls les chemins internes (/views/...) sont acceptés : pas d'adresse externe ni de javascript:. */
    public static function lienInterneValide(?string $lien): ?string
    {
        if ($lien === null || $lien === '' || strlen($lien) > 255) {
            return null;
        }
        if ($lien[0] !== '/' || str_starts_with($lien, '//') || str_contains($lien, '\\') || preg_match('/[\x00-\x1f\x7f]/', $lien)) {
            return null;
        }
        return $lien;
    }

    public function resume(int $utilisateurId, int $limite = 10): array
    {
        $liste = [];
        foreach ($this->notifications->listerPourUtilisateur($utilisateurId, $limite) as $n) {
            $liste[] = [
                'id'              => (int) $n['id'],
                'type'            => $n['type'],
                'titre'           => $n['titre'],
                'message'         => $n['message'],
                'lien'            => self::lienInterneValide($n['lien']),
                'lue'             => (bool) $n['lue'],
                'depuis_secondes' => max(0, (int) $n['depuis_secondes']),
            ];
        }
        return ['non_lues' => $this->notifications->compterNonLues($utilisateurId), 'notifications' => $liste];
    }

    public function marquerLue(int $id, int $utilisateurId): bool
    {
        return $this->notifications->marquerLue($id, $utilisateurId);
    }

    public function toutesLues(int $utilisateurId): void
    {
        $this->notifications->marquerToutesLues($utilisateurId);
    }

    private function envoyerParEmail(int $notificationId, int $destinataireId, string $titre, string $message): void
    {
        $user = $this->utilisateurs->trouverParId($destinataireId);
        if ($user === null) {
            return;
        }

        $html = '<p><strong>' . htmlspecialchars($titre) . '</strong></p><p>' . nl2br(htmlspecialchars($message)) . '</p>';

        if ($this->mailService->envoyer($user['email'], 'PerPay : ' . $titre, $html)) {
            $this->notifications->marquerEmailEnvoye($notificationId);
        }
    }
}
