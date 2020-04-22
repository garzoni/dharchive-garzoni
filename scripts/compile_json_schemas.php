<?php

declare(strict_types=1);

namespace Application;

use function Application\createText as _;

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

// Variables
$workingDir = $config->dir->db . 'schemas/';

$templateDir = $workingDir . 'templates';
$templateFileExt = '.tpl.json';
$schemaDir = $workingDir . 'compiled';
$schemaFileExt = '.schema.json';

$sqlScriptFile = $workingDir . 'scripts/json_schemas.sql';
$sqlScriptContent = '';
$sqlCommand = "UPDATE entity_types "
	. "SET details = jsonb_set(details, '{schema}', '%s') "
	. "WHERE prefix = '%s' AND name = '%s';";

$entityTypes = [];
$schemaVariables = [];

$errorFlag = '-> ERROR: ';

/*----------------------------------------------------------------------------
   Functions
  ----------------------------------------------------------------------------*/

/**
 * Inserts sequence numbers to JSON Schema properties.
 *
 * @param array $object
 */
function insertPropertyOrder(array &$object)
{
    foreach ($object as $key => $value) {
        if (is_array($value)) {
            if ($key === 'properties') {
                $sequenceNumber = 1;
                foreach ($value as $k => $v) {
                    if (is_array($v)) {
                        $object[$key][$k]['propertyOrder'] = $sequenceNumber;
                        $sequenceNumber++;
                    }
                }
            }
            insertPropertyOrder($object[$key]);
        }
    }
}

/*----------------------------------------------------------------------------
   Execution
  ----------------------------------------------------------------------------*/

require $workingDir . 'schema_definitions.php';
require $workingDir . 'entity_types.php';

// Remove all files from the schema directory
array_map('unlink', glob($schemaDir . '/*'));

foreach ($entityTypes as $typeQName) {
	$filename = _($typeQName)->replace(':', '_')->underscorize()->toString();
	$tplFile = $templateDir . '/' . $filename . $templateFileExt;
	if (!file_exists($tplFile)) {
        continue;
    }
	$schema = trim(file_get_contents($tplFile));
	if (empty($schema)) {
        continue;
	}
    $schema = strtr($schema, $schemaVariables);
    $data = json_decode($schema, true);
    echo 'Generating JSON Schema for ' . $typeQName . PHP_EOL;
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            if (empty($data)) {
                break;
            }
            insertPropertyOrder($data);
            $schemaFile = $schemaDir . '/' . $filename . $schemaFileExt;
            list($typePrefix, $typeName) = explode(':', $typeQName);
            $sqlScriptContent .= sprintf(
                $sqlCommand,
                json_encode(
                    $data,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                ),
                $typePrefix,
                $typeName
            );
            $sqlScriptContent .= PHP_EOL;
            file_put_contents(
                $schemaFile,
                json_encode(
                    $data,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                )
            );
            echo '-> Saved in ' . $schemaFile;
            break;
        case JSON_ERROR_DEPTH:
            echo $errorFlag . 'Maximum stack depth exceeded';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            echo $errorFlag . 'Underflow or mode mismatch';
            break;
        case JSON_ERROR_CTRL_CHAR:
            echo $errorFlag . 'Unexpected control character found';
            break;
        case JSON_ERROR_SYNTAX:
            echo $errorFlag . 'Syntax error, malformed JSON';
            break;
        case JSON_ERROR_UTF8:
            echo $errorFlag . 'Malformed UTF-8 characters, '
                . 'possibly incorrectly encoded';
            break;
        default:
            echo $errorFlag . 'Unknown error';
            break;
    }
    echo PHP_EOL;
}

file_put_contents($sqlScriptFile, $sqlScriptContent);
echo 'Process completed.' . PHP_EOL;

// -- End of file
