<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Employés : tables employe, utilisateur (compte), contrat, solde_conge. */
final class Employe
{
    public const PAR_PAGE = 10;

    public static function departements(): array
    {
        return Database::all('SELECT id_departement, nom FROM departement ORDER BY nom');
    }

    /** Employés encore en poste, pour les listes déroulantes. */
    public static function pourSelection(): array
    {
        return Database::all("SELECT id_employe, matricule, nom, prenom FROM employe WHERE statut <> 'SORTI' ORDER BY nom, prenom");
    }

    public static function postes(): array
    {
        return Database::all('SELECT id_poste, intitule FROM poste ORDER BY intitule');
    }

    /** Recherche + filtres + pagination (utilisée par la recherche instantanée AJAX). */
    public static function rechercher(array $f, int $page): array
    {
        $where = ['1 = 1'];
        $p = [];
        if ($f['q'] !== '') {
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';          // % et _ saisis par l'utilisateur sont pris au sens littéral
            $where[] = "(e.nom LIKE :q1 OR e.prenom LIKE :q2 OR e.matricule LIKE :q3 OR u.email LIKE :q4 OR CONCAT(e.prenom, ' ', e.nom) LIKE :q5)";
            foreach ([1, 2, 3, 4, 5] as $i) {
                $p["q$i"] = $like;
            }
        }
        if ($f['departement'] > 0) { $where[] = 'e.id_departement = :dep'; $p['dep'] = $f['departement']; }
        if ($f['poste'] > 0)       { $where[] = 'e.id_poste = :poste';      $p['poste'] = $f['poste']; }
        if ($f['statut'] !== '')   { $where[] = 'e.statut = :statut';       $p['statut'] = $f['statut']; }

        $from = 'FROM employe e JOIN utilisateur u ON u.id_utilisateur = e.id_utilisateur
                 JOIN departement d ON d.id_departement = e.id_departement JOIN poste po ON po.id_poste = e.id_poste
                 WHERE ' . implode(' AND ', $where);
        $total = (int) Database::value("SELECT COUNT(*) $from", $p);
        $pages = max(1, (int) ceil($total / self::PAR_PAGE));
        $page = min(max(1, $page), $pages);
        $limit = self::PAR_PAGE;
        $offset = ($page - 1) * self::PAR_PAGE;                        // entiers : sûrs à insérer dans LIMIT
        $lignes = Database::all("SELECT e.id_employe AS id, e.matricule, e.nom, e.prenom, u.email,
                                        d.nom AS departement, po.intitule AS poste, e.statut
                                 $from ORDER BY e.nom, e.prenom LIMIT $limit OFFSET $offset", $p);
        return ['lignes' => $lignes, 'total' => $total, 'page' => $page, 'pages' => $pages,
                'debut' => $total === 0 ? 0 : $offset + 1, 'fin' => $offset + count($lignes)];
    }

    public static function trouver(int $id): ?array
    {
        return Database::one(
            'SELECT e.*, u.email, u.role, u.statut AS statut_compte, u.derniere_connexion, d.nom AS departement, po.intitule AS poste
             FROM employe e JOIN utilisateur u ON u.id_utilisateur = e.id_utilisateur
             JOIN departement d ON d.id_departement = e.id_departement JOIN poste po ON po.id_poste = e.id_poste
             WHERE e.id_employe = :id', ['id' => $id]);
    }

    public static function contrats(int $id): array
    {
        return Database::all('SELECT reference, type_contrat, date_debut, date_fin, salaire_base, actif FROM contrat WHERE id_employe = :id ORDER BY date_debut DESC', ['id' => $id]);
    }

    public static function soldes(int $id): array
    {
        return Database::all('SELECT t.libelle, s.annee, s.jours_acquis, s.jours_pris FROM solde_conge s
                              JOIN type_conge t ON t.id_type_conge = s.id_type_conge WHERE s.id_employe = :id ORDER BY s.annee DESC, t.libelle', ['id' => $id]);
    }

    public static function departementExiste(int $id): bool { return (bool) Database::value('SELECT COUNT(*) FROM departement WHERE id_departement = :i', ['i' => $id]); }
    public static function posteExiste(int $id): bool { return (bool) Database::value('SELECT COUNT(*) FROM poste WHERE id_poste = :i', ['i' => $id]); }

    public static function emailPris(string $email, ?int $sauf = null): bool
    {
        return (bool) Database::value('SELECT COUNT(*) FROM utilisateur WHERE email = :e AND id_utilisateur <> :u', ['e' => $email, 'u' => $sauf ?? 0]);
    }

    /** Crée le compte, l'employé, son premier contrat et ses soldes de congés, en UNE transaction (tout ou rien). */
    public static function creer(array $d, int $auteur, ?string $ip): array
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $motDePasse = substr(strtr(base64_encode(random_bytes(9)), '+/', 'xK'), 0, 12);
            Database::run("INSERT INTO utilisateur (email, mot_de_passe_hash, role, statut) VALUES (:e, :h, 'EMPLOYE', 'ACTIF')",
                ['e' => $d['email'], 'h' => password_hash($motDePasse, PASSWORD_DEFAULT)]);
            $idUtilisateur = (int) $pdo->lastInsertId();

            $matricule = sprintf('EMP-%04d', 1 + (int) Database::value("SELECT COALESCE(MAX(CAST(SUBSTRING(matricule, 5) AS UNSIGNED)), 0) FROM employe"));
            Database::run('INSERT INTO employe (id_utilisateur, id_departement, id_poste, matricule, nom, prenom, date_naissance, telephone, adresse, date_embauche, statut)
                           VALUES (:u, :d, :p, :m, :n, :pr, :dn, :t, :a, :de, :s)',
                ['u' => $idUtilisateur, 'd' => $d['id_departement'], 'p' => $d['id_poste'], 'm' => $matricule, 'n' => $d['nom'], 'pr' => $d['prenom'],
                 'dn' => $d['date_naissance'], 't' => $d['telephone'], 'a' => $d['adresse'], 'de' => $d['date_embauche'], 's' => 'ACTIF']);
            $idEmploye = (int) $pdo->lastInsertId();

            $reference = sprintf('CT-%04d', 1 + (int) Database::value("SELECT COALESCE(MAX(CAST(SUBSTRING(reference, 4) AS UNSIGNED)), 0) FROM contrat"));
            Database::run('INSERT INTO contrat (id_employe, reference, type_contrat, date_debut, date_fin, salaire_base) VALUES (:e, :r, :t, :d, :f, :s)',
                ['e' => $idEmploye, 'r' => $reference, 't' => $d['type_contrat'], 'd' => $d['date_embauche'], 'f' => $d['date_fin'], 's' => $d['salaire_base']]);

            Database::run('INSERT INTO solde_conge (id_employe, id_type_conge, annee, jours_acquis)
                           SELECT :e, id_type_conge, :y, jours_par_an FROM type_conge WHERE jours_par_an > 0',
                ['e' => $idEmploye, 'y' => (int) date('Y')]);
            Journal::enregistrer($auteur, 'CREATION_EMPLOYE', 'employe', $idEmploye, "Matricule $matricule", $ip);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return ['id' => $idEmploye, 'matricule' => $matricule, 'mot_de_passe' => $motDePasse];
    }

    /** Met à jour la fiche. Le statut du compte suit le statut de l'employé (sauf comptes RH/direction, gérés par la direction). */
    public static function mettreAJour(array $e, array $d, int $auteur, ?string $ip): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            Database::run('UPDATE employe SET id_departement = :d, id_poste = :p, nom = :n, prenom = :pr, date_naissance = :dn, telephone = :t,
                                  adresse = :a, date_embauche = :de, statut = :s WHERE id_employe = :id',
                ['d' => $d['id_departement'], 'p' => $d['id_poste'], 'n' => $d['nom'], 'pr' => $d['prenom'], 'dn' => $d['date_naissance'],
                 't' => $d['telephone'], 'a' => $d['adresse'], 'de' => $d['date_embauche'], 's' => $d['statut'], 'id' => $e['id_employe']]);
            Database::run('UPDATE utilisateur SET email = :em WHERE id_utilisateur = :u', ['em' => $d['email'], 'u' => $e['id_utilisateur']]);
            if (!in_array($e['role'], ['RESPONSABLE_RH', 'DIRECTEUR_GENERAL'], true)) {
                $compte = in_array($d['statut'], ['SUSPENDU', 'SORTI'], true) ? 'DESACTIVE' : 'ACTIF';
                Database::run('UPDATE utilisateur SET statut = :s WHERE id_utilisateur = :u', ['s' => $compte, 'u' => $e['id_utilisateur']]);
            }
            Journal::enregistrer($auteur, 'MODIFICATION_EMPLOYE', 'employe', (int) $e['id_employe'], 'Matricule ' . $e['matricule'], $ip);
            $pdo->commit();
        } catch (\Throwable $ex) {
            $pdo->rollBack();
            throw $ex;
        }
    }
}
