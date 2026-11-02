<?php
/** Détail. Variables : $d, $validations, $mouvements, $prochaine, $etapes, $justificatifOk, $action, $peutAnnuler, $peutAnnulerRh, $retour */
[$libStatut, $classeStatut] = statut_conge($d['statut']);
$actionUrl = ['avis' => '/chef/conges/avis', 'rh' => '/rh/conges/decision', 'dg' => '/direction/conges/decision'][$action] ?? null;
$favorable = $action === 'avis' ? 'Avis favorable' : 'Approuver';
$defavorable = $action === 'avis' ? 'Avis défavorable' : 'Refuser';
$bouton = $action === 'avis' ? 'Enregistrer l\'avis' : 'Enregistrer la décision';
$cellule = static function (string $valeur): string {
    return abs((float) $valeur) < 0.001 ? '—' : e(nombre($valeur));
};
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 fw-bold mb-1"><?= e($d['prenom'] . ' ' . $d['nom']) ?></h1>
        <p class="text-secondary mb-0"><?= e($d['type_libelle']) ?> · <?= e($d['matricule']) ?> · <?= e($d['departement']) ?></p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url($retour)) ?>">Retour</a>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 fw-bold mb-0">Demande</h2>
                <span class="badge <?= e($classeStatut) ?>"><?= e($libStatut) ?></span>
            </div>
            <div class="d-flex justify-content-between gap-3 py-2 border-top"><span class="text-secondary">Du</span><strong><?= e(date_fr(substr((string) $d['date_debut'], 0, 10)) . ' (' . periode_conge($d['periode_debut']) . ')') ?></strong></div>
            <div class="d-flex justify-content-between gap-3 py-2 border-top"><span class="text-secondary">Au</span><strong><?= e(date_fr(substr((string) $d['date_fin'], 0, 10)) . ' (' . periode_conge($d['periode_fin']) . ')') ?></strong></div>
            <div class="d-flex justify-content-between gap-3 py-2 border-top"><span class="text-secondary">Jours ouvrables</span><strong><?= e(nombre($d['nb_jours_ouvres'])) ?></strong></div>
            <div class="d-flex justify-content-between gap-3 py-2 border-top"><span class="text-secondary">Solde</span><strong><?= (int) $d['decompte_solde'] === 1 ? 'Décompté' : 'Non décompté' ?></strong></div>
            <div class="d-flex justify-content-between gap-3 py-2 border-top"><span class="text-secondary">Déposée le</span><strong><?= e(date('d/m/Y H:i', strtotime((string) $d['date_depot']))) ?></strong></div>
            <div class="py-2 border-top"><span class="text-secondary d-block">Motif</span><?= e($d['motif'] ?: '—') ?></div>
            <div class="py-2 border-top">
                <?php if ($justificatifOk): ?>
                    <a href="<?= e(url('/conges/justificatif?id=' . (int) $d['id_demande_conge'])) ?>">Télécharger le justificatif</a>
                <?php elseif (!empty($d['justificatif'])): ?>
                    <span class="text-secondary">Pièce déclarée : <?= e($d['justificatif']) ?> (fichier absent de ce serveur)</span>
                <?php else: ?>
                    <span class="text-secondary">Aucun justificatif</span>
                <?php endif; ?>
            </div>
        </div></div>

        <?php if ($actionUrl !== null): ?>
        <div class="card mb-3"><div class="card-body">
            <h2 class="h6 fw-bold">Votre décision</h2>
            <form method="post" action="<?= e(url($actionUrl)) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $d['id_demande_conge'] ?>">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="decision" id="decision-ok" value="APPROUVE" required>
                    <label class="form-check-label" for="decision-ok"><?= e($favorable) ?></label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="decision" id="decision-non" value="REFUSE">
                    <label class="form-check-label" for="decision-non"><?= e($defavorable) ?></label>
                </div>
                <label for="commentaire" class="form-label fw-semibold">Commentaire <span class="fw-normal text-secondary">obligatoire en cas de refus</span></label>
                <textarea class="form-control mb-3" id="commentaire" name="commentaire" rows="3" maxlength="255"></textarea>
                <button type="submit" class="btn btn-primary"><?= e($bouton) ?></button>
            </form>
        </div></div>
        <?php endif; ?>

        <?php if ($peutAnnuler): ?>
        <form method="post" action="<?= e(url('/employe/conges/annuler')) ?>" class="mb-3">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $d['id_demande_conge'] ?>">
            <button type="submit" class="btn btn-outline-danger">Annuler cette demande</button>
            <p class="small text-secondary mt-2 mb-0">Les jours réservés seront libérés. Cette action est définitive.</p>
        </form>
        <?php endif; ?>

        <?php if ($peutAnnulerRh): ?>
        <form method="post" action="<?= e(url('/rh/conges/annuler')) ?>" class="mb-3">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $d['id_demande_conge'] ?>">
            <button type="submit" class="btn btn-outline-danger">Annuler ce congé approuvé</button>
            <p class="small text-secondary mt-2 mb-0">Possible seulement avant le premier jour. Les jours pris sont restitués par une nouvelle ligne de journal.</p>
        </form>
        <?php endif; ?>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card mb-3"><div class="card-body">
            <h2 class="h6 fw-bold">Circuit</h2>
            <ol class="mb-2">
                <?php foreach ($etapes as $etape): ?><li><?= e($etape['libelle']) ?></li><?php endforeach; ?>
            </ol>
            <?php if ($prochaine): ?><p class="mb-0">Prochaine étape : <strong><?= e($prochaine) ?></strong></p><?php else: ?><p class="mb-0 text-secondary">Circuit terminé.</p><?php endif; ?>
        </div></div>
        <div class="card mb-3"><div class="card-body">
            <h2 class="h6 fw-bold">Décisions</h2>
            <?php if ($validations === []): ?>
                <p class="mb-0 text-secondary">Aucune décision enregistrée.</p>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($validations as $v): ?>
                        <li class="border-top py-2">
                            <strong><?= e(etape_conge($v['etape'])) ?></strong> · <?= $v['decision'] === 'APPROUVE' ? 'Favorable' : 'Défavorable' ?>
                            <div class="small text-secondary"><?= e($v['prenom'] . ' ' . $v['nom']) ?> · <?= e(date('d/m/Y H:i', strtotime((string) $v['date_validation']))) ?></div>
                            <?php if (!empty($v['commentaire'])): ?><div><?= e($v['commentaire']) ?></div><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div></div>
        <div class="card"><div class="card-body">
            <h2 class="h6 fw-bold">Journal des mouvements</h2>
            <?php if ((int) $d['decompte_solde'] !== 1): ?>
                <p class="mb-0 text-secondary">Ce type de congé ne passe pas par le journal des soldes.</p>
            <?php elseif ($mouvements === []): ?>
                <p class="mb-0 text-secondary">Aucune ligne. Un congé qui décompte le solde devrait en avoir une dès le dépôt.</p>
            <?php else: ?>
            <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                <thead><tr><th scope="col">Mouvement</th><th scope="col">Réserve</th><th scope="col">Pris</th></tr></thead>
                <tbody>
                <?php foreach ($mouvements as $m): ?>
                    <tr>
                        <td><?= e(mouvement_conge($m['type_mouvement'])) ?><div class="small text-secondary"><?= e($m['motif']) ?></div></td>
                        <td><?= $cellule((string) $m['delta_reserve']) ?></td>
                        <td><?= $cellule((string) $m['delta_pris']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>
        </div></div>
    </div>
</div>
