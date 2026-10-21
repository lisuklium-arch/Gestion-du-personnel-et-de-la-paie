<?php /** Variables : $p (null = création), $v, $err */ ?>
<h1 class="h3 fw-bold mb-3"><?= e($titre) ?></h1>
<?php if ($err): ?><div class="alert alert-danger" role="alert">Le formulaire contient des erreurs : corrigez les champs en rouge.</div><?php endif; ?>
<form method="post" action="<?= e($p ? url('/rh/postes/modifier?id=' . (int) $p['id_poste']) : url('/rh/postes')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="card mb-3"><div class="card-body"><div class="row g-3">
        <?= form_champ('intitule', 'Intitulé du poste', $v, $err, 'text', 'required maxlength="100"') ?>
        <?= form_champ('description', 'Description', $v, $err, 'text', 'maxlength="255"') ?>
        <div class="col-12"><p class="form-text mb-0">Les primes liées au poste (transport, logement…) se règlent dans les paramètres de paie (étape suivante).</p></div>
    </div></div></div>
    <div class="d-flex gap-2"><button type="submit" class="btn btn-primary">Enregistrer</button><a class="btn btn-outline-secondary" href="<?= e(url('/rh/organisation')) ?>">Annuler</a></div>
</form>
