<?php

declare(strict_types=1);

/**
 * Validation et application contrôlée des décisions.
 *
 * Garanties :
 * - aucune suppression avant validation + confirmation explicite ;
 * - sauvegarde de tout fichier touché dans `storage/media-manager/backups/<ts>/` ;
 * - les médias retirés sont déplacés en corbeille, jamais effacés sec ;
 * - un média encore référencé n'est jamais supprimé ;
 * - les NON DÉCIDÉS et les CONSERVÉS ne sont jamais modifiés ;
 * - échec d'une opération isolée → opération annulée, reste du lot préservé.
 */

/**
 * Récapitulatif, conflits et contrats pour l'état courant.
 *
 * @param array<string, mixed> $state
 * @return array<string, mixed>
 */
function mm_validate_state(array $state): array
{
    $recap = [
        'conserver'        => [],
        'supprimer'        => [],
        'remplacer'        => [],
        'non_decides'      => [],
        'octets_supprimes' => 0,
        'octets_remplaces' => 0,
    ];
    $pages = [];
    $conflicts = [];
    $contracts = [];

    foreach ((array) ($state['inventaire'] ?? []) as $media) {
        $path = (string) $media['chemin'];
        $decision = mm_decision_for($state, $path);
        $choice = (string) $decision['decision'];

        if ($choice === 'keep') {
            $recap['conserver'][] = $path;
        } elseif ($choice === 'delete') {
            $recap['supprimer'][] = $path;
            $recap['octets_supprimes'] += (int) $media['octets'];
        } elseif ($choice === 'replace') {
            $recap['remplacer'][] = $path;
            $recap['octets_remplaces'] += (int) $media['octets'];
        } else {
            $recap['non_decides'][] = $path;
        }

        $exactUsages = array_values(array_filter(
            (array) ($media['usages'] ?? []),
            static fn (array $usage): bool => ($usage['exact'] ?? false) === true
        ));

        if ($choice === 'delete' || $choice === 'replace') {
            foreach ($exactUsages as $usage) {
                $pages[(string) $usage['fichier']][$path] = true;
            }
        }

        if ($choice === 'replace') {
            $replacement = is_array($decision['remplacement']) ? $decision['remplacement'] : null;
            if ($replacement === null) {
                $conflicts[] = [
                    'code'    => 'remplacement_sans_fichier',
                    'gravite' => 'bloquant',
                    'media'   => $path,
                    'message' => 'Remplacement annoncé sans fichier choisi.',
                    'detail'  => 'Choisir un fichier local (image ou vidéo) avant validation.',
                ];
                continue;
            }

            $contract = mm_contract_media($state, $media, $replacement);
            $contracts[$path] = $contract;
            $derogated = is_array($decision['derogation']) && ($decision['derogation']['accordee'] ?? false) === true;

            if ($contract['statut'] === 'bloque' && !$derogated) {
                $blockingRules = array_values(array_filter(
                    $contract['regles'],
                    static fn (array $rule): bool => $rule['statut'] === 'bloque'
                ));
                $conflicts[] = [
                    'code'    => 'contrat_bloque',
                    'gravite' => 'bloquant',
                    'media'   => $path,
                    'message' => 'Contrat média non satisfait (' . count($blockingRules) . ' règle(s) bloquante(s)).',
                    'detail'  => implode(' | ', array_map(
                        static fn (array $rule): string => $rule['libelle'] . ' → ' . $rule['correctif'],
                        $blockingRules
                    )),
                    'regles'  => $blockingRules,
                ];
            } elseif ($contract['statut'] !== 'conforme') {
                $conflicts[] = [
                    'code'    => 'contrat_avertissement',
                    'gravite' => 'avertissement',
                    'media'   => $path,
                    'message' => 'Contrat média avec ' . $contract['avertissements'] . ' avertissement(s).',
                    'detail'  => $derogated ? 'Dérogation utilisateur enregistrée.' : 'À relire avant application.',
                ];
            }
        }

        if ($choice === 'delete') {
            $contracts[$path] = mm_contract_media($state, $media);
            $retrait = is_array($decision['retrait']) && ($decision['retrait']['demande'] ?? false) === true;
            $derogated = is_array($decision['derogation']) && ($decision['derogation']['accordee'] ?? false) === true;

            if ($exactUsages !== []) {
                $conflicts[] = [
                    'code'    => 'suppression_utilisee',
                    'gravite' => $retrait ? 'avertissement' : 'bloquant',
                    'media'   => $path,
                    'message' => 'Média encore utilisé par ' . count($exactUsages) . ' référence(s).',
                    'detail'  => $retrait
                        ? 'Retrait des éléments demandé : vérifié pendant l’application (lignes simples uniquement), sinon le fichier est conservé.'
                        : 'Choisir CONSERVER, REMPLACER ou demander un retrait propre ; aucune suppression aveugle.',
                    'usages'  => $exactUsages,
                ];
            } else {
                $conflicts[] = [
                    'code'    => 'suppression_prevue',
                    'gravite' => 'information',
                    'media'   => $path,
                    'message' => 'Média inutilisé : sera déplacé en corbeille (restaurable).',
                    'detail'  => 'Aucune référence exacte détectée.',
                ];
            }

            if ($derogated) {
                $conflicts[] = [
                    'code'    => 'suppression_protegee',
                    'gravite' => 'bloquant',
                    'media'   => $path,
                    'message' => 'Média protégé (dérogation active) mais marqué SUPPRIMER.',
                    'detail'  => 'Retirer la dérogation ou changer la décision.',
                ];
            }
        }

        if ($choice === 'undecided') {
            $conflicts[] = [
                'code'    => 'non_decide',
                'gravite' => 'avertissement',
                'media'   => $path,
                'message' => 'Décision non prise : ce média ne sera ni modifié ni supprimé.',
                'detail'  => 'Reprendre plus tard grâce au filtre « non décidés ».',
            ];
        }
    }

    $pagesList = array_keys($pages);
    sort($pagesList);

    return [
        'recapitulatif' => [
            'conserver'        => $recap['conserver'],
            'supprimer'        => $recap['supprimer'],
            'remplacer'        => $recap['remplacer'],
            'non_decides'      => $recap['non_decides'],
            'nb_conserver'     => count($recap['conserver']),
            'nb_supprimer'     => count($recap['supprimer']),
            'nb_remplacer'     => count($recap['remplacer']),
            'nb_non_decides'   => count($recap['non_decides']),
            'octets_supprimes' => $recap['octets_supprimes'],
            'octets_remplaces' => $recap['octets_remplaces'],
            'pages_affectees'  => $pagesList,
        ],
        'conflits'  => $conflicts,
        'contrats'  => $contracts,
        'genere_le' => mm_now(),
    ];
}

