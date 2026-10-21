# Gestion du personnel et de la paie — Projet 8

Application web PHP 8 / MySQL (MariaDB) / Bootstrap 5, architecture MVC.
**Avancement : étape 1 (base de données), étape 2 (socle MVC, sécurité, connexion par profil) et étape 3 (employés : recherche instantanée AJAX, création, dossier, modification) et étape 4 (départements avec désignation du chef, postes, contrats).**

## Prérequis
- PHP **8.1 ou plus** (`php -v`) avec les extensions `mysql` et `mbstring`
- MariaDB ou MySQL

```
sudo apt install php php-mysql php-mbstring mariadb-server unzip
```

## Installation (à faire une seule fois)
Depuis le dossier du projet :

```
sudo mysql < database/schema.sql                      # crée la base et les 23 tables
sudo mysql personnel_paie < database/seed.sql         # données de test
sudo mysql < database/utilisateur_application.sql     # compte MySQL de l'application (droits limités)
cp .env.example .env                                  # réglages locaux (jamais envoyés sur Git)
```

## Lancer l'application
```
php -S localhost:8000 -t public
```
Ouvre ensuite http://localhost:8000 dans le navigateur (Ctrl+C dans le terminal pour arrêter).

## Comptes de test (mot de passe de tous : `Paie@2026`)
| Profil | E-mail |
|---|---|
| Employé | p.mballa@organisation.cm |
| Chef de département | d.kouam@organisation.cm |
| Responsable RH | n.ebogo@organisation.cm |
| Directeur général | d.mbarga@organisation.cm |

## Structure
```
public/            seul dossier accessible depuis le navigateur (index.php + assets)
app/
  bootstrap.php    démarrage : chemins, chargement des classes, configuration, session
  config/          config.php (réglages), routes.php (adresses), menu.php (menus par profil)
  core/            Router, Auth, Csrf, Session, Database (PDO), Config, helpers
  controllers/     un contrôleur par thème (AuthController, DashboardController…)
  models/          accès à la base (Utilisateur, Journal…)
  views/           pages HTML (layouts/, auth/, dashboard/, errors/)
database/          scripts SQL
storage/           journaux d'erreurs et documents (hors du dossier public)
```
Chaque requête passe par `public/index.php` → `Router` (connexion, profil, jeton CSRF) → Contrôleur → Modèle → Vue.

## Sécurité déjà en place
- **Mots de passe** : `password_hash` / `password_verify` ; jamais stockés ni affichés en clair.
- **Injection SQL** : uniquement des requêtes préparées PDO (`Database.php`).
- **XSS** : tout affichage passe par `e()` ; en plus, une politique CSP n'autorise que les fichiers du site (pas de script ni de style écrit directement dans les pages).
- **CSRF** : jeton secret dans chaque formulaire, vérifié automatiquement pour tout POST.
- **Session** : cookie HttpOnly + SameSite, nouvel identifiant à la connexion, déconnexion après 30 min d'inactivité.
- **Connexion** : message d'erreur identique pour e-mail inconnu ou mot de passe faux ; blocage 15 min après 5 échecs ; échecs et connexions écrits dans `journal_action`.
- **Droits** : chaque page déclare les profils autorisés (`config/routes.php`) ; réponse 403 sinon.
- **Erreurs** : aucun détail technique affiché hors développement (`APP_DEBUG=false` en production).

## À savoir
- Le chef de département et le responsable RH ont aussi accès à l'espace employé ; le directeur général n'a que ses pages (comme dans le diagramme de cas d'utilisation).
- Les entrées de menu marquées « bientôt » sont les pages des prochaines étapes.
