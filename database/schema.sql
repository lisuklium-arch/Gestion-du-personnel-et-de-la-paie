
DROP DATABASE IF EXISTS personnel_paie;
CREATE DATABASE personnel_paie CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE personnel_paie;


CREATE TABLE utilisateur (
  id_utilisateur        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email                 VARCHAR(150) NOT NULL,
  mot_de_passe_hash     VARCHAR(255) NOT NULL,                      
  role                  ENUM('EMPLOYE','CHEF_DEPARTEMENT','RESPONSABLE_RH','DIRECTEUR_GENERAL') NOT NULL DEFAULT 'EMPLOYE',
  statut                ENUM('EN_ATTENTE','ACTIF','DESACTIVE') NOT NULL DEFAULT 'EN_ATTENTE',
  token_reinitialisation VARCHAR(64) NULL,                          
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

