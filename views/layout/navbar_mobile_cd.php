<?php require __DIR__ . '/nav_items_cd.php'; ?>
<nav class="app-bottom-bar" aria-label="Navigation mobile">
    <?php foreach ($cdBottom as $key): $item = $cdItems[$key]; $on = ($key === $active); ?>
        <a class="app-bottom-bar__item<?= $on ? ' is-active' : '' ?>" href="<?= $item['url'] ?>"<?= $on ? ' aria-current="page"' : '' ?>>
            <i class="<?= $item['icon'] ?>" aria-hidden="true"></i><span><?= $item['short'] ?></span>
        </a>
    <?php endforeach; ?>
    <button type="button" class="app-bottom-bar__item<?= isset($navItems[$active]) ? ' is-active' : '' ?>" data-bs-toggle="offcanvas" data-bs-target="#menu-plus" aria-controls="menu-plus">
        <i class="fa-solid fa-ellipsis" aria-hidden="true"></i><span>Plus</span>
    </button>
</nav>

<div class="offcanvas offcanvas-bottom app-offcanvas" tabindex="-1" id="menu-plus" aria-labelledby="menu-plus-titre">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title h5 mb-0" id="menu-plus-titre">Mon espace personnel</h2>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
    </div>
    <div class="offcanvas-body pt-0">
        <ul class="app-nav mt-0">
            <?php foreach ($navItems as $key => $item): $on = ($key === $active); ?>
                <li>
                    <a class="app-nav__link<?= $on ? ' is-active' : '' ?>" href="<?= $employeBase . $item['file'] ?>"<?= $on ? ' aria-current="page"' : '' ?>>
                        <i class="<?= $item['icon'] ?>" aria-hidden="true"></i><?= $item['label'] ?>
                    </a>
                </li>
            <?php endforeach; ?>
            <li>
                <a class="app-nav__link" href="<?= $logoutUrl ?>">
                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>Se déconnecter
                </a>
            </li>
        </ul>
    </div>
</div>
