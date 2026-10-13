
USE personnel_paie;
SET NAMES utf8mb4;

INSERT INTO utilisateur (id_utilisateur, email, mot_de_passe_hash, role, statut, date_creation) VALUES
  (1, 'd.mbarga@organisation.cm', '$2y$10$4rLlYaMdTkd.p.huTs3SDOk2ME9idzMWRWN.NOUgeoZe4yJt7iJVC', 'DIRECTEUR_GENERAL', 'ACTIF', '2019-01-07 08:00:00'),
  (2, 'n.ebogo@organisation.cm', '$2y$10$4rLlYaMdTkd.p.huTs3SDOk2ME9idzMWRWN.NOUgeoZe4yJt7iJVC', 'RESPONSABLE_RH', 'ACTIF', '2021-03-01 08:00:00'),
  (3, 'm.tabi@organisation.cm', '$2y$10$4rLlYaMdTkd.p.huTs3SDOk2ME9idzMWRWN.NOUgeoZe4yJt7iJVC', 'RESPONSABLE_RH', 'ACTIF', '2022-06-15 08:00:00'),
  (4, 'd.kouam@organisation.cm', '$2y$10$4rLlYaMdTkd.p.huTs3SDOk2ME9idzMWRWN.NOUgeoZe4yJt7iJVC', 'CHEF_DEPARTEMENT', 'ACTIF', '2020-02-10 08:00:00'),
  (5, 's.kamga@organisation.cm', '$2y$10$4rLlYaMdTkd.p.huTs3SDOk2ME9idzMWRWN.NOUgeoZe4yJt7iJVC', 'EMPLOYE', 'ACTIF', '2023-09-01 08:00:00'),
  (6, 'p.mballa@organisation.cm', '$2y$10$4rLlYaMdTkd.p.huTs3SDOk2ME9idzMWRWN.NOUgeoZe4yJt7iJVC', 'EMPLOYE', 'ACTIF', '2023-03-01 08:00:00'),
  (7, 'a.ngono@organisation.cm', '$2y$10$4rLlYaMdTkd.p.huTs3SDOk2ME9idzMWRWN.NOUgeoZe4yJt7iJVC', 'EMPLOYE', 'ACTIF', '2025-02-03 08:00:00'),
  (8, 'e.fotso@organisation.cm', '$2y$10$4rLlYaMdTkd.p.huTs3SDOk2ME9idzMWRWN.NOUgeoZe4yJt7iJVC', 'EMPLOYE', 'ACTIF', '2025-05-12 08:00:00'),
  (9, 'n.biyong@organisation.cm', '$2y$10$4rLlYaMdTkd.p.huTs3SDOk2ME9idzMWRWN.NOUgeoZe4yJt7iJVC', 'EMPLOYE', 'ACTIF', '2026-09-01 08:00:00'),
  (10, 'l.tchoua@organisation.cm', '$2y$10$4rLlYaMdTkd.p.huTs3SDOk2ME9idzMWRWN.NOUgeoZe4yJt7iJVC', 'CHEF_DEPARTEMENT', 'ACTIF', '2020-08-17 08:00:00'),
  (11, 'j.nkeng@organisation.cm', '$2y$10$4rLlYaMdTkd.p.huTs3SDOk2ME9idzMWRWN.NOUgeoZe4yJt7iJVC', 'CHEF_DEPARTEMENT', 'ACTIF', '2021-01-11 08:00:00'),
  (12, 'c.ndzie@organisation.cm', '$2y$10$4rLlYaMdTkd.p.huTs3SDOk2ME9idzMWRWN.NOUgeoZe4yJt7iJVC', 'EMPLOYE', 'ACTIF', '2024-04-02 08:00:00'),
  (13, 'h.mouelle@organisation.cm', '$2y$10$4rLlYaMdTkd.p.huTs3SDOk2ME9idzMWRWN.NOUgeoZe4yJt7iJVC', 'CHEF_DEPARTEMENT', 'ACTIF', '2021-10-04 08:00:00'),
  (14, 'm.essomba@organisation.cm', '$2y$10$4rLlYaMdTkd.p.huTs3SDOk2ME9idzMWRWN.NOUgeoZe4yJt7iJVC', 'EMPLOYE', 'DESACTIVE', '2022-11-21 08:00:00');

