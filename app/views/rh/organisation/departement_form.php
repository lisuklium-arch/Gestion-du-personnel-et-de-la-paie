<?php /** Variables : $dep (null = création), $v, $err, $parents, $chefs */ ?>
<h1 class="h3 fw-bold mb-3"><?= e($titre) ?></h1>
<?php if ($err): ?><div class="alert alert-danger" role="alert">Le formulaire contient des erreurs : corrigez les champs en rouge.</div><?php endif; ?>
<form method="post" action="<?= e($dep ? url('/rh/departements/modifier?id=' . (int) $dep['id_departement']) : url('/rh/departements')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="card mb-3"><div class="card-body"><div class="row g-3">
        <?= form_champ('code', 'Code', $v, $err, 'text', 'required maxlength="20"') ?>
        <?= form_champ('nom', 'Nom', $v, $err, 'text', 'required maxlength="100"') ?>
        <?= form_champ('description', 'Description', $v, $err, 'text', 'maxlength="255"', 'col-12') ?>
        <?= form_liste('parent', 'Département parent (organigramme)', $parents, $v + ['parent' => $v['id_departement_parent'] ?? ''], $err, 'Aucun') ?>
        <?php if ($dep): ?>
            <?= form_liste('chef', 'Chef de département', $chefs, $v + ['chef' => $v['id_chef'] ?? ''], $err, 'Aucun') ?>
            <div class="col-12"><p class="form-text mb-0">Le chef est choisi parmi les employés actifs du département. Il reçoit le profil « Chef de département » ; l’ancien chef redevient « Employé ».</p></div>
        <?php else: ?>
            <div class="col-12"><p class="form-text mb-0">Le chef pourra être désigné après l’arrivée des premiers employés (en modifiant le département).</p></div>
        <?php endif; ?>
    </div></div></div>
    <div class="d-flex gap-2"><button type="submit" class="btn btn-primary">Enregistrer</button><a class="btn btn-outline-secondary" href="<?= e(url('/rh/organisation')) ?>">Annuler</a></div>
</form>
