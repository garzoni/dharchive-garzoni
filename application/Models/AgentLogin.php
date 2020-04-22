<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;
use DateTime;

/**
 * Class AgentLogin
 * @package Application\Models
 */
class AgentLogin extends PersistentModel
{
    const TABLE = 'agent_logins';
    const FIELDS = [
        'id' => [
            'column' => 'id',
            'type' => 'int',
        ],
        'agent_id' => [
            'column' => 'agent_id',
            'type' => 'int',
        ],
        'ip_address' => [
            'column' => 'ip_address',
            'type' => 'inet',
        ],
        'access_time' => [
            'column' => 'access_time',
            'type' => 'timestamp',
        ],
        'is_successful' => [
            'column' => 'is_successful',
            'type' => 'boolean',
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
     * @param int $agentId
     * @param string $ipAddress
     * @param bool $isSuccessful
     * @return bool
     */
    public function log(
        int $agentId,
        string $ipAddress,
        bool $isSuccessful
    ): bool {
        return $this->insertRecord([
            'agent_id' => $agentId,
            'ip_address' => $ipAddress,
            'access_time' => date(DateTime::ATOM),
            'is_successful' => $isSuccessful,
        ]);
    }
}

// -- End of file
