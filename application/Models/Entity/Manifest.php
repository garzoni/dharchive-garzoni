<?php

declare(strict_types=1);

namespace Application\Models\Entity;

use Application\Core\Database\Database;
use Application\Core\Type\Map;
use Application\Models\Entity;
use Application\Models\EntityRelation;

/**
 * Class Manifest
 * @package Application\Models\Entity
 */
class Manifest extends Entity
{
    const ENTITY_TYPE = 'dhc:Manifest';

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
        $this->fields['collection_id'] = [
            'expression' => "SUBSTRING(properties->>'label' FROM 1 FOR 4)",
        ];
    }

    /**
     * @param string $code
     * @param array $fields
     * @return Map
     */
    public function findByCode(string $code, array $fields = []): Map
    {
        return $this->find(
            [['code', '=', $code]],
            $fields
        );
    }

    /**
     * @param string $manifestId
     * @param string $sequenceId
     * @param int $agentId
     * @param int|null $sequenceNumber
     * @return bool
     */
    public function addSequence(
        string $manifestId,
        string $sequenceId,
        int $agentId,
        int $sequenceNumber = null
    ): bool {
        $propertyId = $this->getPropertyId('dhc:hasMember');
        $entityRelation = new EntityRelation($this->db);
        return !is_null($entityRelation->create(
            [self::ENTITY_TYPE, $propertyId, CanvasSequence::ENTITY_TYPE],
            $manifestId,
            $propertyId,
            $sequenceId,
            $agentId,
            $sequenceNumber
        ));
    }

    /**
     * @param string $manifestId
     * @return bool
     */
    public function delete(string $manifestId): bool
    {
        $canvas = new Canvas($this->db);
        foreach ($canvas->getList('id', [['manifest_id', '=', $manifestId]])
            as $canvasId) {
            $canvas->delete($canvasId);
        }
        $canvasSequence = new CanvasSequence($this->db);
        foreach ($canvasSequence->getList('id', [['manifest_id', '=', $manifestId]])
            as $canvasSequenceId) {
            $canvasSequence->delete($canvasSequenceId);
        }
        return parent::delete($manifestId);
    }
}

// -- End of file
