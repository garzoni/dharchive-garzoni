<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;
use PDO;

/**
 * Class Agent
 * @package Application\Models
 */
class Agent extends PersistentModel
{
    const TABLE = 'agents';
    const FIELDS = [
        'id' => [
            'column' => 'id',
            'type' => 'int',
        ],
        'type_id' => [
            'column' => 'agent_type_id',
            'type' => 'int',
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
     * @return array
     */
    public function getPermissions(int $agentId): array {
        if (!$agentId) {
            return [];
        }

        $query = '
            SELECT permissions.id, permissions.code
            FROM agents_roles, roles_permissions, permissions
            WHERE agents_roles.role_id = roles_permissions.role_id
                AND roles_permissions.permission_id = permissions.id
                AND agents_roles.agent_id = :agent_id
        ';
        $parameters = [
            [':agent_id', $agentId, PDO::PARAM_INT]
        ];

        $permissions = [];
        foreach ($this->db->fetch('table', $query, $parameters, true) as $permission) {
            $permissions[$permission['code']] = $permission['id'];
        }

        return $permissions;
    }

    /**
     * @param int $agentId
     * @return array
     */
    public function getRoles(int $agentId): array {
        if (!$agentId) {
            return [];
        }

        $query = '
            SELECT roles.id, roles.code
            FROM agents_roles, roles
            WHERE agents_roles.role_id = roles.id
                AND agents_roles.agent_id = :agent_id
        ';
        $parameters = [
            [':agent_id', $agentId, PDO::PARAM_INT]
        ];

        $roles = [];
        foreach ($this->db->fetch('table', $query, $parameters, true) as $role) {
            $roles[$role['code']] = $role['id'];
        }

        return $roles;
    }

    /**
     * @param int $agentId
     * @param int $roleId
     * @return bool
     */
    public function addRole(int $agentId, int $roleId): bool
    {
        $statement = '
            INSERT INTO agents_roles (agent_id, role_id)
            VALUES (:agent_id, :role_id)
        ';

        $parameters = [
            [':agent_id', $agentId, PDO::PARAM_INT],
            [':role_id', $roleId, PDO::PARAM_INT]
        ];

        return $this->executeStatement($statement, $parameters);
    }

    /**
     * @param int $agentId
     * @param int $roleId
     * @return bool
     */
    public function removeRole(int $agentId, int $roleId): bool
    {
        $statement = '
            DELETE FROM agents_roles
            WHERE agent_id = :agent_id
                AND role_id = :role_id
        ';

        $parameters = [
            [':agent_id', $agentId, PDO::PARAM_INT],
            [':role_id', $roleId, PDO::PARAM_INT]
        ];

        return $this->executeStatement($statement, $parameters);
    }

    /**
     * @param mixed $key
     * @return bool
     */
    public function invalidate($key): bool
    {
        return $this->updateRecord($key, ['is_active' => false]);
    }

    /**
     * @return bool
     */
    public function invalidateAll(): bool
    {
        return $this->updateAllRecords(['is_active' => false]);
    }

    /**
     * @param array $criteria
     * @return bool
     */
    public function invalidateMany(array $criteria): bool
    {
        return $this->updateMultipleRecords($criteria, ['is_active' => false]);
    }

    /**
     * @param mixed $key
     * @return bool
     */
    public function restore($key): bool
    {
        return $this->updateRecord($key, ['is_active' => true]);
    }

    /**
     * @return bool
     */
    public function restoreAll(): bool
    {
        return $this->updateAllRecords(['is_active' => true]);
    }

    /**
     * @param array $criteria
     * @return bool
     */
    public function restoreMany(array $criteria): bool
    {
        return $this->updateMultipleRecords($criteria, ['is_active' => true]);
    }
}

// -- End of file
