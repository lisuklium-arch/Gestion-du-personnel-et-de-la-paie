-- Domaine B · Organisation
-- Règles introduites :
-- 1. un poste ne peut être proposé que dans ses départements autorisés ;
-- 2. un chef de département est une nomination, jamais un simple poste ;
-- 3. les demandes de modification peuvent être routées vers un approbateur nommé ;
-- 4. les nominations de chef sont décidées par le DG.

START TRANSACTION;

CREATE TABLE poste_departement (
    poste_id        INT UNSIGNED NOT NULL,
    departement_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (poste_id, departement_id),
    KEY idx_poste_departement_departement (departement_id),
    CONSTRAINT fk_poste_departement_poste FOREIGN KEY (poste_id)
        REFERENCES poste (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_poste_departement_departement FOREIGN KEY (departement_id)
        REFERENCES departement (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Les deux postes métier de démonstration sont rattachés à leur service réel.
-- « Chef de département » (poste historique id 2) reste intact afin de ne pas
-- casser les données D existantes ; il n'est volontairement pas autorisé dans
-- poste_departement et ne sera plus proposé pour une nouvelle création.
UPDATE poste SET intitule = 'Chargé RH' WHERE id = 1 AND intitule = 'Responsable RH';
INSERT INTO poste_departement (poste_id, departement_id) VALUES
    (1, 1),
    (3, 2);

ALTER TABLE demande_modification
    ADD COLUMN approbateur_id INT UNSIGNED NULL AFTER traite_par_id,
    ADD KEY idx_demmodif_approbateur (approbateur_id),
    ADD CONSTRAINT fk_demmodif_approbateur FOREIGN KEY (approbateur_id)
        REFERENCES utilisateur (id) ON DELETE SET NULL ON UPDATE CASCADE;

CREATE TABLE demande_nomination_chef (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    employe_id        INT UNSIGNED NOT NULL,
    departement_id    INT UNSIGNED NOT NULL,
    demande_par_id    INT UNSIGNED NOT NULL,
    statut            ENUM('en_attente','acceptee','refusee','annulee') NOT NULL DEFAULT 'en_attente',
    traite_par_id     INT UNSIGNED NULL,
    date_demande      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_traitement   DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_nomination_employe (employe_id, statut),
    KEY idx_nomination_departement (departement_id, statut),
    KEY idx_nomination_demandeur (demande_par_id),
    KEY idx_nomination_traite_par (traite_par_id),
    CONSTRAINT fk_nomination_employe FOREIGN KEY (employe_id)
        REFERENCES employe (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_nomination_departement FOREIGN KEY (departement_id)
        REFERENCES departement (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_nomination_demandeur FOREIGN KEY (demande_par_id)
        REFERENCES utilisateur (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_nomination_traite_par FOREIGN KEY (traite_par_id)
        REFERENCES utilisateur (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

COMMIT;
