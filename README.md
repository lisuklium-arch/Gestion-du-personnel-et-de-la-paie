# Gestion du personnel et de la paie
Projet du Groupe 7

## Configuration locale

1. Copiez `.env.example` en `.env` et adaptez les identifiants MySQL à votre installation. L'application MVC utilise `DB_*`; les anciennes pages et API utilisent `LEGACY_DB_*`.
2. Créez la base et ses tables avant le premier démarrage. Pour le compte applicatif MVC, exécutez `database/utilisateur_application.sql` avec un compte MySQL administrateur sur le port configuré dans `DB_PORT`. Le fichier accorde l'accès à `personnel_paie` et utilise le mot de passe de développement indiqué dans `.env.example`; changez-le pour toute installation partagée.
3. Lancez PerPay depuis la racine du projet avec `php -S 127.0.0.1:8000 -t . dev-server-router.php`, puis ouvrez `http://127.0.0.1:8000`. La racine redirige vers `/public/login.php`; le routeur de développement sert les API et vues nécessaires sans exposer `.env` ni les autres fichiers internes.

Si MySQL refuse `root`, ne remplacez pas son mot de passe par une valeur devinée : renseignez le vrai mot de passe dans `LEGACY_DB_PASS` (ou créez un compte applicatif dédié) et vérifiez que `LEGACY_DB_HOST` et `LEGACY_DB_PORT` pointent vers le bon serveur.
