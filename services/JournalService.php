<?php

require_once __DIR__ . '/../models/JournalAction.php';

class JournalService
{
    private JournalAction $journal;

    public function __construct(JournalAction $journal)
    {
        $this->journal = $journal;
    }

    public function compterRecents(int $utilisateurId, string $action, int $minutes): int
{
    return $this->journal->compterRecents($utilisateurId, $action, $minutes);
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

    public const PAR_PAGE = 50;

    private const ACTIONS_SECURITE = [
        'connexion', 'deconnexion', 'echec_connexion', 'demande_code_mdp', 'code_mdp_invalide',
        'mdp_reinitialise', 'mdp_temporaire_change', 'echec_mdp_actuel',
    ];
    private const ACTIONS_COMPTE = ['creation_compte', 'activation_compte', 'desactivation_compte', 'reactivation_compte'];

    public const TYPES_DG = [
        ''         => 'Comptes RH et congés RH',
        'compte'   => 'Comptes RH',
        'conge'    => 'Congés RH',
        'securite' => 'Connexions et mots de passe',
    ];

    /**
     * Ce que chaque rôle a le droit de lire.
     * DG : supervision (comptes RH, congés RH, sécurité). RH : actions métier, sans la sécurité ni les comptes RH.
     */
    private function perimetre(string $role, string $type): ?array
    {
        if ($role === 'dg') {
            $compte = ['entites' => ['utilisateur'], 'actions' => self::ACTIONS_COMPTE, 'roles_cible' => ['rh']];
            $conge = ['entites' => ['demande_conge']];
            return match ($type) {
                'compte'   => ['groupes' => [$compte]],
                'conge'    => ['groupes' => [$conge]],
                'securite' => ['groupes' => [['actions' => self::ACTIONS_SECURITE]]],
                default    => ['groupes' => [$compte, $conge]],
            };
        }
        if ($role === 'rh') {
            return ['actions_exclues' => array_merge(self::ACTIONS_SECURITE, ['creation_compte']), 'roles_cible_exclus' => ['rh', 'dg']];
        }
        return null;
    }

    /**
     * Lecture du journal pour un rôle. Demande : du, au, auteur_id, action, type (DG), page.
     * Retourne lignes, total, pages, page, auteurs, actions.
     */
    public function lire(string $role, array $demande): array
    {
        $perimetre = $this->perimetre($role, (string) ($demande['type'] ?? ''));
        if ($perimetre === null) {
            return ['lignes' => [], 'total' => 0, 'pages' => 1, 'page' => 1, 'auteurs' => [], 'actions' => []];
        }

        $filtres = $perimetre + array_intersect_key($demande, array_flip(['du', 'au', 'auteur_id', 'action']));
        $total = $this->journal->compter($filtres);
        $pages = max(1, (int) ceil($total / self::PAR_PAGE));
        $page = min(max(1, (int) ($demande['page'] ?? 1)), $pages);

        return [
            'lignes'  => $this->journal->rechercher($filtres, self::PAR_PAGE, ($page - 1) * self::PAR_PAGE),
            'total'   => $total,
            'pages'   => $pages,
            'page'    => $page,
            'auteurs' => $this->journal->auteurs($perimetre),
            'actions' => $this->journal->actionsPresentes($perimetre),
        ];
    }
}
