<?php

require_once __DIR__ . '/../models/JournalAction.php';

class JournalService
{
    private JournalAction $journal;

    public function __construct(JournalAction $journal)
    {
        $this->journal = $journal;
    }

    public function enregistrer(
        int $utilisateurId,
        string $action,
        string $entite,
        ?int $entiteId = null,
        ?array $avant = null,
        ?array $apres = null
    ): void {
        $ancienne = $avant === null ? null : json_encode($avant, JSON_UNESCAPED_UNICODE);
        $nouvelle = $apres === null ? null : json_encode($apres, JSON_UNESCAPED_UNICODE);
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        try {
            $this->journal->inserer($utilisateurId, $action, $entite, $entiteId, $ancienne, $nouvelle, $ip);
        } catch (PDOException $e) {
            error_log('Journal : ' . $e->getMessage());
        }
    }
}
