<?php

declare(strict_types=1);

namespace Application;

use Application\Core\Text\TextGenerator;

/*----------------------------------------------------------------------------
   Includes
  ----------------------------------------------------------------------------*/

// Constants
require realpath('../constants.php');

// Class autoloading
require ROOT_DIR . 'autoload.php';

/*----------------------------------------------------------------------------
   Execution
  ----------------------------------------------------------------------------*/

// Get options from the command line argument list
$optind = null;
$options = getopt('n:', []);

// Number of UUIDs (required value)
$n = isset($options['n']) ? (int) $options['n'] : 1;

// Instantiate a text generator
$generator = new TextGenerator();

// Generate UUIDs
for ($i = 0; $i < $n; $i++) {
    echo $generator->getUuid4() . PHP_EOL;
}

// -- End of file
