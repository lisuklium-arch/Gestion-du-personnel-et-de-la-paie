<?php

const MOIS_NOMS   = [1 => 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
const MOIS_MIN    = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
const MOIS_COURT  = [1 => 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
const JOURS_NOMS  = ['Lun.', 'Mar.', 'Mer.', 'Jeu.', 'Ven.', 'Sam.', 'Dim.'];
const JOURS_LONGS = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];

function h($valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

function dateFr(string $date): string
{
    $d = new DateTime($date);
    return $d->format('j') . ' ' . MOIS_MIN[(int) $d->format('n')] . ' ' . $d->format('Y');
}

function dateCourte(string $date): string
{
    return (new DateTime($date))->format('d/m/Y');
}

function dateLongueFr(DateTime $date): string
{
    return ucfirst(JOURS_LONGS[(int) $date->format('N') - 1]) . ' ' . dateFr($date->format('Y-m-d'));
}

function periodeFr(string $debut, string $fin): string
{
    $a = new DateTime($debut);
    $b = new DateTime($fin);
    $moisFin = MOIS_COURT[(int) $b->format('n')];

    if ($debut === $fin) {
        return $a->format('d') . ' ' . $moisFin . ' ' . $a->format('Y');
    }

    $moisDebut = $a->format('m') === $b->format('m') ? '' : ' ' . MOIS_COURT[(int) $a->format('n')];
    return $a->format('d') . $moisDebut . '–' . $b->format('d') . ' ' . $moisFin . ' ' . $b->format('Y');
}

function periodeLongueFr(string $debut, string $fin): string
{
    $a = new DateTime($debut);
    $b = new DateTime($fin);
    $moisFin = MOIS_MIN[(int) $b->format('n')];

    if ($a->format('Y-m') === $b->format('Y-m')) {
        return $a->format('d') . '–' . $b->format('d') . ' ' . $moisFin . ' ' . $b->format('Y');
    }

    return $a->format('d') . ' ' . MOIS_MIN[(int) $a->format('n')] . ' – ' . $b->format('d') . ' ' . $moisFin . ' ' . $b->format('Y');
}

function periodeNumerique(string $debut, string $fin): string
{
    $a = new DateTime($debut);
    $b = new DateTime($fin);
    $debutAffiche = $a->format('m') === $b->format('m') ? $a->format('d') : $a->format('d/m');
    return $debutAffiche . '–' . $b->format('d/m');
}

function joursOuvres(string $debut, string $fin): int
{
    $total = 0;
    $fin = new DateTime($fin);
    for ($jour = new DateTime($debut); $jour <= $fin; $jour->modify('+1 day')) {
        if ($jour->format('N') < 6) {
            $total++;
        }
    }
    return $total;
}

function libelleJours(int $nombre, bool $ouvres = false): string
{
    $s = $nombre > 1 ? 's' : '';
    return $nombre . ' jour' . $s . ($ouvres ? ' ouvré' . $s : '');
}

function montant(int $valeur): string
{
    return str_replace(',', "\u{00A0}", number_format($valeur)) . "\u{00A0}FCFA";
}

function premiereLettre(string $mot): string
{
    preg_match('/./u', $mot, $lettre);
    return $lettre[0] ?? '';
}

function initiales(string $nom): string
{
    $mots = preg_split('/\s+/', trim($nom));
    return premiereLettre($mots[0]) . premiereLettre(end($mots));
}

function statutJour(array $employe): array
{
    $feminin = !empty($employe['f']);

    if ($employe['jour'] === 'conge') {
        return ['En congé', 'neutral'];
    }
    if ($employe['jour'] === 'absent') {
        return [$feminin ? 'Absente' : 'Absent', 'danger'];
    }
    return [$feminin ? 'Présente' : 'Présent', 'success'];
}

function evenementsCalendrier(array $conges): array
{
    $evenements = [];

    foreach ($conges as $conge) {
        if ($conge['etat'] === 'refuse') {
            continue;
        }

        $nature = $conge['etat'] === 'valide' ? 'valide' : 'attente';
        $fin = new DateTime($conge['fin']);

        for ($jour = new DateTime($conge['debut']); $jour <= $fin; $jour->modify('+1 day')) {
            if ($jour->format('N') < 6) {
                $evenements[$jour->format('Y-m-d')][] = [$conge['emp'], $nature];
            }
        }
    }

    ksort($evenements);
    return $evenements;
}

function personne(string $nom, string $detail = '', bool $chef = false): void
{
    ?>
    <div class="cell-person">
      <span class="avatar avatar--sm" aria-hidden="true"><?= h(initiales($nom)) ?></span>
      <div>
        <p class="cell-person__name"><?= h($nom) ?><?php if ($chef): ?> <span class="chip chip--success">Chef</span><?php endif; ?></p>
        <?php if ($detail !== ''): ?><p class="cell-person__meta"><?= h($detail) ?></p><?php endif; ?>
      </div>
    </div>
    <?php
}
