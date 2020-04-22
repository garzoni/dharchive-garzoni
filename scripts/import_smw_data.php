<?php

declare(strict_types=1);

namespace Application;

use Application\Core\Database\Database;
use Application\Core\Cache\ApcStore;
use Application\Models\Entity;
use Application\Models\EntityProperty;
use Application\Models\EntityRelation;
use Application\Models\EntityRelationRule;
use Application\Models\EntityType;
use Application\Models\LogActionType;
use Application\Models\Robot;

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
const IMPORTER_BOTNAME = 'smw_data_importer';
const SELECTED_CONTRACTS = [];
/*
const SELECTED_CONTRACTS = [
    '56c43ed2947bf3b425586e7e',
    '56c43ed3947bf3b42558701c',
    '56c43ed0947bf3b425586117',
    '56c43ed0947bf3b4255863a4',
    '56c43edf947bf3b4255887dc',
    '56c43edf947bf3b4255887e0',
    '56c43edf947bf3b4255887f4',
    '56c43edf947bf3b4255887f7',
    '56c43edf947bf3b4255887f9',
];
*/

// Data files
$entitiesFile = $config->dir->temp . 'all_entities.tsv';
$entityRelationsFile = $config->dir->temp . 'all_relations.tsv';

// Error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (error_reporting() === 0) {
        return false;
    }
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

/*----------------------------------------------------------------------------
   Functions
  ----------------------------------------------------------------------------*/

function parseCsvFile($file, $callback, $delimiter = "\t") {
    if (($handle = fopen($file, 'r')) !== false) {
        $rowNumber = 0;
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;
            $callback($rowNumber, $data);
        }
        fclose($handle);
    }
}

function parseEntityRecord($data) {
    return [
        'entity_id' => trim($data[0]),
        'entity_type_id' => (int) $data[1],
        'entity_type' => trim($data[3]),
        'properties' => trim($data[2]),
        'contract_oid' => trim($data[4] ?? ''),
        'person_oid' => trim($data[5] ?? ''),
    ];
}

function parseEntityRelationRecord($data) {
    return [
        'entity_relation_rule_id' => (int) $data[0],
        'domain_entity_id' => trim($data[1]),
        'entity_property_id' => (int) $data[2],
        'range_entity_id' => trim($data[3]),
        'domain_type' => trim($data[4]),
        'property' => trim($data[5]),
        'range_type' => trim($data[6]),
        'contract_oid' => trim($data[7] ?? ''),
    ];
}

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

// Instantiate a cache store
$cacheStore = new ApcStore($config->cache->lifetime);
$cacheStore->setPrefix($config->cache->prefix);

// Cache table data
EntityType::cacheData($db, $cacheStore);
EntityProperty::cacheData($db, $cacheStore);
EntityRelationRule::cacheData($db, $cacheStore);
LogActionType::cacheData($db, $cacheStore);

// Instantiate model classes
$robot = new Robot($db);
$entity = new Entity($db);
$entityRelation = new EntityRelation($db);

// Get importer agent identifier
$agentId = $robot->findByBotname(IMPORTER_BOTNAME)->get('id');
if (is_null($agentId)) {
    echo 'ERROR: Robot "' . IMPORTER_BOTNAME . '" does not exist.' . PHP_EOL;
    exit(1);
}

/* Data Validation
  ----------------------------------------------------------------------------*/

$totalEntitiesCount = 0;
$totalEntityRelationsCount = 0;
$abortMessage = 'Data import aborted!';

