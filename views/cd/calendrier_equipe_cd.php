<?php
require_once __DIR__ . '/donnees_demo_cd.php';

const ANNEE_MIN = 2024;
const ANNEE_MAX = 2028;

$pageTitle = 'Calendrier d’équipe';
$active = 'calendrier';
$scripts = ['cd.js'];

$mois = (int) ($_GET['mois'] ?? 0);
$annee = (int) ($_GET['annee'] ?? 0);
if ($mois < 1 || $mois > 12) {
    $mois = (int) $aujourdhui->format('n');
}
if ($annee < ANNEE_MIN || $annee > ANNEE_MAX) {
    $annee = (int) $aujourdhui->format('Y');
}

$premierJour = new DateTime(sprintf('%04d-%02d-01', $annee, $mois));
$nbJours = (int) $premierJour->format('t');
$decalage = (int) $premierJour->format('N') - 1;
$nbCases = (int) ceil(($decalage + $nbJours) / 7) * 7;
$titreMois = MOIS_NOMS[$mois] . ' ' . $annee;
$aujourdhuiTexte = $aujourdhui->format('Y-m-d');
$sousTitre = h($titreMois) . ' • ' . h($chef['departement']);

function lienMois(DateTime $date): ?string
{
    $annee = (int) $date->format('Y');
    if ($annee < ANNEE_MIN || $annee > ANNEE_MAX) {
        return null;
    }
    return '?mois=' . (int) $date->format('n') . '&annee=' . $annee;
}

$lienPrecedent = lienMois((clone $premierJour)->modify('-1 month'));
$lienSuivant = lienMois((clone $premierJour)->modify('+1 month'));

$evenements = evenementsCalendrier($conges);

$absencesDuMois = [];
foreach ($evenements as $date => $liste) {
    if (strpos($date, $premierJour->format('Y-m')) === 0) {
        foreach ($liste as [$matricule, $nature]) {
            $absencesDuMois[] = [$date, $matricule, $nature];
        }
    }
}

include __DIR__ . '/../layout/debut_cd.php';
?>

        <section class="card-panel">
          <form method="get" class="filter-bar" aria-label="Choisir la période à afficher">
            <div class="filter-bar__field">
              <label for="cal-mois" class="form-label">Mois</label>
              <select id="cal-mois" name="mois" class="form-select" data-autosubmit>
                <?php foreach (MOIS_NOMS as $numero => $nom): ?>
                  <option value="<?= $numero ?>"<?= $numero === $mois ? ' selected' : '' ?>><?= h($nom) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="filter-bar__field">
              <label for="cal-annee" class="form-label">Année</label>
              <select id="cal-annee" name="annee" class="form-select" data-autosubmit>
                <?php for ($a = ANNEE_MIN; $a <= ANNEE_MAX; $a++): ?>
                  <option value="<?= $a ?>"<?= $a === $annee ? ' selected' : '' ?>><?= $a ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <noscript><button type="submit" class="btn btn-neutral">Afficher</button></noscript>

            <div class="filter-bar__nav">
              <a class="btn btn-neutral text-decoration-none<?= $lienPrecedent ? '' : ' disabled' ?>"<?= $lienPrecedent ? ' href="' . h($lienPrecedent) . '"' : ' aria-disabled="true"' ?>>
                <i class="fa-solid fa-chevron-left me-2" aria-hidden="true"></i>Précédent
              </a>
              <a class="btn btn-neutral text-decoration-none<?= $lienSuivant ? '' : ' disabled' ?>"<?= $lienSuivant ? ' href="' . h($lienSuivant) . '"' : ' aria-disabled="true"' ?>>
                Suivant<i class="fa-solid fa-chevron-right ms-2" aria-hidden="true"></i>
              </a>
            </div>
          </form>
        </section>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-calendar-days" aria-hidden="true"></i></span>
            <h2 class="card-panel__title"><?= h($titreMois) ?></h2>
          </div>

          <div class="cal-wrap">
            <div class="cal-grid" role="grid" aria-label="Calendrier de <?= h(MOIS_MIN[$mois]) ?> <?= $annee ?>">
              <?php foreach (JOURS_NOMS as $jour): ?>
                <div class="cal-grid__head" role="columnheader"><?= h($jour) ?></div>
              <?php endforeach; ?>

              <?php for ($i = 0; $i < $nbCases; $i++):
                $numero = $i - $decalage + 1;
                if ($numero < 1 || $numero > $nbJours): ?>
                  <div class="cal-day cal-day--out" role="gridcell" aria-hidden="true"></div>
                <?php else:
                  $date = sprintf('%04d-%02d-%02d', $annee, $mois, $numero);
                  $estAujourdhui = $date === $aujourdhuiTexte;
                  $classe = 'cal-day' . ($i % 7 >= 5 ? ' cal-day--weekend' : '') . ($estAujourdhui ? ' cal-day--today' : ''); ?>
                  <div class="<?= $classe ?>" role="gridcell"<?= $estAujourdhui ? ' aria-current="date"' : '' ?>>
                    <p class="cal-day__num"><?= $numero ?></p>
                    <?php if (!empty($evenements[$date])): ?>
                      <ul class="cal-day__list">
                        <?php foreach ($evenements[$date] as [$matricule, $nature]):
                          $nom = $equipe[$matricule]['nom'];
                          $infobulle = $nom . ' — congé ' . $naturesConge[$nature][0]; ?>
                          <li><span class="cal-tag cal-tag--<?= $nature ?>" title="<?= h($infobulle) ?>"><?= h(initiales($nom)) ?><span class="visually-hidden"> <?= h($nom . ', congé ' . $naturesConge[$nature][0]) ?></span></span></li>
                        <?php endforeach; ?>
                      </ul>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              <?php endfor; ?>
            </div>
          </div>

          <ul class="cal-legend">
            <li><span class="cal-tag cal-tag--valide">AB</span> Congé validé</li>
            <li><span class="cal-tag cal-tag--attente">AB</span> Congé en attente</li>
            <li>Les lettres sont les initiales de l’employé.</li>
          </ul>
        </section>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-solid fa-list" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Absences de <?= h(MOIS_MIN[$mois]) ?> <?= $annee ?></h2>
          </div>

          <?php if (!$absencesDuMois): ?>
            <p class="mb-0">Aucun congé ni absence sur cette période.</p>
          <?php else: ?>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Absences du mois">
              <table class="data-table">
                <caption class="visually-hidden">Congés et absences de <?= h($titreMois) ?></caption>
                <thead>
                  <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Employé</th>
                    <th scope="col">Nature</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($absencesDuMois as [$date, $matricule, $nature]):
                    [$texte, $ton] = $naturesConge[$nature]; ?>
                  <tr>
                    <td data-label="Date"><?= h(dateFr($date)) ?></td>
                    <td data-label="Employé"><?php personne($equipe[$matricule]['nom']); ?></td>
                    <td data-label="Nature"><span class="badge-status badge-status--<?= $ton ?> badge-status--dot">Congé <?= h($texte) ?></span></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </section>

<?php include __DIR__ . '/../layout/fin_cd.php'; ?>
