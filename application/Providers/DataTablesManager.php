<?php

declare(strict_types=1);

namespace Application\Providers;

use Application\Core\Database\Database;
use Application\Core\Foundation\Request;

/**
 * Class DataTablesManager
 * @package Application\Providers
 */
class DataTablesManager
{
    const DEFAULT_SORT_DIRECTION = 'asc';
    const DEFAULT_LIMIT = 10; // records
    const DEFAULT_OFFSET = 0; // records
    const DEFAULT_DRAW_COUNT = 0;

    /**
     * @var Database
     */
    protected $db;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $columns;

    /**
     * @var array
     */
    protected $criteria;

    /**
     * @var array
     */
    protected $order;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @var int
     */
    protected $drawCount;

    /**
     * Initializes the class properties.
     *
     * @param Database $db
     * @param Request $request
     */
    public function __construct(
        Database $db,
        Request $request
    ) {
        $this->db = $db;
        $this->request = $request;
        $this->parseRequest();
    }

    public function parseRequest()
    {
        $this->columns = $this->request->getPost('columns', []);
        $this->criteria = $this->request->getPost('filters', []);

        $this->order = [];
        $order = $this->request->getPost('order', []);
        foreach ($order as $rule) {
            $columnIndex = (int) $rule['column'];
            $columnId = $this->columns[$columnIndex]['name'] ?: $this->columns[$columnIndex]['data'];
            $direction = in_array($rule['dir'], ['asc', 'desc']) ? $rule['dir'] : self::DEFAULT_SORT_DIRECTION;
            $this->order[] = [$columnId => $direction];
        }

        $this->limit = (int) $this->request->getPost('length', self::DEFAULT_LIMIT);
        $this->offset = (int) $this->request->getPost('start', self::DEFAULT_OFFSET);
        $this->drawCount = (int) $this->request->getPost('draw', self::DEFAULT_DRAW_COUNT);
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return array
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }

    /**
     * @return array
     */
    public function getOrder(): array
    {
        return $this->order;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return int
     */
    public function getDrawCount(): int
    {
        return $this->drawCount;
    }
}

// -- End of file
