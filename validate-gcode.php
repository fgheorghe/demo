<?php

require("Validator/CNCMachine.php");
require("Validator/CNCMillingValidator.php");
require("Validator/DoesNotFitException.php");
require("Validator/UnreachableAreaException.php");
require "pointLocation.php";

if (count($argv) !== 2) {
    die("Usage: php " . $argv[0] . " file-path\n");
}

$fileName = $argv[1];

try {
    bcscale(16);
    (new CNCMillingValidator((new CNCMachine()), file_get_contents($fileName)))->validate();
} catch (DoesNotFitException $ex) {
    die("\nPiece does not fit machine: " . $ex->getMessage() . "\n");
} catch (UnreachableAreaException $ex) {
    die("\nUnreachable area detected.\n");
} catch (Exception $ex) {
    die("\nUnknown error: " . $ex->getMessage() . ".\n");
}

echo "\nGCode valid.\n";