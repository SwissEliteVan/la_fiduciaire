<?php

declare(strict_types=1);

/**
 * Réception des fichiers choisis par l'utilisateur via le sélecteur local.
 * Les fichiers sont stockés dans un dossier temporaire contrôlé
 * (`storage/media-manager/imports/…`) et jamais copiés dans le projet avant
 * la validation et l'application explicite.
 */

/** Signature binaire réelle du fichier (sans extension PHP fileinfo). */
function mm_sniff_kind(string $absolutePath): ?string
{
    $handle = @fopen($absolutePath, 'rb');
    if ($handle === false) {
        return null;
    }

    $head = (string) fread($handle, 64);
    fclose($handle);

    if (str_starts_with($head, "\xFF\xD8\xFF")) {
        return 'image';
    }
    if (str_starts_with($head, "\x89PNG\r\n\x1A\n")) {
        return 'image';
    }
    if (str_starts_with($head, 'GIF87a') || str_starts_with($head, 'GIF89a')) {
        return 'image';
    }
    if (str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WEBP') {
        return 'image';
    }
    if (substr($head, 4, 4) === 'ftyp') {
        $brand = substr($head, 8, 4);
        if (in_array($brand, ['avif', 'avis', 'heic', 'heix', 'mif1'], true)) {
            return 'image';
        }

        return 'video';
    }
    if (str_starts_with($head, "\x1A\x45\xDF\xA3")) {
        return 'video';
    }
    if (str_starts_with($head, 'OggS')) {
        return 'audio';
    }
    if (str_starts_with($head, 'fLaC')) {
        return 'audio';
    }
    if (str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WAVE') {
        return 'audio';
    }
    if (str_starts_with($head, 'ID3') || str_starts_with($head, "\xFF\xFB") || str_starts_with($head, "\xFF\xF3")) {
        return 'audio';
    }
    if (preg_match('/^\s*(<\?xml[^>]*>\s*)?<svg[\s>]/i', $head) === 1) {
        return 'image';
    }

    return null;
}

function mm_safe_filename(string $name): string
{
    $name = basename(str_replace('\\', '/', $name));
    $name = (string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $name);
    $name = trim($name, '-.');

    if ($name === '') {
        $name = 'media';
    }

    return $name;
}

/** Chemin absolu d'un import, strictement confiné au dossier imports. */
function mm_import_abs(string $relativeTemp): string
{
    $relativeTemp = str_replace('\\', '/', trim($relativeTemp));

    if (!str_starts_with($relativeTemp, 'imports/') || str_contains($relativeTemp, '..')) {
        mm_fail('Référence d’import invalide.', 422);
    }

    $absolute = MM_STORAGE_DIR . '/' . $relativeTemp;
    $realImports = realpath(MM_IMPORTS_DIR);
    $realFile = realpath($absolute);

    if ($realImports === false || $realFile === false
        || !str_starts_with(str_replace('\\', '/', $realFile), str_replace('\\', '/', $realImports))) {
        mm_fail('Fichier importé introuvable ou hors du dossier temporaire.', 422);
    }

    return $realFile;
}

/**
 * Enregistre un fichier téléversé dans le dossier temporaire contrôlé.
 *
 * @param array<string, mixed> $file entrée de $_FILES
 * @return array<string, mixed> métadonnées du remplaçant
 */
function mm_store_upload(array $file, string $mediaType): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'Fichier trop volumineux pour la configuration PHP du serveur local.',
            UPLOAD_ERR_FORM_SIZE  => 'Fichier trop volumineux.',
            UPLOAD_ERR_PARTIAL    => 'Téléversement interrompu.',
            UPLOAD_ERR_NO_FILE    => 'Aucun fichier reçu.',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire PHP indisponible.',
            UPLOAD_ERR_CANT_WRITE => 'Écriture du fichier temporaire impossible.',
        ];
        mm_fail($messages[$error] ?? 'Échec du téléversement (code ' . $error . ').', 422);
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $originalName = (string) ($file['name'] ?? 'media');
    $size = (int) ($file['size'] ?? 0);

    if ($tmp === '' || !is_uploaded_file($tmp)) {
        mm_fail('Fichier téléversé invalide.', 422);
    }
    if ($size <= 0) {
        mm_fail('Fichier vide.', 422);
    }
    if ($size > MM_MAX_UPLOAD_BYTES) {
        mm_fail('Fichier trop volumineux (limite locale 512 Mo).', 422);
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $kind = mm_kind_for_ext($extension);

    if ($kind === null) {
        mm_fail('Extension non autorisée : ' . ($extension !== '' ? '.' . $extension : '(aucune)'), 422);
    }
    if ($kind !== $mediaType) {
        mm_fail(
            'Type incohérent : le média ciblé est de type « ' . $mediaType . ' », le fichier choisi est de type « ' . $kind . ' ».',
            422
        );
    }

    $sniffed = mm_sniff_kind($tmp);
    if ($sniffed === null || $sniffed !== $kind) {
        mm_fail('Signature du fichier non reconnue ou différente du type déclaré (' . $kind . ').', 422);
    }

    $folder = 'imports/' . date('Ymd-His') . '-' . bin2hex(random_bytes(4));
    $targetDir = MM_STORAGE_DIR . '/' . $folder;

    if (!@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        mm_fail('Création du dossier temporaire impossible.', 500);
    }

    $safeName = mm_safe_filename($originalName);
    $target = $targetDir . '/' . $safeName;

    if (!@move_uploaded_file($tmp, $target)) {
        mm_fail('Déplacement du fichier téléversé impossible.', 500);
    }

    $probe = mm_probe_media($target);
    $sha = mm_sha256($target);

    return [
        'nom_original' => $originalName,
        'nom_fichier'  => $safeName,
        'chemin_temp'  => $folder . '/' . $safeName,
        'octets'       => $size,
        'sha256'       => $sha,
        'format'       => $extension,
        'type'         => $kind,
        'dimensions'   => $probe['dimensions'],
        'duree'        => $probe['duree'],
        'codec'        => $probe['codec'],
        'importe_le'   => mm_now(),
    ];
}

/** Supprime un import temporaire explicitement abandonné (jamais un média du projet). */
function mm_discard_import(string $relativeTemp): bool
{
    $absolute = mm_import_abs($relativeTemp);
    $folder = dirname($absolute);

    if (!@unlink($absolute)) {
        return false;
    }

    $remaining = @scandir($folder) ?: [];
    if (count(array_diff($remaining, ['.', '..'])) === 0) {
        @rmdir($folder);
    }

    return true;
}

