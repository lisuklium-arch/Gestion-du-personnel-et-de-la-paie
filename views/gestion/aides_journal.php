<?php

require_once __DIR__ . '/aides_gestion.php';
require_once __DIR__ . '/../../models/JournalAction.php';
require_once __DIR__ . '/../../services/JournalService.php';

const ACTIONS_JOURNAL = [
    'connexion'             => ['Connexion', 'neutral'],
    'deconnexion'           => ['Déconnexion', 'neutral'],
    'echec_connexion'       => ['Échec de connexion', 'danger'],
    'demande_code_mdp'      => ['Code de réinitialisation demandé', 'neutral'],
    'code_mdp_invalide'     => ['Code de réinitialisation incorrect', 'danger'],
    'mdp_reinitialise'      => ['Mot de passe réinitialisé', 'success'],
    'mdp_temporaire_change' => ['Mot de passe temporaire changé', 'success'],
    'echec_mdp_actuel'      => ['Mot de passe actuel incorrect', 'danger'],
    'creation_compte'       => ['Compte créé', 'neutral'],
    'creation_employe'      => ['Dossier employé et compte créés', 'success'],
    'activation_compte'     => ['Compte activé', 'success'],
    'desactivation_compte'  => ['Compte désactivé', 'danger'],
    'reactivation_compte'   => ['Compte réactivé', 'success'],
    'modification_parametre'    => ['Paramètre système modifié', 'neutral'],
    'creation_departement'      => ['Département créé', 'success'],
    'modification_departement'   => ['Département modifié', 'neutral'],
    'creation_poste'            => ['Poste créé', 'success'],
    'modification_poste'        => ['Poste modifié', 'neutral'],
    'creation_type_conge'       => ['Type de congé créé', 'success'],
    'modification_type_conge'   => ['Type de congé modifié', 'neutral'],
    'suppression_parametre'     => ['Paramètre système supprimé', 'danger'],
    'suppression_departement'   => ['Département supprimé', 'danger'],
    'suppression_poste'         => ['Poste supprimé', 'danger'],
    'suppression_type_conge'    => ['Type de congé supprimé', 'danger'],
];

function actionJournal(string $action): array
{
    return ACTIONS_JOURNAL[$action] ?? [ucfirst(str_replace('_', ' ', $action)), 'neutral'];
}

function nomAuteurJournal(array $l): string
{
    return $l['auteur_nom'] !== null ? $l['auteur_prenom'] . ' ' . $l['auteur_nom'] : $l['auteur_email'];
}

function cibleJournal(array $l): string
{
    if ($l['cible_nom'] !== null) {
        return $l['cible_prenom'] . ' ' . $l['cible_nom'] . ' • ' . $l['cible_matricule'];
    }
    if ($l['cible_email'] !== null) {
        return $l['cible_email'];
    }
    $detail = json_decode((string) ($l['nouvelle_valeur'] ?? $l['ancienne_valeur'] ?? ''), true);
    if (is_array($detail)) {
        $nom = $detail['cle'] ?? $detail['nom'] ?? $detail['intitule'] ?? $detail['libelle'] ?? null;
        if (is_string($nom)) {
            return ucfirst(str_replace('_', ' ', $l['entite'])) . ' • ' . $nom;
        }
    }
    return $l['entite'] . ($l['entite_id'] !== null ? ' #' . $l['entite_id'] : '');
}

function dateValide(string $valeur, string $defaut): string
{
    $d = DateTime::createFromFormat('Y-m-d', $valeur);
    return ($d && $d->format('Y-m-d') === $valeur) ? $valeur : $defaut;
}

function lienPageJournal(int $page): string
{
    return '?' . h(http_build_query(array_merge($_GET, ['page' => $page])));
}

function pagination(array $lecture): void
{
    if ($lecture['pages'] <= 1) {
        return;
    }
    echo '<nav class="app-actions" aria-label="Pagination">';
    if ($lecture['page'] > 1) {
        echo '<a class="btn btn-neutral text-decoration-none" href="' . lienPageJournal($lecture['page'] - 1) . '">Précédent</a>';
    }
    echo '<span class="align-self-center">Page ' . (int) $lecture['page'] . ' sur ' . (int) $lecture['pages'] . '</span>';
    if ($lecture['page'] < $lecture['pages']) {
        echo '<a class="btn btn-neutral text-decoration-none" href="' . lienPageJournal($lecture['page'] + 1) . '">Suivant</a>';
    }
    echo '</nav>';
}

function optionsAuteurs(array $auteurs, string $choisi, string $tous): string
{
    $options = ['' => $tous];
    foreach ($auteurs as $a) {
        $options[(string) $a['id']] = $a['nom'] !== null ? $a['prenom'] . ' ' . $a['nom'] : $a['email'];
    }
    return optionsSelect($options, $choisi);
}
