<?php

declare(strict_types=1);

namespace Application\Models\Entity;

use Application\Core\Database\Database;
use Application\Models\Agent;
use Application\Models\AgentType;
use Application\Models\Entity;
use Application\Models\EntityLog;
use Application\Models\EntityRelation;
use Application\Models\LogActionType;
use PDO;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use SyntaxHighlight\SqlFormatter;

/**
 * Class Annotation
 * @package Application\Models\Entity
 */
class Annotation extends Entity
{
    const ENTITY_TYPE = 'dhc:Annotation';

    const TYPE_MOTIVATIONS = [
        'image' => 'sc:painting',
        'transcription' => 'sc:painting',
        'classification' => 'oa:classifying',
        'identification' => 'oa:identifying',
        'tag' => 'oa:tagging',
        'link' => 'oa:linking',
        'mention' => 'oa:linking',
        'description' => 'oa:describing',
        'comment' => 'oa:commenting',
        'highlight' => 'oa:highlighting',
        'bookmark' => 'oa:bookmarking',
        'moderation' => 'oa:moderating',
        'edit' => 'oa:editing',
        'question' => 'oa:questioning',
        'reply' => 'oa:replying',
    ];

    /**
     * @var array Error messages
     */
    protected static $errors = [
        'invalid_search_granularity' => 'Invalid search granularity: "%s"',
    ];

    /**
     * Initializes the class properties.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    /**
     * @param string $annotationId
     * @param string $targetEntityId
     * @param string $targetEntityType
     * @param int $agentId
     * @param int|null $sequenceNumber
     * @return bool
     */
    public function addTarget(
        string $annotationId,
        string $targetEntityId,
        string $targetEntityType,
        int $agentId,
        int $sequenceNumber = null
    ): bool {
        $propertyId = $this->getPropertyId('dhc:hasTarget');
        $entityRelation = new EntityRelation($this->db);
        return !is_null($entityRelation->create(
            [self::ENTITY_TYPE, $propertyId, $targetEntityType],
            $annotationId,
            $propertyId,
            $targetEntityId,
            $agentId,
            $sequenceNumber
        ));
    }

    /**
     * @param string $annotationId
     * @param string $bodyEntityId
     * @param string $bodyEntityType
     * @param int $agentId
     * @param int|null $sequenceNumber
     * @return bool
     */
    public function addBody(
        string $annotationId,
        string $bodyEntityId,
        string $bodyEntityType,
        int $agentId,
        int $sequenceNumber = null
    ): bool {
        $propertyId = $this->getPropertyId('dhc:hasBody');
        $entityRelation = new EntityRelation($this->db);
        return !is_null($entityRelation->create(
            [self::ENTITY_TYPE, $propertyId, $bodyEntityType],
            $annotationId,
            $propertyId,
            $bodyEntityId,
            $agentId,
            $sequenceNumber
        ));
    }

    /**
     * @param string $annotationType
     * @return string
     */
    public function getMotivationQNameByType(string $annotationType): string
    {
        if (array_key_exists($annotationType, self::TYPE_MOTIVATIONS)) {
            return self::TYPE_MOTIVATIONS[$annotationType];
        } else {
            return '';
        }
    }

    /**
     * @param string $motivationQName
     * @return string
     */
    public function getTypeByMotivationQName(string $motivationQName): string
    {
        $motivations = array_flip(self::TYPE_MOTIVATIONS);
        if (array_key_exists($motivationQName, $motivations)) {
            return $motivations[$motivationQName];
        } else {
            return '';
        }
    }

