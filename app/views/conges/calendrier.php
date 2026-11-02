<?php
/** Variables : $libelle, $semaines, $aujourdhui, $precedent, $suivant */
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 fw-bold mb-1"><?= e($titre) ?></h1>
        <p class="text-secondary mb-0">Congés en cours ou approuvés, et absences marquées. Ce n'est pas un pointage des présents.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a class="btn btn-outline-secondary" href="<?= e(url($precedent)) ?>">Mois précédent</a>
        <strong><?= e($libelle) ?></strong>
        <a class="btn btn-outline-secondary" href="<?= e(url($suivant)) ?>">Mois suivant</a>
    </div>
</div>
<div class="card"><div class="table-responsive">
    <table class="table table-bordered align-top mb-0">
        <thead><tr>
            <?php foreach (['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'] as $jour): ?>
                <th scope="col"><?= e($jour) ?></th>
            <?php endforeach; ?>
        </tr></thead>
        <tbody>
        <?php foreach ($semaines as $semaine): ?>
            <tr>
            <?php foreach ($semaine as $cellule): ?>
                <td class="<?= $cellule['dansMois'] ? '' : 'text-secondary' ?>">
                    <div class="small<?= $cellule['date'] === $aujourdhui ? ' fw-bold' : '' ?>"><?= e(substr((string) $cellule['date'], 8, 2)) ?></div>
                    <?php foreach ($cellule['items'] as $item): ?>
                        <div class="small">
                            <?php if ($item['href']): ?>
                                <a href="<?= e(url($item['href'])) ?>"><?= e($item['texte']) ?></a>
                            <?php else: ?>
                                <?= e($item['texte']) ?>
                            <?php endif; ?>
                            <?php if ($item['attente']): ?><span class="badge text-bg-warning">en attente</span><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </td>
            <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div>
