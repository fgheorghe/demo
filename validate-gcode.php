<?php

require("Validator/CNCMachine.php");
require("Validator/CNCMillingValidator.php");
require("Validator/DoesNotFitException.php");
require("Validator/UnreachableAreaException.php");

if (count($argv) !== 2) {
    die("Usage: php " . $argv[0] . " file-path\n");
}

$fileName = $argv[1];

try {
    bcscale(16);
    (new CNCMillingValidator((new CNCMachine()), file_get_contents($fileName)))->validate();
} catch (DoesNotFitException $ex) {
    die("Piece does not fit machine: " . $ex->getMessage() . "\n");
} catch (UnreachableAreaException $ex) {
    die("Unreachable area detected.\n");
} catch (Exception $ex) {
    die("Unknown error: " . $ex->getMessage() . ".\n");
}

echo "GCode valid.\n";