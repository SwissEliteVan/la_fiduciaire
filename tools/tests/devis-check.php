<?php

declare(strict_types=1);

/**
 * Tests ciblés de la validation du formulaire de devis.
 * Aucune base de données, aucune session : exécutable partout.
 *
 * Commande : php tools/tests/devis-check.php
 */

require dirname(__DIR__, 2) . '/app/bootstrap.php';
require APP_ROOT . '/app/devis.php';

$failures = 0;

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

$valide = [
    'nom'          => 'Camille Test',
    'email'        => 'camille@example.test',
    'telephone'    => '+41 21 000 00 00',
    'activite'     => 'Indépendante',
    'canton'       => 'Vaud',
    'besoin'       => 'TVA',
    'message'      => 'Bonjour, je souhaite un devis pour la TVA.',
    'consentement' => '1',
];

$result = devis_validate($valide);
check('formulaire valide accepté', $result['errors'] === []);
check('adresse e-mail conservée', $result['data']['email'] === 'camille@example.test');
check('consentement conservé', $result['data']['consentement'] === '1');

$nomsValides = ['O’Brien', "O'Brien", 'Müller-Lüthi', 'Anne-Marie de la Tour'];
foreach ($nomsValides as $nom) {
    check('nom accepté : ' . $nom, devis_validate(array_merge($valide, ['nom' => $nom]))['errors'] === []);
}

$optionnelsVides = array_merge($valide, ['telephone' => '', 'activite' => '', 'canton' => '']);
check('champs facultatifs vides acceptés', devis_validate($optionnelsVides)['errors'] === []);

$cas = [
    'nom vide'                  => ['nom' => ''],
    'nom trop court'            => ['nom' => 'A'],
    'nom avec balise'           => ['nom' => '<script>alert(1)</script>'],
    'nom avec chiffres'         => ['nom' => 'Client 42'],
    'email invalide'            => ['email' => 'nope'],
    'email vide'                => ['email' => ''],
    'telephone invalide'        => ['telephone' => 'abc'],
    'telephone trop court'      => ['telephone' => '123'],
    'besoin hors liste'         => ['besoin' => 'Inconnu'],
    'besoin absent'             => ['besoin' => ''],
    'message trop court'        => ['message' => 'Bonjour'],
    'message trop long'         => ['message' => str_repeat('a', 4001)],
    'consentement absent'       => ['consentement' => ''],
    'consentement autre valeur' => ['consentement' => 'oui'],
    'caractere de controle'     => ['nom' => "Camille\x07Test"],
    'activite trop longue'      => ['activite' => str_repeat('a', 161)],
    'canton trop long'          => ['canton' => str_repeat('a', 61)],
];

foreach ($cas as $label => $override) {
    $champ = (string) array_key_first($override);
    $errors = devis_validate(array_merge($valide, $override))['errors'];
    check('refus : ' . $label, array_key_exists($champ, $errors));
}

echo "\n" . ($failures === 0 ? 'RESULTAT : PASS' : "RESULTAT : FAIL ({$failures})") . "\n";
exit($failures === 0 ? 0 : 1);
