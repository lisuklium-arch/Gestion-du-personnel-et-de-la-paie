CREATE DATABASE IF NOT EXISTS personnel_paie CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE personnel_paie;

CREATE TABLE utilisateur (
  id_utilisateur        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email                 VARCHAR(150) NOT NULL,
  mot_de_passe_hash     VARCHAR(255) NOT NULL,                     
  role                  ENUM('EMPLOYE','CHEF_DEPARTEMENT','RESPONSABLE_RH','DIRECTEUR_GENERAL') NOT NULL DEFAULT 'EMPLOYE',
  statut                ENUM('EN_ATTENTE','ACTIF','DESACTIVE') NOT NULL DEFAULT 'EN_ATTENTE',
  token_reinitialisation VARCHAR(64) NULL,                         
  expiration_token      DATETIME NULL,
  derniere_connexion    DATETIME NULL,
  date_creation         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_utilisateur),
  UNIQUE KEY uq_utilisateur_email (email)
) ENGINE=InnoDB;

CREATE TABLE poste (
  id_poste    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  intitule    VARCHAR(100) NOT NULL,
  description VARCHAR(255) NULL,
  PRIMARY KEY (id_poste),
  UNIQUE KEY uq_poste_intitule (intitule)
) ENGINE=InnoDB;

CREATE TABLE departement (
  id_departement        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code                  VARCHAR(20) NOT NULL,
  nom                   VARCHAR(100) NOT NULL,
  description           VARCHAR(255) NULL,
  id_chef               INT UNSIGNED NULL,                          
  id_departement_parent INT UNSIGNED NULL,  
   PRIMARY KEY (id_departement),                        
  UNIQUE KEY uq_departement_code (code),
  CONSTRAINT fk_departement_parent FOREIGN KEY (id_departement_parent) REFERENCES departement (id_departement)
) ENGINE=InnoDB;

CREATE TABLE employe (
  id_employe      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_utilisateur  INT UNSIGNED NOT NULL,
  id_departement  INT UNSIGNED NOT NULL,
  id_poste        INT UNSIGNED NOT NULL,
  matricule       VARCHAR(20) NOT NULL,
  nom             VARCHAR(80) NOT NULL,
  prenom          VARCHAR(80) NOT NULL,
  date_naissance  DATE NULL,
  telephone       VARCHAR(30) NULL,
  adresse         VARCHAR(255) NULL,
  photo           VARCHAR(255) NULL,
  date_embauche   DATE NOT NULL,
  statut          ENUM('ACTIF','EN_CONGE','SUSPENDU','SORTI') NOT NULL DEFAULT 'ACTIF',
  PRIMARY KEY (id_employe),
  UNIQUE KEY uq_employe_matricule (matricule),
  UNIQUE KEY uq_employe_utilisateur (id_utilisateur),               
  KEY idx_employe_nom (nom, prenom),
  KEY idx_employe_statut (statut),
  CONSTRAINT fk_employe_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur),
  CONSTRAINT fk_employe_departement FOREIGN KEY (id_departement) REFERENCES departement (id_departement),
  CONSTRAINT fk_employe_poste       FOREIGN KEY (id_poste)       REFERENCES poste (id_poste)
) ENGINE=InnoDB;

ALTER TABLE departement
  ADD CONSTRAINT fk_departement_chef FOREIGN KEY (id_chef) REFERENCES employe (id_employe),
  ADD UNIQUE KEY uq_departement_chef (id_chef);                     

CREATE TABLE contrat (
  id_contrat              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_employe              INT UNSIGNED NOT NULL,
  reference               VARCHAR(30) NOT NULL,
  type_contrat            ENUM('CDI','CDD','STAGE','AUTRE') NOT NULL,
  date_debut              DATE NOT NULL,
  date_fin                DATE NULL,
  salaire_base            DECIMAL(12,2) NOT NULL,
  duree_mensuelle_heures  DECIMAL(6,2) NOT NULL DEFAULT 173.33,     
  actif                   TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_contrat),
  UNIQUE KEY uq_contrat_reference (reference),
  KEY idx_contrat_employe (id_employe, actif),
  KEY idx_contrat_fin (date_fin),
  CONSTRAINT fk_contrat_employe FOREIGN KEY (id_employe) REFERENCES employe (id_employe),
  CONSTRAINT ck_contrat_dates   CHECK (date_fin IS NULL OR date_fin >= date_debut),
  CONSTRAINT ck_contrat_salaire CHECK (salaire_base >= 0 AND duree_mensuelle_heures > 0)
) ENGINE=InnoDB;


