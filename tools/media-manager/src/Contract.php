<?php

declare(strict_types=1);

/**
 * Contrat média (§7 bis) : vérifications textuelles, sans capture d'écran.
 * Les règles bloquantes interdisent l'application ; les avertissements sont
 * consignés et peuvent être dérogés explicitement par l'utilisateur.
 */

/**
 * Contenu des fichiers qui référencent réellement un média (usages exacts).
 *
 * @param array<string, mixed> $media
 */
function mm_contract_context(array $media): string
{
    $files = [];
    foreach ((array) ($media['usages'] ?? []) as $usage) {
        if (($usage['exact'] ?? false) === true && is_string($usage['fichier'] ?? null)) {
            $files[$usage['fichier']] = true;
        }
    }

    $context = '';
    foreach (array_keys($files) as $file) {
        $absolute = MM_PROJECT_ROOT . '/' . $file;
        if (!is_file($absolute)) {
            continue;
        }
        $size = @filesize($absolute);
        if ($size === false || $size > 1048576) {
            continue;
        }
        $context .= "\n" . mm_contract_strip_php((string) @file_get_contents($absolute));
    }

    return $context;
}

/**
 * Neutralise les blocs PHP pour que les balises HTML restent analysables :
 * `poster="<?= e(asset('…')) ?>"` contient un `?>` qui couperait la balise.
 */
function mm_contract_strip_php(string $source): string
{
    $source = (string) preg_replace('/<\?=.*?\?>/s', 'PHPVAL', $source);
    $source = (string) preg_replace('/<\?php.*?\?>/s', '', $source);
    $source = (string) preg_replace('/<\?(?!xml).*?\?>/s', '', $source);

    return $source;
}

/**
 * @param array<string, mixed> $media
 * @return list<string>
 */
function mm_contract_video_formats(array $media): array
{
    $formats = [(string) $media['format']];
    foreach ((array) ($media['variantes'] ?? []) as $variant) {
        $formats[] = strtolower(pathinfo((string) $variant, PATHINFO_EXTENSION));
    }

    $formats = array_values(array_unique(array_filter($formats, static function (string $format): bool {
        return in_array($format, ['mp4', 'webm', 'mov', 'm4v', 'ogv', 'mkv'], true);
    })));

    return $formats;
}

/**
 * @param array<string, mixed> $media
 */
