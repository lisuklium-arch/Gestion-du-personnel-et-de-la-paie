# Domaine C — Congés & absences (version complète)

## Migrations (ordre strict)

1. `20261114_domaine_c_conges_absences_compatible.sql`
2. `20261117_domaine_c_enrichissement.sql`
3. `20261117_domaine_c_complet.sql` (statuts brouillon/en_cours/terminee, report, types enrichis, paramètres)

## Fonctionnalités

### Employé
- Formulaire riche (identité auto, demi-journées, contact, relève, justificatif serveur)
- Aperçu calcul (calendaires, WE, fériés, décompte, reprise)
- Brouillon (sans réservation de solde)
- Soumission avec réservation journal
- Annulation / duplication / PDF imprimable
- Historique des décisions

### Chef
- Avis consultatif
- Absences d’équipe + justificatifs serveur
- Calendrier (existant groupe)

### RH
- Décisions de congés
- Catalogue **Types de congés** (règles par type)
- **Politiques congés** (préavis, durée max, max demandes, rétroactif…)

### DG
- Décision des congés des RH

### Moteur
- Journal immuable `mouvement_solde_conge`
- Disponible = acquis + reportés − réservé − pris
- Contrôles : solde, chevauchement, absence, préavis, politiques, anti auto-validation
- Statuts temps : approuvée → en_cours → terminee

## Hors périmètre volontaire (phase ultérieure)
- Calendriers distincts par département/employé
- Délégation automatique si chef absent
- Acquisition mensuelle automatique (batch)
- Multi-périodes fractionnées en une seule demande

## Retours métier (2026-11-23)

- **Avis chef** : uniquement si `departement.chef_id` pointe vers l’employé-chef dont `utilisateur_id` = session ; sinon la demande part directement en RH.
- **Congés RH** : statut initial `en_attente_dg` ; la RH ne peut pas décider ces demandes.
- **Date de début** : interdite si &lt; aujourd’hui (UI `min=` + contrôle serveur).
- **Heures** : colonnes `heure_debut` / `heure_fin` (migration `20261123_domaine_c_retours.sql`).

### Renseigner les chefs de département

```sql
-- Exemple : employé id=2 devient chef du département id=2
UPDATE departement SET chef_id = 2 WHERE id = 2;
```
