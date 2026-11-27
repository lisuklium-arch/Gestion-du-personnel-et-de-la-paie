<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../models/Employe.php';
require_once __DIR__ . '/../../models/DemandeModification.php';
require_once __DIR__ . '/../gestion/avatar.php';
exigerRole(['employe', 'chef', 'rh']);

$employes = new Employe($pdo);
$resume = $employes->trouverParUtilisateurId((int) $_SESSION['utilisateur_id']);
$moi = $resume === null ? null : $employes->trouver((int) $resume['id']);
if ($moi === null) {
    http_response_code(404);
    exit('Aucun dossier employé n’est associé à ce compte.');
}
$historiqueResidence = (new DemandeModification($pdo))->listerLieuResidencePourEmploye((int) $moi['id']);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf" content="<?= h(csrfToken()) ?>">
  <title>Demander une modification — PerPay</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <link rel="stylesheet" href="/public/css/tokens.css">
  <link rel="stylesheet" href="/public/css/base.css">
  <link rel="stylesheet" href="/public/css/components.css">
  <link rel="stylesheet" href="/public/css/layout.css">
  <?php include __DIR__ . '/../layout/head_notifications.php'; ?>
</head>

<body>

  <div class="app-shell">

    <?php $active = 'profil';
    include __DIR__ . '/../layout/navbar.php'; ?>

    <div class="app-main">

      <header class="app-header">
        <div class="app-header__heading">
          <h1 class="app-header__title">Demander une modification</h1>
          <p class="app-header__sub"><a href="profil_employe.php">Profil</a> · Mise à jour du dossier personnel</p>
        </div>
        <?php include __DIR__ . '/../layout/cloche.php'; ?>
      </header>

      <main class="app-content">
        <p class="app-meta app-meta--chips">
          <?= avatarEmploye($moi, 'sm') ?>
          <span><?= h($moi['prenom'] . ' ' . $moi['nom']) ?></span>
          <span class="chip chip--success"><?= h($moi['matricule']) ?></span>
          <span>Mise à jour du dossier personnel</span>
        </p>

        <section class="card-panel card-panel--form">
          <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-solid fa-pen"
                aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Information personnelle à modifier</h2>
          </div>

          <div id="profil-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
            <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>Demande envoyée. Votre dossier reste
            inchangé jusqu’à la validation de la RH.
          </div>
          <div id="profil-erreur" class="alert alert-danger d-none" role="alert"></div>

          <form id="form-profil" class="needs-validation" novalidate>
            <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">

            <div class="mb-3">
              <label for="champ-concerne" class="form-label">Champ concerné</label>
              <select id="champ-concerne" name="champ" class="form-select" required>
                <option value="nom" data-type="text" data-ancienne="<?= h($moi['nom']) ?>">Nom</option>
                <option value="prenom" data-type="text" data-ancienne="<?= h($moi['prenom']) ?>">Prénom</option>
                <option value="matricule" data-type="text" data-ancienne="<?= h($moi['matricule']) ?>">Matricule</option>
                <option value="date_naissance" data-type="date" data-ancienne="<?= h((string) $moi['date_naissance']) ?>">Date de naissance</option>
                <option value="telephone" data-type="tel" data-ancienne="<?= h((string) $moi['telephone']) ?>">Téléphone</option>
                <option value="lieu_residence" data-type="text" data-minlength="3" data-maxlength="255" data-ancienne="<?= h((string) $moi['lieu_residence']) ?>">Lieu de résidence</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="ancienne-valeur" class="form-label">Ancienne valeur</label>
              <div class="input-icon">
                <i class="fa-solid fa-pen input-icon__left" aria-hidden="true"></i>
                <input type="text" id="ancienne-valeur" class="form-control" value="<?= h($moi['nom']) ?>" readonly>
              </div>
            </div>

            <div class="mb-3">
              <label for="nouvelle-valeur" class="form-label">Nouvelle valeur</label>
              <div class="input-icon">
                <i class="fa-solid fa-pen input-icon__left" aria-hidden="true"></i>
                <input type="text" id="nouvelle-valeur" name="nouvelle_valeur" class="form-control"
                  value="" required>
                <div class="invalid-feedback">Saisissez la nouvelle valeur.</div>
              </div>
            </div>

            <div class="mb-3">
              <label for="motif-profil" class="form-label">Motif</label>
              <textarea id="motif-profil" name="motif" class="form-control" rows="2" required></textarea>
              <div class="invalid-feedback">Indiquez le motif de votre demande.</div>
            </div>

            <div class="note-info note-info--success mb-3" role="note">
              <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
              <span>Votre information actuelle reste inchangée jusqu’à la validation de la RH. La demande et sa décision seront
                conservées dans le journal.</span>
            </div>

            <div class="app-form-actions">
              <button type="submit" class="btn btn-primary"><i class="fa-regular fa-paper-plane me-2"
                  aria-hidden="true"></i>Envoyer la demande</button>
              <a href="profil_employe.php" class="btn btn-neutral text-decoration-none">Annuler</a>
            </div>
          </form>
        </section>

        <section class="card-panel">
          <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></span><h2 class="card-panel__title">Mes demandes de lieu de résidence</h2></div>
          <?php if (!$historiqueResidence): ?>
            <p class="mb-0">Aucune demande de modification du lieu de résidence.</p>
          <?php else: ?>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Historique des demandes de lieu de résidence"><table class="data-table"><thead><tr><th>Demandée le</th><th>Valeur demandée</th><th>Statut</th><th>Décision RH</th></tr></thead><tbody>
            <?php foreach ($historiqueResidence as $demande): $libelleStatut = ['en_attente' => 'En attente', 'acceptee' => 'Acceptée', 'refusee' => 'Refusée'][$demande['statut']] ?? $demande['statut']; ?>
              <tr><td><?= h((new DateTime($demande['date_demande']))->format('d/m/Y H:i')) ?></td><td><span class="data-table__muted"><?= h((string) $demande['ancienne_valeur']) ?></span> → <?= h($demande['nouvelle_valeur']) ?></td><td><?= h($libelleStatut) ?></td><td><?php if ($demande['statut'] === 'en_attente'): ?>—<?php elseif ($demande['statut'] === 'refusee'): ?><strong>Refusée</strong><br><span class="data-table__muted"><?= h((string) $demande['motif_rejet']) ?></span><?php else: ?>Acceptée<?= $demande['date_traitement'] ? ' le ' . h((new DateTime($demande['date_traitement']))->format('d/m/Y')) : '' ?><?php endif; ?></td></tr>
            <?php endforeach; ?></tbody></table></div>
          <?php endif; ?>
        </section>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../layout/navbar_mobile.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/public/js/forms.js"></script>
  <script src="/public/js/demande-modification.js"></script>
</body>

</html>
