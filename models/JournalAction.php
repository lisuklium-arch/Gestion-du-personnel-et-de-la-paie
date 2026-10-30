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

    private const JOINTURES = " FROM journal_action j
        JOIN utilisateur ua ON ua.id = j.utilisateur_id
        LEFT JOIN employe ea ON ea.utilisateur_id = ua.id
        LEFT JOIN employe ec ON j.entite = 'employe' AND ec.id = j.entite_id
        LEFT JOIN utilisateur uc ON uc.id = CASE WHEN j.entite = 'utilisateur' THEN j.entite_id ELSE ec.utilisateur_id END
        LEFT JOIN employe en ON en.utilisateur_id = uc.id";

    public function rechercher(array $filtres, int $limite, int $decalage): array
    {
        [$where, $params] = $this->construireFiltres($filtres);
        $sql = "SELECT j.id, j.date_heure, j.action, j.entite, j.entite_id, j.ancienne_valeur, j.nouvelle_valeur, j.adresse_ip,
                       ua.email AS auteur_email, ua.role AS auteur_role, ea.prenom AS auteur_prenom, ea.nom AS auteur_nom,
                       uc.email AS cible_email, uc.role AS cible_role,
                       en.prenom AS cible_prenom, en.nom AS cible_nom, en.matricule AS cible_matricule"
            . self::JOINTURES . $where . ' ORDER BY j.date_heure DESC, j.id DESC LIMIT :limite OFFSET :decalage';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $cle => $valeur) {
            $stmt->bindValue($cle, $valeur);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':decalage', $decalage, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function compter(array $filtres): int
    {
        [$where, $params] = $this->construireFiltres($filtres);
        $stmt = $this->pdo->prepare('SELECT COUNT(*)' . self::JOINTURES . $where);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function auteurs(array $filtres): array
    {
        [$where, $params] = $this->construireFiltres($filtres);
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT ua.id, ua.email, ea.prenom, ea.nom' . self::JOINTURES . $where . ' ORDER BY ea.nom, ea.prenom, ua.email'
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function actionsPresentes(array $filtres): array
    {
        [$where, $params] = $this->construireFiltres($filtres);
        $stmt = $this->pdo->prepare('SELECT DISTINCT j.action' . self::JOINTURES . $where . ' ORDER BY j.action');
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Clés acceptées : du, au (Y-m-d), auteur_id, action, groupes (liste de blocs reliés par OU),
     * actions_exclues, roles_cible_exclus.
     * Un bloc peut contenir : entites, actions, roles_cible (listes de valeurs autorisées, reliées par ET entre elles).
     */
    private function construireFiltres(array $f): array
    {
        $conditions = [];
        $params = [];
        $n = 0;

        $liste = function (string $colonne, array $valeurs, bool $exclure = false) use (&$params, &$n): string {
            $marques = [];
            foreach (array_values($valeurs) as $valeur) {
                $cle = ':p' . $n++;
                $params[$cle] = $valeur;
                $marques[] = $cle;
            }
            return $colonne . ($exclure ? ' NOT IN (' : ' IN (') . implode(', ', $marques) . ')';
        };

        if (!empty($f['du'])) {
            $conditions[] = 'j.date_heure >= :du';
            $params[':du'] = $f['du'] . ' 00:00:00';
        }
        if (!empty($f['au'])) {
            $conditions[] = 'j.date_heure < :au';
            $params[':au'] = (new DateTime($f['au']))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
        }
        if (!empty($f['auteur_id'])) {
            $conditions[] = 'j.utilisateur_id = :auteur_id';
            $params[':auteur_id'] = (int) $f['auteur_id'];
        }
        if (!empty($f['action'])) {
            $conditions[] = 'j.action = :action';
            $params[':action'] = $f['action'];
        }
        if (!empty($f['actions_exclues'])) {
            $conditions[] = $liste('j.action', $f['actions_exclues'], true);
        }
        if (!empty($f['roles_cible_exclus'])) {
            $conditions[] = '(uc.role IS NULL OR ' . $liste('uc.role', $f['roles_cible_exclus'], true) . ')';
        }
        if (!empty($f['groupes'])) {
            $blocs = [];
            foreach ($f['groupes'] as $groupe) {
                $parties = [];
                if (!empty($groupe['entites']))     { $parties[] = $liste('j.entite', $groupe['entites']); }
                if (!empty($groupe['actions']))     { $parties[] = $liste('j.action', $groupe['actions']); }
                if (!empty($groupe['roles_cible'])) { $parties[] = $liste('uc.role', $groupe['roles_cible']); }
                $blocs[] = '(' . ($parties ? implode(' AND ', $parties) : '1 = 1') . ')';
            }
            $conditions[] = '(' . implode(' OR ', $blocs) . ')';
        }

        return [$conditions ? ' WHERE ' . implode(' AND ', $conditions) : '', $params];
    }
}
