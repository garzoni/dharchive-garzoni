<?php

declare(strict_types=1);

namespace Application\Models\Entity;

use Application\Core\Database\Database;
use Application\Models\Entity;
use Application\Models\Traits\QueryHandler;

/**
 * Class FinancialConditionMention
 * @package Application\Models\Entity
 */
class FinancialConditionMention extends Entity
{
    use QueryHandler;

    const ENTITY_TYPE = 'grz:FinancialConditionMention';

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

    public function getMoneyInformationList(string $pattern = null): array
    {
        return $this->getDistinctValueList('financial_condition_mentions', 'money_information', $pattern);
    }
}

// -- End of file
