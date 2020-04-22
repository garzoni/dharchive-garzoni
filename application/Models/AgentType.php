<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;

/**
 * Class AgentType
 * @package Application\Models
 */
class AgentType extends PersistentModel
{
    const TABLE = 'agent_types';
    const FIELDS = [
        'id' => [
            'column' => 'id',
            'type' => 'int',
        ],
        'prefix' => [
            'column' => 'prefix',
        ],
        'name' => [
            'column' => 'name',
        ],
        'qualified_name' => [
            'expression' => "prefix || ':' || name",
        ],
    ];
    const PRIMARY_KEY = 'id';

    const PERSON = 'prov:Person';
    const ROBOT = 'prov:SoftwareAgent';

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
