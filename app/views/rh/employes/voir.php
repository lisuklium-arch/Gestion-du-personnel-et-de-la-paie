<?php /** Dossier d'un employé. Variables : $e, $contrats, $soldes */
[$libStatut, $classeStatut] = statut_employe($e['statut']);
$ligne = fn(string $l, string $valeur): string => '<div class="d-flex justify-content-between gap-3 py-2 border-top"><span class="text-secondary">' . e($l) . '</span><strong class="text-end">' . e($valeur) . '</strong></div>';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 fw-bold mb-0"><?= e($e['prenom'] . ' ' . $e['nom']) ?></h1>
        <span class="text-secondary"><?= e($e['matricule']) ?> · <?= e($e['poste']) ?> · <?= e($e['departement']) ?></span>
        <span class="badge <?= e($classeStatut) ?> ms-2"><?= e($libStatut) ?></span>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-primary" href="<?= e(url('/rh/employes/modifier?id=' . (int) $e['id_employe'])) ?>">Modifier</a>
        <a class="btn btn-outline-secondary" href="<?= e(url('/rh/employes')) ?>">Retour à la liste</a>
    </div>
</div>
<div class="row g-3">
    <div class="col-12 col-xl-6"><div class="card h-100"><div class="card-body">
        <h2 class="h6 fw-bold">Identité et emploi</h2>
        <?= $ligne('Date de naissance', date_fr($e['date_naissance'])) ?>
        <?= $ligne('Téléphone', $e['telephone'] ?: '—') ?>
        <?= $ligne('Adresse', $e['adresse'] ?: '—') ?>
        <?= $ligne("Date d'embauche", date_fr($e['date_embauche'])) ?>
    </div></div></div>
    <div class="col-12 col-xl-6"><div class="card h-100"><div class="card-body">
        <h2 class="h6 fw-bold">Compte de connexion</h2>
        <?= $ligne('E-mail', $e['email']) ?>
        <?= $ligne('Profil', role_label($e['role'])) ?>
        <?= $ligne('Statut du compte', ['ACTIF' => 'Actif', 'DESACTIVE' => 'Désactivé', 'EN_ATTENTE' => 'En attente'][$e['statut_compte']] ?? $e['statut_compte']) ?>
        <?= $ligne('Dernière connexion', $e['derniere_connexion'] ? date('d/m/Y H:i', strtotime($e['derniere_connexion'])) : 'Jamais') ?>
    </div></div></div>
    <div class="col-12"><div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h6 fw-bold mb-0">Contrats</h2>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/rh/contrats/nouveau?employe=' . (int) $e['id_employe'])) ?>">Nouveau contrat</a></div>
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th scope="col">Référence</th><th scope="col">Type</th><th scope="col">Début</th><th scope="col">Fin</th><th scope="col">Salaire de base</th><th scope="col">État</th></tr></thead>
            <tbody><?php foreach ($contrats as $c): ?>
                <tr><td><?= e($c['reference']) ?></td><td><?= e($c['type_contrat']) ?></td><td><?= e(date_fr($c['date_debut'])) ?></td><td><?= e(date_fr($c['date_fin'])) ?></td>
                    <td><?= e(montant($c['salaire_base'])) ?></td><td><?= $c['actif'] ? 'En cours' : 'Terminé' ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
    </div></div></div>
    <div class="col-12"><div class="card"><div class="card-body">
        <h2 class="h6 fw-bold">Soldes de congés</h2>
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th scope="col">Type</th><th scope="col">Année</th><th scope="col">Acquis</th><th scope="col">Pris</th><th scope="col">Restant</th></tr></thead>
            <tbody><?php foreach ($soldes as $s): ?>
                <tr><td><?= e($s['libelle']) ?></td><td><?= (int) $s['annee'] ?></td><td><?= e(nombre($s['jours_acquis'])) ?></td><td><?= e(nombre($s['jours_pris'])) ?></td>
                    <td><strong><?= e(nombre($s['jours_acquis'] - $s['jours_pris'])) ?></strong></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
    </div></div></div>
</div>
