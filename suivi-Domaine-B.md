# Suivi — Domaine B : Organisation

## Périmètre autorisé

Le Domaine B couvre uniquement l'organisation et les dossiers du personnel :

- employés, départements, postes et contrats ;
- demandes de modification de profil ;
- coffre-fort documentaire et demandes d'attestation ;
- organigramme et consultation de l'équipe par un chef de département.

Sont exclus : l'authentification et les comptes (A), les congés et absences
(C), ainsi que les calculs et traitements de paie (D).
## Dépendances lues du socle

- `Utilisateur` et la session : identification de l'utilisateur et de son rôle ;
- `config/auth.php` : `exigerConnexion()` et `exigerRole()` ;
- `config/csrf.php` : protection obligatoire de tout formulaire ou endpoint
  d'écriture ajouté par B ;
- `JournalService` et `NotificationService` : traçabilité et information des
  utilisateurs, sans les modifier ;
- connexion PDO fournie par `config/init.php`.
## Interfaces fournies à C et D

Les interfaces à stabiliser dans `models/Employe.php` et `models/Contrat.php`
sont les suivantes :

- `Employe::trouver(int $id)` ;
- `Employe::equipeDuChef(int $chefId)` ;
- `Employe::getContratEnCours()` ;
- accès au taux horaire issu du contrat en cours ;
- listes de départements et de postes.

Le Domaine B ne calcule aucune donnée de congé ou de paie.
## État initial constaté

Constat effectué le 23 novembre 2026, sans modification fonctionnelle :

| Élément | État observé | Décision B |
| --- | --- | --- |
| Tables B | Les sept tables B existent déjà dans `database/schema.sql`. | Aucune migration au démarrage. Toute évolution validée ira dans `database/migrations/`, jamais dans le schéma commun. |
| Employé | `Employe::trouverParUtilisateurId()` et `Employe::creer()` existent. | Préserver les méthodes existantes ; compléter ultérieurement les interfaces promises. |
| Contrat | `Contrat::creer()` existe. | Compléter ultérieurement la lecture du contrat en cours, l'historique et le taux horaire. |
| Départements et postes | Ils sont regroupés avec les types de congé dans `Referentiel.php`. | Ne pas étendre ce modèle partagé ; créer des modèles B dédiés si nécessaire. |
| Création de dossier | Un flux existant utilise `NouvelEmployeService`, `api/compte_creer.php` et `storage/contrats`. | Ne pas le modifier dans ce volet ; l'auditer avant toute extension. |
| Écrans B | Les écrans prévus existent, plusieurs utilisent encore des données de démonstration. | Les raccorder progressivement aux modèles B, écran par écran. |
| Stockage privé | `storage/documents`, `storage/contrats` et `storage/attestations` existent. | Conserver le stockage hors de `public/` et créer des téléchargements contrôlés. |
## Décisions d’architecture

- Les écritures B passeront par des services dédiés et des endpoints protégés ;
  les pages ne contiendront pas de requêtes d'écriture.
- Les nouveaux fichiers de B auront une responsabilité unique ; aucun
  déplacement ou réécriture des fichiers relevant de A, C ou D.
- La génération des attestations Word utilisera `phpoffice/phpword`, dont
  l'ajout via Composer est autorisé lorsque cette fonctionnalité sera abordée.
- Chaque formulaire et requête asynchrone d'écriture vérifiera le rôle, le
  jeton CSRF et la propriété de la ressource.
## Journal des changements

### 23 novembre 2026 — Initialisation du suivi et inventaire B
- Objectif : fixer le périmètre strict, les dépendances et l'état initial avant
  toute évolution fonctionnelle.
- Fichiers créés/modifiés : `suivi-Domaine-B.md` uniquement.
- Tables concernées : aucune.
- Justification de périmètre : documentation et inventaire du seul Domaine B ;
  aucun comportement applicatif n'est modifié.
- Sécurité et droits : aucune surface d'accès ajoutée.
- Interfaces impactées : aucune à cette date.
- Vérifications réalisées : lecture des modèles B, du schéma, des répertoires
  de stockage, des flux de création de dossier et des écrans concernés.
- État : terminé — à valider.

## Éléments explicitement exclus

- Réécriture de `Utilisateur`, des sessions, de l'authentification ou du CSRF.
- Gestion des types de congé, demandes de congé, soldes, absences et
  validations de congé.
- Fiches de paie, rubriques, avances, heures supplémentaires et calculs de
  paie.
- Toute modification de `database/schema.sql` ou `database/demo.sql` dans ce
  volet.
## Points de coordination à valider

- Les modifications non validées déjà présentes dans l'espace de travail sont
  préservées et ne sont pas attribuées au Domaine B.