// Entity Records
fwrite(STDERR, 'Processing ' . basename($entitiesFile) . PHP_EOL);
$invalidRecords = 0;
parseCsvFile($entitiesFile, function($rowNumber, $data)
    use($entity, &$totalEntitiesCount, &$invalidRecords) {
    $progressLine = 'Valid records: %d' . PHP_EOL . 'Invalid records: %d' . PHP_EOL;
    $clear = "\e[2A\e[K\e[K";
    if ($rowNumber === 1) {
        fwrite(STDERR, sprintf($progressLine, 0, 0));
        return;
    }
    $columnCount = count($data);
    $isValidRow = true;
    if (($columnCount < 5) || ($columnCount > 6)) {
        if ($isValidRow) {
            fwrite(STDERR, $clear);
        }
        echo 'ERROR: Invalid column count [' . $rowNumber . ']' . PHP_EOL;
        $isValidRow = false;
    }

    $data = parseEntityRecord($data);
    $contracts = explode(' ', $data['contract_oid']);
    if (!empty(SELECTED_CONTRACTS) && empty(array_intersect(SELECTED_CONTRACTS, $contracts))) {
        return;
    }
    $totalEntitiesCount++;

    if (!preg_match('/' . REGEX_UUID . '/', $data['entity_id'])) {
        if ($isValidRow) {
            fwrite(STDERR, $clear);
        }
        echo 'ERROR: Invalid entity ID [' . $rowNumber . ']' . PHP_EOL;
        $isValidRow = false;
    }
    if ($data['entity_type_id'] !== $entity->getTypeId($data['entity_type'])) {
        if ($isValidRow) {
            fwrite(STDERR, $clear);
        }
        echo 'ERROR: Invalid entity type (ID or qualified name) [' . $rowNumber . ']' . PHP_EOL;
        $isValidRow = false;
    }

    if (!$entity->isValid($data['entity_type_id'], $data['properties'])) {
        if ($isValidRow) {
            fwrite(STDERR, $clear);
        }
        echo 'ERROR: Invalid entity properties [' . $rowNumber . ']' . PHP_EOL;
        foreach ($entity->getPropertyValidationErrors() as $number => $details) {
            $number = str_pad(strval($number + 1), 2, '0', STR_PAD_LEFT);
            echo str_repeat(' ', 7) . '#' . $number . ' '
                . ($details['message'] ?? '') . PHP_EOL;
            foreach ($details as $property => $value) {
                if (($property === 'message') || empty($value)) {
                    continue;
                }
                echo str_repeat(' ', 12) . '> ' . $property . ': ' . $value . PHP_EOL;
            }
        }
        $isValidRow = false;
    }

    if (!$isValidRow) {
        $invalidRecords++;
    } else {
        fwrite(STDERR, $clear);
    }
    fwrite(STDERR, sprintf($progressLine, $totalEntitiesCount - $invalidRecords, $invalidRecords));
});

if ($invalidRecords > 0) {
    fwrite(STDERR, $abortMessage . PHP_EOL);
    exit(1);
}

// Entity Relation Records
fwrite(STDERR, 'Processing ' . basename($entityRelationsFile) . PHP_EOL);
$invalidRecords = 0;
parseCsvFile($entityRelationsFile, function($rowNumber, $data)
use($entityRelation, &$totalEntityRelationsCount, &$invalidRecords) {
    $progressLine = 'Valid records: %d' . PHP_EOL . 'Invalid records: %d' . PHP_EOL;
    $clear = "\e[2A\e[K\e[K";
    if ($rowNumber === 1) {
        fwrite(STDERR, sprintf($progressLine, 0, 0));
        return;
    }
    $columnCount = count($data);
    $isValidRow = true;
    if ($columnCount !== 8) {
        if ($isValidRow) {
            fwrite(STDERR, $clear);
        }
        echo 'ERROR: Invalid column count [' . $rowNumber . ']' . PHP_EOL;
        $isValidRow = false;
    }

    $data = parseEntityRelationRecord($data);
    $contracts = explode(' ', $data['contract_oid']);
    if (!empty(SELECTED_CONTRACTS) && empty(array_intersect(SELECTED_CONTRACTS, $contracts))) {
        return;
    }
    $totalEntityRelationsCount++;

    if (!preg_match('/' . REGEX_UUID . '/', $data['domain_entity_id'])) {
        if ($isValidRow) {
            fwrite(STDERR, $clear);
        }
        echo 'ERROR: Invalid domain entity ID [' . $rowNumber . ']' . PHP_EOL;
        $isValidRow = false;
    }
    if (!preg_match('/' . REGEX_UUID . '/', $data['range_entity_id'])) {
        if ($isValidRow) {
            fwrite(STDERR, $clear);
        }
        echo 'ERROR: Invalid range entity ID [' . $rowNumber . ']' . PHP_EOL;
        $isValidRow = false;
    }
    $relationRuleId = $entityRelation->getRelationRuleId([
        $data['domain_type'], $data['property'], $data['range_type']
    ]);
    if ($data['entity_relation_rule_id'] !== $relationRuleId) {
        if ($isValidRow) {
            fwrite(STDERR, $clear);
        }
        echo 'ERROR: Invalid entity relation rule [' . $rowNumber . ']' . PHP_EOL;
        $isValidRow = false;
    }
    if (!$isValidRow) {
        $invalidRecords++;
    } else {
        fwrite(STDERR, $clear);
    }
    fwrite(STDERR, sprintf($progressLine, $totalEntityRelationsCount - $invalidRecords, $invalidRecords));
});