function mm_contract_has_poster(array $media): bool
{
    foreach ((array) ($media['variantes'] ?? []) as $variant) {
        $ext = strtolower(pathinfo((string) $variant, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'avif'], true)
            && stripos((string) $variant, 'poster') !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Variantes de format présentes pour une image (même radical).
 *
 * @param array<string, mixed> $media
 * @return array<string, string> format => chemin
 */
function mm_contract_image_variants(array $media): array
{
    $found = [(string) $media['format'] => (string) $media['chemin']];
    foreach ((array) ($media['variantes'] ?? []) as $variant) {
        $ext = strtolower(pathinfo((string) $variant, PATHINFO_EXTENSION));
        if (in_array($ext, ['avif', 'webp', 'jpg', 'jpeg'], true)) {
            $found[$ext === 'jpeg' ? 'jpeg' : $ext] = (string) $variant;
        }
    }

    return $found;
}

/**
 * Construit le rapport de contrat d'un média (ou de son remplaçant).
 *
 * @param array<string, mixed> $state
 * @param array<string, mixed> $media
 * @param array<string, mixed>|null $replacement métadonnées du fichier de remplacement
 * @return array<string, mixed>
 */
function mm_contract_media(array $state, array $media, ?array $replacement = null): array
{
    $settings = $state['reglages']['contrat'] ?? [];
    $type = (string) ($replacement['type'] ?? $media['type'] ?? 'image');
    $context = mm_contract_context($media);
    $rules = [];

    $add = static function (string $id, string $label, string $status, string $detail, string $fix = '') use (&$rules): void {
        $rules[] = [
            'id'        => $id,
            'libelle'   => $label,
            'statut'    => $status,
            'detail'    => $detail,
            'correctif' => $fix,
        ];
    };

    $videoTag = null;
    if (preg_match('/<video\b[^>]*>/i', $context, $match) === 1) {
        $videoTag = $match[0];
    }

    if ($type === 'video') {
        $config = (array) ($settings['video'] ?? []);
        if ($replacement !== null) {
            $oldFormat = strtolower(pathinfo((string) $media['chemin'], PATHINFO_EXTENSION));
            $formats = array_values(array_diff(mm_contract_video_formats($media), [$oldFormat]));
            $formats[] = strtolower((string) ($replacement['format'] ?? ''));
            $formats = array_values(array_unique(array_filter($formats)));
        } else {
            $formats = mm_contract_video_formats($media);
        }
        $expected = (array) ($config['formats'] ?? ['mp4', 'webm']);
        $missing = array_values(array_diff($expected, $formats));
        $add(
            'video_formats',
            'Formats MP4 + WebM',
            $missing === [] ? 'ok' : 'bloque',
            $missing === []
                ? 'Formats présents : ' . implode(', ', $formats)
                : 'Format(s) manquant(s) : ' . implode(', ', $missing),
            $missing === [] ? '' : 'Fournir la variante manquante (génération explicite depuis la source autorisée).'
        );

        $hasPoster = $replacement !== null
            ? (bool) ($media['contrat_poster_present'] ?? mm_contract_has_poster($media))
            : mm_contract_has_poster($media);
        $posterAttr = preg_match('/poster\s*=\s*["\'][^"\']+/i', $context) === 1;
        $add(
            'video_poster',
            'Poster local',
            ($hasPoster && $posterAttr) ? 'ok' : 'bloque',
            sprintf(
                'Variante poster : %s ; attribut poster dans le markup : %s',
                $hasPoster ? 'présente' : 'absente',
                $posterAttr ? 'présent' : 'absent'
            ),
            'Produire un poster JPG local et le déclarer via l’attribut poster.'
        );

        $missingAttributes = [];
        foreach (['muted', 'playsinline', 'loop'] as $attribute) {
            if ($videoTag === null || stripos($videoTag, $attribute) === false) {
                $missingAttributes[] = $attribute;
            }
        }
        $add(
            'video_attributs',
            'muted / playsinline / loop',
            $missingAttributes === [] ? 'ok' : 'bloque',
            $missingAttributes === []
                ? 'Attributs présents sur la balise <video>.'
                : 'Attribut(s) absent(s) : ' . implode(', ', $missingAttributes)
                    . ($videoTag === null ? ' (aucune balise <video> trouvée dans les usages exacts)' : ''),
            'Ajouter les attributs manquants sur la balise <video>.'
        );

        $dimensions = $videoTag !== null
            && preg_match('/\bwidth\s*=\s*["\']?\d+/i', $videoTag) === 1
            && preg_match('/\bheight\s*=\s*["\']?\d+/i', $videoTag) === 1;
        $add(
            'video_dimensions',
            'Dimensions explicites',
            $dimensions ? 'ok' : 'bloque',
            $dimensions ? 'width et height présents sur <video>.' : 'width/height absents de la balise <video> (risque de CLS).',
            'Ajouter width et height (dimensions réelles du média) sur <video>.'
        );

        $autoplay = $videoTag !== null && stripos($videoTag, 'autoplay') !== false;
        $autoplayRule = (string) ($config['autoplay'] ?? 'avertissement');
        $add(
            'video_autoplay',
            'Autoplay',
            !$autoplay ? 'ok' : ($autoplayRule === 'bloque' ? 'bloque' : 'avertissement'),
            $autoplay
                ? 'autoplay présent : écart avec le contrat §7 bis, traité en avertissement (vidéo hero exigée par les règles du projet, lecture contrôlable, mouvement réduit respecté).'
                : 'Aucun autoplay dans le markup.',
            $autoplay ? 'Décision propriétaire : conserver la lecture automatique silencieuse ou passer en lecture manuelle.' : ''
        );

        $weight = $replacement !== null
            ? (int) ($replacement['octets'] ?? 0)
            : (int) ($media['octets'] ?? 0);
        $desktopMax = (int) ($config['poids_max_desktop'] ?? 2621440);
        $mobileMax = (int) ($config['poids_max_mobile'] ?? 1258291);
        $add(
            'video_poids',
            'Poids ≤ 2,5 Mo desktop / ≤ 1,2 Mo mobile',
            $weight <= $desktopMax ? 'ok' : 'bloque',
            sprintf(
                'Fichier évalué : %s o (budget desktop %s o, budget mobile %s o).',
                number_format($weight, 0, ',', ' '),
                number_format($desktopMax, 0, ',', ' '),
                number_format($mobileMax, 0, ',', ' ')
            ) . ($weight > $mobileMax ? ' Au-dessus du budget mobile.' : ''),
            $weight <= $desktopMax ? '' : 'Ré-encoder le média ou fournir une variante mobile plus légère.'
        );
    }

    elseif ($type === 'image') {
        $config = (array) ($settings['image'] ?? []);
        if ($replacement !== null) {
            $newFormat = strtolower((string) ($replacement['format'] ?? ''));
            $add(
                'image_variantes',
                'Variantes AVIF puis WebP avec repli JPEG',
                in_array($newFormat, ['jpg', 'jpeg', 'png', 'webp', 'avif'], true) ? 'avertissement' : 'bloque',
                in_array($newFormat, ['jpg', 'jpeg', 'png', 'webp', 'avif'], true)
                    ? 'Nouveau fichier ' . strtoupper($newFormat) . ' : les variantes AVIF/WebP existantes correspondent à l’ancien contenu et doivent être régénérées.'
                    : 'Format de remplacement non compatible avec le contrat image.',
                'Cocher « générer les variantes » lors du remplacement, ou régénérer après validation.'
            );
        } else {
            $variants = mm_contract_image_variants($media);
            $missing = [];
            foreach (['avif', 'webp'] as $expected) {
                if (!isset($variants[$expected])) {
                    $missing[] = strtoupper($expected);
                }
            }
            $hasJpeg = isset($variants['jpeg']) || isset($variants['jpg']);
            $add(
                'image_variantes',
                'Variantes AVIF puis WebP avec repli JPEG',
                ($missing === [] && $hasJpeg) ? 'ok' : 'bloque',
                $missing === [] && $hasJpeg
                    ? 'Variantes AVIF, WebP et repli JPEG présentes.'
                    : 'Manquant : ' . implode(', ', array_merge($missing, $hasJpeg ? [] : ['JPEG'])),
                'Générer les variantes AVIF/WebP (action explicite, source conservée).'
            );
        }

        $basename = preg_quote(basename((string) $media['chemin']), '/');
        $isBackground = preg_match('/url\([^)]*' . $basename . '/i', $context) === 1;
        $isPoster = preg_match('/poster\s*=\s*["\'][^"\']*' . $basename . '/i', $context) === 1;
        $hasPicture = stripos($context, '<picture') !== false;
        $hasSrcset = preg_match('/srcset\s*=/i', $context) === 1;
        $hasImgWidthHeight = preg_match('/<img\b[^>]*\bwidth\s*=/i', $context) === 1
            && preg_match('/<img\b[^>]*\bheight\s*=/i', $context) === 1;
        $hasAlt = preg_match('/<img\b[^>]*\balt\s*=/i', $context) === 1;
        $nonApplicable = $isBackground || $isPoster;

        $add(
            'image_picture',
            '<picture> AVIF → WebP → JPEG',
            $hasPicture ? 'ok' : ($nonApplicable ? 'avertissement' : 'bloque'),
            $hasPicture
                ? '<picture> détecté dans les fichiers d’usage.'
                : ($isBackground
                    ? 'Média utilisé en arrière-plan CSS : <picture> non applicable sans refonte ; dérogation utilisateur tracée requise.'
                    : ($isPoster
                        ? 'Média utilisé comme poster de vidéo : <picture> non applicable ; dérogation utilisateur tracée requise.'
                        : 'Aucun <picture> dans les usages.')),
            $hasPicture ? '' : 'Utiliser <picture> avec sources AVIF/WebP et repli <img>.'
        );

        $widthsOk = false;
        foreach ((array) ($config['largeurs'] ?? [320, 640, 1280, 1920]) as $width) {
            if ($hasSrcset && preg_match('/' . (int) $width . 'w/i', $context) === 1) {
                $widthsOk = true;
            }
        }
        $add(
            'image_largeurs',
            'Largeurs 320/640/1280/1920',
            $widthsOk ? 'ok' : ($nonApplicable ? 'avertissement' : 'bloque'),
            $widthsOk ? 'srcset avec largeurs cibles détecté.' : 'Aucun srcset avec les largeurs 320/640/1280/1920.',
            $hasSrcset ? '' : 'Fournir un srcset avec les largeurs cibles.'
        );

        $add(
            'image_dimensions',
            'width / height explicites',
            $hasImgWidthHeight ? 'ok' : 'avertissement',
            $hasImgWidthHeight ? 'width et height présents sur <img>.' : 'Pas de <img> avec dimensions : média décoratif (background/poster) ou balise non renseignée.',
            'Ajouter width et height sur l’image affichée.'
        );

        $add(
            'image_alt',
            'Texte alternatif',
            $hasAlt ? 'ok' : 'avertissement',
            $hasAlt ? 'alt présent sur <img>.' : 'Aucun alt sur <img> : média décoratif ou balise non renseignée.',
            'Renseigner un alt utile, ou masquer l’image si purement décorative.'
        );
    } elseif ($type === 'audio') {
        $add(
            'audio_controles',
            'Contrôles accessibles',
            preg_match('/<audio\b[^>]*controls/i', $context) === 1 ? 'ok' : 'avertissement',
            'Un lecteur audio doit rester contrôlable et sans lecture automatique imposée.',
            'Ajouter l’attribut controls et éviter autoplay.'
        );
    } else {
        $add('type_inconnu', 'Type de média', 'avertissement', 'Type non couvert par le contrat.', 'Vérifier manuellement.');
    }

    $blocking = 0;
    $warnings = 0;
    foreach ($rules as $rule) {
        if ($rule['statut'] === 'bloque') {
            $blocking++;
        } elseif ($rule['statut'] === 'avertissement') {
            $warnings++;
        }
    }

    return [
        'type'           => $type,
        'statut'         => $blocking > 0 ? 'bloque' : ($warnings > 0 ? 'avertissement' : 'conforme'),
        'bloquant'       => $blocking,
        'avertissements' => $warnings,
        'regles'         => $rules,
        'evalue_le'      => mm_now(),
    ];
}

/**
 * Applique le contrat à tout l'inventaire.
 *
 * @param array<string, mixed> $state
 * @return array<string, array<string, mixed>> chemin => rapport
 */
function mm_contract_inventory(array $state): array
{
    $reports = [];
    foreach ((array) ($state['inventaire'] ?? []) as $media) {
        $reports[(string) $media['chemin']] = mm_contract_media($state, $media);
    }

    return $reports;
}

