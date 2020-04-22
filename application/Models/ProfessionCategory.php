<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;
use Application\Core\Type\Table;
use PDO;

/**
 * Class ProfessionCategory
 * @package Application\Models
 */
class ProfessionCategory extends PersistentModel
{
    const TABLE = 'profession_categories';

    const FIELDS = [
        'id' => [
            'column' => 'id',
            'type' => 'int',
        ],
        'parent_id' => [
            'column' => 'parent_id',
            'type' => 'int',
        ],
        'label' => [
            'column' => 'label',
        ],
        'description' => [
            'column' => 'description',
        ],
        'sector' => [
            'column' => 'sector',
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

    public function getChildren(int $parentId = null): Table {
        $categoryIds = $this->getChildIds($parentId);
        if (empty($categoryIds)) {
            return new Table();
        }
        return $this->findAll([['id', 'in', $categoryIds]], [], ['label']);
    }

    public function getChildrenOfMany(array $parentIds): Table {
        $categoryIds = $this->getChildIdsOfMany($parentIds);
        if (empty($categoryIds)) {
            return new Table();
        }
        return $this->findAll([['id', 'in', $categoryIds]], [], ['label']);
    }

    public function getChildIds(int $parentId = null): array {
        return $this->getList('id', [['parent_id', '=', $parentId]], false);
    }

    public function getChildIdsOfMany(array $parentIds): array {
        $parentIds = array_values(array_filter($parentIds, 'is_int'));
        return $this->getList('id', [['parent_id', 'in', $parentIds]], false);
    }

    public function getDescendantIds(int $ancestorCategoryId): array {
        $query = $this->getTreeCte();
        $query .= '
            SELECT id FROM category_tree
            WHERE :ancestor_category_id = ANY(ancestors)
        ';

        $parameters = [
            [':ancestor_category_id', $ancestorCategoryId, PDO::PARAM_INT]
        ];

        return $this->db->fetch('list', $query, $parameters, true);
    }

    public function getLabels(
        array $categoryIds = [],
        bool $useDescription = false,
        string $separator = ' > '
    ): array {
        $categoryIds = array_values(array_filter($categoryIds, 'is_int'));
        $query = $this->getTreeCte($useDescription);
        $query .= 'SELECT id, ARRAY_TO_STRING(name_path, ' . $this->db->quote($separator) . ') AS label'
            . ' FROM category_tree';
        if ($categoryIds) {
            $in = str_repeat('?,', count($categoryIds) - 1) . '?';
            $query .= ' WHERE id IN (' . $in . ')';
        }
        return array_column($this->db->fetch('table', $query, $categoryIds), 'label', 'id');
    }

    protected function getTreeCte(bool $useDescription = false): string
    {
        $label = $useDescription ? 'COALESCE(description, label)' : 'label';
        return "
            WITH RECURSIVE category_tree (id, level, name, name_path, ancestors) AS (
                SELECT id, 0, $label, ARRAY[$label], ARRAY[]::INTEGER[]
                FROM profession_categories WHERE parent_id IS NULL
            
                UNION ALL
            
                SELECT
                    t1.id,
                    t2.level + 1,
                    $label,
                    ARRAY_APPEND(t2.name_path, $label),
                    ARRAY_APPEND(t2.ancestors, t1.parent_id)
                FROM profession_categories t1, category_tree t2
                WHERE t1.parent_id = t2.id
            )
        ";
    }
}

// -- End of file
