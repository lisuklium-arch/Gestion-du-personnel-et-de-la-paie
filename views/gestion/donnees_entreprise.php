<?php

require_once __DIR__ . '/aides_gestion.php';

$aujourdhui = new DateTime('2026-10-09');
$situation = 'Situation au ' . dateFr($aujourdhui->format('Y-m-d'));

$dossiers = ['total' => 50, 'actif' => 48, 'suspendu' => 1, 'sorti' => 1];

$departements = [
    'Finance'              => ['chef' => 'Jean-Paul Mbarga', 'effectif' => 12, 'net' => 3840000, 'etat' => 'Actif'],
    'Ressources humaines'  => ['chef' => 'Bruno Manga',      'effectif' => 4,  'net' => 1440000, 'etat' => 'Actif'],
    'Opérations'           => ['chef' => 'Samuel Fotso',     'effectif' => 20, 'net' => 5400000, 'etat' => 'Actif'],
    'Commercial'           => ['chef' => 'Éric Ngue',        'effectif' => 12, 'net' => 3720000, 'etat' => 'Actif'],
];

$postes = [
    'Comptable'           => 25000,
    'Assistante comptable' => 20000,
    'Technicien'          => 30000,
    'Responsable RH'      => 50000,
    'Chef de département' => 40000,
    'Chargée de clientèle' => 20000,
    'Commercial'          => 20000,
    'Assistante RH'       => 20000,
];

$typesContrat = [
    'CDI'        => 'Sans date de fin',
    'CDD'        => 'Date de fin obligatoire',
    'Stage'      => 'Période définie au contrat',
    'Prestation' => 'Durée définie par la mission',
];

$typesConge = [
    'ANNUEL'     => ['libelle' => 'Congé annuel',       'droit' => 24, 'regle' => '24 jours par an',           'actif' => true],
    'MALADIE'    => ['libelle' => 'Maladie',            'droit' => 0,  'regle' => 'Certificat médical requis', 'actif' => true],
    'FAMILIAL'   => ['libelle' => 'Événement familial', 'droit' => 0,  'regle' => 'Durée selon événement',     'actif' => true],
    'MATERNITE'  => ['libelle' => 'Maternité',          'droit' => 0,  'regle' => 'Certificat médical requis', 'actif' => true],
    'SANS_SOLDE' => ['libelle' => 'Congé sans solde',   'droit' => 0,  'regle' => 'Sans décompte du solde',    'actif' => true],
];

$roles = ['Employé', 'Chef de département', 'Responsable RH'];

