<?php
require_once __DIR__ . '/../../config/init.php';
exigerRole(['dg']);
require_once __DIR__ . '/../gestion/aides_journal.php';

$espace = 'dg';
$active = 'dg_journal';
$pageTitle = 'Journal';
$sousTitre = 'Journal de supervision • Comptes RH, congés RH et sécurité';
$scripts = ['gestion.js'];

$aujourdhui = new DateTime('today');
$du = dateValide((string) ($_GET['du'] ?? ''), (clone $aujourdhui)->modify('-30 days')->format('Y-m-d'));
$au = dateValide((string) ($_GET['au'] ?? ''), $aujourdhui->format('Y-m-d'));
$auteurChoisi = (string) ($_GET['auteur'] ?? '');
$typeChoisi = (string) ($_GET['type'] ?? '');
if (!isset(JournalService::TYPES_DG[$typeChoisi])) {
    $typeChoisi = '';
}

$lecture = (new JournalService(new JournalAction($pdo)))->lire('dg', [
    'du' => $du, 'au' => $au, 'type' => $typeChoisi,
    'auteur_id' => ctype_digit($auteurChoisi) ? (int) $auteurChoisi : 0,
    'page' => (int) ($_GET['page'] ?? 1),
]);
$entrees = $lecture['lignes'];
$typesEvenement = JournalService::TYPES_DG;

include __DIR__ . '/../layout/debut_page.php';
?>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-filter" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Filtres de supervision</h2>
          </div>
          <form method="get" class="filter-bar" aria-label="Filtrer le journal de supervision">
            <div class="filter-bar__field">
              <label for="jd-du" class="form-label">Du</label>
              <input type="date" id="jd-du" name="du" class="form-control" value="<?= h($du) ?>" data-autosubmit>
            </div>
            <div class="filter-bar__field">
              <label for="jd-au" class="form-label">Au</label>
              <input type="date" id="jd-au" name="au" class="form-control" value="<?= h($au) ?>" data-autosubmit>
            </div>
            <div class="filter-bar__field">
              <label for="jd-auteur" class="form-label">Auteur</label>
              <select id="jd-auteur" name="auteur" class="form-select" data-autosubmit>
                <?= optionsAuteurs($lecture['auteurs'], $auteurChoisi, 'Tous les auteurs') ?>
              </select>
            </div>
            <div class="filter-bar__field">
              <label for="jd-type" class="form-label">Type d’événement</label>
              <select id="jd-type" name="type" class="form-select" data-autosubmit><?= optionsSelect($typesEvenement, $typeChoisi) ?></select>
            </div>
            <noscript><button type="submit" class="btn btn-neutral">Filtrer</button></noscript>
          </form>
        </section>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-list" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Événements de supervision</h2>
          </div>

          <?php if (!$entrees): ?>
            <p class="mb-0">Aucun événement ne correspond à ces filtres.</p>
          <?php else: ?>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Événements de supervision">
              <table class="data-table">
                <caption class="visually-hidden">Événements de supervision</caption>
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
                    <?php [$libelleAction, $tonAction] = actionJournal($l['action']); ?>
                    <td data-label="Horodatage"><?= h(dateHeureJournal($l['date_heure'])) ?></td>
                    <td data-label="Auteur"><?= h(nomAuteurJournal($l)) ?></td>
                    <td data-label="Action"><?= badge($libelleAction, $tonAction, false) ?></td>
                    <td data-label="Cible"><?= h(cibleJournal($l)) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

          <p class="card-panel__note"><i class="fa-solid fa-lock me-1" aria-hidden="true"></i><?= (int) $lecture['total'] ?> événement<?= $lecture['total'] > 1 ? 's' : '' ?> de supervision • Lecture seule • Ordre chronologique décroissant.</p>
          <?php pagination($lecture); ?>
        </section>

        <div class="note-info" role="note">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
          <span>Les comptes ne sont jamais supprimés. L’auteur, l’horodatage, l’action et la cible de chaque événement restent conservés.</span>
        </div>

<?php include __DIR__ . '/../layout/fin_page.php'; ?>
