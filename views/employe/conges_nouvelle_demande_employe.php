<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nouvelle demande de congé — PerPay</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <link rel="stylesheet" href="/public/css/tokens.css">
  <link rel="stylesheet" href="/public/css/base.css">
  <link rel="stylesheet" href="/public/css/components.css">
  <link rel="stylesheet" href="/public/css/layout.css">
</head>
<body>

  <div class="app-shell">

    <?php $active = 'conges'; include __DIR__ . '/../layout/navbar.php'; ?>

    <div class="app-main">

      <header class="app-header">
        <div class="app-header__heading">
          <h1 class="app-header__title">Nouvelle demande de congé</h1>
          <p class="app-header__sub"><a href="conges_employe.php">Congés</a> / <strong>Nouvelle demande</strong></p>
        </div>
        <button type="button" class="icon-btn" aria-label="Notifications">
          <i class="fa-regular fa-bell" aria-hidden="true"></i>
        </button>
      </header>

      <main class="app-content">
        <p class="app-meta app-meta--chips">
          <span><i class="fa-regular fa-user me-1" aria-hidden="true"></i>Marie Ngo Ndjock</span>
          <span><i class="fa-solid fa-hashtag me-1" aria-hidden="true"></i>EMP-0042</span>
          <span class="badge-status badge-status--warning"><i class="fa-solid fa-clock" aria-hidden="true"></i>Demande non encore envoyée</span>
        </p>

        <div class="app-split">

          <section class="card-panel">
            <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span><div><h2 class="card-panel__title">Période et motif</h2><p class="card-panel__sub">Complétez les informations ci-dessous</p></div></div>

            <div id="conge-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
            <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>Demande envoyée. Le chef donnera son avis, puis la RH décidera.
          </div>

            <form id="form-conge" class="needs-validation" novalidate data-confirmation="conge-confirmation">

              <div class="mb-3">
                <label for="type-conge" class="form-label">Type de congé</label>
                <select id="type-conge" name="type_conge" class="form-select" required>
                  <option value="annuel" selected>Congé annuel</option>
                  <option value="maladie">Congé maladie</option>
                  <option value="evenement">Événement familial</option>
                </select>
              </div>

              <div class="app-form-row">
                <div class="mb-3">
                  <label for="date-debut" class="form-label">Date de début</label>
                  <input type="date" id="date-debut" name="date_debut" class="form-control" value="2026-10-19" required>
                </div>
                <div class="mb-3">
                  <label for="date-fin" class="form-label">Date de fin</label>
                  <input type="date" id="date-fin" name="date_fin" class="form-control" value="2026-10-21" required>
                  <div class="invalid-feedback" id="date-fin-erreur">La date de fin doit être postérieure ou égale à la date de début.</div>
                </div>
              </div>

              <div class="mb-3">
                <label for="motif-conge" class="form-label">Motif</label>
                <textarea id="motif-conge" name="motif" class="form-control" rows="3" required>Je souhaite prendre trois jours pour un déplacement familial à Yaoundé.</textarea>
                <div class="invalid-feedback">Indiquez le motif de votre demande.</div>
              </div>

              <div class="note-info note-info--warning mb-3" role="note">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <span>Les 4 jours de CG-0261 sont déjà réservés. Cette nouvelle demande réservera 3 jours supplémentaires après envoi.</span>
              </div>

              <fieldset class="upload-group">
    <legend class="upload-group__legend">Justificatifs <span class="field-tag">Facultatifs</span></legend>
    <p class="upload-group__intro">Vous pouvez joindre jusqu’à 2 pièces (PDF, JPG ou PNG, 5 Mo maximum chacune). Aucune n’est obligatoire.</p>
    <div class="mb-3">
      <label for="conge-justificatif-1" class="form-label">Justificatif 1 <span class="field-tag">Optionnel</span></label>
      <input type="file" id="conge-justificatif-1" name="justificatif_1" class="form-control"
             accept=".pdf,.jpg,.jpeg,.png" data-max-mb="5">
      <div class="invalid-feedback">Fichier trop volumineux (5 Mo maximum).</div>
    </div>
    <div class="mb-0">
      <label for="conge-justificatif-2" class="form-label">Justificatif 2 <span class="field-tag">Optionnel</span></label>
      <input type="file" id="conge-justificatif-2" name="justificatif_2" class="form-control"
             accept=".pdf,.jpg,.jpeg,.png" data-max-mb="5">
      <div class="invalid-feedback">Fichier trop volumineux (5 Mo maximum).</div>
    </div>
  </fieldset>

              <div class="app-form-actions">
                <button type="submit" class="btn btn-primary"><i class="fa-regular fa-paper-plane me-2" aria-hidden="true"></i>Envoyer la demande</button>
                <a href="conges_employe.php" class="btn btn-neutral text-decoration-none"><i class="fa-solid fa-xmark me-2" aria-hidden="true"></i>Annuler</a>
              </div>
            </form>
          </section>

          <div class="app-stack">

            <section class="card-panel">
              <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-regular fa-clock" aria-hidden="true"></i></span><h2 class="card-panel__title">Durée calculée</h2></div>
              <div class="net-band" aria-live="polite">
                <p class="net-band__value net-band__value--md"><span id="duree-jours">3</span> <span id="duree-libelle">jours ouvrés</span></p>
                <p class="net-band__calc" id="duree-plage">lundi → mercredi</p>
              </div>
            </section>

            <section class="card-panel">
              <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i></span><h2 class="card-panel__title">Solde de congés</h2></div>
              <ul class="kv-list">
                <li><span>Avant cette demande</span><span><span class="kv-list__value">12</span> jours disponibles</span></li>
                <li><span class="chip">16 avant réservation</span></li>
                <li><span>Après réservation</span><span><span class="kv-list__value is-success" id="solde-apres">9</span> jours disponibles</span></li>
              </ul>
            </section>

            <section class="card-panel">
              <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-solid fa-diagram-project" aria-hidden="true"></i></span><h2 class="card-panel__title">Circuit de validation</h2></div>
              <ol class="step-list">
                <li class="step-list__item is-current"><span class="step-list__num">1</span><div><p class="step-list__name">Jean-Paul Mbarga</p><p class="step-list__role">Chef Finance • Avis</p></div></li>
                <li class="step-list__item"><span class="step-list__num">2</span><div><p class="step-list__name">Responsable RH</p><p class="step-list__role">Décision finale</p></div></li>
              </ol>
              <p class="card-panel__note"><i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i>Week-ends et jours fériés non décomptés.</p>
            </section>
          </div>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../layout/navbar_mobile.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/public/js/forms.js"></script>
  <script>
    (function () {
      var AVAILABLE = 12;
      var start = document.getElementById('date-debut');
      var end = document.getElementById('date-fin');
      var days = document.getElementById('duree-jours');
      var label = document.getElementById('duree-libelle');
      var range = document.getElementById('duree-plage');
      var after = document.getElementById('solde-apres');

      function parse(value) {
        var p = value.split('-');
        return new Date(Number(p[0]), Number(p[1]) - 1, Number(p[2]));
      }

      function update() {
        if (!start.value || !end.value) { return; }
        var a = parse(start.value);
        var b = parse(end.value);

        if (b < a) {
          end.setCustomValidity('Date de fin invalide.');
          end.classList.add('is-invalid');
          return;
        }
        end.setCustomValidity('');
        end.classList.remove('is-invalid');

        var n = 0;
        for (var d = new Date(a); d <= b; d.setDate(d.getDate() + 1)) {
          var w = d.getDay();
          if (w !== 0 && w !== 6) { n++; }
        }
        var fmt = { weekday: 'long' };
        days.textContent = n;
        label.textContent = n > 1 ? 'jours ouvrés' : 'jour ouvré';
        range.textContent = a.toLocaleDateString('fr-FR', fmt) + ' → ' + b.toLocaleDateString('fr-FR', fmt);
        after.textContent = Math.max(AVAILABLE - n, 0);
      }

      start.addEventListener('change', update);
      end.addEventListener('change', update);
      update();
    })();
  </script>
</body>
</html>
