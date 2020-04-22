<?php

declare(strict_types=1);

namespace Application\Models\Entity;

use Application\Core\Database\Database;
use Application\Core\Type\Map;
use Application\Core\Type\Json\Schema as JsonSchema;
use Application\Models\Entity;
use Application\Models\Traits\QueryHandler;

use function Application\insertAfter;
use function Application\insertBefore;

/**
 * Class PersonMention
 * @package Application\Models\Entity
 */
class PersonMention extends Entity
{
    use QueryHandler;

    const ENTITY_TYPE = 'grz:PersonMention';

    /**
     * Initializes the class properties.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        parent::__construct($db);

        $this->fields['full_name'] = [
            'expression' => "get_full_name(properties->'name')",
        ];

        $this->fields['unaccented_full_name'] = [
            'expression' => "unaccent(get_full_name(properties->'name'))",
        ];
    }

    public function getDatabaseManager(): Database {
        return $this->db;
    }

    /**
     * @param string $mentionId
     * @return Map
     */
    public function getDetails(string $mentionId): Map
    {
        if (empty($mentionId)) {
            return new Map();
        }
        return new Map($this->getDetailsOfMany([$mentionId])[0] ?? []);
    }

    /**
     * @param array $mentionIds
     * @param bool $allOnEmpty
     * @return array
     */
    public function getDetailsOfMany(array $mentionIds, bool $allOnEmpty = false): array
    {
        if (empty($mentionIds)) {
            $in = '';
            if (!$allOnEmpty) {
                return [];
            }
        } else {
            $in = str_repeat('?,', count($mentionIds) - 1) . '?';
        }

        $query = "
            WITH types AS (
                SELECT id, (prefix || ':' || name) AS qname
                FROM entity_types
            )
            SELECT
                mentions.id, (
                    properties || 
                    jsonb_build_object('tag', tag_properties || jsonb_build_object('id', tag_id)) || 
                    jsonb_build_object('entity', named_entity_properties || jsonb_build_object('id', named_entity_id)) || 
                    jsonb_build_object('fullName', get_full_name(properties->'name'))
                ) properties,
                manifest_id,
                canvas_code,
                page_number,
                target_id,
                target_bbox,
                contract_id
            FROM
                mentions LEFT JOIN types ON (mentions.type_id = types.id)
            WHERE
                types.qname = '" . self::ENTITY_TYPE . "'
        ";

        if (!empty($mentionIds)) {
            $query .= ' AND mentions.id IN (' . $in . ')';
        }

        return $this->db->fetch('table', $query, $mentionIds);
    }

    public function search(array $criteria = [], array $order = [], int $limit = 0, int $offset = 0): array
    {
        $query = 'SELECT r1.id FROM ' . $this->getSearchSelectionClause($criteria);
        if (isset($order[0]['full_name'])) {
            $query .= ' ORDER BY full_name ' . strtoupper($order[0]['full_name']);
        }
        $query .= ' ' . $this->getLimitClause($limit, $offset);
        return $this->db->fetch('list', $query);
    }

    public function getSearchResultCount(array $criteria = []): int
    {
        $query = 'SELECT COUNT(r1.id) FROM ' . $this->getSearchSelectionClause($criteria);
        return (int) $this->db->fetch('scalar', $query);
    }

    public function getNameList(string $pattern = null): array
    {
        return $this->getDistinctValueList('person_mentions', 'full_name', $pattern);
    }

    public function getAgeList(string $pattern = null): array
    {
        return $this->getDistinctValueList('person_mentions', 'age', $pattern);
    }

    public function getGeoOriginTranscriptList(string $pattern = null): array
    {
        return $this->getDistinctValueList('person_mention_geo_origins', 'transcript', $pattern);
    }

    public function getGeoOriginStandardFormList(string $pattern = null): array
    {
        return $this->getDistinctValueList('person_mention_geo_origins', 'standard_form', $pattern);
    }

    public function getProfessionTranscriptList(string $pattern = null): array
    {
        return $this->getDistinctValueList('person_mention_professions', 'transcript', $pattern);
    }

    public function getProfessionStandardFormList(string $pattern = null): array
    {
        return $this->getDistinctValueList('person_mention_professions', 'standard_form', $pattern);
    }

    public function getWorkshopSiteList(string $pattern = null): array
    {
        return $this->getDistinctValueList('person_mention_workshops', 'site', $pattern);
    }

    public function getWorkshopInsigniaList(string $pattern = null): array
    {
        return $this->getDistinctValueList('person_mention_workshops', 'insignia', $pattern);
    }

    public function getDisplaySchema(): JsonSchema
    {
        $buildStringSchema = function($label) {
            return ['type' => 'string', 'label' => ['en' => $label]];
        };

        $schema = new JsonSchema($this->getTypeSchema());
        $schema = new Map($schema->getBasicSchema()->toArray());

        $locationProperties = $schema->get('properties.geoOrigin.properties');
        insertAfter($locationProperties, 'standardForm', [
            'name' => $buildStringSchema('Standard Form (Italian)'),
        ]);
        insertBefore($locationProperties, 'type', [
            'province' => $buildStringSchema('Province'),
            'country' => $buildStringSchema('Country'),
        ]);

        $schema->set('properties.geoOrigin.properties', $locationProperties);
        $schema->set('properties.chargeLocation.properties', $locationProperties);
        $schema->set('properties.residence.properties', $locationProperties);

        $professionProperties = $schema->get('properties.professions.items.properties');
        insertAfter($professionProperties, 'standardForm', [
            'occupation' => $buildStringSchema('Occupation'),
            'category' => $buildStringSchema('Category'),
            'materials' => $buildStringSchema('Materials'),
            'products' => $buildStringSchema('Products'),
        ]);

        $schema->set('properties.professions.items.properties', $professionProperties);

        return new JsonSchema($schema->toJson());
    }

    public function getProfessionSummary(array $mention, $useStandardForms = true): string
    {
        $professions = $mention['professions'] ?? [];
        $propertyName = $useStandardForms ? 'standardForm' : 'transcript';
        $labels = [];
        foreach ($professions as $profession) {
            if (isset($profession[$propertyName])) {
                $labels[] = $profession[$propertyName];
            }
        }
        return implode(', ', $labels);
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
            SELECT id, get_full_name(properties->'name') AS full_name
            FROM entities
            WHERE entity_type_id = 33
                AND is_active = TRUE
        ";

        if ($criteria['full_name']) {
            $clause .= " AND id IN (SELECT id FROM person_mentions WHERE unaccent(full_name) ILIKE unaccent("
                . $this->db->quote('%' . $criteria['full_name'] . '%') . "))";
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
        return [
            'full_name' => $criteria['full_name'] ?? ''
        ];
    }

}

// -- End of file