- Avant de toucher au flux existant de création de dossier, vérifier avec
  l'équipe qu'il relève bien du Domaine B et qu'aucun autre membre ne le modifie
  en parallèle.
### 23 novembre 2026 — Interfaces de lecture des employés
- Objectif : rendre disponibles les deux lectures organisationnelles promises
  aux écrans B et aux autres domaines.
- Fichiers créés/modifiés : `models/Employe.php` et `suivi-Domaine-B.md`.
- Tables concernées : lecture seule de `employe`, `departement` et `poste`.
- Justification de périmètre : les méthodes ajoutées concernent exclusivement
  la consultation d'un dossier et de l'équipe d'un chef.
- Sécurité et droits : aucun accès HTTP ou droit nouveau n'est ajouté ; les
  futurs écrans devront conserver leurs gardes de rôle avant de les appeler.
- Interfaces impactées : `Employe::trouver(int $id)` et
  `Employe::equipeDuChef(int $chefId)`. Le paramètre `chefId` est l'identifiant
  interne `employe.id`, conformément à `departement.chef_id`.
- Vérifications réalisées : requêtes préparées, lecture seule, exclusion du
  chef de sa propre équipe et exclusion des employés suspendus ou sortis.
- État : implémenté — à tester.

### 23 novembre 2026 — Diagnostic de génération Word
- Vérification réalisée : génération PhpWord exécutée avec succès après
  activation de ZIP/GD ; document `.docx` créé et demande traitée.
- Amélioration : les erreurs internes de génération sont maintenant inscrites
  dans le journal PHP, sans être exposées au navigateur.

### 24 novembre 2026 — Fondations de la hiérarchie organisationnelle
- Objectif : séparer le poste métier, la nomination de chef et le circuit
  d’approbation des modifications.
- Migration créée et appliquée :
  `database/migrations/20261124_001_organisation_hierarchie.sql`.
- Changements de base : table `poste_departement`, champ
  `demande_modification.approbateur_id`, table `demande_nomination_chef`.
- Données existantes : « Responsable RH » devient « Chargé RH » et est limité
  aux Ressources humaines ; « Comptable » est limité à Finance.
- Compatibilité : l’ancien poste « Chef de département » est conservé comme
  donnée historique non proposée aux nouvelles créations, car il est déjà lié
  aux règles de paie existantes.
- Vérifications : migration exécutée sur `projet8_rh_paie` ; deux associations
  poste/département, table de nomination et colonne d’approbateur confirmées.
- État : fondation appliquée ; écrans et services de nomination à raccorder.

### 24 novembre 2026 — Fiches réelles et affectations cohérentes
- Fiches RH et DG : les écrans lisent désormais `Employe::trouverParMatricule()`
  au lieu des tableaux fictifs ; identité, contrat et documents viennent de la
  base.
- Création de dossier : seuls les postes autorisés pour le département choisi
  sont proposés et le contrôle est répété côté serveur.
- Rôles : les nouveaux dossiers sont Employé ou RH ; « chef » n’est plus un
  rôle attribué à la création. Un compte RH doit être dans Ressources humaines.
- La proposition de nomination de chef sera raccordée au flux DG séparé dans
  l’étape suivante.

### 23 novembre 2026 — Correction de la recherche des dossiers RH
- Objectif : rétablir la recherche « Nom, matricule ou e-mail » de la liste
  des employés.
- Fichiers créés/modifiés : `models/Utilisateur.php` et `suivi-Domaine-B.md`.
- Tables concernées : lecture seule de `utilisateur`, `employe`,
  `departement`, `poste` et `contrat`.
- Justification de périmètre : l'écran RH des employés, qui appartient au
  Domaine B, repose déjà sur `Utilisateur::rechercherComptes()` fourni par le
  socle. La correction est limitée à ses paramètres SQL ; elle ne modifie ni
  les comptes ni les règles du Domaine A.
- Sécurité et droits : aucune surface d'accès ou permission n'est ajoutée.
- Interfaces impactées : le filtre `q` accepte de nouveau un ou plusieurs
  termes de recherche, sans erreur PDO.
- Vérifications réalisées : chaque occurrence SQL reçoit désormais son propre
  paramètre nommé, compatible avec les requêtes préparées PDO natives.
- État : implémenté — à tester.

### 23 novembre 2026 — Recherche dynamique des dossiers employés
- Objectif : mettre à jour la liste RH et DG pendant la saisie ou la sélection
  d'un filtre, sans recharger la page.
- Fichiers créés/modifiés : `api/employes_rechercher.php`,
  `public/js/employes-recherche.js`, `public/js/gestion.js`,
  `views/gestion/corps_employes.php`, `views/rh/employes_rh.php`,
  `views/dg/employes_dg.php` et `suivi-Domaine-B.md`.
