<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Models\Notification;
use Throwable;

/**
 * E-mail de la décision, envoyé après le commit.
 * Un échec SMTP ne défait pas la décision : la notification interne reste.
 */
final class CourrierConge
{
    /** @var list<int> */
    private static array $file = [];

    public static function planifier(int $idNotification): void
    {
        if ($idNotification > 0) {
            self::$file[] = $idNotification;
        }
    }

    public static function annulerFile(): void
    {
        self::$file = [];
    }

    public static function expedier(): void
    {
        $ids = self::$file;
        self::$file = [];
        if ($ids === []) {
            return;
        }
        $cfg = Config::get('mail', []);
        if (!is_array($cfg) || trim((string) ($cfg['host'] ?? '')) === '' || trim((string) ($cfg['expediteur_email'] ?? '')) === '') {
            return;
        }
        $autoload = ROOT_PATH . '/vendor/autoload.php';
        $classe = ROOT_PATH . '/services/MailService.php';
        if (!is_file($autoload) || !is_file($classe)) {
            error_log('Courrier congé : PHPMailer ou MailService absent, e-mail non envoyé.');
            return;
        }
        require_once $autoload;
        require_once $classe;
        $mail = new \MailService($cfg);
        $base = rtrim((string) Config::get('app.url', ''), '/');
        foreach (Notification::aExpedier($ids) as $n) {
            $texte = $n['message'];
            if ($n['lien'] !== null) {
                $adresse = $base !== '' ? $base . $n['lien'] : $n['lien'];
                $texte .= "\n\nOuvrir la demande : " . $adresse;
            }
            try {
                if ($mail->envoyerMessage($n['email'], $n['titre'], $texte)) {
                    Notification::marquerEmail($n['id_notification']);
                }
            } catch (Throwable $e) {
                error_log('Courrier congé : ' . $e->getMessage());
            }
        }
    }
}
