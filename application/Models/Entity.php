<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Foundation\PersistentModel;
use Application\Core\Text\TextGenerator;
use Application\Core\Text\Translator;
use Application\Core\Type\Map;
use Application\Core\Type\Table;

use Exception;
use InvalidArgumentException;
use JsonSchema\Validator as SchemaValidator;

/**
 * Class Entity
 * @package Application\Models
 */
class Entity extends PersistentModel
{
    const TABLE = 'entities';
    const FIELDS = [
        'id' => [
            'column' => 'id',
            'type' => 'uuid',
        ],
        'type_id' => [
            'column' => 'entity_type_id',
            'type' => 'int',
        ],
        'properties' => [
            'column' => 'properties',
            'type' => 'jsonb',
        ],
        'is_active' => [
            'column' => 'is_active',
            'type' => 'boolean',
        ],
        'qualified_name' => [
            'expression' => "properties->>'qualifiedName'",
        ],
        'relative_id' => [
            'expression' => "properties->>'id'",
        ],
        'manifest_id' => [
            'expression' => "properties->>'manifestUuid'",
        ],
        'canvas_code' => [
            'expression' => "properties->>'canvasCode'",
        ],
        'name' => [
            'expression' => "properties->>'name'",
        ],
    ];
    const PRIMARY_KEY = 'id';
    const ENTITY_TYPE = null;