CREATE TABLE type_conge (
  id_type_conge       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  libelle             VARCHAR(80) NOT NULL,
  jours_par_an        DECIMAL(5,1) NOT NULL DEFAULT 0,
  remunere            TINYINT(1) NOT NULL DEFAULT 1,
  justificatif_requis TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id_type_conge),
  UNIQUE KEY uq_type_conge_libelle (libelle)
) ENGINE=InnoDB;

CREATE TABLE solde_conge (
  id_solde       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_employe     INT UNSIGNED NOT NULL,
  id_type_conge  INT UNSIGNED NOT NULL,
  annee          SMALLINT UNSIGNED NOT NULL,
  jours_acquis   DECIMAL(5,1) NOT NULL DEFAULT 0,
  jours_pris     DECIMAL(5,1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id_solde),
  UNIQUE KEY uq_solde (id_employe, id_type_conge, annee),
  CONSTRAINT fk_solde_employe FOREIGN KEY (id_employe)    REFERENCES employe (id_employe),
  CONSTRAINT fk_solde_type    FOREIGN KEY (id_type_conge) REFERENCES type_conge (id_type_conge),
  CONSTRAINT ck_solde CHECK (jours_acquis >= 0 AND jours_pris >= 0)
) ENGINE=InnoDB;

CREATE TABLE demande_conge (
  id_demande_conge INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_employe       INT UNSIGNED NOT NULL,
  id_type_conge    INT UNSIGNED NOT NULL,
  date_debut       DATE NOT NULL,
  date_fin         DATE NOT NULL,
  nb_jours_ouvres  DECIMAL(5,1) NOT NULL,
  motif            VARCHAR(255) NULL,
  justificatif     VARCHAR(255) NULL,
  statut           ENUM('EN_ATTENTE_AVIS','EN_ATTENTE_RH','APPROUVE','REFUSE','ANNULE') NOT NULL DEFAULT 'EN_ATTENTE_AVIS',
  date_depot       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_demande_conge),
  KEY idx_demande_employe (id_employe, date_debut),
  KEY idx_demande_statut (statut),
  CONSTRAINT fk_demande_employe FOREIGN KEY (id_employe)    REFERENCES employe (id_employe),
  CONSTRAINT fk_demande_type    FOREIGN KEY (id_type_conge) REFERENCES type_conge (id_type_conge),
  CONSTRAINT ck_demande_dates   CHECK (date_fin >= date_debut AND nb_jours_ouvres > 0)
) ENGINE=InnoDB;

CREATE TABLE presence (
  id_presence    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_employe     INT UNSIGNED NOT NULL,
  date_presence  DATE NOT NULL,
  statut         ENUM('PRESENT','ABSENT','JUSTIFIE','RETARD') NOT NULL DEFAULT 'PRESENT',
  motif          VARCHAR(255) NULL,
  PRIMARY KEY (id_presence),
  UNIQUE KEY uq_presence (id_employe, date_presence),
  CONSTRAINT fk_presence_employe FOREIGN KEY (id_employe) REFERENCES employe (id_employe)
) ENGINE=InnoDB;


CREATE TABLE type_rubrique (
  id_type_rubrique  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code              VARCHAR(30) NOT NULL,
  libelle           VARCHAR(100) NOT NULL,
  nature            ENUM('PRIME','HEURES_SUP','RETENUE') NOT NULL,
  mode_calcul       ENUM('MONTANT_FIXE','POURCENTAGE_BRUT') NOT NULL DEFAULT 'MONTANT_FIXE',
  valeur_par_defaut DECIMAL(12,2) NOT NULL DEFAULT 0,
  actif             TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_type_rubrique),
  UNIQUE KEY uq_type_rubrique_code (code)
) ENGINE=InnoDB;

