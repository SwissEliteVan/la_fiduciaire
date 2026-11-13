<?php

declare(strict_types=1);

/**
 * Tests HTTP du formulaire de devis, serveur local déjà démarré.
 *
 * Démarrage conseillé (PowerShell) :
 *   $env:DEVIS_THROTTLE_FILE = "$PWD\storage\devis-throttle.test.json"
 *   php -S 127.0.0.1:8123 index.php
 *
 * Exécution :
 *   php tools/tests/devis-http.php http://127.0.0.1:8123
 *
 * Limite connue : sans extension pdo_mysql ni base de recette, l'insertion
 * réelle ne peut pas être prouvée. Le test vérifie alors la reprise après
 * erreur (503) sans fuite technique. La preuve d'insertion reste à faire sur
 * un environnement MySQL (voir projet/NOW.md).
 */

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8123', '/');
$throttleFile = getenv('DEVIS_THROTTLE_FILE') ?: dirname(__DIR__, 2) . '/storage/devis-throttle.json';
$failures = 0;
$cookies = [];

// Parcours reproductible : le compteur de tentatives est un artefact de test.
@unlink($throttleFile);

function check(string $label, bool $condition): void
{
    global $failures;

    if ($condition) {
        echo "PASS  {$label}\n";
        return;
    }

    $failures++;
    echo "FAIL  {$label}\n";
}

/**
 * @return array{status: int, headers: array<string, list<string>>, body: string}
 */
function http(string $method, string $url, array $headers = [], ?string $body = null): array
{
    global $cookies;

    $lines = ['Connection: close'];

    if ($cookies !== []) {
        $pairs = [];
        foreach ($cookies as $name => $value) {
            $pairs[] = $name . '=' . $value;
        }
        $lines[] = 'Cookie: ' . implode('; ', $pairs);
    }

    foreach ($headers as $header) {
        $lines[] = $header;
    }

    $context = stream_context_create(['http' => [
        'method'          => $method,
        'header'          => implode("\r\n", $lines),
        'content'         => $body ?? '',
        'ignore_errors'   => true,
        'follow_location' => 0,
        'timeout'         => 15,
    ]]);

    $responseBody = @file_get_contents($url, false, $context);
    $status = 0;
    $responseHeaders = [];

    foreach ($http_response_header ?? [] as $line) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $match) === 1) {
            $status = (int) $match[1];
            continue;
        }

        $parts = explode(':', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $name = strtolower(trim($parts[0]));
        $responseHeaders[$name][] = trim($parts[1]);

        if ($name === 'set-cookie' && preg_match('/^([A-Za-z0-9_]+)=([^;]*)/', trim($parts[1]), $cookieMatch) === 1) {
            $cookies[$cookieMatch[1]] = $cookieMatch[2];
        }
    }

    return ['status' => $status, 'headers' => $responseHeaders, 'body' => (string) $responseBody];
}

function header_value(array $response, string $name): string
{
    $values = $response['headers'][strtolower($name)] ?? [];

    return $values === [] ? '' : implode(', ', $values);
}

function csrf_from(string $html): string
{
    return preg_match('/name="csrf_token" value="([^"]+)"/', $html, $match) === 1 ? $match[1] : '';
}

/** @return array<string, string> */
function form_data(array $override = []): array
{
    return array_merge([
        'nom'          => 'Camille Test',
        'email'        => 'camille@example.test',
        'telephone'    => '+41 21 000 00 00',
        'activite'     => 'Indépendante',
        'canton'       => 'Vaud',
        'besoin'       => 'TVA',
        'message'      => 'Bonjour, je souhaite un devis pour la TVA.',
        'consentement' => '1',
    ], $override);
}

function post_devis(string $base, array $data): array
{
    return http(
        'POST',
        $base . '/devis',
        ['Content-Type: application/x-www-form-urlencoded'],
        http_build_query($data)
    );
}

function get_devis(string $base): array
{
    return http('GET', $base . '/devis');
}

// 1. Non-régression vitrine et média hero déjà en place.
$home = http('GET', $base . '/');
check('GET / = 200', $home['status'] === 200);
check('accueil : balise video presente', str_contains($home['body'], '<video'));
check('accueil : source WebM presente', str_contains($home['body'], 'hero-video.webm'));
check('accueil : aucun cookie de session', header_value($home, 'set-cookie') === '');

foreach (['/prestations', '/tarifs', '/zones', '/mentions-legales'] as $page) {
    check('GET ' . $page . ' = 200', http('GET', $base . $page)['status'] === 200);
}

check('GET /absent = 404', http('GET', $base . '/absent')['status'] === 404);

// 2. Page de devis : jeton, piège, non-indexation, non-cache.
$devis = get_devis($base);
$token = csrf_from($devis['body']);
check('GET /devis = 200', $devis['status'] === 200);
check('formulaire : jeton CSRF present', $token !== '');
check('devis : cookie de session present', str_contains(header_value($devis, 'set-cookie'), 'LFSESSID'));
check('formulaire : champ piege present', str_contains($devis['body'], 'name="site_web"'));
check('devis : X-Robots-Tag noindex', str_contains(header_value($devis, 'x-robots-tag'), 'noindex'));
check('devis : Cache-Control no-store', str_contains(header_value($devis, 'cache-control'), 'no-store'));
check('devis : meta robots noindex', str_contains($devis['body'], 'name="robots" content="noindex'));
check('devis : aucun placeholder de configuration', !str_contains($devis['body'], 'REMPLACER_PAR'));

