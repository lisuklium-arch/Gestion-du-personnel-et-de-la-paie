<?php
require_once __DIR__ . '/donnees_demo_rh.php';

$espace = 'rh';
$active = 'rh_tableau';
$pageTitle = 'Tableau de bord';
$titrePage = 'Bonjour, ' . $rh['prenom'] . ' !';
$sousTitre = h($rh['nom']) . ' • Situation au ' . h(dateFr($aujourdhui->format('Y-m-d')));
$actionEntete = '<a href="compte_creation_rh.php" class="btn btn-outline-primary text-decoration-none"><i class="fa-solid fa-user-plus me-2" aria-hidden="true"></i>Nouvel employé</a>';

$totalDemandes = array_sum(array_column($demandesParType, 'nombre'));
$maxDemandes = max(array_column($demandesParType, 'nombre'));
$detailDemandes = implode(' • ', array_map(fn($d) => $d['nombre'] . ' ' . $d['pluriel'], $demandesParType));
$pourcentValidees = (int) round($stats['fiches_validees'] / $stats['fiches_calculees'] * 100);
$brouillons = $stats['fiches_calculees'] - $stats['fiches_validees'];

include __DIR__ . '/../layout/debut_page.php';
?>

        <section class="app-kpis app-kpis--wide" aria-label="Chiffres clés">
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
              <p class="kpi-card__label">Employés actifs</p>
            </div>
            <p class="kpi-card__value kpi-card__value--neutral"><?= (int) $stats['employes_actifs'] ?></p>
            <p class="kpi-card__meta"><?= (int) $stats['departements'] ?> départements</p>
          </article>
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm icon-tile--danger"><i class="fa-solid fa-inbox" aria-hidden="true"></i></span>
              <p class="kpi-card__label">Demandes à traiter</p>
            </div>
            <p class="kpi-card__value kpi-card__value--danger"><?= $totalDemandes ?></p>
            <p class="kpi-card__meta"><?= h($detailDemandes) ?></p>
          </article>
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span>
              <p class="kpi-card__label">Contrats à échéance</p>
            </div>
            <p class="kpi-card__value kpi-card__value--warning"><?= (int) $stats['contrats_echeance'] ?></p>
            <p class="kpi-card__meta">Dans les 30 prochains jours</p>
          </article>
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-wallet" aria-hidden="true"></i></span>
              <p class="kpi-card__label">Net de septembre</p>
            </div>
            <p class="kpi-card__value kpi-card__value--neutral kpi-card__value--money"><?= h(montant($stats['net_septembre'])) ?></p>
            <p class="kpi-card__meta"><?= (int) $stats['fiches_calculees'] ?> fiches calculées • <?= (int) $stats['fiches_validees'] ?> validées</p>
          </article>
        </section>

        <div class="duo">
          <section class="card-panel">
            <div class="card-panel__head">
              <span class="icon-tile icon-tile--sm icon-tile--danger"><i class="fa-solid fa-inbox" aria-hidden="true"></i></span>
              <div>
                <h2 class="card-panel__title">Demandes à traiter</h2>
                <p class="card-panel__sub"><?= $totalDemandes ?> demandes en attente RH</p>
              </div>
            </div>
            <ul class="bar-list">
              <?php foreach ($demandesParType as $type => $d): ?>
              <li class="bar-row">
                <a class="breadcrumb-link" href="demandes_rh.php?type=<?= h($type) ?>"><?= h($d['libelle']) ?></a>
                <span class="bar-track" aria-hidden="true"><span class="bar-fill" style="width: <?= round($d['nombre'] / $maxDemandes * 100) ?>%"></span></span>
                <span class="bar-row__value"><?= (int) $d['nombre'] ?></span>
              </li>
              <?php endforeach; ?>
            </ul>
          </section>

          <section class="card-panel">
            <div class="card-panel__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-wallet" aria-hidden="true"></i></span>
              <h2 class="card-panel__title">Paie de septembre 2026</h2>
            </div>
            <p class="stat-big"><?= (int) $stats['fiches_validees'] ?> / <?= (int) $stats['fiches_calculees'] ?></p>
            <p class="stat-line mt-2"><?= (int) $stats['fiches_validees'] ?> fiches validées • <?= $brouillons ?> brouillon<?= $brouillons > 1 ? 's' : '' ?></p>
            <div class="bar-track bar-track--lg my-3" role="progressbar" aria-label="Fiches validées" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $pourcentValidees ?>">
              <span class="bar-fill" style="width: <?= $pourcentValidees ?>%"></span>
            </div>
            <p class="stat-line stat-line--strong mb-0"><?= h(montant($stats['net_septembre'])) ?> nets calculés</p>
          </section>
        </div>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Demandes prioritaires</h2>
          </div>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Demandes prioritaires">
            <table class="data-table">
              <caption class="visually-hidden">Demandes en attente de traitement par les RH</caption>
              <thead>
                <tr>
                  <th scope="col">Demande</th>
                  <th scope="col">Demandeur</th>
                  <th scope="col">Reçue le</th>
                  <th scope="col">Étape</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($demandesPrioritaires as $d): ?>
                <tr>
                  <td data-label="Demande"><a class="data-table__ref" href="demandes_rh.php?type=<?= h($d['type']) ?>"><?= h($d['ref']) ?></a> • <?= h($d['objet']) ?></td>
                  <td data-label="Demandeur"><?= h($d['demandeur']) ?></td>
                  <td data-label="Reçue le"><?= h(dateCourte($d['recue'])) ?></td>
                  <td data-label="Étape"><span class="badge-status badge-status--warning badge-status--dot">En attente RH</span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>

        <div class="app-actions">
          <a href="demandes_rh.php" class="btn btn-primary text-decoration-none">Traiter les demandes</a>
          <a href="paie_rh.php" class="btn btn-neutral text-decoration-none">Ouvrir la paie</a>
        </div>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Contrats à surveiller</h2>
          </div>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Contrats à surveiller">
            <table class="data-table">
              <caption class="visually-hidden">Contrats arrivant à échéance</caption>
              <thead>
                <tr>
                  <th scope="col">Employé</th>
                  <th scope="col">Département</th>
                  <th scope="col">Contrat</th>
                  <th scope="col">Échéance</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($contratsASurveiller as $c):
                  $jours = (int) $aujourdhui->diff(new DateTime($c['echeance']))->days; ?>
                <tr>
                  <td data-label="Employé"><?php personne($c['nom']); ?></td>
                  <td data-label="Département"><?= h($c['departement']) ?></td>
                  <td data-label="Contrat"><span class="chip chip--success"><?= h($c['contrat']) ?></span></td>
                  <td data-label="Échéance"><span class="due due--<?= $jours <= 14 ? 'danger' : 'warning' ?>"><?= h(dateCourte($c['echeance'])) ?></span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>

        <div class="note-info" role="note">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
          <span>Les demandes de congé de la RH sont adressées au Directeur général. Retrouvez votre dossier personnel dans la navigation.</span>
        </div>

<?php include __DIR__ . '/../layout/fin_page.php'; ?>