/** Nombre de conflits bloquants dans une validation. */
function mm_validation_blocking(array $validation): int
{
    $count = 0;
    foreach ((array) ($validation['conflits'] ?? []) as $conflict) {
        if (($conflict['gravite'] ?? '') === 'bloquant') {
            $count++;
        }
    }

    return $count;
}


/**
 * Copie un fichier du projet dans la sauvegarde horodatée.
 *
 * @param array<string, mixed> $manifest
 */
function mm_backup_file(string $relative, string $backupDir, array &$manifest): void
{
    $source = mm_project_abs($relative);
    if (!is_file($source)) {
        return;
    }

    foreach ((array) $manifest['fichiers'] as $entry) {
        if (($entry['chemin'] ?? '') === $relative) {
            return;
        }
    }

    $target = $backupDir . '/files/' . $relative;
    $directory = dirname($target);
    if (!is_dir($directory)) {
        @mkdir($directory, 0775, true);
    }

    if (!@copy($source, $target)) {
        throw new RuntimeException('Sauvegarde impossible : ' . $relative);
    }

    $manifest['fichiers'][] = [
        'chemin' => $relative,
        'sha256' => mm_sha256($source),
        'octets' => (int) @filesize($source),
    ];
}

/**
 * Écrit un fichier après sauvegarde, de façon atomique.
 *
 * @param array<string, mixed> $manifest
 */
function mm_write_with_backup(string $relative, string $content, string $backupDir, array &$manifest): void
{
    mm_backup_file($relative, $backupDir, $manifest);
    $absolute = mm_project_abs($relative);

    if (!@file_put_contents($absolute, $content, LOCK_EX)) {
        throw new RuntimeException('Écriture impossible : ' . $relative);
    }
}

/**
 * Met à jour les références d'un média déplacé/renommé.
 *
 * @param list<string> $files
 * @return list<string> fichiers réellement modifiés
 */
function mm_update_references(string $oldRelative, string $newRelative, array $files, string $backupDir, array &$manifest): array
{
    $oldBase = basename($oldRelative);
    $newBase = basename($newRelative);
    $oldPath = str_replace('\\', '/', $oldRelative);
    $newPath = str_replace('\\', '/', $newRelative);
    $changed = [];

    foreach ($files as $file) {
        $relative = mm_safe_rel((string) $file);
        $absolute = mm_project_abs($relative);

        if (!is_file($absolute)) {
            continue;
        }

        $content = (string) @file_get_contents($absolute);
        $updated = str_replace([$oldPath, $oldBase], [$newPath, $newBase], $content);

        if ($updated === $content) {
            continue;
        }

        mm_write_with_backup($relative, $updated, $backupDir, $manifest);
        $changed[] = $relative;
    }

    return $changed;
}


/**
 * Supprime des lignes de référence « simples » (une balise autonome).
 * Toute autre forme est refusée : le fichier n'est pas modifié.
 *
 * @param list<string> $files
 * @return array{fichiers:list<string>, refusees:int}
 */
