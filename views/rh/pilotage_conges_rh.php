<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../services/CongesPilotageService.php';
require_once __DIR__ . '/../layout/conges_helpers.php';
exigerRole(['rh']);
$svc = new CongesPilotageService($pdo);
$kpis = $svc->kpis();
$etapes = $svc->fileParEtape();
$retard = $svc->absencesJustificatifEnRetard(3);
$mois = (int) ($_GET['mois'] ?? date('n'));
$annee = (int) ($_GET['annee'] ?? date('Y'));
$ev = $svc->evenementsMois($annee, $mois, null);
$alertes = $svc->alertesChevauchementEquipe($ev['debut'], $ev['fin'], 2);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pilotage congés — RH</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="/public/css/tokens.css">
  <link rel="stylesheet" href="/public/css/base.css">
  <link rel="stylesheet" href="/public/css/components.css">
  <link rel="stylesheet" href="/public/css/layout.css">
  <link rel="stylesheet" href="/public/css/domaine-c.css">
  <?php include __DIR__ . '/../layout/head_page.php'; ?>
</head>
<body>
<div class="app-shell">
  <?php $active = 'rh_pilotage_conges'; include __DIR__ . '/../layout/navbar_rh.php'; ?>
  <div class="app-main">
    <header class="app-header">
      <div class="app-header__heading">
        <h1 class="app-header__title">Pilotage congés & absences</h1>
        <p class="app-header__sub">Indicateurs, calendrier global, alertes d’effectif</p>
      </div>
      <?php include __DIR__ . '/../layout/cloche.php'; ?>
    </header>
    <main class="app-content">
      <div class="dc-kpis">
        <div class="dc-kpi"><span class="dc-kpi__label">En attente</span><span class="dc-kpi__value"><?= (int)$kpis['en_attente'] ?></span></div>
        <div class="dc-kpi"><span class="dc-kpi__label">Approuvées (mois)</span><span class="dc-kpi__value"><?= (int)$kpis['approuvees_mois'] ?></span></div>
        <div class="dc-kpi"><span class="dc-kpi__label">Abs. non just. (mois)</span><span class="dc-kpi__value"><?= (int)$kpis['absences_non_justifiees_mois'] ?></span></div>
        <div class="dc-kpi"><span class="dc-kpi__label">Départs ≤ 30 j.</span><span class="dc-kpi__value"><?= (int)$kpis['departs_30j'] ?></span></div>
      </div>

      <div class="dc-actions mb-3">
        <a class="btn btn-neutral" href="soldes_annee_rh.php">Soldes / année</a>
        <a class="btn btn-neutral" href="export_conges_rh.php">Export CSV</a>
        <a class="btn btn-neutral" href="conges_rh.php">Décisions</a>
      </div>

      <?php if ($alertes): ?>
      <section class="card-panel dc-elevated mb-3">
        <h2 class="card-panel__title">Alertes chevauchement équipe (≥ 2 absents le même jour)</h2>
        <div class="table-responsive">
          <table class="table dc-table mb-0">
            <thead><tr><th>Date</th><th>Département</th><th>Nb</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($alertes, 0, 30) as $a): ?>
              <tr>
                <td><?= h(conge_fmt_date($a['date'])) ?></td>
                <td><?= h($a['departement']) ?></td>
                <td><strong><?= (int)$a['nb'] ?></strong></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
      <?php endif; ?>

      <div class="dc-split">
        <section class="card-panel dc-elevated">
          <h2 class="card-panel__title">Congés du mois (<?= (int)$mois ?>/<?= (int)$annee ?>)</h2>
          <div class="table-responsive">
            <table class="table dc-table mb-0">
              <thead><tr><th>Employé</th><th>Type</th><th>Période</th><th>Statut</th></tr></thead>
              <tbody>
              <?php if (!$ev['conges']): ?>
                <tr><td colspan="4" class="text-muted">Aucun congé sur la période.</td></tr>
              <?php else: foreach ($ev['conges'] as $c):
                [$lib,$cls] = conge_statut_badge($c['statut']); ?>
                <tr>
                  <td><?= h($c['prenom'].' '.$c['nom']) ?></td>
                  <td><?= h($c['type_libelle']) ?></td>
                  <td><?= h(conge_fmt_date($c['date_debut'])) ?> – <?= h(conge_fmt_date($c['date_fin'])) ?></td>
                  <td><span class="dc-badge <?= h($cls) ?>"><?= h($lib) ?></span></td>
                </tr>
              <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </section>
        <section class="card-panel dc-elevated">
          <h2 class="card-panel__title">Justificatifs d’absence en retard (&gt; 3 j.)</h2>
          <?php if (!$retard): ?>
            <p class="text-muted small mb-0">Aucun retard signalé.</p>
          <?php else: foreach ($retard as $r): ?>
            <div class="small border-bottom py-2">
              <strong><?= h($r['prenom'].' '.$r['nom']) ?></strong>
              · <?= h(conge_fmt_date($r['date_absence'])) ?>
              · <?= h($r['matricule']) ?>
            </div>
          <?php endforeach; endif; ?>
        </section>
      </div>
    </main>
  </div>
</div>
</body>
</html>
