<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;
use Application\Core\Type\Table;

/**
 * Class XmlNamespace
 * @package Application\Models
 */
class XmlNamespace extends PersistentModel
{
    const TABLE = 'namespaces';
    const FIELDS = [
        'prefix' => [
            'column' => 'prefix',
        ],
        'uri' => [
            'column' => 'uri',
        ],
        'name' => [
            'column' => 'name',
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
        return $this->findAll([], [], ['prefix'], $limit, $offset)
            ->setKeyColumn('prefix');
    }
}

// -- End of file
