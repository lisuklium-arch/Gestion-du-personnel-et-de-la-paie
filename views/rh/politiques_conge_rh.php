<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../models/ParametreSysteme.php';
exigerRole(['rh']);
$repo = new ParametreSysteme($pdo);
$cles = [
    'conge_preavis_min_jours' => 'Préavis minimal (jours)',
    'conge_duree_max_jours' => 'Durée max d\'une demande (jours ouvrés, 0 = illimité)',
    'conge_max_demandes_ouvertes' => 'Max demandes en cours par employé',
    'conge_autoriser_retroactif' => 'Autoriser rétroactif (1=oui, 0=non)',
    'conge_jours_ouvrables' => 'Jours ouvrés (ex. 1,2,3,4,5 pour lun–ven)',
];
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfExiger();
    foreach ($cles as $cle => $_) {
        if (isset($_POST[$cle])) {
            $val = trim((string) $_POST[$cle]);
            if ($repo->trouver($cle)) {
                $repo->modifierValeur($cle, $val);
            } else {
                $pdo->prepare('INSERT INTO parametre_systeme (cle, valeur, description) VALUES (?,?,?)')
                    ->execute([$cle, $val, $cles[$cle]]);
            }
        }
    }
    $msg = 'Politiques enregistrées.';
}
$vals = [];
foreach ($cles as $cle => $_) {
    $row = $repo->trouver($cle);
    $vals[$cle] = $row['valeur'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Politiques congés — RH</title>
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
  <?php $active = 'rh_politiques_conge'; include __DIR__ . '/../layout/navbar_rh.php'; ?>
  <div class="app-main">
    <header class="app-header">
      <div class="app-header__heading"><h1 class="app-header__title">Politiques de congés</h1></div>
      <?php include __DIR__ . '/../layout/cloche.php'; ?>
    </header>
    <main class="app-content">
      <?php if ($msg): ?><div class="dc-alert dc-alert--ok"><?= h($msg) ?></div><?php endif; ?>
      <section class="card-panel">
        <form method="post" class="row g-3">
          <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
          <?php foreach ($cles as $cle => $label): ?>
            <div class="col-md-6">
              <label class="form-label"><?= h($label) ?></label>
              <input class="form-control" name="<?= h($cle) ?>" value="<?= h($vals[$cle]) ?>">
              <div class="form-text"><?= h($cle) ?></div>
            </div>
          <?php endforeach; ?>
          <div class="col-12"><button class="btn btn-primary" type="submit">Enregistrer</button></div>
        </form>
      </section>
    </main>
  </div>
</div>
</body>
</html>
