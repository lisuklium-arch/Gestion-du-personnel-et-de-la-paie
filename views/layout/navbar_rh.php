<?php
require __DIR__ . '/nav_items_rh.php';
$persoOuvert = isset($navItems[$active]);
?>
<aside class="app-sidebar" aria-label="Navigation principale">
    <div class="app-sidebar__brand app-sidebar__brand--center">
        <p class="app-sidebar__title">PerPay</p>
        <p class="app-sidebar__role">Responsable RH</p>
    </div>

    <nav aria-label="Menu Responsable RH">
        <p class="app-nav__group">Gestion RH</p>
        <ul class="app-nav app-nav--tight">
            <?php foreach ($rhItems as $key => $item): $on = ($key === $active); ?>
                <li>
                    <a class="app-nav__link<?= $on ? ' is-active' : '' ?>" href="<?= $item['url'] ?>"<?= $on ? ' aria-current="page"' : '' ?>>
                        <i class="<?= $item['icon'] ?>" aria-hidden="true"></i><?= $item['label'] ?>
                        <?php if (!empty($item['badge'])): ?><span class="app-nav__badge"><?= (int) $item['badge'] ?></span><?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <button type="button" class="app-nav__toggle" data-bs-toggle="collapse" data-bs-target="#nav-perso"
                aria-expanded="<?= $persoOuvert ? 'true' : 'false' ?>" aria-controls="nav-perso">
            <span>Mon espace personnel</span>
            <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
        </button>
        <div class="collapse<?= $persoOuvert ? ' show' : '' ?>" id="nav-perso">
            <ul class="app-nav app-nav--tight app-nav--sub">
                <?php foreach ($navItems as $key => $item): $on = ($key === $active); ?>
                    <li>
                        <a class="app-nav__link<?= $on ? ' is-active' : '' ?>" href="<?= $employeBase . $item['file'] ?>"<?= $on ? ' aria-current="page"' : '' ?>>
                            <i class="<?= $item['icon'] ?>" aria-hidden="true"></i><?= $item['label'] ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>

    <div class="app-sidebar__footer">
        <p class="app-user__name"><?= h($_SESSION['nom_affiche'] ?? '') ?></p>
        <p class="app-user__status">Compte actif</p>
        <a href="<?= $logoutUrl ?>" class="btn btn-neutral w-100 text-decoration-none">
            <i class="fa-solid fa-right-from-bracket me-2" aria-hidden="true"></i>Se déconnecter
        </a>
    </div>
</aside>
