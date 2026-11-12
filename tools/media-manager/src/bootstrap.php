<?php

declare(strict_types=1);

/**
 * Amorçage du gestionnaire local de médias (La Fiduciaire).
 *
 * Outil local, sans dépendance runtime, réservé à 127.0.0.1.
 * - Ne sert jamais les fichiers du projet directement : seul le routeur
 *   `server.php` expose l'interface et une API contrôlée.
 * - Le JSON de gestion et les sauvegardes vivent hors répertoires publics,
 *   dans `storage/media-manager/` (déjà ignoré par Git).
 * - Aucune suppression immédiate : les fichiers retirés sont mis en corbeille
 *   dans une sauvegarde horodatée.
 */

const MM_VERSION = '1.0.0';

define('MM_TOOL_ROOT', dirname(__DIR__));
define('MM_PROJECT_ROOT', dirname(MM_TOOL_ROOT, 2));
define('MM_PUBLIC_DIR', MM_TOOL_ROOT . '/public');
define('MM_STORAGE_DIR', MM_PROJECT_ROOT . '/storage/media-manager');
define('MM_STATE_FILE', MM_STORAGE_DIR . '/media-manager.json');
define('MM_IMPORTS_DIR', MM_STORAGE_DIR . '/imports');
define('MM_BACKUPS_DIR', MM_STORAGE_DIR . '/backups');
define('MM_LOGS_DIR', MM_STORAGE_DIR . '/logs');
define('MM_HISTORY_DIR', MM_STORAGE_DIR . '/history');

const MM_MAX_UPLOAD_BYTES = 536870912; // 512 Mo, garde-fou local.

date_default_timezone_set('Europe/Zurich');

/** Extension => type de média. */
const MM_MEDIA_EXT = [
    'jpg'   => 'image',
    'jpeg'  => 'image',
    'png'   => 'image',
    'webp'  => 'image',
    'avif'  => 'image',
    'gif'   => 'image',
    'svg'   => 'image',
    'heic'  => 'image',
    'tif'   => 'image',
    'tiff'  => 'image',
    'ico'   => 'image',
    'bmp'   => 'image',
    'mp4'   => 'video',
    'webm'  => 'video',
    'mov'   => 'video',
    'm4v'   => 'video',
    'ogv'   => 'video',
    'mkv'   => 'video',
    'avi'   => 'video',
    'mp3'   => 'audio',
    'wav'   => 'audio',
    'ogg'   => 'audio',
    'oga'   => 'audio',
    'm4a'   => 'audio',
    'flac'  => 'audio',
    'aac'   => 'audio',
];

/** Extensions de fichiers texte analysés pour trouver les usages. */
const MM_TEXT_EXT = [
    'php', 'html', 'htm', 'css', 'js', 'mjs', 'cjs', 'ts', 'tsx', 'jsx',
    'vue', 'twig', 'json', 'yml', 'yaml', 'xml', 'htaccess',
];

/** Dossiers jamais inventoriés (internes, dépendances, copies de build). */
const MM_EXCLUDED_DIRS = [
    '.git', 'node_modules', 'vendor', 'storage', 'tools', 'instructions',
    'projet', '.next', 'out', 'dist', 'build', '.cache', 'coverage',
    'assets-staging', '.vscode', '.idea',
];

/** Types MIME servis par l'API de fichiers. */
const MM_MIME = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'webp' => 'image/webp',
    'avif' => 'image/avif',
    'gif'  => 'image/gif',
    'svg'  => 'image/svg+xml',
    'ico'  => 'image/x-icon',
    'bmp'  => 'image/bmp',
    'tif'  => 'image/tiff',
    'tiff' => 'image/tiff',
    'mp4'  => 'video/mp4',
    'm4v'  => 'video/mp4',
    'webm' => 'video/webm',
    'ogv'  => 'video/ogg',
    'mov'  => 'video/quicktime',
    'mkv'  => 'video/x-matroska',
    'mp3'  => 'audio/mpeg',
    'wav'  => 'audio/wav',
    'ogg'  => 'audio/ogg',
    'oga'  => 'audio/ogg',
    'm4a'  => 'audio/mp4',
    'flac' => 'audio/flac',
    'aac'  => 'audio/aac',
];

function mm_now(): string
{
    return date('c');
}

function mm_boot(): void
{
    date_default_timezone_set('Europe/Zurich');
    mm_ensure_dirs();
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('X-Frame-Options: DENY');
}

function mm_ensure_dirs(): void
{
    foreach ([MM_STORAGE_DIR, MM_IMPORTS_DIR, MM_BACKUPS_DIR, MM_LOGS_DIR, MM_HISTORY_DIR] as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }
}

/** Session locale : jeton anti-CSRF, cookie strict, aucun service externe. */
function mm_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('MMSESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    @session_start();

    if (empty($_SESSION['mm_token']) || !is_string($_SESSION['mm_token'])) {
        $_SESSION['mm_token'] = bin2hex(random_bytes(24));
    }
}

