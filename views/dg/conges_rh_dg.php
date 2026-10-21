<?php
require_once __DIR__ . '/../../config/init.php';
exigerRole(['dg']);
require_once __DIR__ . '/donnees_demo_dg.php';

$espace = 'dg';
$active = 'dg_conges_rh';
$pageTitle = 'Congés des RH';
$sousTitre = 'Directeur général — supervision exclusive';
$scripts = ['gestion.js'];

$d = $congeRh;
$ref = $d['ref'];
$demandeuse = $annuaire[$d['emp']];
$soldes = $employes[$d['emp']]['conges'];
$jours = joursOuvres($d['debut'], $d['fin']);
$disponibles = $soldes['acquis'] - $soldes['pris'] - $soldes['attente'];

$actionEntete = '<p class="header-meta">Dépôt • ' . h(dateFr(substr($d['depose'], 0, 10))) . '<br>' . h(substr($d['depose'], 11, 5)) . '</p>';

$decTitre = 'Décision DG';
$decValider = 'Demande ' . $ref . ' validée. La demandeuse est notifiée et la décision est journalisée.';
$decRefuser = 'Demande ' . $ref . ' refusée. La demandeuse est notifiée avec le motif.';
$decNote = 'La demandeuse est notifiée et la décision est journalisée.';

ob_start();
?>
  <div class="app-stack mt-3">
    <div class="consequence consequence--success">
      <p class="consequence__title">Après validation</p>
      <p><?= $soldes['pris'] + $jours ?> jours pris • 0 en attente • <?= $soldes['acquis'] - $soldes['pris'] - $jours ?> disponibles</p>
    </div>
    <div class="consequence consequence--danger">
      <p class="consequence__title">Après refus</p>
      <p><?= $soldes['pris'] ?> jours pris • 0 en attente • <?= $soldes['acquis'] - $soldes['pris'] ?> disponibles</p>
    </div>
  </div>
<?php
$decApres = ob_get_clean();

include __DIR__ . '/../layout/debut_page.php';
?>

        <div class="note-info note-info--warning" role="note">
          <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
          <span>1 demande en attente • Décision réservée au Directeur général.</span>
        </div>

        <div class="app-split">
          <div class="app-stack">
            <section class="card-panel">
              <div class="card-panel__head card-panel__head--between">
                <div>
                  <h2 class="card-panel__title">Demande <?= h($ref) ?></h2>
                  <p class="card-panel__sub"><?= h($d['type']) ?></p>
                </div>
                <?= badge('En attente DG', 'warning') ?>
              </div>

              <dl class="info-grid">
                <div><dt>Demandeuse</dt><dd><?= h($demandeuse['nom']) ?><span class="info-grid__sub"><?= h($d['emp']) ?> • <?= h($demandeuse['poste']) ?></span></dd></div>
                <div><dt>Département</dt><dd><?= h($demandeuse['dept']) ?><span class="info-grid__sub">Dépôt le <?= h(dateHeure($d['depose'])) ?></span></dd></div>
                <div><dt>Période</dt><dd><?= h(periodeLongueFr($d['debut'], $d['fin'])) ?><span class="info-grid__sub"><?= h(libelleJours($jours, true)) ?></span></dd></div>
                <div><dt>Type de congé</dt><dd><?= h($d['type']) ?></dd></div>
                <div><dt>Motif</dt><dd><?= h($d['motif']) ?></dd></div>
                <div><dt>Organisation du relais</dt><dd><?= h($d['relais']) ?></dd></div>
              </dl>

              <h3 class="form-label mt-4 mb-2">Solde 2026</h3>
              <dl class="solde-grid">
                <div class="solde-grid__item"><dt>Acquis</dt><dd><?= (int) $soldes['acquis'] ?></dd></div>
                <div class="solde-grid__item"><dt>Pris</dt><dd><?= (int) $soldes['pris'] ?></dd></div>
                <div class="solde-grid__item solde-grid__item--warning"><dt>En attente</dt><dd><?= (int) $soldes['attente'] ?></dd></div>
                <div class="solde-grid__item solde-grid__item--success"><dt>Disponibles</dt><dd><?= (int) $disponibles ?></dd></div>
              </dl>
            </section>

            <div class="note-info" role="note">
              <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
              <span>Les congés d’un membre RH sont décidés par le DG seul, sans avis du chef ni décision RH.</span>
            </div>
          </div>

          <?php include __DIR__ . '/../gestion/decision.php'; ?>
        </div>

<?php include __DIR__ . '/../layout/fin_page.php'; ?>
