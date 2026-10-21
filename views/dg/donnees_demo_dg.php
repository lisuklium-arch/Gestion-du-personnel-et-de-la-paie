<?php

require_once __DIR__ . '/../gestion/donnees_entreprise.php';

$dg = ['email' => $_SESSION['nom_affiche'] ?? ''];

$stats = [
    'employes_actifs' => 48,
    'fiches_calculees' => 48,
    'fiches_validees' => 47,
    'net_septembre' => 14400000,
    'net_brouillon' => 270000,
];

$comptesRh = [
    'beatrice' => [
        'nom' => 'Béatrice Essono', 'email' => 'beatrice.essono@entreprise.com', 'etat' => 'attente', 'derniere' => null,
        'matricule' => 'EMP-0068', 'poste' => 'Assistante RH', 'dept' => 'Ressources humaines', 'role' => 'Responsable RH',
        'cree_par' => 'Directeur général', 'cree_le' => '2026-10-08 10:15', 'contrat' => 'CDD', 'fin' => '2026-11-06',
        'detail' => 'Compte en attente d’activation',
    ],
    'chantal' => [
        'nom' => 'Chantal Mvondo', 'email' => 'chantal.mvondo@entreprise.com', 'etat' => 'actif', 'derniere' => '2026-10-09 08:10',
        'matricule' => 'EMP-0008', 'poste' => 'Responsable RH', 'dept' => 'Ressources humaines', 'role' => 'Responsable RH',
        'cree_par' => 'Directeur général', 'cree_le' => '2026-09-01 09:00', 'contrat' => 'CDI', 'fin' => null,
        'detail' => 'Accès RH opérationnel',
    ],
    'lucien' => [
        'nom' => 'Lucien Abega', 'email' => 'lucien.abega@entreprise.com', 'etat' => 'desactive', 'derniere' => '2026-09-30 16:00',
        'matricule' => 'EMP-0031', 'poste' => 'Assistant RH', 'dept' => 'Ressources humaines', 'role' => 'Responsable RH',
        'cree_par' => 'Directeur général', 'cree_le' => '2025-03-03 09:00', 'contrat' => 'CDI', 'fin' => null,
        'detail' => 'Dossier employé conservé',
    ],
];

$etatsCompte = [
    'attente'   => ['En attente', 'warning'],
    'actif'     => ['Actif', 'success'],
    'desactive' => ['Désactivé', 'danger'],
];

$congeRh = [
    'ref' => 'CG-0269', 'emp' => 'EMP-0008', 'depose' => '2026-10-08 14:30', 'type' => 'Congé annuel',
    'debut' => '2026-11-12', 'fin' => '2026-11-13', 'motif' => 'Déplacement familial à Kribi.',
    'relais' => 'Béatrice Essono assure le suivi administratif.',
];

$journalDg = [
    ['date' => '2026-10-08 14:30', 'auteur' => 'Chantal Mvondo',  'action' => 'Congé RH soumis au DG',  'ton' => 'warning', 'type' => 'conge',  'cible' => 'CG-0269 • 12–13 novembre'],
    ['date' => '2026-10-08 10:15', 'auteur' => 'dg@entreprise.com', 'action' => 'Compte RH créé en attente', 'ton' => 'neutral', 'type' => 'compte', 'cible' => 'beatrice.essono@entreprise.com'],
    ['date' => '2026-09-30 16:20', 'auteur' => 'dg@entreprise.com', 'action' => 'Compte RH désactivé',  'ton' => 'danger',  'type' => 'compte', 'cible' => 'lucien.abega@entreprise.com'],
    ['date' => '2026-09-18 09:00', 'auteur' => 'dg@entreprise.com', 'action' => 'Congé RH validé',      'ton' => 'success', 'type' => 'conge',  'cible' => 'CG-0224 • Chantal • 2 jours'],
    ['date' => '2026-09-01 09:00', 'auteur' => 'dg@entreprise.com', 'action' => 'Compte RH activé',     'ton' => 'success', 'type' => 'compte', 'cible' => 'chantal.mvondo@entreprise.com'],
];

$premierMoisPaie = [2025, 1];
$dernierMoisPaie = [2026, 9];

function moisPaieDisponibles(int $annee): array
{
    global $premierMoisPaie, $dernierMoisPaie;
    $debut = $annee === $premierMoisPaie[0] ? $premierMoisPaie[1] : 1;
    $fin = $annee === $dernierMoisPaie[0] ? $dernierMoisPaie[1] : 12;
    return $fin >= $debut ? range($debut, $fin) : [];
}

