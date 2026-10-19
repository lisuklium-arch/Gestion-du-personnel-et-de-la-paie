<?php
require_once __DIR__ . '/../../config/init.php';
exigerRole(['dg']);
require_once __DIR__ . '/donnees_demo_dg.php';

$espace = 'dg';
$active = 'dg_comptes_rh';
$pageTitle = 'Comptes RH';
$sousTitre = 'Activation et désactivation des comptes de rôle Responsable RH';
$scripts = ['gestion.js'];

$choisi = (string) ($_GET['compte'] ?? '');
if (!isset($comptesRh[$choisi])) {
    $choisi = 'beatrice';
}
$compte = $comptesRh[$choisi];
$enAttente = $compte['etat'] === 'attente';
$nbAttente = count(array_filter($comptesRh, fn($c) => $c['etat'] === 'attente'));
$autres = array_filter($comptesRh, fn($c) => $c['etat'] !== 'attente');

include __DIR__ . '/../layout/debut_page.php';
?>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-user-shield" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Comptes de la RH</h2>
          </div>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Comptes de la RH">
            <table class="data-table">
              <caption class="visually-hidden">Comptes de rôle Responsable RH</caption>
              <thead>
                <tr>
                  <th scope="col">Compte</th>
                  <th scope="col">E-mail</th>
                  <th scope="col">État</th>
                  <th scope="col">Dernière connexion</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($comptesRh as $cle => $c):
                  [$texteEtat, $tonEtat] = $etatsCompte[$c['etat']];
                  $classe = $cle === $choisi ? 'is-selected' : ($c['etat'] === 'attente' ? 'is-pending' : ''); ?>
                <tr<?= $classe !== '' ? ' class="' . $classe . '"' : '' ?>>
                  <td data-label="Compte">
                    <div class="cell-person">
                      <span class="avatar avatar--sm" aria-hidden="true"><?= h(initiales($c['nom'])) ?></span>
                      <div>
                        <p class="cell-person__name"><a class="data-table__ref" href="?compte=<?= h($cle) ?>"<?= $cle === $choisi ? ' aria-current="true"' : '' ?>><?= h($c['nom']) ?></a></p>
                        <?php if ($cle === $choisi): ?><p class="cell-person__meta">Sélectionné</p><?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td data-label="E-mail"><?= h($c['email']) ?></td>
                  <td data-label="État"><?= badge($texteEtat, $tonEtat) ?></td>
                  <td data-label="Dernière connexion"><?= $c['derniere'] ? h(dateHeureJournal($c['derniere'])) : 'Jamais' ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="card-panel card-panel--cd-form">
          <div class="card-panel__head card-panel__head--between">
            <div class="d-flex align-items-center gap-3">
              <span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
              <div>
                <h2 class="card-panel__title"><?= $enAttente ? 'Compte en attente d’activation' : 'Détail du compte' ?></h2>
                <?php if ($enAttente): ?><p class="card-panel__sub"><?= $nbAttente ?> demande en attente</p><?php endif; ?>
              </div>
            </div>
          </div>

          <dl class="detail-list">
            <div><dt>Titulaire du dossier</dt><dd><?= h($compte['nom'] . ' • ' . $compte['matricule'] . ' • ' . $compte['poste']) ?></dd></div>
            <div><dt>Rôle demandé</dt><dd><?= h($compte['role']) ?></dd></div>
            <div><dt>Compte créé par</dt><dd><?= h($compte['cree_par'] . ' • ' . dateHeure($compte['cree_le'])) ?></dd></div>
            <div><dt>Dossier employé</dt><dd><?= badge('Actif', 'success', false) ?> <?= h($compte['dept'] . ' • ' . $compte['contrat'] . ($compte['fin'] ? ' au ' . dateCourte($compte['fin']) : '')) ?></dd></div>
          </dl>

          <?php if ($enAttente): ?>
            <div id="activation-confirmation" class="alert alert-success d-none mt-3" role="status" aria-live="polite">
              <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i><span data-confirmation-label></span>
            </div>
            <form data-demo-form data-verrou data-confirmation="activation-confirmation" class="mt-3" novalidate>
              <div class="mb-3">
                <label for="activation-commentaire" class="form-label">Commentaire de supervision <span class="field-tag">Optionnel</span></label>
                <textarea id="activation-commentaire" name="commentaire" class="form-control" rows="3" placeholder="Accès RH contrôlé et confirmé pour la gestion administrative."></textarea>
              </div>
              <div class="app-form-actions">
                <button type="button" class="btn btn-primary" data-message="Compte de <?= h($compte['nom']) ?> activé. Le rôle Responsable RH est accordé et l’action est journalisée."><i class="fa-solid fa-check me-2" aria-hidden="true"></i>Activer le compte</button>
              </div>
            </form>
          <?php endif; ?>
        </section>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-gear" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Gestion des accès existants</h2>
          </div>

          <div id="acces-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
            <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i><span data-confirmation-label></span>
          </div>

          <ul class="access-list">
            <?php foreach ($autres as $c):
              $actif = $c['etat'] === 'actif'; ?>
              <li class="access-list__item" data-compte="<?= h($c['nom']) ?>">
                <?php personne($c['nom'], $c['detail']); ?>
                <span class="badge-status badge-status--dot badge-status--<?= $actif ? 'success' : 'danger' ?>" data-compte-etat><?= $actif ? 'Compte actif' : 'Compte désactivé' ?></span>
                <button type="button" class="btn text-decoration-none <?= $actif ? 'btn-danger-outline' : 'btn-neutral' ?>" data-compte-action="<?= $actif ? 'desactiver' : 'reactiver' ?>">
                  <i class="fa-solid <?= $actif ? 'fa-user-slash' : 'fa-rotate-right' ?> me-2" aria-hidden="true"></i><span data-libelle><?= $actif ? 'Désactiver' : 'Réactiver' ?> <?= h($c['nom']) ?></span>
                </button>
              </li>
            <?php endforeach; ?>
          </ul>

          <div class="note-info" role="note">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span>La désactivation retire l’accès à l’application sans supprimer le compte, le dossier ni l’historique. Toute activation ou désactivation est journalisée.</span>
          </div>
        </section>

<?php include __DIR__ . '/../layout/fin_page.php'; ?>
