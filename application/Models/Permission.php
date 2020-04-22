<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;

/**
 * Class Permission
 * @package Application\Models
 */
class Permission extends PersistentModel
{
    const TABLE = 'permissions';
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
     * @param int $permissionId
     * @return bool
     */
    public function delete(int $permissionId): bool
    {
        return $this->deleteRecord($permissionId);
    }
}

// -- End of file
