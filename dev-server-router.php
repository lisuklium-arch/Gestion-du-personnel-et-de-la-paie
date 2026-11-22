<?php
declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($uri) ? rawurldecode($uri) : '/';

if (str_contains($path, "\0")) {
    http_response_code(400);
    exit;
}

if ($path === '/') {
    header('Location: /public/login.php', true, 302);
    exit;
}

$projectRoot = realpath(__DIR__);
$target = realpath($projectRoot . DIRECTORY_SEPARATOR . ltrim($path, '/\\'));
$publicRoot = realpath($projectRoot . DIRECTORY_SEPARATOR . 'public');
$allowedPhpRoots = [
    realpath($projectRoot . DIRECTORY_SEPARATOR . 'api'),
    realpath($projectRoot . DIRECTORY_SEPARATOR . 'views'),
];

$isPublicFile = $target !== false
    && is_file($target)
    && str_starts_with($target, $publicRoot . DIRECTORY_SEPARATOR);
$isApplicationPhp = $target !== false
    && is_file($target)
    && strtolower(pathinfo($target, PATHINFO_EXTENSION)) === 'php'
    && array_filter(
        $allowedPhpRoots,
        static fn (?string $root): bool => $root !== false
            && str_starts_with($target, $root . DIRECTORY_SEPARATOR)
    ) !== [];

if (!$isPublicFile && !$isApplicationPhp) {
    http_response_code(404);
    exit('Not found');
}

return false;