$employes = [
    'EMP-0042' => [
        'prenom' => 'Marie', 'nom' => 'Ngo Ndjock', 'f' => true,
        'naissance' => '1992-05-14', 'tel' => '+237 699 12 34 56', 'adresse' => 'Bonamoussadi, Douala', 'embauche' => '2023-03-01',
        'dept' => 'Finance', 'poste' => 'Comptable', 'chef' => 'Jean-Paul Mbarga',
        'email' => 'marie.ngo@entreprise.com', 'role' => 'Employé', 'statut' => 'actif',
        'contrat' => ['ref' => 'CT-0042', 'type' => 'CDI', 'debut' => '2023-03-01', 'fin' => null, 'base' => 300000, 'heures' => '173,33', 'statut' => 'En cours'],
        'paie' => ['mois' => 'Septembre 2026', 'etat' => 'Validée', 'net' => 320000],
        'conges' => ['acquis' => 24, 'pris' => 8, 'attente' => 4],
        'pieces' => [
            ['Contrat signé',          'CT-0042',     'En cours • 01/03/2023',  '',        true,  null],
            ['Pièce d’identité',       'DOC-0042-01', 'Conservée au dossier',   '',        true,  null],
            ['Modification téléphone', 'MD-0038',     'En attente RH',          'warning', false, ['modifications', 'MD-0038']],
            ['Attestation de salaire', 'AT-0083',     'En attente RH',          'warning', false, ['attestations', 'AT-0083']],
        ],
    ],
    'EMP-0012' => [
        'prenom' => 'Jean-Paul', 'nom' => 'Mbarga', 'f' => false,
        'naissance' => '1984-07-22', 'tel' => '+237 677 20 30 40', 'adresse' => 'Bonanjo, Douala', 'embauche' => '2016-09-01',
        'dept' => 'Finance', 'poste' => 'Chef de département', 'chef' => '',
        'email' => 'jean-paul.mbarga@entreprise.com', 'role' => 'Chef de département', 'statut' => 'actif',
        'contrat' => ['ref' => 'CT-0012', 'type' => 'CDI', 'debut' => '2016-09-01', 'fin' => null, 'base' => 400000, 'heures' => '173,33', 'statut' => 'En cours'],
        'paie' => ['mois' => 'Septembre 2026', 'etat' => 'Validée', 'net' => 450000],
        'conges' => ['acquis' => 24, 'pris' => 10, 'attente' => 2],
        'pieces' => [
            ['Contrat signé',    'CT-0012',     'En cours • 01/09/2016', '',        true,  null],
            ['Pièce d’identité', 'DOC-0012-01', 'Conservée au dossier',  '',        true,  null],
            ['Demande de congé', 'CG-0268',     'En attente RH',         'warning', false, ['conges', 'CG-0268']],
        ],
    ],
    'EMP-0051' => [
        'prenom' => 'Aline', 'nom' => 'Njoya', 'f' => true,
        'naissance' => '1996-02-03', 'tel' => '+237 677 11 22 33', 'adresse' => 'Akwa, Douala', 'embauche' => '2024-01-08',
        'dept' => 'Finance', 'poste' => 'Assistante comptable', 'chef' => 'Jean-Paul Mbarga',
        'email' => 'aline.njoya@entreprise.com', 'role' => 'Employé', 'statut' => 'actif',
        'contrat' => ['ref' => 'CT-0051', 'type' => 'CDI', 'debut' => '2024-01-08', 'fin' => null, 'base' => 250000, 'heures' => '173,33', 'statut' => 'En cours'],
        'paie' => ['mois' => 'Septembre 2026', 'etat' => 'Validée', 'net' => 260000],
        'conges' => ['acquis' => 24, 'pris' => 3, 'attente' => 0],
        'pieces' => [
            ['Contrat signé',          'CT-0051',     'En cours • 08/01/2024', '', true, null],
            ['Pièce d’identité',       'DOC-0051-01', 'Conservée au dossier',  '', true, null],
            ['Attestation de salaire', 'AT-0072',     'Mise à disposition',    '', true, null],
        ],
    ],
    'EMP-0063' => [
        'prenom' => 'Samuel', 'nom' => 'Fotso', 'f' => false,
        'naissance' => '1988-11-09', 'tel' => '+237 677 44 55 66', 'adresse' => 'Bepanda, Douala', 'embauche' => '2020-06-01',
        'dept' => 'Opérations', 'poste' => 'Technicien', 'chef' => '',
        'email' => 'samuel.fotso@entreprise.com', 'role' => 'Chef de département', 'statut' => 'actif',
        'contrat' => ['ref' => 'CT-0063', 'type' => 'CDI', 'debut' => '2020-06-01', 'fin' => null, 'base' => 250000, 'heures' => '173,33', 'statut' => 'En cours'],
        'paie' => ['mois' => 'Septembre 2026', 'etat' => 'Brouillon', 'net' => 270000],
        'conges' => ['acquis' => 24, 'pris' => 12, 'attente' => 0],
        'pieces' => [
            ['Contrat signé',    'CT-0063',     'En cours • 01/06/2020', '', true, null],
            ['Pièce d’identité', 'DOC-0063-01', 'Conservée au dossier',  '', true, null],
        ],
    ],
    'EMP-0008' => [
        'prenom' => 'Chantal', 'nom' => 'Mvondo', 'f' => true,
        'naissance' => '1988-03-14', 'tel' => '+237 677 00 00 08', 'adresse' => 'Bonapriso, Douala', 'embauche' => '2018-02-01',
        'dept' => 'Ressources humaines', 'poste' => 'Responsable RH', 'chef' => 'Bruno Manga',
        'email' => 'chantal.mvondo@entreprise.com', 'role' => 'Responsable RH', 'statut' => 'actif',
        'contrat' => ['ref' => 'CT-0008', 'type' => 'CDI', 'debut' => '2018-02-01', 'fin' => null, 'base' => 400000, 'heures' => '173,33', 'statut' => 'En cours'],
        'paie' => ['mois' => 'Septembre 2026', 'etat' => 'Validée', 'net' => 450000],
        'conges' => ['acquis' => 24, 'pris' => 10, 'attente' => 2],
        'pieces' => [
            ['Contrat signé',    'CT-0008',     'En cours • 01/02/2018', '',        true,  null],
            ['Pièce d’identité', 'DOC-0008-01', 'Conservée au dossier',  '',        true,  null],
            ['Demande de congé', 'CG-0269',     'En attente DG',         'warning', false, null],
        ],
    ],
    'EMP-0072' => [
        'prenom' => 'Rose', 'nom' => 'Atangana', 'f' => true,
        'naissance' => '1993-09-27', 'tel' => '+237 677 77 88 99', 'adresse' => 'Deido, Douala', 'embauche' => '2022-04-04',
        'dept' => 'Commercial', 'poste' => 'Chargée de clientèle', 'chef' => 'Éric Ngue',
        'email' => 'rose.atangana@entreprise.com', 'role' => 'Employé', 'statut' => 'suspendu',
        'contrat' => ['ref' => 'CT-0072', 'type' => 'CDI', 'debut' => '2022-04-04', 'fin' => null, 'base' => 220000, 'heures' => '173,33', 'statut' => 'En cours'],
        'paie' => ['mois' => 'Août 2026', 'etat' => 'Validée', 'net' => 210000],
        'conges' => ['acquis' => 24, 'pris' => 6, 'attente' => 0],
        'pieces' => [
            ['Contrat signé',    'CT-0072',     'En cours • 04/04/2022', '', true, null],
            ['Pièce d’identité', 'DOC-0072-01', 'Conservée au dossier',  '', true, null],
        ],
    ],
    'EMP-0027' => [
        'prenom' => 'François', 'nom' => 'Ebong', 'f' => false,
        'naissance' => '1985-12-01', 'tel' => '+237 677 31 41 51', 'adresse' => 'Logpom, Douala', 'embauche' => '2019-10-01',
        'dept' => 'Commercial', 'poste' => 'Commercial', 'chef' => 'Éric Ngue',
        'email' => 'francois.ebong@entreprise.com', 'role' => 'Employé', 'statut' => 'sorti',
        'contrat' => ['ref' => 'CT-0027', 'type' => 'CDI', 'debut' => '2019-10-01', 'fin' => '2026-06-30', 'base' => 230000, 'heures' => '173,33', 'statut' => 'Terminé'],
        'paie' => ['mois' => 'Juin 2026', 'etat' => 'Validée', 'net' => 225000],
        'conges' => ['acquis' => 24, 'pris' => 24, 'attente' => 0],
        'pieces' => [
            ['Contrat signé',    'CT-0027',     'Terminé • 30/06/2026', '', true, null],
            ['Pièce d’identité', 'DOC-0027-01', 'Conservée au dossier', '', true, null],
        ],
    ],
];

$annuaire = [];
foreach ($employes as $id => $e) {
    $annuaire[$id] = [
        'nom' => nomComplet($e), 'poste' => $e['poste'], 'dept' => $e['dept'], 'role' => $e['role'],
        'acquis' => $e['conges']['acquis'], 'pris' => $e['conges']['pris'],
    ];
}
$annuaire += [
    'EMP-0079' => ['nom' => 'Judith Onana',    'poste' => 'Technicienne',        'dept' => 'Opérations',          'role' => 'Employé', 'acquis' => 24, 'pris' => 6],
    'EMP-0054' => ['nom' => 'Nadine Biloa',    'poste' => 'Chargée de clientèle', 'dept' => 'Commercial',          'role' => 'Employé', 'acquis' => 24, 'pris' => 9],
    'EMP-0091' => ['nom' => 'Patrick Tchoumi', 'poste' => 'Technicien',          'dept' => 'Opérations',          'role' => 'Employé', 'acquis' => 24, 'pris' => 14],
    'EMP-0068' => ['nom' => 'Béatrice Essono', 'poste' => 'Assistante RH',       'dept' => 'Ressources humaines', 'role' => 'Employé', 'acquis' => 24, 'pris' => 2],
];
