<?php

declare(strict_types=1);

/**
 * Inventaire des médias : parcours du projet, empreintes, dimensions,
 * regroupement des variantes et détection des usages (statiques et partiels).
 * Aucun fichier n'est modifié par ce module.
 */

/**
 * Parcourt récursivement la racine du projet sans suivre les liens symboliques.
 *
 * @return list<string> chemins relatifs POSIX, triés
 */
function mm_walk_files(string $root): array
{
    $files = [];
    $stack = [''];
    $excluded = array_map('strtolower', MM_EXCLUDED_DIRS);

    while ($stack !== []) {
        $relativeDir = array_pop($stack);
        $absoluteDir = $relativeDir === '' ? $root : $root . '/' . $relativeDir;
        $entries = @scandir($absoluteDir);

        if ($entries === false) {
            continue;
        }

        foreach ($entries as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }

            $childRelative = $relativeDir === '' ? $name : $relativeDir . '/' . $name;
            $childAbsolute = $root . '/' . $childRelative;

            if (is_link($childAbsolute)) {
                continue;
            }

            if (is_dir($childAbsolute)) {
                if (in_array(strtolower($name), $excluded, true)) {
                    continue;
                }
                if (str_starts_with($name, '.') && $name !== '.well-known') {
                    continue;
                }
                $stack[] = $childRelative;
                continue;
            }

            if (is_file($childAbsolute)) {
                $files[] = $childRelative;
            }
        }
    }

    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    return $files;
}

/**
 * Sonde un média via ffprobe (dimensions, durée, codec).
 * Repli : lecture d'en-tête pour JPEG/PNG/WebP/GIF, attributs pour SVG.
 *
 * @return array{dimensions:array{largeur:int|null, hauteur:int|null}, duree:float|null, codec:string|null, source:string}
 */
function mm_probe_media(string $absolutePath): array
{
    $result = [
        'dimensions' => ['largeur' => null, 'hauteur' => null],
        'duree'      => null,
        'codec'      => null,
        'source'     => 'indisponible',
    ];

    $probe = @mm_run([
        'ffprobe', '-v', 'error', '-show_streams', '-show_format',
        '-of', 'json', $absolutePath,
    ]);

    if ($probe['code'] === 0 && trim($probe['sortie']) !== '') {
        $data = json_decode($probe['sortie'], true);
        if (is_array($data)) {
            $stream = null;
            foreach ((array) ($data['streams'] ?? []) as $candidate) {
                if (is_array($candidate) && ($candidate['codec_type'] ?? '') === 'video') {
                    $stream = $candidate;
                    break;
                }
            }
            if (is_array($stream)) {
                $result['dimensions']['largeur'] = isset($stream['width']) ? (int) $stream['width'] : null;
                $result['dimensions']['hauteur'] = isset($stream['height']) ? (int) $stream['height'] : null;
                $result['codec'] = isset($stream['codec_name']) ? (string) $stream['codec_name'] : null;
                $duration = $data['format']['duration'] ?? ($stream['duration'] ?? null);
                $result['duree'] = is_numeric($duration) ? round((float) $duration, 3) : null;
                $result['source'] = 'ffprobe';
            }
        }
    }

    $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

    if ($result['source'] === 'indisponible') {
        $size = mm_image_size($absolutePath, $ext);
        if ($size !== null) {
            $result['dimensions'] = $size;
            $result['source'] = 'entete';
        }
    }

    return $result;
}

/**
 * Lecture directe des dimensions (aucune extension PHP requise).
 *
 * @return array{largeur:int, hauteur:int}|null
 */
