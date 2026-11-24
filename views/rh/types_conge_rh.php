<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../services/TypeCongeService.php';
exigerRole(['rh']);
$svc = new TypeCongeService($pdo);
$msg = null; $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfExiger();
    try {
        $id = (int) ($_POST['id'] ?? 0) ?: null;
        $svc->enregistrer($_POST, $id);
        $msg = 'Type de congé enregistré.';
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}
$types = $svc->tous();
$edit = null;
if (isset($_GET['edit'])) {
    $edit = $svc->trouver((int) $_GET['edit']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Types de congés — RH</title>
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
  <?php $active = 'rh_types_conge'; include __DIR__ . '/../layout/navbar_rh.php'; ?>
  <div class="app-main">
    <header class="app-header">
      <div class="app-header__heading"><h1 class="app-header__title">Types de congés</h1></div>
      <?php include __DIR__ . '/../layout/cloche.php'; ?>
    </header>
    <main class="app-content">
      <?php if ($msg): ?><div class="dc-alert dc-alert--ok"><?= h($msg) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="dc-alert dc-alert--err"><?= h($err) ?></div><?php endif; ?>
      <div class="dc-split">
        <section class="card-panel">
          <h2 class="card-panel__title"><?= $edit ? 'Modifier' : 'Nouveau type' ?></h2>
          <form method="post" class="row g-2">
            <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
            <div class="col-md-4"><label class="form-label">Code</label><input name="code" class="form-control" required value="<?= h($edit['code'] ?? '') ?>"></div>
            <div class="col-md-8"><label class="form-label">Libellé</label><input name="libelle" class="form-control" required value="<?= h($edit['libelle'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label">Description</label><input name="description_type" class="form-control" value="<?= h($edit['description_type'] ?? '') ?>"></div>
            <div class="col-md-4">
              <label class="form-label">Catégorie</label>
              <select name="categorie" class="form-select">
                <?php foreach (['paye'=>'Payé','non_paye'=>'Non payé','special'=>'Spécial'] as $k=>$l): ?>
                  <option value="<?= $k ?>" <?= ($edit['categorie'] ?? 'paye')===$k?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4"><label class="form-label">Droit annuel (j)</label><input type="number" step="0.5" name="droit_annuel_jours" class="form-control" value="<?= h((string)($edit['droit_annuel_jours'] ?? '18')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Préavis (j)</label><input type="number" name="preavis_jours" class="form-control" value="<?= h((string)($edit['preavis_jours'] ?? '0')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Durée min</label><input type="number" step="0.5" name="duree_min_jours" class="form-control" value="<?= h((string)($edit['duree_min_jours'] ?? '0.5')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Durée max</label><input type="number" step="0.5" name="duree_max_jours" class="form-control" value="<?= h((string)($edit['duree_max_jours'] ?? '')) ?>"></div>
            <div class="col-12">
              <label class="me-3"><input type="checkbox" name="decompte_solde" value="1" <?= !isset($edit) || !empty($edit['decompte_solde'])?'checked':'' ?>> Décompte solde</label>
              <label class="me-3"><input type="checkbox" name="justificatif_requis" value="1" <?= !empty($edit['justificatif_requis'])?'checked':'' ?>> Justificatif requis</label>
              <label class="me-3"><input type="checkbox" name="fractionnable" value="1" <?= !isset($edit) || !empty($edit['fractionnable'])?'checked':'' ?>> Fractionnable</label>
              <label class="me-3"><input type="checkbox" name="reportable" value="1" <?= !empty($edit['reportable'])?'checked':'' ?>> Reportable</label>
              <label><input type="checkbox" name="actif" value="1" <?= !isset($edit) || !empty($edit['actif'])?'checked':'' ?>> Actif</label>
            </div>
            <div class="col-12"><button class="btn btn-primary" type="submit">Enregistrer</button>
              <?php if ($edit): ?><a class="btn btn-neutral" href="types_conge_rh.php">Nouveau</a><?php endif; ?>
            </div>
          </form>
        </section>
        <section class="card-panel">
          <h2 class="card-panel__title">Catalogue</h2>
          <div class="table-responsive">
            <table class="table dc-table mb-0">
              <thead><tr><th>Code</th><th>Libellé</th><th>Solde</th><th>Actif</th><th></th></tr></thead>
              <tbody>
              <?php foreach ($types as $t): ?>
                <tr>
                  <td><?= h($t['code']) ?></td>
                  <td><?= h($t['libelle']) ?></td>
                  <td><?= (int)$t['decompte_solde']?'Oui':'Non' ?></td>
                  <td><?= (int)$t['actif']?'Oui':'Non' ?></td>
                  <td><a href="?edit=<?= (int)$t['id'] ?>">Modifier</a></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </main>
  </div>
</div>
</body>
</html>
