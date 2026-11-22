<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../services/DemandeCongeService.php';
require_once __DIR__ . '/../layout/conges_helpers.php';
exigerRole(['chef']);

$svc = new DemandeCongeService($pdo);
$id = (int) ($_GET['id'] ?? 0);
$d = $svc->trouver($id);
if ($d === null) { http_response_code(404); exit('Demande introuvable.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfExiger();
    $decision = $_POST['decision'] ?? '';
    $commentaire = trim((string)($_POST['commentaire'] ?? ''));
    try {
        $svc->avisChef($id, (int)$_SESSION['utilisateur_id'], $decision, $commentaire !== '' ? $commentaire : null);
        $_SESSION['flash_conge'] = ['ok'=>true,'msg'=>'Avis enregistré. La demande est transmise à la RH.'];
        header('Location: avis_conges_cd.php');
        exit;
    } catch (Throwable $e) {
        $erreur = $e->getMessage();
    }
}
[$lib,$cls]=conge_statut_badge($d['statut']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Avis #<?= (int)$d['id'] ?> — PerPay</title>
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
  <?php $active = 'avis'; include __DIR__ . '/../layout/navbar_cd.php'; ?>
  <div class="app-main">
    <header class="app-header">
      <div class="app-header__heading">
        <h1 class="app-header__title"><?= h($d['prenom'].' '.$d['nom']) ?></h1>
        <p class="app-header__sub"><a href="avis_conges_cd.php">Avis congés</a> / #<?= (int)$d['id'] ?></p>
      </div>
      <?php include __DIR__ . '/../layout/cloche.php'; ?>
    </header>
    <main class="app-content">
      <?php if (!empty($erreur)): ?><div class="dc-alert dc-alert--err"><?= h($erreur) ?></div><?php endif; ?>
      <section class="card-panel mb-3">
        <p><span class="dc-badge <?= h($cls) ?>"><?= h($lib) ?></span></p>
        <p class="mb-1"><strong><?= h($d['type_libelle']) ?></strong> · <?= h(conge_fmt_nb($d['nb_jours'])) ?> j.</p>
        <p class="mb-1"><?= h(conge_fmt_date($d['date_debut'])) ?> – <?= h(conge_fmt_date($d['date_fin'])) ?></p>
        <p class="mb-1">Motif : <?= h($d['motif'] ?: '—') ?></p>
        <?php if (!empty($d['justificatif_chemin'])): ?>
          <p><a href="/public/telecharger_justificatif.php?type=conge&id=<?= (int)$d['id'] ?>">Justificatif</a></p>
        <?php endif; ?>
      </section>
      <?php if ($d['statut'] === 'en_attente_chef'): ?>
      <section class="card-panel">
        <h2 class="card-panel__title">Votre avis (consultatif)</h2>
        <form method="post" class="row g-3">
          <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
          <div class="col-12">
            <label class="form-label">Commentaire</label>
            <textarea name="commentaire" class="form-control" rows="2" maxlength="255"></textarea>
          </div>
          <div class="col-12 dc-actions">
            <button class="btn btn-primary" name="decision" value="favorable" type="submit">Avis favorable</button>
            <button class="btn btn-neutral" name="decision" value="defavorable" type="submit">Avis défavorable</button>
            <a class="btn btn-neutral" href="avis_conges_cd.php">Retour</a>
          </div>
        </form>
      </section>
      <?php else: ?>
        <a class="btn btn-neutral" href="avis_conges_cd.php">Retour</a>
      <?php endif; ?>
    </main>
  </div>
</div>
</body>
</html>
