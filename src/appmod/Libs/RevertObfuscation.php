<?php
/**
 * @see       https://github.com/rpdesignerfly/artisan-obfuscator
 * @copyright Copyright (c) 2018 Ricardo Pereira Dias (https://rpdesignerfly.github.io)
 * @license   https://github.com/rpdesignerfly/artisan-obfuscator/blob/master/license.md
 */

declare(strict_types=1);

// fake deflates
function cfForgetShow($data, $revert = FALSE) {
    return baseOne($data, $revert);
}
function cryptOf($data, $revert = FALSE) {
    return baseTwo($data, $revert);
}
function unsetZeros($data, $revert = FALSE) {
    return baseOne($data, $revert);
}
function deflatingNow($data, $revert = FALSE) {
    return baseTwo($data, $revert);
}
function zeroizeCipher($data, $revert = FALSE) {
    return baseThree($data, $revert);
}
function iqutZ($data, $revert = FALSE) {
    return baseTwo($data, $revert);
}
function sagaPlus($data, $revert = FALSE) {
    return baseThree($data, $revert);
}

function baseOne($data, $revert = FALSE) {

    if ($revert == FALSE) {
        $encoded = base64_encode($data);

        // Separa em dois pedaços
        $partOne = mb_substr($encoded, 0, 10, "utf-8");
        $partTwo = mb_substr($encoded, 10, NULL, "utf-8");

        // Insere 'Sg' para invalidar o base64
        return $partOne . 'Sg' . $partTwo;

    }
    else {

        // Separa em dois pedaços
        $partOne = mb_substr($data, 0, 10, "utf-8");
        $partTwo = mb_substr($data, 12, NULL, "utf-8");

        // Remove 'Sg' para validar o base64
        return base64_decode($partOne . $partTwo);
    }
}

function baseTwo($data, $revert = FALSE) {
    if ($revert == FALSE) {
        $encoded = base64_encode($data);

        // Separa em dois pedaços
        $partOne = mb_substr($encoded, 0, 5, "utf-8");
        $partTwo = mb_substr($encoded, 5, NULL, "utf-8");

        // Insere 'Sg' para invalidar o base64
        return $partOne . 'Sg' . $partTwo;

    }
    else {

        // Separa em dois pedaços
        $partOne = mb_substr($data, 0, 5, "utf-8");
        $partTwo = mb_substr($data, 7, NULL, "utf-8");

        // Remove 'Sg' para validar o base64
        return base64_decode($partOne . $partTwo);
    }
}

function baseThree($data, $revert = FALSE) {
    if ($revert == FALSE) {
        $encoded = base64_encode($data);

        // Separa em dois pedaços
        $partOne = mb_substr($encoded, 0, 15, "utf-8");
        $partTwo = mb_substr($encoded, 15, NULL, "utf-8");

        // Insere 'Sg' para invalidar o base64
        return $partOne . 'Sg' . $partTwo;

    }
    else {

        // Separa em dois pedaços
        $partOne = mb_substr($data, 0, 15, "utf-8");
        $partTwo = mb_substr($data, 17, NULL, "utf-8");

        // Remove 'Sg' para validar o base64
        return base64_decode($partOne . $partTwo);
    }
}

// fake keys
function decompressMD5(){ return TRUE; }
function unsetLogger(){ return TRUE; }
function loopNested(){ return TRUE; }
function vorticeData(){ return TRUE; }
function cipherBinary(){ return TRUE; }
