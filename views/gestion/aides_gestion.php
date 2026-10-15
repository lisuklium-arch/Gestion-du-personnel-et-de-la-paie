<?php

require_once __DIR__ . '/../cd/aides_cd.php';

const STATUTS_DOSSIER = [
    'actif'    => ['Actif', 'success'],
    'suspendu' => ['Suspendu', 'danger'],
    'sorti'    => ['Sorti', 'neutral'],
];

function nombre(int $valeur): string
{
    return str_replace(',', "\u{00A0}", number_format($valeur));
}

function nomComplet(array $employe): string
{
    return $employe['prenom'] . ' ' . $employe['nom'];
}

function dateHeure(string $date): string
{
    return (new DateTime($date))->format('d/m/Y \à H:i');
}

function dateHeureJournal(string $date): string
{
    return (new DateTime($date))->format('d/m/Y • H:i');
}

function badge(string $texte, string $ton = 'neutral', bool $point = true): string
{
    return '<span class="badge-status badge-status--' . h($ton) . ($point ? ' badge-status--dot' : '') . '">' . h($texte) . '</span>';
}

function optionsSelect(array $valeurs, string $choisie = ''): string
{
    $html = '';
    foreach ($valeurs as $valeur => $libelle) {
        $html .= '<option value="' . h($valeur) . '"' . ((string) $valeur === $choisie ? ' selected' : '') . '>' . h($libelle) . '</option>';
    }
    return $html;
}

function champLecture(string $libelle, string $valeur): void
{
    ?>
    <div class="field-box">
      <dt class="field-box__label"><?= h($libelle) ?></dt>
      <dd class="field-box__value"><?= h($valeur) ?></dd>
    </div>
    <?php
}
