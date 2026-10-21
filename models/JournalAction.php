<?php

class JournalAction
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

   

    public function inserer(
        int $utilisateurId,
        string $action,
        string $entite,
        ?int $entiteId,
        ?string $ancienne,
        ?string $nouvelle,
        ?string $ip
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO journal_action
                (utilisateur_id, action, entite, entite_id, ancienne_valeur, nouvelle_valeur, adresse_ip, date_heure)
                VALUES
                (:utilisateur_id, :action, :entite, :entite_id, :ancienne, :nouvelle, :ip, NOW())'
        );
        $stmt->execute([
            ':utilisateur_id' => $utilisateurId,
            ':action' => $action,
            ':entite' => $entite,
            ':entite_id' => $entiteId,
            ':ancienne' => $ancienne,
            ':nouvelle' => $nouvelle,
            ':ip' => $ip,
        ]);
    }

    public function compterRecents(int $utilisateurId, string $action, int $minutes): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM journal_action
             WHERE utilisateur_id = :id AND action = :action
               AND date_heure > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)'
        );
        $stmt->bindValue(':id', $utilisateurId, PDO::PARAM_INT);
        $stmt->bindValue(':action', $action);
        $stmt->bindValue(':minutes', $minutes, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}
