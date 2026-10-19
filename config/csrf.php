<?php

function csrfToken(): string
{
    if (isset($_SESSION['csrf'])) {
        return $_SESSION['csrf'];
    }

    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrfVerifier(string $token = ''): bool
{
    if (!isset($_SESSION['csrf'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf'], $token);
}

function csrfExiger(): void
{

    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!is_string($token) || !csrfVerifier($token)) {
        http_response_code(403);
        exit('Requête refusée (jeton CSRF invalide).');
    }
}


