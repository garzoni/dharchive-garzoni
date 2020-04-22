<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;

/**
 * Class AgentSession
 * @package Application\Models
 */
class AgentSession extends PersistentModel
{
    const TABLE = 'agent_sessions';
    const FIELDS = [
        'id' => [
            'column' => 'id',
        ],
        'data' => [
            'column' => 'data',
        ],
        'start_time' => [
            'column' => 'start_time',
            'type' => 'timestamp',
        ],
        'last_access_time' => [
            'column' => 'last_access_time',
            'type' => 'timestamp',
        ],
        'requests' => [
            'column' => 'requests',
            'type' => 'int',
        ],
        'agent_id' => [
            'column' => 'agent_id',
            'type' => 'int',
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
     * @param string $sessionId
     * @param int $agentId
     * @return bool
     */
    public function assignAgent(string $sessionId, int $agentId): bool
    {
        return $this->updateRecord($sessionId, ['agent_id' => $agentId]);
    }
}

// -- End of file
