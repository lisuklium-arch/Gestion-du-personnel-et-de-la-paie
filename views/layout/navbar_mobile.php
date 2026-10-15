<?php
require __DIR__ . '/nav_items.php';
if (in_array($role, ['cd', 'rh'], true)) {
    require __DIR__ . "/navbar_mobile_{$role}.php";
    return;
}
?>
<nav class="app-bottom-bar" aria-label="Navigation mobile">
    <?php
    foreach ($navBottom as $key) {
        $item = $navItems[$key];
        $on = ($key === $active);

        $activeClass = $on ? ' is-active' : '';
        $ariaCurrent = $on ? ' aria-current="page"' : '';
        $url = $employeBase . $item['file'];
        $icon = $item['icon'];
        $short = $item['short'];

        echo '<a class="app-bottom-bar__item' . $activeClass . '" href="' . $url . '"' . $ariaCurrent . '>' .
            '<i class="' . $icon . '" aria-hidden="true"></i><span>' . $short . '</span>' .
            '</a>';
    }
    ?>
    <button type="button" class="app-bottom-bar__item<?= in_array($active, $navPlus, true) ? ' is-active' : '' ?>"
        data-bs-toggle="offcanvas" data-bs-target="#menu-plus" aria-controls="menu-plus">
        <i class="fa-solid fa-ellipsis" aria-hidden="true"></i><span>Plus</span>
    </button>
</nav>

<div class="offcanvas offcanvas-bottom app-offcanvas" tabindex="-1" id="menu-plus" aria-labelledby="menu-plus-titre">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title h5 mb-0" id="menu-plus-titre">Plus</h2>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
            aria-label="Fermer"></button>
    </div>
    <div class="offcanvas-body pt-0">
        <ul class="app-nav mt-0">
            <?php foreach ($navPlus as $key):
                $item = $navItems[$key];
                $on = ($key === $active); ?>
                <li>
                    <a class="app-nav__link<?= $on ? ' is-active' : '' ?>" href="<?= $employeBase . $item['file'] ?>" <?= $on ? ' aria-current="page"' : '' ?>>
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