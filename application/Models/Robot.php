<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Type\Map;

/**
 * Class Robot
 * @package Application\Models
 */
class Robot extends Agent
{
    const TABLE = 'agents_robots';
    const FIELDS = [
        'id' => [
            'column' => 'id',
            'type' => 'int',
        ],
        'botname' => [
            'column' => 'botname',
        ],
        'details' => [
            'column' => 'details',
            'type' => 'jsonb',
        ],
        'registration_time' => [
            'column' => 'registration_time',
            'type' => 'timestamp',
        ],
        'is_active' => [
            'column' => 'is_active',
            'type' => 'boolean',
        ],
        'name' => [
            'expression' => "details->>'name'",
        ],
    ];
    const PRIMARY_KEY = 'id';
    const BOTNAME_REGEX = '/^[a-z]{1}[a-z0-9_]{3,50}$/';

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
     * @param string $botname
     * @param array $fields
     * @return Map
     */
    public function findByBotname(string $botname, array $fields = []): Map
    {
        return $this->find([['botname', '=', $botname]], $fields);
    }

    /**
     * {@inheritdoc}
     */
    public function insert(array $values): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function updateMany(array $criteria, array $values): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMany(array $criteria): bool
    {
        return false;
    }
}

// -- End of file