    /**
     * @param string $resourceType
     * @param array $filter
     * @return array
     */
    public function getStatistics(string $resourceType, array $filter = []): array
    {
        if (!in_array($resourceType, ['collection', 'manifest', 'canvas'])) {
            return [];
        }

        $selectClause = '
            SUM(transcriptions::int) AS transcriptions,
            SUM(mentions::int) AS mentions,
            SUM(tags::int) AS tags,
            SUM(identifications::int) AS identifications
        ';
        $groupByClause = '';

        switch ($resourceType) {
            case 'canvas':
                $selectClause = '
                    manifest_code,
                    canvas_code,
                    transcriptions,
                    mentions,
                    tags,
                    identifications
                ';
                break;
            case 'manifest':
                $selectClause = '
                    manifest_code,
                ' . $selectClause;
                $groupByClause = 'manifest_code';
                break;
            case 'collection':
                $selectClause = '
                    LEFT(manifest_code, 4) AS collection_code,
                ' . $selectClause;
                $groupByClause = 'LEFT(manifest_code, 4)';
                break;
        }

        $query = "
            SELECT " . $selectClause . "
            FROM (
                SELECT
                    manifest_code,
                    canvas_code,
                    COUNT(body_id) AS transcriptions
                FROM canvas_object_annotations
                WHERE motivation = 'sc:painting'
                GROUP BY manifest_code, canvas_code
            ) transcriptions FULL JOIN (
                SELECT
                    manifest_code,
                    canvas_code,
                    COUNT(body_id) AS mentions,
                    SUM(tags.count::int) AS tags,
                    SUM(identifications.count::int) AS identifications
                FROM canvas_object_annotations
                LEFT JOIN (
                    SELECT target_id, count(body_id) AS count
                    FROM mention_annotations
                    WHERE motivation = 'oa:tagging'
                    GROUP BY target_id
                ) tags ON canvas_object_annotations.body_id = tags.target_id
                LEFT JOIN (
                    SELECT target_id, count(body_id) AS count
                    FROM mention_annotations
                    WHERE motivation = 'oa:identifying'
                    GROUP BY target_id
                ) identifications ON canvas_object_annotations.body_id = identifications.target_id
                WHERE motivation = 'oa:linking'
                GROUP BY manifest_code, canvas_code
            ) mentions USING (manifest_code, canvas_code)
        ";

        if (!empty($filter)) {
            $query .= ' WHERE ' . $this->getSubqueryExpression('manifest_code', 'in', $filter, 'text');
        }
        if (!empty($groupByClause)) {
            $query .= ' GROUP BY ' . $groupByClause;
        }

        return $this->db->fetch('table', $query);
    }

    /**
     * @param string $query
     * @param array $filter
     * @param string $granularity
     * @param bool $anyTerm
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function search(
        string $query,
        array $filter = [],
        string $granularity = 'canvas_object',
        bool $anyTerm = false,
        int $limit = 0,
        int $offset = 0
    ): array {
        $parameters = [];
        $query = $this->buildSearchQuery($query, $filter, $parameters, $granularity, $anyTerm)
            . ' ' . $this->getLimitClause($limit, $offset);
        return $this->db->fetch('table', $query, $parameters);
    }

    /**
     * @param string $query
     * @param array $filter
     * @param string $granularity
     * @param bool $anyTerm
     * @return int
     */
    public function getSearchResultCount(
        string $query,
        array $filter = [],
        string $granularity = 'canvas_object',
        bool $anyTerm = false
    ): int {
        $parameters = [];
        $query = $this->buildSearchQuery($query, $filter, $parameters, $granularity, $anyTerm, true);
        return (int) $this->db->fetch('scalar', $query, $parameters);
    }

    /**
     * @param string $query
     * @return string
     */
    public function formatSearchQuery(string $query): string
    {
        // Strip unnecessary characters
        $stripChars = '/[^[:alnum:][:space:]"#@&\|\.,:-]/u';
        $query = preg_replace($stripChars, ' ', trim($query));

        // Parse phrases in quotes
        while (($start = strpos($query, '"')) !== false) {
            $end = strpos($query, '"', $start + 1);
            if ($end === false) {
                $query = substr_replace($query, '', $start, 1);
            } else {
                $term = substr($query, $start + 1, $end - $start - 1);
                $term = preg_replace('/[[:space:]]+/', '_', trim($term));
                $query = substr_replace(
                    $query,
                    $term,
                    $start,
                    $end - $start + 1
                );
            }
        }

        // Collapse consecutive whitespace characters
        $query = preg_replace('/[[:space:]]+/', ' ', $query);

        // Parse boolean operators
        $query = str_replace(' OR ', ' | ', $query);
        $query = str_replace(' AND ', ' & ', $query);
        $query = preg_replace('/ [\| ]{2,} /', ' | ', $query);
        $query = preg_replace('/ [^\|]{1} /', ' ', $query);
        $query = preg_replace('/([^\|]{1}) ([^\|]{1})/', '$1 & $2', $query);

        return $query;
    }

