<?php
require_once __DIR__ . '/donnees_demo_cd.php';

$pageTitle = 'Mon équipe';
$active = 'equipe';
$sousTitre = h($chef['nom']) . ' • ' . h($chef['departement']) . ' • ' . h(dateLongueFr($aujourdhui));
$scripts = ['forms.js', 'cd.js'];

$demandes = $conges;
uasort($demandes, fn($a, $b) => strcmp($a['debut'], $b['debut']));

include __DIR__ . '/../layout/debut_cd.php';
?>

        <section class="app-kpis app-kpis--3" aria-label="Indicateurs de l’équipe">
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
              <p class="kpi-card__label">Effectif actif</p>
            </div>
            <p class="kpi-card__value kpi-card__value--neutral"><?= (int) $effectif['actifs'] ?></p>
            <p class="kpi-card__meta">Chef inclus</p>
          </article>
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-circle-check" aria-hidden="true"></i></span>
              <p class="kpi-card__label">Présents aujourd’hui</p>
            </div>
            <p class="kpi-card__value kpi-card__value--neutral" id="kpi-presents"><?= (int) $effectif['presents'] ?></p>
            <p class="kpi-card__meta">Statut du jour calculé</p>
          </article>
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-regular fa-sun" aria-hidden="true"></i></span>
              <p class="kpi-card__label">En congé aujourd’hui</p>
            </div>
            <p class="kpi-card__value kpi-card__value--neutral"><?= (int) $effectif['en_conge'] ?></p>
            <p class="kpi-card__meta"><?= h($effectif['en_conge_detail']) ?></p>
          </article>
        </section>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-user-group" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Membres de <?= h($chef['departement']) ?></h2>
          </div>

          <div id="equipe-feedback" class="alert alert-success d-none" role="status" aria-live="polite"></div>

          <div class="table-toolbar">
            <label for="equipe-recherche" class="form-label">Rechercher un membre</label>
            <div class="input-icon">
              <i class="fa-solid fa-magnifying-glass input-icon__left" aria-hidden="true"></i>
              <input type="search" id="equipe-recherche" class="form-control" placeholder="Nom ou matricule"
                     autocomplete="off" data-table-filter="#table-equipe" data-empty-message="#equipe-vide">
            </div>
          </div>

          <div class="table-scroll" tabindex="0" role="region" aria-label="Membres du département">
            <table class="data-table" id="table-equipe">
              <caption class="visually-hidden">Membres du département <?= h($chef['departement']) ?></caption>
              <thead>
                <tr>
                  <th scope="col">Personne</th>
                  <th scope="col">Poste</th>
                  <th scope="col">Statut dossier</th>
                  <th scope="col">Statut du jour</th>
                  <th scope="col" class="col-actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($equipe as $id => $e):
                  $estChef = !empty($e['chef']);
                  $enConge = $e['jour'] === 'conge';
                  [$statut, $ton] = statutJour($e); ?>
                <tr<?= $estChef ? ' class="is-chef"' : '' ?> data-emp="<?= h($id) ?>" data-search="<?= h(strtolower($e['nom'] . ' ' . $id)) ?>">
                  <td data-label="Personne"><?php personne($e['nom'], $id, $estChef); ?></td>
                  <td data-label="Poste"><?= h($e['poste']) ?></td>
                  <td data-label="Statut dossier"><span class="badge-status badge-status--success badge-status--dot"><?= h($e['dossier']) ?></span></td>
                  <td data-label="Statut du jour"><span class="badge-status badge-status--<?= $ton ?> js-statut" data-feminin="<?= !empty($e['f']) ? '1' : '0' ?>"><?= h($statut) ?></span></td>
                  <td data-label="Actions" class="col-actions">
                    <?php if ($estChef): ?>
                      <span class="data-table__muted" aria-hidden="true">—</span><span class="visually-hidden">Aucune action sur votre propre ligne</span>
                    <?php else: ?>
                    <div class="dropdown">
                      <button type="button" class="icon-btn" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}'
                              aria-expanded="false" aria-label="Actions pour <?= h($e['nom']) ?>">
                        <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end row-menu">
                        <li>
                          <button type="button" class="dropdown-item js-absence"<?= $enConge ? ' disabled' : '' ?>
                                  data-bs-toggle="modal" data-bs-target="#modal-absence"
                                  data-id="<?= h($id) ?>" data-nom="<?= h($e['nom']) ?>">
                            <i class="fa-solid fa-user-slash" aria-hidden="true"></i>Marquer <?= !empty($e['f']) ? 'absente' : 'absent' ?><?= $enConge ? ' <small>(en congé)</small>' : '' ?>
                          </button>
                        </li>
                        <li>
                          <a class="dropdown-item" href="heures_sup_ajout_cd.php?emp=<?= urlencode($id) ?>">
                            <i class="fa-regular fa-clock" aria-hidden="true"></i>Ajouter des heures sup.
                          </a>
                        </li>
                      </ul>
                    </div>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <p id="equipe-vide" class="card-panel__note" hidden>Aucun membre ne correspond à cette recherche.</p>
          <p class="card-panel__note"><?= count($equipe) ?> membres affichés sur <?= (int) $effectif['actifs'] ?>. « En congé » décrit la journée, pas le statut du dossier.</p>
        </section>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Absences et demandes</h2>
          </div>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Absences et demandes de congé">
            <table class="data-table">
              <caption class="visually-hidden">Absences et demandes de congé de l’équipe</caption>
              <thead>
                <tr>
                  <th scope="col">Personne</th>
                  <th scope="col">Période</th>
                  <th scope="col">Type</th>
                  <th scope="col">État</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($demandes as $c):
                  [$etat, $ton] = $etatsConge[$c['etat']]; ?>
                <tr>
                  <td data-label="Personne"><?php personne($equipe[$c['emp']]['nom']); ?></td>
                  <td data-label="Période"><?= h(periodeNumerique($c['debut'], $c['fin'])) ?> • <?= h(libelleJours(joursOuvres($c['debut'], $c['fin']))) ?></td>
                  <td data-label="Type"><?= h($c['type']) ?></td>
                  <td data-label="État"><span class="badge-status badge-status--<?= $ton ?> badge-status--dot"><?= h($etat) ?></span></td>
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

        <div class="app-actions">
          <a href="calendrier_equipe_cd.php" class="btn btn-primary text-decoration-none">
            <i class="fa-regular fa-calendar-days me-2" aria-hidden="true"></i>Consulter le calendrier
          </a>
        </div>

        <div class="note-info" role="note">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
          <span>Votre espace personnel reste accessible dans la navigation. Vous pouvez valider les heures supplémentaires de votre équipe uniquement.</span>
        </div>

        <div class="modal fade" id="modal-absence" tabindex="-1" aria-labelledby="modal-absence-titre" aria-hidden="true"
             data-today="<?= $aujourdhui->format('Y-m-d') ?>">
          <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
              <div class="modal-header">
                <h2 class="modal-title h5" id="modal-absence-titre">Marquer une absence</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
              </div>
              <form id="form-absence" class="needs-validation" novalidate data-confirmation="absence-confirmation">
                <div class="modal-body">
                  <p class="mb-3">Employé : <strong id="absence-employe-nom"></strong></p>
                  <input type="hidden" name="employe" id="absence-employe-id" value="">

                  <div id="absence-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
                    <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i><span data-confirmation-label>Absence enregistrée.</span>
                  </div>

                  <div class="mb-3">
                    <label for="absence-date" class="form-label">Date de l’absence</label>
                    <input type="date" id="absence-date" name="date" class="form-control" value="<?= $aujourdhui->format('Y-m-d') ?>" required>
                    <div class="invalid-feedback">Indiquez la date.</div>
                  </div>
                  <div class="mb-3">
                    <label for="absence-motif" class="form-label">Motif</label>
                    <select id="absence-motif" name="motif" class="form-select" required>
                      <option value="" selected disabled>Choisir un motif</option>
                      <option value="maladie">Maladie</option>
                      <option value="injustifiee">Absence injustifiée</option>
                      <option value="personnel">Raison personnelle</option>
                      <option value="autre">Autre</option>
                    </select>
                    <div class="invalid-feedback">Choisissez un motif.</div>
                  </div>
                  <div class="mb-3">
                    <label for="absence-commentaire" class="form-label">Commentaire <span class="field-tag">Optionnel</span></label>
                    <textarea id="absence-commentaire" name="commentaire" class="form-control" rows="2"></textarea>
                  </div>
                  <?php $jp = 'absence'; include __DIR__ . '/../layout/justificatifs.php'; ?>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-2" aria-hidden="true"></i>Enregistrer l’absence</button>
                  <button type="button" class="btn btn-neutral" data-bs-dismiss="modal">Annuler</button>
                </div>
              </form>
            </div>
          </div>
        </div>

<?php include __DIR__ . '/../layout/fin_cd.php'; ?>
