<?php
$jp = $jp ?? 'form';
?>
<fieldset class="upload-group">
    <legend class="upload-group__legend">Justificatifs <span class="field-tag">Facultatifs</span></legend>
    <p class="upload-group__intro">Vous pouvez joindre jusqu’à 2 pièces (PDF, JPG ou PNG, 5 Mo maximum chacune). Aucune n’est obligatoire.</p>
    <div class="mb-3">
        <label for="<?= $jp ?>-justificatif-1" class="form-label">Justificatif 1 <span class="field-tag">Optionnel</span></label>
        <input type="file" id="<?= $jp ?>-justificatif-1" name="justificatif_1" class="form-control" accept=".pdf,.jpg,.jpeg,.png" data-max-mb="5">
        <div class="invalid-feedback">Fichier trop volumineux (5 Mo maximum).</div>
    </div>
    <div class="mb-0">
        <label for="<?= $jp ?>-justificatif-2" class="form-label">Justificatif 2 <span class="field-tag">Optionnel</span></label>
        <input type="file" id="<?= $jp ?>-justificatif-2" name="justificatif_2" class="form-control" accept=".pdf,.jpg,.jpeg,.png" data-max-mb="5">
        <div class="invalid-feedback">Fichier trop volumineux (5 Mo maximum).</div>
    </div>
</fieldset>
