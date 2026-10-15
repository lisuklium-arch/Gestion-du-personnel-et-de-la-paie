<?php
require __DIR__ . '/nav_items.php';
if (in_array($role, ['cd', 'rh'], true)) {
    require __DIR__ . "/navbar_{$role}.php";
    return;
}
?>
<aside class="app-sidebar" aria-label="Navigation principale">
    <div class="app-sidebar__brand">
        <p class="app-sidebar__title " style="margin-left: 2.5rem;font-size: 2rem;">PerPay</p>
        <p class="app-sidebar__role" style="margin-left: 4rem;">Employé</p>
    </div>

    <nav aria-label="Menu Employé">
        <ul class="app-nav">
            <?php
            foreach ($navItems as $key => $item) {
                $on = ($key === $active);

                $activeClass = $on ? ' is-active' : '';
                $ariaCurrent = $on ? ' aria-current="page"' : '';
                $url = $employeBase . $item['file'];
                $icon = $item['icon'];
                $label = $item['label'];

                echo '<li>' .
                    '<a class="app-nav__link' . $activeClass . '" href="' . $url . '"' . $ariaCurrent . '>' .
                    '<i class="' . $icon . '" aria-hidden="true"></i>' . $label .
                    '</a>' .
                    '</li>';
            }
            ?>
        </ul>
    </nav>

    <div class="app-sidebar__footer">
        <p class="app-user__name">Marie Ngo Ndjock</p>
        <p class="app-user__status">Compte actif</p>
        <a href="<?= $logoutUrl ?>" class="btn btn-neutral w-100 text-decoration-none">
            <i class="fa-solid fa-right-from-bracket me-2" aria-hidden="true"></i>Se déconnecter
        </a>
    </div>
</aside>