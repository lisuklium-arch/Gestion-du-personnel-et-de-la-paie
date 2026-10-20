<?php

class JetonReinitialisation
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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

   
    public function secondesRestantesDernier(int $utilisateurId): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT TIMESTAMPDIFF(SECOND, NOW(), expire_le) AS restantes
             FROM jeton_reinitialisation
             WHERE utilisateur_id = :id
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([':id' => $utilisateurId]);
        $ligne = $stmt->fetch();
        return $ligne ? (int) $ligne['restantes'] : null;
    }

    public function invaliderActifs(int $utilisateurId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE jeton_reinitialisation SET utilise_le = NOW()
             WHERE utilisateur_id = :id AND utilise_le IS NULL'
        );
        $stmt->execute([':id' => $utilisateurId]);
    }

    public function creer(int $utilisateurId, string $jetonHash, int $minutesValidite): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO jeton_reinitialisation (utilisateur_id, jeton_hash, expire_le)
             VALUES (:id, :hash, DATE_ADD(NOW(), INTERVAL :minutes MINUTE))'
        );
        $stmt->bindValue(':id', $utilisateurId, PDO::PARAM_INT);
        $stmt->bindValue(':hash', $jetonHash);
        $stmt->bindValue(':minutes', $minutesValidite, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function consommer(int $utilisateurId, string $jetonHash): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE jeton_reinitialisation SET utilise_le = NOW()
             WHERE utilisateur_id = :id AND jeton_hash = :hash
               AND utilise_le IS NULL AND expire_le > NOW()'
        );
        $stmt->execute([':id' => $utilisateurId, ':hash' => $jetonHash]);
        return $stmt->rowCount() === 1;
    }

    public function estValable(int $utilisateurId, string $jetonHash): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM jeton_reinitialisation
             WHERE utilisateur_id = :id AND jeton_hash = :hash
               AND utilise_le IS NULL AND expire_le > NOW()
             LIMIT 1'
        );
        $stmt->execute([':id' => $utilisateurId, ':hash' => $jetonHash]);
        return $stmt->fetchColumn() !== false;
    }
}
