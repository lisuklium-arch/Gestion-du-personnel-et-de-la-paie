<?php
require_once __DIR__ . '/donnees_demo_cd.php';

$pageTitle = 'Heures supplémentaires';
$active = 'heures';
$sousTitre = h($chef['departement']) . ' • ' . h(dateLongueFr($aujourdhui));
$scripts = ['cd.js'];

$liste = $heuresSup;
uasort($liste, fn($a, $b) => strcmp($b['date'], $a['date']));

$enAttente = array_filter($heuresSup, fn($hs) => $hs['etat'] === 'attente_chef');
$integrees = array_filter($heuresSup, fn($hs) => $hs['etat'] === 'integrees');
$nbAttente = count($enAttente);

include __DIR__ . '/../layout/debut_cd.php';
?>

        <section class="app-kpis" aria-label="Indicateurs des heures supplémentaires">
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-regular fa-hourglass-half" aria-hidden="true"></i></span>
              <p class="kpi-card__label">À valider</p>
            </div>
            <p class="kpi-card__value kpi-card__value--neutral" id="kpi-hs-attente"><?= array_sum(array_column($enAttente, 'h')) ?> h</p>
            <p class="kpi-card__meta" id="kpi-hs-attente-meta"><?= $nbAttente ?> déclaration<?= $nbAttente > 1 ? 's' : '' ?> en attente</p>
          </article>
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-circle-check" aria-hidden="true"></i></span>
              <p class="kpi-card__label">Intégrées à la paie</p>
            </div>
            <p class="kpi-card__value kpi-card__value--neutral" id="kpi-hs-integrees"><?= array_sum(array_column($integrees, 'h')) ?> h</p>
            <p class="kpi-card__meta">Déclarations déjà validées</p>
          </article>
        </section>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Heures supplémentaires de l’équipe</h2>
          </div>

          <div id="hs-feedback" class="alert alert-success d-none" role="status" aria-live="polite"></div>

          <div class="table-scroll" tabindex="0" role="region" aria-label="Heures supplémentaires de l’équipe">
            <table class="data-table" id="table-hs">
              <caption class="visually-hidden">Heures supplémentaires déclarées par l’équipe</caption>
              <thead>
                <tr>
                  <th scope="col">Référence</th>
                  <th scope="col">Employé</th>
                  <th scope="col">Date</th>
                  <th scope="col">Durée</th>
                  <th scope="col">Motif</th>
                  <th scope="col">État</th>
                  <th scope="col" class="col-actions">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($liste as $ref => $hs):
                  $nom = $equipe[$hs['emp']]['nom'];
                  [$etat, $ton] = $etatsHs[$hs['etat']]; ?>
                <tr data-ref="<?= h($ref) ?>" data-h="<?= (int) $hs['h'] ?>">
                  <td data-label="Référence" class="data-table__ref"><?= h($ref) ?></td>
                  <td data-label="Employé"><?php personne($nom); ?></td>
                  <td data-label="Date"><?= h(dateCourte($hs['date'])) ?></td>
                  <td data-label="Durée"><?= (int) $hs['h'] ?> h</td>
                  <td data-label="Motif"><?= h($hs['motif']) ?></td>
                  <td data-label="État"><span class="badge-status badge-status--<?= $ton ?> badge-status--dot js-hs-etat"><?= h($etat) ?></span></td>
                  <td data-label="Action" class="col-actions">
                    <?php if ($hs['etat'] === 'attente_chef'): ?>
                      <button type="button" class="btn btn-outline-primary js-valider-hs" data-ref="<?= h($ref) ?>" data-nom="<?= h($nom) ?>">
                        <i class="fa-solid fa-check me-2" aria-hidden="true"></i>Valider
                      </button>
                    <?php else: ?>
                      <span class="data-table__muted" aria-hidden="true">—</span><span class="visually-hidden">Aucune action</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="note-info mt-3" role="note">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span>Une fois validées, les heures sont transmises à la paie. Pour déclarer des heures à la place d’un membre de l’équipe, utilisez le bouton ci-dessous ou le menu « Actions » de la page Mon équipe.</span>
          </div>
        </section>

        <div class="app-actions">
          <a href="heures_sup_ajout_cd.php" class="btn btn-primary text-decoration-none">
            <i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Ajouter des heures supplémentaires
          </a>
        </div>

<?php include __DIR__ . '/../layout/fin_cd.php'; ?>
