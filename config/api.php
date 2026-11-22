<?php

function reponseJson(int $code, array $donnees): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    exit;
}

function exigerPost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        reponseJson(405, ['ok' => false, 'message' => 'Méthode non autorisée.']);
    }
    csrfExiger();
}

function auteurCourant(): array
{
    return ['id' => (int) $_SESSION['utilisateur_id'], 'role' => (string) $_SESSION['role']];
}
