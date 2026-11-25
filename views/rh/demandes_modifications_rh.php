<?php
require_once __DIR__ . '/../gestion/aides_gestion.php';
require_once __DIR__ . '/../../models/DemandeModification.php';

$demandesModification = (new DemandeModification($pdo))->listerEnAttente();
$espace = 'rh';
$active = 'rh_demandes';
$pageTitle = 'Demandes de modification';
$titrePage = 'Demandes à traiter — Modifications';
$sousTitre = count($demandesModification) . ' demande' . (count($demandesModification) > 1 ? 's' : '') . ' en attente de décision RH';
$scripts = ['gestion.js', 'demandes-modifications-rh.js'];

include __DIR__ . '/../layout/debut_page.php';
?>
        <nav aria-label="Types de demande">
          <ul class="tabs-pills">
            <li><a class="tab-pill" href="?type=conges">Congés</a></li>
            <li><a class="tab-pill is-active" href="?type=modifications" aria-current="page">Modifications (<?= count($demandesModification) ?>)</a></li>
            <li><a class="tab-pill" href="?type=attestations">Attestations</a></li>
            <li><a class="tab-pill" href="?type=avances">Avances</a></li>
          </ul>
        </nav>

        <div id="demande-modification-retour" class="alert d-none" role="status" aria-live="polite"></div>
        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Modifications en attente</h2>
          </div>
          <?php if (!$demandesModification): ?>
            <p class="mb-0">Aucune demande de modification n’est en attente.</p>
          <?php else: ?>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Demandes de modification en attente">
              <table class="data-table">
                <thead><tr><th scope="col">Employé</th><th scope="col">Champ</th><th scope="col">Valeur demandée</th><th scope="col">Motif</th><th scope="col">Décision</th></tr></thead>
                <tbody>
                <?php foreach ($demandesModification as $demande): ?>
                  <tr data-demande-modification="<?= (int) $demande['id'] ?>">
                    <td data-label="Employé"><strong><?= h($demande['prenom'] . ' ' . $demande['nom']) ?></strong><br><span class="data-table__muted"><?= h($demande['matricule'] . ' • ' . $demande['departement'] . ' • ' . $demande['poste']) ?></span></td>
                    <td data-label="Champ"><?= h(['nom' => 'Nom', 'prenom' => 'Prénom', 'matricule' => 'Matricule', 'date_naissance' => 'Date de naissance', 'telephone' => 'Téléphone'][$demande['champ']] ?? $demande['champ']) ?></td>
                    <td data-label="Valeur demandée"><span class="data-table__muted"><?= h((string) $demande['ancienne_valeur']) ?></span> → <?= h($demande['nouvelle_valeur']) ?></td>
                    <td data-label="Motif"><?= h((string) $demande['motif']) ?></td>
                    <td data-label="Décision">
                      <form class="d-inline" data-traiter-demande-modification>
                        <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int) $demande['id'] ?>">
                        <button type="submit" name="decision" value="acceptee" class="btn btn-primary">Accepter</button>
                        <button type="submit" name="decision" value="refusee" class="btn btn-danger-outline">Refuser</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </section>
<?php include __DIR__ . '/../layout/fin_page.php'; ?>
