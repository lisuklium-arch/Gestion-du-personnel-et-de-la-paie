# Domaine C — Congés & absences

## Intégration dans le projet de groupe

- **Aucune table existante n’a été supprimée.**
- Ajouts SQL : `database/migrations/20261114_domaine_c_conges_absences_compatible.sql`
  - colonne `solde_conge.jours_reserves`
  - colonne `absence.justificatif_chemin`
  - table `mouvement_solde_conge` + triggers d’immuabilité
  - backfill ACQUISITION depuis les soldes déjà présents

## Justificatifs (multi-poste)

Les fichiers sont enregistrés sous `storage/justificatifs/{conges|absences}/` sur le **serveur**.
Téléchargement via `/public/telecharger_justificatif.php` (contrôle session + droits).
Le dossier `storage/` est protégé par `.htaccess` (pas d’accès HTTP direct).

## Circuit

1. Employé dépose → réservation journal si type à décompte
2. Chef : avis consultatif → statut `en_attente_rh`
3. RH : décision (si demandeur = RH et approbation → `en_attente_dg`)
4. DG : décision sur congés des RH
5. Annulation employé (tant que non tranchée) → libération

## Contrats pour la paie

- `AbsenceService::nonJustifieesDuMois($employeId, $mois, $annee)`
- Solde disponible = `jours_acquis - jours_reserves - jours_pris` (colonnes tenues à jour depuis le journal)
