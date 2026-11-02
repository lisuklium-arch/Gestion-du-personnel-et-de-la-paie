<?php /** Absences de l'équipe. Variables : $equipe, $absences, $erreurs, $v */ ?>
<h1 class="h3 fw-bold mb-1"><?= e($titre) ?></h1>
<p class="text-secondary">Une absence est un jour ouvrable manqué, sans demande de congé approuvée. Une absence non justifiée est celle que la paie pourra retenir. On ne pointe pas les présents.</p>
<?php if (!empty($erreurs['global'])): ?><div class="alert alert-danger" role="alert"><?= e($erreurs['global']) ?></div><?php endif; ?>

<div class="row g-3">
    <div class="col-12 col-xl-5">
        <div class="card"><div class="card-body">
            <h2 class="h6 fw-bold mb-3">Marquer une absence</h2>
            <?php if ($equipe === []): ?>
                <p class="mb-0">Aucun employé actif dans votre équipe.</p>
            <?php else: ?>
            <form method="post" action="<?= e(url(request_path())) ?>" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="id_employe" class="form-label fw-semibold">Employé</label>
                    <select class="form-select" id="id_employe" name="id_employe" required>
                        <option value="">Choisir…</option>
                        <?php foreach ($equipe as $e): ?>
                            <option value="<?= (int) $e['id_employe'] ?>"<?= (string) ($v['id_employe'] ?? '') === (string) $e['id_employe'] ? ' selected' : '' ?>><?= e($e['nom'] . ' ' . $e['prenom'] . ' · ' . $e['matricule']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="date_absence" class="form-label fw-semibold">Date</label>
                    <input type="date" class="form-control<?= isset($erreurs['date_absence']) ? ' is-invalid' : '' ?>" id="date_absence" name="date_absence" value="<?= e($v['date_absence'] ?? '') ?>" max="<?= e(date('Y-m-d')) ?>" required>
                    <?php if (isset($erreurs['date_absence'])): ?><div class="invalid-feedback"><?= e($erreurs['date_absence']) ?></div><?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="motif" class="form-label fw-semibold">Motif</label>
                    <input type="text" class="form-control" id="motif" name="motif" maxlength="255" value="<?= e($v['motif'] ?? '') ?>">
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="justifiee" name="justifiee" value="1"<?= !empty($v['justifiee']) ? ' checked' : '' ?>>
                    <label class="form-check-label" for="justifiee">Justifiée</label>
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form>
            <?php endif; ?>
        </div></div>
    </div>
    <div class="col-12 col-xl-7">
        <div class="card"><div class="card-body">
            <h2 class="h6 fw-bold">Dernières absences</h2>
            <?php if ($absences === []): ?>
                <p class="mb-0 text-secondary">Aucune absence marquée.</p>
            <?php else: ?>
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th scope="col">Date</th><th scope="col">Employé</th><th scope="col">Motif</th><th scope="col">Justifiée</th></tr></thead>
                <tbody>
                <?php foreach ($absences as $a): ?>
                    <tr>
                        <td><?= e(date_fr(substr((string) $a['date_absence'], 0, 10))) ?></td>
                        <td><?= e($a['prenom'] . ' ' . $a['nom']) ?></td>
                        <td><?= e($a['motif'] ?: '—') ?></td>
                        <td><?= (int) $a['justifiee'] === 1 ? 'Oui' : 'Non' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>
        </div></div>
    </div>
</div>
