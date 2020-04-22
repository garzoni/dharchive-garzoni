<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;
use Application\Core\Type\Map;
use Application\Core\Type\Table;

/**
 * Class EntityType
 * @package Application\Models
 */
class EntityType extends PersistentModel
{
    const TABLE = 'entity_types';
    const FIELDS = [
        'id' => [
            'column' => 'id',
            'type' => 'int',
        ],
        'prefix' => [
            'column' => 'prefix',
        ],
        'name' => [
            'column' => 'name',
        ],
        'details' => [
            'column' => 'details',
            'type' => 'jsonb',
        ],
        'is_internal' => [
            'column' => 'is_internal',
            'type' => 'boolean',
        ],
        'qualified_name' => [
            'expression' => "prefix || ':' || name",
        ],
        'label' => [
            'expression' => "details->>'label'",
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
        return $this->findAll([], [], ['qualified_name'], $limit, $offset)
            ->setKeyColumn('qualified_name')->addIndex('id');
    }

    /**
     * @param string $qName
     * @param array $fields
     * @return Map
     */
    public function findByQName(string $qName, array $fields = []): Map
    {
        return $this->find([['qualified_name', '=', $qName]], $fields);
    }

    public function getIndex(): array
    {
        $entityTypes = [];
        foreach ($this->getAll()->toArray() as $type) {
            $entityTypes[$type['qualified_name']] = $type['id'];
        }
        return $entityTypes;
    }
}

// -- End of file
