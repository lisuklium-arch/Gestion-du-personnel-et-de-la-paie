<?php
$estDg = $modeCompte === 'dg';
$rolesProposes = $estDg ? $roles : array_values(array_diff($roles, ['Responsable RH']));
$noteCompte = $estDg
    ? 'Un compte et un dossier distincts seront créés. Un compte Employé ou Chef de département est actif immédiatement. Un compte Responsable RH est créé en attente puis activé depuis « Comptes RH ».'
    : 'Un compte et un dossier distincts seront créés. Le compte sera en attente puis activé par la RH. Seul le Directeur général peut créer un compte Responsable RH.';
$messageCompte = $estDg
    ? 'Dossier employé et compte de connexion créés. Le compte est actif.'
    : 'Dossier employé et compte de connexion créés. Le compte est en attente d’activation.';
$messageRoleRh = 'Dossier employé et compte Responsable RH créés. Le compte est en attente : activez-le depuis « Comptes RH ».';
$aideRole = $estDg
    ? 'Rôles : Employé, Chef de département, Responsable RH. Seul le Directeur général peut créer un compte Responsable RH.'
    : 'Rôles : Employé, Chef de département. La création d’un compte Responsable RH est réservée au Directeur général.';
?>
        <div id="compte-confirmation" class="alert alert-success d-none card-panel--cd-form" role="status" aria-live="polite">
          <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i><span data-confirmation-label><?= h($messageCompte) ?></span>
        </div>

        <form id="form-compte" class="needs-validation" novalidate data-confirmation="compte-confirmation" data-confirmation-text="<?= h($messageCompte) ?>" data-message-role-rh="<?= h($messageRoleRh) ?>" data-role-rh="Responsable RH">
          <div class="form-columns">
          <section class="card-panel">
            <div class="card-panel__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-id-card" aria-hidden="true"></i></span>
              <h2 class="card-panel__title">Identité et accès</h2>
            </div>

            <div class="form-row-2">
              <div class="mb-3">
                <label for="compte-nom" class="form-label">Nom</label>
                <input type="text" id="compte-nom" name="nom" class="form-control" autocomplete="off" required>
                <div class="invalid-feedback">Indiquez le nom.</div>
              </div>
              <div class="mb-3">
                <label for="compte-prenom" class="form-label">Prénom</label>
                <input type="text" id="compte-prenom" name="prenom" class="form-control" autocomplete="off" required>
                <div class="invalid-feedback">Indiquez le prénom.</div>
              </div>
            </div>

            <div class="mb-3">
              <label for="compte-naissance" class="form-label">Date de naissance</label>
              <input type="date" id="compte-naissance" name="naissance" class="form-control" max="<?= $aujourdhui->format('Y-m-d') ?>" required>
              <div class="invalid-feedback">Indiquez une date de naissance valide.</div>
            </div>

            <div class="mb-3">
              <label for="compte-email" class="form-label">E-mail professionnel</label>
              <input type="email" id="compte-email" name="email" class="form-control" autocomplete="off" placeholder="prenom.nom@entreprise.com" required>
              <div class="invalid-feedback">Indiquez une adresse e-mail valide.</div>
            </div>

            <div class="form-row-2">
              <div class="mb-3">
                <label for="compte-tel" class="form-label">Téléphone</label>
                <input type="tel" id="compte-tel" name="telephone" class="form-control" pattern="\+?[0-9 ]{8,20}" placeholder="+237 6XX XX XX XX" required>
                <div class="invalid-feedback">Indiquez un numéro de téléphone valide.</div>
              </div>
              <div class="mb-3">
                <label for="compte-matricule" class="form-label">Matricule</label>
                <input type="text" id="compte-matricule" name="matricule" class="form-control" pattern="EMP-[0-9]{4}" placeholder="EMP-0073" required>
                <div class="invalid-feedback">Format attendu : EMP-0000.</div>
              </div>
            </div>

            <div class="form-row-2">
              <div class="mb-3">
                <label for="compte-dept" class="form-label">Département</label>
                <select id="compte-dept" name="departement" class="form-select" required>
                  <option value="" selected disabled>Choisir</option>
                  <?= optionsSelect(array_combine(array_keys($departements), array_keys($departements))) ?>
                </select>
                <div class="invalid-feedback">Choisissez un département.</div>
              </div>
              <div class="mb-3">
                <label for="compte-poste" class="form-label">Poste</label>
                <select id="compte-poste" name="poste" class="form-select" required>
                  <option value="" selected disabled>Choisir</option>
                  <?= optionsSelect(array_combine(array_keys($postes), array_keys($postes))) ?>
                </select>
                <div class="invalid-feedback">Choisissez un poste.</div>
              </div>
            </div>

            <div class="mb-0">
              <label for="compte-role" class="form-label">Rôle</label>
              <select id="compte-role" name="role" class="form-select" required>
                <option value="" selected disabled>Choisir</option>
                <?= optionsSelect(array_combine($rolesProposes, $rolesProposes)) ?>
              </select>
              <div class="invalid-feedback">Choisissez un rôle.</div>
              <p class="form-hint mb-0"><?= h($aideRole) ?></p>
            </div>
          </section>

          <section class="card-panel">
            <div class="card-panel__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-file-lines" aria-hidden="true"></i></span>
              <h2 class="card-panel__title">Contrat initial</h2>
            </div>

            <div class="mb-3">
              <label for="compte-contrat" class="form-label">Type de contrat</label>
              <select id="compte-contrat" name="type_contrat" class="form-select" data-contrat-type="compte-fin" required>
                <option value="" selected disabled>Choisir</option>
                <?= optionsSelect(array_combine(array_keys($typesContrat), array_keys($typesContrat))) ?>
              </select>
              <div class="invalid-feedback">Choisissez un type de contrat.</div>
            </div>

            <div class="form-row-2">
              <div class="mb-3">
                <label for="compte-debut" class="form-label">Date de début / embauche</label>
                <input type="date" id="compte-debut" name="date_debut" class="form-control" required>
                <div class="invalid-feedback">Indiquez la date de début.</div>
              </div>
              <div class="mb-3">
                <label for="compte-fin" class="form-label">Date de fin</label>
                <input type="date" id="compte-fin" name="date_fin" class="form-control">
                <div class="invalid-feedback">La date de fin est obligatoire hors CDI.</div>
              </div>
            </div>

            <div class="form-row-2">
              <div class="mb-3">
                <label for="compte-salaire" class="form-label">Salaire de base (FCFA)</label>
                <input type="number" id="compte-salaire" name="salaire_base" class="form-control" min="0" step="1" inputmode="numeric" required>
                <div class="invalid-feedback">Indiquez le salaire de base.</div>
              </div>
              <div class="mb-3">
                <label for="compte-heures" class="form-label">Heures mensuelles</label>
                <input type="number" id="compte-heures" name="heures_mensuelles" class="form-control" min="1" max="300" step="0.01" value="173.33" required>
                <div class="invalid-feedback">Indiquez les heures mensuelles.</div>
              </div>
            </div>

            <div class="mb-3">
              <label for="compte-fichier" class="form-label">Contrat signé <span class="field-tag">Optionnel</span></label>
              <input type="file" id="compte-fichier" name="contrat_signe" class="form-control" accept=".pdf" data-max-mb="5">
              <div class="invalid-feedback">Fichier trop volumineux (5 Mo maximum).</div>
            </div>

            <div class="note-info mb-3" role="note">
              <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
              <span><?= h($noteCompte) ?></span>
            </div>
          </section>
          </div>

          <div class="app-form-actions">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-user-plus me-2" aria-hidden="true"></i>Créer le compte</button>
            <a href="<?= h($listeUrl) ?>" class="btn btn-neutral text-decoration-none">Annuler</a>
          </div>
        </form>
