<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../services/DemandeCongeService.php';
require_once __DIR__ . '/../layout/conges_helpers.php';
exigerRole(['rh']);

$svc = new DemandeCongeService($pdo);
$liste = $svc->fileRh();
$flash = $_SESSION['flash_conge'] ?? null;
unset($_SESSION['flash_conge']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfExiger();
    $id = (int) ($_POST['id'] ?? 0);
    $decision = (string) ($_POST['decision'] ?? '');
    $commentaire = trim((string) ($_POST['commentaire'] ?? ''));
    try {
        $svc->decisionRh($id, (int) $_SESSION['utilisateur_id'], $decision, $commentaire !== '' ? $commentaire : null);
        $_SESSION['flash_conge'] = ['ok' => true, 'msg' => 'Décision enregistrée.'];
    } catch (Throwable $e) {
        $_SESSION['flash_conge'] = ['ok' => false, 'msg' => $e->getMessage()];
    }
    header('Location: conges_rh.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Congés — RH · PerPay</title>
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
  <?php $active = 'rh_conges'; include __DIR__ . '/../layout/navbar_rh.php'; ?>
  <div class="app-main">
    <header class="app-header">
      <div class="app-header__heading"><h1 class="app-header__title">Décisions de congés</h1></div>
      <?php include __DIR__ . '/../layout/cloche.php'; ?>
    </header>
    <main class="app-content">
      <?php if ($flash): ?><div class="dc-alert <?= $flash['ok']?'dc-alert--ok':'dc-alert--err' ?>"><?= h($flash['msg']) ?></div><?php endif; ?>
      <section class="card-panel">
        <div class="table-responsive">
          <table class="table table-hover align-middle dc-table mb-0">
            <thead><tr><th>Employé</th><th>Type</th><th>Période</th><th>Jours</th><th>Statut</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($liste as $d): [$lib,$cls]=conge_statut_badge($d['statut']); ?>
              <tr>
                <td><strong><?= h($d['prenom'].' '.$d['nom']) ?></strong><div class="small text-muted"><?= h($d['matricule']) ?></div></td>
                <td><?= h($d['type_libelle']) ?></td>
                <td><?= h(conge_fmt_date($d['date_debut'])) ?> – <?= h(conge_fmt_date($d['date_fin'])) ?></td>
                <td><?= h(conge_fmt_nb($d['nb_jours'])) ?></td>
                <td><span class="dc-badge <?= h($cls) ?>"><?= h($lib) ?></span>
                  <?php if (!empty($d['justificatif_chemin'])): ?>
                    <div class="small"><a href="/public/telecharger_justificatif.php?type=conge&id=<?= (int)$d['id'] ?>">Justificatif</a></div>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($d['statut'] === 'en_attente_rh'): ?>
                  <form method="post" class="dc-actions">
                    <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                    <button class="btn btn-sm btn-primary" name="decision" value="approuve" type="submit">Approuver</button>
                    <button class="btn btn-sm btn-neutral" name="decision" value="refuse" type="submit">Refuser</button>
                  </form>
                  <?php else: ?>—<?php endif; ?>
                </td>
              </tr>
            <?php endforeach; if ($liste===[]): ?>
              <tr><td colspan="6" class="text-muted">Aucune demande.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</div>
</body>
</html>