CREATE TABLE rubrique_poste (                                       
  id_rubrique_poste INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_poste          INT UNSIGNED NOT NULL,
  id_type_rubrique  INT UNSIGNED NOT NULL,
  montant           DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id_rubrique_poste),
  UNIQUE KEY uq_rubrique_poste (id_poste, id_type_rubrique),
  CONSTRAINT fk_rubrique_poste_poste FOREIGN KEY (id_poste)         REFERENCES poste (id_poste),
  CONSTRAINT fk_rubrique_poste_type  FOREIGN KEY (id_type_rubrique) REFERENCES type_rubrique (id_type_rubrique)
) ENGINE=InnoDB;

CREATE TABLE rubrique_employe (                                     
  id_rubrique_employe INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_employe          INT UNSIGNED NOT NULL,
  id_type_rubrique    INT UNSIGNED NOT NULL,
  montant             DECIMAL(12,2) NOT NULL,
  date_debut          DATE NOT NULL,
  date_fin            DATE NULL,
  justification       VARCHAR(255) NULL,
  PRIMARY KEY (id_rubrique_employe),
  KEY idx_rubrique_employe (id_employe, date_debut),
  CONSTRAINT fk_rubrique_employe_employe FOREIGN KEY (id_employe)       REFERENCES employe (id_employe),
  CONSTRAINT fk_rubrique_employe_type    FOREIGN KEY (id_type_rubrique) REFERENCES type_rubrique (id_type_rubrique),
  CONSTRAINT ck_rubrique_employe CHECK (date_fin IS NULL OR date_fin >= date_debut)
) ENGINE=InnoDB;

CREATE TABLE parametre_paie (
  id_parametre INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cle          VARCHAR(50) NOT NULL,
  valeur       VARCHAR(100) NOT NULL,
  description  VARCHAR(255) NULL,
  date_effet   DATE NOT NULL,
  PRIMARY KEY (id_parametre),
  UNIQUE KEY uq_parametre_cle (cle)
) ENGINE=InnoDB;


CREATE TABLE document_employe (
  id_document      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_employe       INT UNSIGNED NOT NULL,
  id_contrat       INT UNSIGNED NULL,                               
  `type`           ENUM('CONTRAT','ATTESTATION','FICHE_PAIE','JUSTIFICATIF','AUTRE') NOT NULL,
  nom_fichier      VARCHAR(150) NOT NULL,
  `format`         VARCHAR(10) NOT NULL,
  chemin_stockage  VARCHAR(255) NOT NULL,                           
  date_ajout       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_document),
  KEY idx_document_employe (id_employe, `type`),
  CONSTRAINT fk_document_employe FOREIGN KEY (id_employe) REFERENCES employe (id_employe),
  CONSTRAINT fk_document_contrat FOREIGN KEY (id_contrat) REFERENCES contrat (id_contrat)
) ENGINE=InnoDB;

CREATE TABLE fiche_paie (
  id_fiche          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_employe        INT UNSIGNED NOT NULL,
  id_document       INT UNSIGNED NULL,                              
  mois              TINYINT UNSIGNED NOT NULL,
  annee             SMALLINT UNSIGNED NOT NULL,
  salaire_brut      DECIMAL(12,2) NOT NULL,
  total_primes      DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_heures_sup  DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_retenues    DECIMAL(12,2) NOT NULL DEFAULT 0,
  salaire_net       DECIMAL(12,2) NOT NULL,                         
  statut            ENUM('BROUILLON','VERIFIEE','VALIDEE','ARCHIVEE') NOT NULL DEFAULT 'BROUILLON',
  anomalie_detectee TINYINT(1) NOT NULL DEFAULT 0,
  date_generation   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_envoi        DATETIME NULL,
  PRIMARY KEY (id_fiche),
  UNIQUE KEY uq_fiche_periode (id_employe, annee, mois),            
  UNIQUE KEY uq_fiche_document (id_document),
  KEY idx_fiche_statut (statut),
  CONSTRAINT fk_fiche_employe  FOREIGN KEY (id_employe)  REFERENCES employe (id_employe),
  CONSTRAINT fk_fiche_document FOREIGN KEY (id_document) REFERENCES document_employe (id_document),
  CONSTRAINT ck_fiche_mois CHECK (mois BETWEEN 1 AND 12)
) ENGINE=InnoDB;