function mm_image_size(string $absolutePath, string $ext): ?array
{
    $handle = @fopen($absolutePath, 'rb');
    if ($handle === false) {
        return null;
    }

    $head = (string) fread($handle, 65536);
    fclose($handle);

    if (str_ends_with($ext, 'svg')) {
        if (preg_match('/<svg[^>]*\bwidth=["\']([0-9.]+)/i', $head, $w) === 1
            && preg_match('/<svg[^>]*\bheight=["\']([0-9.]+)/i', $head, $h) === 1) {
            $width = (int) round((float) $w[1]);
            $height = (int) round((float) $h[1]);
            if ($width > 0 && $height > 0) {
                return ['largeur' => $width, 'hauteur' => $height];
            }
        }
        if (preg_match('/viewBox=["\']\s*[-0-9.]+\s+[-0-9.]+\s+([0-9.]+)\s+([0-9.]+)/i', $head, $vb) === 1) {
            return ['largeur' => (int) round((float) $vb[1]), 'hauteur' => (int) round((float) $vb[2])];
        }

        return null;
    }

    if (str_starts_with($head, "\xFF\xD8")) {
        $offset = 2;
        $length = strlen($head);
        while ($offset + 9 < $length) {
            if (ord($head[$offset]) !== 0xFF) {
                $offset++;
                continue;
            }
            $marker = ord($head[$offset + 1]);
            $segmentLength = unpack('n', substr($head, $offset + 2, 2))[1] ?? 0;
            if (in_array($marker, [0xC0, 0xC1, 0xC2, 0xC3, 0xC5, 0xC6, 0xC7, 0xC9, 0xCA, 0xCB, 0xCD, 0xCE, 0xCF], true)) {
                $height = unpack('n', substr($head, $offset + 5, 2))[1] ?? 0;
                $width = unpack('n', substr($head, $offset + 7, 2))[1] ?? 0;
                if ($width > 0 && $height > 0) {
                    return ['largeur' => (int) $width, 'hauteur' => (int) $height];
                }

                return null;
            }
            if ($segmentLength < 2) {
                return null;
            }
            $offset += 2 + $segmentLength;
        }

        return null;
    }

    if (str_starts_with($head, "\x89PNG\r\n\x1A\n") && strlen($head) >= 24) {
        $width = unpack('N', substr($head, 16, 4))[1] ?? 0;
        $height = unpack('N', substr($head, 20, 4))[1] ?? 0;

        return ($width > 0 && $height > 0) ? ['largeur' => (int) $width, 'hauteur' => (int) $height] : null;
    }

    if (str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WEBP' && strlen($head) >= 30) {
        $chunk = substr($head, 12, 4);
        if ($chunk === 'VP8X') {
            $width = 1 + (unpack('V', substr($head, 24, 3) . "\0")[1] ?? 0);
            $height = 1 + (unpack('V', substr($head, 27, 3) . "\0")[1] ?? 0);

            return ['largeur' => (int) $width, 'hauteur' => (int) $height];
        }
        if ($chunk === 'VP8 ') {
            $width = unpack('v', substr($head, 26, 2))[1] ?? 0;
            $height = unpack('v', substr($head, 28, 2))[1] ?? 0;

            return ($width > 0 && $height > 0) ? ['largeur' => (int) $width, 'hauteur' => (int) $height] : null;
        }
        if ($chunk === 'VP8L' && strlen($head) >= 25) {
            $bits = unpack('V', substr($head, 21, 4))[1] ?? 0;
            $width = ($bits & 0x3FFF) + 1;
            $height = (($bits >> 14) & 0x3FFF) + 1;

            return ['largeur' => (int) $width, 'hauteur' => (int) $height];
        }
    }

    if (str_starts_with($head, 'GIF8') && strlen($head) >= 10) {
        $width = unpack('v', substr($head, 6, 2))[1] ?? 0;
        $height = unpack('v', substr($head, 8, 2))[1] ?? 0;

        return ($width > 0 && $height > 0) ? ['largeur' => (int) $width, 'hauteur' => (int) $height] : null;
    }

    return null;
}

/** Radical de variante : retire les suffixes de taille, poster, variante… */
function mm_normalize_stem(string $relativePath): string
{
    $directory = strtolower(str_replace('\\', '/', dirname($relativePath)));
    $directory = $directory === '.' ? '' : $directory . '/';
    $base = strtolower(pathinfo($relativePath, PATHINFO_FILENAME));

    $pattern = '/[-_.@](poster|thumb|thumbnail|preview|min|mob|mobile|desktop|tablet|large|small|xs|sm|md|lg|xl|2x|3x|[0-9]{2,4}w|[0-9]{1,2}x[0-9]{1,2}|[0-9]{2,5})$/i';
    do {
        $before = $base;
        $base = (string) preg_replace($pattern, '', $base);
    } while ($base !== $before && $base !== '');

    return $directory . $base;
}


