<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;
use Application\Core\Type\Map;
use Exception;

/**
 * Class EntityRelation
 * @package Application\Models
 */
class EntityRelation extends PersistentModel
{
    const TABLE = 'entity_relations';
    const FIELDS = [
        'id' => [
            'column' => 'id',
            'type' => 'int',
        ],
        'rule_id' => [
            'column' => 'entity_relation_rule_id',
            'type' => 'int',
        ],
        'domain_entity_id' => [
            'column' => 'domain_entity_id',
            'type' => 'uuid',
        ],
        'entity_property_id' => [
            'column' => 'entity_property_id',
            'type' => 'int',
        ],
        'range_entity_id' => [
            'column' => 'range_entity_id',
            'type' => 'uuid',
        ],
        'sequence_number' => [
            'column' => 'sequence_number',
            'type' => 'int',
        ],
        'is_active' => [
            'column' => 'is_active',
            'type' => 'boolean',
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

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            ['id', 'type', 'integer'],
            ['rule_id', 'type', 'integer'],
            ['domain_entity_id', 'type', 'uuid'],
            ['entity_property_id', 'type', 'integer'],
            ['range_entity_id', 'type', 'uuid'],
            ['sequence_number', 'type', 'integer'],
            ['is_active', 'type', 'boolean'],
        ];
    }

    /**
     * @param int|array|string $ruleKey
     * @param string $domainEntityId
     * @param int $entityPropertyId
     * @param string $rangeEntityId
     * @param int $agentId
     * @param int $sequenceNumber
     * @return int|null
     */
    public function create(
        $ruleKey,
        string $domainEntityId,
        int $entityPropertyId,
        string $rangeEntityId,
        int $agentId,
        int $sequenceNumber = null
    ) {
        $ruleId = is_int($ruleKey)
            ? $ruleKey : $this->getRelationRuleId($ruleKey);

        if (is_null($ruleId)) {
            trigger_error('Undefined entity relation rule', E_USER_WARNING);
            return null;
        }

        $this->db->startTransaction();

        try {
            $this->insertRecord([
                'entity_relation_rule_id' => $ruleId,
                'domain_entity_id' => $domainEntityId,
                'entity_property_id' => $entityPropertyId,
                'range_entity_id' => $rangeEntityId,
                'sequence_number' => $sequenceNumber,
            ]);

            $entityRelationId = (int) $this->db->getLastInsertId(
                'entity_relations_id_seq'
            );
            $log = new EntityRelationLog($this->db);
            $log->logCreation($entityRelationId, $agentId);
        }
        catch(Exception $exception) {
            $this->db->abortTransaction();
            trigger_error($exception->getMessage(), E_USER_WARNING);
            return null;
        }

        $this->db->commitTransaction();

        return $entityRelationId;
    }

    /**
     * @param string $entityRelationId
     * @param array $values
     * @param int $agentId
     * @return bool
     */
    public function update(
        string $entityRelationId,
        array $values,
        int $agentId
    ): bool {
        $criteria = $this->getPrimaryKeyCriteria($entityRelationId);
        return $this->updateMany($criteria, $values, $agentId);
    }

    /**
     * @param array $values
     * @param int $agentId
     * @return bool
     */
    public function updateAll(array $values, int $agentId): bool
    {
        return $this->updateMany([], $values, $agentId);
    }

    /**
     * @param array $criteria
     * @param array $values
     * @param int $agentId
     * @return bool
     */
    public function updateMany(
        array $criteria,
        array $values,
        int $agentId
    ): bool {
        $this->db->startTransaction();

        try {
            $entityRelationIds = $this->getList('id', $criteria);
            $this->updateMultipleRecords($criteria, $values);
            $log = new EntityRelationLog($this->db);
            $actionType = (isset($values['is_active'])
                && $values['is_active'] === false)
                ? 'invalidation' : 'revision';
            foreach ($entityRelationIds as $entityRelationId) {
                if ($actionType === 'revision') {
                    $log->logUpdate($entityRelationId, $agentId);
                } else {
                    $log->logDeletion($entityRelationId, $agentId);
                }
            }
        }
        catch(Exception $exception) {
            $this->db->abortTransaction();
            trigger_error($exception->getMessage(), E_USER_WARNING);
            return false;
        }

        $this->db->commitTransaction();

        return true;
    }

    /**
     * @param string $entityRelationId
     * @param int $agentId
     * @return bool
     */
    public function invalidate(string $entityRelationId, int $agentId): bool
    {
        return $this->update(
            $entityRelationId,
            ['is_active' => false],
            $agentId
        );
    }

    /**
     * @param int $agentId
     * @return bool
     */
    public function invalidateAll(int $agentId): bool
    {
        return $this->updateAll(['is_active' => false], $agentId);
    }

    /**
     * @param array $criteria
     * @param int $agentId
     * @return bool
     */
    public function invalidateMany(array $criteria, int $agentId): bool
    {
        return $this->updateMany($criteria, ['is_active' => false], $agentId);
    }

    /**
     * @param string $entityRelationId
     * @param int $agentId
     * @return bool
     */
    public function restore(string $entityRelationId, int $agentId): bool
    {
        return $this->update(
            $entityRelationId,
            ['is_active' => true],
            $agentId
        );
    }

    /**
     * @param int $agentId
     * @return bool
     */
    public function restoreAll(int $agentId): bool
    {
        return $this->updateAll(['is_active' => true], $agentId);
    }

    /**
     * @param array $criteria
     * @param int $agentId
     * @return bool
     */
    public function restoreMany(array $criteria, int $agentId): bool
    {
        return $this->updateMany($criteria, ['is_active' => true], $agentId);
    }

    /**
     * @param string $entityId
     * @return bool
     */
    public function delete(string $entityId): bool
    {
        return $this->deleteRecord($entityId);
    }

    /**
     * @return bool
     */
    public function deleteAll(): bool
    {
        return $this->deleteAllRecords();
    }

    /**
     * @param array $criteria
     * @return bool
     */
    public function deleteMany(array $criteria): bool
    {
        return $this->deleteMultipleRecords($criteria);
    }

    /**
     * @param mixed $key
     * @return Map
     */
    public function getRelationRule($key): Map
    {
        $relationRule = new EntityRelationRule($this->db);

        if (is_int($key)) {
            return $relationRule->fetch($key);
        } elseif (is_string($key)) {
            return $relationRule->findByFingerprint($key);
        } elseif (is_array($key)) {
            $entity = new Entity($this->db);
            $domainTypeId = is_string($key[0])
                ? $entity->getTypeId($key[0]) : $key[0];
            $propertyId = is_string($key[1])
                ? $entity->getPropertyId($key[1]) : $key[1];
            $rangeTypeId = is_string($key[2])
                ? $entity->getTypeId($key[2]) : $key[2];
            return $relationRule->findByKeys(
                (int) $domainTypeId,
                (int) $propertyId,
                (int) $rangeTypeId
            );
        } else {
            return new Map();
        }
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function getRelationRuleFingerprint(int $id)
    {
        return $this->getRelationRule($id)->get('fingerprint');
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getRelationRuleId($key)
    {
        return $this->getRelationRule($key)->get('id');
    }
}

// -- End of file
