<?php /** Variables : $departements, $postes, $contrats */
$sup = fn(string $action, int $id, string $confirm): string => '<form method="post" action="' . e(url("/rh/$action/supprimer?id=$id")) . '" data-confirm="' . e($confirm) . '" class="d-inline">' . csrf_field() . '<button type="submit" class="btn btn-outline-danger btn-sm">Supprimer</button></form>';
?>
<div class="mb-3"><h1 class="h3 fw-bold mb-0">Départements, postes et contrats</h1><p class="text-secondary mb-0">Structure de l’organisation</p></div>
<div class="row g-3 mb-3">
    <div class="col-12 col-xxl-6"><div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center bg-white"><h2 class="h6 fw-bold mb-0">Départements</h2><a class="btn btn-primary btn-sm" href="<?= e(url('/rh/departements/nouveau')) ?>">Nouveau département</a></div>
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th scope="col">Département</th><th scope="col">Chef</th><th scope="col">Effectif</th><th scope="col">Actions</th></tr></thead>
            <tbody><?php foreach ($departements as $d): ?>
                <tr><td><strong><?= e($d['nom']) ?></strong><div class="small text-secondary"><?= e($d['code']) ?><?= $d['parent'] ? ' · sous ' . e($d['parent']) : '' ?></div></td>
                    <td><?= e($d['chef'] ?: '—') ?></td><td><?= (int) $d['effectif'] ?></td>
                    <td><div class="d-flex flex-wrap gap-1"><a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/rh/departements/modifier?id=' . (int) $d['id_departement'])) ?>">Modifier</a>
                        <?= $sup('departements', (int) $d['id_departement'], 'Supprimer le département ' . $d['nom'] . ' ?') ?></div></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
    </div></div>
    <div class="col-12 col-xxl-6"><div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center bg-white"><h2 class="h6 fw-bold mb-0">Postes et primes liées</h2><a class="btn btn-primary btn-sm" href="<?= e(url('/rh/postes/nouveau')) ?>">Nouveau poste</a></div>
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th scope="col">Poste</th><th scope="col">Primes automatiques</th><th scope="col">Actions</th></tr></thead>
            <tbody><?php foreach ($postes as $p): ?>
                <tr><td><strong><?= e($p['intitule']) ?></strong><div class="small text-secondary"><?= (int) $p['effectif'] ?> employé(s)</div></td>
                    <td class="small"><?= $p['primes'] ? e(implode(' · ', $p['primes'])) : '—' ?></td>
                    <td><div class="d-flex flex-wrap gap-1"><a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/rh/postes/modifier?id=' . (int) $p['id_poste'])) ?>">Modifier</a>
                        <?= $sup('postes', (int) $p['id_poste'], 'Supprimer le poste ' . $p['intitule'] . ' ?') ?></div></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
    </div></div>
</div>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center bg-white"><h2 class="h6 fw-bold mb-0">Contrats en cours</h2><a class="btn btn-primary btn-sm" href="<?= e(url('/rh/contrats/nouveau')) ?>">Nouveau contrat</a></div>
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th scope="col">Référence</th><th scope="col">Employé</th><th scope="col">Type</th><th scope="col">Début</th><th scope="col">Fin</th><th scope="col">Salaire de base</th><th scope="col">Échéance</th><th scope="col">Document</th></tr></thead>
        <tbody><?php foreach ($contrats as $c): $j = $c['jours'] === null ? null : (int) $c['jours']; ?>
            <tr><td><?= e($c['reference']) ?></td><td><a href="<?= e(url('/rh/employes/voir?id=' . (int) $c['id_employe'])) ?>"><?= e($c['prenom'] . ' ' . $c['nom']) ?></a></td><td><?= e($c['type_contrat']) ?></td>
                <td><?= e(date_fr($c['date_debut'])) ?></td><td><?= e(date_fr($c['date_fin'])) ?></td><td><?= e(montant($c['salaire_base'])) ?></td>
                <td><?php if ($j === null): ?><span class="badge text-bg-success">Aucune</span><?php elseif ($j < 0): ?><span class="badge text-bg-secondary">Expiré</span><?php elseif ($j <= 90): ?><span class="badge text-bg-warning">Dans <?= $j ?> jour<?= $j > 1 ? 's' : '' ?></span><?php else: ?><span class="badge text-bg-light border">Dans <?= $j ?> jours</span><?php endif; ?></td>
                <td><button type="button" class="btn btn-outline-secondary btn-sm" disabled>Générer (Word) · bientôt</button></td></tr>
        <?php endforeach; ?></tbody>
    </table></div>
</div>