    /**
     * @param string $targetEntityId
     * @param int $maxDepth
     * @return array
     */
    public function findByTarget(string $targetEntityId, int $maxDepth = 2): array
    {
        $annotations = $this->findByTargets([$targetEntityId], $maxDepth);
        return $this->buildTree($annotations, $targetEntityId);
    }

    /**
     * @param array $targetEntityIds
     * @param int $maxDepth
     * @return array
     */
    public function findByTargets(array $targetEntityIds, int $maxDepth = 2): array
    {
        $depth = 1;
        $annotations = [];
        while (!empty($targetEntityIds) && ($depth <= $maxDepth)) {
            $query = 'SELECT id, target_id, body_id FROM annotations';
            if (count($targetEntityIds) > 1) {
                $query .= ' WHERE ' . $this->getSubqueryExpression('target_id', 'in', $targetEntityIds, 'uuid', false);
                $records = $this->db->fetch('table', $query);
            } else {
                $query .= ' WHERE target_id = :target_id';
                $parameters = [[':target_id', $targetEntityIds[0], PDO::PARAM_STR]];
                $records = $this->db->fetch('table', $query, $parameters, true);
            }
            if (!empty($records)) {
                $annotations = array_merge($annotations, $records);
                $targetEntityIds = array_column($records, 'body_id');
            } else {
                $targetEntityIds = [];
            }
            $depth++;
        }

        $entities = $this->getEntityDetails($annotations);
        $groupedAnnotations = [];

        foreach ($annotations as $annot) {
            $annotation = $entities[$annot['id']];
            $annotationType = $this->getTypeByMotivationQName(
                $annotation['properties']['motivation']
            );
            $groupedAnnotations[$annot['target_id']][] = [
                'id' => $annotation['id'],
                'type' => $annotationType,
                'motivation' => $annotation['properties']['motivation'],
                'body' => $entities[$annot['body_id']],
                'created' => $annotation['created'],
                'creator' => $annotation['creator'],
            ];
        }

        return $groupedAnnotations;
    }

    /**
     * @param string $bodyEntityId
     * @return array
     */
    public function findByBody(string $bodyEntityId) : array
    {
        return $this->findByBodies([$bodyEntityId]);
    }

    /**
     * @param array $bodyEntityIds
     * @return array
     */
    public function findByBodies(array $bodyEntityIds): array
    {
        $query = 'SELECT id, target_id, body_id FROM annotations';
        if (count($bodyEntityIds) > 1) {
            $query .= ' WHERE ' . $this->getSubqueryExpression('body_id', 'in', $bodyEntityIds, 'uuid', false);
            return $this->db->fetch('table', $query);
        } else {
            $query .= ' WHERE body_id = :body_id';
            $parameters = [[':body_id', $bodyEntityIds[0], PDO::PARAM_STR]];
            return $this->db->fetch('table', $query, $parameters, true);
        }
    }

    /**
     * @param array $annotations
     * @return array
     */
    public function getEntityDetails(array &$annotations): array
    {
        $entityIds = [];

        foreach((new RecursiveIteratorIterator(
            new RecursiveArrayIterator($annotations))) as $entityId) {
            $entityIds[] = $entityId;
        }

        if (empty($entityIds)) {
            return [];
        }

        $entityRecords = (new Entity($this->db))->findAll(
            [['id', 'in', array_unique($entityIds)]],
            ['id', 'type_id', 'properties']
        )->toArray();

        $entityLogRecords = (new EntityLog($this->db))->findAll(
            [
                ['entity_id', 'in', array_column($annotations, 'id')],
                ['action_type_id', '=', (new LogActionType($this->db))
                    ->findByQName(LogActionType::CREATION)->get('id')],
            ],
            ['entity_id', 'agent_id', 'timestamp']
        )->setKeyColumn('entity_id');

        $agentTypes = (new AgentType($this->db))->fetchAll(
            ['id', 'qualified_name']
        )->setKeyColumn('id');

        $agentRecords = (new Agent($this->db))->findAll(
            [['id', 'in', array_column($entityLogRecords->toArray(), 'agent_id')]],
            ['id', 'type_id', 'details']
        )->toArray();

        $agents = [];
        foreach ($agentRecords as $agent) {
            $type = $agentTypes->get($agent['type_id'])['qualified_name'];
            $details = json_decode($agent['details'], true);
            $name = null;
            if ($type === AgentType::PERSON) {
                $name = $details['firstName'] . ' ' . $details['lastName'];
                $type = 'Person';
            } elseif ($type === AgentType::ROBOT) {
                $name = $details['name'];
                $type = 'Software';
            }
            $agents[$agent['id']] = [
                'id' => $agent['id'],
                'type' => $type,
                'name' => $name,
            ];
        }

        $entities = [];
        foreach ($entityRecords as $entity) {
            $details = [
                'id' => $entity['id'],
                'type' => $this->getTypeQName($entity['type_id']),
                'properties' => json_decode($entity['properties'], true),
            ];
            $logRecord = $entityLogRecords->getRow($entity['id']);
            if (!empty($logRecord)) {
                $details['created'] = $logRecord['timestamp'];
                $details['creator'] = $agents[$logRecord['agent_id']];
            }
            $entities[$entity['id']] = $details;
        }

        return $entities;
    }

