<?php
require_once __DIR__ . '/donnees_demo_dg.php';

$espace = 'dg';
$active = 'dg_tableau';
$pageTitle = 'Tableau de bord global';
$scripts = ['gestion.js'];
$sousTitre = 'Supervision de l’entreprise • ' . h($situation);

$anneesPaie = [2026 => '2026', 2025 => '2025'];
$annee = (int) ($_GET['annee'] ?? 2026);
if (!isset($anneesPaie[$annee])) {
    $annee = 2026;
}
$moisPaie = moisPaieDisponibles($annee);
$mois = (int) ($_GET['mois'] ?? $dernierMoisPaie[1]);
if ($mois !== 0 && !in_array($mois, $moisPaie, true)) {
    $mois = end($moisPaie);
}

$synthese = syntheseEntreprise($annee, $mois);
$lignesSynthese = $synthese['lignes'];
$moisActuel = $annee === $dernierMoisPaie[0] && $mois === $dernierMoisPaie[1];
$libellePeriode = $mois > 0 ? MOIS_MIN[$mois] . ' ' . $annee : 'année ' . $annee;
$libelleNet = $mois > 0 ? 'Net ' . MOIS_MIN[$mois] . ' ' . $annee : 'Net cumulé ' . $annee;

$totalEffectif = array_sum(array_column($lignesSynthese, 'effectif'));
$totalNet = array_sum(array_column($lignesSynthese, 'net'));
$detailEffectif = implode(' + ', array_column($lignesSynthese, 'effectif'));
$netBrouillon = $moisActuel ? $stats['net_brouillon'] : 0;

$supAnnee = (int) ($_GET['sup_annee'] ?? 2026);
if (!isset($anneesPaie[$supAnnee])) {
    $supAnnee = 2026;
}
$supMois = (int) ($_GET['sup_mois'] ?? 10);
if ($supMois < 0 || $supMois > 12) {
    $supMois = 0;
}
$periodeSup = sprintf('%04d-%02d', $supAnnee, $supMois);
$lignesSup = array_filter($supervisions, fn($l) => $supMois > 0
    ? substr($l['date'], 0, 7) === $periodeSup
    : substr($l['date'], 0, 4) === (string) $supAnnee);
$enAttenteTotal = array_filter($supervisions, fn($l) => $l['attente']);
$enAttentePeriode = array_filter($lignesSup, fn($l) => $l['attente']);
$horsPeriode = count($enAttenteTotal) - count($enAttentePeriode);

$nbComptes = count(array_filter($comptesRh, fn($c) => $c['etat'] === 'attente'));
$conge = $congeRh;
$demandeuse = $annuaire[$conge['emp']];
$joursConge = joursOuvres($conge['debut'], $conge['fin']);

$optionsMoisPaie = ['0' => 'Toute l’année'];
foreach ($moisPaie as $m) {
    $optionsMoisPaie[(string) $m] = MOIS_NOMS[$m];
}
$optionsMoisSup = ['0' => 'Toute l’année'];
foreach (MOIS_NOMS as $m => $nom) {
    $optionsMoisSup[(string) $m] = $nom;
}

