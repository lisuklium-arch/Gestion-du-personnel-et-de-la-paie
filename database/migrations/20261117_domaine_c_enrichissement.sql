-- Domaine C — enrichissement (ADDITIF, ne supprime rien)
USE projet8_rh_paie;
SET NAMES utf8mb4;

-- Demi-journées + contact + relève + commentaire
ALTER TABLE demande_conge
  ADD COLUMN periode_debut ENUM('MATIN','APRES_MIDI') NOT NULL DEFAULT 'MATIN' AFTER date_debut,
  ADD COLUMN periode_fin   ENUM('MATIN','APRES_MIDI') NOT NULL DEFAULT 'APRES_MIDI' AFTER date_fin,
  ADD COLUMN contact_urgence VARCHAR(120) NULL AFTER motif,
  ADD COLUMN remplacant_id INT UNSIGNED NULL AFTER contact_urgence,
  ADD COLUMN date_reprise DATE NULL AFTER remplacant_id;

-- Type de congé : règles supplémentaires
ALTER TABLE type_conge
  ADD COLUMN description_type VARCHAR(255) NULL AFTER libelle,
  ADD COLUMN duree_min_jours DECIMAL(4,1) NOT NULL DEFAULT 0.5 AFTER droit_annuel_jours,
  ADD COLUMN duree_max_jours DECIMAL(4,1) NULL AFTER duree_min_jours,
  ADD COLUMN preavis_jours INT UNSIGNED NOT NULL DEFAULT 0 AFTER duree_max_jours;

-- Politiques congés (si la table parametre_systeme existe)
INSERT INTO parametre_systeme (cle, valeur, description) VALUES
  ('conge_preavis_min_jours', '2', 'Délai minimal (jours calendaires) avant le début du congé'),
  ('conge_duree_max_jours', '30', 'Durée maximale d''une demande (jours ouvrés), 0 = illimité'),
  ('conge_max_demandes_ouvertes', '3', 'Nombre max de demandes non terminées par employé'),
  ('conge_autoriser_retroactif', '0', '1 = autoriser une date de début déjà passée')
ON DUPLICATE KEY UPDATE description = VALUES(description);
