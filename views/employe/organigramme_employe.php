<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../models/Employe.php';
require_once __DIR__ . '/../gestion/avatar.php';
exigerRole(['employe', 'chef', 'rh']);
$organigramme = (new Employe($pdo))->organigramme();
$total = array_sum(array_column($organigramme, 'effectif'));
?>
<!DOCTYPE html>
<html lang="fr"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Organigramme — PerPay</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><link rel="stylesheet" href="/public/css/tokens.css"><link rel="stylesheet" href="/public/css/base.css"><link rel="stylesheet" href="/public/css/components.css"><link rel="stylesheet" href="/public/css/layout.css"><link rel="stylesheet" href="/public/css/organigramme.css">
  <?php include __DIR__ . '/../layout/head_notifications.php'; ?>
</head><body><div class="app-shell"><?php $active = 'organigramme'; include __DIR__ . '/../layout/navbar.php'; ?><div class="app-main">
  <header class="app-header"><div class="app-header__heading"><h1 class="app-header__title">Organigramme</h1><p class="app-header__sub">Structure réelle de l’entreprise • <?= $total ?> employés actifs • <?= count($organigramme) ?> départements</p></div><?php include __DIR__ . '/../layout/cloche.php'; ?></header>
  <main class="app-content">
    <section class="card-panel"><div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></span><div><h2 class="card-panel__title">Direction générale</h2><p class="card-panel__sub">Supervision des départements</p></div></div><div class="org-root__body"><span class="avatar" aria-hidden="true">DG</span><div class="org-root__who"><p class="app-person__name">Direction générale</p><p class="app-person__meta">Compte sans dossier employé</p></div></div></section>
    <div class="org-connector" aria-hidden="true"></div><div class="org-divider"><span><?= count($organigramme) ?> départements</span></div>
    <section class="org-grid" aria-label="Départements">
      <?php foreach ($organigramme as $departement): ?>
      <article class="org-dept"><div class="org-dept__head"><span class="icon-tile icon-tile--sm"><i class="fa-solid fa-building" aria-hidden="true"></i></span><div><h3 class="org-dept__name"><?= h($departement['nom']) ?></h3><p class="org-dept__count"><?= (int) $departement['effectif'] ?> employé<?= (int) $departement['effectif'] > 1 ? 's' : '' ?></p></div></div>
      <?php if ($departement['chef_employe_id']): $chef = ['id'=>$departement['chef_employe_id'], 'prenom'=>$departement['chef_prenom'], 'nom'=>$departement['chef_nom'], 'photo_chemin'=>$departement['chef_photo']]; ?>
        <div class="org-chief"><?= avatarEmploye($chef, 'sm') ?><div><p class="org-chief__name"><?= h($departement['chef_prenom'] . ' ' . $departement['chef_nom']) ?></p><span class="badge-status badge-status--success">Chef désigné</span></div></div>
      <?php else: ?><p class="org-dept__foot">Aucun chef désigné.</p><?php endif; ?>
      <ul class="org-members"><?php foreach ($departement['membres'] as $membre): ?><li class="org-member"><?= avatarEmploye($membre, 'sm') ?><div><p class="org-member__name"><?= h($membre['prenom'] . ' ' . $membre['nom']) ?></p><p class="org-member__role"><?= h($membre['poste']) ?></p></div></li><?php endforeach; ?></ul><p class="org-dept__foot">Équipe rattachée à ce département</p></article>
      <?php endforeach; ?>
    </section><p class="org-footnote">Les chefs désignés et les effectifs sont lus directement depuis la base.</p>
  </main></div></div><?php include __DIR__ . '/../layout/navbar_mobile.php'; ?><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>
