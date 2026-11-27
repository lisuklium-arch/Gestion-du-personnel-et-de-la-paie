<?php

/** Affiche une photo protégée, ou les initiales si elle est absente ou indisponible. */
function avatarEmploye(array $employe, string $taille = ''): string
{
    $nom = trim((string) ($employe['prenom'] ?? '') . ' ' . (string) ($employe['nom'] ?? ''));
    $initiales = strtoupper(mb_substr((string) ($employe['prenom'] ?? ''), 0, 1) . mb_substr((string) ($employe['nom'] ?? ''), 0, 1));
    $classe = 'avatar' . ($taille === '' ? '' : ' avatar--' . $taille);
    // Les listes de comptes exposent l'id utilisateur dans `id` et l'id du dossier dans
    // `employe_id`. Une photo est toujours reliée au dossier employé.
    $id = filter_var($employe['employe_id'] ?? $employe['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $photo = is_string($employe['photo_chemin'] ?? null) ? $employe['photo_chemin'] : '';
    if ($id !== false && preg_match('#^documents/[a-f0-9]{32}\.(jpg|png)$#', $photo)) {
        $version = rawurlencode(substr($photo, 10, 32));
        return '<span class="' . h($classe) . '" aria-label="Photo de ' . h($nom) . '"><img class="avatar__image" src="/api/photo_profil.php?id=' . (int) $id . '&amp;v=' . $version . '" alt="Photo de ' . h($nom) . '"></span>';
    }
    return '<span class="' . h($classe) . '" aria-label="Initiales de ' . h($nom) . '">' . h($initiales !== '' ? $initiales : '?') . '</span>';
}
