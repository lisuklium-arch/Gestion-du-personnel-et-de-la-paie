<?php
require_once __DIR__ . '/../../config/init.php';
exigerRole(['rh']);
require_once __DIR__ . '/donnees_demo_rh.php';

function objetDemande(string $type, array $d): string
{
    if ($type === 'conges') {
        return periodeLongueFr($d['debut'], $d['fin']) . ' • ' . libelleJours(joursOuvres($d['debut'], $d['fin']), true);
    }
    if ($type === 'modifications') {
        return $d['champ'];
    }
    if ($type === 'attestations') {
        return $d['type'];
    }
    return montant($d['montant']) . ' • ' . MOIS_MIN[(int) substr((string) array_key_first($d['mois']), 5, 2)];
}

$espace = 'rh';
$active = 'rh_demandes';
$scripts = ['gestion.js'];

$type = (string) ($_GET['type'] ?? 'conges');
if (!isset($demandes[$type])) {
    $type = 'conges';
}

$file = $demandes[$type];
$ref = (string) ($_GET['ref'] ?? '');
if (!isset($file[$ref])) {
    $ref = (string) array_key_first($file);
}

$d = $file[$ref] ?? null;
$demandeur = $d ? $annuaire[$d['emp']] : null;
$total = array_sum(array_map('count', $demandes));

$pageTitle = 'Demandes à traiter';
$titrePage = 'Demandes à traiter — ' . $demandesParType[$type]['libelle'];
$sousTitre = $total . ' demandes en attente de décision RH • ' . h($situation);

if ($d) {
    $prenom = explode(' ', $demandeur['nom'])[0];
    $employeTexte = $demandeur['nom'] . ' • ' . $d['emp'];
    $decValider = 'Demande ' . $ref . ' validée. Le demandeur est notifié et la décision est journalisée.';
    $decRefuser = 'Demande ' . $ref . ' refusée. Le demandeur est notifié avec le motif.';
    $decNote = 'Le demandeur reçoit la décision. Le journal conserve l’auteur, la date, l’action et la demande concernée.';
    $decAvant = '';

    if ($type === 'conges') {
        $jours = joursOuvres($d['debut'], $d['fin']);
        $avant = $demandeur['acquis'] - $demandeur['pris'];
        $pourChef = $d['avis'] === null;
    }

    if ($type === 'avances') {
        ob_start();
        ?>
        <div class="mb-3">
          <label for="decision-retenue" class="form-label">Mois de retenue</label>
          <select id="decision-retenue" name="mois_retenue" class="form-select"><?= optionsSelect($d['mois']) ?></select>
        </div>
        <?php
        $decAvant = ob_get_clean();
        $decValider = 'Avance ' . $ref . ' accordée. Elle sera retenue sur la paie du mois choisi.';
    }
}

include __DIR__ . '/../layout/debut_page.php';
?>

        <nav aria-label="Types de demande">
          <ul class="tabs-pills">
            <?php foreach ($demandesParType as $cle => $info): ?>
              <li><a class="tab-pill<?= $cle === $type ? ' is-active' : '' ?>" href="?type=<?= h($cle) ?>"<?= $cle === $type ? ' aria-current="page"' : '' ?>><?= h($info['libelle']) ?> (<?= count($demandes[$cle]) ?>)</a></li>
            <?php endforeach; ?>
          </ul>
        </nav>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">File d’attente</h2>
          </div>

          <?php if (!$d): ?>
            <p class="mb-0">Aucune demande en attente dans cette catégorie.</p>
          <?php else: ?>
            <div class="table-scroll" tabindex="0" role="region" aria-label="File d’attente">
              <table class="data-table">
                <caption class="visually-hidden">Demandes en attente de décision RH</caption>
                <thead>
                  <tr>
                    <th scope="col">Référence</th>
                    <th scope="col">Demandeur</th>
                    <th scope="col">Objet</th>
                    <th scope="col">État</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($file as $r => $ligne): ?>
                  <tr<?= $r === $ref ? ' class="is-selected"' : '' ?>>
                    <td data-label="Référence"><a class="data-table__ref" href="?type=<?= h($type) ?>&amp;ref=<?= h($r) ?>"<?= $r === $ref ? ' aria-current="true"' : '' ?>><?= h($r) ?></a><?= $r === $ref ? ' • Sélectionnée' : '' ?></td>
                    <td data-label="Demandeur"><?= h($annuaire[$ligne['emp']]['nom']) ?></td>
                    <td data-label="Objet"><?= h(objetDemande($type, $ligne)) ?></td>
                    <td data-label="État"><?= badge('En attente RH', 'warning') ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <p class="card-panel__note">La première demande est sélectionnée par défaut. Son dossier et les actions de décision sont affichés ci-dessous.</p>
          <?php endif; ?>
        </section>