    /**
     * @param array $annotations
     * @param string $targetEntityId
     * @return array
     */
    public function buildTree(
        array &$annotations,
        string $targetEntityId
    ): array {
        $tree = [];
        if (!isset($annotations[$targetEntityId])) {
            return $tree;
        }
        foreach ($annotations[$targetEntityId] as $annotation) {
            $bodyEntityId = strval($annotation['body']['id'] ?? null);
            if (!is_null($bodyEntityId) && isset($annotations[$bodyEntityId])) {
                $annotation['body']['annotations'] = $this->buildTree(
                    $annotations,
                    $bodyEntityId
                );
            }
            $tree[$annotation['id']] = $annotation;
        }
        return $tree;
    }

    /**
     * @param string $query
     * @param bool $anyTerm
     * @return array
     */
    protected function parseSearchQuery(
        string $query,
        bool $anyTerm = false
    ): array {
        $segments = preg_split('/( \& | \| )/', $query, 0, PREG_SPLIT_DELIM_CAPTURE);
        $hasAny = [];
        $hasAll = [];
        $termCount = 0;
        foreach ($segments as $index => $segment) {
            $segment = trim($segment);
            if (in_array($segment, ['&', '|'])) {
                continue;
            }
            $previousOperator = trim($segments[$index - 1] ?? '');
            $nextOperator = trim($segments[$index + 1] ?? '');
            $isExcluded = false;
            if (substr($segment, 0, 1) === '-') {
                $isExcluded = true;
                $segment = ltrim($segment, '-');
            }
            $term = $this->parseSearchTerm($segment);
            if (empty($term) || ($anyTerm && $isExcluded)) {
                continue;
            }
            $termCount++;
            $term['id'] = 't' . $termCount;
            $term['isExcluded'] = $isExcluded;
            if ((($previousOperator === '|') && ($nextOperator !== '|'))
                || ($nextOperator === '|') || $anyTerm) {
                $hasAny[] = $term;
            } else {
                $hasAll[] = $term;
            }
        }
        if (empty($hasAny) && empty($hasAll)) {
            return [];
        }
        return  [
            'hasAny' => $hasAny,
            'hasAll' => $hasAll,
        ];
    }

    /**
     * @param string $term
     * @return array
     */
    protected function parseSearchTerm(string $term): array
    {
        $flags = ['#', '@'];
        $term = str_replace('_', ' ', $term);
        $term = trim($term, ' &|.,:-');
        $term = rtrim($term, implode('', $flags));
        if (empty($term)) {
            return [];
        }
        $term = mb_strtolower($term);
        $segments = preg_split(
            '/(' . implode('|', $flags) . ')/',
            $term, 0, PREG_SPLIT_DELIM_CAPTURE
        );
        $activeFlag = '';
        $content = '';
        $hashtags = '';
        $entities = '';
        foreach ($segments as $segment) {
            if (in_array($segment, $flags)) {
                $activeFlag = $segment;
                continue;
            } elseif (empty($segment)) {
                continue;
            }
            switch ($activeFlag) {
                case '#':
                    if (!empty($hashtags)) {
                        $hashtags .= '&';
                    }
                    $hashtags .= $segment;
                    break;
                case '@':
                    if (!empty($entities)) {
                        $entities .= '&';
                    }
                    $entities .= $segment;
                    break;
                default:
                    $content = $segment;
            }
        }
        return  [
            'content' => $content,
            'hashtags' => $this->parseSearchTermSpecifier($hashtags, '#'),
            'entities' => $this->parseSearchTermSpecifier($entities, '|'),
        ];
    }

