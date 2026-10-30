<?php
require_once __DIR__ . '/../../config/init.php';
exigerRole(['dg']);
require_once __DIR__ . '/../gestion/aides_gestion.php';
require_once __DIR__ . '/../gestion/bouton_compte.php';
require_once __DIR__ . '/../../models/Utilisateur.php';
require_once __DIR__ . '/../../services/CompteService.php';

$espace = 'dg';
$active = 'dg_comptes_rh';
$pageTitle = 'Comptes RH';
$sousTitre = 'Activation et désactivation des comptes de rôle Responsable RH';
$scripts = ['gestion.js'];

$recherche = trim((string) ($_GET['q'] ?? ''));
$statutChoisi = (string) ($_GET['statut'] ?? '');
if (!in_array($statutChoisi, ['en_attente', 'actif', 'desactive'], true)) {
    $statutChoisi = '';
}
$statutsListe = ['' => 'Tous les états', 'en_attente' => 'En attente', 'actif' => 'Actif', 'desactive' => 'Désactivé'];

$auteur = ['id' => (int) $_SESSION['utilisateur_id'], 'role' => (string) $_SESSION['role']];
$utilisateurs = new Utilisateur($pdo);
$comptesRh = $utilisateurs->rechercherComptes(['roles' => ['rh'], 'q' => $recherche, 'statut_compte' => $statutChoisi]);
$totalRh = count($utilisateurs->rechercherComptes(['roles' => ['rh']]));
foreach ($comptesRh as &$c) {
    $c['nom_affiche'] = $c['nom'] !== null ? $c['prenom'] . ' ' . $c['nom'] : $c['email'];
}
unset($c);

$filtres = array_filter(['q' => $recherche, 'statut' => $statutChoisi], fn($v) => $v !== '');
$compte = null;
$choisi = (int) ($_GET['compte'] ?? 0);
foreach ($comptesRh as $c) {
    if ((int) $c['id'] === $choisi) { $compte = $c; break; }
}
$compte ??= $comptesRh[0] ?? null;
$choisi = $compte ? (int) $compte['id'] : 0;
$enAttente = $compte && $compte['statut_compte'] === 'en_attente';
$nbAttente = count(array_filter($comptesRh, fn($c) => $c['statut_compte'] === 'en_attente'));

