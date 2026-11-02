-- Mise à niveau d'une base créée avec l'ancien schéma (colonnes jours_acquis / jours_pris, table presence).
-- À lancer une fois : mysql personnel_paie < database/migrations/20261029_001_journal_solde_et_absences.sql
-- Ne pas lancer à la place de schema.sql sur une installation neuve.

USE personnel_paie;

DROP PROCEDURE IF EXISTS upgrade_journal_solde;

DELIMITER $$
CREATE PROCEDURE upgrade_journal_solde()
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'solde_conge' AND COLUMN_NAME = 'jours_acquis'
  ) THEN
    UPDATE employe SET statut = 'ACTIF' WHERE statut = 'EN_CONGE';
    ALTER TABLE employe MODIFY statut ENUM('ACTIF','SUSPENDU','SORTI') NOT NULL DEFAULT 'ACTIF';

    IF NOT EXISTS (
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'type_conge' AND COLUMN_NAME = 'code'
    ) THEN
      ALTER TABLE type_conge
        ADD code VARCHAR(30) NULL AFTER id_type_conge,
        ADD decompte_solde TINYINT(1) NOT NULL DEFAULT 1 AFTER remunere,
        ADD actif TINYINT(1) NOT NULL DEFAULT 1 AFTER justificatif_requis;
    END IF;

    UPDATE type_conge SET code = 'ANNUEL', decompte_solde = 1 WHERE libelle LIKE '%nnuel%';
    UPDATE type_conge SET code = 'MALADIE', decompte_solde = 0, jours_par_an = 0 WHERE libelle LIKE '%aladie%';
    UPDATE type_conge SET code = 'SANS_SOLDE', decompte_solde = 0 WHERE libelle LIKE '%ans solde%';
    UPDATE type_conge SET code = CONCAT('TYPE_', id_type_conge) WHERE code IS NULL OR code = '';
    ALTER TABLE type_conge MODIFY code VARCHAR(30) NOT NULL;
    IF NOT EXISTS (
      SELECT 1 FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'type_conge' AND INDEX_NAME = 'uq_type_conge_code'
    ) THEN
      ALTER TABLE type_conge ADD UNIQUE KEY uq_type_conge_code (code);
    END IF;

    CREATE TABLE IF NOT EXISTS jour_ferie (
      id_jour_ferie INT UNSIGNED NOT NULL AUTO_INCREMENT,
      date_ferie    DATE NOT NULL,
      libelle       VARCHAR(100) NOT NULL,
      recurrent     TINYINT(1) NOT NULL DEFAULT 0,
      PRIMARY KEY (id_jour_ferie),
      UNIQUE KEY uq_jour_ferie_date (date_ferie)
    ) ENGINE=InnoDB;

    ALTER TABLE demande_conge
      MODIFY statut ENUM('EN_ATTENTE_AVIS','EN_ATTENTE_RH','EN_ATTENTE_DG','APPROUVE','REFUSE','ANNULE') NOT NULL DEFAULT 'EN_ATTENTE_AVIS';
    IF NOT EXISTS (
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'demande_conge' AND COLUMN_NAME = 'periode_debut'
    ) THEN
      ALTER TABLE demande_conge
        ADD periode_debut ENUM('MATIN','APRES_MIDI') NOT NULL DEFAULT 'MATIN' AFTER date_fin,
        ADD periode_fin ENUM('MATIN','APRES_MIDI') NOT NULL DEFAULT 'APRES_MIDI' AFTER periode_debut;
    END IF;

    CREATE TABLE IF NOT EXISTS mouvement_solde_conge (
      id_mouvement        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      id_solde            INT UNSIGNED NOT NULL,
      id_demande_conge    INT UNSIGNED NULL,
      id_utilisateur      INT UNSIGNED NULL,
      type_mouvement      ENUM('INITIALISATION','ACQUISITION','RESERVATION','LIBERATION','APPROBATION','RESTITUTION','CORRECTION') NOT NULL,
      delta_acquis        DECIMAL(5,1) NOT NULL DEFAULT 0,
      delta_reserve       DECIMAL(5,1) NOT NULL DEFAULT 0,
      delta_pris          DECIMAL(5,1) NOT NULL DEFAULT 0,
      reference_operation VARCHAR(100) NOT NULL,
      motif               VARCHAR(255) NOT NULL,
      date_mouvement      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id_mouvement),
      UNIQUE KEY uq_mouvement_reference (reference_operation),
      KEY idx_mouvement_solde_date (id_solde, date_mouvement, id_mouvement),
      KEY idx_mouvement_demande (id_demande_conge),
      CONSTRAINT fk_mouvement_solde FOREIGN KEY (id_solde) REFERENCES solde_conge (id_solde),
      CONSTRAINT fk_mouvement_demande FOREIGN KEY (id_demande_conge) REFERENCES demande_conge (id_demande_conge),
      CONSTRAINT fk_mouvement_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ) ENGINE=InnoDB;

    INSERT INTO mouvement_solde_conge (
      id_solde, type_mouvement, delta_acquis, delta_reserve, delta_pris, reference_operation, motif
    )
    SELECT
      s.id_solde, 'INITIALISATION', s.jours_acquis, COALESCE(r.jours_reserves, 0), s.jours_pris,
      CONCAT('INIT:', s.id_solde), 'Ouverture du journal lors de la migration'
    FROM solde_conge s
    LEFT JOIN (
      SELECT d.id_employe, d.id_type_conge, YEAR(d.date_debut) AS annee, SUM(d.nb_jours_ouvres) AS jours_reserves
      FROM demande_conge d
      JOIN type_conge t ON t.id_type_conge = d.id_type_conge
      WHERE d.statut IN ('EN_ATTENTE_AVIS', 'EN_ATTENTE_RH', 'EN_ATTENTE_DG') AND t.decompte_solde = 1
      GROUP BY d.id_employe, d.id_type_conge, YEAR(d.date_debut)
    ) r ON r.id_employe = s.id_employe AND r.id_type_conge = s.id_type_conge AND r.annee = s.annee
    WHERE NOT EXISTS (SELECT 1 FROM mouvement_solde_conge m WHERE m.id_solde = s.id_solde)
      AND (s.jours_acquis <> 0 OR s.jours_pris <> 0 OR COALESCE(r.jours_reserves, 0) <> 0);

    ALTER TABLE solde_conge DROP CHECK ck_solde;
    ALTER TABLE solde_conge DROP COLUMN jours_acquis, DROP COLUMN jours_pris;

    CREATE TABLE IF NOT EXISTS absence (
      id_absence           INT UNSIGNED NOT NULL AUTO_INCREMENT,
      id_employe           INT UNSIGNED NOT NULL,
      date_absence         DATE NOT NULL,
      motif                VARCHAR(255) NULL,
      justifiee            TINYINT(1) NOT NULL DEFAULT 0,
      statut_justificatif  ENUM('AUCUN','DEPOSE','ACCEPTE','REFUSE') NOT NULL DEFAULT 'AUCUN',
      justificatif         VARCHAR(255) NULL,
      id_demande_conge     INT UNSIGNED NULL,
      id_auteur            INT UNSIGNED NULL,
      date_marquage        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id_absence),
      UNIQUE KEY uq_absence (id_employe, date_absence),
      CONSTRAINT fk_absence_employe FOREIGN KEY (id_employe) REFERENCES employe (id_employe),
      CONSTRAINT fk_absence_demande FOREIGN KEY (id_demande_conge) REFERENCES demande_conge (id_demande_conge),
      CONSTRAINT fk_absence_auteur FOREIGN KEY (id_auteur) REFERENCES utilisateur (id_utilisateur)
    ) ENGINE=InnoDB;

    IF EXISTS (
      SELECT 1 FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'presence'
    ) THEN
      INSERT INTO absence (id_employe, date_absence, motif, justifiee, statut_justificatif)
      SELECT p.id_employe, p.date_presence, p.motif,
             IF(p.statut = 'JUSTIFIE', 1, 0),
             IF(p.statut = 'JUSTIFIE', 'ACCEPTE', 'AUCUN')
      FROM presence p
      WHERE p.statut IN ('ABSENT', 'JUSTIFIE')
        AND NOT EXISTS (
          SELECT 1 FROM absence a WHERE a.id_employe = p.id_employe AND a.date_absence = p.date_presence
        );
      DROP TABLE presence;
    END IF;

    ALTER TABLE validation DROP FOREIGN KEY fk_validation_valideur;
    UPDATE validation v
      JOIN employe e ON e.id_employe = v.id_valideur
      SET v.id_valideur = e.id_utilisateur;
    ALTER TABLE validation
      MODIFY etape ENUM('AVIS_CHEF','DECISION_RH','DECISION_DG') NOT NULL;
    ALTER TABLE validation
      ADD CONSTRAINT fk_validation_valideur FOREIGN KEY (id_valideur) REFERENCES utilisateur (id_utilisateur);
  END IF;
