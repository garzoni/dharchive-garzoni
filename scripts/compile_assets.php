<?php

declare(strict_types=1);

namespace Application;

use Application\Core\Text\TextGenerator;
use CssMin\Minifier as CssMinifier;
use JShrink\Minifier as JsMinifier;

/*----------------------------------------------------------------------------
   Includes
  ----------------------------------------------------------------------------*/

// Constants
require realpath('../constants.php');

// Class autoloading
require ROOT_DIR . 'autoload.php';

/*----------------------------------------------------------------------------
   Configuration
  ----------------------------------------------------------------------------*/

// Load application configuration
$config = require ROOT_DIR . 'configuration/main.conf.php';

/*----------------------------------------------------------------------------
   Execution
  ----------------------------------------------------------------------------*/

// Load asset definitions
$sourceFiles = require $config->files->asset_sources;

if(!is_array($sourceFiles)) {
    echo 'Error: The asset definitions must be stored in arrays.' . PHP_EOL;
    exit(1);
}

// Instantiate a text generator
$generator = new TextGenerator();

// Instantiate code minifiers
$cssMinifier = new CssMinifier();
$jsMinifier = new JsMinifier();

// Initialize arrays for processing
$assets = [];
$compiledFiles = [];

// Parse command line argument list
if ($argc === 1) {
    // Process all resources
    $assets = $sourceFiles;
}  else {
    // Process the specified resources, if they have been defined
    for ($i = 1; $i < $argc; $i++) {
        $resource = $argv[$i];
        if (array_key_exists($resource, $sourceFiles)) {
            $assets[$resource] = $sourceFiles[$resource];
        }
    }
}

// Process resources
foreach ($assets as $resource => $files) {

    // File properties
    $fileExtension = pathinfo($resource, PATHINFO_EXTENSION);
    $minifiedContent = '';

    if (!in_array($fileExtension, ['css', 'js'])) {
        trigger_error('Unsupported resource type: .' . $fileExtension, E_USER_WARNING);
        continue;
    }

    // Source repository
    $repository = $config->dir->public . 'sources/' . $fileExtension . '/';

    // Compress and concatenate the files
    foreach ($files as $assetName) {
        if (filter_var($assetName, FILTER_VALIDATE_URL)) {
            $file = $assetName;
        } else if (substr($assetName, 0, 1) === '/') {
            $file = $config->url->base . ltrim($assetName, '/');
        } else {
            $file = $repository . $assetName;
        }

        $fileContent = file_get_contents($file);

        if ($fileContent !== false) {
            switch ($fileExtension) {
                case 'css':
                    $minifiedContent .= $cssMinifier->run($fileContent, 2000);
                    break;
                case 'js':
                    $minifiedContent .= $jsMinifier->minify($fileContent);
                    break;
            }
        } else {
            trigger_error('File not found: ' . $file, E_USER_WARNING);
        }
    }

    $fileName = $generator->getUuid4() . '.' . $fileExtension;

    // Save output
    $outputDir = $config->dir->public . 'assets/' . $fileExtension . '/';
    if (file_put_contents($outputDir . $fileName, $minifiedContent)) {
        $compiledFiles[$resource] = $fileName;
    }
}

// Update resource fingerprints
$fileContent = '<?php' . str_repeat(PHP_EOL, 2) . 'return ';
$fileContent .= var_export($compiledFiles, true) . ';' . str_repeat(PHP_EOL, 2);
$fileContent .= '// This file has been generated. Do not modify it manually.' . PHP_EOL;

file_put_contents($config->files->compiled_assets, $fileContent);

echo 'The assets were successfully compiled.' . PHP_EOL;

// -- End of file