function mm_remove_reference_lines(string $relative, array $files, string $backupDir, array &$manifest): array
{
    $base = basename($relative);
    $changed = [];
    $refused = 0;

    foreach ($files as $file) {
        $relativeFile = mm_safe_rel((string) $file);
        $absolute = mm_project_abs($relativeFile);

        if (!is_file($absolute)) {
            continue;
        }

        $lines = preg_split("/\r\n|\n|\r/", (string) @file_get_contents($absolute)) ?: [];
        $kept = [];
        $fileRefused = 0;
        $removed = 0;

        foreach ($lines as $line) {
            if (!str_contains($line, $base)) {
                $kept[] = $line;
                continue;
            }

            $safe = preg_match('/^\s*<(source|img|picture|track)\b[^>]*>\s*$/i', $line) === 1;
            if (!$safe) {
                $fileRefused++;
                $kept[] = $line;
                continue;
            }

            $removed++;
        }

        if ($removed > 0) {
            $updated = implode("\n", $kept);
            if (str_ends_with($relativeFile, '.php')) {
                $temp = tempnam(sys_get_temp_dir(), 'mm-');
                if ($temp !== false) {
                    @file_put_contents($temp, $updated);
                    $lint = @mm_run([PHP_BINARY, '-l', $temp]);
                    @unlink($temp);
                    if ($lint['code'] !== 0) {
                        $refused += $removed;
                        continue;
                    }
                }
            }

            mm_write_with_backup($relativeFile, $updated, $backupDir, $manifest);
            $changed[] = $relativeFile;
        }

        $refused += $fileRefused;
    }

    return ['fichiers' => $changed, 'refusees' => $refused];
}

function mm_project_relative(string $absolutePath): string
{
    $root = str_replace('\\', '/', MM_PROJECT_ROOT);
    $absolute = str_replace('\\', '/', $absolutePath);

    return str_starts_with($absolute, $root . '/') ? substr($absolute, strlen($root) + 1) : $absolute;
}

/**
 * Génère les variantes d'image (largeurs cibles) et mesure le gain AVIF.
 * Aucune source n'est modifiée ; aucun markup n'est touché.
 *
 * @param array<string, mixed> $state
 * @return array<string, mixed>
 */
function mm_generate_image_variants(array $state, string $relativeImage): array
{
    $settings = (array) ($state['reglages']['contrat']['image'] ?? []);
    $widths = array_map('intval', (array) ($settings['largeurs'] ?? [320, 640, 1280, 1920]));
    $gainMin = (float) ($settings['gain_avif_min'] ?? 15);
    $absolute = mm_project_abs($relativeImage);
    $directory = dirname($absolute);
    $stem = pathinfo($absolute, PATHINFO_FILENAME);
    $probe = mm_probe_media($absolute);
    $sourceWidth = (int) ($probe['dimensions']['largeur'] ?? 0);
    $report = ['variantes' => [], 'erreurs' => [], 'gain_avif' => null];

    if (@mm_run(['magick', '-version'])['code'] !== 0) {
        $report['erreurs'][] = 'ImageMagick absent : aucune variante générée.';

        return $report;
    }

    foreach ($widths as $width) {
        if ($width <= 0 || ($sourceWidth > 0 && $width > $sourceWidth)) {
            continue;
        }

        $webpPath = $directory . '/' . $stem . '-' . $width . 'w.webp';
        $avifPath = $directory . '/' . $stem . '-' . $width . 'w.avif';

        $webp = @mm_run(['magick', $absolute, '-resize', $width . 'x', '-quality', '82', $webpPath]);
        if ($webp['code'] !== 0 || !is_file($webpPath)) {
            $report['erreurs'][] = 'Échec WebP ' . $width . 'w : ' . trim($webp['erreur']);
            continue;
        }

        $avif = @mm_run(['magick', $absolute, '-resize', $width . 'x', '-quality', '50', $avifPath]);
        $avifKept = false;
        $webpSize = (int) @filesize($webpPath);
        $avifSize = 0;
        $gain = null;

        if ($avif['code'] === 0 && is_file($avifPath)) {
            $avifSize = (int) @filesize($avifPath);
            $gain = $webpSize > 0 ? (($webpSize - $avifSize) / $webpSize) * 100 : 0;
            $report['gain_avif'] = round($gain, 2);

            if ($gain >= $gainMin) {
                $avifKept = true;
            } else {
                @unlink($avifPath);
                $report['erreurs'][] = sprintf(
                    'AVIF %dw supprimé : gain %.1f %% < %.0f %% exigés (WebP %s o, AVIF %s o).',
                    $width,
                    $gain,
                    $gainMin,
                    number_format($webpSize, 0, ',', ' '),
                    number_format($avifSize, 0, ',', ' ')
                );
            }
        } else {
            $report['erreurs'][] = 'Échec AVIF ' . $width . 'w : ' . trim($avif['erreur']);
        }

        $report['variantes'][] = [
            'largeur'     => $width,
            'webp'        => mm_project_relative($webpPath),
            'webp_octets' => $webpSize,
            'avif'        => $avifKept ? mm_project_relative($avifPath) : null,
            'avif_octets' => $avifKept ? $avifSize : null,
            'gain_avif'   => $avifKept ? round((float) $gain, 2) : null,
        ];
    }

    return $report;
}


/**
 * Vérifie la syntaxe des fichiers PHP modifiés.
 *
 * @param list<string> $relativeFiles
 * @return list<array{chemin:string, code:int, sortie:string}>
 */
