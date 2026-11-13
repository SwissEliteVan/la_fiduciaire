<?php

declare(strict_types=1);

/**
 * Double de test uniquement, utilisé en recette locale sur 127.0.0.1.
 * Ne détecte aucun logiciel malveillant. Ne jamais configurer en production.
 */

$args = array_slice($argv, 1);
if (count($args) !== 3 || $args[0] !== '--no-summary' || $args[1] !== '--' || !is_file($args[2])) {
    fwrite(STDERR, "usage: scanner-factice.php --no-summary -- FICHIER\n");
    exit(2);
}

$content = (string) file_get_contents($args[2], false, null, 0, 4096);
if (str_contains($content, 'LF_SCANNER_FAIL')) {
    exit(2);
}
if (str_contains($content, 'LF_SCANNER_REFUSE')) {
    exit(1);
}
exit(0);