include __DIR__ . '/../layout/debut_page.php';
?>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Rechercher et filtrer</h2>
          </div>
          <form method="get" class="filter-bar" aria-label="Filtrer les comptes RH">
            <div class="filter-bar__field">
              <label for="rh-recherche" class="form-label">Recherche</label>
              <input type="search" id="rh-recherche" name="q" class="form-control" value="<?= h($recherche) ?>" placeholder="Nom, matricule ou e-mail" autocomplete="off">
            </div>
            <div class="filter-bar__field">
              <label for="rh-statut" class="form-label">État du compte</label>
              <select id="rh-statut" name="statut" class="form-select" data-autosubmit>
                <?= optionsSelect($statutsListe, $statutChoisi) ?>
              </select>
            </div>
            <noscript><button type="submit" class="btn btn-neutral">Filtrer</button></noscript>
          </form>
        </section>

        <div class="app-actions">
          <a href="compte_creation_dg.php" class="btn btn-primary text-decoration-none"><i class="fa-solid fa-user-plus me-2" aria-hidden="true"></i>Créer un compte</a>
          <a href="comptes_rh_dg.php" class="btn btn-neutral text-decoration-none">Réinitialiser les filtres</a>
        </div>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-user-shield" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Comptes de la RH</h2>
          </div>

          <div id="acces-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
            <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i><span data-confirmation-label></span>
          </div>
          <div id="acces-erreur" class="alert alert-danger d-none" role="alert"></div>

          <?php if (!$comptesRh): ?>
            <p class="mb-0">Aucun compte Responsable RH ne correspond à ces filtres.</p>
          <?php else: ?>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Comptes de la RH">
            <table class="data-table">
              <caption class="visually-hidden">Comptes de rôle Responsable RH</caption>
              <thead>
                <tr>
                  <th scope="col">Compte</th>
                  <th scope="col">E-mail</th>
                  <th scope="col">État</th>
                  <th scope="col">Dernière connexion</th>
                  <th scope="col">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($comptesRh as $c):
                  [$texteEtat, $tonEtat] = etatCompte($c['statut_compte']);
                  $cle = (int) $c['id'];
                  $classe = $cle === $choisi ? 'is-selected' : ($c['statut_compte'] === 'en_attente' ? 'is-pending' : '');
                  $action = CompteService::actionPossible($auteur, $c); ?>
                <tr<?= $classe !== '' ? ' class="' . $classe . '"' : '' ?> data-compte="<?= h($c['nom_affiche']) ?>" data-id="<?= $cle ?>">
                  <td data-label="Compte">
                    <div class="cell-person">
                      <span class="avatar avatar--sm" aria-hidden="true"><?= h(initiales($c['nom_affiche'])) ?></span>
                      <div>
                        <p class="cell-person__name"><a class="data-table__ref" href="?<?= h(http_build_query($filtres + ['compte' => $cle])) ?>#detail-compte"<?= $cle === $choisi ? ' aria-current="true"' : '' ?>><?= h($c['nom_affiche']) ?></a></p>
                        <?php if ($cle === $choisi): ?><p class="cell-person__meta">Sélectionné</p><?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td data-label="E-mail"><?= h($c['email']) ?></td>
                  <td data-label="État"><span class="badge-status badge-status--dot badge-status--<?= h($tonEtat) ?>" data-compte-etat><?= h($texteEtat) ?></span></td>
                  <td data-label="Dernière connexion"><?= $c['derniere_connexion'] ? h(dateHeureJournal($c['derniere_connexion'])) : 'Jamais' ?></td>
                  <td data-label="Action"><?= boutonCompte($action, $c['nom_affiche']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>

          <p class="card-panel__note"><?= count($comptesRh) ?> compte<?= count($comptesRh) > 1 ? 's' : '' ?> affiché<?= count($comptesRh) > 1 ? 's' : '' ?> sur <?= $totalRh ?>. La désactivation retire l’accès à l’application sans supprimer le compte, le dossier ni l’historique ; toute activation ou désactivation est journalisée.</p>
        </section>

        <?php if ($compte): ?>
        <section id="detail-compte" class="card-panel card-panel--cd-form">
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
            <div><dt>Titulaire du dossier</dt><dd><?= h($compte['nom_affiche'] . ' • ' . ($compte['matricule'] ?? '—') . ' • ' . ($compte['poste'] ?? '—')) ?></dd></div>
            <div><dt>Rôle demandé</dt><dd>Responsable RH</dd></div>
            <div><dt>Compte créé par</dt><dd><?= h(($compte['cree_par_email'] ?? 'Non renseigné') . ($compte['cree_le'] ? ' • ' . dateHeure($compte['cree_le']) : '')) ?></dd></div>
            <div><dt>Dossier employé</dt><dd><?= $compte['matricule'] ? badge('Actif', 'success', false) . ' ' . h(($compte['departement'] ?? '') . ' • ' . ($compte['type_contrat'] ?? 'Sans contrat') . ($compte['date_fin'] ? ' au ' . dateCourte($compte['date_fin']) : '')) : 'Aucun dossier employé' ?></dd></div>
          </dl>

          <?php if ($enAttente): ?>
            <div id="activation-confirmation" class="alert alert-success d-none mt-3" role="status" aria-live="polite">
              <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i><span data-confirmation-label></span>
            </div>
            <form data-activation data-id="<?= $choisi ?>" data-nom="<?= h($compte['nom_affiche']) ?>" data-confirmation="activation-confirmation" class="mt-3" novalidate>
              <div id="activation-erreur" class="alert alert-danger d-none" role="alert"></div>
              <div class="mb-3">
                <label for="activation-commentaire" class="form-label">Commentaire de supervision <span class="field-tag">Optionnel</span></label>
                <textarea id="activation-commentaire" name="commentaire" class="form-control" rows="3" maxlength="500" placeholder="Accès RH contrôlé et confirmé pour la gestion administrative."></textarea>
              </div>
              <div class="app-form-actions">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-2" aria-hidden="true"></i>Activer le compte</button>
              </div>
            </form>
          <?php endif; ?>
        </section>
        <?php endif; ?>

<?php include __DIR__ . '/../layout/fin_page.php'; ?>