function mm_lint_paths(array $relativeFiles): array
{
    $results = [];
    foreach (array_unique($relativeFiles) as $relative) {
        if (!str_ends_with(strtolower((string) $relative), '.php')) {
            continue;
        }
        $absolute = mm_project_abs((string) $relative);
        if (!is_file($absolute)) {
            continue;
        }
        $run = @mm_run([PHP_BINARY, '-l', $absolute]);
        $results[] = [
            'chemin' => (string) $relative,
            'code'   => $run['code'],
            'sortie' => trim($run['sortie'] . ' ' . $run['erreur']),
        ];
    }

    return $results;
}

/**
 * Test de fumée HTTP du site après application (serveur intégré temporaire).
 * Preuves textuelles uniquement, aucune capture.
 *
 * @return array<string, mixed>
 */
function mm_smoke_test(): array
{
    $port = null;
    for ($candidate = 8210; $candidate <= 8260; $candidate++) {
        $socket = @stream_socket_server('tcp://127.0.0.1:' . $candidate, $errno, $errstr);
        if ($socket !== false) {
            fclose($socket);
            $port = $candidate;
            break;
        }
    }

    if ($port === null) {
        return ['execute' => false, 'raison' => 'Aucun port local libre entre 8210 et 8260.', 'routes' => []];
    }

    $stdout = (string) tempnam(sys_get_temp_dir(), 'mm-smoke-out-');
    $stderr = (string) tempnam(sys_get_temp_dir(), 'mm-smoke-err-');
    $process = @proc_open(
        [PHP_BINARY, '-S', '127.0.0.1:' . $port, 'index.php'],
        [0 => ['pipe', 'r'], 1 => ['file', $stdout, 'w'], 2 => ['file', $stderr, 'w']],
        $pipes,
        MM_PROJECT_ROOT
    );

    if (!is_resource($process)) {
        @unlink($stdout);
        @unlink($stderr);

        return ['execute' => false, 'raison' => 'Démarrage du serveur de test impossible.', 'routes' => []];
    }

    fclose($pipes[0]);
    usleep(900000);

    $checks = [
        ['/', 'GET', 200],
        ['/prestations', 'GET', 200],
        ['/tarifs', 'GET', 200],
        ['/zones', 'GET', 200],
        ['/devis', 'GET', 200],
        ['/mentions-legales', 'GET', 200],
        ['/absent', 'GET', 404],
        ['/assets/css/app.css', 'HEAD', 200],
        ['/assets/js/hero.js', 'HEAD', 200],
    ];

    // Médias réellement présents à la racine du site (poster compris, quel que soit son format).
    $rootEntries = @scandir(MM_PROJECT_ROOT) ?: [];
    $rootMedia = 0;
    foreach ($rootEntries as $entry) {
        if ($rootMedia >= 4) {
            break;
        }
        if (is_file(MM_PROJECT_ROOT . '/' . $entry) && mm_is_media_path($entry)) {
            $checks[] = ['/' . $entry, 'HEAD', 200];
            $rootMedia++;
        }
    }


    $routes = [];
    foreach ($checks as [$route, $method, $expected]) {
        $context = stream_context_create([
            'http' => ['method' => $method, 'ignore_errors' => true, 'timeout' => 8],
        ]);
        $body = @file_get_contents('http://127.0.0.1:' . $port . $route, false, $context);
        $status = null;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', (string) $header, $match) === 1) {
                $status = (int) $match[1];
            }
        }
        $routes[] = [
            'route'   => $route,
            'methode' => $method,
            'attendu' => $expected,
            'obtenu'  => $status,
            'octets'  => is_string($body) ? strlen($body) : 0,
            'ok'      => $status === $expected,
        ];
    }

    proc_terminate($process);
    foreach ($pipes as $pipe) {
        if (is_resource($pipe)) {
            @fclose($pipe);
        }
    }
    proc_close($process);

    $serverErrors = trim((string) @file_get_contents($stderr));
    @unlink($stdout);
    @unlink($stderr);

    $ok = count(array_filter($routes, static fn (array $route): bool => $route['ok'] === false)) === 0;

    return [
        'execute'       => true,
        'port'          => $port,
        'routes'        => $routes,
        'ok'            => $ok,
        'erreurs_serveur' => $serverErrors !== '' ? mm_substr($serverErrors, 0, 2000) : '',
    ];
}



/**
 * Applique les décisions validées, avec sauvegardes et rollback.
 *
 * @param array<string, mixed> $state
 * @return array<string, mixed>
 */
