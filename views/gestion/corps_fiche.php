<?php
$onglets = ['identite' => 'Identité', 'contrat' => 'Contrat', 'documents' => 'Documents', 'conges' => 'Congés', 'paie' => 'Paie'];
$compteEtats = ['actif' => 'Actif', 'suspendu' => 'Suspendu', 'sorti' => 'Désactivé'];
$contrat = $e['contrat'];
$conges = $e['conges'];
$disponibles = $conges['acquis'] - $conges['pris'] - $conges['attente'];
[$texteStatut, $tonStatut] = STATUTS_DOSSIER[$e['statut']];
$actionTraiter = $actionTraiter ?? null;
$lienPaie = $lienPaie ?? null;
$demandesUrl = $demandesUrl ?? null;
$base = h($ficheUrl) . '?id=' . h($id);

$contratTexte = $contrat['statut'] === 'En cours'
    ? $contrat['type'] . ' depuis ' . dateCourte($contrat['debut']) . ' • Salaire de base ' . montant($contrat['base'])
    : 'Aucun contrat en cours (' . $contrat['type'] . ' terminé le ' . dateCourte($contrat['fin']) . ')';
?>
        <nav aria-label="Sections du dossier">
          <ul class="tabs-pills">
            <?php foreach ($onglets as $cle => $libelle): ?>
              <li><a class="tab-pill<?= $cle === $onglet ? ' is-active' : '' ?>" href="<?= $base ?>&amp;onglet=<?= $cle ?>"<?= $cle === $onglet ? ' aria-current="page"' : '' ?>><?= h($libelle) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </nav>

<?php if ($onglet === 'identite'): ?>
        <div class="duo">
          <section class="card-panel">
            <div class="card-panel__head card-panel__head--between">
              <h2 class="card-panel__title">Identité</h2>
              <?= badge($texteStatut, $tonStatut) ?>
            </div>
            <dl class="field-grid">
              <?php
              champLecture('Nom', $e['nom']);
              champLecture('Prénom', $e['prenom']);
              champLecture('Date de naissance', dateCourte($e['naissance']));
              champLecture('Matricule', $id);
              champLecture('Téléphone', $e['tel']);
              champLecture('Adresse', $e['adresse']);
              champLecture('Date d’embauche', dateCourte($e['embauche']));
              ?>
            </dl>
          </section>

          <section class="card-panel">
            <div class="card-panel__head">
              <h2 class="card-panel__title">Rattachement et compte</h2>
            </div>
            <dl class="field-grid">
              <?php
              champLecture('Département', $e['dept'] . ($e['chef'] !== '' ? ' • Chef : ' . $e['chef'] : ''));
              champLecture('Poste', $e['poste']);
              champLecture('Compte de connexion', $e['email'] . ' • ' . $e['role'] . ' • ' . $compteEtats[$e['statut']]);
              champLecture('Contrat en cours', $contratTexte);
              champLecture('Dernière paie', $e['paie']['mois'] . ' • ' . $e['paie']['etat'] . ' • ' . montant($e['paie']['net']) . ' nets');
              champLecture('Congé annuel 2026', $conges['acquis'] . ' acquis • ' . $conges['pris'] . ' pris • ' . $conges['attente'] . ' en attente • ' . $disponibles . ' disponibles');
              ?>
            </dl>
          </section>
        </div>
<?php endif; ?>

<?php if ($onglet === 'contrat'): ?>
        <section class="card-panel">
          <div class="card-panel__head card-panel__head--between">
            <h2 class="card-panel__title">Contrat</h2>
            <?= badge($contrat['statut'], $contrat['statut'] === 'En cours' ? 'success' : 'neutral') ?>
          </div>
          <dl class="field-grid">
            <?php
            champLecture('Référence', $contrat['ref']);
            champLecture('Type', $contrat['type']);
            champLecture('Date de début', dateCourte($contrat['debut']));
            champLecture('Date de fin', $contrat['fin'] ? dateCourte($contrat['fin']) : 'Sans date de fin');
            champLecture('Salaire de base', montant($contrat['base']));
            champLecture('Heures mensuelles', $contrat['heures'] . ' h');
            ?>
          </dl>
        </section>
