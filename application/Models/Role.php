<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;
use PDO;

/**
 * Class Role
 * @package Application\Models
 */
class Role extends PersistentModel
{
    const TABLE = 'roles';
    const FIELDS = [
        'id' => [
            'column' => 'id',
            'type' => 'int',
        ],
        'code' => [
            'column' => 'code',
        ],
    ];
    const PRIMARY_KEY = 'id';
    const ADMIN_ROLE_CODE = 'administrator';

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
     * @param int[] ...$roleIds
     * @return array
     */
    public function getPermissions(int ...$roleIds): array {
        if (empty($roleIds)) {
            return [];
        }
        $query = '
            SELECT permissions.id, permissions.code
            FROM roles_permissions, permissions
            WHERE roles_permissions.permission_id = permissions.id
                AND ' . $this->getSubqueryExpression(
                    'roles_permissions.role_id', 'in', $roleIds, 'int');
        $permissions = [];
        foreach ($this->db->fetch('table', $query) as $permission) {
            $permissions[$permission['code']] = $permission['id'];
        }
        return $permissions;
    }

    /**
     * @param int $roleId
     * @param int $permissionId
     * @return bool
     */
    public function addPermission(int $roleId, int $permissionId): bool
    {
        $statement = '
            INSERT INTO roles_permissions (role_id, permission_id)
            VALUES (:role_id, :permission_id)
        ';

        $parameters = [
            [':role_id', $roleId, PDO::PARAM_INT],
            [':permission_id', $permissionId, PDO::PARAM_INT]
        ];

        return $this->executeStatement($statement, $parameters);
    }

    /**
     * @param int $roleId
     * @param int $permissionId
     * @return bool
     */
    public function removePermission(int $roleId, int $permissionId): bool
    {
        $statement = '
            DELETE FROM roles_permissions
            WHERE role_id = :role_id
                AND permission_id = :permission_id
        ';

        $parameters = [
            [':role_id', $roleId, PDO::PARAM_INT],
            [':permission_id', $permissionId, PDO::PARAM_INT]
        ];

        return $this->executeStatement($statement, $parameters);
    }

    /**
     * @param array $data
     * @return int|null
     */
    public function create(array $data)
    {
        return $this->insertRecord([
            'code' => $data['code'],
        ]);
    }

    /**
     * @param int $roleId
     * @return bool
     */
    public function delete(int $roleId): bool
    {
        return $this->deleteRecord($roleId);
    }
}

// -- End of file