INSERT INTO poste (id_poste, intitule, description) VALUES
  (1, 'Directeur général', 'Direction de l’organisation'),
  (2, 'Responsable RH', 'Gestion du personnel et de la paie'),
  (3, 'Développeur', 'Développement web et mobile'),
  (4, 'Analyste', 'Analyse et conception'),
  (5, 'Comptable', 'Comptabilité et finances'),
  (6, 'Chargé de clientèle', 'Relation client et ventes'),
  (7, 'Magasinier', 'Gestion des stocks');

INSERT INTO departement (id_departement, code, nom, description) VALUES
  (1, 'DIR', 'Direction', 'Direction générale et RH'),
  (2, 'TECH', 'Technique', 'Équipe technique'),
  (3, 'FIN', 'Finance', 'Comptabilité et finances'),
  (4, 'COM', 'Commercial', 'Ventes et relation client'),
  (5, 'LOG', 'Logistique', 'Stocks et approvisionnement');

INSERT INTO employe (id_employe, id_utilisateur, id_departement, id_poste, matricule, nom, prenom, date_naissance, telephone, date_embauche, statut) VALUES
  (1, 1, 1, 1, 'EMP-0001', 'Mbarga', 'Étienne', '1975-05-14', '+237 6 70 00 00 01', '2019-01-07', 'ACTIF'),
  (2, 2, 1, 2, 'EMP-0002', 'Ebogo', 'Nadine', '1988-09-02', '+237 6 70 00 00 02', '2021-03-01', 'ACTIF'),
  (3, 3, 1, 2, 'EMP-0003', 'Tabi', 'Marc', '1990-01-21', '+237 6 70 00 00 03', '2022-06-15', 'ACTIF'),
  (4, 4, 2, 4, 'EMP-0004', 'Kouam', 'Daniel', '1985-11-30', '+237 6 70 00 00 04', '2020-02-10', 'ACTIF'),
  (5, 5, 2, 4, 'EMP-0015', 'Kamga', 'Sandra', '1994-03-18', '+237 6 70 00 00 05', '2023-09-01', 'ACTIF'),
  (6, 6, 2, 3, 'EMP-0012', 'Mballa', 'Paul', '1995-04-12', '+237 6 70 00 00 06', '2023-03-01', 'ACTIF'),
  (7, 7, 2, 4, 'EMP-0042', 'Ngono', 'Aïcha', '1997-07-25', '+237 6 70 00 00 07', '2025-02-03', 'ACTIF'),
  (8, 8, 2, 3, 'EMP-0044', 'Fotso', 'Éric', '1996-12-05', '+237 6 70 00 00 08', '2025-05-12', 'ACTIF'),
  (9, 9, 2, 3, 'EMP-0046', 'Biyong', 'Nadège', '2002-08-09', '+237 6 70 00 00 09', '2026-09-01', 'ACTIF'),
  (10, 10, 3, 5, 'EMP-0021', 'Tchoua', 'Léa', '1987-06-16', '+237 6 70 00 00 10', '2020-08-17', 'ACTIF'),
  (11, 11, 4, 6, 'EMP-0027', 'Nkeng', 'Joël', '1989-10-03', '+237 6 70 00 00 11', '2021-01-11', 'ACTIF'),
  (12, 12, 4, 6, 'EMP-0033', 'Ndzié', 'Carine', '1999-02-27', '+237 6 70 00 00 12', '2024-04-02', 'ACTIF'),
  (13, 13, 5, 7, 'EMP-0030', 'Mouelle', 'Hervé', '1986-05-08', '+237 6 70 00 00 13', '2021-10-04', 'ACTIF'),
  (14, 14, 5, 7, 'EMP-0034', 'Essomba', 'Mireille', '1993-09-14', '+237 6 70 00 00 14', '2022-11-21', 'SUSPENDU');