/** Catégorie lisible, déduite du type et du nom. */
function mm_categorize(string $relative, string $kind): string
{
    $lower = strtolower(str_replace('\\', '/', $relative));

    if ($kind === 'video') {
        return 'vidéo';
    }
    if ($kind === 'audio') {
        return 'audio';
    }
    if (str_ends_with($lower, '.svg')) {
        return (str_contains($lower, 'icon') || str_contains($lower, '/icons/')) ? 'icône' : 'vectoriel';
    }
    if (str_ends_with($lower, '.gif')) {
        return 'animation';
    }
    if (str_contains($lower, 'hero') || str_contains($lower, 'banner')) {
        return 'hero';
    }
    if (preg_match('/(logo|brand|favicon|apple-touch|wordmark)/', $lower) === 1) {
        return 'logo';
    }

    return 'image';
}

function mm_media_id(string $relative): string
{
    return 'm_' . substr(md5($relative), 0, 12);
}

/**
 * Index texte du projet pour la recherche d'usages.
 *
 * @param list<string> $files
 * @return array<string, list<string>>
 */
function mm_text_index(array $files): array
{
    $index = [];

    foreach ($files as $relative) {
        $ext = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        if (!in_array($ext, MM_TEXT_EXT, true) && !str_ends_with($relative, '.htaccess')) {
            continue;
        }

        $absolute = MM_PROJECT_ROOT . '/' . $relative;
        $size = @filesize($absolute);
        if ($size === false || $size > 2097152) {
            continue;
        }

        $content = @file_get_contents($absolute);
        if (!is_string($content)) {
            continue;
        }

        $index[$relative] = preg_split("/\r\n|\n|\r/", $content) ?: [];
    }

    return $index;
}

/**
 * @param array<string, list<string>> $textIndex
 * @param list<string> $siblingPaths autres variantes du même groupe
 * @return list<array{fichier:string, ligne:int, extrait:string, exact:bool}>
 */
function mm_find_usages(string $relative, array $textIndex, array $siblingPaths = []): array
{
    $base = basename($relative);
    $stem = pathinfo($base, PATHINFO_FILENAME);
    $siblings = [];
    foreach ($siblingPaths as $sibling) {
        $siblings[] = basename($sibling);
    }
    $usages = [];
    $seen = [];

    foreach ($textIndex as $file => $lines) {
        foreach ($lines as $number => $line) {
            $exact = str_contains($line, $relative) || str_contains($line, $base);
            $dynamic = preg_match('/(asset\(|url\(|src\s*=|srcset\s*=|poster\s*=|href\s*=|import\s|require(_once)?\s*\(|include\s|content\(|glob\(|readfile\()/i', $line) === 1;
            $otherVariant = false;
            foreach ($siblings as $sibling) {
                if (str_contains($line, $sibling)) {
                    $otherVariant = true;
                    break;
                }
            }
            $partial = !$exact && !$otherVariant && $dynamic && mm_strlen($stem) >= 4 && str_contains($line, $stem);

            if (!$exact && !$partial) {
                continue;
            }

            $key = $file . ':' . $number;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $usages[] = [
                'fichier' => $file,
                'ligne'   => $number + 1,
                'extrait' => mm_substr(trim($line), 0, 200),
                'exact'   => $exact,
            ];
        }
    }

    return $usages;
}


/**
 * Construit l'inventaire complet. Lecture seule, aucun déplacement.
 *
 * @return list<array<string, mixed>>
 */
