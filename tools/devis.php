<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/portal/quote-store.php';

$command = $argv[1] ?? 'liste';
if (!in_array($command, ['liste', 'lire', 'chemin'], true)
    || ($command === 'lire' && !preg_match('/^[a-f0-9]{32}$/D', $argv[2] ?? ''))) {
    fwrite(STDERR, "Usage : php tools/devis.php [liste|chemin|lire ID]\n");
    exit(2);
}
try {
    $dir = lf_quote_dir();
    if ($command === 'chemin') {
        echo $dir, PHP_EOL;
    } elseif ($command === 'lire') {
        echo json_encode(lf_quote_read($dir . '/' . $argv[2] . '.json'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
    } else {
        $found = [];
        foreach (new DirectoryIterator($dir) as $file) {
            if (preg_match('/^([a-f0-9]{32})\.json$/D', $file->getFilename(), $match) && !$file->isLink() && $file->isFile()) {
                $found[] = [$file->getMTime(), $match[1]];
            }
        }
        rsort($found);
        if (!$found) {
            echo "Aucune demande enregistrée.\n";
        }
        foreach ($found as [$time, $id]) {
            echo gmdate('Y-m-d H:i:s', $time), " UTC  ", $id, PHP_EOL;
        }
    }
} catch (Throwable) {
    fwrite(STDERR, "Impossible d’accéder aux devis. Vérifiez le dossier privé, les permissions et l’identifiant.\n");
    exit(1);
}
