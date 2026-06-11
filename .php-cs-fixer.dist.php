<?php

$finder = PhpCsFixer\Finder::create()->in([__DIR__ . '/src', __DIR__ . '/public', __DIR__ . '/tests']);

$config = new PhpCsFixer\Config();
$config->setRules([
    '@PSR12' => true,
    'array_syntax' => ['syntax' => 'short'],
    'fully_qualified_strict_types' => true,
    'global_namespace_import' => [
        'import_classes' => true, 
        'import_constants' => true, 
        'import_functions' => true,
    ],
    'no_unused_imports' => true,
    'ordered_imports' => ['sort_algorithm' => 'alpha'],
]);
$config->setFinder($finder);

return $config;
