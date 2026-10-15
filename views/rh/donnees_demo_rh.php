<?php

require_once __DIR__ . '/../gestion/donnees_entreprise.php';

$rh = ['nom' => 'Chantal Mvondo', 'prenom' => 'Chantal', 'poste' => 'Responsable RH'];

$stats = [
    'employes_actifs' => 48,
    'departements' => 4,
    'contrats_echeance' => 3,
    'fiches_calculees' => 48,
    'fiches_validees' => 47,
    'net_septembre' => 14400000,
];

$demandesParType = [
    'conges'        => ['libelle' => 'Congés',        'nombre' => 3, 'pluriel' => 'congés'],
    'modifications' => ['libelle' => 'Modifications', 'nombre' => 2, 'pluriel' => 'modifications'],
    'attestations'  => ['libelle' => 'Attestations',  'nombre' => 2, 'pluriel' => 'attestations'],
    'avances'       => ['libelle' => 'Avances',       'nombre' => 1, 'pluriel' => 'avance'],
];

$demandesPrioritaires = [
    ['ref' => 'CG-0261', 'objet' => 'Congé annuel',          'type' => 'conges',        'demandeur' => 'Marie Ngo Ndjock', 'recue' => '2026-10-06'],
    ['ref' => 'MD-0038', 'objet' => 'Téléphone',             'type' => 'modifications', 'demandeur' => 'Marie Ngo Ndjock', 'recue' => '2026-10-07'],
    ['ref' => 'AT-0083', 'objet' => 'Attestation de salaire', 'type' => 'attestations',  'demandeur' => 'Marie Ngo Ndjock', 'recue' => '2026-10-08'],
    ['ref' => 'AV-0093', 'objet' => '30 000 FCFA',           'type' => 'avances',       'demandeur' => 'Marie Ngo Ndjock', 'recue' => '2026-10-07'],
];

$contratsASurveiller = [
    ['nom' => 'Patrick Tchoumi',  'departement' => 'Opérations',          'contrat' => 'CDD', 'echeance' => '2026-10-31'],
    ['nom' => 'Paul Nlend',       'departement' => 'Commercial',          'contrat' => 'CDD', 'echeance' => '2026-10-31'],
    ['nom' => 'Béatrice Essono',  'departement' => 'Ressources humaines', 'contrat' => 'CDD', 'echeance' => '2026-11-06'],
];

$demandes = [
    'conges' => [
        'CG-0261' => [
            'emp' => 'EMP-0042', 'depose' => '2026-10-06 09:10', 'type' => 'Congé annuel',
            'debut' => '2026-11-02', 'fin' => '2026-11-05', 'motif' => 'Séjour familial à Yaoundé.',
            'avis' => ['Favorable', 'Jean-Paul Mbarga', '2026-10-08 15:10', 'Organisation du relais assurée avec David Etoa.'],
        ],
        'CG-0268' => [
            'emp' => 'EMP-0012', 'depose' => '2026-10-07 16:40', 'type' => 'Congé annuel',
            'debut' => '2026-11-16', 'fin' => '2026-11-17', 'motif' => 'Démarches personnelles.',
            'avis' => null,
        ],
        'CG-0270' => [
            'emp' => 'EMP-0079', 'depose' => '2026-10-08 10:05', 'type' => 'Congé annuel',
            'debut' => '2026-11-09', 'fin' => '2026-11-13', 'motif' => 'Congés en famille.',
            'avis' => ['Favorable', 'Samuel Fotso', '2026-10-09 08:30', 'Activité couverte pendant cette période.'],
        ],
    ],
    'modifications' => [
        'MD-0038' => [
            'emp' => 'EMP-0042', 'depose' => '2026-10-07 11:25', 'champ' => 'Téléphone',
            'ancienne' => '+237 699 12 34 56', 'nouvelle' => '+237 677 45 67 89',
            'motif' => 'J’ai changé de numéro personnel. Merci de mettre à jour mes coordonnées de contact.',
        ],
        'MD-0040' => [
            'emp' => 'EMP-0054', 'depose' => '2026-10-08 09:15', 'champ' => 'Adresse personnelle',
            'ancienne' => 'Bonapriso, Douala', 'nouvelle' => 'Akwa, Douala',
            'motif' => 'Changement de domicile.',
        ],
    ],
    'attestations' => [
        'AT-0083' => [
            'emp' => 'EMP-0042', 'depose' => '2026-10-08 08:45', 'type' => 'Attestation de salaire',
            'destinataire' => 'Banque de l’entreprise', 'motif' => 'Constitution de mon dossier administratif bancaire.',
            'infos' => 'CDI en cours • base ' . montant(300000) . ' • net septembre ' . montant(320000),
            'fichier' => ['Attestation_salaire_EMP-0042.pdf', '98 Ko'],
        ],
        'AT-0084' => [
            'emp' => 'EMP-0091', 'depose' => '2026-10-08 13:20', 'type' => 'Attestation de travail',
            'destinataire' => 'Bailleur', 'motif' => 'Constitution d’un dossier de location.',
            'infos' => 'CDD en cours • échéance au 31/10/2026',
            'fichier' => ['Attestation_travail_EMP-0091.pdf', '84 Ko'],
        ],
    ],
    'avances' => [
        'AV-0093' => [
            'emp' => 'EMP-0042', 'depose' => '2026-10-07 14:10', 'montant' => 30000, 'base' => 300000,
            'motif' => 'Dépenses scolaires exceptionnelles pour ma famille.',
            'derniere' => 'AV-0081 • ' . montant(20000) . ' accordés • retenus sur septembre',
            'mois' => ['2026-10' => 'Octobre 2026', '2026-11' => 'Novembre 2026'],
        ],
    ],
];

