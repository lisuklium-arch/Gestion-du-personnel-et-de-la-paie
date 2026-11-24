<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../models/Employe.php';
require_once __DIR__ . '/../../services/DemandeCongeService.php';
require_once __DIR__ . '/../layout/conges_helpers.php';
exigerRole(['employe', 'chef', 'rh']);

$moi = (new Employe($pdo))->trouverParUtilisateurId((int) $_SESSION['utilisateur_id']);
if ($moi === null) {
    http_response_code(404);
    exit('Aucun dossier employé associé à ce compte.');
}
$svc = new DemandeCongeService($pdo);
$profil = $svc->profilEmploye((int) $moi['id']);
$types = $svc->typesActifs();
$soldes = $svc->soldesEmploye((int) $moi['id'], (int) date('Y'));
$soldesParType = [];
foreach ($soldes as $s) {
    $soldesParType[(int) $s['type_conge_id']] = $s;
}
$collegues = $svc->colleguesDepartement((int) $moi['id']);
$erreurs = [];
$v = [
    'type_conge_id' => '',
    'date_debut' => '',
    'date_fin' => '',
    'periode_debut' => 'MATIN',
    'periode_fin' => 'APRES_MIDI',
    'motif' => '',
    'contact_urgence' => '',
    'remplacant_id' => '',
    'heure_debut' => '',
    'heure_fin' => '',
];
$apercu = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerifier($_POST['csrf'] ?? '')) {
        $erreurs[] = 'Session expirée. Rechargez la page.';
    } else {
        foreach ($v as $k => $_) {
            if (isset($_POST[$k])) {
                $v[$k] = is_string($_POST[$k]) ? trim($_POST[$k]) : $_POST[$k];
            }
        }
        $apercu = $svc->apercuCalcul($v);
        if (isset($_POST['calcul_seulement'])) {
            // simple redisplay
        } elseif (isset($_POST['brouillon'])) {
            try {
                $id = $svc->sauvegarderBrouillon((int) $moi['id'], (int) $_SESSION['utilisateur_id'], $v);
                $_SESSION['flash_conge'] = ['ok' => true, 'msg' => 'Brouillon #' . $id . ' enregistré (aucune réservation de solde).'];
                header('Location: conges_employe.php');
                exit;
            } catch (Throwable $e) {
                $erreurs[] = $e->getMessage();
            }
        } else {
            try {
                $id = $svc->deposer((int) $moi['id'], (int) $_SESSION['utilisateur_id'], $v, $_FILES['justificatif'] ?? null);
                $_SESSION['flash_conge'] = [
                    'ok' => true,
                    'msg' => 'Demande #' . $id . ' enregistrée. Solde réservé si le type le prévoit. Circuit de validation démarré.',
                ];
                header('Location: conges_employe.php');
                exit;
            } catch (Throwable $e) {
                $erreurs[] = $e->getMessage();
            }
        }
    }
} elseif ($v['date_debut'] && $v['date_fin']) {
    $apercu = $svc->apercuCalcul($v);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nouvelle demande de congé — PerPay</title>
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

      <!-- Identité (auto) -->
      <section class="card-panel dc-elevated mb-3">
        <p class="dc-section-title">Identité du demandeur</p>
        <div class="dc-id-grid">
          <div><span class="dc-id-grid__label">Nom</span><span class="dc-id-grid__value"><?= h(($profil['prenom'] ?? '') . ' ' . ($profil['nom'] ?? '')) ?></span></div>
          <div><span class="dc-id-grid__label">Matricule</span><span class="dc-id-grid__value"><?= h($profil['matricule'] ?? '—') ?></span></div>
          <div><span class="dc-id-grid__label">Département</span><span class="dc-id-grid__value"><?= h($profil['departement'] ?? '—') ?></span></div>
          <div><span class="dc-id-grid__label">Poste</span><span class="dc-id-grid__value"><?= h($profil['poste'] ?? '—') ?></span></div>
          <div><span class="dc-id-grid__label">Chef de département</span><span class="dc-id-grid__value"><?= h(trim(($profil['chef_prenom'] ?? '') . ' ' . ($profil['chef_nom'] ?? '')) ?: 'Non renseigné') ?></span></div>
        </div>
      </section>

      <div class="dc-split">
        <section class="card-panel">
          <h2 class="card-panel__title mb-3">Période, type et motif</h2>
          <form method="post" enctype="multipart/form-data" class="row g-3" id="form-conge">
            <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">

            <div class="col-12">
              <label class="form-label fw-semibold">Type de congé</label>
              <select name="type_conge_id" id="type_conge_id" class="form-select" required>
                <option value="">Choisir…</option>
                <?php foreach ($types as $t):
                    $sid = (int) $t['id'];
                    $sol = $soldesParType[$sid] ?? null;
                    $dispo = $sol ? conge_fmt_nb($sol['jours_disponibles']) : '—';
                ?>
                  <option value="<?= $sid ?>"
                          data-decompte="<?= (int)$t['decompte_solde'] ?>"
                          data-justif="<?= (int)$t['justificatif_requis'] ?>"
                          data-dispo="<?= h((string)$dispo) ?>"
                          <?= (string)$sid === $v['type_conge_id'] ? 'selected' : '' ?>>
                    <?= h($t['libelle']) ?>
                    <?= (int)$t['decompte_solde'] === 1 ? " — solde $dispo j." : ' — sans décompte' ?>
                    <?= (int)$t['justificatif_requis'] === 1 ? ' (justificatif requis)' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Date de début</label>
              <input type="date" name="date_debut" class="form-control" required min="<?= h(date('Y-m-d')) ?>" value="<?= h($v['date_debut']) ?>">
              <div class="form-text">Impossible de choisir une date antérieure à aujourd’hui.</div>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Période début</label>
              <select name="periode_debut" class="form-select">
                <option value="MATIN" <?= $v['periode_debut']==='MATIN'?'selected':'' ?>>Matin</option>
                <option value="APRES_MIDI" <?= $v['periode_debut']==='APRES_MIDI'?'selected':'' ?>>Après-midi</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Heure de début</label>
              <input type="time" name="heure_debut" class="form-control" value="<?= h($v['heure_debut'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Date de fin</label>
              <input type="date" name="date_fin" class="form-control" required min="<?= h(date('Y-m-d')) ?>" value="<?= h($v['date_fin']) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Période fin</label>
              <select name="periode_fin" class="form-select">
                <option value="APRES_MIDI" <?= $v['periode_fin']==='APRES_MIDI'?'selected':'' ?>>Après-midi</option>
                <option value="MATIN" <?= $v['periode_fin']==='MATIN'?'selected':'' ?>>Matin</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Heure de fin</label>
              <input type="time" name="heure_fin" class="form-control" value="<?= h($v['heure_fin'] ?? '') ?>">
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold">Motif</label>
              <textarea name="motif" class="form-control" rows="3" maxlength="255" placeholder="Ex. repos annuel, motif familial…"><?= h($v['motif']) ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Contact pendant l’absence</label>
              <input type="text" name="contact_urgence" class="form-control" maxlength="120" value="<?= h($v['contact_urgence']) ?>" placeholder="Téléphone ou e-mail">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Collègue de relève (optionnel)</label>
              <select name="remplacant_id" class="form-select">
                <option value="">— Aucun —</option>
                <?php foreach ($collegues as $c): ?>
                  <option value="<?= (int)$c['id'] ?>" <?= (string)$c['id']===$v['remplacant_id']?'selected':'' ?>>
                    <?= h($c['prenom'].' '.$c['nom'].' · '.$c['matricule']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Justificatif</label>
              <input type="file" name="justificatif" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
              <p class="form-text mb-0">Stocké sur le serveur (PDF/JPG/PNG, max 5 Mo). Accessible aux personnes habilitées depuis n’importe quel poste.</p>
            </div>
            <div class="col-12 dc-actions">
              <button type="submit" class="btn btn-primary">Soumettre la demande</button>
              <button type="submit" name="brouillon" value="1" class="btn btn-neutral">Enregistrer brouillon</button>
              <button type="submit" name="calcul_seulement" value="1" class="btn btn-neutral">Recalculer l’aperçu</button>
              <a class="btn btn-neutral" href="conges_employe.php">Annuler</a>
            </div>
          </form>
        </section>

        <aside class="dc-sticky-side">
          <section class="dc-side-card">
            <h2>Aperçu du calcul</h2>
            <?php if ($apercu && $apercu['jours_decomptes'] > 0): ?>
              <div class="dc-calc-row"><span>Jours calendaires</span><strong><?= (int)$apercu['jours_calendaires'] ?></strong></div>
              <div class="dc-calc-row"><span>Week-ends exclus</span><strong><?= (int)$apercu['week_ends'] ?></strong></div>
              <div class="dc-calc-row"><span>Jours fériés exclus</span><strong><?= (int)$apercu['feries_ouvres'] ?></strong></div>
              <div class="dc-calc-row dc-calc-row--hl"><span>Durée décomptée</span><span><?= h(conge_fmt_nb($apercu['jours_decomptes'])) ?> j</span></div>
              <div class="dc-calc-row"><span>Reprise du travail</span><strong><?= h(conge_fmt_date($apercu['date_reprise'])) ?></strong></div>
            <?php else: ?>
              <p class="text-muted small mb-0">Renseignez les dates puis cliquez sur « Recalculer l’aperçu ».</p>
            <?php endif; ?>
          </section>
          <section class="card-panel mb-3">
            <h2 class="card-panel__title">Soldes <?= (int)date('Y') ?></h2>
            <?php if ($soldes === []): ?>
              <p class="text-muted small mb-0">Aucun solde ouvert.</p>
            <?php else: foreach ($soldes as $s): ?>
              <div class="mb-2 small">
                <div class="fw-semibold"><?= h($s['libelle']) ?></div>
                <div>Disponible <strong><?= h(conge_fmt_nb($s['jours_disponibles'])) ?></strong>
                  · acquis <?= h(conge_fmt_nb($s['jours_acquis'])) ?>
                  · réservé <?= h(conge_fmt_nb($s['jours_reserves'] ?? 0)) ?>
                  · pris <?= h(conge_fmt_nb($s['jours_pris'])) ?></div>
              </div>
            <?php endforeach; endif; ?>
          </section>
          <section class="card-panel">
            <h2 class="card-panel__title">Règles appliquées</h2>
            <ul class="small mb-0">
              <li>Dépôt → <strong>réservation</strong> du solde (si type à décompte).</li>
              <li>Avis chef (consultatif) → décision RH (les congés d’un RH passent par le DG).</li>
              <li>Refus / annulation → <strong>libération</strong> de la réserve.</li>
              <li>Contrôles : solde, chevauchement, préavis, absences déjà marquées, auto-validation interdite.</li>
            </ul>
          </section>
        </aside>
      </div>
    </main>
  </div>
</div>
</body>
</html>
