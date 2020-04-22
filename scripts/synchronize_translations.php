<?php

declare(strict_types=1);

namespace Application;

use Application\Core\Text\TranslationManager;
use Application\Core\Text\Translator;
use Application\Core\Type\Map;

/*----------------------------------------------------------------------------
   Includes
  ----------------------------------------------------------------------------*/

// Constants
require realpath('../constants.php');

// Class autoloading
require ROOT_DIR . 'autoload.php';

// Common functions
require ROOT_DIR . 'functions.php';

/*----------------------------------------------------------------------------
   Configuration
  ----------------------------------------------------------------------------*/

// Load application configuration
$config = require ROOT_DIR . 'configuration/main.conf.php';

// Constants
const EXTRACT_KEYS = true;

// Regular expression pattern for key extraction
$keyRegex = '\s*\'([a-z][a-z0-9_.]+)\'';
$functions = ['get', 'getTime', 'interpolate', 'pluralize'];
$regex = '/';
foreach ($functions as $function) {
    $regex .= '\$this->text->' . $function . '\(' . $keyRegex . '|';
}
$regex .= '\/\*\s*#lang\s*\*\/' . $keyRegex . '/m';

// Target directories for key extraction
$directories = [
    $config->dir->app,
    $config->dir->templates,
];

// List of keys to be excluded from the unused key check
$canonicalKeys = ['date_time', 'unit', 'role', 'permission'];

/*----------------------------------------------------------------------------
   Execution
  ----------------------------------------------------------------------------*/

// Application languages
$languages = new Map($config->languages->toArray());

// Instantiate a translator
$translator = new Translator($config->dir->lang);

// Instantiate a translation manager
$translationManager = new TranslationManager(
    $translator,
    $languages,
    $config->dir->backup
);

// Load the translations for the default language
$masterDictionary = new Map($translator->export());

if (EXTRACT_KEYS) {
    // Extract translation keys from files
    $extractedKeys = $translationManager->extractKeys($directories, $regex);

    foreach ($extractedKeys as $key => $value) {
        if (substr($key, -1, 1) === '.') {
            continue;
        }
        if (!$masterDictionary->has($key)) {
            $masterDictionary->set($key, '');
        }
    }
}

$masterRules = $masterDictionary->flatten()->toArray();

// Synchronize translation files
foreach ($config->languages->toArray() as $language => $properties) {
    $dictionary = new Map();
    $rules = $translator->getDictionary($language);
    foreach ($masterRules as $key => $value) {
        $dictionary->set($key, $rules->get($key, ''));
    }
    $dictionary->sortByKeys();
    $translationManager->saveDictionary($language, $dictionary);
}

// Check for unused keys
$unusedKeys = [];
if (isset($extractedKeys) && is_array($extractedKeys)) {
    foreach ($masterRules as $key => $value) {
        if (!array_key_exists($key, $extractedKeys)) {
            $prefix = strstr($key, '.', true) ?: '';
            if (!in_array($prefix, $canonicalKeys)) {
                $unusedKeys[] = ' - ' . $key;
            }
        }
    }
}

echo 'All language files were successfully synchronized.' . PHP_EOL;

if (!empty($unusedKeys)) {
    echo 'The following keys are present in the master dictionary, but are not '
        . 'found in the list of keys extracted from the source code:' . PHP_EOL;
    echo implode(PHP_EOL, $unusedKeys) . PHP_EOL;
}

// -- End of file
