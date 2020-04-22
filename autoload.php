<?php

declare(strict_types=1);

namespace Application;

use Application\Core\Foundation\Autoloader;

// Include the Autoloader class
require ROOT_DIR . 'application/Core/Foundation/Autoloader.php';

// Instantiate an autoloader
$loader = new Autoloader();

// Register the autoloader
$loader->register();

// Add namespaces
$loader->addNamespace(__NAMESPACE__, APP_DIR);
$loader->addNamespace('CssMin', ROOT_DIR . 'vendor/CssMin');
$loader->addNamespace('JShrink', ROOT_DIR . 'vendor/JShrink');
$loader->addNamespace('JsonSchema', ROOT_DIR . 'vendor/JsonSchema');
$loader->addNamespace('Markdown', ROOT_DIR . 'vendor/Markdown');
$loader->addNamespace('ZxcvbnPhp', ROOT_DIR . 'vendor/ZxcvbnPhp');
$loader->addNamespace('Box\Spout', ROOT_DIR . 'vendor/Spout');
$loader->addNamespace('SyntaxHighlight', ROOT_DIR . 'vendor/SyntaxHighlight');

spl_autoload_register(function ($class) {
    $segments = explode('\\', $class);
    if ($segments[0] !== 'DataTables') {
        return;
    }
    $file = ROOT_DIR . 'vendor/DataTables/';
    if (count($segments) === 2) {
        $file .= $segments[1] . '/' . $segments[1];
    } else if (count($segments) === 3) {
        preg_match_all('/[A-Z]+[^A-Z]*/', $segments[2], $matches);
        $file .= $segments[1] . '/' . implode('/', $matches[0]);
    }
    $file .= '.php';
    require $file;
});

// -- End of file
