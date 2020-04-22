<?php

declare(strict_types=1);

namespace Application\Models\Entity;

use Application\Core\Database\Database;
use Application\Models\Entity;

/**
 * Class Parish
 * @package Application\Models\Entity
 */
class Parish extends Entity
{
    const ENTITY_TYPE = 'grz:Parish';

    /**
     * Initializes the class properties.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        parent::__construct($db);

        $this->fields['name'] = [
            'expression' => "properties->>'name'",
        ];

        $this->fields['unaccented_name'] = [
            'expression' => "unaccent(properties->>'name')",
        ];

        $this->fields['sestiere'] = [
            'expression' => "properties->>'sestiere'",
        ];

        $this->fields['qualified_name'] = [
            'expression' => "properties->>'qualifiedName'",
        ];
    }
}

// -- End of file
