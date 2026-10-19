<?php
require_once __DIR__ . '/../../config/init.php';
exigerRole(['rh']);
require_once __DIR__ . '/donnees_demo_rh.php';

$espace = 'rh';
$active = 'rh_parametres';
$pageTitle = 'Paramètres';
$sousTitre = 'Référentiels de l’entreprise • Départements, postes, contrats et types de congé';
$scripts = ['forms.js', 'gestion.js'];

$codeChoisi = (string) ($_GET['conge'] ?? '');
if (!isset($typesConge[$codeChoisi])) {
    $codeChoisi = (string) array_key_first($typesConge);
}
$typeChoisi = $typesConge[$codeChoisi];

include __DIR__ . '/../layout/debut_page.php';
?>

        <div class="duo">
          <section class="card-panel">
            <div class="card-panel__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-sitemap" aria-hidden="true"></i></span>
              <h2 class="card-panel__title">Départements</h2>
            </div>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Départements">
              <table class="data-table">
                <caption class="visually-hidden">Départements de l’entreprise</caption>
                <thead>
                  <tr><th scope="col">Département</th><th scope="col">Chef désigné</th><th scope="col">État</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($departements as $nom => $dept): ?>
                  <tr>
                    <td data-label="Département"><?= h($nom) ?></td>
                    <td data-label="Chef désigné"><?= h($dept['chef']) ?></td>
                    <td data-label="État"><?= badge($dept['etat'], 'success', false) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </section>

          <section class="card-panel">
            <div class="card-panel__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></span>
              <h2 class="card-panel__title">Postes</h2>
            </div>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Postes">
              <table class="data-table">
                <caption class="visually-hidden">Postes et primes de fonction</caption>
                <thead>
                  <tr><th scope="col">Poste</th><th scope="col">Prime de fonction</th><th scope="col">État</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($postes as $intitule => $prime): ?>
                  <tr>
                    <td data-label="Poste"><?= h($intitule) ?></td>
                    <td data-label="Prime de fonction"><?= h(montant($prime)) ?></td>
                    <td data-label="État"><?= badge('Actif', 'success', false) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </section>
        </div>

        <div class="duo">
          <section class="card-panel">
            <div class="card-panel__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-file-lines" aria-hidden="true"></i></span>
              <h2 class="card-panel__title">Contrats</h2>
            </div>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Types de contrat">
              <table class="data-table">
                <caption class="visually-hidden">Types de contrat</caption>
                <thead>
                  <tr><th scope="col">Type</th><th scope="col">Durée / règle</th><th scope="col">État</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($typesContrat as $nom => $regle): ?>
                  <tr>
                    <td data-label="Type"><?= h($nom) ?></td>
                    <td data-label="Durée / règle"><?= h($regle) ?></td>
                    <td data-label="État"><?= badge('Actif', 'success', false) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </section>

          <section class="card-panel">
            <div class="card-panel__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span>
              <h2 class="card-panel__title">Types de congé</h2>
            </div>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Types de congé">
              <table class="data-table">
                <caption class="visually-hidden">Types de congé</caption>
                <thead>
                  <tr><th scope="col">Type</th><th scope="col">Droit / justificatif</th><th scope="col">État</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($typesConge as $code => $t): ?>
                  <tr<?= $code === $codeChoisi ? ' class="is-selected"' : '' ?>>
                    <td data-label="Type"><a class="data-table__ref" href="?conge=<?= h($code) ?>#modifier-conge"<?= $code === $codeChoisi ? ' aria-current="true"' : '' ?>><?= h($t['libelle']) ?></a></td>
                    <td data-label="Droit / justificatif"><?= h($t['regle']) ?></td>
                    <td data-label="État"><?= badge($t['actif'] ? 'Actif' : 'Inactif', $t['actif'] ? 'success' : 'neutral', false) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </section>
        </div>

        <section class="card-panel card-panel--cd-form" id="modifier-conge">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Modifier le type sélectionné</h2>
          </div>

          <div id="param-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
            <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i><span data-confirmation-label>Paramètres enregistrés.</span>
          </div>

          <form id="form-param" class="needs-validation" novalidate data-confirmation="param-confirmation">
            <input type="hidden" name="code" value="<?= h($codeChoisi) ?>">
            <div class="mb-3">
              <p class="form-label mb-2" id="param-type-label">Type de congé</p>
              <div class="form-readonly" aria-labelledby="param-type-label"><?= h($typeChoisi['libelle']) ?></div>
            </div>
            <div class="mb-3">
              <label for="param-droit" class="form-label">Droit annuel (jours)</label>
              <input type="number" id="param-droit" name="droit_annuel" class="form-control" min="0" max="60" step="0.5" value="<?= h($typeChoisi['droit']) ?>" required>
              <div class="invalid-feedback">Indiquez un nombre de jours entre 0 et 60.</div>
            </div>
            <div class="mb-3">
              <label for="param-etat" class="form-label">État</label>
              <select id="param-etat" name="etat" class="form-select">
                <?= optionsSelect(['1' => 'Actif', '0' => 'Inactif'], $typeChoisi['actif'] ? '1' : '0') ?>
              </select>
            </div>
            <div class="app-form-actions">
              <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-2" aria-hidden="true"></i>Enregistrer les paramètres</button>
            </div>
          </form>

          <div class="note-info mt-3" role="note">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span>Les référentiels utilisés dans un historique sont désactivés, jamais supprimés. Un employé ne peut avoir qu’un seul contrat en cours.</span>
          </div>
        </section>

<?php include __DIR__ . '/../layout/fin_page.php'; ?>
