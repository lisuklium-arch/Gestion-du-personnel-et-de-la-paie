<?php
/** Liste de demandes. Variables : $demandes, $intro, $soldes|null, $nouveau|null, $voir, $montrerEmploye */
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <p class="page-kicker mb-1">Congés</p>
        <h1 class="h3 fw-bold mb-1"><?= e($titre) ?></h1>
        <p class="text-secondary mb-0"><?= e($intro) ?></p>
    </div>
    <?php if ($nouveau): ?>
        <a class="btn btn-primary" href="<?= e($nouveau) ?>">Nouvelle demande</a>
    <?php endif; ?>
</div>

<?php if (!empty($soldes)): ?>
<div class="card mb-3"><div class="card-body">
    <h2 class="h6 fw-bold">Soldes</h2>
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th scope="col">Type</th><th scope="col">Année</th><th scope="col">Acquis</th><th scope="col">Réservé</th><th scope="col">Pris</th><th scope="col">Disponible</th></tr></thead>
        <tbody>
        <?php foreach ($soldes as $s): ?>
            <tr>
                <td><?= e($s['libelle']) ?></td>
                <td><?= (int) $s['annee'] ?></td>
                <td><?= e(nombre($s['jours_acquis'])) ?></td>
                <td><?= e(nombre($s['jours_reserves'])) ?></td>
                <td><?= e(nombre($s['jours_pris'])) ?></td>
                <td><strong><?= e(nombre($s['jours_disponibles'])) ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div></div>
<?php endif; ?>

<div class="card"><div class="card-body">
    <h2 class="h6 fw-bold">Demandes</h2>
    <?php if ($demandes === []): ?>
        <p class="mb-0 text-secondary">Aucune demande.</p>
    <?php else: ?>
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr>
            <?php if ($montrerEmploye): ?><th scope="col">Employé</th><?php endif; ?>
            <th scope="col">Type</th><th scope="col">Période</th><th scope="col">Jours</th><th scope="col">Statut</th><th scope="col"></th>
        </tr></thead>
        <tbody>
        <?php foreach ($demandes as $d): [$lib, $classe] = statut_conge($d['statut']); ?>
            <tr>
                <?php if ($montrerEmploye): ?><td><?= e($d['prenom'] . ' ' . $d['nom']) ?><div class="small text-secondary"><?= e($d['matricule']) ?></div></td><?php endif; ?>
                <td><?= e($d['type_libelle']) ?></td>
                <td><?= e(date_fr(substr((string) $d['date_debut'], 0, 10))) ?> – <?= e(date_fr(substr((string) $d['date_fin'], 0, 10))) ?></td>
                <td><?= e(nombre($d['nb_jours_ouvres'])) ?></td>
                <td><span class="badge <?= e($classe) ?>"><?= e($lib) ?></span></td>
                <td class="text-end"><a href="<?= e(url($voir . '?id=' . (int) $d['id_demande_conge'])) ?>">Ouvrir</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div></div>
