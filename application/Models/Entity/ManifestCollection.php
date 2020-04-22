<?php

declare(strict_types=1);

namespace Application\Models\Entity;

use Application\Core\Database\Database;
use Application\Core\Type\Table;
use Application\Models\Entity;
use Application\Models\EntityRelation;
use PDO;

/**
 * Class ManifestCollection
 * @package Application\Models\Entity
 */
class ManifestCollection extends Entity
{
    const ENTITY_TYPE = 'dhc:ManifestCollection';

    /**
     * Initializes the class properties.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        parent::__construct($db);

        $this->fields['code'] = [
            'expression' => "properties->>'code'",
        ];
        $this->fields['label'] = [
            'expression' => "properties->>'label'",
        ];
    }

    /**
     * @param string $collectionId
     * @param string $manifestId
     * @param int $agentId
     * @param int|null $sequenceNumber
     * @return bool
     */
    public function addManifest(
        string $collectionId,
        string $manifestId,
        int $agentId,
        int $sequenceNumber = null
    ): bool {
        $propertyId = $this->getPropertyId('dhc:hasMember');
        $entityRelation = new EntityRelation($this->db);
        return !is_null($entityRelation->create(
            [self::ENTITY_TYPE, $propertyId, Manifest::ENTITY_TYPE],
            $collectionId,
            $propertyId,
            $manifestId,
            $agentId,
            $sequenceNumber
        ));
    }

    /**
     * @param string $collectionId
     * @param int $limit
     * @param int $offset
     * @return Table
     */
    public function getManifests(
        string $collectionId,
        int $limit = 0,
        int $offset = 0
    ): Table {
        $entityRelation = new EntityRelation($this->db);
        $ruleId = $entityRelation->getRelationRuleId(
            [self::ENTITY_TYPE, 'dhc:hasMember', Manifest::ENTITY_TYPE]
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
            [':domain_entity_id', $collectionId, PDO::PARAM_STR],
        ];
        return new Table(
            $this->db->fetch('table', $query, $parameters, true)
        );
    }

    /**
     * @param string $collectionId
     * @return int
     */
    public function getManifestCount(string $collectionId): int {
        $entityRelation = new EntityRelation($this->db);
        $ruleId = $entityRelation->getRelationRuleId(
            [self::ENTITY_TYPE, 'dhc:hasMember', Manifest::ENTITY_TYPE]
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
            [':domain_entity_id', $collectionId, PDO::PARAM_STR],
        ];
        return (int) $this->db->fetch('scalar', $query, $parameters, true);
    }

    /**
     * @param bool $compact
     * @return Table
     */
    public function getStatistics(bool $compact = false): Table {
        $query = "
            WITH documents AS (
                SELECT
                    manifests.properties->>'code' AS document_title,
                    (manifests.properties->'metadata'->>'pageCount')::int AS pages,
                    collections.properties->>'code' AS collection_code,
                    COALESCE(collections.properties->'metadata'->>'type', 'unknown') AS collection_type
                FROM manifests
                    LEFT JOIN collections ON (manifests.collection_id = collections.id)
            )
        ";
        if ($compact) {
            $query .= '
                SELECT
                    collection_type AS type,
                    COUNT(document_title) AS documents,
                    SUM(pages) AS pages
                FROM documents
                GROUP BY collection_type
                ORDER BY type
            ';
        } else {
            $query .= '
                SELECT
                    collection_code AS code,
                    collection_type AS type,
                    COUNT(document_title) AS documents,
                    SUM(pages) AS pages
                FROM documents
                GROUP BY collection_code, collection_type
                ORDER BY type, code
            ';
        }

        return new Table($this->db->fetch('table', $query));
    }
}

// -- End of file
