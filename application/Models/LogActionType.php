<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;
use Application\Core\Type\Map;
use Application\Core\Type\Table;

/**
 * Class LogActionType
 * @package Application\Models
 */
class LogActionType extends PersistentModel
{
    const TABLE = 'log_action_types';
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
        'qualified_name' => [
            'expression' => "prefix || ':' || name",
        ],
    ];
    const PRIMARY_KEY = 'id';
    const CREATION = 'prov:Generation';
    const UPDATE = 'prov:Revision';
    const DELETION = 'prov:Invalidation';

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
}

// -- End of file