CREATE TABLE rubrique_paie (                                        
  id_rubrique      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_fiche         INT UNSIGNED NOT NULL,
  id_type_rubrique INT UNSIGNED NOT NULL,
  libelle          VARCHAR(150) NOT NULL,
  nature           ENUM('PRIME','HEURES_SUP','RETENUE') NOT NULL,
  origine          ENUM('POSTE','EMPLOYE','EXCEPTIONNELLE','HEURES_SUP','AVANCE') NOT NULL,
  base             DECIMAL(12,2) NULL,                              
  taux             DECIMAL(8,2) NULL,                               
  montant          DECIMAL(12,2) NOT NULL,
  justification    VARCHAR(255) NULL,                               
  PRIMARY KEY (id_rubrique),
  KEY idx_rubrique_fiche (id_fiche),
  CONSTRAINT fk_rubrique_fiche FOREIGN KEY (id_fiche)         REFERENCES fiche_paie (id_fiche) ON DELETE CASCADE,
  CONSTRAINT fk_rubrique_type  FOREIGN KEY (id_type_rubrique) REFERENCES type_rubrique (id_type_rubrique)
) ENGINE=InnoDB;

CREATE TABLE heure_supplementaire (
  id_heure_sup    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_employe      INT UNSIGNED NOT NULL,
  id_fiche        INT UNSIGNED NULL,                                
  date_travail    DATE NOT NULL,
  nb_heures       DECIMAL(5,2) NOT NULL,
  code_majoration VARCHAR(30) NOT NULL,                             
  taux_majoration DECIMAL(5,2) NOT NULL,                            
  montant         DECIMAL(12,2) NULL,
  motif           VARCHAR(255) NULL,
  statut          ENUM('SAISIE','VALIDEE_CHEF','INTEGREE','REJETEE') NOT NULL DEFAULT 'SAISIE',
  PRIMARY KEY (id_heure_sup),
  KEY idx_hs_employe (id_employe, date_travail),
  KEY idx_hs_statut (statut),
  CONSTRAINT fk_hs_employe FOREIGN KEY (id_employe) REFERENCES employe (id_employe),
  CONSTRAINT fk_hs_fiche   FOREIGN KEY (id_fiche)   REFERENCES fiche_paie (id_fiche),
  CONSTRAINT ck_hs CHECK (nb_heures > 0 AND taux_majoration > 0)
) ENGINE=InnoDB;

CREATE TABLE avance_salaire (
  id_avance     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_employe    INT UNSIGNED NOT NULL,
  id_fiche      INT UNSIGNED NULL,                                  
  montant       DECIMAL(12,2) NOT NULL,
  motif         VARCHAR(255) NULL,
  date_demande  DATE NOT NULL,
  statut        ENUM('EN_ATTENTE','VALIDEE','REFUSEE','DEDUITE') NOT NULL DEFAULT 'EN_ATTENTE',
  PRIMARY KEY (id_avance),
  KEY idx_avance_employe (id_employe),
  KEY idx_avance_statut (statut),
  CONSTRAINT fk_avance_employe FOREIGN KEY (id_employe) REFERENCES employe (id_employe),
  CONSTRAINT fk_avance_fiche   FOREIGN KEY (id_fiche)   REFERENCES fiche_paie (id_fiche),
  CONSTRAINT ck_avance CHECK (montant > 0)
) ENGINE=InnoDB;


