<?php
/** Nouvelle demande. Variables : $v, $erreurs, $types, $soldes, $jsSoldes, $jsTypes, $annee, $etapes */
$cls = fn(string $k): string => isset($erreurs[$k]) ? ' is-invalid' : '';
$msg = fn(string $k): string => isset($erreurs[$k]) ? '<div class="invalid-feedback">' . e($erreurs[$k]) . '</div>' : '';
$options = [];
foreach ($types as $t) {
    $suffixe = (int) $t['decompte_solde'] === 1 ? '' : ' — ne décompte pas le solde';
    $options[(int) $t['id_type_conge']] = $t['libelle'] . $suffixe;
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 fw-bold mb-0"><?= e($titre) ?></h1>
    <a class="btn btn-outline-secondary" href="<?= e(url('/employe/conges')) ?>">Retour</a>
</div>
<?php if (!empty($erreurs['global'])): ?><div class="alert alert-danger" role="alert"><?= e($erreurs['global']) ?></div><?php endif; ?>
<?php if ($erreurs !== [] && empty($erreurs['global'])): ?><div class="alert alert-danger" role="alert">Le formulaire contient des erreurs.</div><?php endif; ?>

<div class="row g-3">
    <div class="col-12 col-xl-7">
        <form id="form-conge" method="post" action="<?= e(url('/employe/conges')) ?>" enctype="multipart/form-data" novalidate
              data-calcul="<?= e(url('/employe/conges/calcul')) ?>"
              data-annee="<?= (int) $annee ?>"
              data-soldes="<?= e(json_encode($jsSoldes, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>"
              data-types="<?= e(json_encode($jsTypes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>">
            <?= csrf_field() ?>
            <div class="card mb-3"><div class="card-body">
                <h2 class="h6 fw-bold mb-3">Période et motif</h2>
                <div class="row g-3">
                    <?= form_liste('id_type_conge', 'Type de congé', $options, $v, $erreurs, 'Choisir…', 'col-12') ?>
                    <div class="col-12 col-md-6">
                        <label for="date_debut" class="form-label fw-semibold">Date de début</label>
                        <input type="date" class="form-control<?= $cls('date_debut') ?>" id="date_debut" name="date_debut" value="<?= e($v['date_debut'] ?? '') ?>" required>
                        <?= $msg('date_debut') ?>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="date_fin" class="form-label fw-semibold">Date de fin</label>
                        <input type="date" class="form-control<?= $cls('date_fin') ?>" id="date_fin" name="date_fin" value="<?= e($v['date_fin'] ?? '') ?>" required>
                        <?= $msg('date_fin') ?>
                    </div>
                    <div class="col-12 col-md-6">
                        <fieldset>
                            <legend class="form-label fw-semibold fs-6 mb-2">Début</legend>
                            <?php foreach (['MATIN' => 'Matin', 'APRES_MIDI' => 'Après-midi'] as $code => $libelle): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="periode_debut" id="pd-<?= e($code) ?>" value="<?= e($code) ?>"<?= ($v['periode_debut'] ?? '') === $code ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="pd-<?= e($code) ?>"><?= e($libelle) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </fieldset>
                    </div>
                    <div class="col-12 col-md-6">
                        <fieldset>
                            <legend class="form-label fw-semibold fs-6 mb-2">Fin</legend>
                            <?php foreach (['MATIN' => 'Matin inclus', 'APRES_MIDI' => 'Après-midi inclus'] as $code => $libelle): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="periode_fin" id="pf-<?= e($code) ?>" value="<?= e($code) ?>"<?= ($v['periode_fin'] ?? '') === $code ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="pf-<?= e($code) ?>"><?= e($libelle) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </fieldset>
                        <?php if (isset($erreurs['periode'])): ?><div class="text-danger small mt-1"><?= e($erreurs['periode']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label for="motif" class="form-label fw-semibold">Motif</label>
                        <textarea class="form-control<?= $cls('motif') ?>" id="motif" name="motif" rows="3" maxlength="255" required><?= e($v['motif'] ?? '') ?></textarea>
                        <?= $msg('motif') ?>
                    </div>
                    <div class="col-12">
                        <label for="justificatif" class="form-label fw-semibold">Justificatif <span id="justificatif-mention" class="fw-normal text-secondary">Facultatif</span></label>
                        <input type="file" class="form-control" id="justificatif" name="justificatif" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text">PDF, JPG ou PNG, 5 Mo maximum. Obligatoire pour un congé qui l'exige, par exemple la maladie.</div>
                    </div>
                </div>
            </div></div>
            <button type="submit" class="btn btn-primary">Envoyer la demande</button>
        </form>
    </div>
    <div class="col-12 col-xl-5">
        <div class="card mb-3"><div class="card-body">
            <h2 class="h6 fw-bold">Durée calculée</h2>
            <p class="fs-4 fw-bold mb-1" id="duree-jours">—</p>
            <p class="small text-secondary mb-0" id="duree-message">Les week-ends et les jours fériés ne sont pas comptés. Le serveur recalcule à l'envoi.</p>
        </div></div>
        <div class="card mb-3"><div class="card-body">
            <h2 class="h6 fw-bold">Solde <?= (int) $annee ?> après réservation</h2>
            <p class="mb-0" id="solde-ligne">Choisissez un type qui décompte le solde.</p>
            <?php if (!empty($soldes)): ?>
                <ul class="small text-secondary mt-2 mb-0">
                    <?php foreach ($soldes as $s): if ((int) $s['annee'] !== (int) $annee) continue; ?>
                        <li><?= e($s['libelle']) ?> : <?= e(nombre($s['jours_disponibles'])) ?> j disponibles</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div></div>
        <div class="card"><div class="card-body">
            <h2 class="h6 fw-bold">Circuit</h2>
            <ol class="mb-0">
                <?php foreach ($etapes as $etape): ?><li><?= e($etape['libelle']) ?></li><?php endforeach; ?>
            </ol>
        </div></div>
    </div>
</div>
