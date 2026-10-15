<!DOCTYPE html>
<html lang="fr">
<head>
  <?php include __DIR__ . '/head_cd.php'; ?>
</head>
<body>
  <div class="app-shell">
    <?php include __DIR__ . '/navbar_cd.php'; ?>

    <div class="app-main">
      <header class="app-header">
        <div class="app-header__heading">
          <h1 class="app-header__title"><?= h($pageTitle) ?></h1>
          <p class="app-header__sub"><?= $sousTitre ?></p>
        </div>
        <button type="button" class="icon-btn" aria-label="Notifications"><i class="fa-regular fa-bell" aria-hidden="true"></i></button>
      </header>

      <main class="app-content">
