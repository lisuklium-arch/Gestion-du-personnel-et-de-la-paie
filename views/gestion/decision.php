<?php
$decTitre = $decTitre ?? 'Décision RH';
$decAvant = $decAvant ?? '';
$decApres = $decApres ?? '';
$decNote = $decNote ?? '';
?>
<section class="card-panel">
  <div class="card-panel__head">
    <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-gavel" aria-hidden="true"></i></span>
    <h2 class="card-panel__title"><?= h($decTitre) ?></h2>
  </div>

  <div id="decision-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
    <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i><span data-confirmation-label></span>
  </div>

  <form data-demo-form data-verrou data-confirmation="decision-confirmation" novalidate>
    <?= $decAvant ?>
    <div class="mb-3">
      <label for="decision-commentaire" class="form-label">Commentaire de décision <span class="field-tag">Optionnel</span></label>
      <textarea id="decision-commentaire" name="commentaire" class="form-control" rows="3"></textarea>
    </div>
    <div class="mb-3">
      <label for="decision-motif" class="form-label">Motif de refus <span class="field-tag">Obligatoire en cas de refus</span></label>
      <textarea id="decision-motif" name="motif_refus" class="form-control" rows="3" placeholder="Renseigner un motif si la demande est refusée."></textarea>
      <div class="invalid-feedback">Indiquez le motif du refus.</div>
    </div>
    <div class="app-form-actions">
      <button type="button" class="btn btn-primary" data-message="<?= h($decValider) ?>"><i class="fa-solid fa-check me-2" aria-hidden="true"></i>Valider</button>
      <button type="button" class="btn btn-danger-outline" data-message="<?= h($decRefuser) ?>" data-requis="#decision-motif"><i class="fa-solid fa-xmark me-2" aria-hidden="true"></i>Refuser</button>
    </div>
  </form>

  <?= $decApres ?>

  <?php if ($decNote !== ''): ?>
    <div class="note-info mt-3" role="note">
      <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
      <span><?= h($decNote) ?></span>
    </div>
  <?php endif; ?>
</section>
