<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../services/CongesPilotageService.php';
exigerRole(['rh']);
$svc = new CongesPilotageService($pdo);
$depuis = $_GET['depuis'] ?? date('Y-01-01');
$jusque = $_GET['jusque'] ?? date('Y-12-31');
$statut = $_GET['statut'] ?? '';
if (isset($_GET['download'])) {
    $rows = $svc->exportDemandes($depuis ?: null, $jusque ?: null, $statut ?: null);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="conges_export.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['id','soumission','debut','fin','jours','statut','matricule','nom','prenom','type','departement','motif'], ';');
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'], $r['date_soumission'], $r['date_debut'], $r['date_fin'], $r['nb_jours'], $r['statut'],
            $r['matricule'], $r['nom'], $r['prenom'], $r['type_conge'], $r['departement'], $r['motif'] ?? '',
        ], ';');
    }
    fclose($out);
    exit;
}
$rows = $svc->exportDemandes($depuis ?: null, $jusque ?: null, $statut ?: null);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Export congés — RH</title>
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
      <div class="app-header__heading"><h1 class="app-header__title">Export des demandes</h1></div>
      <?php include __DIR__ . '/../layout/cloche.php'; ?>
    </header>
    <main class="app-content">
      <form method="get" class="card-panel dc-elevated row g-2 align-items-end mb-3">
        <div class="col-md-3"><label class="form-label">Du</label><input type="date" name="depuis" class="form-control" value="<?= h($depuis) ?>"></div>
        <div class="col-md-3"><label class="form-label">Au</label><input type="date" name="jusque" class="form-control" value="<?= h($jusque) ?>"></div>
        <div class="col-md-3"><label class="form-label">Statut</label>
          <select name="statut" class="form-select">
            <option value="">Tous</option>
            <?php foreach (['en_attente_chef','en_attente_rh','en_attente_dg','approuvee','refusee','annulee','en_cours','terminee'] as $s): ?>
              <option value="<?= $s ?>" <?= $statut===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 dc-actions">
          <button class="btn btn-neutral" type="submit">Filtrer</button>
          <button class="btn btn-primary" type="submit" name="download" value="1">Télécharger CSV</button>
        </div>
      </form>
      <p class="text-muted small"><?= count($rows) ?> ligne(s)</p>
    </main>
  </div>
</div>
</body>
</html>