include __DIR__ . '/../layout/debut_page.php';
?>

        <section class="app-kpis app-kpis--wide" aria-label="Chiffres clés">
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
              <p class="kpi-card__label">Employés actifs</p>
            </div>
            <p class="kpi-card__value kpi-card__value--neutral"><?= (int) $totalEffectif ?></p>
            <p class="kpi-card__meta"><?= count($lignesSynthese) ?> départements • <?= h($libellePeriode) ?></p>
          </article>
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-wallet" aria-hidden="true"></i></span>
              <p class="kpi-card__label"><?= h($libelleNet) ?></p>
            </div>
            <p class="kpi-card__value kpi-card__value--neutral kpi-card__value--money"><?= h(montant($totalNet)) ?></p>
            <p class="kpi-card__meta"><?php if ($moisActuel): ?><?= (int) $stats['fiches_calculees'] ?> fiches • <?= (int) $stats['fiches_validees'] ?> validées<?php elseif ($mois > 0): ?><?= (int) $totalEffectif ?> fiches • toutes validées<?php else: ?><?= (int) $synthese['nb_mois'] ?> mois de paie cumulés<?php endif; ?></p>
          </article>
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-solid fa-user-shield" aria-hidden="true"></i></span>
              <p class="kpi-card__label">Comptes RH en attente</p>
            </div>
            <p class="kpi-card__value kpi-card__value--warning"><?= $nbComptes ?></p>
            <p class="kpi-card__meta">Activation à décider</p>
          </article>
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span>
              <p class="kpi-card__label">Congés RH à décider</p>
            </div>
            <p class="kpi-card__value kpi-card__value--warning">1</p>
            <p class="kpi-card__meta"><?= h($demandeuse['nom']) ?> • <?= h(libelleJours($joursConge)) ?></p>
          </article>
        </section>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-sitemap" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Synthèse par département</h2>
          </div>

          <form method="get" class="filter-bar mb-3" aria-label="Période de la synthèse">
            <input type="hidden" name="sup_annee" value="<?= (int) $supAnnee ?>">
            <input type="hidden" name="sup_mois" value="<?= (int) $supMois ?>">
            <div class="filter-bar__field">
              <label for="synthese-annee" class="form-label">Année</label>
              <select id="synthese-annee" name="annee" class="form-select" data-autosubmit>
                <?= optionsSelect($anneesPaie, (string) $annee) ?>
              </select>
            </div>
            <div class="filter-bar__field">
              <label for="synthese-mois" class="form-label">Mois</label>
              <select id="synthese-mois" name="mois" class="form-select" data-autosubmit>
                <?= optionsSelect($optionsMoisPaie, (string) $mois) ?>
              </select>
            </div>
            <noscript><button type="submit" class="btn btn-neutral">Afficher</button></noscript>
          </form>

          <div class="table-scroll" tabindex="0" role="region" aria-label="Synthèse par département">
            <table class="data-table">
              <caption class="visually-hidden">Effectif et net par département pour <?= h($libellePeriode) ?></caption>
              <thead>
                <tr>
                  <th scope="col">Département</th>
                  <th scope="col">Chef désigné</th>
                  <th scope="col">Effectif actif</th>
                  <th scope="col" class="col-amount"><?= h($libelleNet) ?></th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 0; foreach ($lignesSynthese as $nom => $dept): $i++; ?>
                <tr>
                  <td data-label="Département"><span class="dot dot--<?= $i ?>" aria-hidden="true"></span><?= h($nom) ?></td>
                  <td data-label="Chef désigné"><?= h($dept['chef']) ?></td>
                  <td data-label="Effectif actif"><span class="chip chip--success"><?= (int) $dept['effectif'] ?></span></td>
                  <td data-label="<?= h($libelleNet) ?>" class="col-amount"><?= h(montant($dept['net'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="is-total">
                  <td data-label="Département">Total</td>
                  <td data-label="Chef désigné"><?= count($lignesSynthese) ?> départements</td>
                  <td data-label="Effectif actif"><span class="chip chip--success"><?= (int) $totalEffectif ?></span></td>
                  <td data-label="<?= h($libelleNet) ?>" class="col-amount"><?= h(montant($totalNet)) ?></td>
                </tr>
              </tbody>
            </table>
          </div>
          <p class="card-panel__note">
            Total : <?= h($detailEffectif) ?> = <?= (int) $totalEffectif ?> employés actifs<?= $mois === 0 ? ' en fin de période' : '' ?>.
            <?php if ($moisActuel): ?>
              Net calculé : <?= h(montant($totalNet)) ?>, dont <?= h(montant($totalNet - $netBrouillon)) ?> validés et <?= h(montant($netBrouillon)) ?> en brouillon.
            <?php elseif ($mois > 0): ?>
              Net calculé : <?= h(montant($totalNet)) ?>, intégralement validé.
            <?php else: ?>
              Net cumulé sur <?= (int) $synthese['nb_mois'] ?> mois de paie : <?= h(montant($totalNet)) ?>.
            <?php endif; ?>
          </p>
        </section>

        <section class="card-panel">
          <div class="card-panel__head card-panel__head--between">
            <div class="d-flex align-items-center gap-3">
              <span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></span>
              <h2 class="card-panel__title">Supervision</h2>
            </div>
            <?= badge(count($enAttentePeriode) . ' en attente', count($enAttentePeriode) ? 'warning' : 'neutral', false) ?>
          </div>

          <form method="get" class="filter-bar mb-3" aria-label="Période de la supervision">
            <input type="hidden" name="annee" value="<?= (int) $annee ?>">
            <input type="hidden" name="mois" value="<?= (int) $mois ?>">
            <div class="filter-bar__field">
              <label for="sup-annee" class="form-label">Année</label>
              <select id="sup-annee" name="sup_annee" class="form-select" data-autosubmit>
                <?= optionsSelect($anneesPaie, (string) $supAnnee) ?>
              </select>
            </div>
            <div class="filter-bar__field">
              <label for="sup-mois" class="form-label">Mois</label>
              <select id="sup-mois" name="sup_mois" class="form-select" data-autosubmit>
                <?= optionsSelect($optionsMoisSup, (string) $supMois) ?>
              </select>
            </div>
            <noscript><button type="submit" class="btn btn-neutral">Afficher</button></noscript>
          </form>

          <?php if ($lignesSup): ?>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Supervision">
            <table class="data-table">
              <caption class="visually-hidden">Décisions du Directeur général sur la période choisie</caption>
              <thead>
                <tr>
                  <th scope="col">Date</th>
                  <th scope="col">Objet</th>
                  <th scope="col">Cible</th>
                  <th scope="col">État</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($lignesSup as $ligne): ?>
                <tr<?= $ligne['attente'] ? ' class="is-pending"' : '' ?>>
                  <td data-label="Date"><?= h(dateCourte($ligne['date'])) ?></td>
                  <td data-label="Objet"><a class="data-table__ref" href="<?= h($ligne['lien']) ?>"><i class="<?= h($ligne['icone']) ?> me-2" aria-hidden="true"></i><?= h($ligne['objet']) ?></a></td>
                  <td data-label="Cible"><?= h($ligne['cible']) ?><br><span class="data-table__muted"><?= h($ligne['detail']) ?></span></td>
                  <td data-label="État"><?= badge($ligne['etat'], $ligne['ton'], false) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <p class="card-panel__note mb-0">Aucune décision de supervision sur cette période.</p>
          <?php endif; ?>
          <?php if ($horsPeriode > 0): ?>
          <p class="card-panel__note"><?= $horsPeriode ?> décision<?= $horsPeriode > 1 ? 's' : '' ?> en attente <?= $horsPeriode > 1 ? 'figurent' : 'figure' ?> hors de cette période.</p>
          <?php endif; ?>
        </section>

        <div class="app-actions">
          <a href="conges_rh_dg.php" class="btn btn-primary text-decoration-none">Examiner le congé RH</a>
          <a href="comptes_rh_dg.php" class="btn btn-neutral text-decoration-none"><i class="fa-solid fa-user-shield me-2" aria-hidden="true"></i>Gérer les comptes RH</a>
        </div>

        <div class="note-info" role="note">
          <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
          <span>Votre compte DG est réservé à la supervision. Il n’est lié à aucun dossier employé, contrat ni salaire personnel.</span>
        </div>

<?php include __DIR__ . '/../layout/fin_page.php'; ?>