if ($invalidRecords > 0) {
    fwrite(STDERR, $abortMessage . PHP_EOL);
    exit(1);
}

/* Data Import
  ----------------------------------------------------------------------------*/

$importedEntitiesCount = 0;
$importedEntityRelationsCount = 0;

// Entity Records
parseCsvFile($entitiesFile, function($rowNumber, $data)
    use($entity, $agentId, $totalEntitiesCount, &$importedEntitiesCount) {
    $progressLine = 'Importing entities: %d%% (%d/%d)' . PHP_EOL;
    $clear = "\e[1A\e[K";

    if ($rowNumber === 1) {
        fwrite(STDERR, sprintf($progressLine, 0, 0, $totalEntitiesCount));
        return;
    }

    $data = parseEntityRecord($data);
    $contracts = explode(' ', $data['contract_oid']);
    if (!empty(SELECTED_CONTRACTS) && empty(array_intersect(SELECTED_CONTRACTS, $contracts))) {
        return;
    }

    try {
        $entity->delete($data['entity_id']);
        $entity->create(
            $data['properties'],
            $agentId,
            $data['entity_id'],
            $data['entity_type']
        );
        fwrite(STDERR, $clear);
        $importedEntitiesCount++;
    } catch (\Exception $e) {
        fwrite(STDERR, $clear);
        $indent = str_repeat(' ', 7);
        echo 'ERROR: ' . $e->getMessage() . ' [' . $importedEntitiesCount . ']' . PHP_EOL;
        echo $indent . preg_replace('@\n@', ("\n" . $indent), $e->getTraceAsString()) . PHP_EOL;
    }

    $percentageOfCompletion = floor(($importedEntitiesCount / $totalEntitiesCount) * 100);
    fwrite(STDERR, sprintf($progressLine, $percentageOfCompletion,
            $importedEntitiesCount, $totalEntitiesCount));
});

// Entity Relation Records
parseCsvFile($entityRelationsFile, function($rowNumber, $data)
use($entityRelation, $agentId, $totalEntityRelationsCount, &$importedEntityRelationsCount) {
    $progressLine = 'Importing entity relations: %d%% (%d/%d)' . PHP_EOL;
    $clear = "\e[1A\e[K";

    if ($rowNumber === 1) {
        fwrite(STDERR, sprintf($progressLine, 0, 0, $totalEntityRelationsCount));
        return;
    }

    $data = parseEntityRelationRecord($data);
    $contracts = explode(' ', $data['contract_oid']);
    if (!empty(SELECTED_CONTRACTS) && empty(array_intersect(SELECTED_CONTRACTS, $contracts))) {
        return;
    }

    try {
        $entityRelation->create(
            $data['entity_relation_rule_id'],
            $data['domain_entity_id'],
            $data['entity_property_id'],
            $data['range_entity_id'],
            $agentId
        );
        fwrite(STDERR, $clear);
        $importedEntityRelationsCount++;
    } catch (\Exception $e) {
        fwrite(STDERR, $clear);
        $indent = str_repeat(' ', 7);
        echo 'ERROR: ' . $e->getMessage() . ' [' . $importedEntityRelationsCount . ']' . PHP_EOL;
        echo $indent . preg_replace('@\n@', ("\n" . $indent), $e->getTraceAsString()) . PHP_EOL;
    }

    $percentageOfCompletion = floor(($importedEntityRelationsCount / $totalEntityRelationsCount) * 100);
    fwrite(STDERR, sprintf($progressLine, $percentageOfCompletion,
        $importedEntityRelationsCount, $totalEntityRelationsCount));
});

fwrite(STDERR, 'Data import completed successfully!' . PHP_EOL);

// -- End of file