UPDATE departement SET id_chef = CASE id_departement WHEN 1 THEN 1 WHEN 2 THEN 4 WHEN 3 THEN 10 WHEN 4 THEN 11 WHEN 5 THEN 13 END;

INSERT INTO contrat (id_contrat, id_employe, reference, type_contrat, date_debut, date_fin, salaire_base, duree_mensuelle_heures, actif) VALUES
  (1, 1, 'CT-0001', 'CDI', '2019-01-07', NULL, 900000, 173.33, 1),
  (2, 2, 'CT-0002', 'CDI', '2021-03-01', NULL, 450000, 173.33, 1),
  (3, 3, 'CT-0003', 'CDI', '2022-06-15', NULL, 420000, 173.33, 1),
  (4, 4, 'CT-0004', 'CDI', '2020-02-10', NULL, 500000, 173.33, 1),
  (5, 5, 'CT-0005', 'CDI', '2023-09-01', NULL, 350000, 173.33, 1),
  (6, 6, 'CT-0006', 'CDI', '2023-03-01', NULL, 250000, 173.33, 1),
  (7, 7, 'CT-0007', 'CDI', '2025-02-03', NULL, 300000, 173.33, 1),
  (8, 8, 'CT-0008', 'CDI', '2025-05-12', NULL, 260000, 173.33, 1),
  (9, 9, 'CT-0009', 'STAGE', '2026-09-01', '2026-11-30', 240000, 173.33, 1),
  (10, 10, 'CT-0010', 'CDI', '2020-08-17', NULL, 380000, 173.33, 1),
  (11, 11, 'CT-0011', 'CDI', '2021-01-11', NULL, 330000, 173.33, 1),
  (12, 12, 'CT-0012', 'CDI', '2024-04-02', NULL, 280000, 173.33, 1),
  (13, 13, 'CT-0013', 'CDI', '2021-10-04', NULL, 300000, 173.33, 1),
  (14, 14, 'CT-0014', 'CDD', '2026-01-01', '2026-12-31', 200000, 173.33, 1);

INSERT INTO type_conge (id_type_conge, libelle, jours_par_an, remunere, justificatif_requis) VALUES
  (1, 'Congé annuel', 30, 1, 0),
  (2, 'Congé maladie', 15, 1, 1),
  (3, 'Congé sans solde', 0, 0, 0);

INSERT INTO type_rubrique (id_type_rubrique, code, libelle, nature, mode_calcul, valeur_par_defaut) VALUES
  (1, 'PRIME_TRANSPORT', 'Prime de transport', 'PRIME', 'MONTANT_FIXE', 20000),
  (2, 'PRIME_LOGEMENT', 'Prime de logement', 'PRIME', 'MONTANT_FIXE', 30000),
  (3, 'PRIME_RISQUE', 'Prime de risque', 'PRIME', 'MONTANT_FIXE', 15000),
  (4, 'PRIME_EXCEPTIONNELLE', 'Prime exceptionnelle', 'PRIME', 'MONTANT_FIXE', 0),
  (5, 'HEURES_SUP', 'Heures supplémentaires', 'HEURES_SUP', 'MONTANT_FIXE', 0),
  (6, 'COTISATION_SOCIALE', 'Cotisation sociale', 'RETENUE', 'POURCENTAGE_BRUT', 4.20),
  (7, 'AVANCE_SALAIRE', 'Avance sur salaire', 'RETENUE', 'MONTANT_FIXE', 0),
  (8, 'PRIME_RESPONSABILITE', 'Prime de responsabilité', 'PRIME', 'MONTANT_FIXE', 25000);

INSERT INTO rubrique_poste (id_poste, id_type_rubrique, montant) VALUES
  (3, 1, 20000),
  (3, 2, 30000),
  (4, 1, 20000),
  (5, 1, 20000),
  (7, 3, 15000);

