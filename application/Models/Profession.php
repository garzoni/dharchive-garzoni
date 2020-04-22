<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;
use Application\Models\Traits\QueryHandler;

/**
 * Class Profession
 * @package Application\Models
 */
class Profession extends PersistentModel
{
    use QueryHandler;

    const TABLE = 'professions';

    const FIELDS = [
        'id' => [
            'column' => 'id',
            'type' => 'int',
        ],
        'standard_form' => [
            'column' => 'standard_form',
        ],
        'occupation' => [
            'column' => 'occupation',
        ],
        'material' => [
            'column' => 'material',
            'type' => 'jsonb',
        ],
        'product' => [
            'column' => 'product',
            'type' => 'jsonb',
        ],
        'category_id' => [
            'column' => 'profession_category_id',
            'type' => 'int',
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

    public function getDatabaseManager(): Database {
        return $this->db;
    }

    public function getOccupationList(string $pattern = null): array
    {
        return $this->getDistinctValueList($this->table, 'occupation', $pattern);
    }

    public function getMaterialList(string $pattern = null): array
    {
        $withClause = 'WITH values AS (SELECT jsonb_array_elements_text(material) AS value FROM ' . $this->table . ')';
        return $this->getDistinctValueList('values', 'value', $pattern, $withClause);
    }

    public function getProductList(string $pattern = null): array
    {
        $withClause = 'WITH values AS (SELECT jsonb_array_elements_text(product) AS value FROM ' . $this->table . ')';
        return $this->getDistinctValueList('values', 'value', $pattern, $withClause);
    }
}

// -- End of file
