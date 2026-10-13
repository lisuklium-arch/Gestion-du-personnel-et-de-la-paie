<?php /** Page de connexion. Variables : $email (ancienne saisie), $erreur */ ?>
<div class="text-center mb-4">
    <div class="logo-mark mb-3" aria-hidden="true">PP</div>
    <h1 class="h3 fw-bold">Gestion du personnel et de la paie</h1>
    <p class="text-secondary mb-0">Connectez-vous avec votre compte professionnel.</p>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <?php if ($erreur): ?>
            <div class="alert alert-danger" role="alert"><?= e($erreur) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= e(url('/login')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Adresse e-mail</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>"
                       autocomplete="username" required maxlength="150" autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Mot de passe</label>
                <input type="password" class="form-control" id="password" name="password"
                       autocomplete="current-password" required maxlength="200">
            </div>
            <p class="small text-secondary">Mot de passe oublié ? Cette fonction sera ajoutée dans une prochaine étape.</p>
            <button type="submit" class="btn btn-primary w-100">Se connecter</button>
        </form>
    </div>
</div>
<p class="small text-secondary text-center mt-3">Accès réservé au personnel de l'organisation. Les profils sont attribués par les RH.</p>