    /**
     * @var array
     */
    private $propertyValidationErrors = [];

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
            ['id', 'type', 'uuid'],
            ['type_id', 'type', 'integer'],
            ['is_active', 'type', 'boolean'],
            ['properties', 'type', 'text'],
            ['properties', 'format', 'json'],
        ];
    }

    /**
     * @return array
     */
    public function getPropertyValidationErrors(): array
    {
        return $this->propertyValidationErrors;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecordCount(array $criteria = []): int
    {
        $this->adjustCriteria($criteria);
        return parent::getRecordCount($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function find(array $criteria = [], array $fields = []): Map
    {
        $this->adjustCriteria($criteria);
        return parent::find($criteria, $fields);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(
        array $criteria = [],
        array $fields = [],
        array $order = [],
        int $limit = 0,
        int $offset = 0
    ): Table {
        $this->adjustCriteria($criteria);
        return parent::findAll($criteria, $fields, $order, $limit, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        string $field,
        array $criteria = [],
        bool $sortAscending = true,
        int $limit = 0,
        int $offset = 0
    ): array {
        $this->adjustCriteria($criteria);
        return parent::getList(
            $field,
            $criteria,
            $sortAscending,
            $limit,
            $offset
        );
    }

    /**
     * @param string $qName
     * @param array $fields
     * @return Map
     */
    public function findByQName(string $qName, array $fields = []): Map
    {
        return $this->find([['qualified_name', '=', $qName]], $fields);
    }

    /**
     * @param int $typeId
     * @param string $relativeId
     * @param array $fields
     * @return Map
     */
    public function findByRelativeId(
        int $typeId,
        string $relativeId,
        array $fields = []
    ): Map {
        return $this->find(
            [['type_id', '=', $typeId], ['relative_id', '=', $relativeId]],
            $fields
        );
    }

    /**
     * @param int $entityTypeId
     * @param string $properties
     * @return bool
     */
    public function isValid(int $entityTypeId, string $properties): bool {
        $schema = $this->getTypeSchema($entityTypeId);
        return $this->isValidJsonData($schema, $properties);
    }

    /**
     * @param string $properties
     * @param int $agentId
     * @param string $entityId
     * @param string|null $entityType
     * @return string|null
     */
    public function create(
        string $properties,
        int $agentId,
        string $entityId = null,
        string $entityType = null
    ) {
        if (!is_null(static::ENTITY_TYPE) && !is_null($entityType)
            && (static::ENTITY_TYPE !== $entityType)) {
            trigger_error('Ambiguous entity type', E_USER_WARNING);
            return null;
        }
        $entityTypeId = $this->getTypeId($entityType);
        if (is_null($entityTypeId)) {
            trigger_error('Unknown entity type', E_USER_WARNING);
            return null;
        }

        $schema = $this->getTypeSchema($entityTypeId);

        if (!$this->isValidJsonData($schema, $properties)) {
            trigger_error('Invalid entity properties', E_USER_WARNING);
            return null;
        }

        if (empty($entityId)) {
            $entityId = (new TextGenerator())->getUuid4();
        }

        $this->db->startTransaction();

        try {
            $this->insertRecord([
                'id' => $entityId,
                'entity_type_id' => $entityTypeId,
                'properties' => $properties,
            ]);

            $log = new EntityLog($this->db);
            $log->logCreation($entityId, $agentId);
        }
        catch(Exception $exception) {
            $this->db->abortTransaction();
            trigger_error($exception->getMessage(), E_USER_WARNING);
            return null;
        }

        $this->db->commitTransaction();

        return $entityId;
    }

    /**
     * @param string $entityId
     * @param array $values
     * @param int $agentId
     * @return bool
     */
    public function update(string $entityId, array $values, int $agentId): bool
    {
        $criteria = $this->getPrimaryKeyCriteria($entityId);
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
        $this->adjustCriteria($criteria);
        $entities = $this->findAll($criteria, ['id', 'type_id']);
        if (isset($values['entity_type_id']) || isset($values['type_id'])) {
            trigger_error('Entity type cannot be changed', E_USER_WARNING);
            return false;
        } elseif (array_key_exists('properties', $values)) {
            $entityTypeIds = !is_null(static::ENTITY_TYPE)
                ? [$this->getTypeId()]
                : array_unique($entities->getList('type_id'));
            foreach ($entityTypeIds as $entityTypeId) {
                $schema = $this->getTypeSchema($entityTypeId);
                if (!$this->isValidJsonData($schema, $values['properties'])) {
                    trigger_error('Invalid entity properties', E_USER_WARNING);
                    return false;
                }
            }
        }

        $this->db->startTransaction();

        try {
            $this->updateMultipleRecords($criteria, $values);
            $log = new EntityLog($this->db);
            $actionType = (isset($values['is_active'])
                && $values['is_active'] === false)
                ? 'invalidation' : 'revision';
            foreach ($entities->toArray() as $entity) {
                if ($actionType === 'revision') {
                    $log->logUpdate($entity['id'], $agentId);
                } else {
                    $log->logDeletion($entity['id'], $agentId);
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
     * @param string $entityId
     * @param string $property
     * @param mixed $value
     * @param int $agentId
     * @return bool
     */
    public function setProperty(
        string $entityId,
        string $property,
        $value,
        int $agentId
    ): bool {
        return $this->updatePropertiesField(
            $entityId, $agentId, 'set', $property, $value
        );
    }

    /**
     * @param string $entityId
     * @param string $property
     * @param int $agentId
     * @return bool
     */
    public function deleteProperty(
        string $entityId,
        string $property,
        int $agentId
    ): bool {
        return $this->updatePropertiesField(
            $entityId, $agentId, 'delete', $property
        );
    }

    /**
     * @param string $entityId
     * @param int $agentId
     * @return bool
     */
    public function invalidate(string $entityId, int $agentId): bool
    {
        return $this->update($entityId, ['is_active' => false], $agentId);
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
     * @param string $entityId
     * @param int $agentId
     * @return bool
     */
    public function restore(string $entityId, int $agentId): bool
    {
        return $this->update($entityId, ['is_active' => true], $agentId);
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
    public function getType($key = null)
    {
        $key = $key ?? static::ENTITY_TYPE;
        $type = new EntityType($this->db);

        if (is_int($key)) {
            return $type->fetch($key);
        } elseif (is_string($key)) {
            return $type->findByQName($key);
        } else {
            return new Map();
        }
    }

    /**
     * @param int|null $id
     * @return mixed
     */
    public function getTypeQName(int $id = null)
    {
        if (is_null($id)) {
            return static::ENTITY_TYPE;
        }
        return $this->getType($id)->get('qualified_name');
    }

    /**
     * @param string|null $qName
     * @return mixed
     */
    public function getTypeId(string $qName = null)
    {
        return $this->getType($qName)->get('id');
    }

    /**
     * @param Translator $translator
     * @param mixed $key
     * @param string|null $language
     * @param bool $useFallback
     * @param string $default
     * @return string
     */
    public function getTypeLabel(
        Translator $translator,
        $key = null,
        string $language = '',
        bool $useFallback = true,
        string $default = ''
    ): string {
        $label = $this->getType($key)->getDecodedJsonValue('label');
        return $translator->resolve($label, $language, $useFallback, $default);
    }

    /**
     * @param mixed $key
     * @param bool $encoded
     * @return mixed
     */
    public function getTypeSchema($key = null, bool $encoded = true)
    {
        $schema = $this->getType($key)->decodeJsonValue('details')
            ->get('details.schema');
        if ($encoded) {
            return !empty($schema) ? json_encode($schema) : '{}';
        } else {
            return $schema;
        }
    }

    /**
     * @param mixed $key
     * @return Map
     */
    public function getProperty($key)
    {
        $property = new EntityProperty($this->db);

        if (is_int($key)) {
            return $property->fetch($key);
        } elseif (is_string($key)) {
            return $property->findByQName($key);
        } else {
            return new Map();
        }
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function getPropertyQName(int $id)
    {
        return $this->getProperty($id)->get('qualified_name');
    }

    /**
     * @param string $qName
     * @return mixed
     */
    public function getPropertyId(string $qName)
    {
        return $this->getProperty($qName)->get('id');
    }

    /**
     * @param Translator $translator
     * @param mixed $key
     * @param string|null $language
     * @param bool $useFallback
     * @param string $default
     * @return string
     */
    public function getPropertyLabel(
        Translator $translator,
        $key,
        string $language = '',
        bool $useFallback = true,
        string $default = ''
    ): string {
        $label = $this->getProperty($key)->getDecodedJsonValue('label');
        return $translator->resolve($label, $language, $useFallback, $default);
    }

    /*--------------------------------------------------------------------*/

    /**
     * @param string $schema
     * @param string $data
     * @return bool
     */
    protected function isValidJsonData(string $schema, string $data): bool
    {
        if (empty($schema)) {
            return false;
        }

        $validator = new SchemaValidator();
        $validator->check(json_decode($data), json_decode($schema));

        $isValid = $validator->isValid();
        if ($isValid) {
            $this->propertyValidationErrors = [];
        } else {
            $this->propertyValidationErrors = $validator->getErrors();
        }

        return $validator->isValid();
    }

    /**
     * @param string $entityId
     * @param int $agentId
     * @param string $action
     * @param string $path
     * @param mixed $value
     * @return bool
     */
    protected function updatePropertiesField(
        string $entityId,
        int $agentId,
        string $action,
        string $path,
        $value = null
    ): bool {
        $entity = $this->fetch($entityId);
        if ($entity->isEmpty()) {
            return true;
        }
        $properties = new Map($entity->getDecodedJsonValue('properties'));
        switch ($action) {
            case 'set':
                $properties->set($path, $value);
                break;
            case 'delete':
                $properties->remove($path);
                break;
            default:
                throw new InvalidArgumentException(sprintf(
                    self::$errors['unsupported_json_update'], $action
                ));
        }
        return $this->update($entityId, [
            'properties' => json_encode($properties->toArray())
        ], $agentId);
    }

    /**
     * @param array $criteria
     */
    protected function adjustCriteria(array &$criteria)
    {
        if (is_null(static::ENTITY_TYPE)) {
            return;
        }
        $adjustedCriteria = [['type_id', '=', $this->getTypeId()]];
        foreach ($criteria as $criterion) {
            if ($criterion[0] !== 'type_id') {
                $adjustedCriteria[] = $criterion;
            }
        }
        $criteria = $adjustedCriteria;
    }
}

// -- End of file
