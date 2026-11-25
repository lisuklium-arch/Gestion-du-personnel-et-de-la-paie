<?php

function etatCompte(string $statut): array
{
    return [
        'en_attente' => ['En attente', 'warning'],
        'actif'      => ['Compte actif', 'success'],
        'desactive'  => ['Compte désactivé', 'danger'],
    ][$statut];
}

function boutonCompte(?string $action, string $nom, bool $court = true): string
{
    $presentation = [
        'activer'    => ['btn-primary', 'fa-check', 'Activer'],
        'desactiver' => ['btn-danger-outline', 'fa-user-slash', 'Désactiver'],
        'reactiver'  => ['btn-neutral', 'fa-rotate-right', 'Réactiver'],
    ];
    if ($action === null) {
        return '<span class="text-muted">—</span>';
    }
    [$classe, $icone, $libelle] = $presentation[$action];
    return '<button type="button" class="btn text-decoration-none ' . $classe . '" data-compte-action="' . h($action) . '"' . ($court ? ' data-court' : '') . '>'
        . '<i class="fa-solid ' . $icone . ' me-2" aria-hidden="true"></i><span data-libelle>' . h($libelle . ($court ? '' : ' ' . $nom)) . '</span></button>';
}