function syntheseMois(int $annee, int $mois): array
{
    global $departements, $premierMoisPaie, $dernierMoisPaie;
    $depuisDebut = ($annee - $premierMoisPaie[0]) * 12 + $mois - $premierMoisPaie[1];
    $maximum = ($dernierMoisPaie[0] - $premierMoisPaie[0]) * 12 + $dernierMoisPaie[1] - $premierMoisPaie[1];
    $reculs = $maximum - $depuisDebut;
    $pas = ['Finance' => 5, 'Opérations' => 5, 'Commercial' => 8];

    $lignes = [];
    foreach ($departements as $nom => $dept) {
        $effectif = isset($pas[$nom]) ? max(1, $dept['effectif'] - intdiv($reculs, $pas[$nom])) : $dept['effectif'];
        $parTete = $dept['net'] / $dept['effectif'] * ($annee === 2025 ? 0.97 : 1);
        $lignes[$nom] = [
            'chef' => $dept['chef'],
            'effectif' => $effectif,
            'net' => (int) (round($effectif * $parTete / 1000) * 1000),
        ];
    }
    return $lignes;
}

function syntheseEntreprise(int $annee, int $mois): array
{
    global $departements;
    $moisCalcules = $mois > 0 ? [$mois] : moisPaieDisponibles($annee);
    $reference = syntheseMois($annee, end($moisCalcules));
    $lignes = [];
    foreach ($reference as $nom => $ligne) {
        $net = 0;
        foreach ($moisCalcules as $m) {
            $net += syntheseMois($annee, $m)[$nom]['net'];
        }
        $lignes[$nom] = ['chef' => $ligne['chef'], 'effectif' => $ligne['effectif'], 'net' => $net];
    }
    return ['lignes' => $lignes, 'nb_mois' => count($moisCalcules), 'dernier_mois' => end($moisCalcules)];
}

$supervisions = [
    ['date' => '2026-10-08', 'objet' => 'Activation de compte RH', 'icone' => 'fa-solid fa-user-shield', 'lien' => 'comptes_rh_dg.php',
     'cible' => 'Béatrice Essono', 'detail' => 'beatrice.essono@entreprise.com', 'etat' => 'En attente', 'ton' => 'warning', 'attente' => true],
    ['date' => '2026-10-08', 'objet' => 'Congé annuel RH', 'icone' => 'fa-regular fa-calendar', 'lien' => 'conges_rh_dg.php',
     'cible' => 'Chantal Mvondo', 'detail' => '12–13 novembre 2026', 'etat' => 'En attente DG', 'ton' => 'warning', 'attente' => true],
    ['date' => '2026-09-30', 'objet' => 'Désactivation de compte RH', 'icone' => 'fa-solid fa-user-shield', 'lien' => 'comptes_rh_dg.php',
     'cible' => 'Lucien Abega', 'detail' => 'lucien.abega@entreprise.com', 'etat' => 'Traité', 'ton' => 'danger', 'attente' => false],
    ['date' => '2026-09-18', 'objet' => 'Congé annuel RH', 'icone' => 'fa-regular fa-calendar', 'lien' => 'conges_rh_dg.php',
     'cible' => 'Chantal Mvondo', 'detail' => 'CG-0224 • 2 jours', 'etat' => 'Validé', 'ton' => 'success', 'attente' => false],
    ['date' => '2026-09-01', 'objet' => 'Activation de compte RH', 'icone' => 'fa-solid fa-user-shield', 'lien' => 'comptes_rh_dg.php',
     'cible' => 'Chantal Mvondo', 'detail' => 'chantal.mvondo@entreprise.com', 'etat' => 'Traité', 'ton' => 'success', 'attente' => false],
    ['date' => '2026-06-22', 'objet' => 'Congé annuel RH', 'icone' => 'fa-regular fa-calendar', 'lien' => 'conges_rh_dg.php',
     'cible' => 'Lucien Abega', 'detail' => 'CG-0187 • 5 jours', 'etat' => 'Validé', 'ton' => 'success', 'attente' => false],
    ['date' => '2025-03-03', 'objet' => 'Activation de compte RH', 'icone' => 'fa-solid fa-user-shield', 'lien' => 'comptes_rh_dg.php',
     'cible' => 'Lucien Abega', 'detail' => 'lucien.abega@entreprise.com', 'etat' => 'Traité', 'ton' => 'success', 'attente' => false],
];
