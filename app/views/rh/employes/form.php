<?php
/** Formulaire employé (création ou modification). Variables : $employe (null = création), $v, $erreurs, $departements, $postes, $statuts, $typesContrat */
$creation = $employe === null;
$privilegie = !$creation && in_array($employe['role'], ['RESPONSABLE_RH', 'DIRECTEUR_GENERAL'], true);
$val = fn(string $k): string => e($v[$k] ?? '');
$cls = fn(string $k): string => isset($erreurs[$k]) ? ' is-invalid' : '';
$msg = fn(string $k): string => isset($erreurs[$k]) ? '<div class="invalid-feedback">' . e($erreurs[$k]) . '</div>' : '';
$action = $creation ? url('/rh/employes') : url('/rh/employes/modifier?id=' . (int) $employe['id_employe']);
$champ = function (string $k, string $label, string $type = 'text', string $attrs = '') use ($val, $cls, $msg): string {
    return '<div class="col-12 col-md-6"><label for="' . $k . '" class="form-label fw-semibold">' . e($label) . '</label>'
         . '<input type="' . $type . '" class="form-control' . $cls($k) . '" id="' . $k . '" name="' . $k . '" value="' . $val($k) . '" ' . $attrs . '>' . $msg($k) . '</div>';
};
$liste = function (string $k, string $label, array $options, string $cleId, string $cleLibelle) use ($v, $cls, $msg): string {
    $h = '<div class="col-12 col-md-6"><label for="' . $k . '" class="form-label fw-semibold">' . e($label) . '</label><select class="form-select' . $cls($k) . '" id="' . $k . '" name="' . $k . '" required><option value="">Choisir…</option>';
    foreach ($options as $o) {
        $h .= '<option value="' . (int) $o[$cleId] . '"' . ((int) ($v[$k] ?? 0) === (int) $o[$cleId] ? ' selected' : '') . '>' . e($o[$cleLibelle]) . '</option>';
    }
    return $h . '</select>' . $msg($k) . '</div>';
};
?>
<h1 class="h3 fw-bold mb-3"><?= e($titre) ?></h1>
<?php if ($erreurs): ?><div class="alert alert-danger" role="alert">Le formulaire contient des erreurs : corrigez les champs en rouge.</div><?php endif; ?>

<form method="post" action="<?= e($action) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="card mb-3"><div class="card-body">
        <h2 class="h6 fw-bold mb-3">Identité</h2>
        <div class="row g-3">
            <?= $champ('nom', 'Nom', 'text', 'required maxlength="80"') ?>
            <?= $champ('prenom', 'Prénom', 'text', 'required maxlength="80"') ?>
            <?= $champ('date_naissance', 'Date de naissance', 'date') ?>
            <?= $champ('telephone', 'Téléphone', 'tel', 'maxlength="30"') ?>
            <div class="col-12"><label for="adresse" class="form-label fw-semibold">Adresse</label>
                <input type="text" class="form-control<?= $cls('adresse') ?>" id="adresse" name="adresse" value="<?= $val('adresse') ?>" maxlength="255"><?= $msg('adresse') ?></div>
        </div>
    </div></div>

    <div class="card mb-3"><div class="card-body">
        <h2 class="h6 fw-bold mb-3">Emploi</h2>
        <div class="row g-3">
            <?= $liste('id_departement', 'Département', $departements, 'id_departement', 'nom') ?>
            <?= $liste('id_poste', 'Poste', $postes, 'id_poste', 'intitule') ?>
            <?= $champ('date_embauche', "Date d'embauche", 'date', 'required') ?>
            <?php if (!$creation): ?>
                <div class="col-12 col-md-6"><label for="statut" class="form-label fw-semibold">Statut</label>
                    <select class="form-select<?= $cls('statut') ?>" id="statut" name="statut"<?= $privilegie ? ' disabled' : '' ?>>
                        <?php foreach ($statuts as $s): ?><option value="<?= e($s) ?>"<?= ($v['statut'] ?? '') === $s ? ' selected' : '' ?>><?= e(statut_employe($s)[0]) ?></option><?php endforeach; ?>
                    </select><?= $msg('statut') ?>
                    <div class="form-text"><?= $privilegie ? 'Statut géré par la direction pour les comptes RH et direction.' : 'Suspendu ou Sorti désactive aussi le compte de connexion.' ?></div></div>
            <?php endif; ?>
        </div>
    </div></div>

    <div class="card mb-3"><div class="card-body">
        <h2 class="h6 fw-bold mb-3">Compte de connexion</h2>
        <div class="row g-3">
            <?= $champ('email', 'E-mail professionnel', 'email', 'required maxlength="150" autocomplete="off"') ?>
            <div class="col-12 col-md-6"><label class="form-label fw-semibold">Profil</label>
                <input type="text" class="form-control" value="<?= e(role_label($creation ? 'EMPLOYE' : $employe['role'])) ?>" disabled>
                <div class="form-text">Le profil « Chef de département » est attribué en désignant le chef dans la gestion des départements.</div></div>
        </div>
        <?php if ($creation): ?><p class="small text-secondary mt-3 mb-0">Un mot de passe provisoire sera généré et affiché une seule fois après la création.</p><?php endif; ?>
    </div></div>

    <?php if ($creation): ?>
    <div class="card mb-3"><div class="card-body">
        <h2 class="h6 fw-bold mb-3">Premier contrat</h2>
        <div class="row g-3">
            <div class="col-12 col-md-4"><label for="type_contrat" class="form-label fw-semibold">Type de contrat</label>
                <select class="form-select<?= $cls('type_contrat') ?>" id="type_contrat" name="type_contrat">
                    <?php foreach ($typesContrat as $t): ?><option value="<?= e($t) ?>"<?= ($v['type_contrat'] ?? 'CDI') === $t ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
                </select><?= $msg('type_contrat') ?></div>
            <div class="col-12 col-md-4"><label for="date_fin" class="form-label fw-semibold">Date de fin (CDD, stage)</label>
                <input type="date" class="form-control<?= $cls('date_fin') ?>" id="date_fin" name="date_fin" value="<?= $val('date_fin') ?>"><?= $msg('date_fin') ?></div>
            <div class="col-12 col-md-4"><label for="salaire_base" class="form-label fw-semibold">Salaire de base (FCFA)</label>
                <input type="text" inputmode="numeric" class="form-control<?= $cls('salaire_base') ?>" id="salaire_base" name="salaire_base" value="<?= $val('salaire_base') ?>" placeholder="250000"><?= $msg('salaire_base') ?></div>
        </div>
        <p class="small text-secondary mt-3 mb-0">Le contrat commence à la date d'embauche. Les soldes de congés de l'année sont créés automatiquement.</p>
    </div></div>
    <?php endif; ?>

    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary"><?= $creation ? 'Créer et activer le compte' : 'Enregistrer' ?></button>
        <a class="btn btn-outline-secondary" href="<?= e($creation ? url('/rh/employes') : url('/rh/employes/voir?id=' . (int) $employe['id_employe'])) ?>">Annuler</a>
    </div>
</form>
