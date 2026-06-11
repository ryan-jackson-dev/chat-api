<?php

require 'vendor/autoload.php';

use Slim\Factory\AppFactory;

$slimStatus = false;

try {
    AppFactory::create();
    $slimStatus = true;
} catch (Exception $e) {
    echo "Slim framework failed to load: {$e->getMessage()}\n";
}

$components = [
    // If we got this far the Autoloader is present.
    'Autoloader' => true,
    'DOM (for PHPUnit)' => class_exists('DOMDocument'),
    'JSON (for API)' => function_exists('json_encode'),
    'PDO SQLite Driver' => in_array('sqlite', PDO::getAvailableDrivers()),
    'PHP Version' => PHP_VERSION,
    'Slim' => $slimStatus,
    'SQLite3 Class' => class_exists('SQLite3'),
];

$padding = 22;

echo str_pad("COMPONENT", $padding) . "STATUS\n";
echo str_repeat("-", 60) . "\n";

foreach ($components as $key => $value) {
    $status = $value ? "✅ OK" : "❌ MISSING";
    echo str_pad($key, $padding) . $status . "\n";
}
