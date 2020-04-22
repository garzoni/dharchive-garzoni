<?php

declare(strict_types=1);

namespace Application\Models\Entity;

use Application\Core\Database\Database;
use Application\Models\Entity;

/**
 * Class Image
 * @package Application\Models\Entity
 */
class Transcription extends Entity
{
    const ENTITY_TYPE = 'dhc:Transcription';

    /**
     * Initializes the class properties.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        parent::__construct($db);
    }
}

// -- End of file
