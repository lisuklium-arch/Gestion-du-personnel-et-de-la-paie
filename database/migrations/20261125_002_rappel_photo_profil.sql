-- Domaine B · Photo de profil : suivi des rappels pour les anciens dossiers.
START TRANSACTION;

CREATE TABLE rappel_photo_profil (
    employe_id           INT UNSIGNED NOT NULL,
    derniere_notification_le DATETIME NULL,
    masque_jusqua        DATETIME NULL,
    complete_le          DATETIME NULL,
    PRIMARY KEY (employe_id),
    CONSTRAINT fk_rappel_photo_employe FOREIGN KEY (employe_id)
        REFERENCES employe (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO rappel_photo_profil (employe_id)
SELECT id FROM employe WHERE photo_chemin IS NULL;

COMMIT;