CREATE TABLE validation (
  id_validation    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_valideur      INT UNSIGNED NOT NULL,                           
  id_demande_conge INT UNSIGNED NULL,
  id_heure_sup     INT UNSIGNED NULL,
  id_avance        INT UNSIGNED NULL,
  etape            ENUM('AVIS_CHEF','DECISION_RH') NOT NULL,
  decision         ENUM('APPROUVE','REFUSE') NOT NULL,
  commentaire      VARCHAR(255) NULL,
  date_validation  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_validation),
  UNIQUE KEY uq_validation_conge (id_demande_conge, etape),         
  UNIQUE KEY uq_validation_hs    (id_heure_sup, etape),
  UNIQUE KEY uq_validation_avance (id_avance, etape),
  CONSTRAINT fk_validation_valideur FOREIGN KEY (id_valideur)      REFERENCES employe (id_employe),
  CONSTRAINT fk_validation_conge    FOREIGN KEY (id_demande_conge) REFERENCES demande_conge (id_demande_conge),
  CONSTRAINT fk_validation_hs       FOREIGN KEY (id_heure_sup)     REFERENCES heure_supplementaire (id_heure_sup),
  CONSTRAINT fk_validation_avance   FOREIGN KEY (id_avance)        REFERENCES avance_salaire (id_avance),
  CONSTRAINT ck_validation_une_cible                                  
    CHECK ((id_demande_conge IS NOT NULL) + (id_heure_sup IS NOT NULL) + (id_avance IS NOT NULL) = 1)
) ENGINE=InnoDB;

CREATE TABLE demande_attestation (
  id_demande_attestation INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_employe             INT UNSIGNED NOT NULL,
  id_document            INT UNSIGNED NULL,                         
  type_attestation       VARCHAR(100) NOT NULL,
  motif                  VARCHAR(255) NULL,
  date_demande           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  statut                 ENUM('EN_ATTENTE','ACCEPTEE','REFUSEE') NOT NULL DEFAULT 'EN_ATTENTE',
  date_traitement        DATETIME NULL,
  commentaire_rh         VARCHAR(255) NULL,
  PRIMARY KEY (id_demande_attestation),
  UNIQUE KEY uq_attestation_document (id_document),
  KEY idx_attestation_statut (statut),
  CONSTRAINT fk_attestation_employe  FOREIGN KEY (id_employe)  REFERENCES employe (id_employe),
  CONSTRAINT fk_attestation_document FOREIGN KEY (id_document) REFERENCES document_employe (id_document)
) ENGINE=InnoDB;

CREATE TABLE demande_modification (
  id_demande_modification INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_employe              INT UNSIGNED NOT NULL,
  champ_concerne          VARCHAR(50) NOT NULL,
  nouvelle_valeur         VARCHAR(255) NOT NULL,
  motif                   VARCHAR(255) NULL,
  date_demande            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  statut                  ENUM('EN_ATTENTE','ACCEPTEE','REFUSEE') NOT NULL DEFAULT 'EN_ATTENTE',
  date_traitement         DATETIME NULL,
  commentaire_rh          VARCHAR(255) NULL,
  PRIMARY KEY (id_demande_modification),
  KEY idx_modification_statut (statut),
  CONSTRAINT fk_modification_employe FOREIGN KEY (id_employe) REFERENCES employe (id_employe)
) ENGINE=InnoDB;


CREATE TABLE notification (
  id_notification   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_utilisateur    INT UNSIGNED NOT NULL,
  titre             VARCHAR(150) NOT NULL,
  message           TEXT NOT NULL,
  `type`            VARCHAR(30) NOT NULL,
  lue               TINYINT(1) NOT NULL DEFAULT 0,
  envoyee_par_email TINYINT(1) NOT NULL DEFAULT 0,
  date_creation     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_notification),
  KEY idx_notification_utilisateur (id_utilisateur, lue),
  CONSTRAINT fk_notification_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE journal_action (
  id_journal      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_utilisateur  INT UNSIGNED NOT NULL,
  action          VARCHAR(100) NOT NULL,
  entite          VARCHAR(50) NOT NULL,
  id_entite       INT UNSIGNED NULL,
  details         TEXT NULL,
  adresse_ip      VARCHAR(45) NULL,
  date_action     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_journal),
  KEY idx_journal_date (date_action),
  KEY idx_journal_utilisateur (id_utilisateur),
  CONSTRAINT fk_journal_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
) ENGINE=InnoDB;
