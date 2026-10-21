<?php
$recherche = trim((string) ($_GET['q'] ?? ''));
$deptChoisi = (string) ($_GET['dept'] ?? '');
$posteChoisi = (string) ($_GET['poste'] ?? '');
$statutChoisi = (string) ($_GET['statut'] ?? '');

$liste = array_filter($employes, function ($e, $id) use ($recherche, $deptChoisi, $posteChoisi, $statutChoisi) {
    $texte = nomComplet($e) . ' ' . $id . ' ' . $e['email'];
    return ($recherche === '' || preg_match('/' . preg_quote($recherche, '/') . '/iu', $texte))
        && ($deptChoisi === '' || $e['dept'] === $deptChoisi)
        && ($posteChoisi === '' || $e['poste'] === $posteChoisi)
        && ($statutChoisi === '' || $e['statut'] === $statutChoisi);
}, ARRAY_FILTER_USE_BOTH);

$statutsListe = ['' => 'Tous : actif, suspendu, sorti'];
foreach (STATUTS_DOSSIER as $cle => $statut) {
    $statutsListe[$cle] = $statut[0];
}
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
                <?= optionsSelect(['' => 'Tous les départements'] + array_combine(array_keys($departements), array_keys($departements)), $deptChoisi) ?>
              </select>
            </div>
            <div class="filter-bar__field">
              <label for="emp-poste" class="form-label">Poste</label>
              <select id="emp-poste" name="poste" class="form-select" data-autosubmit>
                <?= optionsSelect(['' => 'Tous les postes'] + array_combine(array_keys($postes), array_keys($postes)), $posteChoisi) ?>
              </select>
            </div>
            <div class="filter-bar__field">
              <label for="emp-statut" class="form-label">Statut du dossier</label>
              <select id="emp-statut" name="statut" class="form-select" data-autosubmit>
                <?= optionsSelect($statutsListe, $statutChoisi) ?>
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
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($liste as $id => $e):
                    [$texteStatut, $tonStatut] = STATUTS_DOSSIER[$e['statut']]; ?>
                  <tr>
                    <td data-label="Nom complet"><a class="data-table__ref" href="<?= h($ficheUrl) ?>?id=<?= h($id) ?>"><?= h(nomComplet($e)) ?></a></td>
                    <td data-label="Matricule"><?= h($id) ?></td>
                    <td data-label="Département et poste"><?= h($e['dept']) ?> • <?= h($e['poste']) ?></td>
                    <td data-label="Statut"><?= badge($texteStatut, $tonStatut, false) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

          <p class="card-panel__note"><?= count($liste) ?> dossier<?= count($liste) > 1 ? 's' : '' ?> affiché<?= count($liste) > 1 ? 's' : '' ?> sur <?= (int) $dossiers['total'] ?> • Page 1. Les dossiers sortis et suspendus restent consultables pour conserver l’historique.</p>
        </section>
