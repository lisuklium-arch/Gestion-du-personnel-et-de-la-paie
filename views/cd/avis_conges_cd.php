<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../services/DemandeCongeService.php';
require_once __DIR__ . '/../layout/conges_helpers.php';
exigerRole(['chef']);

$svc = new DemandeCongeService($pdo);
$liste = $svc->fileChef((int) $_SESSION['utilisateur_id']);
$flash = $_SESSION['flash_conge'] ?? null;
unset($_SESSION['flash_conge']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Avis congés — PerPay</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="/public/css/tokens.css">
  <link rel="stylesheet" href="/public/css/base.css">
  <link rel="stylesheet" href="/public/css/components.css">
  <link rel="stylesheet" href="/public/css/layout.css">
  <link rel="stylesheet" href="/public/css/domaine-c.css">
  <?php include __DIR__ . '/../layout/head_cd.php'; ?>
</head>
<body>
<div class="app-shell">
  <?php $active = 'avis'; include __DIR__ . '/../layout/navbar_cd.php'; ?>
  <div class="app-main">
    <header class="app-header">
      <div class="app-header__heading">
        <h1 class="app-header__title">Avis sur les congés</h1>
        <p class="app-header__sub">Réservé au chef du département des demandeurs · avis consultatif avant décision RH</p>
      </div>
      <?php include __DIR__ . '/../layout/cloche.php'; ?>
    </header>
    <main class="app-content">
      <?php if ($flash): ?><div class="dc-alert <?= $flash['ok']?'dc-alert--ok':'dc-alert--err' ?>"><?= h($flash['msg']) ?></div><?php endif; ?>
      <section class="card-panel">
        <div class="table-responsive">
          <table class="table table-hover align-middle dc-table mb-0">
            <thead><tr><th>Employé</th><th>Type</th><th>Période</th><th>Jours</th><th>Statut</th><th></th></tr></thead>
            <tbody>
            <?php if ($liste===[]): ?><tr><td colspan="6" class="text-muted">Aucune demande.</td></tr>
            <?php else: foreach ($liste as $d): [$lib,$cls]=conge_statut_badge($d['statut']); ?>
              <tr>
                <td><strong><?= h($d['prenom'].' '.$d['nom']) ?></strong><div class="small text-muted"><?= h($d['matricule']) ?></div></td>
                <td><?= h($d['type_libelle']) ?></td>
                <td><?= h(conge_fmt_date($d['date_debut'])) ?> – <?= h(conge_fmt_date($d['date_fin'])) ?></td>
                <td><?= h(conge_fmt_nb($d['nb_jours'])) ?></td>
                <td><span class="dc-badge <?= h($cls) ?>"><?= h($lib) ?></span></td>
                <td class="text-end"><a class="btn btn-sm btn-primary" href="avis_conge_detail_cd.php?id=<?= (int)$d['id'] ?>"><?= $d['statut']==='en_attente_chef'?'Donner un avis':'Consulter' ?></a></td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</div>
</body>
</html>
