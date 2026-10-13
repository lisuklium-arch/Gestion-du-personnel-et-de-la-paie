<?php /** Accueil d'un profil. Variables : $titre, $intro, $menu, $user */ ?>
<h1 class="h3 fw-bold"><?= e($titre) ?></h1>
<p class="text-secondary"><?= e($intro) ?></p>

<div class="row g-3">
    <div class="col-12 col-xl-6">
        <div class="card h-100"><div class="card-body">
            <h2 class="h6 fw-bold">Bonjour <?= e($user['prenom']) ?> <?= e($user['nom']) ?></h2>
            <p class="mb-1">Profil : <span class="badge text-bg-primary"><?= e(role_label($user['role'])) ?></span></p>
            <p class="small text-secondary mb-0">Étape 2 terminée : la connexion et les droits d'accès par profil fonctionnent. Le contenu de cette page arrive dans les prochaines étapes.</p>
        </div></div>
    </div>
    <div class="col-12 col-xl-6">
        <div class="card h-100"><div class="card-body">
            <h2 class="h6 fw-bold">Pages prévues pour ce profil</h2>
            <ul class="mb-0">
                <?php foreach ($menu as $section): foreach ($section['items'] as [$libelle, , $pret]): ?>
                    <?php if (!$pret): ?><li><?= e($libelle) ?></li><?php endif; ?>
                <?php endforeach; endforeach; ?>
            </ul>
        </div></div>
    </div>
</div>