    /**
     * @param string $specifier
     * @param string $flag
     * @return array
     */
    protected function parseSearchTermSpecifier(
        string $specifier,
        string $flag
    ): array {
        $segments = preg_split('/(\&|\|)/', $specifier, 0, PREG_SPLIT_DELIM_CAPTURE);
        $hasAny = [];
        $hasAll = [];
        foreach ($segments as $index => $segment) {
            if (in_array($segment, ['&', '|'])) {
                continue;
            }
            $previousOperator = $segments[$index - 1] ?? null;
            $nextOperator = $segments[$index + 1] ?? null;
            if ($flag === '#') {
                $segment = preg_replace('/[^[:alnum:]]/u', '', trim($segment));
            }
            if (empty($segment)) {
                continue;
            }
            if ((($previousOperator === '|') && ($nextOperator !== '|'))
                || ($nextOperator === '|')) {
                $hasAny[] = $segment;
            } else {
                $hasAll[] = $segment;
            }
        }
        if (empty($hasAny) && empty($hasAll)) {
            return [];
        }
        return  [
            'hasAny' => $hasAny,
            'hasAll' => $hasAll,
        ];
    }

    /**
     * @param string $query
     * @param array $filter
     * @param array $parameters
     * @param string $granularity
     * @param bool $anyTerm
     * @param bool $countOnly
     * @return string
     */
    protected function buildSearchQuery(
        string $query,
        array $filter,
        array &$parameters,
        string $granularity,
        bool $anyTerm = false,
        bool $countOnly = false
    ): string {
        $query = $this->formatSearchQuery($query);
        $terms = $this->parseSearchQuery($query, $anyTerm);

        switch ($granularity) {
            case 'canvas_object':
                $fields = 'manifest_id, canvas_id, target_id, target_bbox';
                $orderByFields = 'manifest_code, page_number';
                $countField = 'target_id';
                break;
            case 'canvas':
                $fields = 'manifest_id, canvas_id';
                $orderByFields = 'manifest_code, page_number';
                $countField = 'canvas_id';
                break;
            case 'manifest':
                $fields = 'manifest_id';
                $orderByFields = 'manifest_code';
                $countField = 'manifest_id';
                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf(self::$errors['invalid_search_granularity'], $granularity)
                );
        }

        $query = $this->buildSearchCteClause($terms);

        if (!$countOnly) {
            $query .= ' SELECT ' . $fields . ', COUNT(*) AS matches';
        } else {
            $query .= ' SELECT COUNT(DISTINCT ' . $countField . ') AS records';
        }

        $query .= '
            FROM results
                LEFT JOIN (
                    SELECT ' . $fields . ', jsonb_agg(DISTINCT term ORDER BY term) AS terms
                    FROM results
                    GROUP BY ' . $fields . '
                ) agg_results USING (' . $fields . ')
        ';

        $query .= $this->buildSearchConditions($terms, $filter, $parameters);

        if (!$countOnly) {
            $query .= ' GROUP BY ' . $fields . ', ' . $orderByFields;
            $query .= ' ORDER BY ' . $orderByFields;

            /*
            echo '<code style="padding:20px; background: #fee;">'
                . var_export($terms, true) . '</code>';
            echo '<code style="padding:20px; background: white;">'
                . SqlFormatter::format($query) . '</code>';
            */
        }

