<?php
require_once __DIR__ . '/../../config/init.php';
exigerRole(['rh']);
require_once __DIR__ . '/donnees_demo_rh.php';
require_once __DIR__ . '/../gestion/aides_gestion.php';
require_once __DIR__ . '/../../models/ParametreSysteme.php';
require_once __DIR__ . '/../../models/Referentiel.php';
require_once __DIR__ . '/../../services/ParametreSystemeService.php';

$espace = 'rh';
$active = 'rh_parametres';
$pageTitle = 'Paramètres';
$sousTitre = 'Paramètres système et référentiels • Cliquez sur une ligne pour modifier';
$scripts = ['forms.js', 'gestion.js'];

$referentiel = new Referentiel($pdo);
$parametres = (new ParametreSysteme($pdo))->lister();
$departements = $referentiel->listerDepartements();
$postes = $referentiel->listerPostes();
$typesConge = $referentiel->listerTypesConge();

/** Lien qui sélectionne une ligne (ou « nouveau ») dans une section sans perdre la sélection des autres. */
function lienSelection(string $parametre, string $valeur, string $ancre): string
{
    return '?' . h(http_build_query(array_merge($_GET, [$parametre => $valeur]))) . '#' . $ancre;
}

/** Ligne choisie dans une liste, par identifiant ; null = création. */
function ligneChoisie(array $lignes, string $cleGet, string $champ = 'id'): ?array
{
    $voulu = (string) ($_GET[$cleGet] ?? '');
    foreach ($lignes as $ligne) {
        if ((string) $ligne[$champ] === $voulu) {
            return $ligne;
        }
    }
    return null;
}

function nombreAffiche($valeur): string
{
    return rtrim(rtrim(number_format((float) $valeur, 2, ',', ' '), '0'), ',');
}

$paramChoisi = ligneChoisie($parametres, 'param', 'cle') ?? ($parametres[0] ?? null);
$deptChoisi = ligneChoisie($departements, 'dept');
$posteChoisi = ligneChoisie($postes, 'poste');
$congeChoisi = ligneChoisie($typesConge, 'conge');
$reglesParam = $paramChoisi !== null ? (ParametreSystemeService::REGLES[$paramChoisi['cle']] ?? null) : null;