INSERT INTO rubrique_employe (id_employe, id_type_rubrique, montant, date_debut, justification) VALUES
  (4, 8, 25000, '2020-02-10', 'Chef du département Technique'),
  (10, 8, 25000, '2020-08-17', 'Cheffe du département Finance'),
  (11, 8, 25000, '2021-01-11', 'Chef du département Commercial'),
  (13, 8, 25000, '2021-10-04', 'Chef du département Logistique');

INSERT INTO parametre_paie (cle, valeur, description, date_effet) VALUES
  ('DEVISE', 'FCFA', 'Devise des montants', '2026-01-01'),
  ('DUREE_MENSUELLE_HEURES', '173.33', 'Durée mensuelle de travail par défaut (heures)', '2026-01-01'),
  ('MAJ_HS_JOUR', '125', 'Majoration des heures supplémentaires de jour (%)', '2026-01-01'),
  ('MAJ_HS_NUIT', '150', 'Majoration des heures supplémentaires de nuit (%)', '2026-01-01'),
  ('MAJ_HS_DIMANCHE', '200', 'Majoration des heures supplémentaires du dimanche (%)', '2026-01-01'),
  ('TAUX_COTISATION_SOCIALE', '4.20', 'Cotisation sociale salariale (% du brut) — pédagogique', '2026-01-01');

INSERT INTO demande_conge (id_demande_conge, id_employe, id_type_conge, date_debut, date_fin, nb_jours_ouvres, motif, justificatif, statut, date_depot) VALUES
  (1, 6, 1, '2026-08-03', '2026-08-07', 5, 'Vacances', NULL, 'APPROUVE', '2026-07-20 09:00:00'),
  (2, 6, 1, '2026-10-19', '2026-10-23', 5, 'Repos', NULL, 'EN_ATTENTE_RH', '2026-10-08 10:30:00'),
  (3, 5, 2, '2026-10-14', '2026-10-15', 2, 'Consultation médicale', 'certificat_medical.pdf', 'EN_ATTENTE_AVIS', '2026-10-12 08:15:00'),
  (4, 12, 1, '2026-11-02', '2026-11-13', 10, 'Voyage familial', NULL, 'EN_ATTENTE_RH', '2026-10-09 14:00:00');

INSERT INTO solde_conge (id_employe, id_type_conge, annee, jours_acquis, jours_pris) VALUES
  (1, 1, 2026, 30, 0),
  (2, 1, 2026, 30, 0),
  (3, 1, 2026, 30, 0),
  (4, 1, 2026, 30, 0),
  (5, 1, 2026, 30, 0),
  (6, 1, 2026, 30, 5),
  (7, 1, 2026, 30, 0),
  (8, 1, 2026, 30, 0),
  (9, 1, 2026, 30, 0),
  (10, 1, 2026, 30, 0),
  (11, 1, 2026, 30, 0),
  (12, 1, 2026, 30, 0),
  (13, 1, 2026, 30, 0),
  (14, 1, 2026, 30, 0),
  (5, 2, 2026, 15, 0);

INSERT INTO presence (id_employe, date_presence, statut, motif) VALUES
  (6, '2026-10-13', 'PRESENT', NULL),
  (5, '2026-10-13', 'JUSTIFIE', 'Rendez-vous médical'),
  (7, '2026-10-13', 'PRESENT', NULL),
  (8, '2026-10-13', 'RETARD', 'Retard de 20 minutes'),
  (9, '2026-10-13', 'PRESENT', NULL);

INSERT INTO document_employe (id_document, id_employe, id_contrat, type, nom_fichier, format, chemin_stockage) VALUES
  (1, 6, 6, 'CONTRAT', 'Contrat_Paul_Mballa.docx', 'docx', 'storage/documents/6/Contrat_Paul_Mballa.docx'),
  (2, 6, NULL, 'ATTESTATION', 'Attestation_travail_Paul_Mballa.docx', 'docx', 'storage/documents/6/Attestation_travail_Paul_Mballa.docx'),
  (3, 10, NULL, 'FICHE_PAIE', 'Fiche_2026-09_EMP-0021.pdf', 'pdf', 'storage/documents/10/Fiche_2026-09_EMP-0021.pdf'),
  (4, 6, NULL, 'JUSTIFICATIF', 'CNI_Paul_Mballa.pdf', 'pdf', 'storage/documents/6/CNI_Paul_Mballa.pdf');

