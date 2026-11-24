<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../services/SoldeAnneeService.php';
require_once __DIR__ . '/../layout/conges_helpers.php';
exigerRole(['rh']);
$svc = new SoldeAnneeService($pdo);
$employes = $pdo->query(
    "SELECT id, matricule, nom, prenom FROM employe WHERE statut = 'actif' ORDER BY nom, prenom"
)->fetchAll();
$employeId = (int) ($_GET['employe_id'] ?? 0);
$annee = (int) ($_GET['annee'] ?? date('Y'));
$msg = null; $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'corriger') {
    csrfExiger();
    try {
        $svc->corriger(
            (int) $_POST['solde_id'],
            (int) $_SESSION['utilisateur_id'],
            (float) $_POST['delta'],
            (string) ($_POST['motif'] ?? '')
        );
        $msg = 'Correction enregistrée dans le journal.';
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}
$soldes = $employeId ? $svc->soldesEmploye($employeId, $annee ?: null) : [];
$histo = [];
$soldeSel = (int) ($_GET['solde_id'] ?? 0);
if ($soldeSel) {
    $histo = $svc->historiqueSolde($soldeSel);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Historique soldes — RH</title>
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
        <h1 class="app-header__title">Historique & correction de solde</h1>
        <p class="app-header__sub">Lecture seule du journal · correction = nouveau mouvement</p>
      </div>
      <?php include __DIR__ . '/../layout/cloche.php'; ?>
    </header>
    <main class="app-content">
      <?php if ($msg): ?><div class="dc-alert dc-alert--ok"><?= h($msg) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="dc-alert dc-alert--err"><?= h($err) ?></div><?php endif; ?>
      <section class="card-panel dc-elevated mb-3">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-5">
            <label class="form-label">Employé</label>
            <select name="employe_id" class="form-select" required>
              <option value="">—</option>
              <?php foreach ($employes as $e): ?>
                <option value="<?= (int)$e['id'] ?>" <?= $employeId===(int)$e['id']?'selected':'' ?>>
                  <?= h($e['prenom'].' '.$e['nom'].' · '.$e['matricule']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Année (vide = toutes)</label>
            <input type="number" name="annee" class="form-control" value="<?= $annee ?: '' ?>">
          </div>
          <div class="col-md-2"><button class="btn btn-primary" type="submit">Afficher</button></div>
        </form>
      </section>
      <?php if ($soldes): ?>
      <section class="card-panel dc-elevated mb-3">
        <div class="table-responsive">
          <table class="table dc-table mb-0">
            <thead><tr><th>Année</th><th>Type</th><th>Acquis</th><th>Réservé</th><th>Pris</th><th>Dispo</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($soldes as $s): ?>
              <tr>
                <td><?= (int)$s['annee'] ?></td>
                <td><?= h($s['libelle']) ?></td>
                <td><?= h(conge_fmt_nb($s['jours_acquis'])) ?></td>
                <td><?= h(conge_fmt_nb($s['jours_reserves'] ?? 0)) ?></td>
                <td><?= h(conge_fmt_nb($s['jours_pris'])) ?></td>
                <td><strong><?= h(conge_fmt_nb($s['disponible'])) ?></strong></td>
                <td><a href="?employe_id=<?= $employeId ?>&annee=<?= (int)$s['annee'] ?>&solde_id=<?= (int)$s['id'] ?>">Journal</a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
      <?php endif; ?>
      <?php if ($soldeSel): ?>
      <section class="card-panel dc-elevated mb-3">
        <h2 class="card-panel__title">Mouvements · solde #<?= $soldeSel ?></h2>
        <ul class="dc-timeline">
          <?php if (!$histo): ?><li class="text-muted">Aucun mouvement (table journal absente ou vide).</li>
          <?php else: foreach ($histo as $m): ?>
            <li>
              <strong><?= h($m['type_mouvement'] ?? $m['type'] ?? '') ?></strong>
              · Δ acquis <?= h((string)($m['delta_acquis'] ?? 0)) ?>
              · réservé <?= h((string)($m['delta_reserve'] ?? 0)) ?>
              · pris <?= h((string)($m['delta_pris'] ?? 0)) ?>
              <div class="text-muted"><?= h($m['date_mouvement'] ?? '') ?> · <?= h($m['motif'] ?? '') ?></div>
            </li>
          <?php endforeach; endif; ?>
        </ul>
        <hr>
        <h3 class="h6">Correction RH</h3>
        <form method="post" class="row g-2">
          <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
          <input type="hidden" name="action" value="corriger">
          <input type="hidden" name="solde_id" value="<?= $soldeSel ?>">
          <div class="col-md-3"><label class="form-label">Δ acquis (ex. 1 ou -0.5)</label>
            <input type="number" step="0.5" name="delta" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label">Motif obligatoire</label>
            <input type="text" name="motif" class="form-control" required maxlength="255"></div>
          <div class="col-md-3"><label class="form-label d-block">&nbsp;</label>
            <button class="btn btn-primary" type="submit">Poster la correction</button></div>
        </form>
      </section>
      <?php endif; ?>
    </main>
  </div>
</div>
</body>
</html>
