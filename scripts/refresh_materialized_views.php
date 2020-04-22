<?php

declare(strict_types=1);

namespace Application;

use Application\Core\Database\Database;

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

/*----------------------------------------------------------------------------
   Execution
  ----------------------------------------------------------------------------*/

// Disable execution time limit
set_time_limit(0);

// Instantiate a PDO wrapper
$db = new Database(
    $config->db->dsn,
    $config->db->username,
    $config->db->password
);

$command = '
    REFRESH MATERIALIZED VIEW collections;
    REFRESH MATERIALIZED VIEW manifests;
    REFRESH MATERIALIZED VIEW canvases;
    REFRESH MATERIALIZED VIEW canvas_object_annotations;
    REFRESH MATERIALIZED VIEW mention_annotations;
    REFRESH MATERIALIZED VIEW mentions;
    REFRESH MATERIALIZED VIEW si_tags;
    REFRESH MATERIALIZED VIEW si_named_entities;
    REFRESH MATERIALIZED VIEW person_mentions;
    REFRESH MATERIALIZED VIEW person_relationships;
';

$db->executeUpdate($command);

// -- End of file
