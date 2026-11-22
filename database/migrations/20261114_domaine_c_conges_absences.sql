-- Domaine C — Congés / Absences
-- ADDITIF uniquement : n'efface aucune table ni donnée existante.
-- À exécuter sur la base projet8_rh_paie après schema.sql + demo.sql.

USE projet8_rh_paie;
SET NAMES utf8mb4;

-- 1) Réserve explicite sur le solde (compatible avec jours_acquis / jours_pris déjà présents)
ALTER TABLE solde_conge
  ADD COLUMN IF NOT EXISTS jours_reserves DECIMAL(4,1) NOT NULL DEFAULT 0 AFTER jours_acquis;

-- MySQL < 8.0.12 n'a pas IF NOT EXISTS sur ADD COLUMN : variante sûre via procédure
-- (si la colonne existe déjà, ignorer l'erreur manuellement)

-- 2) Justificatif d'absence accessible depuis n'importe quel poste (chemin serveur)
ALTER TABLE absence
  ADD COLUMN IF NOT EXISTS justificatif_chemin VARCHAR(255) NULL AFTER motif;

-- 3) Journal immuable des mouvements de solde (source de vérité métier)
CREATE TABLE IF NOT EXISTS mouvement_solde_conge (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  solde_id            INT UNSIGNED NOT NULL,
  demande_conge_id    INT UNSIGNED NULL,
  utilisateur_id      INT UNSIGNED NOT NULL,
  type_mouvement      ENUM(
                        'ACQUISITION','RESERVATION','LIBERATION',
                        'APPROBATION','RESTITUTION','CORRECTION'
                      ) NOT NULL,
  delta_acquis        DECIMAL(5,1) NOT NULL DEFAULT 0,
  delta_reserve       DECIMAL(5,1) NOT NULL DEFAULT 0,
  delta_pris          DECIMAL(5,1) NOT NULL DEFAULT 0,
  reference_operation VARCHAR(80) NOT NULL,
  motif               VARCHAR(255) NULL,
  date_mouvement      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mouvement_reference (reference_operation),
  KEY idx_mvt_solde (solde_id, date_mouvement),
  KEY idx_mvt_demande (demande_conge_id),
  CONSTRAINT fk_mvt_solde FOREIGN KEY (solde_id)
    REFERENCES solde_conge (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_mvt_demande FOREIGN KEY (demande_conge_id)
    REFERENCES demande_conge (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_mvt_utilisateur FOREIGN KEY (utilisateur_id)
    REFERENCES utilisateur (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT ck_mvt_non_vide CHECK (
    NOT (delta_acquis = 0 AND delta_reserve = 0 AND delta_pris = 0)
  )
) ENGINE=InnoDB;

-- 4) Triggers d'immuabilité
DROP TRIGGER IF EXISTS trg_mvt_solde_no_update;
DROP TRIGGER IF EXISTS trg_mvt_solde_no_delete;

DELIMITER $$
CREATE TRIGGER trg_mvt_solde_no_update
BEFORE UPDATE ON mouvement_solde_conge
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Journal de solde immuable : UPDATE interdit';
END$$

CREATE TRIGGER trg_mvt_solde_no_delete
BEFORE DELETE ON mouvement_solde_conge
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Journal de solde immuable : DELETE interdit';
END$$
DELIMITER ;

-- 5) Backfill : une acquisition initiale par solde déjà présent (idempotent via reference)
INSERT INTO mouvement_solde_conge
  (solde_id, demande_conge_id, utilisateur_id, type_mouvement,
   delta_acquis, delta_reserve, delta_pris, reference_operation, motif, date_mouvement)
SELECT
  s.id,
  NULL,
  COALESCE(
    (SELECT u.id FROM utilisateur u WHERE u.role = 'rh' AND u.statut_compte = 'actif' ORDER BY u.id LIMIT 1),
    1
  ),
  'ACQUISITION',
  s.jours_acquis,
  0,
  0,
  CONCAT('ACQ-INIT:', s.id, ':', s.annee),
  'Reprise solde initial (migration domaine C)',
  CONCAT(s.annee, '-01-01 08:00:00')
FROM solde_conge s
WHERE s.jours_acquis > 0
  AND NOT EXISTS (
    SELECT 1 FROM mouvement_solde_conge m
    WHERE m.reference_operation = CONCAT('ACQ-INIT:', s.id, ':', s.annee)
  );

-- 6) Recalcule jours_reserves / jours_pris à partir du journal si des mouvements existent
UPDATE solde_conge s
JOIN (
  SELECT solde_id,
         COALESCE(SUM(delta_reserve), 0) AS r,
         COALESCE(SUM(delta_pris), 0) AS p,
         COALESCE(SUM(delta_acquis), 0) AS a
  FROM mouvement_solde_conge
  GROUP BY solde_id
) m ON m.solde_id = s.id
SET s.jours_reserves = GREATEST(m.r, 0),
    s.jours_pris = GREATEST(m.p, 0),
    s.jours_acquis = GREATEST(m.a, s.jours_acquis);
