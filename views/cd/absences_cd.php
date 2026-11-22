<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../services/AbsenceService.php';
require_once __DIR__ . '/../layout/conges_helpers.php';
exigerRole(['chef']);

$svc = new AbsenceService($pdo);
$equipe = $svc->equipeDuChef((int) $_SESSION['utilisateur_id']);
$ids = array_map(fn($e) => (int) $e['id'], $equipe);
$debut = date('Y-m-01');
$fin = date('Y-m-t');
$liste = $svc->listerPourEmployes($ids, $debut, $fin);
$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfExiger();
    try {
        $svc->marquer(
            (int) ($_POST['employe_id'] ?? 0),
            (string) ($_POST['date_absence'] ?? ''),
            trim((string) ($_POST['motif'] ?? '')) ?: null,
            (int) $_SESSION['utilisateur_id'],
            $_FILES['justificatif'] ?? null
        );
        $_SESSION['flash_conge'] = ['ok' => true, 'msg' => 'Absence enregistrée.'];
        header('Location: absences_cd.php');
        exit;
    } catch (Throwable $e) {
        $erreur = $e->getMessage();
    }
}
$flash = $_SESSION['flash_conge'] ?? null;
unset($_SESSION['flash_conge']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Absences — PerPay</title>
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
  <?php $active = 'absences'; include __DIR__ . '/../layout/navbar_cd.php'; ?>
  <div class="app-main">
    <header class="app-header">
      <div class="app-header__heading"><h1 class="app-header__title">Absences d’équipe</h1></div>
      <?php include __DIR__ . '/../layout/cloche.php'; ?>
    </header>
    <main class="app-content">
      <?php if ($flash): ?><div class="dc-alert <?= $flash['ok']?'dc-alert--ok':'dc-alert--err' ?>"><?= h($flash['msg']) ?></div><?php endif; ?>
      <?php if ($erreur): ?><div class="dc-alert dc-alert--err"><?= h($erreur) ?></div><?php endif; ?>
      <div class="dc-split">
        <section class="card-panel">
          <h2 class="card-panel__title">Marquer une absence</h2>
          <form method="post" enctype="multipart/form-data" class="row g-3">
            <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
            <div class="col-12">
              <label class="form-label">Collaborateur</label>
              <select name="employe_id" class="form-select" required>
                <option value="">Choisir…</option>
                <?php foreach ($equipe as $e): ?>
                  <option value="<?= (int)$e['id'] ?>"><?= h($e['prenom'].' '.$e['nom'].' · '.$e['matricule']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Date</label>
              <input type="date" name="date_absence" class="form-control" required value="<?= h(date('Y-m-d')) ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Motif</label>
              <input type="text" name="motif" class="form-control" maxlength="255">
            </div>
            <div class="col-12">
              <label class="form-label">Justificatif (optionnel)</label>
              <input type="file" name="justificatif" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
              <p class="form-text">Fichier stocké sur le serveur partagé — consultable depuis tout poste autorisé.</p>
            </div>
            <div class="col-12"><button class="btn btn-primary" type="submit">Enregistrer</button></div>
          </form>
        </section>
        <section class="card-panel">
          <h2 class="card-panel__title">Absences du mois</h2>
          <div class="table-responsive">
            <table class="table dc-table mb-0">
              <thead><tr><th>Date</th><th>Employé</th><th>Justifiée</th><th></th></tr></thead>
              <tbody>
              <?php foreach ($liste as $a): ?>
                <tr>
                  <td><?= h(conge_fmt_date($a['date_absence'])) ?></td>
                  <td><?= h($a['prenom'].' '.$a['nom']) ?></td>
                  <td><?= (int)$a['justifiee'] === 1 ? 'Oui' : 'Non' ?></td>
                  <td>
                    <?php if (!empty($a['justificatif_chemin'])): ?>
                      <a href="/public/telecharger_justificatif.php?type=absence&id=<?= (int)$a['id'] ?>">Fichier</a>
                    <?php else: ?>—<?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; if ($liste===[]): ?>
                <tr><td colspan="4" class="text-muted">Aucune absence ce mois.</td></tr>
              <?php endif; ?>
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
