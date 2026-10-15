<?php
require_once __DIR__ . '/donnees_demo_rh.php';

$espace = 'rh';
$active = 'rh_paie';
$pageTitle = 'Paie';
$scripts = ['gestion.js'];

$moisListe = ['2026-10' => 'Octobre 2026', '2026-09' => 'Septembre 2026', '2026-08' => 'Août 2026'];
$moisChoisi = (string) ($_GET['mois'] ?? '2026-09');
if (!isset($moisListe[$moisChoisi])) {
    $moisChoisi = '2026-09';
}
$deptChoisi = (string) ($_GET['dept'] ?? '');
$moisTexte = $moisListe[$moisChoisi];
$periodeCalculee = $moisChoisi === '2026-09';

$sousTitre = h($moisTexte) . ' • Tous les montants sont exprimés en FCFA entiers';

$fiches = array_filter($fichesSeptembre, fn($f) => $deptChoisi === '' || $f['dept'] === $deptChoisi);
$restante = $ficheRestante;
$salarie = $annuaire[$restante['emp']];
$netBrouillons = 0;
foreach ($fichesSeptembre as $f) {
    $netBrouillons += $f['etat'] === 'Brouillon' ? $f['net'] : 0;
}
$brouillons = $stats['fiches_calculees'] - $stats['fiches_validees'];

$brutMarie = 0;
$retenuesMarie = 0;
foreach ($controleMarie as [$element, $origine, $montantLigne, $nature]) {
    if ($nature === 'prime') {
        $brutMarie += $montantLigne;
    } else {
        $retenuesMarie += $montantLigne;
    }
}

include __DIR__ . '/../layout/debut_page.php';
?>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Période de paie</h2>
          </div>
          <form method="get" class="filter-bar" aria-label="Choisir la période et le département">
            <div class="filter-bar__field">
              <label for="paie-mois" class="form-label">Mois</label>
              <select id="paie-mois" name="mois" class="form-select" data-autosubmit><?= optionsSelect($moisListe, $moisChoisi) ?></select>
            </div>
            <div class="filter-bar__field">
              <label for="paie-dept" class="form-label">Département</label>
              <select id="paie-dept" name="dept" class="form-select" data-autosubmit>
                <?= optionsSelect(['' => 'Tous les départements'] + array_combine(array_keys($departements), array_keys($departements)), $deptChoisi) ?>
              </select>
            </div>
            <noscript><button type="submit" class="btn btn-neutral">Afficher</button></noscript>
          </form>
        </section>

<?php if (!$periodeCalculee): ?>
        <section class="card-panel">
          <p class="mb-0">Aucune fiche n’est disponible pour <?= h($moisTexte) ?> dans cette démonstration. Les fiches de septembre 2026 sont affichées en choisissant ce mois.</p>
        </section>
