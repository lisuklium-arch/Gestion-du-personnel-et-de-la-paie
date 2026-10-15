<?php
require_once __DIR__ . '/donnees_demo_rh.php';

$espace = 'rh';
$active = 'rh_journal';
$pageTitle = 'Journal';
$scripts = ['gestion.js'];

$du = (string) ($_GET['du'] ?? '2026-09-24');
$au = (string) ($_GET['au'] ?? '2026-10-09');
$auteurChoisi = (string) ($_GET['auteur'] ?? '');
$actionChoisie = (string) ($_GET['action'] ?? '');

$sousTitre = 'Traçabilité RH • Événements du ' . h(dateFr($du)) . ' au ' . h(dateFr($au));

$entrees = array_filter($journalRh, fn($l) => substr($l['date'], 0, 10) >= $du
    && substr($l['date'], 0, 10) <= $au
    && ($auteurChoisi === '' || $l['auteur'] === $auteurChoisi)
    && ($actionChoisie === '' || $l['action'] === $actionChoisie));

$auteurs = array_unique(array_column($journalRh, 'auteur'));
$actions = array_unique(array_column($journalRh, 'action'));

include __DIR__ . '/../layout/debut_page.php';
?>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-filter" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Filtres</h2>
          </div>
          <form method="get" class="filter-bar" aria-label="Filtrer le journal">
            <div class="filter-bar__field">
              <label for="jr-du" class="form-label">Du</label>
              <input type="date" id="jr-du" name="du" class="form-control" value="<?= h($du) ?>" data-autosubmit>
            </div>
            <div class="filter-bar__field">
              <label for="jr-au" class="form-label">Au</label>
              <input type="date" id="jr-au" name="au" class="form-control" value="<?= h($au) ?>" data-autosubmit>
            </div>
            <div class="filter-bar__field">
              <label for="jr-auteur" class="form-label">Auteur</label>
              <select id="jr-auteur" name="auteur" class="form-select" data-autosubmit>
                <?= optionsSelect(['' => 'Tous les auteurs'] + array_combine($auteurs, $auteurs), $auteurChoisi) ?>
              </select>
            </div>
            <div class="filter-bar__field">
              <label for="jr-action" class="form-label">Action</label>
              <select id="jr-action" name="action" class="form-select" data-autosubmit>
                <?= optionsSelect(['' => 'Toutes les actions RH'] + array_combine($actions, $actions), $actionChoisie) ?>
              </select>
            </div>
            <noscript><button type="submit" class="btn btn-neutral">Filtrer</button></noscript>
          </form>
        </section>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Historique des actions</h2>
          </div>

          <?php if (!$entrees): ?>
            <p class="mb-0">Aucun événement ne correspond à ces filtres.</p>
          <?php else: ?>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Historique des actions">
              <table class="data-table">
                <caption class="visually-hidden">Historique des actions RH</caption>
                <thead>
                  <tr>
                    <th scope="col">Horodatage</th>
                    <th scope="col">Auteur</th>
                    <th scope="col">Action</th>
                    <th scope="col">Cible</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($entrees as $l): ?>
                  <tr>
                    <td data-label="Horodatage"><?= h(dateHeureJournal($l['date'])) ?></td>
                    <td data-label="Auteur"><?= h($l['auteur']) ?></td>
                    <td data-label="Action"><?= h($l['action']) ?></td>
                    <td data-label="Cible"><?= h($l['cible']) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

          <p class="card-panel__note"><?= count($entrees) ?> événement<?= count($entrees) > 1 ? 's' : '' ?> affiché<?= count($entrees) > 1 ? 's' : '' ?> • Les entrées sont en lecture seule, classées de la plus récente à la plus ancienne.</p>
        </section>

        <div class="note-info" role="note">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
          <span>Chaque entrée conserve le compte auteur, la date, l’action et la cible. Les décisions et les dossiers historiques ne sont pas supprimés.</span>
        </div>

<?php include __DIR__ . '/../layout/fin_page.php'; ?>
