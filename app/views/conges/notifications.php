<?php /** Variables : $notifications */ ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 fw-bold mb-1"><?= e($titre) ?></h1>
        <p class="text-secondary mb-0">La décision finale est aussi envoyée par e-mail au demandeur si le serveur de messagerie est configuré. Sinon, cette liste reste la référence.</p>
    </div>
    <form method="post" action="<?= e(url('/notifications/lire')) ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-secondary">Tout marquer comme lu</button>
    </form>
</div>
<div class="card"><div class="card-body">
    <?php if ($notifications === []): ?>
        <p class="mb-0 text-secondary">Aucune notification.</p>
    <?php else: ?>
        <ul class="list-unstyled mb-0">
            <?php foreach ($notifications as $n): ?>
                <li class="border-top py-3">
                    <div class="d-flex justify-content-between gap-3">
                        <strong><?= e($n['titre']) ?></strong>
                        <?php if (!(int) $n['lue']): ?><span class="badge text-bg-primary">Non lue</span><?php endif; ?>
                    </div>
                    <p class="mb-1"><?= e($n['message']) ?></p>
                    <?php if (!empty($n['lien'])): ?>
                        <a href="<?= e(url((string) $n['lien'])) ?>">Ouvrir la demande</a>
                    <?php endif; ?>
                    <div class="small text-secondary">
                        <?= e(date('d/m/Y H:i', strtotime((string) $n['date_creation']))) ?>
                        <?php if ((int) $n['envoyee_par_email'] === 1): ?> · e-mail envoyé<?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div></div>
