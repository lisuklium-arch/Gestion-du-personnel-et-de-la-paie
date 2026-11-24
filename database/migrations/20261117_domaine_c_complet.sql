-- Domaine C — complément (ADDITIF)
USE projet8_rh_paie;
SET NAMES utf8mb4;

-- Statuts élargis (avis chef reste consultatif via validation_conge)
ALTER TABLE demande_conge
  MODIFY COLUMN statut ENUM(
    'brouillon',
    'en_attente_chef',
    'en_attente_rh',
    'en_attente_dg',
    'approuvee',
    'refusee',
    'annulee',
    'en_cours',
    'terminee'
  ) NOT NULL DEFAULT 'en_attente_chef';

-- Report de solde
ALTER TABLE solde_conge
  ADD COLUMN jours_reportes DECIMAL(4,1) NOT NULL DEFAULT 0 AFTER jours_pris;

-- Type de congé : catégorie payé / description déjà possible
ALTER TABLE type_conge
  ADD COLUMN categorie ENUM('paye','non_paye','special') NOT NULL DEFAULT 'paye' AFTER libelle,
  ADD COLUMN fractionnable TINYINT(1) NOT NULL DEFAULT 1 AFTER preavis_jours,
  ADD COLUMN reportable TINYINT(1) NOT NULL DEFAULT 0 AFTER fractionnable;

INSERT INTO parametre_systeme (cle, valeur, description) VALUES
  ('conge_preavis_min_jours', '2', 'Préavis minimal (jours calendaires)'),
  ('conge_duree_max_jours', '30', 'Durée max d''une demande (jours ouvrés), 0=illimité'),
  ('conge_max_demandes_ouvertes', '3', 'Max demandes non closes par employé'),
  ('conge_autoriser_retroactif', '0', '1=autoriser début déjà passé'),
  ('conge_jours_ouvrables', '1,2,3,4,5', 'Jours de semaine ouvrés (1=lundi … 7=dimanche)')
ON DUPLICATE KEY UPDATE description = VALUES(description);
