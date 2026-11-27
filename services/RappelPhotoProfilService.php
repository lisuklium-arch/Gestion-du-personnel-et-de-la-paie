<?php
require_once __DIR__ . '/NotificationService.php';

class RappelPhotoProfilService
{
    public function __construct(private PDO $pdo, private NotificationService $notifications)
    {
    }

    public function doitAfficher(int $employeId, ?string $photo): bool
    {
        if ($photo !== null && $photo !== '') return false;
        $stmt = $this->pdo->prepare('SELECT masque_jusqua, complete_le FROM rappel_photo_profil WHERE employe_id = :id');
        $stmt->execute([':id' => $employeId]); $rappel = $stmt->fetch();
        if ($rappel === false) { $this->pdo->prepare('INSERT INTO rappel_photo_profil (employe_id) VALUES (:id)')->execute([':id' => $employeId]); $rappel = ['masque_jusqua' => null, 'complete_le' => null]; }
        if ($rappel['complete_le'] !== null || ($rappel['masque_jusqua'] !== null && $rappel['masque_jusqua'] > date('Y-m-d H:i:s'))) return false;
        return true;
    }

    public function notifierSiNecessaire(int $employeId, int $utilisateurId): void
    {
        $stmt = $this->pdo->prepare('SELECT derniere_notification_le FROM rappel_photo_profil WHERE employe_id = :id'); $stmt->execute([':id' => $employeId]); $date = $stmt->fetchColumn();
        if ($date !== false && $date !== null && $date > date('Y-m-d H:i:s', strtotime('-7 days'))) return;
        $this->notifications->notifier($utilisateurId, 'photo_profil_requise', 'Photo de profil à compléter', 'Ajoutez votre photo de profil pour faciliter votre identification dans la plateforme.', '/views/employe/profil_employe.php');
        $this->pdo->prepare('UPDATE rappel_photo_profil SET derniere_notification_le = NOW() WHERE employe_id = :id')->execute([':id' => $employeId]);
    }

    public function masquer(int $employeId): void { $this->pdo->prepare('UPDATE rappel_photo_profil SET masque_jusqua = DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE employe_id = :id')->execute([':id' => $employeId]); }
}
