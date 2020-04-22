<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;

/**
 * Class UserCredential
 * @package Application\Models
 */
class UserCredential extends PersistentModel
{
    const TABLE = 'user_credentials';
    const FIELDS = [
        'user_id' => [
            'column' => 'user_id',
            'type' => 'int',
        ],
        'password_hash' => [
            'column' => 'password_hash',
        ],
        'reset_key' => [
            'column' => 'reset_key',
        ],
    ];
    const PRIMARY_KEY = 'user_id';

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
}

// -- End of file
