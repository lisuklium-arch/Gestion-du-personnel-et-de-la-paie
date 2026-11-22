<?php
function conge_statut_badge(string $statut): array
{
    return match ($statut) {
        'en_attente_chef' => ['Avis chef', 'badge-warning'],
        'en_attente_rh'   => ['Décision RH', 'badge-warning'],
        'en_attente_dg'   => ['Décision DG', 'badge-info'],
        'approuvee'       => ['Approuvée', 'badge-success'],
        'refusee'         => ['Refusée', 'badge-danger'],
        'annulee'         => ['Annulée', 'badge-neutral'],
        default           => [$statut, 'badge-neutral'],
    };
}

function conge_fmt_date(?string $d): string
{
    if (!$d) {
        return '—';
    }
    $t = strtotime($d);
    return $t ? date('d/m/Y', $t) : $d;
}

function conge_fmt_nb($n): string
{
    $n = (float) $n;
    return rtrim(rtrim(number_format($n, 1, ',', ''), '0'), ',');
}
