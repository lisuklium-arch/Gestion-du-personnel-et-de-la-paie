<?php
require_once __DIR__ . '/../../config/init.php';
exigerRole(['rh']);
require_once __DIR__ . '/../gestion/aides_journal.php';

$espace = 'rh';
$active = 'rh_journal';
$pageTitle = 'Journal';
$scripts = ['gestion.js'];

$aujourdhui = new DateTime('today');
$du = dateValide((string) ($_GET['du'] ?? ''), (clone $aujourdhui)->modify('-30 days')->format('Y-m-d'));
$au = dateValide((string) ($_GET['au'] ?? ''), $aujourdhui->format('Y-m-d'));
$auteurChoisi = (string) ($_GET['auteur'] ?? '');
$actionChoisie = (string) ($_GET['action'] ?? '');

$sousTitre = 'Traçabilité RH • Événements du ' . h(dateFr($du)) . ' au ' . h(dateFr($au));

$lecture = (new JournalService(new JournalAction($pdo)))->lire('rh', [
    'du' => $du, 'au' => $au, 'action' => $actionChoisie,
    'auteur_id' => ctype_digit($auteurChoisi) ? (int) $auteurChoisi : 0,
    'page' => (int) ($_GET['page'] ?? 1),
]);
$entrees = $lecture['lignes'];

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
                <?= optionsAuteurs($lecture['auteurs'], $auteurChoisi, 'Tous les auteurs') ?>
              </select>
            </div>
            <div class="filter-bar__field">
              <label for="jr-action" class="form-label">Action</label>
              <select id="jr-action" name="action" class="form-select" data-autosubmit>
                <?php $optionsActions = ['' => 'Toutes les actions RH'];
                foreach ($lecture['actions'] as $codeAction) { $optionsActions[$codeAction] = actionJournal($codeAction)[0]; } ?>
                <?= optionsSelect($optionsActions, $actionChoisie) ?>
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
                    <td data-label="Horodatage"><?= h(dateHeureJournal($l['date_heure'])) ?></td>
                    <td data-label="Auteur"><?= h(nomAuteurJournal($l)) ?></td>
                    <td data-label="Action"><?= h(actionJournal($l['action'])[0]) ?></td>
                    <td data-label="Cible"><?= h(cibleJournal($l)) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

          <p class="card-panel__note"><?= (int) $lecture['total'] ?> événement<?= $lecture['total'] > 1 ? 's' : '' ?> • Les entrées sont en lecture seule, classées de la plus récente à la plus ancienne.</p>
          <?php pagination($lecture); ?>
        </section>

        <div class="note-info" role="note">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
          <span>Chaque entrée conserve le compte auteur, la date, l’action et la cible. Les décisions et les dossiers historiques ne sont pas supprimés.</span>
        </div>

<?php include __DIR__ . '/../layout/fin_page.php'; ?>
