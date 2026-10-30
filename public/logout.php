<?php
// Déconnexion : exige un POST avec jeton CSRF (un simple lien ne peut plus déconnecter l'utilisateur).

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../services/AuthService.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfExiger();
    $auth = new AuthService(new Utilisateur($pdo), new Employe($pdo), new JournalService(new JournalAction($pdo)));
    $auth->deconnecter();

    header('Cache-Control: no-store');
    header('Location: ' . PAGE_CONNEXION);
    exit;
}

// Accès direct (GET) : page de confirmation avec un bouton POST
if (!utilisateurConnecte()) {
    header('Location: ' . PAGE_CONNEXION);
    exit;
}
header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Déconnexion — PerPay</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100">
  <form method="post" class="text-center">
    <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
    <p class="mb-3">Voulez-vous vous déconnecter ?</p>
    <button type="submit" class="btn btn-primary">Se déconnecter</button>
    <a href="<?= h(accueilPourRoleCourant() ?? PAGE_CONNEXION) ?>" class="btn btn-link">Annuler</a>
  </form>
</body>
</html>
