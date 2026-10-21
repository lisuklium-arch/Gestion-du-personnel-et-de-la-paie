<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class MailService
{
    private array $cfg;

    public function __construct(array $cfg)
    {
        $this->cfg = $cfg;
    }

   
    public function envoyerCodeReinitialisation(string $email, string $code, int $minutesValidite): bool
    {
        $sujet = 'PerPay : votre code de réinitialisation';
        $texte = "Votre code de vérification est : $code\n"
            . "Il est valable $minutesValidite minutes et n'est utilisable qu'une seule fois.\n"
            . "Si vous n'êtes pas à l'origine de cette demande, ignorez ce message.";
        $html = '<p>Votre code de vérification est :</p>'
            . '<p style="font-size:24px;letter-spacing:4px"><strong>' . htmlspecialchars($code) . '</strong></p>'
            . "<p>Il est valable $minutesValidite minutes et n'est utilisable qu'une seule fois.</p>"
            . "<p>Si vous n'êtes pas à l'origine de cette demande, ignorez ce message.</p>";

        return $this->envoyer($email, $sujet, $html, $texte);
    }

    private function envoyer(string $destinataire, string $sujet, string $html, string $texte): bool
    {
        $mail = new PHPMailer(true); 

        try {
            $mail->isSMTP();
            $mail->Host = $this->cfg['host'];
            $mail->Port = (int) $this->cfg['port'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->cfg['user'];
            $mail->Password = $this->cfg['pass'];
            $mail->SMTPSecure = ($this->cfg['chiffrement'] === 'ssl')
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($this->cfg['expediteur_email'], $this->cfg['expediteur_nom']);
            $mail->addAddress($destinataire);

            $mail->isHTML(true);
            $mail->Subject = $sujet;
            $mail->Body = $html;
            $mail->AltBody = $texte;

            $mail->send();
            return true;    
        } catch (MailException $e) {
            
            error_log('MailService : ' . $mail->ErrorInfo);
            return false;
        }
    }
}