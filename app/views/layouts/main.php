<?php
/** Mise en page de l'application connectée : menu latéral selon le profil. Variables : $content, $flash, $user */
$menu = (require APP_PATH . '/config/menu.php')[$user['role']] ?? [];
$actif = request_path();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titre ?? \App\Core\Config::get('app.name')) ?> · <?= e(\App\Core\Config::get('app.name')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<div class="app-shell">
    <aside class="offcanvas-lg offcanvas-start app-sidebar" tabindex="-1" id="menu" aria-label="Menu principal">
        <div class="offcanvas-header d-lg-none">
            <span class="fw-bold text-white">Menu</span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#menu" aria-label="Fermer le menu"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column p-3">
            <div class="app-brand">
                <?= e(\App\Core\Config::get('app.name')) ?>
                <small><?= e(role_label($user['role'])) ?></small>
            </div>
            <nav class="flex-grow-1" aria-label="Navigation">
                <?php foreach ($menu as $section): ?>
                    <div class="menu-title"><?= e($section['titre']) ?></div>
                    <ul class="nav flex-column gap-1">
                        <?php foreach ($section['items'] as [$libelle, $chemin, $pret]): ?>
                            <li class="nav-item">
                                <?php if ($pret): ?>
                                    <a class="nav-link<?= $actif === $chemin ? ' active' : '' ?>" href="<?= e(url($chemin)) ?>"<?= $actif === $chemin ? ' aria-current="page"' : '' ?>><?= e($libelle) ?></a>
                                <?php else: ?>
                                    <span class="nav-link disabled" aria-disabled="true"><?= e($libelle) ?> <span class="badge text-bg-secondary ms-auto">bientôt</span></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            </nav>
            <div class="app-user">
                <div class="fw-semibold"><?= e($user['prenom'] . ' ' . $user['nom']) ?></div>
                <div class="small mb-2"><?= e($user['email']) ?></div>
                <form method="post" action="<?= e(url('/logout')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-light w-100">Se déconnecter</button>
                </form>
            </div>
        </div>
    </aside>

    <div class="app-main">
        <header class="app-topbar d-lg-none">
            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="offcanvas" data-bs-target="#menu" aria-controls="menu">Menu</button>
            <span class="fw-bold"><?= e(role_label($user['role'])) ?></span>
        </header>
        <div class="app-content">
            <?php foreach ($flash as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?>" role="alert"><?= e($f['message']) ?></div>
            <?php endforeach; ?>
            <?= $content ?>
        </div>
    </div>
</div>
<script src="<?= e(asset('vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
