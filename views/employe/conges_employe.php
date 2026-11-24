<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../models/Employe.php';
require_once __DIR__ . '/../../services/DemandeCongeService.php';
require_once __DIR__ . '/../layout/conges_helpers.php';
exigerRole(['employe', 'chef', 'rh']);

$moi = (new Employe($pdo))->trouverParUtilisateurId((int) $_SESSION['utilisateur_id']);
if ($moi === null) {
    http_response_code(404);
    exit('Aucun dossier employé associé à ce compte.');
}
$svc = new DemandeCongeService($pdo);
$svc->actualiserStatutsTemps();
$annee = (int) date('Y');
$soldes = $svc->soldesEmploye((int) $moi['id'], $annee);
$demandes = $svc->demandesEmploye((int) $moi['id']);
$flash = $_SESSION['flash_conge'] ?? null;
unset($_SESSION['flash_conge']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Congés — PerPay</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="/public/css/tokens.css">
  <link rel="stylesheet" href="/public/css/base.css">
  <link rel="stylesheet" href="/public/css/components.css">
  <link rel="stylesheet" href="/public/css/layout.css">
  <link rel="stylesheet" href="/public/css/domaine-c.css">
  <?php include __DIR__ . '/../layout/head_notifications.php'; ?>
</head>
<body>
<div class="app-shell">
  <?php $active = 'conges'; include __DIR__ . '/../layout/navbar.php'; ?>
  <div class="app-main">
    <header class="app-header">
      <div class="app-header__heading">
        <h1 class="app-header__title">Congés</h1>
      </div>
      <?php include __DIR__ . '/../layout/cloche.php'; ?>
    </header>
    <main class="app-content">
      <div class="dc-hero">
        <div>
          <h2 class="dc-hero__title">Mes congés</h2>
          <p class="dc-hero__sub"><?= h($moi['prenom'] . ' ' . $moi['nom']) ?> · Compteurs <?= $annee ?> · Circuit chef → RH (DG si RH)</p>
        </div>
        <a class="btn btn-primary" href="conges_nouvelle_demande_employe.php"><i class="fa-solid fa-plus me-2"></i>Nouvelle demande</a>
      </div>
      <?php if ($flash): ?>
        <div class="dc-alert <?= $flash['ok'] ? 'dc-alert--ok' : 'dc-alert--err' ?>"><?= h($flash['msg']) ?></div>
      <?php endif; ?>

      <section class="dc-kpis" aria-label="Soldes">
        <?php if ($soldes === []): ?>
          <article class="dc-kpi"><span class="dc-kpi__label">Solde</span><strong class="dc-kpi__value">—</strong><span class="dc-kpi__meta">Aucun solde ouvert</span></article>
        <?php else: foreach ($soldes as $s): ?>
          <article class="dc-kpi">
            <span class="dc-kpi__label"><?= h($s['libelle']) ?></span>
            <strong class="dc-kpi__value"><?= h(conge_fmt_nb($s['jours_disponibles'])) ?> j</strong>
            <span class="dc-kpi__meta">Acquis <?= h(conge_fmt_nb($s['jours_acquis'])) ?> · réservé <?= h(conge_fmt_nb($s['jours_reserves'] ?? 0)) ?> · pris <?= h(conge_fmt_nb($s['jours_pris'])) ?></span>
          </article>
        <?php endforeach; endif; ?>
      </section>

      <section class="card-panel dc-elevated">
        <div class="card-panel__head">
          <h2 class="card-panel__title">Mes demandes</h2>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle dc-table mb-0">
            <thead>
              <tr>
                <th>Type</th><th>Période</th><th>Jours</th><th>Statut</th><th></th>
              </tr>
            </thead>
            <tbody>
            <?php if ($demandes === []): ?>
              <tr><td colspan="5"><div class="dc-empty"><i class="fa-regular fa-calendar"></i>Aucune demande pour le moment.</div></td></tr>
            <?php else: foreach ($demandes as $d): [$lib, $cls] = conge_statut_badge($d['statut']); ?>
              <tr>
                <td><?= h($d['type_libelle']) ?></td>
                <td><?= h(conge_fmt_date($d['date_debut'])) ?> – <?= h(conge_fmt_date($d['date_fin'])) ?></td>
                <td><?= h(conge_fmt_nb($d['nb_jours'])) ?></td>
                <td><span class="dc-badge <?= h($cls) ?>"><?= h($lib) ?></span></td>
                <td class="text-end dc-actions">
                  <a class="btn btn-sm btn-neutral" href="conge_detail_employe.php?id=<?= (int) $d['id'] ?>">Ouvrir</a>
                  <a class="btn btn-sm btn-neutral" href="conge_pdf_employe.php?id=<?= (int) $d['id'] ?>" target="_blank">PDF</a>
                </td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
