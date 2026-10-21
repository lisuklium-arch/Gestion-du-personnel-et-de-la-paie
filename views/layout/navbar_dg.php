<?php require __DIR__ . '/nav_items_dg.php'; ?>
<aside class="app-sidebar" aria-label="Navigation principale">
    <div class="app-sidebar__brand app-sidebar__brand--center">
        <p class="app-sidebar__title">PerPay</p>
        <p class="app-sidebar__role">Directeur général</p>
    </div>

    <nav aria-label="Menu Directeur général">
        <p class="app-nav__group">Supervision</p>
        <ul class="app-nav app-nav--tight">
            <?php foreach ($dgItems as $key => $item): $on = ($key === $active); ?>
                <li>
                    <a class="app-nav__link<?= $on ? ' is-active' : '' ?>" href="<?= $item['url'] ?>"<?= $on ? ' aria-current="page"' : '' ?>>
                        <i class="<?= $item['icon'] ?>" aria-hidden="true"></i><?= $item['label'] ?>
                        <?php if (!empty($item['badge'])): ?><span class="app-nav__badge"><?= (int) $item['badge'] ?></span><?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="app-sidebar__footer">
        <p class="app-user__name"><?= h($_SESSION['nom_affiche'] ?? '') ?></p>
        <p class="app-user__status">Compte actif</p>
        <a href="<?= $logoutUrl ?>" class="btn btn-neutral w-100 text-decoration-none">
            <i class="fa-solid fa-right-from-bracket me-2" aria-hidden="true"></i>Se déconnecter
        </a>
    </div>
</aside>
