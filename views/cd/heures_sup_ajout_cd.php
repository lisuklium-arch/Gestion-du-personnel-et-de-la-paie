<?php
require_once __DIR__ . '/../../config/init.php';
exigerRole(['chef']);
require_once __DIR__ . '/donnees_demo_cd.php';

$pageTitle = 'Ajouter des heures supplémentaires';
$active = 'heures';
$sousTitre = '<a class="breadcrumb-link" href="heures_sup_cd.php">Heures supplémentaires</a> / Nouvelle déclaration';
$scripts = ['forms.js', 'cd.js'];

$membres = array_filter($equipe, fn($e) => empty($e['chef']));

$choisi = $_GET['emp'] ?? '';
if (!is_string($choisi) || !isset($membres[$choisi])) {
    $choisi = '';
}

include __DIR__ . '/../layout/debut_cd.php';
?>

        <section class="card-panel card-panel--cd-form">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Déclarer des heures pour un membre de l’équipe</h2>
          </div>

          <div id="hs-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
            <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>Heures supplémentaires enregistrées. Elles seront transmises à la paie après validation.
          </div>

          <form id="form-hs" class="needs-validation" novalidate data-confirmation="hs-confirmation">
            <div class="mb-3">
              <label for="hs-employe" class="form-label">Employé</label>
              <select id="hs-employe" name="employe" class="form-select" required>
                <option value="" disabled<?= $choisi === '' ? ' selected' : '' ?>>Choisir un employé</option>
                <?php foreach ($membres as $id => $e): ?>
                  <option value="<?= h($id) ?>"<?= $id === $choisi ? ' selected' : '' ?>><?= h($e['nom']) ?> — <?= h($e['poste']) ?> (<?= h($id) ?>)</option>
                <?php endforeach; ?>
              </select>
              <div class="invalid-feedback">Choisissez l’employé concerné.</div>
            </div>

            <div class="mb-3">
              <label for="hs-date" class="form-label">Date</label>
              <input type="date" id="hs-date" name="date" class="form-control" max="<?= $aujourdhui->format('Y-m-d') ?>"
                     value="<?= $aujourdhui->format('Y-m-d') ?>" required>
              <div class="invalid-feedback">Indiquez une date (pas dans le futur).</div>
            </div>

            <div class="form-row-2">
              <div class="mb-3">
                <label for="hs-debut" class="form-label">Heure de début</label>
                <input type="time" id="hs-debut" name="debut" class="form-control" value="18:00" required>
                <div class="invalid-feedback">Indiquez l’heure de début.</div>
              </div>
              <div class="mb-3">
                <label for="hs-fin" class="form-label">Heure de fin</label>
                <input type="time" id="hs-fin" name="fin" class="form-control" value="20:00" required>
                <div class="invalid-feedback">L’heure de fin doit être après l’heure de début.</div>
              </div>
            </div>

            <div class="mb-3">
              <p class="form-label mb-2" id="hs-duree-label">Durée calculée</p>
              <div class="duration-box" aria-live="polite" aria-labelledby="hs-duree-label">
                <span><i class="fa-regular fa-hourglass-half me-2" aria-hidden="true"></i>Total</span>
                <span class="duration-box__value" id="hs-duree">2 h</span>
              </div>
            </div>

            <div class="mb-3">
              <label for="hs-motif" class="form-label">Motif</label>
              <textarea id="hs-motif" name="motif" class="form-control" rows="3" maxlength="300" required
                        placeholder="Ex. : clôture comptable, rapprochement bancaire…"></textarea>
              <div class="invalid-feedback">Indiquez le motif des heures supplémentaires.</div>
            </div>

            <div class="mb-4">
              <?php $jp = 'hs'; include __DIR__ . '/../layout/justificatifs.php'; ?>
            </div>

            <div class="app-form-actions">
              <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-2" aria-hidden="true"></i>Enregistrer les heures</button>
              <a href="heures_sup_cd.php" class="btn btn-neutral text-decoration-none">Annuler</a>
            </div>
          </form>
        </section>

<?php include __DIR__ . '/../layout/fin_cd.php'; ?>
