<?php /** Page d'erreur. Variables : $code, $titre, $message */ ?>
<div class="text-center">
    <p class="display-4 fw-bold text-primary mb-1"><?= e($code) ?></p>
    <h1 class="h4"><?= e($titre) ?></h1>
    <p class="text-secondary"><?= e($message) ?></p>
    <a class="btn btn-primary" href="<?= e(url('/')) ?>">Retour à l'accueil</a>
</div>
