<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../models/Employe.php';
require_once __DIR__ . '/../../services/DemandeCongeService.php';
require_once __DIR__ . '/../../services/JoursOuvrablesService.php';
exigerRole(['employe', 'chef', 'rh']);

$moi = (new Employe($pdo))->trouverParUtilisateurId((int) $_SESSION['utilisateur_id']);
if ($moi === null) {
    http_response_code(404);
    exit('Aucun dossier employé associé à ce compte.');
}
$svc = new DemandeCongeService($pdo);
$types = $svc->typesActifs();
$erreurs = [];
$v = ['type_conge_id' => '', 'date_debut' => '', 'date_fin' => '', 'motif' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerifier($_POST['csrf'] ?? '')) {
        $erreurs[] = 'Session expirée. Rechargez la page.';
    } else {
        $v['type_conge_id'] = (string) ($_POST['type_conge_id'] ?? '');
        $v['date_debut'] = (string) ($_POST['date_debut'] ?? '');
        $v['date_fin'] = (string) ($_POST['date_fin'] ?? '');
        $v['motif'] = trim((string) ($_POST['motif'] ?? ''));
        try {
            $id = $svc->deposer(
                (int) $moi['id'],
                (int) $_SESSION['utilisateur_id'],
                $v,
                $_FILES['justificatif'] ?? null
            );
            $_SESSION['flash_conge'] = ['ok' => true, 'msg' => 'Demande #' . $id . ' enregistrée. Le solde a été réservé si nécessaire.'];
            header('Location: conges_employe.php');
            exit;
        } catch (Throwable $e) {
            $erreurs[] = $e->getMessage();
        }
    }
}
$nbApercu = '';
if ($v['date_debut'] && $v['date_fin']) {
    $nbApercu = (new JoursOuvrablesService($pdo))->compter($v['date_debut'], $v['date_fin']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nouvelle demande — PerPay</title>
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
        <h1 class="app-header__title">Nouvelle demande de congé</h1>
        <p class="app-header__sub"><a href="conges_employe.php">Congés</a> / Nouvelle demande</p>
      </div>
      <?php include __DIR__ . '/../layout/cloche.php'; ?>
    </header>
    <main class="app-content">
      <?php foreach ($erreurs as $err): ?>
        <div class="dc-alert dc-alert--err"><?= h($err) ?></div>
      <?php endforeach; ?>
      <div class="dc-split">
        <section class="card-panel">
          <form method="post" enctype="multipart/form-data" class="row g-3">
            <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
            <div class="col-12">
              <label class="form-label fw-semibold">Type de congé</label>
              <select name="type_conge_id" class="form-select" required>
                <option value="">Choisir…</option>
                <?php foreach ($types as $t): ?>
                  <option value="<?= (int) $t['id'] ?>" <?= (string)$t['id'] === $v['type_conge_id'] ? 'selected' : '' ?>>
                    <?= h($t['libelle']) ?><?= (int)$t['justificatif_requis'] === 1 ? ' (justificatif requis)' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Date de début</label>
              <input type="date" name="date_debut" class="form-control" required value="<?= h($v['date_debut']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Date de fin</label>
              <input type="date" name="date_fin" class="form-control" required value="<?= h($v['date_fin']) ?>">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Motif</label>
              <textarea name="motif" class="form-control" rows="3" maxlength="255"><?= h($v['motif']) ?></textarea>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Justificatif</label>
              <input type="file" name="justificatif" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
              <p class="form-text mb-0">Stocké sur le serveur (PDF/JPG/PNG, max 5 Mo). Accessible depuis n’importe quel poste autorisé.</p>
            </div>
            <div class="col-12 dc-actions">
              <button type="submit" class="btn btn-primary">Soumettre la demande</button>
              <a class="btn btn-neutral" href="conges_employe.php">Annuler</a>
            </div>
          </form>
        </section>
        <aside class="card-panel">
          <h2 class="card-panel__title">Rappel</h2>
          <ul class="mb-0">
            <li>Les week-ends et jours fériés ne sont pas décomptés.</li>
            <li>Le solde est <strong>réservé</strong> dès le dépôt si le type décompte le solde.</li>
            <li>Circuit : chef (avis) → RH (décision). Les congés d’un RH passent aussi par le DG.</li>
            <?php if ($nbApercu !== ''): ?>
              <li class="mt-2">Aperçu période : <strong><?= h((string)$nbApercu) ?></strong> jour(s) ouvrable(s).</li>
            <?php endif; ?>
          </ul>
        </aside>
      </div>
    </main>
  </div>
</div>
</body>
</html>
