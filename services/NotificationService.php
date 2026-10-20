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
        try {
            $id = $this->notifications->creer($destinataireId, $type, $titre, $message, $lien);

            if ($parEmail) {
                $this->envoyerParEmail($id, $destinataireId, $titre, $message);
            }
        } catch (PDOException $e) {
            error_log('Notification : ' . $e->getMessage());
        }
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
