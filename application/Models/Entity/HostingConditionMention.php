<?php

declare(strict_types=1);

namespace Application\Models\Entity;

use Application\Core\Database\Database;
use Application\Models\Entity;
use Application\Models\Traits\QueryHandler;

/**
 * Class HostingConditionMention
 * @package Application\Models\Entity
 */
class HostingConditionMention extends Entity
{
    use QueryHandler;

    const ENTITY_TYPE = 'grz:HostingConditionMention';

    /**
     * Initializes the class properties.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    public function getDatabaseManager(): Database {
        return $this->db;
    }

    public function getClothingTypeList(string $pattern = null): array
    {
        return $this->getDistinctValueList('hosting_condition_mentions', 'clothing_type', $pattern);
    }
}

// -- End of file
