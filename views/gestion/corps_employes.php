<?php
require_once __DIR__ . '/bouton_compte.php';
require_once __DIR__ . '/../../models/Utilisateur.php';
require_once __DIR__ . '/../../services/CompteService.php';

$recherche = trim((string) ($_GET['q'] ?? ''));
$deptChoisi = (string) ($_GET['dept'] ?? '');
$posteChoisi = (string) ($_GET['poste'] ?? '');
$statutChoisi = (string) ($_GET['statut'] ?? '');
$compteChoisi = (string) ($_GET['compte'] ?? '');

$auteur = ['id' => (int) $_SESSION['utilisateur_id'], 'role' => (string) $_SESSION['role']];
$listeDepts = $pdo->query('SELECT id, nom FROM departement ORDER BY nom')->fetchAll(PDO::FETCH_KEY_PAIR);
$listePostes = $pdo->query('SELECT id, intitule FROM poste ORDER BY intitule')->fetchAll(PDO::FETCH_KEY_PAIR);

$utilisateurs = new Utilisateur($pdo);
$liste = $utilisateurs->rechercherComptes([
    'avec_dossier' => true,
    'q' => $recherche,
    'departement_id' => isset($listeDepts[$deptChoisi]) ? $deptChoisi : '',
    'poste_id' => isset($listePostes[$posteChoisi]) ? $posteChoisi : '',
    'statut_dossier' => isset(STATUTS_DOSSIER[$statutChoisi]) ? $statutChoisi : '',
    'statut_compte' => in_array($compteChoisi, ['en_attente', 'actif', 'desactive'], true) ? $compteChoisi : '',
]);
$dossiers = $utilisateurs->compterDossiers();

$statutsListe = ['' => 'Tous : actif, suspendu, sorti'];
foreach (STATUTS_DOSSIER as $cle => $statut) {
    $statutsListe[$cle] = $statut[0];
}
$comptesListe = ['' => 'Tous les comptes', 'actif' => 'Compte actif', 'desactive' => 'Compte désactivé', 'en_attente' => 'En attente d’activation'];
?>
        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Rechercher et filtrer</h2>
          </div>
          <form method="get" class="filter-bar" aria-label="Filtrer les dossiers employés">
            <div class="filter-bar__field">
              <label for="emp-recherche" class="form-label">Recherche</label>
              <input type="search" id="emp-recherche" name="q" class="form-control" value="<?= h($recherche) ?>" placeholder="Nom, matricule ou e-mail" autocomplete="off">
            </div>
            <div class="filter-bar__field">
              <label for="emp-dept" class="form-label">Département</label>
              <select id="emp-dept" name="dept" class="form-select" data-autosubmit>
                <?= optionsSelect(['' => 'Tous les départements'] + $listeDepts, $deptChoisi) ?>
              </select>
            </div>
            <div class="filter-bar__field">
              <label for="emp-poste" class="form-label">Poste</label>
              <select id="emp-poste" name="poste" class="form-select" data-autosubmit>
                <?= optionsSelect(['' => 'Tous les postes'] + $listePostes, $posteChoisi) ?>
              </select>
            </div>
            <div class="filter-bar__field">
              <label for="emp-statut" class="form-label">Statut du dossier</label>
              <select id="emp-statut" name="statut" class="form-select" data-autosubmit>
                <?= optionsSelect($statutsListe, $statutChoisi) ?>
              </select>
            </div>
            <div class="filter-bar__field">
              <label for="emp-compte" class="form-label">Compte de connexion</label>
              <select id="emp-compte" name="compte" class="form-select" data-autosubmit>
                <?= optionsSelect($comptesListe, $compteChoisi) ?>
              </select>
            </div>
            <noscript><button type="submit" class="btn btn-neutral">Filtrer</button></noscript>
          </form>
        </section>

        <div class="app-actions">
          <a href="<?= h($creerUrl) ?>" class="btn btn-primary text-decoration-none"><i class="fa-solid fa-user-plus me-2" aria-hidden="true"></i>Créer un compte</a>
          <a href="<?= h($listeUrl) ?>" class="btn btn-neutral text-decoration-none">Réinitialiser les filtres</a>
        </div>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Dossiers employés</h2>
          </div>

          <div id="acces-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
            <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i><span data-confirmation-label></span>
          </div>
          <div id="acces-erreur" class="alert alert-danger d-none" role="alert"></div>

          <?php if (!$liste): ?>
            <p class="mb-0">Aucun dossier ne correspond à ces filtres.</p>
          <?php else: ?>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Dossiers employés">
              <table class="data-table">
                <caption class="visually-hidden">Dossiers employés</caption>
                <thead>
                  <tr>
                    <th scope="col">Nom complet</th>
                    <th scope="col">Matricule</th>
                    <th scope="col">Département et poste</th>
                    <th scope="col">Statut</th>
                    <th scope="col">Compte</th>
                    <th scope="col">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($liste as $e):
                    [$texteStatut, $tonStatut] = STATUTS_DOSSIER[$e['statut_dossier']];
                    [$texteCompte, $tonCompte] = etatCompte($e['statut_compte']);
                    $nom = $e['prenom'] . ' ' . $e['nom'];
                    $action = CompteService::actionPossible($auteur, $e); ?>
                  <tr<?= $e['statut_compte'] === 'en_attente' ? ' class="is-pending"' : '' ?> data-compte="<?= h($nom) ?>" data-id="<?= (int) $e['id'] ?>">
                    <td data-label="Nom complet"><a class="data-table__ref" href="<?= h($ficheUrl) ?>?id=<?= h($e['matricule']) ?>"><?= h($nom) ?></a></td>
                    <td data-label="Matricule"><?= h($e['matricule']) ?></td>
                    <td data-label="Département et poste"><?= h($e['departement']) ?> • <?= h($e['poste']) ?></td>
                    <td data-label="Statut"><?= badge($texteStatut, $tonStatut, false) ?></td>
                    <td data-label="Compte"><span class="badge-status badge-status--dot badge-status--<?= h($tonCompte) ?>" data-compte-etat><?= h($texteCompte) ?></span></td>
                    <td data-label="Action"><?= boutonCompte($action, $nom) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

          <p class="card-panel__note"><?= count($liste) ?> dossier<?= count($liste) > 1 ? 's' : '' ?> affiché<?= count($liste) > 1 ? 's' : '' ?> sur <?= (int) $dossiers['total'] ?>. Les dossiers sortis et suspendus restent consultables pour conserver l’historique.</p>
        </section>
