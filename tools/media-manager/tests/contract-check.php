<?php

declare(strict_types=1);

/**
 * Vérification du contrat média sur l'inventaire réel (preuve textuelle).
 * Usage : php tools/media-manager/tests/contract-check.php
 */

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/Inventory.php';
require __DIR__ . '/../src/Contract.php';

$state = mm_state_load(false);
$state['inventaire'] = mm_build_inventory(MM_PROJECT_ROOT);
$reports = mm_contract_inventory($state);

foreach ($reports as $path => $report) {
    printf("%s : %s (%d bloquant, %d avertissement)\n", $path, strtoupper($report['statut']), $report['bloquant'], $report['avertissements']);
    foreach ($report['regles'] as $rule) {
        printf("  [%-13s] %-32s %s\n", $rule['statut'], $rule['libelle'], $rule['detail']);
    }
}