INSERT INTO fiche_paie (id_fiche, id_employe, id_document, mois, annee, salaire_brut, total_primes, total_heures_sup, total_retenues, salaire_net, statut, anomalie_detectee, date_generation, date_envoi) VALUES
  (1, 6, NULL, 9, 2026, 250000, 110000, 5408.76, 60500.00, 304908.76, 'BROUILLON', 0, '2026-09-28 10:00:00', NULL),
  (2, 10, 3, 9, 2026, 380000, 45000, 0, 15960.00, 409040.00, 'VALIDEE', 0, '2026-09-28 10:00:00', '2026-09-30 16:00:00'),
  (3, 11, NULL, 9, 2026, 330000, 25000, 0, 13860.00, 341140.00, 'VERIFIEE', 0, '2026-09-28 10:00:00', NULL);

INSERT INTO rubrique_paie (id_fiche, id_type_rubrique, libelle, nature, origine, base, taux, montant, justification) VALUES
  (1, 1, 'Prime de transport', 'PRIME', 'POSTE', NULL, NULL, 20000, NULL),
  (1, 2, 'Prime de logement', 'PRIME', 'POSTE', NULL, NULL, 30000, NULL),
  (1, 4, 'Prime exceptionnelle', 'PRIME', 'EXCEPTIONNELLE', NULL, NULL, 60000, 'Mission client à Yaoundé'),
  (1, 5, 'Heures supplémentaires', 'HEURES_SUP', 'HEURES_SUP', 3.00, 125.00, 5408.76, NULL),
  (1, 6, 'Cotisation sociale', 'RETENUE', 'POSTE', 250000, 4.20, 10500.00, NULL),
  (1, 7, 'Avance sur salaire', 'RETENUE', 'AVANCE', NULL, NULL, 50000, 'Avance du 03/09/2026'),
  (2, 1, 'Prime de transport', 'PRIME', 'POSTE', NULL, NULL, 20000, NULL),
  (2, 8, 'Prime de responsabilité', 'PRIME', 'EMPLOYE', NULL, NULL, 25000, 'Avantage durable'),
  (2, 6, 'Cotisation sociale', 'RETENUE', 'POSTE', 380000, 4.20, 15960.00, NULL),
  (3, 8, 'Prime de responsabilité', 'PRIME', 'EMPLOYE', NULL, NULL, 25000, 'Avantage durable'),
  (3, 6, 'Cotisation sociale', 'RETENUE', 'POSTE', 330000, 4.20, 13860.00, NULL);

INSERT INTO heure_supplementaire (id_heure_sup, id_employe, id_fiche, date_travail, nb_heures, code_majoration, taux_majoration, montant, motif, statut) VALUES
  (1, 6, 1, '2026-09-11', 3.00, 'MAJ_HS_JOUR', 125.00, 5408.76, 'Livraison urgente', 'INTEGREE'),
  (2, 8, NULL, '2026-10-02', 3.00, 'MAJ_HS_JOUR', 125.00, 5625.11, 'Maintenance serveur', 'VALIDEE_CHEF'),
  (3, 9, NULL, '2026-10-05', 2.00, 'MAJ_HS_JOUR', 125.00, 3461.61, 'Clôture mensuelle', 'SAISIE');

INSERT INTO avance_salaire (id_avance, id_employe, id_fiche, montant, motif, date_demande, statut) VALUES
  (1, 6, 1, 50000, 'Dépenses familiales', '2026-09-03', 'DEDUITE'),
  (2, 7, NULL, 50000, 'Frais de scolarité', '2026-10-08', 'EN_ATTENTE');