- Tables concernées : lecture seule de `utilisateur`, `employe`,
  `departement`, `poste` et `contrat`.
- Justification de périmètre : la recherche et le filtrage des dossiers des
  employés font partie du Domaine B.
- Sécurité et droits : endpoint JSON limité aux rôles RH et DG ; les valeurs
  de filtre sont validées côté serveur et le rendu JavaScript utilise du texte
  non interprété.
- Interfaces impactées : nouvel endpoint GET `/api/employes_rechercher.php`.
- Vérifications réalisées : recherche annulable, délai de 250 ms entre les
  frappes, conservation des actions de compte après le renouvellement du tableau.
- État : implémenté — à tester.

### 23 novembre 2026 — Demandes de modification du téléphone
- Objectif : permettre à un employé de demander la modification de ses
  informations personnelles, puis à la RH de l’accepter ou le refuser.
- Fichiers créés/modifiés : `models/DemandeModification.php`,
  `services/DemandeModificationService.php`, les deux endpoints dédiés,
  les écrans employé/RH associés et leurs scripts JavaScript.
- Tables concernées : `demande_modification` et, après acceptation, les champs
  personnels concernés de `employe` ; aucune migration nécessaire.
- Justification de périmètre : cette fonctionnalité est intégralement comprise
  dans le Domaine B.
- Sécurité et droits : création limitée au dossier de l’utilisateur connecté,
  décision limitée à RH, CSRF obligatoire, transition unique depuis
  `en_attente`, transaction lors de l’acceptation et journalisation.
- Champs disponibles : nom, prénom, matricule, date de naissance et téléphone.
  E-mail et adresse ne sont pas proposés, car ils ne sont pas des champs du
  dossier employé actuel ; aucune donnée du Domaine A n’est modifiée.
- État : implémenté — à tester.

### 23 novembre 2026 — Pièces à la création d’un dossier employé
- Objectif : compléter le flux existant de création d’un dossier avec une
  photo et une pièce jointe facultatives.
- Fichiers créés/modifiés : `models/DocumentEmploye.php`,
  `models/Employe.php`, `services/NouvelEmployeService.php`,
  `api/compte_creer.php`, `views/gestion/corps_compte.php` et ce suivi.
- Tables concernées : écriture dans `employe.photo_chemin` et
  `document_employe`; le contrat initial reste enregistré dans `contrat`.
- Justification de périmètre : photo, pièce jointe et contrat initial sont des
  éléments du dossier employé du Domaine B.
- Sécurité et droits : fichiers hors de `public`, noms internes aléatoires,
  contrôle MIME côté serveur, taille maximale de 2 Mo pour la photo et 5 Mo
  pour une pièce PDF/JPG/PNG. En cas d’échec transactionnel, les fichiers déjà
  déplacés sont retirés.
- État : implémenté — à tester.

### 23 novembre 2026 — Correction du contrôle des fichiers joints
- Objectif : accepter les fichiers réellement valides même lorsque le type MIME
  temporaire fourni par PHP est imprécis.
- Fichiers modifiés : `services/NouvelEmployeService.php` et ce suivi.
- Justification de périmètre : correction du dépôt sécurisé des fichiers du
  dossier employé.
- Sécurité : image vérifiée avec `getimagesize()` et MIME interne JPEG/PNG ;
  PDF vérifié avec sa signature `%PDF-`, sans faire confiance à son extension
  ni au type annoncé par le navigateur.
- État : implémenté — à tester.

### 23 novembre 2026 — Notification de décision sur une modification
- Objectif : informer le demandeur dès qu’une RH accepte ou refuse sa demande.
- Fichiers modifiés : `models/DemandeModification.php`,
  `services/DemandeModificationService.php`, les deux endpoints associés et ce suivi.
- Justification de périmètre : notification d’une décision liée au dossier
  employé, intégralement dans le Domaine B.
- Comportement : une notification non lue est créée avec un lien vers le profil
  de l’employé, tant pour l’acceptation que pour le refus.
- État : implémenté — à tester.

### 23 novembre 2026 — Coffre-fort et attestations Word
- Objectif : offrir le téléchargement protégé des documents, les demandes
  d’attestation et la génération Word par la RH.
- Fichiers ajoutés : modèles `DocumentEmploye`/`DemandeDocument`, endpoints de
  téléchargement et de traitement, service `AttestationService`, écran RH et
  scripts associés.
- Sécurité : chemin privé vérifié par liste blanche, contrôle de propriété à
  chaque téléchargement, RH/DG autorisés, stockage hors de `public`.
- Dépendance : PhpWord 1.4 installé après activation des extensions ZIP/GD.
- État : implémenté — à tester.
