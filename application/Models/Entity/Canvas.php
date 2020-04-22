<?php

declare(strict_types=1);

namespace Application\Models\Entity;

use Application\Core\Database\Database;
use Application\Core\Type\Map;
use Application\Core\Type\Table;
use Application\Models\Entity;
use Application\Models\EntityRelation;
use Application\Models\EntityRelationRule;

/**
 * Class Canvas
 * @package Application\Models\Entity
 */
class Canvas extends Entity
{
    const ENTITY_TYPE = 'dhc:Canvas';
    const OBJECT_TYPES = [
        'dhc:CanvasSegment',
        'dhc:CanvasSegmentGroup',
    ];

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
        $this->fields['label'] = [
            'expression' => "properties->>'label'",
        ];
        $this->fields['thumbnail'] = [
            'expression' => "properties->>'thumbnail'",
        ];
    }

    /**
     * @param string $manifestId
     * @param string $canvasCode
     * @param array $fields
     * @return Map
     */
    public function findByManifestKeys(
        string $manifestId,
        string $canvasCode,
        array $fields = []
    ): Map {
        return $this->find(
            [['manifest_id', '=', $manifestId], ['code', '=', $canvasCode]],
            $fields
        );
    }

    /**
     * @param string $manifestId
     * @param string $canvasCode
     * @param bool $flagAnnotated
     * @return Table
     */
    public function getObjects(
        string $manifestId,
        string $canvasCode,
        bool $flagAnnotated = false
    ): Table {
        $entity = new Entity($this->db);
        $entityTypeIds = [];
        foreach (static::OBJECT_TYPES as $entityType) {
            $entityTypeId = $this->getTypeId($entityType);
            if (!empty($entityTypeId)) {
                $entityTypeIds[] = $entityTypeId;
            }
        }
        $objects = $entity->findAll(
            [
                ['type_id', 'in', $entityTypeIds],
                ['manifest_id', '=', $manifestId],
                ['canvas_code', '=', $canvasCode],
                ['is_active', '=', true],
            ],
            ['id', 'type_id', 'properties']
        );

        if ($flagAnnotated) {
            $objects->addColumn('is_annotated', [
                'type' => 'boolean',
                'not_null' => true,
                'default' => false,
            ]);
            $objectIds = array_column($objects->toArray(), 'id');
            $entityRelationRule = new EntityRelationRule($this->db);
            $ruleIds = $entityRelationRule->getList('id', [
                ['domain_type_id', '=', $this->getTypeId(Annotation::ENTITY_TYPE)],
                ['entity_property_id', '=', $this->getPropertyId('dhc:hasTarget')],
            ]);
            if (!empty($ruleIds) && !empty($objectIds)) {
                $entityRelation = new EntityRelation($this->db);
                $annotatedObjectIds = array_flip($entityRelation->getList(
                    'range_entity_id',
                    [
                        ['rule_id', 'in', $ruleIds],
                        ['range_entity_id', 'in', $objectIds],
                    ]
                ));
                foreach ($objects->toArray() as $rowId => $object) {
                    if (array_key_exists($object['id'], $annotatedObjectIds)) {
                        $objects->updateField($rowId, 'is_annotated', true);
                    }
                }
            }
        }

        return $objects;
    }

    /**
     * @param string $canvasId
     * @return bool
     */
    public function delete(string $canvasId): bool
    {
        $canvas = $this->fetch($canvasId, ['manifest_id', 'code']);
        $canvasObjects = $this->getObjects(
            $canvas->get('manifest_id'),
            $canvas->get('code')
        );
        $entityIds = [];
        if (!$canvasObjects->isEmpty()) {
            $entityIds = $canvasObjects->getList('id');
        }
        $entityIds[] = $canvasId;
        $motivations = ['sc:painting', 'oa:linking'];
        $query = "
            SELECT id, body_id, properties->>'motivation' AS motivation
            FROM annotations
            WHERE " . $this->getSubqueryExpression(
                'target_id', 'in', $entityIds, 'uuid'
            );
        foreach ($this->db->fetch('table', $query) as $annotation) {
            $entityIds[] = $annotation['id'];
            if (in_array($annotation['motivation'], $motivations)) {
                $entityIds[] = $annotation['body_id'];
            }
        }

        return parent::deleteMany([['id', 'in', $entityIds]]);
    }
}

// -- End of file
