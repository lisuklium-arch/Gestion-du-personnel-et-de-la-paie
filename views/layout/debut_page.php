<!DOCTYPE html>
<html lang="fr">
<head>
  <?php include __DIR__ . '/head_page.php'; ?>
</head>
<body>
  <div class="app-shell">
    <?php include __DIR__ . "/navbar_{$espace}.php"; ?>

    <div class="app-main">
      <header class="app-header">
        <div class="app-header__heading">
          <h1 class="app-header__title"><?= h($titrePage ?? $pageTitle) ?></h1>
          <p class="app-header__sub"><?= $sousTitre ?></p>
        </div>
        <?= $actionEntete ?? '' ?>
        <button type="button" class="icon-btn" aria-label="Notifications"><i class="fa-regular fa-bell" aria-hidden="true"></i></button>
      </header>

      <main class="app-content">
