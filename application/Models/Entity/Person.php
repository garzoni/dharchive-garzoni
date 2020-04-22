<?php

declare(strict_types=1);

namespace Application\Models\Entity;

use Application\Core\Database\Database;
use Application\Core\Type\Table;
use Application\Models\Entity;
use Application\Models\EntityProperty;
use PDO;

use const Application\REGEX_QNAME;
use const Application\REGEX_UUID;

/**
 * Class Person
 * @package Application\Models\Entity
 */
class Person extends Entity
{
    const ENTITY_TYPE = 'grz:Person';

    /**
     * Initializes the class properties.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        parent::__construct($db);

        $this->fields['name'] = [
            'expression' => "properties->>'name'",
        ];

        $this->fields['unaccented_name'] = [
            'expression' => "unaccent(properties->>'name')",
        ];
    }

    /**
     * @param string $personId
     * @return array
     */
    public function getContracts(string $personId): array
    {
        if (empty($personId)) {
            return [];
        }
        $query = 'SELECT DISTINCT contract_id FROM mentions WHERE named_entity_id = :person_id';
        return $this->db->fetch('list', $query, [[':person_id', $personId, \PDO::PARAM_STR]], true);
    }

    /**
     * @param string $personId
     * @return Table
     */
    public function getRelationships(string $personId): Table
    {
        if (empty($personId)) {
            return new Table();
        }
        $query = '
            SELECT relationship, person1_id, person1_name, person2_id, person2_name
            FROM person_relationships
            WHERE person1_id = :person_id
                OR person2_id = :person_id
        ';
        $parameters = [
            [':person_id', $personId, PDO::PARAM_STR],
        ];
        return new Table($this->db->fetch('table', $query, $parameters, true));
    }

    /**
     * @return Table
     */
    public function getAllRelationships(): Table
    {
        $query = '
            SELECT person1_id, person1_name, relationship, person2_id, person2_name
            FROM person_relationships
            ORDER BY person1_name, person2_name
        ';
        return new Table($this->db->fetch('table', $query));
    }

    /**
     * @param string $personId
     * @return array
     */
    public function getMentionList(string $personId): array
    {
        if (empty($personId)) {
            return [];
        }
        $query = "
            SELECT target_id FROM mention_annotations
            WHERE motivation = 'oa:identifying' AND body_id = :person_id
        ";
        $parameters = [
            [':person_id', $personId, PDO::PARAM_STR],
        ];
        return $this->db->fetch('list', $query, $parameters, true);
    }

    /**
     * @param array $mentionIds
     * @return Table
     */
    public function getMentionTags(array $mentionIds): Table
    {
        if (empty($mentionIds)) {
            return new Table();
        }
        $in = str_repeat('?,', count($mentionIds) - 1) . '?';
        $query = "
            SELECT
                body_id AS id,
                body_type_id AS entity_type_id,
                body_properties AS properties,
                target_id AS mention_id
            FROM mention_annotations
            WHERE motivation = 'oa:tagging'
                AND target_id IN (" . $in . ")
        ";
        return new Table($this->db->fetch('table', $query, $mentionIds));
    }

    /**
     * @param array $mentionIds
     * @return Table
     */
    public function getMentionContexts(array $mentionIds): Table
    {
        if (empty($mentionIds)) {
            return new Table();
        }
        $in = str_repeat('?,', count($mentionIds) - 1) . '?';
        $query = "
            SELECT
                canvas_object_annotations.body_id AS id,
                entity_types.prefix || ':' || entity_types.name AS type,
                canvas_object_annotations.body_properties AS properties,
                canvas_object_annotations.manifest_id,
                canvas_object_annotations.canvas_code,
                canvas_object_annotations.target_id
            FROM canvas_object_annotations, entity_types
            WHERE canvas_object_annotations.body_type_id = entity_types.id
                AND canvas_object_annotations.target_id IN (
                    SELECT target_id
                    FROM canvas_object_annotations
                    WHERE motivation = 'oa:linking'
                        AND body_id IN (" . $in . ")
                )
        ";
        return new Table($this->db->fetch('table', $query, $mentionIds));
    }

    public function search(array $criteria = [], array $order = [], int $limit = 0, int $offset = 0): array
    {
        $selectClause = 'SELECT DISTINCT r1.id';
        $fromClause = 'FROM ' . $this->getSearchSelectionClause($criteria);
        $orderByClause = '';
        if (isset($order[0]['name'])) {
            $selectClause .= ', name';
            $orderByClause .= ' ORDER BY name ' . strtoupper($order[0]['name']);
        }
        $query = 'SELECT id FROM (' . $selectClause . ' ' . $fromClause . ' '
            . $orderByClause . ' ' . $this->getLimitClause($limit, $offset) . ') results';
        return $this->db->fetch('list', $query);
    }

    public function getSearchResultCount(array $criteria = []): int
    {
        $query = 'SELECT COUNT(DISTINCT r1.id) FROM ' . $this->getSearchSelectionClause($criteria);
        return (int) $this->db->fetch('scalar', $query);
    }

    /**
     * @param array $criteria
     * @return string
     */
    protected function getSearchSelectionClause(array $criteria = []): string
    {
        $criteria = $this->parseSearchCriteria($criteria);
        $subclauseId = 1;

        $clause = "
            SELECT id, properties->>'name' AS name
            FROM entities
            WHERE entity_type_id = 27
                AND is_active = TRUE
        ";

        if ($criteria['name']) {
            $clause .= " AND unaccent(properties->>'name') ILIKE unaccent("
                . $this->db->quote('%' . $criteria['name'] . '%') . ')';
        }

        $relations = [];
        foreach ($criteria['relations'] as $rule) {
            $relation = [];
            if ($rule['type']) {
                $relation['relationType'] = $rule['type'];
            }
            if ($rule['person']) {
                $relation['person'] = $rule['person'];
            }
            if ($relation) {
                $relations[] = $relation;
            }
        }
        if ($relations) {
            $clause .= " AND properties->'relationships' @> '"
                . json_encode($relations) . "'";
        }

        $clause = '(' . $clause . ') r' . $subclauseId;

        return $clause;
    }

    /**
     * @param array $criteria
     * @return array
     */
    protected function parseSearchCriteria(array $criteria): array
    {
        $uuidPattern = '/' . REGEX_UUID . '/';
        $qNamePattern = '/' . REGEX_QNAME . '/';

        $relations = [];
        $decodedRelations = isset($criteria['relations']) ? json_decode($criteria['relations'], true) : null;

        if (is_array($decodedRelations)) {
            foreach ($decodedRelations as $relation) {
                $relation = [
                    'type' => preg_match($qNamePattern, $relation['type'])
                        ? $relation['type'] : '',
                    'person' => preg_match($uuidPattern, $relation['person'])
                        ? $relation['person'] : '',
                ];
                $relations[] = $relation;
            }
        }

        return [
            'name' => $criteria['name'] ?? '',
            'relations' => $relations,
        ];
    }

    /**
     * @return array
     */
    public function getRelationTypes(): array
    {
        $relationTypes = [];
        $entityProperties = (new EntityProperty($this->db))->findAll(
                [['is_internal', '=', false]],
                ['qualified_name', 'label'],
                ['label']
            )->toArray();
        foreach ($entityProperties as $property) {
            $label = json_decode($property['label'], true);
            $relationTypes[$property['qualified_name']] = $label;
        }

        return $relationTypes;
    }
}

// -- End of file
