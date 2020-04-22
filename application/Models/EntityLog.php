<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;

/**
 * Class EntityLog
 * @package Application\Models
 */
class EntityLog extends PersistentModel
{
    const TABLE = 'entity_log';
    const FIELDS = [
        'id' => [
            'column' => 'id',
            'type' => 'int',
        ],
        'entity_id' => [
            'column' => 'entity_id',
            'type' => 'uuid',
        ],
        'agent_id' => [
            'column' => 'agent_id',
            'type' => 'int',
        ],
        'action_type_id' => [
            'column' => 'log_action_type_id',
            'type' => 'int',
        ],
        'timestamp' => [
            'column' => 'action_time',
            'type' => 'timestamp',
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
     * @param string $entityId
     * @param int $agentId
     * @return bool
     */
    public function logCreation(string $entityId, int $agentId): bool
    {
        return $this->log($entityId, $agentId, LogActionType::CREATION);
    }

    /**
     * @param string $entityId
     * @param int $agentId
     * @return bool
     */
    public function logUpdate(string $entityId, int $agentId): bool
    {
        return $this->log($entityId, $agentId, LogActionType::UPDATE);
    }

    /**
     * @param string $entityId
     * @param int $agentId
     * @return bool
     */
    public function logDeletion(string $entityId, int $agentId): bool
    {
        return $this->log($entityId, $agentId, LogActionType::DELETION);
    }

    /**
     * @param string $entityId
     * @param int $agentId
     * @param string $actionType
     * @return bool
     */
    public function log(
        string $entityId,
        int $agentId,
        string $actionType
    ): bool {
        $actionTypeId = (new LogActionType($this->db))
            ->findByQName($actionType)->get('id');
        if (is_null($actionTypeId)) {
            return false;
        }
        return $this->insertRecord([
            'entity_id' => $entityId,
            'agent_id' => $agentId,
            'log_action_type_id' => $actionTypeId,
            'action_time' => date(DATE_ATOM)
        ]);
    }
}

// -- End of file
