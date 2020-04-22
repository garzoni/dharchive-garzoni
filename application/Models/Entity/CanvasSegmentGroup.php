<?php

declare(strict_types=1);

namespace Application\Models\Entity;

use Application\Core\Type\Table;
use Application\Models\Entity;
use Application\Models\EntityRelation;
use \PDO;

/**
 * Class CanvasSegmentGroup
 * @package Application\Models\Entity
 */
class CanvasSegmentGroup extends Entity
{
    const ENTITY_TYPE = 'dhc:CanvasSegmentGroup';

    /**
     * @param string $segmentGroupId
     * @param string $segmentId
     * @param int $agentId
     * @param int|null $sequenceNumber
     * @return bool
*/
    public function addMember(
        string $segmentGroupId,
        string $segmentId,
        int $agentId,
        int $sequenceNumber = null
    ): bool {
        $propertyId = $this->getPropertyId('dhc:hasMember');
        $entityRelation = new EntityRelation($this->db);
        return !is_null($entityRelation->create(
            [self::ENTITY_TYPE, $propertyId, CanvasSegment::ENTITY_TYPE],
            $segmentGroupId,
            $propertyId,
            $segmentId,
            $agentId,
            $sequenceNumber
        ));
    }

    /**
     * @param string $segmentGroupId
     * @return Table
     */
    public function getMembers(string $segmentGroupId): Table
    {
        $entityRelation = new EntityRelation($this->db);
        $ruleId = $entityRelation->getRelationRuleId(
            [self::ENTITY_TYPE, 'dhc:hasMember', CanvasSegment::ENTITY_TYPE]
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
        $parameters = [
            [':rule_id', $ruleId, PDO::PARAM_INT],
            [':domain_entity_id', $segmentGroupId, PDO::PARAM_STR],
        ];
        return new Table(
            $this->db->fetch('table', $query, $parameters, true)
        );
    }

    /**
     * @param array $segmentGroupIds
     * @return Table
     */
    public function getMemberTable(array $segmentGroupIds): Table
    {
        $entityRelation = new EntityRelation($this->db);
        $ruleId = $entityRelation->getRelationRuleId(
            [self::ENTITY_TYPE, 'dhc:hasMember', CanvasSegment::ENTITY_TYPE]
        );
        if (is_null($ruleId) || empty($segmentGroupIds)) {
            return new Table();
        }
        return $entityRelation->findAll(
            [
                ['rule_id', '=', $ruleId],
                ['domain_entity_id', 'in', $segmentGroupIds],
            ],
            ['domain_entity_id', 'range_entity_id']
        );
    }
}

// -- End of file