function mm_token(): string
{
    mm_session_start();

    return (string) $_SESSION['mm_token'];
}

/** Refuse tout accès non local (défense en profondeur). */
function mm_guard_local(): void
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if (!in_array($ip, ['127.0.0.1', '::1'], true)) {
        mm_fail('Accès réservé à la machine locale.', 403);
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host !== '' && !preg_match('/^(127\.0\.0\.1|localhost|\[::1\])(:\d+)?$/', $host)) {
        mm_fail('Hôte non autorisé.', 403);
    }
}

/**
 * @param array<string, mixed> $payload
 */
function mm_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

/**
 * @param array<string, mixed> $extra
 */
function mm_ok(array $extra = []): never
{
    mm_json(['ok' => true] + $extra);
}

/**
 * @param array<string, mixed> $extra
 */
function mm_fail(string $message, int $status = 400, array $extra = []): never
{
    mm_json(['ok' => false, 'erreur' => $message] + $extra, $status);
}

/**
 * @return array<string, mixed>
 */
function mm_body(): array
{
    $raw = file_get_contents('php://input');

    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $data = json_decode($raw, true);

    return is_array($data) ? $data : [];
}

function mm_require_post(): void
{
    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        mm_fail('Méthode non autorisée.', 405);
    }
}

function mm_require_token(): void
{
    mm_session_start();
    $sent = (string) ($_SERVER['HTTP_X_MM_TOKEN'] ?? '');
    $expected = (string) ($_SESSION['mm_token'] ?? '');

    if ($expected === '' || $sent === '' || !hash_equals($expected, $sent)) {
        mm_fail('Jeton de session invalide : recharge la page du gestionnaire.', 419);
    }
}


/** Chemin relatif normalisé, refusant toute tentative de traversée. */
function mm_safe_rel(string $relative): string
{
    $relative = str_replace('\\', '/', trim($relative));
    $relative = ltrim($relative, '/');

    if ($relative === '' || str_contains($relative, '..') || preg_match('/^[a-zA-Z]:/', $relative) === 1) {
        mm_fail('Chemin de média invalide.', 422);
    }

    if (str_contains($relative, "\0")) {
        mm_fail('Chemin de média invalide.', 422);
    }

    return $relative;
}

function mm_project_abs(string $relative): string
{
    return MM_PROJECT_ROOT . '/' . mm_safe_rel($relative);
}

function mm_is_media_path(string $relative): bool
{
    $ext = strtolower(pathinfo($relative, PATHINFO_EXTENSION));

    return isset(MM_MEDIA_EXT[$ext]);
}

function mm_kind_for_ext(string $ext): ?string
{
    return MM_MEDIA_EXT[strtolower($ext)] ?? null;
}

function mm_sha256(string $absolutePath): ?string
{
    if (!is_file($absolutePath)) {
        return null;
    }

    $hash = @hash_file('sha256', $absolutePath);

    return is_string($hash) ? $hash : null;
}

/**
 * Exécute une commande sans passer par un shell (tableau d'arguments).
 *
 * @param list<string> $command
 * @return array{code:int, sortie:string, erreur:string}
 */
function mm_run(array $command): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = @proc_open($command, $descriptors, $pipes, MM_PROJECT_ROOT);

    if (!is_resource($process)) {
        return ['code' => 127, 'sortie' => '', 'erreur' => 'proc_open indisponible'];
    }

    fclose($pipes[0]);
    $stdout = (string) stream_get_contents($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);

    return ['code' => $code, 'sortie' => $stdout, 'erreur' => $stderr];
}

/** Détection de pile applicative, sans supposition. */
function mm_detect_stack(): string
{
    if (is_file(MM_PROJECT_ROOT . '/composer.json')) {
        $composer = (string) @file_get_contents(MM_PROJECT_ROOT . '/composer.json');
        if (stripos($composer, 'laravel/framework') !== false) {
            return 'Laravel (PHP)';
        }
        if (stripos($composer, 'symfony/') !== false) {
            return 'Symfony (PHP)';
        }

        return 'PHP + Composer';
    }

    if (is_file(MM_PROJECT_ROOT . '/package.json')) {
        $package = (string) @file_get_contents(MM_PROJECT_ROOT . '/package.json');
        foreach (['next', 'nuxt', 'vue', 'react', 'angular', 'svelte'] as $framework) {
            if (stripos($package, '"' . $framework . '"') !== false) {
                return ucfirst($framework) . ' (Node)';
            }
        }

        return 'Node.js';
    }

    if (is_file(MM_PROJECT_ROOT . '/wp-config.php')) {
        return 'WordPress';
    }

    if (is_file(MM_PROJECT_ROOT . '/index.php')) {
        return 'PHP natif';
    }

    if (is_file(MM_PROJECT_ROOT . '/index.html')) {
        return 'HTML/CSS/JS statique';
    }

    return 'Inconnue';
}

function mm_project_name(): string
{
    return basename(MM_PROJECT_ROOT);
}


