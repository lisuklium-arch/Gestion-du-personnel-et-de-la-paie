<?php
require_once __DIR__ . '/donnees_demo_cd.php';

$pageTitle = 'Avis sur un congé';
$active = 'avis';

$liste = $conges;
uasort($liste, fn($a, $b) => strcmp($a['debut'], $b['debut']));

$sansAvis = count(array_filter($conges, fn($c) => $c['avis'] === null));
$sousTitre = h($chef['departement']) . ' • ' . $sansAvis . ' avis à donner';

include __DIR__ . '/../layout/debut_cd.php';
?>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-regular fa-sun" aria-hidden="true"></i></span>
            <div>
              <h2 class="card-panel__title">Employés en congé</h2>
              <p class="card-panel__sub">Congés en cours et demandes de votre équipe</p>
            </div>
          </div>

          <div class="table-scroll" tabindex="0" role="region" aria-label="Employés en congé">
            <table class="data-table">
              <caption class="visually-hidden">Employés en congé ou ayant une demande de congé</caption>
              <thead>
                <tr>
                  <th scope="col">Employé</th>
                  <th scope="col">Type de congé</th>
                  <th scope="col">Durée</th>
                  <th scope="col">Période</th>
                  <th scope="col">État</th>
                  <th scope="col" class="col-actions">Avis</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($liste as $ref => $c):
                  [$etat, $ton] = $etatsConge[$c['etat']];
                  $lien = 'avis_conge_detail_cd.php?ref=' . urlencode($ref); ?>
                <tr>
                  <td data-label="Employé"><?php personne($equipe[$c['emp']]['nom'], $c['emp']); ?></td>
                  <td data-label="Type de congé"><?= h($c['type']) ?></td>
                  <td data-label="Durée"><?= h(libelleJours(joursOuvres($c['debut'], $c['fin']), true)) ?></td>
                  <td data-label="Période"><?= h(periodeFr($c['debut'], $c['fin'])) ?></td>
                  <td data-label="État"><span class="badge-status badge-status--<?= $ton ?> badge-status--dot"><?= h($etat) ?></span></td>
                  <td data-label="Avis" class="col-actions">
                    <?php if ($c['avis'] === null): ?>
                      <a class="btn btn-outline-primary text-decoration-none" href="<?= $lien ?>">
                        <i class="fa-regular fa-pen-to-square me-2" aria-hidden="true"></i>Donner un avis
                      </a>
                    <?php else: ?>
                      <a class="btn btn-neutral text-decoration-none" href="<?= $lien ?>">
                        <i class="fa-regular fa-eye me-2" aria-hidden="true"></i>Consulter
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="note-info note-info--warning mt-3" role="note">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <span>Le chef émet un avis ; la décision finale appartient aux RH.</span>
          </div>
        </section>

<?php include __DIR__ . '/../layout/fin_cd.php'; ?>
