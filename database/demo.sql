SET NAMES utf8mb4;
USE projet8_rh_paie;

INSERT INTO utilisateur (id, email, mot_de_passe_hash, role, statut_compte, doit_changer_mdp, valide_par_id, date_validation) VALUES
 (1, 'dg@demo.test',    '$2y$10$Q.Gyegsx1UyJF9A/z1P66.a.KSbLgR2QXesz.BD2tq.kRSyvk8hVe', 'dg',      'actif', 0, NULL, NOW()),
 (2, 'rh@demo.test',    '$2y$10$Q.Gyegsx1UyJF9A/z1P66.a.KSbLgR2QXesz.BD2tq.kRSyvk8hVe', 'rh',      'actif', 0, 1,    NOW()),
 (3, 'chef@demo.test',  '$2y$10$Q.Gyegsx1UyJF9A/z1P66.a.KSbLgR2QXesz.BD2tq.kRSyvk8hVe', 'chef',    'actif', 0, 1,    NOW()),
 (4, 'alain@demo.test', '$2y$10$Q.Gyegsx1UyJF9A/z1P66.a.KSbLgR2QXesz.BD2tq.kRSyvk8hVe', 'employe', 'actif', 0, 2,    NOW()),
 (5, 'marie@demo.test', '$2y$10$Q.Gyegsx1UyJF9A/z1P66.a.KSbLgR2QXesz.BD2tq.kRSyvk8hVe', 'employe', 'actif', 0, 2,    NOW()),
 (6, 'david@demo.test', '$2y$10$Q.Gyegsx1UyJF9A/z1P66.a.KSbLgR2QXesz.BD2tq.kRSyvk8hVe', 'employe', 'actif', 0, 2,    NOW());

INSERT INTO departement (id, nom, description) VALUES
 (1, 'Ressources humaines', 'Gestion du personnel et de la paie'),
 (2, 'Finance',             'Comptabilité et trésorerie');

INSERT INTO poste (id, intitule, description) VALUES
 (1, 'Responsable RH',      NULL),
 (2, 'Chef de département', NULL),
 (3, 'Comptable',           NULL);

INSERT INTO employe (id, utilisateur_id, matricule, nom, prenom, date_naissance, telephone, date_embauche, statut, departement_id, poste_id) VALUES
 (1, 2, 'EMP-0001', 'Fotso',      'Carine',    '1988-03-14', '+237 677 00 00 01', '2018-02-01', 'actif', 1, 1),
 (2, 3, 'EMP-0002', 'Mbarga',     'Jean-Paul', '1984-07-22', '+237 677 00 00 02', '2016-09-01', 'actif', 2, 2),
 (3, 4, 'EMP-0003', 'Mballa',     'Alain',     '1992-11-05', '+237 677 00 00 03', '2021-01-15', 'actif', 2, 3),
 (4, 5, 'EMP-0004', 'Ngo Ndjock', 'Marie',     '1994-05-30', '+237 677 00 00 04', '2022-03-01', 'actif', 2, 3),
 (5, 6, 'EMP-0005', 'Etoa',       'David',     '1990-09-18', '+237 677 00 00 05', '2020-06-01', 'actif', 2, 3);

UPDATE departement SET chef_id = 2 WHERE id = 2;

INSERT INTO contrat (employe_id, type, date_debut, date_fin, salaire_base, heures_mensuelles, statut) VALUES
 (1, 'CDI', '2018-02-01', NULL, 450000, 173.33, 'en_cours'),
 (2, 'CDI', '2016-09-01', NULL, 550000, 173.33, 'en_cours'),
 (3, 'CDI', '2021-01-15', NULL, 320000, 173.33, 'en_cours'),
 (4, 'CDI', '2022-03-01', NULL, 300000, 173.33, 'en_cours'),
 (5, 'CDD', '2025-06-01', '2026-12-31', 280000, 173.33, 'en_cours');

INSERT INTO type_conge (id, code, libelle, decompte_solde, droit_annuel_jours, justificatif_requis, actif) VALUES
 (1, 'ANNUEL',   'Congé annuel',        1, 18.0, 0, 1),
 (2, 'MALADIE',  'Congé maladie',       0,  0.0, 1, 1),
 (3, 'MATERNITE','Congé de maternité',  0,  0.0, 1, 1),
 (4, 'SANS_SOLDE','Congé sans solde',   0,  0.0, 0, 1);

INSERT INTO solde_conge (employe_id, type_conge_id, annee, jours_acquis, jours_pris)
SELECT e.id, 1, 2026, 18.0, 0 FROM employe e;

INSERT INTO jour_ferie (date_ferie, libelle, recurrent) VALUES
 ('2026-01-01', 'Jour de l''an',         1),
 ('2026-02-11', 'Fête de la jeunesse',   1),
 ('2026-05-01', 'Fête du travail',       1),
 ('2026-05-20', 'Fête nationale',        1),
 ('2026-08-15', 'Assomption',            1),
 ('2026-12-25', 'Noël',                  1),
 ('2026-04-03', 'Vendredi saint',        0),
 ('2026-05-14', 'Ascension',             0);

INSERT INTO rubrique_paie (id, code, libelle, nature, mode_calcul, valeur_defaut, actif) VALUES
 (1, 'PRIME_TRANSPORT', 'Prime de transport',            'prime',   'fixe',        25000, 1),
 (2, 'PRIME_RESP',      'Prime de responsabilité',       'prime',   'fixe',        50000, 1),
 (3, 'CNPS',            'Cotisation CNPS (part salariale)','retenue','pourcentage',   2.8, 1),
 (4, 'RET_ABSENCE',     'Retenue pour absence non justifiée','retenue','fixe',          0, 1),
 (5, 'RET_AVANCE',      'Remboursement d''avance',       'retenue', 'fixe',            0, 1);

INSERT INTO poste_rubrique (poste_id, rubrique_id, montant) VALUES
 (2, 2, 50000),
 (3, 1, 25000);

INSERT INTO parametre_systeme (cle, valeur, description) VALUES
 ('majoration_heures_sup_pct', '25',     'Majoration des heures supplémentaires (%)'),
 ('heures_mensuelles_defaut',  '173.33', 'Heures mensuelles par défaut'),
 ('cotisation_cnps_pct',       '2.8',    'Taux de cotisation salariale (%)'),
 ('seuil_anomalie_net_pct',    '30',     'Écart de net (%) déclenchant une anomalie'),
 ('droit_annuel_defaut_jours', '18',     'Droit annuel de congé par défaut (jours)');