END$$
DELIMITER ;

CALL upgrade_journal_solde();
DROP PROCEDURE upgrade_journal_solde;

INSERT IGNORE INTO jour_ferie (date_ferie, libelle, recurrent) VALUES
  ('2026-01-01', 'Jour de l''an', 1),
  ('2026-02-11', 'Fête de la jeunesse', 1),
  ('2026-04-03', 'Vendredi saint', 0),
  ('2026-04-06', 'Lundi de Pâques', 0),
  ('2026-05-01', 'Fête du travail', 1),
  ('2026-05-14', 'Ascension', 0),
  ('2026-05-20', 'Fête nationale', 1),
  ('2026-08-15', 'Assomption', 1),
  ('2026-12-25', 'Noël', 1);

DROP TRIGGER IF EXISTS trg_mouvement_solde_immuable_update;
DROP TRIGGER IF EXISTS trg_mouvement_solde_immuable_delete;

DELIMITER $$
CREATE TRIGGER trg_mouvement_solde_immuable_update
BEFORE UPDATE ON mouvement_solde_conge
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Journal des soldes immuable : correction par contre-passation uniquement.';
END$$
CREATE TRIGGER trg_mouvement_solde_immuable_delete
BEFORE DELETE ON mouvement_solde_conge
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Journal des soldes immuable : suppression interdite.';
END$$
DELIMITER ;
