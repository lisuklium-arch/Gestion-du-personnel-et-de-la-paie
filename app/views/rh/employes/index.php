<?php /** Liste des employés avec recherche instantanée. Variables : $departements, $postes */ ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 fw-bold mb-0">Employés</h1>
        <p class="text-secondary mb-0" id="compte" aria-live="polite">Chargement…</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/rh/employes/nouveau')) ?>">Nouvel employé</a>
</div>

<noscript><div class="alert alert-warning">JavaScript est nécessaire pour afficher la liste des employés.</div></noscript>
<div id="message" class="alert alert-danger d-none" role="alert"></div>

<div class="card">
    <form id="filtres" class="card-body border-bottom row g-3 align-items-end" action="#"
          data-url="<?= e(url('/rh/employes/recherche')) ?>" data-voir="<?= e(url('/rh/employes/voir')) ?>">
        <div class="col-12 col-lg-4">
            <label for="q" class="form-label fw-semibold">Rechercher</label>
            <input type="search" class="form-control" id="q" placeholder="Nom, prénom, matricule ou e-mail" maxlength="100" autocomplete="off">
        </div>
        <div class="col-12 col-sm-4 col-lg-3">
            <label for="departement" class="form-label fw-semibold">Département</label>
            <select class="form-select" id="departement">
                <option value="">Tous</option>
                <?php foreach ($departements as $d): ?><option value="<?= (int) $d['id_departement'] ?>"><?= e($d['nom']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-sm-4 col-lg-3">
            <label for="poste" class="form-label fw-semibold">Poste</label>
            <select class="form-select" id="poste">
                <option value="">Tous</option>
                <?php foreach ($postes as $p): ?><option value="<?= (int) $p['id_poste'] ?>"><?= e($p['intitule']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-sm-4 col-lg-2">
            <label for="statut" class="form-label fw-semibold">Statut</label>
            <select class="form-select" id="statut">
                <option value="">Tous</option>
                <?php foreach (['ACTIF', 'EN_CONGE', 'SUSPENDU', 'SORTI'] as $s): ?><option value="<?= e($s) ?>"><?= e(statut_employe($s)[0]) ?></option><?php endforeach; ?>
            </select>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th scope="col">Matricule</th><th scope="col">Employé</th><th scope="col">Département</th><th scope="col">Poste</th><th scope="col">Statut</th><th scope="col">Action</th></tr></thead>
            <tbody id="lignes"></tbody>
        </table>
    </div>
    <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="small text-secondary" id="plage"></span>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" id="prec" disabled>Précédent</button>
            <button type="button" class="btn btn-outline-secondary" id="suiv" disabled>Suivant</button>
        </div>
    </div>
</div>