<?php endif; ?>

<?php if ($onglet === 'documents'): ?>
        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-folder-open" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Documents du dossier</h2>
          </div>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Documents du dossier">
            <table class="data-table">
              <caption class="visually-hidden">Documents conservés dans le dossier</caption>
              <thead>
                <tr>
                  <th scope="col">Document</th>
                  <th scope="col">Référence</th>
                  <th scope="col">Situation</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($e['pieces'] as [$element, $ref, $situation, $ton, $estDocument]): if (!$estDocument) { continue; } ?>
                <tr>
                  <td data-label="Document"><?= h($element) ?></td>
                  <td data-label="Référence"><?= h($ref) ?></td>
                  <td data-label="Situation"><?= h($situation) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
<?php endif; ?>

<?php if ($onglet === 'conges'): ?>
        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Solde de congé annuel 2026</h2>
          </div>
          <dl class="solde-grid">
            <div class="solde-grid__item"><dt>Acquis</dt><dd><?= (int) $conges['acquis'] ?></dd></div>
            <div class="solde-grid__item"><dt>Pris</dt><dd><?= (int) $conges['pris'] ?></dd></div>
            <div class="solde-grid__item solde-grid__item--warning"><dt>En attente</dt><dd><?= (int) $conges['attente'] ?></dd></div>
            <div class="solde-grid__item solde-grid__item--success"><dt>Disponibles</dt><dd><?= (int) $disponibles ?></dd></div>
          </dl>
        </section>
<?php endif; ?>

<?php if ($onglet === 'paie'): ?>
        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-wallet" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Dernière paie</h2>
          </div>
          <dl class="field-grid">
            <?php
            champLecture('Période', $e['paie']['mois']);
            champLecture('État de la fiche', $e['paie']['etat']);
            champLecture('Net à payer', montant($e['paie']['net']));
            champLecture('Salaire de base', montant($contrat['base']));
            ?>
          </dl>
        </section>
<?php endif; ?>

<?php if ($onglet === 'identite'): ?>
        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-folder-open" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Pièces et demandes liées</h2>
          </div>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Pièces et demandes liées">
            <table class="data-table">
              <caption class="visually-hidden">Pièces et demandes liées au dossier</caption>
              <thead>
                <tr>
                  <th scope="col">Élément</th>
                  <th scope="col">Référence</th>
                  <th scope="col">Situation</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($e['pieces'] as [$element, $ref, $situation, $ton, $estDocument, $lien]): ?>
                <tr>
                  <td data-label="Élément"><?= h($element) ?></td>
                  <td data-label="Référence"><?php if ($lien && $demandesUrl): ?><a class="data-table__ref" href="<?= h($demandesUrl) ?>?type=<?= h($lien[0]) ?>&amp;ref=<?= h($lien[1]) ?>"><?= h($ref) ?></a><?php else: ?><?= h($ref) ?><?php endif; ?></td>
                  <td data-label="Situation"><?= $ton !== '' ? badge($situation, $ton, false) : h($situation) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
<?php endif; ?>

        <div class="app-actions">
          <?php if ($actionTraiter): ?>
            <a href="<?= h($actionTraiter['url']) ?>" class="btn btn-primary text-decoration-none"><?= h($actionTraiter['label']) ?></a>
          <?php endif; ?>
          <?php if ($lienPaie && $onglet === 'paie'): ?>
            <a href="<?= h($lienPaie) ?>" class="btn btn-neutral text-decoration-none">Ouvrir la paie</a>
          <?php endif; ?>
          <a href="<?= h($listeUrl) ?>" class="btn btn-neutral text-decoration-none">Retour aux employés</a>
        </div>

        <div class="note-info" role="note">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
          <span>L’onglet <?= h($onglets[$onglet]) ?> est ouvert. Les autres onglets donnent accès aux éléments du même dossier. Un seul contrat peut être en cours.</span>
        </div>