        return $query;
    }

    /**
     * @param array $terms
     * @return string
     */
    protected function buildSearchCteClause(array $terms): string
    {
        if (empty($terms)) {
            return '';
        }
        $termQueries = [];
        foreach (array_merge($terms['hasAny'], $terms['hasAll']) as $term) {
            $termQueries[] = '(' . $this->buildSearchTermQuery($term) . ')';
        }
        return 'WITH results AS (' . implode(' UNION ', $termQueries) . ')';
    }

    /**
     * @param array $term
     * @return string
     */
    protected function buildSearchTermQuery(array $term): string
    {
        $query = "
            SELECT manifest_id, manifest_code, canvas_id, page_number,
                annotations.target_id, target_bbox, body_digest,"
            . (empty($term['hashtags']) ? " '[]'::jsonb AS" : '') . ' hashtags,'
            . (empty($term['entities']) ? " '[]'::jsonb AS" : '') . ' names,'
            . " '" . $term['id'] . "'::text AS term
            FROM canvas_object_annotations annotations
        ";
        if (!empty($term['hashtags'])) {
            $query .= '
                LEFT JOIN si_tags
                    ON annotations.body_id = si_tags.target_id
            ';
        }
        if (!empty($term['entities'])) {
            $query .= '
                LEFT JOIN si_named_entities
                    ON annotations.body_id = si_named_entities.target_id
            ';
        }
        $query .= "
            WHERE annotations.motivation IN ('oa:linking', 'sc:painting')
        ";
        if (!empty($term['content'])) {
            $query .= " AND unaccent(body_digest)
                ILIKE unaccent('%" . $term['content'] . "%')";
        }
        if (!empty($term['hashtags'])) {
            $hasAny = $term['hashtags']['hasAny'];
            $hasAll = $term['hashtags']['hasAll'];
            if (!empty($hasAny)) {
                $query .= " AND (JSONB_EXISTS_ANY(si_tags.hashtags, ARRAY['"
                    . implode("', '", $hasAny) . "']))";
            }
            if (!empty($hasAll)) {
                $query .= " AND (JSONB_EXISTS_ALL(si_tags.hashtags, ARRAY['"
                    . implode("', '", $hasAll) . "']))";
            }
        }
        if (!empty($term['entities'])) {
            $hasAny = $term['entities']['hasAny'];
            $hasAll = $term['entities']['hasAll'];
            if (!empty($hasAny)) {
                $query .= " AND (si_named_entities.names::text ~* unaccent('"
                    . implode("|", $hasAny) . "'))";
            }
            foreach ($hasAll as $name) {
                $query .= " AND (si_named_entities.names::text ILIKE unaccent('%"
                    . $name . "%'))";
            }
        }

        return $query;
    }

    /**
     * @param array $terms
     * @param array $filter
     * @param array $parameters
     * @return string
     */
    protected function buildSearchConditions(array $terms, array $filter, array &$parameters): string
    {
        $query = 'WHERE results.manifest_id IS NOT NULL';

        if (!empty($terms['hasAny'])) {
            $conditions = [];
            foreach ($terms['hasAny'] as $term) {
                $conditions[] = ($term['isExcluded'] ? 'NOT ' : '')
                    . "JSONB_EXISTS(terms, '" . $term['id'] . "')";
            }
            $query .= ' AND (' . implode(' OR ', $conditions) . ')';
        }
        foreach ($terms['hasAll'] as $term) {
            $query .= ' AND ' .  ($term['isExcluded'] ? 'NOT ' : '')
                . "JSONB_EXISTS(terms, '" . $term['id'] . "')";
        }

        $fields = ['manifest_id', 'manifest_code', 'canvas_id', 'canvas_code'];
        foreach ($filter as $field => $value) {
            $exactMatch = true;
            if (is_array($value)) {
                $exactMatch = isset($value[1]) ? (bool) $value[1] : true;
                $value = $value[0] ?? '';
            }
            if (in_array($field, $fields)) {
                if (is_array($value)) {
                    if (empty($value)) {
                        continue;
                    }
                    $query .= ' AND ' . $field;
                    if ($exactMatch) {
                        $query .= " IN ('" . implode("', '", $value) . "')";
                    } else {
                        $query .= " ILIKE ANY (ARRAY['%" . implode("%', '%", $value) . "%'])";
                    }
                }
                else {
                    $paramCode = ':' . $field;
                    $query .= ' AND ' . $field
                        . ($exactMatch ? ' = ' : ' ILIKE ') . $paramCode;
                    $parameters[$paramCode] = $exactMatch
                        ? $value : ('%' . $value . '%');
                }
            }
        }

        return $query;
    }
}

// -- End of file
