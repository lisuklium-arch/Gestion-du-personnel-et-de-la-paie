<?php
require_once __DIR__ . '/donnees_demo_cd.php';

$pageTitle = 'Avis sur un congé';
$active = 'avis';
$scripts = ['forms.js', 'cd.js'];

$ref = $_GET['ref'] ?? '';
$c = is_string($ref) ? ($conges[$ref] ?? null) : null;

if ($c === null) {
    http_response_code(404);
    $sousTitre = '<a class="breadcrumb-link" href="avis_conges_cd.php">Employés en congé</a> / Introuvable';
} else {
    $e = $equipe[$c['emp']];
    [$etat, $ton] = $etatsConge[$c['etat']];
    $avisTexte = $c['avis'] === 'favorable' ? 'favorable' : 'défavorable';
    $sousTitre = '<a class="breadcrumb-link" href="avis_conges_cd.php">Employés en congé</a> / ' . h($ref);
}

include __DIR__ . '/../layout/debut_cd.php';
?>

      <?php if ($c === null): ?>
        <section class="card-panel card-panel--cd-form">
          <h2 class="card-panel__title">Demande introuvable</h2>
          <p>Cette demande de congé n’existe pas ou ne fait pas partie de votre équipe.</p>
          <div class="app-actions">
            <a href="avis_conges_cd.php" class="btn btn-neutral text-decoration-none">Retour à la liste</a>
          </div>
        </section>
      <?php else: ?>
        <div class="app-person">
          <span class="avatar" aria-hidden="true"><?= h(initiales($e['nom'])) ?></span>
          <div>
            <p class="app-person__name"><?= h($e['nom']) ?></p>
            <p class="app-person__meta"><?= h($c['emp']) ?> • <?= h($e['poste']) ?> • <?= h($chef['departement']) ?></p>
          </div>
        </div>

        <div class="app-split">
          <div class="app-stack">
            <section class="card-panel">
              <div class="card-panel__head">
                <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span>
                <h2 class="card-panel__title">Demande <?= h($ref) ?></h2>
                <span class="badge-status badge-status--<?= $ton ?> badge-status--dot ms-auto"><?= h($etat) ?></span>
              </div>
              <dl class="info-grid">
                <div><dt>Type de congé</dt><dd><?= h($c['type']) ?></dd></div>
                <div><dt>Période</dt><dd><?= h(periodeLongueFr($c['debut'], $c['fin'])) ?></dd></div>
                <div><dt>Durée</dt><dd><?= h(libelleJours(joursOuvres($c['debut'], $c['fin']), true)) ?></dd></div>
                <div><dt>Déposée le</dt><dd><?= h(dateFr($c['depose'])) ?></dd></div>
                <div><dt>Solde après ce congé</dt><dd><?= (int) $c['solde_apres'] ?> jours</dd></div>
                <div><dt>Motif</dt><dd><?= h($c['motif']) ?></dd></div>
              </dl>
            </section>

            <section class="card-panel">
              <div class="card-panel__head">
                <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-comment-dots" aria-hidden="true"></i></span>
                <h2 class="card-panel__title">Votre avis</h2>
              </div>

              <?php if ($c['avis'] !== null): ?>
                <div class="note-info note-info--success" role="note">
                  <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                  <span>Avis <?= $avisTexte ?> transmis aux RH le <?= h(dateFr($c['avis_le'])) ?>.</span>
                </div>
                <div class="app-actions mt-3">
                  <a href="avis_conges_cd.php" class="btn btn-neutral text-decoration-none">Retour à la liste</a>
                </div>
              <?php else: ?>
                <div id="avis-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
                  <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>Avis transmis aux RH. La décision finale leur appartient.
                </div>
                <form id="form-avis" class="needs-validation" novalidate data-confirmation="avis-confirmation">
                  <input type="hidden" name="ref" value="<?= h($ref) ?>">
                  <fieldset class="mb-3">
                    <legend class="form-label">Votre avis</legend>
                    <div class="avis-choices">
                      <label class="avis-choice"><input type="radio" class="form-check-input mt-0" name="avis" value="favorable" required><span>Avis favorable</span></label>
                      <label class="avis-choice"><input type="radio" class="form-check-input mt-0" name="avis" value="defavorable" required><span>Avis défavorable</span></label>
                    </div>
                    <div class="invalid-feedback">Choisissez un avis.</div>
                  </fieldset>
                  <div class="mb-3">
                    <label for="avis-commentaire" class="form-label">Commentaire <span class="field-tag">Optionnel</span></label>
                    <textarea id="avis-commentaire" name="commentaire" class="form-control" rows="3"></textarea>
                  </div>
                  <div class="app-form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fa-regular fa-paper-plane me-2" aria-hidden="true"></i>Envoyer mon avis</button>
                    <a href="avis_conges_cd.php" class="btn btn-neutral text-decoration-none">Annuler</a>
                  </div>
                </form>
              <?php endif; ?>
            </section>
          </div>

          <section class="card-panel">
            <div class="card-panel__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-diagram-project" aria-hidden="true"></i></span>
              <h2 class="card-panel__title">Circuit de validation</h2>
            </div>
            <ol class="step-list">
              <li class="step-list__item<?= $c['avis'] === null ? ' is-current' : '' ?>">
                <span class="step-list__num">1</span>
                <div><p class="step-list__name"><?= h($chef['nom']) ?></p><p class="step-list__role">Chef <?= h($chef['departement']) ?> • Avis</p></div>
              </li>
              <li class="step-list__item<?= $c['avis'] !== null && $c['etat'] === 'attente_rh' ? ' is-current' : '' ?>">
                <span class="step-list__num">2</span>
                <div><p class="step-list__name">Responsable RH</p><p class="step-list__role">Décision finale</p></div>
              </li>
            </ol>
          </section>
        </div>
      <?php endif; ?>

<?php include __DIR__ . '/../layout/fin_cd.php'; ?>
