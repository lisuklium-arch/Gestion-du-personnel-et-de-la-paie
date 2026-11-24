<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../services/SoldeAnneeService.php';
exigerRole(['rh']);
$svc = new SoldeAnneeService($pdo);
$msg = null; $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfExiger();
    try {
        $action = $_POST['action'] ?? '';
        $annee = (int) ($_POST['annee'] ?? date('Y'));
        $uid = (int) $_SESSION['utilisateur_id'];
        if ($action === 'ouvrir') {
            $r = $svc->ouvrirAnnee($annee, $uid);
            $msg = "Année $annee : {$r['crees']} solde(s) créé(s), {$r['ignores']} déjà existant(s).";
        } elseif ($action === 'reporter') {
            $r = $svc->reporterVers($annee, $uid);
            $msg = "Report $annee → {$r['annee_cible']} : {$r['reports']} ligne(s), {$r['jours']} j.";
        }
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}
$annees = $svc->anneesConnues();
$anneeDef = (int) date('Y');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Soldes par année — RH</title>
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
  <?php $active = 'rh_soldes_annee'; include __DIR__ . '/../layout/navbar_rh.php'; ?>
  <div class="app-main">
    <header class="app-header">
      <div class="app-header__heading">
        <h1 class="app-header__title">Soldes multi-années</h1>
        <p class="app-header__sub">Ouverture des droits et report de reliquat (journal conservé)</p>
      </div>
      <?php include __DIR__ . '/../layout/cloche.php'; ?>
    </header>
    <main class="app-content">
      <?php if ($msg): ?><div class="dc-alert dc-alert--ok"><?= h($msg) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="dc-alert dc-alert--err"><?= h($err) ?></div><?php endif; ?>
      <div class="dc-split">
        <section class="card-panel dc-elevated">
          <h2 class="card-panel__title">Ouvrir une année</h2>
          <p class="small text-muted">Crée un solde par employé actif × type à décompte, avec mouvement ACQUISITION.</p>
          <form method="post" class="row g-2 align-items-end">
            <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="action" value="ouvrir">
            <div class="col-auto"><label class="form-label">Année</label>
              <input type="number" name="annee" class="form-control" value="<?= $anneeDef + 1 ?>" min="2020" max="2100"></div>
            <div class="col-auto"><button class="btn btn-primary" type="submit">Ouvrir</button></div>
          </form>
        </section>
        <section class="card-panel dc-elevated">
          <h2 class="card-panel__title">Reporter le reliquat</h2>
          <p class="small text-muted">Pour les types <em>reportable</em> : bascule le disponible de N vers N+1 (report / acquisition journal).</p>
          <form method="post" class="row g-2 align-items-end">
            <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="action" value="reporter">
            <div class="col-auto"><label class="form-label">Année source</label>
              <input type="number" name="annee" class="form-control" value="<?= $anneeDef ?>" min="2020" max="2100"></div>
            <div class="col-auto"><button class="btn btn-primary" type="submit">Reporter vers N+1</button></div>
          </form>
        </section>
      </div>
      <section class="card-panel dc-elevated mt-3">
        <h2 class="card-panel__title">Années déjà présentes en base</h2>
        <p class="mb-0"><?= $annees ? h(implode(', ', $annees)) : 'Aucune — ouvrez une année.' ?></p>
        <p class="small text-muted mt-2 mb-0"><a href="solde_historique_rh.php">Consulter / corriger un solde employé</a></p>
      </section>
    </main>
  </div>
</div>
</body>
</html>
