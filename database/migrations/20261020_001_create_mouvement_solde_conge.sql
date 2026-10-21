

CREATE TABLE mouvement_solde_conge (
  id_mouvement        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_solde            INT UNSIGNED NOT NULL,
  id_demande_conge    INT UNSIGNED NULL,
  id_utilisateur      INT UNSIGNED NULL,
  type_mouvement      ENUM('INITIALISATION','ACQUISITION','RESERVATION','APPROBATION','LIBERATION','RESTITUTION','CORRECTION') NOT NULL,
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
  KEY idx_mouvement_utilisateur (id_utilisateur),
  CONSTRAINT fk_mouvement_solde FOREIGN KEY (id_solde)
    REFERENCES solde_conge (id_solde) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_mouvement_demande FOREIGN KEY (id_demande_conge)
    REFERENCES demande_conge (id_demande_conge) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_mouvement_utilisateur FOREIGN KEY (id_utilisateur)
    REFERENCES utilisateur (id_utilisateur) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT ck_mouvement_non_nul CHECK (
    delta_acquis <> 0 OR delta_reserve <> 0 OR delta_pris <> 0
  )
) ENGINE=InnoDB;

-- Point d'ouverture: conserve les soldes déjà présents et réserve les demandes en attente.
INSERT INTO mouvement_solde_conge (
  id_solde,
  type_mouvement,
  delta_acquis,
  delta_reserve,
  delta_pris,
  reference_operation,
  motif
)
SELECT
  s.id_solde,
  'INITIALISATION',
  s.jours_acquis,
  COALESCE(r.jours_reserves, 0),
  s.jours_pris,
  CONCAT('INIT:', s.id_solde),
  'Ouverture du journal lors de la migration'
FROM solde_conge s
LEFT JOIN (
  SELECT
    d.id_employe,
    d.id_type_conge,
    YEAR(d.date_debut) AS annee,
    SUM(d.nb_jours_ouvres) AS jours_reserves
  FROM demande_conge d
  WHERE d.statut IN ('EN_ATTENTE_AVIS', 'EN_ATTENTE_RH')
  GROUP BY d.id_employe, d.id_type_conge, YEAR(d.date_debut)
) r
  ON r.id_employe = s.id_employe
  AND r.id_type_conge = s.id_type_conge
  AND r.annee = s.annee
WHERE s.jours_acquis <> 0
   OR s.jours_pris <> 0
   OR COALESCE(r.jours_reserves, 0) <> 0
ON DUPLICATE KEY UPDATE id_solde = VALUES(id_solde);
