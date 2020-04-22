<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;
use Application\Models\Traits\QueryHandler;

/**
 * Class Location
 * @package Application\Models
 */
class Location extends PersistentModel
{
    use QueryHandler;

    const TABLE = 'locations';

    const FIELDS = [
        'id' => [
            'column' => 'id',
            'type' => 'int',
        ],
        'standard_form' => [
            'column' => 'standard_form',
        ],
        'name' => [
            'column' => 'name',
        ],
        'type' => [
            'column' => 'type',
        ],
        'province' => [
            'column' => 'province',
        ],
        'country' => [
            'column' => 'country',
        ],
        'coordinates' => [
            'column' => 'coordinates',
            'type' => 'point',
        ],
        'geonames_id' => [
            'column' => 'geonames_id',
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

    public function getNameList(string $pattern = null): array
    {
        return $this->getDistinctValueList($this->table, 'name', $pattern);
    }

    public function getTypeList(string $pattern = null): array
    {
        return $this->getDistinctValueList($this->table, 'type', $pattern);
    }

    public function getProvinceList(string $pattern = null): array
    {
        return $this->getDistinctValueList($this->table, 'province', $pattern);
    }

    public function getCountryList(string $pattern = null): array
    {
        return $this->getDistinctValueList($this->table, 'country', $pattern);
    }
}

// -- End of file
