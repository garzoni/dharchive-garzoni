<?php

declare(strict_types=1);

namespace Application\Models\Entity;

use Application\Models\Entity;
use Application\Models\EntityRelationRule;
use PDO;

use const Application\REGEX_LANG;
use const Application\VALUE_LIST_LIMIT;

/**
 * Class EntityList
 * @package Application\Models\Entity
 */
class EntityList extends Entity
{
    const ENTITY_TYPE = 'dhc:List';
    const KEY_FIELDS = [
        'id' => 'entities.id',
        'relative_id' => "properties->>'id'",
        'qualified_name' => "properties->>'qualifiedName'",
    ];
    const LABEL_FIELDS = [
        'name' => "properties->>'name'",
        'label' => "properties->>'label'",
        'preferred_label' => "properties->'labels'->>'preferred'",
    ];

    /**
     * @param string $listQName
     * @param string $keyProperty
     * @param string $labelProperty
     * @param string|null $label
     * @param string|null $language
     * @param array $keys
     * @param int $limit
     * @return array
     */
    public function getEntities(
        string $listQName,
        string $keyProperty,
        string $labelProperty,
        string $label = null,
        string $language = null,
        array $keys = [],
        int $limit = VALUE_LIST_LIMIT
    ): array {
        if (!array_key_exists($keyProperty, static::KEY_FIELDS)
            || !array_key_exists($labelProperty, static::LABEL_FIELDS)) {
            return [];
        }
        $keyField = static::KEY_FIELDS[$keyProperty];
        $labelField = static::LABEL_FIELDS[$labelProperty];
        $query = 'SELECT ' . $keyField . ' AS ' . $keyProperty . ', '
            . $labelField . 'AS ' . $labelProperty;

        if (!empty($language) && preg_match('/' . REGEX_LANG . '/', $language)) {
            $query .= ', ' . str_replace('->>', '->', $labelField)
                . "->>'" . $language . "' AS localized_label";
        }

        $entity = new Entity($this->db);
        $entityTypeId = $entity->getTypeId($listQName);
        if (!is_null($entityTypeId)) {
            $query .= ' FROM entities WHERE entity_type_id = :entity_type_id';
            $parameters = [
                [':entity_type_id', $entityTypeId, PDO::PARAM_INT],
            ];
        } else {
            $list = $this->findByQName($listQName);
            $entityRelationRule = new EntityRelationRule($this->db);
            $ruleIds = $entityRelationRule->getList('id',
                [
                    ['domain_type_id', '=', $this->getTypeId()],
                    ['entity_property_id', '=', $this->getPropertyId('dhc:hasMember')],
                ]
            );
            if ($list->isEmpty() || empty($ruleIds)) {
                return [];
            }

            $query .= '
                FROM entity_relations, entities
                WHERE entity_relations.range_entity_id = entities.id
                    AND entity_relation_rule_id IN (' . implode(', ', $ruleIds) . ')
                    AND domain_entity_id = :domain_entity_id
            ';
            $parameters = [
                [':domain_entity_id', $list->get('id'), PDO::PARAM_STR],
            ];
        }

        if (!empty($keys)) {
            $keyFieldDataType = ($keyProperty === 'id') ? 'uuid' : 'text';
            $query .= ' AND ' . $this->getSubqueryExpression(
                $keyField, 'in', $keys, $keyFieldDataType
            );
        } elseif (!empty($label)) {
            $query .= ' AND unaccent(' . $labelField . ') ILIKE unaccent(:label) ';
            $parameters[] = [':label', '%' . $label . '%', PDO::PARAM_STR];
        }

        $query .= ' ORDER BY ' . $labelProperty;
        if ($limit > 0) {
            $query .= ' LIMIT ' . $limit;
        }

        return $this->db->fetch('table', $query, $parameters, true);
    }

    public function getAssortedEntities(
        array $lists,
        string $language = null
    ): array {
        $values = [];
        foreach ($lists as $list) {
            if (!is_array($list) || (count($list) !== 3)) {
                throw new InvalidArgumentException('Invalid list structure');
            }
            list($listQName, $keyProperty, $labelProperty) = $list;
            if (!is_string($listQName) || !is_string($keyProperty) || !is_string($labelProperty)) {
                throw new InvalidArgumentException('Invalid list parameters');
            }
            foreach ($this->getEntities($listQName, $keyProperty, $labelProperty, null, $language, [], 0) as $entity) {
                $values[$entity[$keyProperty]] = $this->getLocalizedLabel($entity, $labelProperty, $language);
            }
        }
        asort($values);
        return $values;
    }

    public function getLocalizedLabel(
        array $entity,
        string $labelProperty,
        string $fallbackLanguage
    ): string {
        $label = $entity['localized_label'] ?? '';
        if (empty($label)) {
            $labelValues = json_decode($entity[$labelProperty], true);
            if ((json_last_error() === JSON_ERROR_NONE)
                && is_array($labelValues)
                && array_key_exists($fallbackLanguage, $labelValues)) {
                $label = $labelValues[$fallbackLanguage];
            }
        }
        return $label ?: $entity[$labelProperty];
    }
}

// -- End of file
