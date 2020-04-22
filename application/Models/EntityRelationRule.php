<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;
use Application\Core\Type\Map;
use Application\Core\Type\Table;

/**
 * Class EntityRelationRule
 * @package Application\Models
 */
class EntityRelationRule extends PersistentModel
{
    const TABLE = 'entity_relation_rules';
    const FIELDS = [
        'id' => [
            'column' => 'id',
            'type' => 'int',
        ],
        'domain_type_id' => [
            'column' => 'domain_type_id',
            'type' => 'int',
        ],
        'entity_property_id' => [
            'column' => 'entity_property_id',
            'type' => 'int',
        ],
        'range_type_id' => [
            'column' => 'range_type_id',
            'type' => 'int',
        ],
        'fingerprint' => [
            'expression' => "domain_type_id || '|' || entity_property_id"
                . " || '|' || range_type_id",
        ],
    ];
    const PRIMARY_KEY = 'id';

    /**
     * Initializes the class properties.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        parent::__construct($db);

        $this->table = self::TABLE;
        $this->fields = self::FIELDS;
        $this->key = self::PRIMARY_KEY;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return Table
     */
    public function getAll(int $limit = 0, int $offset = 0): Table
    {
        $order = ['domain_type_id', 'entity_property_id', 'range_type_id'];
        return $this->findAll([], [], $order, $limit, $offset)
            ->setKeyColumn('fingerprint')->addIndex('id');
    }

    /**
     * @param string $fingerprint
     * @param array $fields
     * @return Map
     */
    public function findByFingerprint(
        string $fingerprint,
        array $fields = []
    ): Map {
        return $this->find([['fingerprint', '=', $fingerprint]], $fields);
    }
    
    /**
     * @param int $domainTypeId
     * @param int $propertyId
     * @param int $rangeTypeId
     * @param array $fields
     * @return Map
     */
    public function findByKeys(
        int $domainTypeId,
        int $propertyId,
        int $rangeTypeId,
        array $fields = []
    ): Map {
        $fingerprint = implode('|', [
            $domainTypeId,
            $propertyId,
            $rangeTypeId,
        ]);

        return $this->findByFingerprint($fingerprint, $fields);
    }
}

// -- End of file