<?php if ($d): ?>
        <div class="app-split">
          <section class="card-panel">
            <div class="card-panel__head card-panel__head--between">
              <h2 class="card-panel__title">Demande sélectionnée</h2>
              <?= badge('En attente RH', 'warning') ?>
            </div>

            <dl class="detail-list">
              <div><dt>Demande</dt><dd><?= h($ref) ?> • déposée le <?= h(dateHeure($d['depose'])) ?></dd></div>
              <div><dt>Employé</dt><dd><?= h($employeTexte . ' • ' . $demandeur['poste'] . ' • ' . $demandeur['dept']) ?></dd></div>

              <?php if ($type === 'conges'): ?>
                <div><dt>Période</dt><dd><?= h($d['type'] . ' • ' . periodeLongueFr($d['debut'], $d['fin']) . ' • ' . libelleJours($jours, true)) ?></dd></div>
                <div><dt>Motif</dt><dd><?= h($d['motif']) ?></dd></div>
                <div><dt>Solde 2026</dt><dd><?= $demandeur['acquis'] ?> acquis − <?= $demandeur['pris'] ?> pris = <?= $avant ?> avant réservation ; <?= $jours ?> en attente ; <?= $avant - $jours ?> disponibles</dd></div>
                <?php if ($pourChef): ?>
                  <div><dt>Avis du chef</dt><dd>Aucun avis : le congé d’un chef est décidé par la RH.</dd></div>
                <?php else: ?>
                  <div><dt>Avis du chef</dt><dd><?= h($d['avis'][0] . ' • ' . $d['avis'][1] . ' • ' . dateHeure($d['avis'][2])) ?></dd></div>
                  <div><dt>Commentaire du chef</dt><dd><?= h($d['avis'][3]) ?></dd></div>
                <?php endif; ?>
              <?php elseif ($type === 'modifications'): ?>
                <div><dt>Champ concerné</dt><dd><?= h($d['champ']) ?></dd></div>
                <div><dt>Ancienne valeur</dt><dd><?= h($d['ancienne']) ?></dd></div>
                <div><dt>Nouvelle valeur</dt><dd><?= h($d['nouvelle']) ?></dd></div>
                <div><dt>Motif</dt><dd><?= h($d['motif']) ?></dd></div>
              <?php elseif ($type === 'attestations'): ?>
                <div><dt>Type</dt><dd><?= h($d['type']) ?></dd></div>
                <div><dt>Destinataire</dt><dd><?= h($d['destinataire']) ?></dd></div>
                <div><dt>Motif</dt><dd><?= h($d['motif']) ?></dd></div>
                <div><dt>Informations à attester</dt><dd><?= h($d['infos']) ?></dd></div>
                <div>
                  <dt>Document préparé</dt>
                  <dd class="file-box"><i class="fa-regular fa-file-pdf" aria-hidden="true"></i><p><?= h($d['fichier'][0]) ?> • <?= h($d['fichier'][1]) ?></p></dd>
                </div>
              <?php else: ?>
                <div><dt>Montant demandé</dt><dd><?= h(montant($d['montant'])) ?></dd></div>
                <div><dt>Salaire de base</dt><dd><?= h(montant($d['base'])) ?></dd></div>
                <div><dt>Motif</dt><dd><?= h($d['motif']) ?></dd></div>
                <div><dt>Dernière avance</dt><dd><?= h($d['derniere']) ?></dd></div>
              <?php endif; ?>
            </dl>

            <div class="note-info mt-3" role="note">
              <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
              <?php if ($type === 'conges'): ?>
                <span>Ces <?= $jours ?> jours sont déjà réservés. Validation : <?= $demandeur['pris'] + $jours ?> pris au total, 0 en attente, <?= $demandeur['acquis'] - $demandeur['pris'] - $jours ?> disponibles. Refus : <?= $demandeur['pris'] ?> pris, 0 en attente, <?= $avant ?> disponibles.<?= $pourChef ? ' Les congés d’un chef sont décidés par la RH sans avis préalable.' : '' ?></span>
              <?php elseif ($type === 'modifications'): ?>
                <span>Valider met à jour uniquement le champ <?= h(strtolower($d['champ'])) ?>. L’ancienne et la nouvelle valeur restent conservées dans la demande. Un refus laisse le dossier inchangé.</span>
              <?php elseif ($type === 'attestations'): ?>
                <span>Après validation, le document est mis à disposition dans les documents personnels de <?= h($prenom) ?>. En cas de refus, aucun document n’est publié.</span>
              <?php else: ?>
                <span>Valider accorde l’avance ; elle sera retenue lors du calcul de la paie du mois de retenue choisi. Aucun paiement en ligne n’est effectué dans cette application.</span>
              <?php endif; ?>
            </div>
          </section>

          <?php include __DIR__ . '/../gestion/decision.php'; ?>
        </div>
<?php endif; ?>

<?php include __DIR__ . '/../layout/fin_page.php'; ?>
