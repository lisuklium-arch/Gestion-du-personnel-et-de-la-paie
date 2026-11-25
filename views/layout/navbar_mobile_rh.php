<?php
require __DIR__ . '/nav_items_rh.php';
?>
<nav class="app-bottom-nav" aria-label="Navigation mobile RH">
    <?php
    $bottomMap = [
        'rh_tableau'  => $rhItems['rh_tableau'] ?? null,
        'rh_conges'   => ['label' => 'Congés', 'short' => 'Congés', 'icon' => 'fa-regular fa-calendar', 'url' => $rhBase . 'conges_rh.php'],
        'rh_employes' => $rhItems['rh_employes'] ?? null,
        'rh_paie'     => $rhItems['rh_paie'] ?? null,
    ];
    foreach ($bottomMap as $key => $item):
        if (!$item) {
            continue;
        }
        $on = ($key === $active) || ($key === 'rh_conges' && isset($rhCongesItems[$active]));
    ?>
        <a class="app-bottom-nav__link<?= $on ? ' is-active' : '' ?>" href="<?= $item['url'] ?>">
            <i class="<?= $item['icon'] ?>" aria-hidden="true"></i>
            <span><?= h($item['short'] ?? $item['label']) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<div class="offcanvas offcanvas-start app-offcanvas" tabindex="-1" id="menuRh" aria-labelledby="menuRhLabel">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title" id="menuRhLabel">Menu RH</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
    </div>
    <div class="offcanvas-body">
        <p class="app-nav__group">Gestion RH</p>
        <ul class="app-nav app-nav--tight">
            <?php foreach ($rhItems as $key => $item): $on = ($key === $active); ?>
                <li>
                    <a class="app-nav__link<?= $on ? ' is-active' : '' ?>" href="<?= $item['url'] ?>"<?= $on ? ' aria-current="page"' : '' ?>>
                        <i class="<?= $item['icon'] ?>" aria-hidden="true"></i><?= $item['label'] ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <p class="app-nav__group">Congés</p>
        <ul class="app-nav app-nav--tight">
            <?php foreach ($rhCongesItems as $key => $item): $on = ($key === $active); ?>
                <li>
                    <a class="app-nav__link<?= $on ? ' is-active' : '' ?>" href="<?= $item['url'] ?>"<?= $on ? ' aria-current="page"' : '' ?>>
                        <i class="<?= $item['icon'] ?>" aria-hidden="true"></i><?= $item['label'] ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <p class="app-nav__group">Mon espace personnel</p>
        <ul class="app-nav app-nav--tight">
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
