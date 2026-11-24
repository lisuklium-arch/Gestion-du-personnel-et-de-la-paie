<?php
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/MailService.php';

/** Notifications domaine C — échoue silencieusement si mail/config incomplet. */
class CongesNotificationHelper
{
    private PDO $pdo;
    private NotificationService $notif;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->notif = new NotificationService(
            new Notification($pdo),
            new Utilisateur($pdo),
            new MailService($pdo)
        );
    }

    public function apresDepot(array $demande, int $employeId): void
    {
        $chefUid = $this->chefUtilisateurId($employeId);
        $lien = '/views/cd/avis_conge_detail_cd.php?id=' . (int) $demande['id'];
        if ($demande['statut'] === 'en_attente_chef' && $chefUid) {
            $this->safe($chefUid, 'conge', 'Nouvelle demande de congé',
                'Une demande nécessite votre avis.', $lien);
        } elseif ($demande['statut'] === 'en_attente_rh') {
            foreach ($this->utilisateursRole('rh') as $uid) {
                $this->safe($uid, 'conge', 'Demande de congé à traiter',
                    'Une demande est en attente de décision RH.', '/views/rh/conges_rh.php');
            }
        } elseif ($demande['statut'] === 'en_attente_dg') {
            foreach ($this->utilisateursRole('dg') as $uid) {
                $this->safe($uid, 'conge', 'Congé RH à valider',
                    'Une demande d’un responsable RH attend votre décision.', '/views/dg/conges_dg.php');
            }
        }
    }

    public function apresDecision(int $demandeId, int $employeUtilisateurId, string $statut, string $resume): void
    {
        $this->safe(
            $employeUtilisateurId,
            'conge',
            'Mise à jour de votre demande de congé',
            $resume . ' (demande #' . $demandeId . ').',
            '/views/employe/conge_detail_employe.php?id=' . $demandeId
        );
        if ($statut === 'en_attente_rh') {
            foreach ($this->utilisateursRole('rh') as $uid) {
                $this->safe($uid, 'conge', 'Avis chef reçu — décision RH',
                    'Une demande est prête pour décision RH.', '/views/rh/conges_rh.php');
            }
        }
        if ($statut === 'en_attente_dg') {
            foreach ($this->utilisateursRole('dg') as $uid) {
                $this->safe($uid, 'conge', 'Congé en attente DG',
                    'Une demande attend la direction.', '/views/dg/conges_dg.php');
            }
        }
    }

    private function safe(int $uid, string $type, string $titre, string $msg, string $lien): void
    {
        try {
            $this->notif->notifier($uid, $type, $titre, $msg, $lien, false);
        } catch (Throwable $e) {
            error_log('Conges notif: ' . $e->getMessage());
        }
    }

    private function chefUtilisateurId(int $employeId): ?int
    {
        $st = $this->pdo->prepare(
            'SELECT chef.utilisateur_id
             FROM employe e
             JOIN departement d ON d.id = e.departement_id
             JOIN employe chef ON chef.id = d.chef_id
             WHERE e.id = ?'
        );
        $st->execute([$employeId]);
        $v = $st->fetchColumn();
        return $v ? (int) $v : null;
    }

    private function utilisateursRole(string $role): array
    {
        $st = $this->pdo->prepare(
            "SELECT id FROM utilisateur WHERE role = ? AND statut_compte = 'actif'"
        );
        $st->execute([$role]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }
}
