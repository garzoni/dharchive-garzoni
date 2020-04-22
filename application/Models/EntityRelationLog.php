<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;

/**
 * Class EntityRelationLog
 * @package Application\Models
 */
class EntityRelationLog extends PersistentModel
{
    const TABLE = 'entity_relation_log';
    const FIELDS = [
        'id' => [
            'column' => 'id',
            'type' => 'int',
        ],
        'entity_relation_id' => [
            'column' => 'entity_relation_id',
            'type' => 'int',
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
     * @param int $entityRelationId
     * @param int $agentId
     * @return bool
     */
    public function logCreation(int $entityRelationId, int $agentId): bool
    {
        return $this->log($entityRelationId, $agentId, LogActionType::CREATION);
    }

    /**
     * @param int $entityRelationId
     * @param int $agentId
     * @return bool
     */
    public function logUpdate(int $entityRelationId, int $agentId): bool
    {
        return $this->log($entityRelationId, $agentId, LogActionType::UPDATE);
    }

    /**
     * @param int $entityRelationId
     * @param int $agentId
     * @return bool
     */
    public function logDeletion(int $entityRelationId, int $agentId): bool
    {
        return $this->log($entityRelationId, $agentId, LogActionType::DELETION);
    }

    /**
     * @param int $entityRelationId
     * @param int $agentId
     * @param string $actionType
     * @return bool
     */
    public function log(
        int $entityRelationId,
        int $agentId,
        string $actionType
    ): bool {
        $actionTypeId = (new LogActionType($this->db))
            ->findByQName($actionType)->get('id');
        if (is_null($actionTypeId)) {
            return false;
        }
        return $this->insertRecord([
            'entity_relation_id' => $entityRelationId,
            'agent_id' => $agentId,
            'log_action_type_id' => $actionTypeId,
            'action_time' => date(DATE_ATOM)
        ]);
    }
}

// -- End of file