$fichesSeptembre = [
    ['emp' => 'EMP-0042', 'dept' => 'Finance',             'brut' => 350000, 'retenues' => 30000, 'net' => 320000, 'etat' => 'Validée'],
    ['emp' => 'EMP-0012', 'dept' => 'Finance',             'brut' => 465000, 'retenues' => 15000, 'net' => 450000, 'etat' => 'Validée'],
    ['emp' => 'EMP-0051', 'dept' => 'Finance',             'brut' => 270000, 'retenues' => 10000, 'net' => 260000, 'etat' => 'Validée'],
    ['emp' => 'EMP-0008', 'dept' => 'Ressources humaines', 'brut' => 465000, 'retenues' => 15000, 'net' => 450000, 'etat' => 'Validée'],
    ['emp' => 'EMP-0063', 'dept' => 'Opérations',          'brut' => 280000, 'retenues' => 10000, 'net' => 270000, 'etat' => 'Brouillon'],
];

$controleMarie = [
    ['Salaire de base',          'CDI • CT-0042',               300000, 'prime'],
    ['Primes',                   'Fonction 25 000 + transport 10 000', 35000, 'prime'],
    ['Heures supplémentaires',   'HS-0118 • 8 h validées chef', 15000, 'prime'],
    ['Cotisations',              'Retenue salariale',           10000, 'retenue'],
    ['Avance accordée',          'AV-0081 • septembre',         20000, 'retenue'],
];

$ficheRestante = [
    'emp' => 'EMP-0063', 'base' => 250000, 'hs_heures' => 0, 'hs_montant' => 0, 'avance' => 0,
    'prime' => 30000, 'retenues' => 10000,
];

$journalRh = [
    ['date' => '2026-10-09 09:20', 'auteur' => 'Chantal Mvondo',    'action' => 'Attestation mise à disposition',  'cible' => 'AT-0072 • Aline Njoya'],
    ['date' => '2026-10-08 15:10', 'auteur' => 'Jean-Paul Mbarga',  'action' => 'Avis favorable sur congé',        'cible' => 'CG-0261 • Marie Ngo Ndjock'],
    ['date' => '2026-10-06 10:00', 'auteur' => 'Marie Ngo Ndjock',  'action' => 'Demande de congé déposée',        'cible' => 'CG-0261 • 4 jours'],
    ['date' => '2026-10-02 16:30', 'auteur' => 'Chantal Mvondo',    'action' => 'Paie septembre validée',          'cible' => '47 fiches • 14 130 000 FCFA nets'],
    ['date' => '2026-09-24 17:00', 'auteur' => 'Jean-Paul Mbarga',  'action' => 'Heures supplémentaires validées', 'cible' => 'HS-0118 • Marie • 8 h'],
];
