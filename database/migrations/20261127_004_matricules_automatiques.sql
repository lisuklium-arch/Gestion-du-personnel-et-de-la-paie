-- Domaine B · Organisation
-- Attribution transactionnelle et conservation définitive des matricules.

START TRANSACTION;

CREATE TABLE historique_matricule (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    employe_id          INT UNSIGNED NOT NULL,
    matricule           VARCHAR(20) NOT NULL,
    type_action         ENUM('attribution_initiale','correction_exceptionnelle') NOT NULL,
    ancien_matricule    VARCHAR(20) NULL,
    effectue_par_id     INT UNSIGNED NULL,
    date_action         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_historique_matricule (matricule),
    KEY idx_historique_matricule_employe (employe_id, date_action),
    CONSTRAINT fk_historique_matricule_employe FOREIGN KEY (employe_id)
        REFERENCES employe (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_historique_matricule_auteur FOREIGN KEY (effectue_par_id)
        REFERENCES utilisateur (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Tous les matricules déjà présents sont réservés, y compris ceux des dossiers sortis.
INSERT INTO historique_matricule (employe_id, matricule, type_action)
SELECT id, matricule, 'attribution_initiale' FROM employe;

CREATE TABLE compteur_matricule (
    id                  TINYINT UNSIGNED NOT NULL,
    prochain_numero     SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT ck_compteur_matricule_id CHECK (id = 1),
    CONSTRAINT ck_compteur_matricule_plage CHECK (prochain_numero BETWEEN 1 AND 10000)
) ENGINE=InnoDB;

-- Recherche le premier numéro non réservé de la plage EMP-0001 à EMP-9999.
INSERT INTO compteur_matricule (id, prochain_numero)
SELECT 1, COALESCE(MIN(n.numero), 10000)
FROM (
    SELECT u.n + d.n * 10 + c.n * 100 + m.n * 1000 + 1 AS numero
    FROM (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) u
    CROSS JOIN (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) d
    CROSS JOIN (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) c
    CROSS JOIN (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) m
) n
LEFT JOIN historique_matricule h ON h.matricule = CONCAT('EMP-', LPAD(n.numero, 4, '0'))
WHERE h.id IS NULL;

COMMIT;