/**
 * État par défaut du gestionnaire.
 *
 * @return array<string, mixed>
 */
function mm_state_default(): array
{
    return [
        'version'     => 1,
        'genere_le'   => mm_now(),
        'projet'      => [
            'nom'      => mm_project_name(),
            'racine'   => MM_PROJECT_ROOT,
            'stack'    => mm_detect_stack(),
            'stockage' => MM_STATE_FILE,
        ],
        'inventaire'  => [],
        'decisions'   => [],
        'revision'    => 0,
        'validation'  => null,
        'application' => ['derniere' => null, 'historique' => []],
        'reglages'    => [
            'contrat' => [
                'image' => [
                    'formats'             => ['avif', 'webp', 'jpeg'],
                    'largeurs'            => [320, 640, 1280, 1920],
                    'gain_avif_min'       => 15,
                    'exiger_width_height' => true,
                    'exiger_alt'          => true,
                ],
                'video' => [
                    'formats'           => ['mp4', 'webm'],
                    'exiger_poster'     => true,
                    'autoplay'          => 'avertissement',
                    'poids_max_desktop' => 2621440,
                    'poids_max_mobile'  => 1258291,
                    'exiger_dimensions' => true,
                ],
            ],
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function mm_state_load(bool $create = true): array
{
    mm_ensure_dirs();

    if (!is_file(MM_STATE_FILE)) {
        $state = mm_state_default();
        if ($create) {
            mm_state_save($state, 'création');
        }

        return $state;
    }

    $raw = @file_get_contents(MM_STATE_FILE);
    $data = is_string($raw) ? json_decode($raw, true) : null;

    if (!is_array($data)) {
        // JSON illisible : jamais écrasé en silence, une copie est conservée.
        $broken = MM_STORAGE_DIR . '/media-manager-corrompu-' . date('Ymd-His') . '.json';
        @copy(MM_STATE_FILE, $broken);
        mm_fail('Le fichier JSON de gestion est illisible. Une copie a été conservée dans ' . $broken, 500);
    }

    $default = mm_state_default();
    foreach ($default as $key => $value) {
        if (!array_key_exists($key, $data)) {
            $data[$key] = $value;
        }
    }

    return $data;
}

/**
 * Écriture atomique + instantané d'historique borné.
 *
 * @param array<string, mixed> $state
 */
function mm_state_save(array $state, string $motif = ''): void
{
    mm_ensure_dirs();

    $export = $state;
    if (empty($export['decisions'])) {
        $export['decisions'] = new stdClass();
    }
    $export['genere_le'] = mm_now();
    $json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (!is_string($json)) {
        mm_fail('Sérialisation JSON impossible.', 500);
    }

    if (is_file(MM_STATE_FILE)) {
        $snapshot = MM_HISTORY_DIR . '/etat-' . date('Ymd-His') . '-' . substr(sha1($motif . microtime(true)), 0, 6) . '.json';
        @copy(MM_STATE_FILE, $snapshot);
        mm_prune_history();
    }

    $tmp = MM_STATE_FILE . '.tmp-' . bin2hex(random_bytes(4));

    if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
        mm_fail('Écriture du JSON de gestion impossible (droits du dossier storage).', 500);
    }

    if (!@rename($tmp, MM_STATE_FILE)) {
        @unlink($tmp);
        mm_fail('Remplacement atomique du JSON impossible.', 500);
    }
}

function mm_prune_history(int $keep = 40): void
{
    $files = glob(MM_HISTORY_DIR . '/etat-*.json') ?: [];
    if (count($files) <= $keep) {
        return;
    }

    sort($files);
    $obsolete = array_slice($files, 0, count($files) - $keep);
    foreach ($obsolete as $file) {
        @unlink($file);
    }
}

/**
 * Décision par défaut pour un média.
 *
 * @return array<string, mixed>
 */
function mm_decision_default(): array
{
    return [
        'decision'     => 'undecided',
        'note'         => '',
        'portee'       => 'globale',
        'cibles'       => [],
        'remplacement' => null,
        'retrait'      => null,
        'derogation'   => null,
        'maj_le'       => null,
    ];
}

/**
 * @param array<string, mixed> $state
 * @return array<string, mixed>
 */
function mm_decision_for(array $state, string $relative): array
{
    $decision = $state['decisions'][$relative] ?? [];

    return array_merge(mm_decision_default(), is_array($decision) ? $decision : []);
}

/**
 * Invalide la validation dès qu'une décision change.
 *
 * @param array<string, mixed> $state
 */
function mm_touch_state(array &$state): void
{
    $state['revision'] = (int) ($state['revision'] ?? 0) + 1;
    $state['validation'] = null;
}

/** Repli si l'extension mbstring n'est pas disponible (poste local minimal). */
function mm_strlen(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function mm_substr(string $value, int $start, int $length): string
{
    return function_exists('mb_substr') ? mb_substr($value, $start, $length, 'UTF-8') : substr($value, $start, $length);
}


