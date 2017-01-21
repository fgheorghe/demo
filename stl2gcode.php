<?php
require "vendor/autoload.php";

use php3d\stlslice\Examples\STL2GCode;
use php3d\stlslice\Examples\STLMillingEdit;
use php3d\stlslice\STLSlice;
use php3d\stl\STL;

if (count($argv) !== 2) {
    die("Usage: php " . $argv[0] . " file-path\n");
}

$fileName = $argv[1];

try {
    bcscale(16);
    $stl = STL::fromString(file_get_contents($fileName));
    $mill = STL::fromArray((new STLMillingEdit($stl->toArray()))
        ->extractMillingContent()
        ->getStlFileContentArray()
    );

    $layers = (new STLSlice($mill, 100))
        ->slice();

    die((new STL2GCode($layers, 100))->toGCodeString());
} catch (Exception $ex) {
    die("Can not convert file: " . $ex->getMessage());
}