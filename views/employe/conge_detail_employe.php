<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../models/Employe.php';
require_once __DIR__ . '/../../services/DemandeCongeService.php';
require_once __DIR__ . '/../layout/conges_helpers.php';
exigerRole(['employe', 'chef', 'rh']);

$moi = (new Employe($pdo))->trouverParUtilisateurId((int) $_SESSION['utilisateur_id']);
if ($moi === null) { http_response_code(404); exit('Dossier introuvable.'); }
$svc = new DemandeCongeService($pdo);
$id = (int) ($_GET['id'] ?? 0);
$d = $svc->trouver($id);
if ($d === null || (int) $d['employe_id'] !== (int) $moi['id']) {
    http_response_code(404); exit('Demande introuvable.');
}
$vals = $svc->validationsDe($id);
$apercu = $svc->apercuCalcul([
    'date_debut' => $d['date_debut'],
    'date_fin' => $d['date_fin'],
    'periode_debut' => $d['periode_debut'] ?? 'MATIN',
    'periode_fin' => $d['periode_fin'] ?? 'APRES_MIDI',
]);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'dupliquer') {
    csrfExiger();
    try {
        $nid = $svc->dupliquer($id, (int) $moi['id']);
        $_SESSION['flash_conge'] = ['ok' => true, 'msg' => 'Brouillon #' . $nid . ' créé par duplication.'];
        header('Location: conges_employe.php');
        exit;
    } catch (Throwable $e) {
        $_SESSION['flash_conge'] = ['ok' => false, 'msg' => $e->getMessage()];
        header('Location: conge_detail_employe.php?id=' . $id);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'annuler') {
    csrfExiger();
    try {
        $svc->annuler($id, (int) $moi['id'], (int) $_SESSION['utilisateur_id']);
        $_SESSION['flash_conge'] = ['ok' => true, 'msg' => 'Demande annulée. Le solde réservé a été libéré.'];
    } catch (Throwable $e) {
        $_SESSION['flash_conge'] = ['ok' => false, 'msg' => $e->getMessage()];
    }
    header('Location: conges_employe.php');
    exit;
}
[$lib, $cls] = conge_statut_badge($d['statut']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Demande #<?= (int)$d['id'] ?> — PerPay</title>
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
        <h1 class="app-header__title">Demande #<?= (int)$d['id'] ?></h1>
        <p class="app-header__sub"><a href="conges_employe.php">Congés</a> / Détail</p>
      </div>
      <?php include __DIR__ . '/../layout/cloche.php'; ?>
    </header>
    <main class="app-content">
      <div class="dc-split">
        <section class="card-panel">
          <p><span class="dc-badge <?= h($cls) ?>"><?= h($lib) ?></span></p>
          <dl class="row mb-0">
            <dt class="col-sm-4">Type</dt><dd class="col-sm-8"><?= h($d['type_libelle']) ?></dd>
            <dt class="col-sm-4">Période</dt>
            <dd class="col-sm-8">
              <?= h(conge_fmt_date($d['date_debut'])) ?>
              (<?= h($d['periode_debut'] ?? 'MATIN') ?>)
              –
              <?= h(conge_fmt_date($d['date_fin'])) ?>
              (<?= h($d['periode_fin'] ?? 'APRES_MIDI') ?>)
            </dd>
            <dt class="col-sm-4">Jours décomptés</dt><dd class="col-sm-8"><?= h(conge_fmt_nb($d['nb_jours'])) ?></dd>
            <dt class="col-sm-4">Horaires</dt><dd class="col-sm-8"><?php
              $hd = $d['heure_debut'] ?? null; $hf = $d['heure_fin'] ?? null;
              echo ($hd || $hf) ? h(substr((string)$hd,0,5) . ' → ' . substr((string)$hf,0,5)) : '—';
            ?></dd>
            <dt class="col-sm-4">Reprise</dt><dd class="col-sm-8"><?= h(conge_fmt_date($d['date_reprise'] ?? $apercu['date_reprise'] ?? null)) ?></dd>
            <dt class="col-sm-4">Motif</dt><dd class="col-sm-8"><?= h($d['motif'] ?: '—') ?></dd>
            <dt class="col-sm-4">Contact</dt><dd class="col-sm-8"><?= h($d['contact_urgence'] ?? '—') ?></dd>
            <dt class="col-sm-4">Justificatif</dt>
            <dd class="col-sm-8">
              <?php if (!empty($d['justificatif_chemin'])): ?>
                <a href="/public/telecharger_justificatif.php?type=conge&id=<?= (int)$d['id'] ?>">Télécharger</a>
              <?php else: ?>—<?php endif; ?>
            </dd>
            <dt class="col-sm-4">Déposée le</dt><dd class="col-sm-8"><?= h($d['date_soumission']) ?></dd>
          </dl>
          <div class="dc-actions mt-3">
            <a class="btn btn-neutral" href="conges_employe.php">Retour</a>
            <a class="btn btn-neutral" href="conge_pdf_employe.php?id=<?= (int)$d['id'] ?>" target="_blank">PDF / Imprimer</a>
            <form method="post" class="d-inline">
              <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
              <input type="hidden" name="action" value="dupliquer">
              <button class="btn btn-neutral" type="submit">Dupliquer</button>
            </form>
            <?php if (in_array($d['statut'], ['en_attente_chef','en_attente_rh','en_attente_dg'], true)): ?>
            <form method="post" class="d-inline" onsubmit="return confirm('Annuler cette demande ?');">
              <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
              <input type="hidden" name="action" value="annuler">
              <button class="btn btn-danger" type="submit">Annuler la demande</button>
            </form>
            <?php endif; ?>
          </div>
        </section>
        <aside>
          <section class="card-panel mb-3">
            <h2 class="card-panel__title">Décompte</h2>
            <ul class="small mb-0">
              <li>Calendaires : <?= (int)($apercu['jours_calendaires'] ?? 0) ?></li>
              <li>Week-ends exclus : <?= (int)($apercu['week_ends'] ?? 0) ?></li>
              <li>Fériés exclus : <?= (int)($apercu['feries_ouvres'] ?? 0) ?></li>
              <li>Décomptés : <strong><?= h(conge_fmt_nb($apercu['jours_decomptes'] ?? $d['nb_jours'])) ?></strong></li>
            </ul>
          </section>
          <section class="card-panel">
            <h2 class="card-panel__title">Historique des décisions</h2>
            <?php if ($vals === []): ?>
              <p class="text-muted small mb-0">Aucune décision pour l’instant.</p>
            <?php else: foreach ($vals as $v): ?>
              <div class="small mb-2 border-bottom pb-2">
                <div class="fw-semibold"><?= h(strtoupper($v['etape'])) ?> · <?= h($v['decision']) ?></div>
                <div><?= h(trim(($v['decideur_prenom'] ?? '') . ' ' . ($v['decideur_nom'] ?? '')) ?: $v['email']) ?></div>
                <div class="text-muted"><?= h($v['date_decision']) ?></div>
                <?php if (!empty($v['commentaire'])): ?><div><?= h($v['commentaire']) ?></div><?php endif; ?>
              </div>
            <?php endforeach; endif; ?>
          </section>
        </aside>
      </div>
    </main>
  </div>
</div>
</body>
</html>