function mm_apply_state(array $state, string $confirm): array
{
    if ($confirm !== 'APPLIQUER') {
        mm_fail('Confirmation explicite requise : saisir APPLIQUER.', 428);
    }

    $validation = is_array($state['validation'] ?? null) ? $state['validation'] : null;
    if ($validation === null) {
        mm_fail('Aucune validation enregistrée : valide tes choix avant d’appliquer.', 409);
    }
    if ((int) ($validation['revision'] ?? -1) !== (int) ($state['revision'] ?? 0)) {
        mm_fail('Les décisions ont changé depuis la validation : revalide avant d’appliquer.', 409);
    }

    $fresh = mm_validate_state($state);
    if (mm_validation_blocking($fresh) > 0) {
        mm_json([
            'ok'       => false,
            'erreur'   => 'Conflits bloquants : rien n’a été appliqué.',
            'conflits' => array_values(array_filter(
                (array) $fresh['conflits'],
                static fn (array $conflict): bool => ($conflict['gravite'] ?? '') === 'bloquant'
            )),
        ], 409);
    }

    $applicationId = date('Ymd-His') . '-' . bin2hex(random_bytes(3));
    $backupDir = MM_BACKUPS_DIR . '/' . $applicationId;

    if (!@mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
        mm_fail('Création du dossier de sauvegarde impossible.', 500);
    }

    $manifest = [
        'id'             => $applicationId,
        'horodatage'     => mm_now(),
        'projet'         => MM_PROJECT_ROOT,
        'fichiers'       => [],
        'creations'      => [],
        'corbeille'      => [],
        'remplacements'  => [],
        'retraits'       => [],
        'suppressions'   => [],
        'variantes'      => [],
        'avertissements' => [],
        'erreurs'        => [],
        'tests'          => [],
    ];

    $inventoryByPath = [];
    foreach ((array) $state['inventaire'] as $media) {
        $inventoryByPath[(string) $media['chemin']] = $media;
    }

    // Pré-contrôle : aucun fichier n'est touché si un import est manquant/altéré.
    $preflight = [];
    foreach ((array) $state['decisions'] as $path => $decision) {
        if (($decision['decision'] ?? '') !== 'replace' || !empty($decision['applique_le'])) {
            continue;
        }
        $replacement = is_array($decision['remplacement'] ?? null) ? $decision['remplacement'] : null;
        if ($replacement === null || empty($replacement['chemin_temp'])) {
            $preflight[] = $path . ' : aucun fichier de remplacement.';
            continue;
        }
        try {
            $importAbs = mm_import_abs((string) $replacement['chemin_temp']);
        } catch (Throwable $error) {
            $preflight[] = $path . ' : ' . $error->getMessage();
            continue;
        }
        if (mm_sha256($importAbs) !== ($replacement['sha256'] ?? null)) {
            $preflight[] = $path . ' : empreinte du fichier importé différente de celle enregistrée.';
        }
    }

    if ($preflight !== []) {
        mm_json(['ok' => false, 'erreur' => 'Pré-contrôle échoué : rien n’a été modifié.', 'details' => $preflight], 409);
    }

    $changedPhp = [];

    try {
        // 1. Remplacements.
        foreach ((array) $state['decisions'] as $path => $decision) {
            if (($decision['decision'] ?? '') !== 'replace' || !empty($decision['applique_le'])) {
                continue;
            }
            $media = $inventoryByPath[$path] ?? null;
            if ($media === null) {
                continue;
            }

            $replacement = (array) $decision['remplacement'];
            $tempRelative = str_replace('\\', '/', (string) ($replacement['chemin_temp'] ?? ''));
            $importAbsolute = realpath(MM_STORAGE_DIR . '/' . $tempRelative);
            $importsRoot = realpath(MM_IMPORTS_DIR);
            if (!str_starts_with($tempRelative, 'imports/') || $importAbsolute === false || $importsRoot === false
                || !str_starts_with(str_replace('\\', '/', $importAbsolute), str_replace('\\', '/', $importsRoot))) {
                throw new RuntimeException('Fichier importé introuvable : ' . $path);
            }
            $importAbs = $importAbsolute;
            $oldExt = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $newExt = strtolower((string) ($replacement['format'] ?? $oldExt));
            $directory = dirname($path);
            $directory = $directory === '.' ? '' : $directory . '/';
            $base = pathinfo($path, PATHINFO_FILENAME);
            $destRel = $oldExt === $newExt ? $path : $directory . $base . '.' . $newExt;
            $destAbs = mm_project_abs($destRel);
            $destDir = dirname($destAbs);

            if (!is_dir($destDir) && !@mkdir($destDir, 0775, true)) {
                throw new RuntimeException('Dossier de destination impossible : ' . $destRel);
            }

            mm_backup_file($path, $backupDir, $manifest);
            if ($destRel !== $path) {
                mm_backup_file($destRel, $backupDir, $manifest);
            }

            $temporary = $destAbs . '.mm-tmp-' . bin2hex(random_bytes(3));
            if (!is_file($destAbs) && $destRel !== $path) {
                $manifest['creations'][] = $destRel;
            }
            if (!@copy($importAbs, $temporary)) {
                throw new RuntimeException('Copie du remplaçant impossible : ' . $destRel);
            }
            if (mm_sha256($temporary) !== ($replacement['sha256'] ?? null)) {
                @unlink($temporary);
                throw new RuntimeException('Empreinte du remplaçant incohérente après copie : ' . $destRel);
            }
            if (!@rename($temporary, $destAbs)) {
                @unlink($temporary);
                throw new RuntimeException('Installation du remplaçant impossible : ' . $destRel);
            }

            $updatedReferences = [];
            if ($destRel !== $path) {
                $files = [];
                foreach ((array) ($media['usages'] ?? []) as $usage) {
                    if (!empty($usage['fichier'])) {
                        $files[(string) $usage['fichier']] = true;
                    }
                }
                $files = array_keys($files);
                if (($decision['portee'] ?? 'globale') === 'pages' && !empty($decision['cibles'])) {
                    $files = array_values(array_intersect($files, array_map('strval', (array) $decision['cibles'])));
                }
                $updatedReferences = mm_update_references($path, $destRel, $files, $backupDir, $manifest);
                $changedPhp = array_merge($changedPhp, $updatedReferences);
            }

            $variants = null;
            if (!empty($decision['generer_variantes']) && ($replacement['type'] ?? '') === 'image') {
                $widths = (array) ($state['reglages']['contrat']['image']['largeurs'] ?? [320, 640, 1280, 1920]);
                $stem = pathinfo($destAbs, PATHINFO_FILENAME);
                $variantDir = dirname($destAbs);
                foreach ($widths as $width) {
                    foreach (['webp', 'avif'] as $variantExt) {
                        $variantPath = $variantDir . '/' . $stem . '-' . (int) $width . 'w.' . $variantExt;
                        $variantRel = mm_project_relative($variantPath);
                        if (is_file($variantPath)) {
                            mm_backup_file($variantRel, $backupDir, $manifest);
                        } else {
                            $manifest['creations'][] = $variantRel;
                        }
                    }
                }
                $variants = mm_generate_image_variants($state, $destRel);
                $manifest['variantes'][] = $variants;
                $manifest['avertissements'] = array_merge($manifest['avertissements'], (array) $variants['erreurs']);
            }

            $manifest['remplacements'][] = [
                'ancien'                  => $path,
                'nouveau'                 => $destRel,
                'empreinte'               => $replacement['sha256'] ?? null,
                'octets'                  => (int) ($replacement['octets'] ?? 0),
                'portee'                  => (string) ($decision['portee'] ?? 'globale'),
                'references_mises_a_jour' => $updatedReferences,
                'variantes'               => $variants,
            ];

            // L'ancien fichier ne reste que s'il est encore référencé (portée limitée).
            if ($destRel !== $path) {
                $restantes = mm_usages_for_paths([$path])[$path] ?? [];
                $exactes = array_values(array_filter(
                    $restantes,
                    static fn (array $usage): bool => ($usage['exact'] ?? false) === true
                ));

                if ($exactes === []) {
                    $oldAbsolute = mm_project_abs($path);
                    if (is_file($oldAbsolute)) {
                        $trash = $backupDir . '/corbeille/' . $path;
                        if (!is_dir(dirname($trash)) && !@mkdir(dirname($trash), 0775, true)) {
                            throw new RuntimeException('Corbeille impossible : ' . $path);
                        }
                        if (!@rename($oldAbsolute, $trash)
                            && (!@copy($oldAbsolute, $trash) || !@unlink($oldAbsolute))) {
                            throw new RuntimeException('Mise en corbeille de l’ancien média impossible : ' . $path);
                        }
                        $manifest['corbeille'][] = $path;
                        $manifest['remplacements'][count($manifest['remplacements']) - 1]['ancien_deplace'] = true;
                    }
                } else {
                    $manifest['remplacements'][count($manifest['remplacements']) - 1]['ancien_conserve'] = true;
                    $manifest['avertissements'][] = 'Ancien média conservé car encore référencé : ' . $path;
                }
            }

            $state['decisions'][$path]['applique_le'] = mm_now();
        }


        // 2. Retraits demandés puis suppressions (médias inutilisés uniquement).
        foreach ((array) $state['decisions'] as $path => $decision) {
            if (($decision['decision'] ?? '') !== 'delete') {
                continue;
            }
            if (!isset($inventoryByPath[$path])) {
                continue;
            }

            $exactUsages = static function (array $usages): array {
                return array_values(array_filter(
                    $usages,
                    static fn (array $usage): bool => ($usage['exact'] ?? false) === true
                ));
            };

            $current = mm_usages_for_paths([$path])[$path] ?? [];
            $blockingUsages = $exactUsages($current);

            $retraitAsked = is_array($decision['retrait'] ?? null) && ($decision['retrait']['demande'] ?? false) === true;
            if ($retraitAsked && $blockingUsages !== []) {
                $files = [];
                foreach ($blockingUsages as $usage) {
                    $files[(string) $usage['fichier']] = true;
                }
                $files = array_keys($files);
                if (!empty($decision['retrait']['fichiers'])) {
                    $files = array_values(array_intersect($files, array_map('strval', (array) $decision['retrait']['fichiers'])));
                }

                $retrait = mm_remove_reference_lines($path, $files, $backupDir, $manifest);
                $changedPhp = array_merge($changedPhp, $retrait['fichiers']);
                $manifest['retraits'][] = [
                    'media'    => $path,
                    'fichiers' => $retrait['fichiers'],
                    'refusees' => $retrait['refusees'],
                ];

                $current = mm_usages_for_paths([$path])[$path] ?? [];
                $blockingUsages = $exactUsages($current);
            }

            if ($blockingUsages !== []) {
                $manifest['suppressions'][] = [
                    'media'   => $path,
                    'statut'  => 'conserve',
                    'raison'  => 'Encore référencé : suppression annulée pour cette opération.',
                    'usages'  => $blockingUsages,
                ];
                continue;
            }

            $absolute = mm_project_abs($path);
            $trash = $backupDir . '/corbeille/' . $path;
            if (!is_dir(dirname($trash)) && !@mkdir(dirname($trash), 0775, true)) {
                throw new RuntimeException('Corbeille impossible : ' . $path);
            }
            if (!@rename($absolute, $trash)) {
                if (!@copy($absolute, $trash) || !@unlink($absolute)) {
                    throw new RuntimeException('Mise en corbeille impossible : ' . $path);
                }
            }

            $manifest['corbeille'][] = $path;
            $manifest['suppressions'][] = ['media' => $path, 'statut' => 'corbeille'];
            unset($state['decisions'][$path]);
        }


        // 3. Inventaire à jour (lecture seule) et nettoyage des décisions orphelines.
        $state['inventaire'] = mm_build_inventory(MM_PROJECT_ROOT);
        $existing = [];
        foreach ((array) $state['inventaire'] as $media) {
            $existing[(string) $media['chemin']] = true;
        }
        foreach (array_keys((array) $state['decisions']) as $decidedPath) {
            if (!isset($existing[(string) $decidedPath])) {
                unset($state['decisions'][$decidedPath]);
            }
        }

        // 4. Vérifications : syntaxe PHP puis test HTTP réel du site.
        $lint = mm_lint_paths($changedPhp);
        $lintOk = true;
        foreach ($lint as $result) {
            if ((int) $result['code'] !== 0) {
                $lintOk = false;
                $manifest['erreurs'][] = 'php -l en échec : ' . $result['chemin'] . ' → ' . $result['sortie'];
            }
        }

        $smoke = mm_smoke_test();
        $manifest['tests'] = [
            'lint'       => $lint,
            'lint_ok'    => $lintOk,
            'fumee'      => $smoke,
            'execute_le' => mm_now(),
        ];

        $status = ($lintOk && ($smoke['ok'] ?? false) && $manifest['erreurs'] === [])
            ? 'applique'
            : 'applique_avec_alertes';

        $manifest['statut'] = $status;

        $reportRelative = 'logs/apply-' . $applicationId . '.json';
        @file_put_contents(
            MM_STORAGE_DIR . '/' . $reportRelative,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $entry = [
            'id'               => $applicationId,
            'horodatage'       => mm_now(),
            'statut'           => $status,
            'sauvegarde'       => 'backups/' . $applicationId,
            'rapport'          => $reportRelative,
            'remplacements'    => count($manifest['remplacements']),
            'retraits'         => count($manifest['retraits']),
            'corbeille'        => count($manifest['corbeille']),
            'conserve_utilise' => count(array_filter(
                (array) $manifest['suppressions'],
                static fn (array $line): bool => ($line['statut'] ?? '') === 'conserve'
            )),
            'alertes'          => count($manifest['avertissements']) + count($manifest['erreurs']),
        ];

        $state['application']['derniere'] = $entry;
        $history = (array) ($state['application']['historique'] ?? []);
        array_unshift($history, $entry);
        $state['application']['historique'] = array_slice($history, 0, 20);
        $state['revision'] = (int) ($state['revision'] ?? 0) + 1;
        $state['validation'] = null;
        mm_state_save($state, 'application ' . $applicationId);

        return [
            'application' => $entry,
            'manifest'    => $manifest,
            'rapport'     => $reportRelative,
            'sauvegarde'  => 'backups/' . $applicationId,
        ];
    } catch (Throwable $error) {

        $manifest['erreurs'][] = $error->getMessage();
        $restoration = mm_restore_backup($backupDir, $manifest);
        $manifest['rollback'] = $restoration;
        $manifest['statut'] = $restoration['ok'] ? 'echec_rollback' : 'echec_rollback_partiel';

        $reportRelative = 'logs/apply-' . $applicationId . '-echec.json';
        @file_put_contents(
            MM_STORAGE_DIR . '/' . $reportRelative,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $entry = [
            'id'         => $applicationId,
            'horodatage' => mm_now(),
            'statut'     => $manifest['statut'],
            'sauvegarde' => 'backups/' . $applicationId,
            'rapport'    => $reportRelative,
            'erreur'     => $error->getMessage(),
        ];
        $state['application']['derniere'] = $entry;
        $history = (array) ($state['application']['historique'] ?? []);
        array_unshift($history, $entry);
        $state['application']['historique'] = array_slice($history, 0, 20);
        $state['revision'] = (int) ($state['revision'] ?? 0) + 1;
        $state['validation'] = null;
        mm_state_save($state, 'échec application ' . $applicationId);

        mm_json([
            'ok'       => false,
            'erreur'   => 'Application interrompue : ' . $error->getMessage(),
            'rollback' => $restoration,
            'manifest' => $manifest,
            'rapport'  => $reportRelative,
        ], 500);
    }
}

/**
 * Liste récursive simple (sans exclusions) pour restaurer une sauvegarde.
 *
 * @return list<string> chemins relatifs POSIX
 */
function mm_list_recursive(string $directory, string $prefix = ''): array
{
    $entries = @scandir($directory);
    if ($entries === false) {
        return [];
    }

    $files = [];
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $absolute = $directory . '/' . $entry;
        $relative = $prefix === '' ? $entry : $prefix . '/' . $entry;
        if (is_dir($absolute)) {
            $files = array_merge($files, mm_list_recursive($absolute, $relative));
        } elseif (is_file($absolute)) {
            $files[] = $relative;
        }
    }

    return $files;
}

/**
 * Restaure une sauvegarde : fichiers d'origine, corbeille, fichiers créés.
 *
 * @param array<string, mixed> $manifest
 * @return array{ok:bool, fichiers:list<string>, corbeille:list<string>, supprimes:list<string>, erreurs:list<string>}
 */
function mm_restore_backup(string $backupDir, array $manifest): array
{
    $result = ['ok' => true, 'fichiers' => [], 'corbeille' => [], 'supprimes' => [], 'erreurs' => []];

    $filesRoot = $backupDir . '/files';
    foreach (mm_list_recursive($filesRoot) as $relative) {
        $source = $filesRoot . '/' . $relative;
        $target = MM_PROJECT_ROOT . '/' . $relative;
        if (!is_dir(dirname($target))) {
            @mkdir(dirname($target), 0775, true);
        }
        if (@copy($source, $target)) {
            $result['fichiers'][] = $relative;
        } else {
            $result['ok'] = false;
            $result['erreurs'][] = 'Restauration impossible : ' . $relative;
        }
    }

    $trashRoot = $backupDir . '/corbeille';
    foreach (mm_list_recursive($trashRoot) as $relative) {
        $source = $trashRoot . '/' . $relative;
        $target = MM_PROJECT_ROOT . '/' . $relative;
        if (!is_dir(dirname($target))) {
            @mkdir(dirname($target), 0775, true);
        }
        if (@rename($source, $target) || (@copy($source, $target) && @unlink($source))) {
            $result['corbeille'][] = $relative;
        } else {
            $result['ok'] = false;
            $result['erreurs'][] = 'Retour de corbeille impossible : ' . $relative;
        }
    }

    foreach ((array) ($manifest['creations'] ?? []) as $relative) {
        $target = MM_PROJECT_ROOT . '/' . (string) $relative;
        if (is_file($target) && @unlink($target)) {
            $result['supprimes'][] = (string) $relative;
        }
    }

    return $result;
}


/**
 * Restaure explicitement une application précédente depuis sa sauvegarde.
 * Aucune restauration automatique : action demandée par l'utilisateur.
 *
 * @param array<string, mixed> $state
 * @return array<string, mixed>
 */
function mm_rollback_application(array $state, string $applicationId): array
{
    if (preg_match('/^\d{8}-\d{6}-[0-9a-f]{6}$/', $applicationId) !== 1) {
        mm_fail('Identifiant d’application invalide.', 422);
    }

    $backupDir = MM_BACKUPS_DIR . '/' . $applicationId;
    if (!is_dir($backupDir)) {
        mm_fail('Sauvegarde introuvable pour cette application.', 404);
    }

    $manifest = [];
    foreach (['logs/apply-' . $applicationId . '.json', 'logs/apply-' . $applicationId . '-echec.json'] as $candidate) {
        $path = MM_STORAGE_DIR . '/' . $candidate;
        if (is_file($path)) {
            $decoded = json_decode((string) @file_get_contents($path), true);
            if (is_array($decoded)) {
                $manifest = $decoded;
            }
            break;
        }
    }

    $restoration = mm_restore_backup($backupDir, $manifest);
    $state['inventaire'] = mm_build_inventory(MM_PROJECT_ROOT);

    foreach ((array) $state['decisions'] as $path => $decision) {
        if (isset($decision['applique_le'])) {
            unset($state['decisions'][$path]['applique_le']);
        }
    }

    $entry = [
        'id'           => $applicationId,
        'horodatage'   => mm_now(),
        'statut'       => $restoration['ok'] ? 'rollback' : 'rollback_partiel',
        'sauvegarde'   => 'backups/' . $applicationId,
        'restauration' => $restoration,
    ];

    $state['application']['derniere'] = $entry;
    $history = (array) ($state['application']['historique'] ?? []);
    array_unshift($history, $entry);
    $state['application']['historique'] = array_slice($history, 0, 20);
    $state['revision'] = (int) ($state['revision'] ?? 0) + 1;
    $state['validation'] = null;
    mm_state_save($state, 'rollback ' . $applicationId);

    return [
        'restauration' => $restoration,
        'application'  => $entry,
        'inventaire'   => count((array) $state['inventaire']),
    ];
}

