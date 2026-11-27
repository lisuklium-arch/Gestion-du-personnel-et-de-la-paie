<?php
$estDg = $modeCompte === 'dg';
$listeDepts = $pdo->query('SELECT id, nom FROM departement ORDER BY nom')->fetchAll(PDO::FETCH_KEY_PAIR);
$listePostes = $pdo->query('SELECT p.id, p.intitule, pd.departement_id FROM poste p JOIN poste_departement pd ON pd.poste_id = p.id ORDER BY p.intitule')->fetchAll();
$optionsRoles = $estDg ? ['employe' => 'Employé', 'rh' => 'RH'] : ['employe' => 'Employé'];
$optionsContrat = ['CDI' => 'CDI', 'CDD' => 'CDD', 'stage' => 'Stage', 'prestation' => 'Prestation'];
$noteCompte = $estDg
    ? 'Un compte et un dossier distincts seront créés. La création d’un compte RH est réservée au Directeur général. La nomination d’un chef de département est une décision distincte du DG.'
    : 'Un compte Employé et son dossier sont créés. La nomination d’un chef de département est une décision distincte du DG.';
$messageCompte = 'Dossier employé et compte de connexion créés. Le compte est actif : les accès ont été envoyés par e-mail.';
$messageRoleRh = 'Dossier employé et compte RH créés. Le compte est en attente : activez-le depuis « Comptes RH ».';
$aideRole = $estDg
    ? 'Rôles applicatifs : Employé ou RH. Le chef de département est une nomination distincte, validée par le DG.'
    : 'Le nouveau dossier est créé avec le rôle Employé. La nomination d’un chef de département est validée par le DG.';
?>
        <div id="compte-confirmation" class="alert alert-success d-none card-panel--cd-form" role="status" aria-live="polite">
          <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i><span data-confirmation-label><?= h($messageCompte) ?></span>
        </div>

        <div id="compte-erreur" class="alert alert-danger d-none card-panel--cd-form" role="alert"></div>

        <form id="form-compte" class="needs-validation" novalidate data-api="/api/compte_creer.php" enctype="multipart/form-data" data-confirmation="compte-confirmation" data-confirmation-text="<?= h($messageCompte) ?>" data-message-role-rh="<?= h($messageRoleRh) ?>" data-role-rh="rh">
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
              <label for="compte-residence" class="form-label">Lieu de résidence <span class="field-tag">Obligatoire</span></label>
              <input type="text" id="compte-residence" name="lieu_residence" class="form-control" minlength="3" maxlength="255" autocomplete="street-address" placeholder="Quartier, ville" required>
              <div class="invalid-feedback">Indiquez un lieu de résidence entre 3 et 255 caractères.</div>
            </div>

            <div class="mb-3">
              <label for="compte-photo" class="form-label">Photo de profil <span class="field-tag">Obligatoire</span></label>
              <input type="file" id="compte-photo" name="photo" class="form-control" accept=".jpg,.jpeg,.png" data-max-mb="2" required>
              <div class="invalid-feedback">Photo JPG ou PNG obligatoire, 2 Mo maximum.</div>
              <img id="compte-photo-apercu" class="avatar-preview d-none" alt="Aperçu de la photo sélectionnée">
            </div>

            <div class="mb-3">
              <label for="compte-naissance" class="form-label">Date de naissance</label>
              <input type="date" id="compte-naissance" name="naissance" class="form-control" max="<?= $aujourdhui->format('Y-m-d') ?>" required>
              <div class="invalid-feedback">Indiquez une date de naissance valide.</div>
            </div>

            <div class="mb-3">
              <label for="compte-piece" class="form-label">Pièce jointe au dossier <span class="field-tag">Optionnel</span></label>
              <input type="file" id="compte-piece" name="piece_jointe" class="form-control" accept=".pdf,.jpg,.jpeg,.png" data-max-mb="5">
              <div class="invalid-feedback">PDF, JPG ou PNG, 5 Mo maximum.</div>
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
                <select id="compte-dept" name="departement_id" class="form-select" required>
                  <option value="" selected disabled>Choisir</option>
                  <?= optionsSelect($listeDepts) ?>
                </select>
                <div class="invalid-feedback">Choisissez un département.</div>
              </div>
              <div class="mb-3">
                <label for="compte-poste" class="form-label">Poste</label>
                <select id="compte-poste" name="poste_id" class="form-select" required>
                  <option value="" selected disabled>Choisir</option>
                  <?php foreach ($listePostes as $poste): ?><option value="<?= (int) $poste['id'] ?>" data-departement="<?= (int) $poste['departement_id'] ?>"><?= h($poste['intitule']) ?></option><?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Choisissez un poste.</div>
              </div>
            </div>

            <div class="mb-0">
              <label for="compte-role" class="form-label">Rôle</label>
              <select id="compte-role" name="role" class="form-select" required>
                <option value="" selected disabled>Choisir</option>
                <?= optionsSelect($optionsRoles) ?>
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
                <?= optionsSelect($optionsContrat) ?>
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