<?php else: ?>
        <section class="app-kpis app-kpis--3" aria-label="Chiffres clés de la paie">
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-file-lines" aria-hidden="true"></i></span>
              <p class="kpi-card__label">Fiches calculées</p>
            </div>
            <p class="kpi-card__value kpi-card__value--neutral"><?= (int) $stats['fiches_calculees'] ?></p>
            <p class="kpi-card__meta"><?= (int) $stats['fiches_validees'] ?> validées • <?= $brouillons ?> brouillon<?= $brouillons > 1 ? 's' : '' ?></p>
          </article>
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-wallet" aria-hidden="true"></i></span>
              <p class="kpi-card__label">Net total calculé</p>
            </div>
            <p class="kpi-card__value kpi-card__value--neutral kpi-card__value--money"><?= h(montant($stats['net_septembre'])) ?></p>
            <p class="kpi-card__meta">Dont <?= h(montant($stats['net_septembre'] - $netBrouillons)) ?> validés</p>
          </article>
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
              <p class="kpi-card__label">Brouillon restant</p>
            </div>
            <p class="kpi-card__value kpi-card__value--warning kpi-card__value--money"><?= h(montant($netBrouillons)) ?></p>
            <p class="kpi-card__meta"><?= h($salarie['nom']) ?> • <?= h($salarie['dept']) ?></p>
          </article>
        </section>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Fiches de septembre</h2>
          </div>
          <?php if (!$fiches): ?>
            <p class="mb-0">Aucune fiche pour ce département.</p>
          <?php else: ?>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Fiches de paie de septembre">
              <table class="data-table">
                <caption class="visually-hidden">Fiches de paie de septembre 2026</caption>
                <thead>
                  <tr>
                    <th scope="col">Employé</th>
                    <th scope="col" class="col-amount">Brut FCFA</th>
                    <th scope="col" class="col-amount">Retenues FCFA</th>
                    <th scope="col" class="col-amount">Net FCFA</th>
                    <th scope="col">État</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($fiches as $f): ?>
                  <tr>
                    <td data-label="Employé"><?= h($annuaire[$f['emp']]['nom']) ?></td>
                    <td data-label="Brut FCFA" class="col-amount"><?= h(nombre($f['brut'])) ?></td>
                    <td data-label="Retenues FCFA" class="col-amount"><?= h(nombre($f['retenues'])) ?></td>
                    <td data-label="Net FCFA" class="col-amount"><?= h(nombre($f['net'])) ?></td>
                    <td data-label="État"><?= badge($f['etat'], $f['etat'] === 'Validée' ? 'success' : 'warning', false) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
          <p class="card-panel__note"><?= count($fiches) ?> fiche<?= count($fiches) > 1 ? 's' : '' ?> affichée<?= count($fiches) > 1 ? 's' : '' ?> sur <?= (int) $stats['fiches_calculees'] ?>. Les fiches validées sont verrouillées ; elles ne sont pas régénérées.</p>
        </section>

        <div class="duo">
          <section class="card-panel">
            <div class="card-panel__head card-panel__head--between">
              <h2 class="card-panel__title">Contrôle de Marie — lecture seule</h2>
              <?= badge('Validée', 'success') ?>
            </div>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Détail de la fiche de Marie">
              <table class="data-table">
                <caption class="visually-hidden">Rubriques de la fiche de paie de Marie Ngo Ndjock</caption>
                <thead>
                  <tr>
                    <th scope="col">Élément</th>
                    <th scope="col">Origine</th>
                    <th scope="col" class="col-amount">Montant FCFA</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($controleMarie as [$element, $origine, $montantLigne, $nature]): ?>
                  <tr>
                    <td data-label="Élément"><?= h($element) ?></td>
                    <td data-label="Origine"><?= h($origine) ?></td>
                    <td data-label="Montant FCFA" class="col-amount"><?= $nature === 'retenue' ? '−' : '' ?><?= h(nombre($montantLigne)) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <p class="calc-result mt-3 mb-0">Brut <?= h(nombre($brutMarie)) ?> − retenues <?= h(nombre($retenuesMarie)) ?> = net <?= h(nombre($brutMarie - $retenuesMarie)) ?> FCFA</p>
          </section>

          <section class="card-panel">
            <div class="card-panel__head card-panel__head--between">
              <h2 class="card-panel__title">Fiche restante — <?= h($salarie['nom']) ?></h2>
              <?= badge('Brouillon', 'warning') ?>
            </div>

            <div id="paie-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
              <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i><span data-confirmation-label></span>
            </div>

            <form data-demo-form data-paie data-base="<?= (int) $restante['base'] ?>" data-extras="<?= (int) $restante['hs_montant'] ?>" data-avance="<?= (int) $restante['avance'] ?>" data-confirmation="paie-confirmation" novalidate>
              <div class="mb-3">
                <label for="paie-prime" class="form-label">Prime de fonction (FCFA)</label>
                <input type="number" id="paie-prime" name="prime" class="form-control" min="0" step="1" inputmode="numeric" value="<?= (int) $restante['prime'] ?>">
              </div>
              <div class="mb-3">
                <label for="paie-retenues" class="form-label">Retenues salariales (FCFA)</label>
                <input type="number" id="paie-retenues" name="retenues" class="form-control" min="0" step="1" inputmode="numeric" value="<?= (int) $restante['retenues'] ?>">
              </div>

              <dl class="detail-list mb-3">
                <div><dt>Salaire de base</dt><dd><?= h(montant($restante['base'])) ?></dd></div>
                <div><dt>Heures supplémentaires validées</dt><dd><?= (int) $restante['hs_heures'] ?> h • <?= h(montant($restante['hs_montant'])) ?></dd></div>
                <div><dt>Avances accordées à retenir</dt><dd><?= $restante['avance'] > 0 ? h(montant($restante['avance'])) : 'Aucune' ?></dd></div>
                <div><dt>Résultat calculé</dt><dd class="calc-result"><span data-sortie="brut"></span> brut − <span data-sortie="retenues"></span> retenues = <span data-sortie="net"></span> FCFA nets</dd></div>
              </dl>

              <div class="app-form-actions">
                <button type="button" class="btn btn-primary" data-verrou data-message="Fiche de <?= h($salarie['nom']) ?> générée. Elle sera en lecture seule après validation RH."><i class="fa-solid fa-file-circle-plus me-2" aria-hidden="true"></i>Générer la fiche restante</button>
                <button type="button" class="btn btn-neutral" data-message="Brouillon de <?= h($salarie['nom']) ?> enregistré.">Enregistrer le brouillon</button>
              </div>
            </form>

            <div class="note-info mt-3" role="note">
              <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
              <span>La génération produit la fiche brouillon et ses rubriques. Après contrôle et validation RH, elle devient en lecture seule et le PDF est disponible pour l’employé.</span>
            </div>
          </section>
        </div>
<?php endif; ?>

<?php include __DIR__ . '/../layout/fin_page.php'; ?>
