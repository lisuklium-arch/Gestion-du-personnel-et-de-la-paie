<?php /** Variables : $v, $err, $employes, $types */ ?>
<h1 class="h3 fw-bold mb-3"><?= e($titre) ?></h1>
<?php if ($err): ?><div class="alert alert-danger" role="alert">Le formulaire contient des erreurs : corrigez les champs en rouge.</div><?php endif; ?>
<form method="post" action="<?= e(url('/rh/contrats')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="card mb-3"><div class="card-body"><div class="row g-3">
        <?= form_liste('id_employe', 'Employé', $employes, $v, $err, 'Choisir…', 'col-12') ?>
        <?= form_liste('type_contrat', 'Type de contrat', $types, $v, $err, '', 'col-12 col-md-4') ?>
        <?= form_champ('date_debut', 'Début', $v, $err, 'date', 'required', 'col-12 col-md-4') ?>
        <?= form_champ('date_fin', 'Fin (CDD, stage)', $v, $err, 'date', '', 'col-12 col-md-4') ?>
        <?= form_champ('salaire_base', 'Salaire de base (FCFA)', $v, $err, 'text', 'inputmode="numeric" required placeholder="250000"', 'col-12 col-md-6') ?>
        <?= form_champ('duree', 'Durée mensuelle de travail (heures)', $v, $err, 'text', 'inputmode="decimal"', 'col-12 col-md-6') ?>
        <div class="col-12"><p class="form-text mb-0">Un nouveau contrat clôt automatiquement le contrat en cours de l’employé (la veille de la date de début). L’historique reste visible dans son dossier.</p></div>
    </div></div></div>
    <div class="d-flex gap-2"><button type="submit" class="btn btn-primary">Créer le contrat</button><a class="btn btn-outline-secondary" href="<?= e(url('/rh/organisation')) ?>">Annuler</a></div>
</form>
