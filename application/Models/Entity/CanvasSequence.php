<?php

declare(strict_types=1);

namespace Application\Models\Entity;

use Application\Core\Database\Database;
use Application\Core\Type\Table;
use Application\Models\Entity;
use Application\Models\EntityRelation;
use PDO;

/**
 * Class CanvasSequence
 * @package Application\Models\Entity
 */
class CanvasSequence extends Entity
{
    const ENTITY_TYPE = 'dhc:CanvasSequence';

    /**
     * Initializes the class properties.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        parent::__construct($db);

        $this->fields['manifest_id'] = [
            'expression' => "properties->>'manifestUuid'",
        ];
        $this->fields['code'] = [
            'expression' => "properties->>'code'",
        ];
    }

    /**
     * @param string $sequenceId
     * @param string $canvasId
     * @param int $agentId
     * @param int|null $sequenceNumber
     * @return bool
     */
    public function addCanvas(
        string $sequenceId,
        string $canvasId,
        int $agentId,
        int $sequenceNumber = null
    ): bool {
        $propertyId = $this->getPropertyId('dhc:hasMember');
        $entityRelation = new EntityRelation($this->db);
        return !is_null($entityRelation->create(
            [self::ENTITY_TYPE, $propertyId, Canvas::ENTITY_TYPE],
            $sequenceId,
            $propertyId,
            $canvasId,
            $agentId,
            $sequenceNumber
        ));
    }

    /**
     * @param string $sequenceId
     * @param int $limit
     * @param int $offset
     * @return Table
     */
    public function getCanvases(
        string $sequenceId,
        int $limit = 0,
        int $offset = 0
    ): Table {
        $entityRelation = new EntityRelation($this->db);
        $ruleId = $entityRelation->getRelationRuleId(
            [self::ENTITY_TYPE, 'dhc:hasMember', Canvas::ENTITY_TYPE]
        );
        if (is_null($ruleId)) {
            return new Table();
        }
        $query = '
            SELECT range_entity_id AS id, properties,
                entity_relations.id AS relation_id, sequence_number
            FROM entity_relations, entities
            WHERE entity_relations.range_entity_id = entities.id
                AND entity_relation_rule_id = :rule_id
                AND domain_entity_id = :domain_entity_id
            ORDER BY sequence_number, relation_id
        ';
        $query .= $this->getLimitClause($limit, $offset);
        $parameters = [
            [':rule_id', $ruleId, PDO::PARAM_INT],
            [':domain_entity_id', $sequenceId, PDO::PARAM_STR],
        ];
        return new Table(
            $this->db->fetch('table', $query, $parameters, true)
        );
    }

    /**
     * @param string $sequenceId
     * @return int
     */
    public function getCanvasCount(string $sequenceId): int {
        $entityRelation = new EntityRelation($this->db);
        $ruleId = $entityRelation->getRelationRuleId(
            [self::ENTITY_TYPE, 'dhc:hasMember', Canvas::ENTITY_TYPE]
        );
        if (is_null($ruleId)) {
            return 0;
        }
        $query = '
            SELECT COUNT(range_entity_id)
            FROM entity_relations
            WHERE entity_relation_rule_id = :rule_id
                AND domain_entity_id = :domain_entity_id
        ';
        $parameters = [
            [':rule_id', $ruleId, PDO::PARAM_INT],
            [':domain_entity_id', $sequenceId, PDO::PARAM_STR],
        ];
        return (int) $this->db->fetch('scalar', $query, $parameters, true);
    }
}

// -- End of file
