<?php

require_once __DIR__ . '/aides_cd.php';

$aujourdhui = new DateTime('2026-10-09');

$chef = ['nom' => 'Jean-Paul Mbarga', 'matricule' => 'EMP-0012', 'departement' => 'Finance'];

$effectif = ['actifs' => 12, 'presents' => 11, 'en_conge' => 1, 'en_conge_detail' => 'Aline Njoya • 08–09 octobre'];

$equipe = [
    'EMP-0042' => ['nom' => 'Marie Ngo Ndjock', 'poste' => 'Comptable',            'dossier' => 'Actif', 'jour' => 'present', 'f' => true],
    'EMP-0012' => ['nom' => 'Jean-Paul Mbarga', 'poste' => 'Chef de département',  'dossier' => 'Actif', 'jour' => 'present', 'chef' => true],
    'EMP-0051' => ['nom' => 'Aline Njoya',      'poste' => 'Assistante comptable', 'dossier' => 'Actif', 'jour' => 'conge',   'f' => true],
    'EMP-0031' => ['nom' => 'David Etoa',       'poste' => 'Comptable',            'dossier' => 'Actif', 'jour' => 'present'],
    'EMP-0038' => ['nom' => 'Nadine Biloa',     'poste' => 'Trésorière',           'dossier' => 'Actif', 'jour' => 'present', 'f' => true],
    'EMP-0049' => ['nom' => 'Alain Mballa',     'poste' => 'Assistant financier',  'dossier' => 'Actif', 'jour' => 'present'],
];

$etatsConge = [
    'attente_chef' => ['En attente chef', 'warning'],
    'attente_rh'   => ['En attente RH',   'warning'],
    'valide'       => ['Validé',          'success'],
    'refuse'       => ['Refusé',          'danger'],
];

$naturesConge = [
    'valide'  => ['validé',     'success'],
    'attente' => ['en attente', 'warning'],
];

$conges = [
    'CG-0251' => ['emp' => 'EMP-0051', 'type' => 'Congé annuel', 'debut' => '2026-10-08', 'fin' => '2026-10-09', 'depose' => '2026-09-24',
                  'motif' => 'Repos annuel.', 'solde_apres' => 14, 'etat' => 'valide', 'avis' => 'favorable', 'avis_le' => '2026-09-25'],
    'CG-0274' => ['emp' => 'EMP-0031', 'type' => 'Congé annuel', 'debut' => '2026-10-26', 'fin' => '2026-10-28', 'depose' => '2026-10-09',
                  'motif' => 'Déplacement familial à Bafoussam.', 'solde_apres' => 10, 'etat' => 'attente_chef', 'avis' => null, 'avis_le' => null],
    'CG-0261' => ['emp' => 'EMP-0042', 'type' => 'Congé annuel', 'debut' => '2026-11-02', 'fin' => '2026-11-05', 'depose' => '2026-10-05',
                  'motif' => 'Congé annuel planifié.', 'solde_apres' => 12, 'etat' => 'attente_rh', 'avis' => 'favorable', 'avis_le' => '2026-10-08'],
];

$etatsHs = [
    'integrees'    => ['Intégrées',       'success'],
    'attente_chef' => ['En attente chef', 'warning'],
];

$heuresSup = [
    'HS-0118' => ['emp' => 'EMP-0042', 'date' => '2026-09-24', 'h' => 8, 'motif' => 'Clôture comptable', 'etat' => 'integrees'],
    'HS-0134' => ['emp' => 'EMP-0031', 'date' => '2026-10-07', 'h' => 3, 'motif' => 'Rapprochement bancaire', 'etat' => 'attente_chef'],
    'HS-0135' => ['emp' => 'EMP-0038', 'date' => '2026-10-08', 'h' => 2, 'motif' => 'Contrôle de trésorerie', 'etat' => 'attente_chef'],
];
