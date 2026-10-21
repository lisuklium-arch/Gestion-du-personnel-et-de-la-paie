<?php
declare(strict_types=1);

/** Échappe une valeur avant de l'afficher dans une page HTML (protection contre le XSS). À utiliser pour TOUT affichage. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Lit une variable du fichier .env (ou de l'environnement). */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    return ($value === false || $value === null || $value === '') ? $default : $value;
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

/** Dossier où l'application est installée dans l'URL ('' si elle est à la racine du site). */
function base_path(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    return ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
}

/** Adresse d'une page de l'application : url('/login'). */
function url(string $path = '/'): string
{
    return base_path() . '/' . ltrim($path, '/');
}

/** Adresse d'un fichier du dossier public/assets : asset('css/app.css'). */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/** Chemin demandé par le navigateur, sans le dossier d'installation ni la query string. */
function request_path(): string
{
    $uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $base = base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    return '/' . trim($uri, '/');
}

/** Redirige vers une page DE L'APPLICATION (jamais vers un site externe : pas de "redirection ouverte"). */
function redirect(string $path): never
{
    header('Location: ' . url($path), true, 303);
    exit;
}

function role_label(string $role): string
{
    return [
        'EMPLOYE'           => 'Employé',
        'CHEF_DEPARTEMENT'  => 'Chef de département',
        'RESPONSABLE_RH'    => 'Responsable RH',
        'DIRECTEUR_GENERAL' => 'Directeur général',
    ][$role] ?? $role;
}

function client_ip(): ?string
{
    return $_SERVER['REMOTE_ADDR'] ?? null;   // on ne fait pas confiance à X-Forwarded-For (falsifiable)
}

/** En-têtes de sécurité envoyés avec chaque page. */
function send_security_headers(): void
{
    // Politique de contenu : seuls les fichiers de NOTRE site peuvent être chargés (bloque les scripts injectés).
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; "
        . "font-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cache-Control: no-store');
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
    header_remove('X-Powered-By');
}

/** Champ caché CSRF à mettre dans chaque formulaire POST. */
function csrf_field(): string
{
    return \App\Core\Csrf::field();
}

function date_fr(?string $date): string
{
    $t = $date ? strtotime($date) : false;
    return $t ? date('d/m/Y', $t) : '—';
}

function montant(mixed $valeur): string
{
    return ($valeur === null || $valeur === '') ? '—' : number_format((float) $valeur, 0, ',', "\u{00A0}") . "\u{00A0}FCFA";
}

function statut_employe(string $code): array   // [libellé, classe Bootstrap]
{
    return [
        'ACTIF'    => ['Actif', 'text-bg-success'],
        'EN_CONGE' => ['En congé', 'text-bg-warning'],
        'SUSPENDU' => ['Suspendu', 'text-bg-danger'],
        'SORTI'    => ['Sorti', 'text-bg-secondary'],
    ][$code] ?? [$code, 'text-bg-secondary'];
}

/** Nombre décimal lisible : 30.0 => "30", 18.5 => "18,5". */
function nombre(mixed $valeur): string
{
    return rtrim(rtrim(number_format((float) $valeur, 1, ',', ''), '0'), ',');
}

/** Champ de formulaire Bootstrap (texte, date, e-mail…) avec son message d'erreur. */
function form_champ(string $k, string $label, array $v, array $err, string $type = 'text', string $attrs = '', string $col = 'col-12 col-md-6'): string
{
    $mauvais = isset($err[$k]);
    return '<div class="' . $col . '"><label for="' . $k . '" class="form-label fw-semibold">' . e($label) . '</label>'
         . '<input type="' . $type . '" class="form-control' . ($mauvais ? ' is-invalid' : '') . '" id="' . $k . '" name="' . $k . '" value="' . e($v[$k] ?? '') . '" ' . $attrs . '>'
         . ($mauvais ? '<div class="invalid-feedback">' . e($err[$k]) . '</div>' : '') . '</div>';
}

/** Liste déroulante : $options = [valeur => libellé]. */
function form_liste(string $k, string $label, array $options, array $v, array $err, string $vide = 'Choisir…', string $col = 'col-12 col-md-6'): string
{
    $mauvais = isset($err[$k]);
    $h = '<div class="' . $col . '"><label for="' . $k . '" class="form-label fw-semibold">' . e($label) . '</label><select class="form-select' . ($mauvais ? ' is-invalid' : '') . '" id="' . $k . '" name="' . $k . '">';
    if ($vide !== '') { $h .= '<option value="">' . e($vide) . '</option>'; }
    foreach ($options as $valeur => $libelle) {
        $h .= '<option value="' . e($valeur) . '"' . ((string) ($v[$k] ?? '') === (string) $valeur ? ' selected' : '') . '>' . e($libelle) . '</option>';
    }
    return $h . '</select>' . ($mauvais ? '<div class="invalid-feedback">' . e($err[$k]) . '</div>' : '') . '</div>';
}