INSERT INTO validation (id_valideur, id_demande_conge, id_heure_sup, id_avance, etape, decision, commentaire, date_validation) VALUES
  (4, 1, NULL, NULL, 'AVIS_CHEF', 'APPROUVE', 'Aucun impact sur le planning', '2026-07-21 09:00:00'),
  (2, 1, NULL, NULL, 'DECISION_RH', 'APPROUVE', NULL, '2026-07-22 11:00:00'),
  (4, 2, NULL, NULL, 'AVIS_CHEF', 'APPROUVE', 'Pas d’impact sur le planning', '2026-10-09 09:30:00'),
  (11, 4, NULL, NULL, 'AVIS_CHEF', 'REFUSE', 'Période de forte activité commerciale', '2026-10-10 10:00:00'),
  (4, NULL, 1, NULL, 'AVIS_CHEF', 'APPROUVE', 'Heures effectuées et autorisées', '2026-09-14 09:00:00'),
  (2, NULL, 1, NULL, 'DECISION_RH', 'APPROUVE', NULL, '2026-09-25 15:00:00'),
  (4, NULL, 2, NULL, 'AVIS_CHEF', 'APPROUVE', 'Maintenance confirmée', '2026-10-05 09:00:00'),
  (2, NULL, NULL, 1, 'DECISION_RH', 'APPROUVE', NULL, '2026-09-04 10:00:00');

INSERT INTO demande_attestation (id_demande_attestation, id_employe, id_document, type_attestation, motif, date_demande, statut, date_traitement) VALUES
  (1, 6, 2, 'Attestation de travail', 'Dossier de visa', '2026-10-04 09:00:00', 'ACCEPTEE', '2026-10-05 11:00:00'),
  (2, 7, NULL, 'Attestation de travail', 'Demande de prêt', '2026-10-09 16:00:00', 'EN_ATTENTE', NULL);

INSERT INTO demande_modification (id_demande_modification, id_employe, champ_concerne, nouvelle_valeur, motif, statut) VALUES
  (1, 7, 'telephone', '+237 6 11 11 11 11', 'Changement d’opérateur', 'EN_ATTENTE');

INSERT INTO notification (id_utilisateur, titre, message, type, lue, envoyee_par_email, date_creation) VALUES
  (6, 'Avis favorable du chef', 'Votre demande de congé du 19 au 23 octobre a reçu un avis favorable.', 'CONGE', 0, 1, '2026-10-09 09:31:00'),
  (6, 'Attestation prête', 'Votre attestation de travail est disponible dans votre coffre-fort.', 'DOCUMENT', 1, 1, '2026-10-05 11:01:00'),
  (7, 'Demande d’avance enregistrée', 'Votre demande d’avance de 50 000 FCFA est en attente de décision.', 'AVANCE', 0, 0, '2026-10-08 10:00:00'),
  (2, 'Demandes à traiter', 'Plusieurs demandes attendent votre décision.', 'DEMANDE', 0, 0, '2026-10-10 08:00:00'),
  (4, 'Demande de congé à examiner', 'Sandra Kamga a déposé une demande de congé maladie.', 'CONGE', 0, 0, '2026-10-12 08:16:00');

INSERT INTO journal_action (id_utilisateur, action, entite, id_entite, details, adresse_ip, date_action) VALUES
  (2, 'Création d’un compte employé', 'employe', 9, 'Compte de Nadège Biyong créé', '10.0.0.12', '2026-09-01 08:30:00'),
  (4, 'Avis favorable sur un congé', 'demande_conge', 2, 'Congé de Paul Mballa', '10.0.0.34', '2026-10-09 09:30:00'),
  (2, 'Validation d’une avance', 'avance_salaire', 1, 'Avance de 50 000 FCFA', '10.0.0.12', '2026-09-04 10:00:00'),
  (3, 'Création d’une fiche de paie', 'fiche_paie', 2, 'Fiche de septembre 2026', '10.0.0.15', '2026-09-28 10:00:00'),
  (7, 'Demande d’avance sur salaire', 'avance_salaire', 2, '50 000 FCFA', '10.0.0.77', '2026-10-08 10:00:00');
