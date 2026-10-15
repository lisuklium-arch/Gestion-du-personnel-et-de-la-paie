SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS projet8_rh_paie;
CREATE DATABASE projet8_rh_paie CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE projet8_rh_paie;

CREATE TABLE utilisateur (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email               VARCHAR(190) NOT NULL,
    mot_de_passe_hash   VARCHAR(255) NOT NULL,
    role                ENUM('employe','chef','rh','dg') NOT NULL,
    statut_compte       ENUM('en_attente','actif','desactive') NOT NULL DEFAULT 'en_attente',
    doit_changer_mdp    TINYINT(1) NOT NULL DEFAULT 1,
    derniere_connexion  DATETIME NULL,
    valide_par_id       INT UNSIGNED NULL,
    date_validation     DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_utilisateur_email (email),
    KEY idx_utilisateur_role (role),
    CONSTRAINT fk_utilisateur_valide_par FOREIGN KEY (valide_par_id)
        REFERENCES utilisateur (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE jeton_reinitialisation (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    utilisateur_id  INT UNSIGNED NOT NULL,
    jeton_hash      CHAR(64) NOT NULL,
    expire_le       DATETIME NOT NULL,
    utilise_le      DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_jeton_hash (jeton_hash),
    KEY idx_jeton_utilisateur (utilisateur_id),
    CONSTRAINT fk_jeton_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE departement (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom          VARCHAR(100) NOT NULL,
    description  VARCHAR(255) NULL,
    parent_id    INT UNSIGNED NULL,
    chef_id      INT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_departement_nom (nom),
    UNIQUE KEY uq_departement_chef (chef_id),
    KEY idx_departement_parent (parent_id),
    CONSTRAINT fk_departement_parent FOREIGN KEY (parent_id)
        REFERENCES departement (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE poste (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    intitule     VARCHAR(100) NOT NULL,
    description  VARCHAR(255) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_poste_intitule (intitule)
) ENGINE=InnoDB;

CREATE TABLE employe (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    utilisateur_id  INT UNSIGNED NOT NULL,
    matricule       VARCHAR(20) NOT NULL,
    nom             VARCHAR(100) NOT NULL,
    prenom          VARCHAR(100) NOT NULL,
    date_naissance  DATE NULL,
    telephone       VARCHAR(30) NULL,
    photo_chemin    VARCHAR(255) NULL,
    date_embauche   DATE NOT NULL,
    statut          ENUM('actif','suspendu','sorti') NOT NULL DEFAULT 'actif',
    departement_id  INT UNSIGNED NOT NULL,
    poste_id        INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_employe_utilisateur (utilisateur_id),
    UNIQUE KEY uq_employe_matricule (matricule),
    KEY idx_employe_departement (departement_id),
    KEY idx_employe_poste (poste_id),
    KEY idx_employe_statut (statut),
    KEY idx_employe_nom (nom, prenom),
    CONSTRAINT fk_employe_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_employe_departement FOREIGN KEY (departement_id)
        REFERENCES departement (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_employe_poste FOREIGN KEY (poste_id)
        REFERENCES poste (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

ALTER TABLE departement
    ADD CONSTRAINT fk_departement_chef FOREIGN KEY (chef_id)
        REFERENCES employe (id) ON DELETE SET NULL ON UPDATE CASCADE;

CREATE TABLE contrat (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    employe_id         INT UNSIGNED NOT NULL,
    type               ENUM('CDI','CDD','stage','prestation') NOT NULL,
    date_debut         DATE NOT NULL,
    date_fin           DATE NULL,
    salaire_base       DECIMAL(12,0) NOT NULL,
    heures_mensuelles  DECIMAL(5,2) NOT NULL DEFAULT 173.33,
    statut             ENUM('en_cours','termine','resilie') NOT NULL DEFAULT 'en_cours',
    fichier_chemin     VARCHAR(255) NULL,
    PRIMARY KEY (id),
    KEY idx_contrat_employe (employe_id, statut),
    CONSTRAINT ck_contrat_dates CHECK (date_fin IS NULL OR date_fin >= date_debut),
    CONSTRAINT ck_contrat_salaire CHECK (salaire_base >= 0),
    CONSTRAINT fk_contrat_employe FOREIGN KEY (employe_id)
        REFERENCES employe (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE document_employe (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    employe_id     INT UNSIGNED NOT NULL,
    type           ENUM('piece_identite','diplome','contrat_signe','attestation','autre') NOT NULL,
    nom_fichier    VARCHAR(255) NOT NULL,
    chemin_prive   VARCHAR(255) NOT NULL,
    ajoute_par_id  INT UNSIGNED NOT NULL,
    date_ajout     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_document_employe (employe_id, type),
    CONSTRAINT fk_document_employe FOREIGN KEY (employe_id)
        REFERENCES employe (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_document_ajoute_par FOREIGN KEY (ajoute_par_id)
        REFERENCES utilisateur (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE demande_modification (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    employe_id       INT UNSIGNED NOT NULL,
    champ            VARCHAR(50) NOT NULL,
    ancienne_valeur  VARCHAR(255) NULL,
    nouvelle_valeur  VARCHAR(255) NOT NULL,
    motif            VARCHAR(255) NULL,
    statut           ENUM('en_attente','acceptee','refusee') NOT NULL DEFAULT 'en_attente',
    traite_par_id    INT UNSIGNED NULL,
    date_demande     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_traitement  DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_demmodif_employe (employe_id),
    KEY idx_demmodif_statut (statut),
    CONSTRAINT fk_demmodif_employe FOREIGN KEY (employe_id)
        REFERENCES employe (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_demmodif_traite_par FOREIGN KEY (traite_par_id)
        REFERENCES utilisateur (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE demande_document (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    employe_id     INT UNSIGNED NOT NULL,
    type           VARCHAR(50) NOT NULL,
    motif          VARCHAR(255) NULL,
    statut         ENUM('en_attente','generee','refusee') NOT NULL DEFAULT 'en_attente',
    document_id    INT UNSIGNED NULL,
    traite_par_id  INT UNSIGNED NULL,
    date_demande   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_demdoc_document (document_id),
    KEY idx_demdoc_employe (employe_id),
    KEY idx_demdoc_statut (statut),
    CONSTRAINT fk_demdoc_employe FOREIGN KEY (employe_id)
        REFERENCES employe (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_demdoc_document FOREIGN KEY (document_id)
        REFERENCES document_employe (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_demdoc_traite_par FOREIGN KEY (traite_par_id)
        REFERENCES utilisateur (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE type_conge (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code                VARCHAR(30) NOT NULL,
    libelle             VARCHAR(100) NOT NULL,
    decompte_solde      TINYINT(1) NOT NULL DEFAULT 1,
    droit_annuel_jours  DECIMAL(4,1) NOT NULL DEFAULT 18.0,
    justificatif_requis TINYINT(1) NOT NULL DEFAULT 0,
    actif               TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_type_conge_code (code)
) ENGINE=InnoDB;

CREATE TABLE solde_conge (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    employe_id     INT UNSIGNED NOT NULL,
    type_conge_id  INT UNSIGNED NOT NULL,
    annee          SMALLINT UNSIGNED NOT NULL,
    jours_acquis   DECIMAL(4,1) NOT NULL DEFAULT 0,
    jours_pris     DECIMAL(4,1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_solde (employe_id, type_conge_id, annee),
    KEY idx_solde_type (type_conge_id),
    CONSTRAINT ck_solde_positif CHECK (jours_acquis >= 0 AND jours_pris >= 0),
    CONSTRAINT fk_solde_employe FOREIGN KEY (employe_id)
        REFERENCES employe (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_solde_type FOREIGN KEY (type_conge_id)
        REFERENCES type_conge (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE demande_conge (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    employe_id          INT UNSIGNED NOT NULL,
    type_conge_id       INT UNSIGNED NOT NULL,
    date_debut          DATE NOT NULL,
    date_fin            DATE NOT NULL,
    nb_jours            DECIMAL(4,1) NOT NULL,
    motif               VARCHAR(255) NULL,
    justificatif_chemin VARCHAR(255) NULL,
    statut              ENUM('en_attente_chef','en_attente_rh','en_attente_dg','approuvee','refusee','annulee')
                        NOT NULL DEFAULT 'en_attente_chef',
    date_soumission     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_demconge_employe (employe_id, statut),
    KEY idx_demconge_type (type_conge_id),
    KEY idx_demconge_periode (date_debut, date_fin),
    KEY idx_demconge_statut (statut),
    CONSTRAINT ck_demconge_dates CHECK (date_fin >= date_debut),
    CONSTRAINT ck_demconge_jours CHECK (nb_jours > 0),
    CONSTRAINT fk_demconge_employe FOREIGN KEY (employe_id)
        REFERENCES employe (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_demconge_type FOREIGN KEY (type_conge_id)
        REFERENCES type_conge (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE validation_conge (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    demande_conge_id  INT UNSIGNED NOT NULL,
    etape             ENUM('chef','rh','dg') NOT NULL,
    decision          ENUM('favorable','defavorable','approuve','refuse') NOT NULL,
    commentaire       VARCHAR(255) NULL,
    decideur_id       INT UNSIGNED NOT NULL,
    date_decision     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_validation (demande_conge_id, etape),
    KEY idx_validation_decideur (decideur_id),
    CONSTRAINT fk_validation_demande FOREIGN KEY (demande_conge_id)
        REFERENCES demande_conge (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_validation_decideur FOREIGN KEY (decideur_id)
        REFERENCES utilisateur (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE jour_ferie (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    date_ferie  DATE NOT NULL,
    libelle     VARCHAR(100) NOT NULL,
    recurrent   TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_jour_ferie_date (date_ferie)
) ENGINE=InnoDB;

CREATE TABLE absence (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    employe_id        INT UNSIGNED NOT NULL,
    date_absence      DATE NOT NULL,
    motif             VARCHAR(255) NULL,
    justifiee         TINYINT(1) NOT NULL DEFAULT 0,
    demande_conge_id  INT UNSIGNED NULL,
    marquee_par_id    INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_absence (employe_id, date_absence),
    KEY idx_absence_demande (demande_conge_id),
    KEY idx_absence_marquee_par (marquee_par_id),
    CONSTRAINT fk_absence_employe FOREIGN KEY (employe_id)
        REFERENCES employe (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_absence_demande FOREIGN KEY (demande_conge_id)
        REFERENCES demande_conge (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_absence_marquee_par FOREIGN KEY (marquee_par_id)
        REFERENCES utilisateur (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE fiche_paie (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    employe_id        INT UNSIGNED NOT NULL,
    contrat_id        INT UNSIGNED NOT NULL,
    annee             SMALLINT UNSIGNED NOT NULL,
    mois              TINYINT UNSIGNED NOT NULL,
    salaire_base      DECIMAL(12,0) NOT NULL,
    total_heures_sup  DECIMAL(12,0) NOT NULL DEFAULT 0,
    salaire_brut      DECIMAL(12,0) NOT NULL DEFAULT 0,
    total_primes      DECIMAL(12,0) NOT NULL DEFAULT 0,
    total_retenues    DECIMAL(12,0) NOT NULL DEFAULT 0,
    salaire_net       DECIMAL(12,0) NOT NULL DEFAULT 0,
    statut            ENUM('brouillon','verifiee','validee','archivee') NOT NULL DEFAULT 'brouillon',
    valide_par_id     INT UNSIGNED NULL,
    date_validation   DATETIME NULL,
    pdf_chemin        VARCHAR(255) NULL,
    envoyee_le        DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_fiche (employe_id, annee, mois),
    KEY idx_fiche_contrat (contrat_id),
    KEY idx_fiche_periode (annee, mois, statut),
    KEY idx_fiche_valide_par (valide_par_id),
    CONSTRAINT ck_fiche_mois CHECK (mois BETWEEN 1 AND 12),
    CONSTRAINT fk_fiche_employe FOREIGN KEY (employe_id)
        REFERENCES employe (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_fiche_contrat FOREIGN KEY (contrat_id)
        REFERENCES contrat (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_fiche_valide_par FOREIGN KEY (valide_par_id)
        REFERENCES utilisateur (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE rubrique_paie (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code            VARCHAR(30) NOT NULL,
    libelle         VARCHAR(100) NOT NULL,
    nature          ENUM('prime','retenue') NOT NULL,
    mode_calcul     ENUM('fixe','pourcentage') NOT NULL DEFAULT 'fixe',
    valeur_defaut   DECIMAL(12,2) NOT NULL DEFAULT 0,
    actif           TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rubrique_code (code)
) ENGINE=InnoDB;

CREATE TABLE ligne_paie (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    fiche_paie_id  INT UNSIGNED NOT NULL,
    rubrique_id    INT UNSIGNED NULL,
    libelle        VARCHAR(150) NOT NULL,
    nature         ENUM('prime','retenue') NOT NULL,
    base_calcul    DECIMAL(12,2) NULL,
    taux           DECIMAL(5,2) NULL,
    montant        DECIMAL(12,0) NOT NULL,
    origine        ENUM('poste','employe','exceptionnelle','auto','avance') NOT NULL,
    justification  VARCHAR(255) NULL,
    PRIMARY KEY (id),
    KEY idx_ligne_fiche (fiche_paie_id),
    KEY idx_ligne_rubrique (rubrique_id),
    CONSTRAINT fk_ligne_fiche FOREIGN KEY (fiche_paie_id)
        REFERENCES fiche_paie (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ligne_rubrique FOREIGN KEY (rubrique_id)
        REFERENCES rubrique_paie (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE poste_rubrique (
    poste_id     INT UNSIGNED NOT NULL,
    rubrique_id  INT UNSIGNED NOT NULL,
    montant      DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (poste_id, rubrique_id),
    KEY idx_poste_rubrique_rubrique (rubrique_id),
    CONSTRAINT fk_posterub_poste FOREIGN KEY (poste_id)
        REFERENCES poste (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_posterub_rubrique FOREIGN KEY (rubrique_id)
        REFERENCES rubrique_paie (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE employe_rubrique (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    employe_id   INT UNSIGNED NOT NULL,
    rubrique_id  INT UNSIGNED NOT NULL,
    montant      DECIMAL(12,2) NOT NULL,
    date_debut   DATE NOT NULL,
    date_fin     DATE NULL,
    PRIMARY KEY (id),
    KEY idx_emprub_employe (employe_id, date_debut),
    KEY idx_emprub_rubrique (rubrique_id),
    CONSTRAINT ck_emprub_dates CHECK (date_fin IS NULL OR date_fin >= date_debut),
    CONSTRAINT fk_emprub_employe FOREIGN KEY (employe_id)
        REFERENCES employe (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_emprub_rubrique FOREIGN KEY (rubrique_id)
        REFERENCES rubrique_paie (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE heure_supplementaire (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    employe_id        INT UNSIGNED NOT NULL,
    date_realisation  DATE NOT NULL,
    nb_heures         DECIMAL(5,2) NOT NULL,
    motif             VARCHAR(255) NULL,
    statut            ENUM('saisie','validee_chef','integree','rejetee') NOT NULL DEFAULT 'saisie',
    taux_majoration   DECIMAL(5,2) NULL,
    montant           DECIMAL(12,0) NULL,
    valide_par_id     INT UNSIGNED NULL,
    date_validation   DATETIME NULL,
    fiche_paie_id     INT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_hs_employe (employe_id, date_realisation),
    KEY idx_hs_statut (statut),
    KEY idx_hs_fiche (fiche_paie_id),
    KEY idx_hs_valide_par (valide_par_id),
    CONSTRAINT ck_hs_heures CHECK (nb_heures > 0),
    CONSTRAINT fk_hs_employe FOREIGN KEY (employe_id)
        REFERENCES employe (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_hs_valide_par FOREIGN KEY (valide_par_id)
        REFERENCES utilisateur (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_hs_fiche FOREIGN KEY (fiche_paie_id)
        REFERENCES fiche_paie (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE avance_salaire (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    employe_id      INT UNSIGNED NOT NULL,
    montant         DECIMAL(12,0) NOT NULL,
    motif           VARCHAR(255) NULL,
    statut          ENUM('en_attente','accordee','refusee') NOT NULL DEFAULT 'en_attente',
    traite_par_id   INT UNSIGNED NULL,
    fiche_paie_id   INT UNSIGNED NULL,
    date_demande    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_decision   DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_avance_employe (employe_id, statut),
    KEY idx_avance_fiche (fiche_paie_id),
    KEY idx_avance_traite_par (traite_par_id),
    CONSTRAINT ck_avance_montant CHECK (montant > 0),
    CONSTRAINT fk_avance_employe FOREIGN KEY (employe_id)
        REFERENCES employe (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_avance_traite_par FOREIGN KEY (traite_par_id)
        REFERENCES utilisateur (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_avance_fiche FOREIGN KEY (fiche_paie_id)
        REFERENCES fiche_paie (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notification (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    utilisateur_id  INT UNSIGNED NOT NULL,
    type            VARCHAR(50) NOT NULL,
    titre           VARCHAR(150) NOT NULL,
    message         VARCHAR(500) NULL,
    lien            VARCHAR(255) NULL,
    lue             TINYINT(1) NOT NULL DEFAULT 0,
    email_envoye    TINYINT(1) NOT NULL DEFAULT 0,
    date_creation   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notification_utilisateur (utilisateur_id, lue, date_creation),
    CONSTRAINT fk_notification_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE journal_action (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    utilisateur_id   INT UNSIGNED NOT NULL,
    action           VARCHAR(50) NOT NULL,
    entite           VARCHAR(50) NOT NULL,
    entite_id        INT UNSIGNED NULL,
    ancienne_valeur  TEXT NULL,
    nouvelle_valeur  TEXT NULL,
    adresse_ip       VARCHAR(45) NULL,
    date_heure       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_journal_utilisateur (utilisateur_id, date_heure),
    KEY idx_journal_entite (entite, entite_id),
    KEY idx_journal_date (date_heure),
    CONSTRAINT fk_journal_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TRIGGER trg_journal_no_update BEFORE UPDATE ON journal_action
    FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'journal_action est en ajout seulement';
CREATE TRIGGER trg_journal_no_delete BEFORE DELETE ON journal_action
    FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'journal_action est en ajout seulement';

CREATE TABLE parametre_systeme (
    cle               VARCHAR(60) NOT NULL,
    valeur            VARCHAR(255) NOT NULL,
    description       VARCHAR(255) NULL,
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (cle)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