include __DIR__ . '/../layout/debut_page.php';
?>

        <div class="duo">
          <section class="card-panel">
            <div class="card-panel__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-sliders" aria-hidden="true"></i></span>
              <h2 class="card-panel__title">Paramètres système</h2>
            </div>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Paramètres système">
              <table class="data-table">
                <caption class="visually-hidden">Paramètres système utilisés par la paie et les congés</caption>
                <thead>
                  <tr><th scope="col">Paramètre</th><th scope="col">Valeur</th><th scope="col">Modifié le</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($parametres as $p):
                    $choisi = $paramChoisi !== null && $p['cle'] === $paramChoisi['cle'];
                    $unite = ParametreSystemeService::REGLES[$p['cle']]['unite'] ?? ''; ?>
                  <tr<?= $choisi ? ' class="is-selected"' : '' ?>>
                    <td data-label="Paramètre"><a class="data-table__ref" href="<?= lienSelection('param', $p['cle'], 'form-param') ?>"<?= $choisi ? ' aria-current="true"' : '' ?>><?= h($p['description'] ?? $p['cle']) ?></a></td>
                    <td data-label="Valeur"><?= h($p['valeur']) ?><?= $unite !== '' ? ' ' . h($unite) : '' ?></td>
                    <td data-label="Modifié le"><?= h(dateHeure($p['date_modification'])) ?></td>
                  </tr>
                  <?php endforeach; ?>
                  <?php if (!$parametres): ?>
                  <tr><td colspan="3">Aucun paramètre enregistré.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>

          <section class="card-panel card-panel--cd-form" id="form-param">
            <div class="card-panel__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i></span>
              <h2 class="card-panel__title">Modifier le paramètre</h2>
            </div>
            <?php if ($paramChoisi === null): ?>
            <p>Aucun paramètre à modifier.</p>
            <?php else: ?>
            <div id="param-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
              <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i><span data-confirmation-label>Paramètre enregistré.</span>
            </div>
            <div id="param-erreur" class="alert alert-danger d-none" role="alert"></div>
            <form class="needs-validation" novalidate data-api="/api/parametre_modifier.php" data-confirmation="param-confirmation" data-erreur="param-erreur" data-recharger>
              <input type="hidden" name="cle" value="<?= h($paramChoisi['cle']) ?>">
              <div class="mb-3">
                <p class="form-label mb-2" id="param-nom-label">Paramètre</p>
                <div class="form-readonly" aria-labelledby="param-nom-label"><?= h($paramChoisi['description'] ?? $paramChoisi['cle']) ?> <small class="text-muted">(<?= h($paramChoisi['cle']) ?>)</small></div>
              </div>
              <div class="mb-3">
                <label for="param-valeur" class="form-label">Valeur<?= $reglesParam ? ' (' . h($reglesParam['unite']) . ')' : '' ?></label>
                <?php if ($reglesParam): ?>
                <input type="number" id="param-valeur" name="valeur" class="form-control" inputmode="decimal"
                       min="<?= h($reglesParam['min']) ?>" max="<?= h($reglesParam['max']) ?>" step="<?= h($reglesParam['pas']) ?>"
                       value="<?= h($paramChoisi['valeur']) ?>" required>
                <div class="invalid-feedback"><?= h($reglesParam['aide']) ?></div>
                <?php else: ?>
                <input type="text" id="param-valeur" name="valeur" class="form-control" maxlength="255" value="<?= h($paramChoisi['valeur']) ?>" required>
                <div class="invalid-feedback">Indiquez une valeur (255 caractères maximum).</div>
                <?php endif; ?>
              </div>
              <div class="mb-3">
                <p class="form-label mb-2" id="param-date-label">Dernière modification</p>
                <div class="form-readonly" aria-labelledby="param-date-label"><?= h(dateHeure($paramChoisi['date_modification'])) ?></div>
              </div>
              <div class="app-form-actions">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-2" aria-hidden="true"></i>Enregistrer</button>
                <?php if ($paramChoisi !== null): ?>
                <button type="button" class="btn btn-danger-outline" data-supprimer="/api/parametre_supprimer.php" data-champ="cle" data-valeur="<?= h($paramChoisi['cle']) ?>" data-erreur="param-erreur" data-confirmation="param-confirmation"><i class="fa-solid fa-trash me-2" aria-hidden="true"></i><span data-libelle>Supprimer</span></button>
                <?php endif; ?>
              </div>
            </form>
            <?php endif; ?>
          </section>
        </div>

        <div class="duo">
          <section class="card-panel">
            <div class="card-panel__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-sitemap" aria-hidden="true"></i></span>
              <h2 class="card-panel__title">Départements</h2>
            </div>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Départements">
              <table class="data-table">
                <caption class="visually-hidden">Départements de l’entreprise</caption>
                <thead>
                  <tr><th scope="col">Département</th><th scope="col">Chef désigné</th><th scope="col">Employés</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($departements as $d):
                    $choisi = $deptChoisi !== null && (int) $d['id'] === (int) $deptChoisi['id']; ?>
                  <tr<?= $choisi ? ' class="is-selected"' : '' ?>>
                    <td data-label="Département"><a class="data-table__ref" href="<?= lienSelection('dept', (string) $d['id'], 'form-dept') ?>"<?= $choisi ? ' aria-current="true"' : '' ?>><?= h($d['nom']) ?></a></td>
                    <td data-label="Chef désigné"><?= $d['chef_nom'] !== null ? h($d['chef_prenom'] . ' ' . $d['chef_nom']) : 'Aucun' ?></td>
                    <td data-label="Employés"><?= (int) $d['nb_employes'] ?></td>
                  </tr>
                  <?php endforeach; ?>
                  <?php if (!$departements): ?>
                  <tr><td colspan="3">Aucun département.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <div class="app-actions mt-3">
              <a class="btn btn-neutral text-decoration-none" href="<?= lienSelection('dept', 'nouveau', 'form-dept') ?>"><i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Nouveau département</a>
            </div>
          </section>

          <section class="card-panel card-panel--cd-form" id="form-dept">
            <div class="card-panel__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i></span>
              <h2 class="card-panel__title"><?= $deptChoisi ? 'Modifier le département' : 'Nouveau département' ?></h2>
            </div>
            <div id="dept-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
              <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i><span data-confirmation-label>Département enregistré.</span>
            </div>
            <div id="dept-erreur" class="alert alert-danger d-none" role="alert"></div>
            <form class="needs-validation" novalidate data-api="/api/departement_enregistrer.php" data-confirmation="dept-confirmation" data-erreur="dept-erreur" data-recharger>
              <input type="hidden" name="id" value="<?= $deptChoisi ? h($deptChoisi['id']) : '' ?>">
              <div class="mb-3">
                <label for="dept-nom" class="form-label">Nom</label>
                <input type="text" id="dept-nom" name="nom" class="form-control" maxlength="100" value="<?= h($deptChoisi['nom'] ?? '') ?>" required>
                <div class="invalid-feedback">Indiquez le nom du département.</div>
              </div>
              <div class="mb-3">
                <label for="dept-description" class="form-label">Description</label>
                <input type="text" id="dept-description" name="description" class="form-control" maxlength="255" value="<?= h($deptChoisi['description'] ?? '') ?>">
              </div>
              <div class="mb-3">
                <label for="dept-parent" class="form-label">Département parent</label>
                <select id="dept-parent" name="parent_id" class="form-select">
                  <?php $optionsParent = ['' => 'Aucun (département principal)'];
                  foreach ($departements as $d) {
                      if (!$deptChoisi || (int) $d['id'] !== (int) $deptChoisi['id']) { $optionsParent[(string) $d['id']] = $d['nom']; }
                  } ?>
                  <?= optionsSelect($optionsParent, (string) ($deptChoisi['parent_id'] ?? '')) ?>
                </select>
              </div>
              <?php if ($deptChoisi): ?>
              <div class="mb-3">
                <p class="form-label mb-2" id="dept-chef-label">Chef désigné</p>
                <div class="form-readonly" aria-labelledby="dept-chef-label"><?= $deptChoisi['chef_nom'] !== null ? h($deptChoisi['chef_prenom'] . ' ' . $deptChoisi['chef_nom']) : 'Aucun chef désigné' ?></div>
              </div>
              <?php endif; ?>
              <div class="app-form-actions">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-2" aria-hidden="true"></i>Enregistrer</button>
                <?php if ($deptChoisi): ?><a class="btn btn-neutral text-decoration-none" href="<?= lienSelection('dept', 'nouveau', 'form-dept') ?>">Nouveau</a><?php endif; ?>
                <?php if ($deptChoisi): ?>
                <button type="button" class="btn btn-danger-outline" data-supprimer="/api/departement_supprimer.php" data-champ="id" data-valeur="<?= h($deptChoisi['id']) ?>" data-erreur="dept-erreur" data-confirmation="dept-confirmation"><i class="fa-solid fa-trash me-2" aria-hidden="true"></i><span data-libelle>Supprimer</span></button>
                <?php endif; ?>
              </div>
            </form>
          </section>
        </div>

        <div class="duo">
          <section class="card-panel">
            <div class="card-panel__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></span>
              <h2 class="card-panel__title">Postes</h2>
            </div>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Postes">
              <table class="data-table">
                <caption class="visually-hidden">Postes de l’entreprise</caption>
                <thead>
                  <tr><th scope="col">Poste</th><th scope="col">Description</th><th scope="col">Employés</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($postes as $p):
                    $choisi = $posteChoisi !== null && (int) $p['id'] === (int) $posteChoisi['id']; ?>
                  <tr<?= $choisi ? ' class="is-selected"' : '' ?>>
                    <td data-label="Poste"><a class="data-table__ref" href="<?= lienSelection('poste', (string) $p['id'], 'form-poste') ?>"<?= $choisi ? ' aria-current="true"' : '' ?>><?= h($p['intitule']) ?></a></td>
                    <td data-label="Description"><?= h($p['description'] ?? '—') ?></td>
                    <td data-label="Employés"><?= (int) $p['nb_employes'] ?></td>
                  </tr>
                  <?php endforeach; ?>
                  <?php if (!$postes): ?>
                  <tr><td colspan="3">Aucun poste.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <div class="app-actions mt-3">
              <a class="btn btn-neutral text-decoration-none" href="<?= lienSelection('poste', 'nouveau', 'form-poste') ?>"><i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Nouveau poste</a>
            </div>
          </section>

          <section class="card-panel card-panel--cd-form" id="form-poste">
            <div class="card-panel__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i></span>
              <h2 class="card-panel__title"><?= $posteChoisi ? 'Modifier le poste' : 'Nouveau poste' ?></h2>
            </div>
            <div id="poste-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
              <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i><span data-confirmation-label>Poste enregistré.</span>
            </div>
            <div id="poste-erreur" class="alert alert-danger d-none" role="alert"></div>
            <form class="needs-validation" novalidate data-api="/api/poste_enregistrer.php" data-confirmation="poste-confirmation" data-erreur="poste-erreur" data-recharger>
              <input type="hidden" name="id" value="<?= $posteChoisi ? h($posteChoisi['id']) : '' ?>">
              <div class="mb-3">
                <label for="poste-intitule" class="form-label">Intitulé</label>
                <input type="text" id="poste-intitule" name="intitule" class="form-control" maxlength="100" value="<?= h($posteChoisi['intitule'] ?? '') ?>" required>
                <div class="invalid-feedback">Indiquez l’intitulé du poste.</div>
              </div>
              <div class="mb-3">
                <label for="poste-description" class="form-label">Description</label>
                <input type="text" id="poste-description" name="description" class="form-control" maxlength="255" value="<?= h($posteChoisi['description'] ?? '') ?>">
              </div>
              <div class="app-form-actions">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-2" aria-hidden="true"></i>Enregistrer</button>
                <?php if ($posteChoisi): ?><a class="btn btn-neutral text-decoration-none" href="<?= lienSelection('poste', 'nouveau', 'form-poste') ?>">Nouveau</a><?php endif; ?>
                <?php if ($posteChoisi): ?>
                <button type="button" class="btn btn-danger-outline" data-supprimer="/api/poste_supprimer.php" data-champ="id" data-valeur="<?= h($posteChoisi['id']) ?>" data-erreur="poste-erreur" data-confirmation="poste-confirmation"><i class="fa-solid fa-trash me-2" aria-hidden="true"></i><span data-libelle>Supprimer</span></button>
                <?php endif; ?>
              </div>
            </form>
          </section>
        </div>

        <div class="duo">
          <section class="card-panel">
            <div class="card-panel__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span>
              <h2 class="card-panel__title">Types de congé</h2>
            </div>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Types de congé">
              <table class="data-table">
                <caption class="visually-hidden">Types de congé</caption>
                <thead>
                  <tr><th scope="col">Type</th><th scope="col">Droit / justificatif</th><th scope="col">État</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($typesConge as $t):
                    $choisi = $congeChoisi !== null && (int) $t['id'] === (int) $congeChoisi['id'];
                    $regle = nombreAffiche($t['droit_annuel_jours']) . ' j/an' . ($t['justificatif_requis'] ? ' • justificatif requis' : ''); ?>
                  <tr<?= $choisi ? ' class="is-selected"' : '' ?>>
                    <td data-label="Type"><a class="data-table__ref" href="<?= lienSelection('conge', (string) $t['id'], 'form-conge') ?>"<?= $choisi ? ' aria-current="true"' : '' ?>><?= h($t['libelle']) ?></a></td>
                    <td data-label="Droit / justificatif"><?= h($regle) ?></td>
                    <td data-label="État"><?= badge($t['actif'] ? 'Actif' : 'Inactif', $t['actif'] ? 'success' : 'neutral', false) ?></td>
                  </tr>
                  <?php endforeach; ?>
                  <?php if (!$typesConge): ?>
                  <tr><td colspan="3">Aucun type de congé.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <div class="app-actions mt-3">
              <a class="btn btn-neutral text-decoration-none" href="<?= lienSelection('conge', 'nouveau', 'form-conge') ?>"><i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Nouveau type de congé</a>
            </div>
          </section>

          <section class="card-panel card-panel--cd-form" id="form-conge">
            <div class="card-panel__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i></span>
              <h2 class="card-panel__title"><?= $congeChoisi ? 'Modifier le type de congé' : 'Nouveau type de congé' ?></h2>
            </div>
            <div id="conge-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
              <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i><span data-confirmation-label>Type de congé enregistré.</span>
            </div>
            <div id="conge-erreur" class="alert alert-danger d-none" role="alert"></div>
            <form class="needs-validation" novalidate data-api="/api/type_conge_enregistrer.php" data-confirmation="conge-confirmation" data-erreur="conge-erreur" data-recharger>
              <input type="hidden" name="id" value="<?= $congeChoisi ? h($congeChoisi['id']) : '' ?>">
              <div class="mb-3">
                <?php if ($congeChoisi): ?>
                <p class="form-label mb-2" id="conge-code-label">Code</p>
                <div class="form-readonly" aria-labelledby="conge-code-label"><?= h($congeChoisi['code']) ?></div>
                <?php else: ?>
                <label for="conge-code" class="form-label">Code</label>
                <input type="text" id="conge-code" name="code" class="form-control text-uppercase" maxlength="30" pattern="[A-Za-z][A-Za-z0-9_]{1,29}" required>
                <div class="invalid-feedback">2 à 30 caractères : lettres, chiffres ou « _ », en commençant par une lettre.</div>
                <?php endif; ?>
              </div>
              <div class="mb-3">
                <label for="conge-libelle" class="form-label">Libellé</label>
                <input type="text" id="conge-libelle" name="libelle" class="form-control" maxlength="100" value="<?= h($congeChoisi['libelle'] ?? '') ?>" required>
                <div class="invalid-feedback">Indiquez le libellé du type de congé.</div>
              </div>
              <div class="mb-3">
                <label for="conge-droit" class="form-label">Droit annuel (jours)</label>
                <input type="number" id="conge-droit" name="droit_annuel_jours" class="form-control" min="0" max="60" step="0.5"
                       value="<?= h($congeChoisi ? nombreAffiche($congeChoisi['droit_annuel_jours']) : '0') ?>" required>
                <div class="invalid-feedback">Indiquez un nombre de jours entre 0 et 60, par demi-journée.</div>
              </div>
              <div class="mb-3">
                <label for="conge-decompte" class="form-label">Décompté du solde</label>
                <select id="conge-decompte" name="decompte_solde" class="form-select">
                  <?= optionsSelect(['1' => 'Oui', '0' => 'Non'], $congeChoisi ? (string) (int) $congeChoisi['decompte_solde'] : '1') ?>
                </select>
              </div>
              <div class="mb-3">
                <label for="conge-justificatif" class="form-label">Justificatif requis</label>
                <select id="conge-justificatif" name="justificatif_requis" class="form-select">
                  <?= optionsSelect(['0' => 'Non', '1' => 'Oui'], $congeChoisi ? (string) (int) $congeChoisi['justificatif_requis'] : '0') ?>
                </select>
              </div>
              <div class="mb-3">
                <label for="conge-etat" class="form-label">État</label>
                <select id="conge-etat" name="actif" class="form-select">
                  <?= optionsSelect(['1' => 'Actif', '0' => 'Inactif'], $congeChoisi ? (string) (int) $congeChoisi['actif'] : '1') ?>
                </select>
              </div>
              <div class="app-form-actions">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-2" aria-hidden="true"></i>Enregistrer</button>
                <?php if ($congeChoisi): ?><a class="btn btn-neutral text-decoration-none" href="<?= lienSelection('conge', 'nouveau', 'form-conge') ?>">Nouveau</a><?php endif; ?>
                <?php if ($congeChoisi): ?>
                <button type="button" class="btn btn-danger-outline" data-supprimer="/api/type_conge_supprimer.php" data-champ="id" data-valeur="<?= h($congeChoisi['id']) ?>" data-erreur="conge-erreur" data-confirmation="conge-confirmation"><i class="fa-solid fa-trash me-2" aria-hidden="true"></i><span data-libelle>Supprimer</span></button>
                <?php endif; ?>
              </div>
            </form>
          </section>
        </div>

        <section class="card-panel">
          <div class="card-panel__head">
            <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-file-lines" aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Types de contrat</h2>
          </div>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Types de contrat">
            <table class="data-table">
              <caption class="visually-hidden">Types de contrat</caption>
              <thead>
                <tr><th scope="col">Type</th><th scope="col">Durée / règle</th></tr>
              </thead>
              <tbody>
                <?php foreach ($typesContrat as $nom => $regle): ?>
                <tr>
                  <td data-label="Type"><?= h($nom) ?></td>
                  <td data-label="Durée / règle"><?= h($regle) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="note-info mt-3" role="note">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span>Les types de contrat sont fixés par la base de données : ils ne se modifient pas ici. Les départements, postes et types de congé ne sont supprimés seulement s’ils ne sont plus utilisés ; un type de congé utilisé se désactive. Un employé ne peut avoir qu’un seul contrat en cours.</span>
          </div>
        </section>

<?php include __DIR__ . '/../layout/fin_page.php'; ?>
