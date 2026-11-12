<?php

declare(strict_types=1);

/**
 * Vérification en ligne de commande de l'inventaire (preuve textuelle).
 * Usage : php tools/media-manager/tests/inventory-check.php
 */

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/Inventory.php';

$items = mm_build_inventory(MM_PROJECT_ROOT);

echo 'Projet : ' . mm_project_name() . PHP_EOL;
echo 'Pile   : ' . mm_detect_stack() . PHP_EOL;
echo 'Médias : ' . count($items) . PHP_EOL;
echo str_repeat('-', 100) . PHP_EOL;

foreach ($items as $item) {
    $dimensions = ($item['dimensions']['largeur'] ?? null) !== null
        ? $item['dimensions']['largeur'] . 'x' . $item['dimensions']['hauteur']
        : 'n/d';

    printf(
        "%-28s %-6s %-6s %10s o  %-11s %-10s var=%-40s usages=%d%s\n",
        $item['chemin'],
        $item['type'],
        $item['format'],
        number_format($item['octets'], 0, ',', ' '),
        $item['statut'],
        $dimensions,
        implode(',', $item['variantes']),
        $item['usages_total'],
        $item['doublon_de'] !== null ? ' doublon_de=' . $item['doublon_de'] : ''
    );

    foreach ($item['usages'] as $usage) {
        printf("    -> %s:%d %s %s\n", $usage['fichier'], $usage['ligne'], $usage['exact'] ? '[exact]' : '[partiel]', $usage['extrait']);
    }
}
