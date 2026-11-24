# Domaine C — priorités livrées

## Fonctionnalités
1. Ouverture d’année + report de reliquat (`SoldeAnneeService`, vue RH)
2. Pilotage RH (KPI, mois, alertes chevauchement, justificatifs en retard)
3. Notifications de circuit (dépôt, avis, décision)
4. Historique journal + correction RH contrôlée
5. Export CSV des demandes
6. Calendrier d’équipe (existant + données pilotage)

## Migrations requises (ordre)
- 20261114 compatible
- 20261117 enrichissement
- 20261117 complet
- 20261123 retours

Aucun schéma de base écrasé : uniquement ADD / services / vues.