// 3. CSRF : requête sans jeton puis jeton invalide.
$sansJeton = post_devis($base, form_data());
check('POST sans jeton = 403', $sansJeton['status'] === 403);
check('POST sans jeton : message neutre', str_contains($sansJeton['body'], 'session a expiré'));

$devis = get_devis($base);
$token = csrf_from($devis['body']);
$mauvaisJeton = post_devis($base, form_data(['csrf_token' => 'jeton-faux']));
check('POST jeton invalide = 403', $mauvaisJeton['status'] === 403);

// 4. Saisie invalide : 422, valeurs conservées, contenu échappé.
$devis = get_devis($base);
$token = csrf_from($devis['body']);
$invalide = post_devis($base, form_data([
    'csrf_token'   => $token,
    'nom'          => '<script>alert(1)</script>',
    'email'        => 'nope',
    'message'      => 'court',
    'consentement' => '',
]));
check('POST invalide = 422', $invalide['status'] === 422);
check('POST invalide : erreurs accessibles', str_contains($invalide['body'], 'aria-invalid="true"'));
check('POST invalide : message global role=alert', str_contains($invalide['body'], 'role="alert"'));
check('POST invalide : balise echappee', str_contains($invalide['body'], '&lt;script&gt;alert(1)&lt;/script&gt;'));
check('POST invalide : aucune balise injectee', !str_contains($invalide['body'], '<script>alert(1)</script>'));
check('POST invalide : e-mail conserve', str_contains($invalide['body'], 'value="nope"'));
check('POST invalide : consentement non coche', !str_contains($invalide['body'], 'checked'));

// 5. Formulaire valide sans base : reprise 503, aucune fuite technique.
$valide = post_devis($base, form_data(['csrf_token' => $token]));
check('POST valide sans base = 503', $valide['status'] === 503);
check('POST valide : message de reprise', str_contains($valide['body'], 'momentanément indisponible'));
check('POST valide : e-mail conserve', str_contains($valide['body'], 'camille@example.test'));
check('POST valide : aucune fuite technique', !str_contains($valide['body'], 'PDOException')
    && !str_contains($valide['body'], 'could not find driver')
    && !str_contains($valide['body'], 'REMPLACER_PAR'));

// 6. Envoi trop volumineux : refus avant tout traitement.
$volumineux = post_devis($base, form_data([
    'csrf_token' => $token,
    'message'    => str_repeat('a', 40000),
]));
check('POST trop volumineux = 413', $volumineux['status'] === 413);

// 7. Piège à robots : succès neutre, aucune écriture, jeton renouvelé.
$devis = get_devis($base);
$tokenAvant = csrf_from($devis['body']);
$piege = post_devis($base, form_data([
    'csrf_token' => $tokenAvant,
    'site_web'   => 'https://spam.example',
]));
check('POST piege = 303', $piege['status'] === 303);
check('POST piege : redirection /devis', header_value($piege, 'location') === '/devis');

$succes = get_devis($base);
check('succes : confirmation affichee', str_contains($succes['body'], 'Demande envoyée'));
check('succes : formulaire masque', !str_contains($succes['body'], 'name="csrf_token"'));

$formulaire = get_devis($base);
$tokenApres = csrf_from($formulaire['body']);
check('succes : confirmation non repetee au rechargement', !str_contains($formulaire['body'], 'role="status"'));
check('succes : jeton CSRF renouvele', $tokenApres !== '' && $tokenApres !== $tokenAvant);

// 8. Rejeu de l'ancien jeton (double envoi) : refuse, aucune insertion.
$rejoue = post_devis($base, form_data(['csrf_token' => $tokenAvant]));
check('rejeu apres succes = 403', $rejoue['status'] === 403);

// 9. Limitation des tentatives par IP : 10 acceptées, 11e bloquée.
@unlink($throttleFile);
$devis = get_devis($base);
$token = csrf_from($devis['body']);
$statuts = [];

for ($i = 1; $i <= 11; $i++) {
    $statuts[] = post_devis($base, form_data([
        'csrf_token' => $token,
        'email'      => 'invalide',
    ]))['status'];

    if (end($statuts) === 429) {
        break;
    }
}

check('limitation : 10 premieres tentatives traitees (422)', count(array_filter($statuts, static fn (int $s): bool => $s === 422)) === 10);
check('limitation : 11e tentative bloquee (429)', in_array(429, $statuts, true));

// 10. Méthode non autorisée.
$put = http('PUT', $base . '/devis', ['Content-Type: application/x-www-form-urlencoded'], 'x=1');
check('PUT /devis = 405', $put['status'] === 405);
check('PUT /devis : en-tete Allow', str_contains(header_value($put, 'allow'), 'GET'));

echo "\n" . ($failures === 0 ? 'RESULTAT : PASS' : "RESULTAT : FAIL ({$failures})") . "\n";
exit($failures === 0 ? 0 : 1);
