<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;

/**
 * Class EntityError
 * @package Application\Models
 */
class EntityError extends PersistentModel
{
    const TABLE = 'entity_errors';
    const FIELDS = [
        'entity_id' => [
            'column' => 'entity_id',
            'type' => 'uuid',
        ],
        'error_type_id' => [
            'column' => 'error_type_id',
            'type' => 'int',
        ],
        'error_count' => [
            'column' => 'error_count',
            'type' => 'int',
        ],
        'status' => [
            'column' => 'status',
            'type' => 'boolean',
        ],
        'reviewer_user_id' => [
            'column' => 'reviewer_user_id',
            'type' => 'int',
        ],
        'review_time' => [
            'column' => 'review_time',
            'type' => 'timestamp',
        ],
    ];
    const PRIMARY_KEY = ['entity_id', 'error_type_id'];

    protected $entityTypes;

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

        $this->entityTypes = (new EntityType($this->db))->getIndex();
    }

    public function get(int $entityTypeId, array $criteria = [], int $limit = 0, int $offset = 0): array
    {
        $conditions = $this->getSelectionConditions($entityTypeId, $criteria);

        switch ($entityTypeId) {
            case $this->entityTypes['dhc:Canvas']:
                $query = "
                    SELECT
                        entities.id AS canvas_id,
                        entities.properties->>'code' AS canvas_code,
                        entities.properties->'label'->>'en' AS canvas_label,
                        manifests.id AS document_id,
                        manifests.properties->>'label' AS document_title,
                        entity_errors.error_type_id,
                        error_types.name AS error_type,
                        entity_errors.error_count,
                        entity_errors.status,
                        entity_errors.reviewer_user_id,
                        entity_errors.review_time
                    FROM entity_errors, entities, error_types, manifests
                    WHERE entity_errors.entity_id = entities.id
                        AND entity_errors.error_type_id = error_types.id
                        AND (entities.properties->>'manifestUuid')::uuid = manifests.id
                        AND entities.entity_type_id = " . $entityTypeId
                        . $conditions . "
                    ORDER BY
                        manifests.properties->>'label',
                        (TRIM(LEADING 'Page ' FROM entities.properties->'label'->>'en')::numeric)
                ";
                break;
            case $this->entityTypes['dhc:CanvasSegment']:
                $query = "
                    SELECT
                        entities.id AS segment_id,
                        ('['
                            || (entities.properties->'bbox'->>'x') || ','
                            || (entities.properties->'bbox'->>'y') || ','
                            || (entities.properties->'bbox'->>'w') || ','
                            || (entities.properties->'bbox'->>'h') || ']'
                        ) AS segment_bbox,
                        canvases.properties->>'code' AS canvas_code,
                        canvases.properties->'label'->>'en' AS canvas_label,
                        manifests.id AS document_id,
                        manifests.properties->>'label' AS document_title,
                        entity_errors.error_type_id,
                        error_types.name AS error_type,
                        entity_errors.error_count,
                        entity_errors.status,
                        entity_errors.reviewer_user_id,
                        entity_errors.review_time
                    FROM entity_errors, entities, error_types, canvases, manifests
                    WHERE entity_errors.entity_id = entities.id
                        AND entity_errors.error_type_id = error_types.id
                        AND ((entities.properties->>'canvasCode' = canvases.properties->>'code')
                            AND ((entities.properties->>'manifestUuid')::uuid = canvases.manifest_id))
                        AND (entities.properties->>'manifestUuid')::uuid = manifests.id
                        AND entities.entity_type_id = " . $entityTypeId
                        . $conditions . "
                    ORDER BY
                        manifests.properties->>'label',
                        (TRIM(LEADING 'Page ' FROM canvases.properties->'label'->>'en')::numeric)
                ";
                break;
            case $this->entityTypes['grz:ContractMention']:
                $query = "
                    SELECT
                        entities.id AS contract_id,
                        entity_errors.error_type_id,
                        error_types.name AS error_type,
                        entity_errors.error_count,
                        entity_errors.status,
                        entity_errors.reviewer_user_id,
                        entity_errors.review_time
                    FROM entity_errors, entities, error_types
                    WHERE entity_errors.entity_id = entities.id
                        AND entity_errors.error_type_id = error_types.id
                        AND entities.entity_type_id = " . $entityTypeId
                        . $conditions . "
                    ORDER BY entities.properties->>'date'
                ";
                break;
            default:
                return [];
        }

        $query .= ' ' . $this->getLimitClause($limit, $offset);

        return $this->db->fetch('table', $query);
    }

    public function getCount(int $entityTypeId, array $criteria = []): int
    {
        $conditions = $this->getSelectionConditions($entityTypeId, $criteria);

        $query = '
            SELECT COUNT(*) AS error_count
            FROM entity_errors, entities
            WHERE entity_errors.entity_id = entities.id
                AND entities.entity_type_id = ' . $entityTypeId
                . $conditions;
        return (int) $this->db->fetch('scalar', $query);
    }

    public function getCountByType(int $entityTypeId): array
    {
        $query = '
            SELECT
                entity_errors.error_type_id,
                error_types.name AS error_type,
                COUNT(*) AS error_count
            FROM entity_errors, entities, error_types
            WHERE entity_errors.entity_id = entities.id
                AND entity_errors.error_type_id = error_types.id
                AND entities.entity_type_id = ' . $entityTypeId . '
            GROUP BY
                entity_errors.error_type_id,
                error_types.name
            ORDER BY error_types.name
        ';
        return $this->db->fetch('table', $query);
    }

    public function updateStatus(string $entityId, int $errorTypeId, ?bool $status, int $agentId): bool
    {
        return $this->updateRecord(
            [
                'entity_id' => $entityId,
                'error_type_id' => $errorTypeId
            ],
            [
                'status' => $status,
                'reviewer_user_id' => $agentId,
                'review_time' => date(DATE_ATOM)
            ]
        );
    }

    /**
     * @param int $entityTypeId
     * @param array $criteria
     * @return string
     */
    protected function getSelectionConditions(int $entityTypeId, array $criteria = []): string
    {
        $criteria = [
            'error_types' => $criteria['error_types'] ?? [],
            'error_status' => ($criteria['error_status'] ?? null) ?: 'any',
            'created_after' => $this->parseDate($criteria['created_after'] ?? ''),
            'created_before' => $this->parseDate(($criteria['created_before'] ?? ''), 'P1M'),
        ];

        $clause = '';

        if ($criteria['error_types'] !== 'any') {
            $criteria['error_types'] = is_array($criteria['error_types'])
                ? array_filter($criteria['error_types'], 'intval') : [];
            $clause .= ' AND entity_errors.error_type_id ';
            if ($criteria['error_types']) {
                $clause .= 'IN (' . implode(', ', $criteria['error_types']) . ')';
            } else {
                $clause .= 'IS NULL';
            }
        }

        if ($criteria['error_status'] !== 'any') {
            $condition = ' AND entity_errors.status IS ';
            switch ($criteria['error_status']) {
                case 'unreviewed':
                    $condition .= 'UNKNOWN';
                    break;
                case 'reviewed':
                    $condition .= 'FALSE';
                    break;
                case 'corrected':
                    $condition .= 'TRUE';
                    break;
                default:
                    $condition = '';
            }
            $clause .= $condition;
        }

        if ($entityTypeId === $this->entityTypes['grz:ContractMention']) {
            if ($criteria['created_after']) {
                $clause .= " AND entities.properties->>'date'"
                    . ' >= ' . $this->db->quote($criteria['created_after']);
            }
            if ($criteria['created_before']) {
                $clause .= " AND entities.properties->>'date'"
                    . ' < ' . $this->db->quote($criteria['created_before']);
            }
        }

        return $clause;
    }
}

// -- End of file