function mm_build_inventory(string $root): array
{
    $allFiles = mm_walk_files($root);
    $mediaPaths = [];
    $textFiles = [];

    foreach ($allFiles as $relative) {
        if (mm_is_media_path($relative)) {
            $mediaPaths[] = $relative;
        } else {
            $textFiles[] = $relative;
        }
    }

    $textIndex = mm_text_index($textFiles);
    $items = [];
    $shaMap = [];

    foreach ($mediaPaths as $relative) {
        $absolute = $root . '/' . $relative;
        $ext = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        $kind = mm_kind_for_ext($ext) ?? 'image';
        $stat = @stat($absolute);
        $sha = mm_sha256($absolute);
        $probe = mm_probe_media($absolute);

        $item = [
            'id'          => mm_media_id($relative),
            'chemin'      => $relative,
            'nom'         => basename($relative),
            'type'        => $kind,
            'format'      => $ext,
            'octets'      => $stat !== false ? (int) $stat['size'] : 0,
            'sha256'      => $sha,
            'modifie_le'  => $stat !== false ? date('c', (int) $stat['mtime']) : null,
            'dimensions'  => $probe['dimensions'],
            'duree'       => $kind === 'video' ? $probe['duree'] : null,
            'codec'       => $kind === 'video' ? $probe['codec'] : null,
            'probe'       => $probe['source'],
            'categorie'   => mm_categorize($relative, $kind),
            'variantes'   => [],
            'doublon_de'  => null,
            'doublons'    => [],
            'usages'      => [],
            'usages_total' => 0,
            'statut'      => 'INUTILISÉ',
        ];

        if ($sha !== null) {
            if (isset($shaMap[$sha])) {
                $item['doublon_de'] = $shaMap[$sha];
            } else {
                $shaMap[$sha] = $relative;
            }
        }

        $items[] = $item;
    }

    // Regroupement des variantes par radical normalisé.
    $groups = [];
    foreach ($items as $item) {
        $groups[mm_normalize_stem($item['chemin'])][] = $item['chemin'];
    }

    foreach ($items as $index => $item) {
        $key = mm_normalize_stem($item['chemin']);
        $variants = array_values(array_diff($groups[$key] ?? [], [$item['chemin']]));
        $items[$index]['variantes'] = $variants;

        $duplicates = [];
        if ($item['sha256'] !== null) {
            foreach ($items as $otherIndex => $other) {
                if ($otherIndex !== $index && $other['sha256'] === $item['sha256']) {
                    $duplicates[] = $other['chemin'];
                }
            }
        }
        $items[$index]['doublons'] = $duplicates;
    }

    // Usages et statut.
    foreach ($items as $index => $item) {
        $usages = mm_find_usages($item['chemin'], $textIndex, $item['variantes']);
        $exact = false;
        $partial = false;

        foreach ($usages as $usage) {
            if ($usage['exact']) {
                $exact = true;
            } else {
                $partial = true;
            }
        }

        if ($exact) {
            $status = 'UTILISÉ';
        } elseif ($partial) {
            $status = 'INCERTAIN';
        } elseif ($item['doublon_de'] !== null) {
            $status = 'DOUBLON';
        } else {
            $status = 'INUTILISÉ';
        }

        $items[$index]['usages'] = array_slice($usages, 0, 40);
        $items[$index]['usages_total'] = count($usages);
        $items[$index]['statut'] = $status;
    }

    // Une variante d'un média utilisé n'est jamais déclarée inutilisée.
    $statusByPath = [];
    foreach ($items as $item) {
        $statusByPath[$item['chemin']] = $item['statut'];
    }
    foreach ($items as $index => $item) {
        if ($item['statut'] !== 'INUTILISÉ') {
            continue;
        }
        foreach ($item['variantes'] as $variant) {
            $variantStatus = $statusByPath[$variant] ?? 'INUTILISÉ';
            if ($variantStatus === 'UTILISÉ' || $variantStatus === 'INCERTAIN') {
                $items[$index]['statut'] = 'INCERTAIN';
                break;
            }
        }
    }

    usort($items, static function (array $a, array $b): int {
        $order = ['hero' => 0, 'vidéo' => 1, 'image' => 2, 'logo' => 3, 'icône' => 4, 'vectoriel' => 5, 'animation' => 6, 'audio' => 7];

        return [$order[$a['categorie']] ?? 9, $a['chemin']] <=> [$order[$b['categorie']] ?? 9, $b['chemin']];
    });

    return $items;
}

/**
 * Recalcule les usages de fichiers donnés (après application).
 *
 * @param list<string> $relativePaths
 * @return array<string, list<array{fichier:string, ligne:int, extrait:string, exact:bool}>>
 */
function mm_usages_for_paths(array $relativePaths): array
{
    $textFiles = [];
    foreach (mm_walk_files(MM_PROJECT_ROOT) as $relative) {
        if (!mm_is_media_path($relative)) {
            $textFiles[] = $relative;
        }
    }

    $textIndex = mm_text_index($textFiles);
    $result = [];

    foreach ($relativePaths as $relative) {
        $result[$relative] = mm_find_usages($relative, $textIndex);
    }

    return $result;
}

