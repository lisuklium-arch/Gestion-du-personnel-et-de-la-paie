USE projet8_rh_paie;
SET NAMES utf8mb4;

-- Heures de prise de congé (optionnelles)
ALTER TABLE demande_conge
  ADD COLUMN heure_debut TIME NULL AFTER periode_debut,
  ADD COLUMN heure_fin TIME NULL AFTER periode_fin;

-- Forcer politique : pas de rétroactif par défaut (si ligne absente, l'appli bloque déjà)
INSERT INTO parametre_systeme (cle, valeur, description) VALUES
  ('conge_autoriser_retroactif', '0', '0 = date de début >= aujourd''hui obligatoire')
ON DUPLICATE KEY UPDATE valeur = '0', description = VALUES(description);
