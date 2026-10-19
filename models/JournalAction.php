<?php

class JournalAction
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Journal append-only : INSERT uniquement, jamais d'UPDATE ni de DELETE.
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
            ':action'         => $action,
            ':entite'         => $entite,
            ':entite_id'      => $entiteId,
            ':ancienne'       => $ancienne,
            ':nouvelle'       => $nouvelle,
            ':ip'             => $ip,
        ]);
    }
}
