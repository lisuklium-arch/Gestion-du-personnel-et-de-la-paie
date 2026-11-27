-- Domaine B · Organisation
-- Ajoute le lieu de résidence officiel et le motif de refus des demandes.
-- Les deux colonnes restent nullables afin de préserver les dossiers existants.

START TRANSACTION;

ALTER TABLE employe
    ADD COLUMN lieu_residence VARCHAR(255) NULL AFTER telephone;

ALTER TABLE demande_modification
    ADD COLUMN motif_rejet VARCHAR(255) NULL AFTER motif;

COMMIT;
