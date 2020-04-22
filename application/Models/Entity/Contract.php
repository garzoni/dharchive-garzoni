<?php

declare(strict_types=1);

namespace Application\Models\Entity;

use Application\Core\Database\Database;
use Application\Core\Type\Map;
use Application\Core\Type\Table;
use Application\Models\Entity;
use Application\Models\Location;
use Application\Models\Profession;
use Application\Models\ProfessionCategory;
use Application\Models\Traits\QueryHandler;

use function Application\splitValues;

use const Application\REGEX_QNAME;
use const Application\REGEX_UUID;

/**
 * Class Contract
 * @package Application\Models\Entity
 */
class Contract extends Entity
{
    use QueryHandler;

    const ENTITY_TYPE = 'grz:ContractMention';

    /**
     * Initializes the class properties.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        parent::__construct($db);

        $this->fields['date'] = [
            'expression' => "properties->>'date'",
        ];
    }

    public function getDatabaseManager(): Database {
        return $this->db;
    }

    public function getPersonLink(array $mention, string $personUrl): string {
        $personId = $mention['entity']['id'] ?? '';
        $personName = $mention['entity']['name'] ?? $personId;
        if ($personId) {
            return '<a href="' . $personUrl . '/' . $personId . '" data-title="'
                . htmlspecialchars($personName) . '" class="tooltipped" target="_blank">'
                . '<i class="user outline icon"></i></a>';
        } else {
            return '';
        }
    }

    public function getPersonMentionName(array $mention): string {
        $popup = '<div class="ui popup">'
            . '<div class="header">' . htmlspecialchars($mention['fullName']) . '</div>';
        $content = '';
        if (isset($mention['geoOrigin']['standardForm'])) {
            $content .= 'Geographical origin: <strong>' . ($mention['geoOrigin']['standardForm'] ?? '') . '</strong>';
        }
        if ($content) {
            $content = '<div class="content">' .  $content . '</div>';
        }
        $popup .= $content . '</div>';
        return '<span class="person-mention popup-target">' . $mention['fullName'] . '</span>' . $popup;
    }

    public function getPersonMentionGender(array $mention): string {
        $gender = $mention['gender'] ?? '';
        switch ($gender) {
            case 'grz:Male':
                $icon = 'mars';
                break;
            case 'grz:Female':
                $icon = 'venus';
                break;
            default:
                $icon = 'genderless';
        }
        return '<span class="note"><i class="' . $icon . ' icon"></i></span>';
    }

    public function getPersonMentionProfessions(array $mention, bool $textOnly = false): string {
        $professions = $mention['professions'] ?? [];
        $standardForms = [];
        foreach ($professions as $profession) {
            if (isset($profession['standardForm'])) {
                $standardForms[] = $profession['standardForm'];
            }
        }
        if (empty($standardForms)) {
            return '';
        }
        $standardForms = implode(', ', $standardForms);
        return $textOnly ? $standardForms : ('<span class="note">' . $standardForms . '</span>');
    }

    public function getSummary(
        array $mentions,
        string $personUrl,
        bool $detailed = true
    ): array {
        $date = '';
        $masters = [];
        $apprentices = [];
        $guarantors = [];
        foreach ($mentions as $mention) {
            if ($mention['instanceOf'] === 'grz:PersonMention') {
                if ($detailed) {
                    $personMention = $this->getPersonMentionName($mention) . ' '
                        . $this->getPersonLink($mention, $personUrl) . ' '
                        . $this->getPersonMentionGender($mention) . ' '
                        . $this->getPersonMentionProfessions($mention);
                } else {
                    $personMention = $mention['fullName'];
                }
                if ($mention['tag']['qualifiedName'] === 'grz:Master') {
                    $masters[] = $personMention;
                } elseif ($mention['tag']['qualifiedName'] === 'grz:Apprentice') {
                    $apprentices[] = $personMention;
                } elseif ($mention['tag']['qualifiedName'] === 'grz:Guarantor') {
                    $guarantors[] = $personMention;
                }
            } elseif ($mention['instanceOf'] === 'grz:ContractMention') {
                $date = ($mention['date'] ?? '0000-00-00');
            }
        }
        return [
            'date' => $date,
            'masters' => $masters,
            'apprentices' => $apprentices,
            'guarantors' => $guarantors,
        ];
    }

    public function getSummaryTable(array $mentions, string $personUrl, string $contractUrl): array
    {
        $contract = $this->getSummary($mentions, $personUrl);
        return array_filter([
            'Date' => $contract['date'] . ' <a class="pull-right" target="_blank" href="' . $contractUrl . '">'
                . '<i class="ui newspaper outline icon"></i></a>',
            'Master' => implode('<br />', $contract['masters']),
            'Apprentice' => implode('<br />', $contract['apprentices']),
            'Guarantor' => implode('<br />', $contract['guarantors']),
        ]);
    }

    public function getMentionedProfessions(string $contractId): Table
    {
        if (empty($contractId)) {
            return new Table();
        }
        $query = "
            SELECT DISTINCT profession->>'standardForm' standard_form
            FROM mentions, jsonb_array_elements(properties->'professions') profession
            WHERE type_id = (
                    SELECT id FROM entity_types
                    WHERE prefix = 'grz' AND name = 'PersonMention'
                )
                AND contract_id = :contract_id
                AND profession->>'standardForm' IS NOT NULL;
        ";

        $standardForms = $this->db->fetch('list', $query, [[':contract_id',  $contractId, \PDO::PARAM_STR]], true);

        if (empty($standardForms)) {
            return new Table();
        }

        $records = (new Profession($this->db))->findAll([['standard_form', 'in', $standardForms]]);

        if ($records->isEmpty()) {
            return $records;
        }

        $categories = [];
        $categoryIds = $records->getList('category_id');
        if ($categoryIds) {
            $professionCategory = new ProfessionCategory($this->db);
            $categories = $professionCategory->getLabels($categoryIds, true);
        }

        $concatenate = function($values) {
            $values = json_decode(strval($values), true);
            return is_array($values) ? implode(', ', $values) : null;
        };

        $professions = [];
        foreach ($records->toArray() as $profession) {
            $profession['material'] = $concatenate($profession['material']);
            $profession['product'] = $concatenate($profession['product']);
            $profession['category_name'] = $categories[$profession['category_id']] ?? null;
            $professions[$profession['standard_form']] = $profession;
        }

        return new Table($professions);
    }

    public function getMentionedLocations(string $contractId): Table
    {
        if (empty($contractId)) {
            return new Table();
        }
        $query = "
            SELECT standard_form
            FROM (
                SELECT DISTINCT jsonb_array_elements_text(standard_forms) standard_form
                FROM (
                    SELECT jsonb_build_array(
                        properties#>'{geoOrigin,standardForm}',
                        properties#>'{chargeLocation,standardForm}',
                        properties#>'{residence,standardForm}'
                    ) AS standard_forms
                    FROM mentions
                    WHERE type_id = (
                            SELECT id FROM entity_types
                            WHERE prefix = 'grz' AND name = 'PersonMention'
                        )
                        AND contract_id = :contract_id
                ) mentions
            ) professions
            WHERE standard_form IS NOT NULL;
        ";

        $standardForms = $this->db->fetch('list', $query, [[':contract_id',  $contractId, \PDO::PARAM_STR]], true);

        if (empty($standardForms)) {
            return new Table();
        }

        $locations = (new Location($this->db))->findAll([['standard_form', 'in', $standardForms]]);

        if ($locations->isEmpty()) {
            return $locations;
        }

        return $locations->setKeyColumn('standard_form');
    }

    public function search(array $criteria = [], array $order = [], int $limit = 0, int $offset = 0): array
    {
        $selectClause = 'SELECT DISTINCT r1.contract_id';
        $fromClause = 'FROM ' . $this->getSearchSelectionClause($criteria);
        $orderByClause = '';
        if (isset($order[0]['date'])) {
            $selectClause .= ', date';
            $orderByClause .= ' ORDER BY date ' . strtoupper($order[0]['date']);
        }
        $query = 'SELECT contract_id FROM (' . $selectClause . ' ' . $fromClause . ' '
            . $orderByClause . ' ' . $this->getLimitClause($limit, $offset) . ') results';
        return $this->db->fetch('list', $query);
    }

    public function getSearchResultCount(array $criteria = []): int
    {
        $query = 'SELECT COUNT(DISTINCT r1.contract_id) FROM ' . $this->getSearchSelectionClause($criteria);
        return (int) $this->db->fetch('scalar', $query);
    }

    /**
     * @param string $contractId
     * @return Map
     */
    public function getDetails(string $contractId): Map
    {
        if (empty($contractId)) {
            return new Map();
        }
        return new Map($this->getDetailsOfMany([$contractId])[0] ?? []);
    }

    /**
     * @param array $contractIds
     * @param bool $allOnEmpty
     * @return array
     */
    public function getDetailsOfMany(array $contractIds, bool $allOnEmpty = false): array
    {
        if (empty($contractIds)) {
            if (!$allOnEmpty) {
                return [];
            }
            $in = '';
        } else {
            $in = str_repeat('?,', count($contractIds) - 1) . '?';
        }

        $query = "
            WITH types AS (
                SELECT id, (prefix || ':' || name) AS qname
                FROM entity_types
            )
            SELECT
                contract_id,
                array_to_json(array_agg(
                    properties || 
                    jsonb_build_object('id', mentions.id, 'instanceOf', types.qname) || 
                    jsonb_build_object('tag', tag_properties || jsonb_build_object('id', tag_id)) || 
                    jsonb_build_object('entity', named_entity_properties || jsonb_build_object('id', named_entity_id)) || 
                    CASE types.qname
                        WHEN 'grz:PersonMention'
                            THEN jsonb_build_object('fullName', get_full_name(properties->'name'))
                        ELSE '{}'::jsonb
                    END
                    ORDER BY types.qname, tag_properties->>'qualifiedName'
                )) mentions,
                manifest_id,
                canvas_code,
                page_number,
                target_id,
                target_bbox
            FROM
                mentions LEFT JOIN types ON (mentions.type_id = types.id)
        ";

        if (!empty($contractIds)) {
            $query .= ' WHERE contract_id IN (' . $in . ')';
        }

        $query .= '
            GROUP BY
                contract_id,
                manifest_id,
                canvas_code,
                page_number,
                target_id,
                target_bbox
        ';

        return $this->db->fetch('table', $query, $contractIds);
    }

    /**
     * @param array $canvasObjectIds
     * @return array
     */
    public function getContractIds(array $canvasObjectIds): array
    {
        if (empty($canvasObjectIds)) {
            return [];
        }
        $in = str_repeat('?,', count($canvasObjectIds) - 1) . '?';
        $query = "
            SELECT target_id, body_id
            FROM canvas_object_annotations
            WHERE target_id IN (" . $in . ")
                AND body_type_id = (
                    SELECT id
                    FROM entity_types
                    WHERE prefix = 'grz' AND name = 'ContractMention'
                    LIMIT 1
                )
        ";
        $identifiers = [];
        foreach ($this->db->fetch('table', $query, $canvasObjectIds) as $record) {
            $identifiers[$record['target_id']] = $record['body_id'];
        }
        return $identifiers;
    }

    /**
     * @param array $criteria
     * @return string
     */
    protected function getSearchSelectionClause(array $criteria = []): string
    {
        $criteria = $this->processSearchCriteria($criteria);
        $subclauseId = 1;

        $clause = "
            SELECT id AS contract_id, properties->>'date' AS date
            FROM entities
            WHERE entity_type_id = 32
                AND is_active = TRUE
        ";

        if ($criteria['created_after']) {
            $clause .= ' AND ' . $this->getTextCondition("properties->>'date'", $criteria['created_after'], '>=', true);
        }
        if ($criteria['created_before']) {
            $clause .= ' AND ' . $this->getTextCondition("properties->>'date'", $criteria['created_before'], '<', true);
        }
        if ($criteria['on_multiple_pages'] !== 'any') {
            $clause .= ' AND ' . $this->getBooleanCondition("properties->>'onMultiplePages'", $criteria['on_multiple_pages']);
        }
        if ($criteria['has_margin'] !== 'any') {
            $clause .= ' AND ' . $this->getBooleanCondition("properties->>'hasMargin'", $criteria['has_margin']);
        }
        if ($criteria['has_details'] !== 'any') {
            $clause .= ' AND ' . $this->getBooleanCondition("LENGTH(COALESCE(TRIM(properties->>'details'), ''))", $criteria['has_details']);
        }
        if ($criteria['details']) {
            $clause .= ' AND ' . $this->getTextCondition("properties->>'details'", $criteria['details'], '=~');
        }

        $clause = '(' . $clause . ') r' . $subclauseId;

        foreach ($criteria['mentions'] as $mention) {
            $conditions = [];
            switch ($mention['type']) {
                case 'grz:PersonMention':
                    $professionCategoryId = $mention['profession_subcategory'] ?: $mention['profession_category'];
                    if ($mention['gender']) {
                        $conditions[] = $this->getTextCondition("properties->>'gender'", $mention['gender']);
                    }
                    if ($mention['ages']) {
                        $conditions[] = $this->getMultipleTextConditions("properties->>'age'", $mention['ages']);
                    }
                    if ($mention['full_names']) {
                        $conditions[] = 'id IN (SELECT DISTINCT id FROM person_mentions
                            WHERE ' . $this->getMultipleTextConditions('full_name', $mention['full_names']) . ')';
                    }
                    if ($mention['named_entity']) {
                        $conditions[] = $this->getTextCondition('named_entity_id', $mention['named_entity']);
                    }
                    if ($mention['profession_standard_forms']) {
                        $conditions[] = 'id IN (SELECT DISTINCT id FROM person_mention_professions
                            WHERE ' . $this->getMultipleTextConditions('standard_form', $mention['profession_standard_forms']) . ')';
                    }
                    if ($mention['profession_occupations']) {
                        $conditions[] = 'id IN (SELECT DISTINCT r1.id
                            FROM person_mention_professions r1 JOIN professions r2 USING (standard_form)
                            WHERE ' . $this->getMultipleTextConditions('r2.occupation', $mention['profession_occupations']) . ')';
                    }
                    if ($professionCategoryId) {
                        $professionCategoryIds = (new ProfessionCategory($this->db))->getDescendantIds($professionCategoryId);
                        $professionCategoryIds[] = $professionCategoryId;
                        $conditions[] = 'id IN (SELECT DISTINCT r1.id
                            FROM person_mention_professions r1 JOIN professions r2 USING (standard_form)
                            WHERE profession_category_id IN (' . implode(', ', $professionCategoryIds) . '))';
                    }
                    if ($mention['profession_materials']) {
                        $conditions[] = 'id IN (SELECT DISTINCT r1.id
                            FROM person_mention_professions r1 JOIN professions r2 USING (standard_form)
                            WHERE ' . $this->getMultipleJsonConditions('r2.material', $mention['profession_materials']) . ')';
                    }
                    if ($mention['profession_products']) {
                        $conditions[] = 'id IN (SELECT DISTINCT r1.id
                            FROM person_mention_professions r1 JOIN professions r2 USING (standard_form)
                            WHERE ' . $this->getMultipleJsonConditions('r2.product', $mention['profession_products']) . ')';
                    }
                    if ($mention['geo_origin_standard_forms']) {
                        $conditions[] = 'id IN (SELECT DISTINCT id FROM person_mention_geo_origins
                            WHERE ' . $this->getMultipleTextConditions('standard_form', $mention['geo_origin_standard_forms']) . ')';
                    }
                    if ($mention['geo_origin_names']) {
                        $conditions[] = 'id IN (SELECT DISTINCT r1.id
                            FROM person_mention_geo_origins r1 JOIN locations r2 USING (standard_form)
                            WHERE ' . $this->getMultipleTextConditions('r2.name', $mention['geo_origin_names']) . ')';
                    }
                    if ($mention['geo_origin_parish']) {
                        $conditions[] = $this->getTextCondition("properties->'geoOrigin'->>'parish'", $mention['geo_origin_parish']);
                    } elseif ($mention['geo_origin_sestiere']) {
                        $conditions[] = $this->getTextCondition("properties->'geoOrigin'->>'sestiere'", $mention['geo_origin_sestiere']);
                    }
                    if ($mention['geo_origin_provinces']) {
                        $conditions[] = 'id IN (SELECT DISTINCT r1.id
                            FROM person_mention_geo_origins r1 JOIN locations r2 USING (standard_form)
                            WHERE ' . $this->getMultipleTextConditions('r2.province', $mention['geo_origin_provinces']) . ')';
                    }
                    if ($mention['geo_origin_countries']) {
                        $conditions[] = 'id IN (SELECT DISTINCT r1.id
                            FROM person_mention_geo_origins r1 JOIN locations r2 USING (standard_form)
                            WHERE ' . $this->getMultipleTextConditions('r2.country', $mention['geo_origin_countries']) . ')';
                    }
                    if ($mention['has_details'] !== 'any') {
                        $conditions[] = $this->getBooleanCondition("LENGTH(COALESCE(TRIM(properties->>'details'), ''))", $mention['has_details']);
                    }
                    if ($mention['details']) {
                        $conditions[] = $this->getTextCondition("properties->>'details'", $mention['details'], '=~');
                    }
                    break;
                case 'grz:WorkshopMention':
                    $mention['type'] = 'grz:PersonMention';
                    if ($mention['insignias']) {
                        $conditions[] = $this->getMultipleTextConditions("properties->'workshop'->>'insigna'", $mention['insignias']);
                    }
                    if ($mention['sites']) {
                        $conditions[] = $this->getMultipleTextConditions("properties->'workshop'->>'site'", $mention['sites']);
                    }
                    if ($mention['sestiere']) {
                        $conditions[] = $this->getTextCondition("properties->'workshop'->>'sestiere'", $mention['sestiere']);
                    }
                    if ($mention['parish']) {
                        $conditions[] = $this->getTextCondition("properties->'workshop'->>'parish'", $mention['parish']);
                    }
                    break;
                case 'grz:EventMention':
                    if ($mention['after']) {
                        $conditions[] = $this->getTextCondition("properties->>'startDate'", $mention['after'], '>=', true);
                    }
                    if ($mention['before']) {
                        $conditions[] = $this->getTextCondition("properties->>'startDate'", $mention['before'], '<', true);
                    }
                    if ($mention['duration_years']) {
                        $conditions[] = "(properties->'duration'->>'years')::int = " . $mention['duration_years'];
                    }
                    if ($mention['duration_months']) {
                        $conditions[] = "(properties->'duration'->>'months')::int = " . $mention['duration_months'];
                    }
                    if ($mention['duration_days']) {
                        $conditions[] = "(properties->'duration'->>'days')::int = " . $mention['duration_days'];
                    }
                    if ($mention['has_details'] !== 'any') {
                        $conditions[] = $this->getBooleanCondition("LENGTH(COALESCE(TRIM(properties->>'details'), ''))", $mention['has_details']);
                    }
                    if ($mention['details']) {
                        $conditions[] = $this->getTextCondition("properties->>'details'", $mention['details'], '=~');
                    }
                    break;
                case 'grz:HostingConditionMention':
                    if ($mention['paid_in_goods'] !== 'any') {
                        $conditions[] = $this->getBooleanCondition("properties->>'paidInGoods'", $mention['paid_in_goods']);
                    }
                    if ($mention['paid_by']) {
                        $conditions[] = $this->getMultipleTextConditions("properties->>'paidBy'", $mention['paid_by']);
                    }
                    if ($mention['periodization']) {
                        $conditions[] = $this->getMultipleTextConditions("properties->>'periodization'", $mention['periodization']);
                    }
                    if ($mention['period']) {
                        $conditions[] = $this->getTextCondition("properties->>'period'", $mention['period'], '=~');
                    }
                    if ($mention['application_rule']) {
                        $conditions[] = $this->getTextCondition("properties->>'applicationRule'", $mention['application_rule']);
                    }
                    if ($mention['clothing_types']) {
                        $conditions[] = $this->getMultipleTextConditions("properties->>'clothingType'", $mention['clothing_types']);
                    }
                    if ($mention['has_details'] !== 'any') {
                        $conditions[] = $this->getBooleanCondition("LENGTH(COALESCE(TRIM(properties->>'details'), ''))", $mention['has_details']);
                    }
                    if ($mention['details']) {
                        $conditions[] = $this->getTextCondition("properties->>'details'", $mention['details'], '=~');
                    }
                    break;
                case 'grz:FinancialConditionMention':
                    if ($mention['paid_in_goods'] !== 'any') {
                        $conditions[] = $this->getBooleanCondition("properties->>'paidInGoods'", $mention['paid_in_goods']);
                    }
                    if ($mention['paid_by']) {
                        $conditions[] = $this->getMultipleTextConditions("properties->>'paidBy'", $mention['paid_by']);
                    }
                    if ($mention['periodization']) {
                        $conditions[] = $this->getMultipleTextConditions("properties->>'periodization'", $mention['periodization']);
                    }
                    if ($mention['period']) {
                        $conditions[] = $this->getTextCondition("properties->>'period'", $mention['period'], '=~');
                    }
                    if ($mention['partial_amount']) {
                        $conditions[] = $this->getTextCondition("properties->>'partialAmount'", $mention['partial_amount'], '=~');
                    }
                    if ($mention['total_amount']) {
                        $conditions[] = $this->getTextCondition("properties->>'totalAmount'", $mention['total_amount'], '=~');
                    }
                    if ($mention['currency_units']) {
                        $conditions[] = $this->getMultipleTextConditions("properties->>'currencyUnit'", $mention['currency_units']);
                    }
                    if ($mention['money_information']) {
                        $conditions[] = $this->getMultipleTextConditions("properties->>'moneyInformation'", $mention['money_information']);
                    }
                    if ($mention['has_details'] !== 'any') {
                        $conditions[] = $this->getBooleanCondition("LENGTH(COALESCE(TRIM(properties->>'details'), ''))", $mention['has_details']);
                    }
                    if ($mention['details']) {
                        $conditions[] = $this->getTextCondition("properties->>'details'", $mention['details'], '=~');
                    }
                    break;
                default:
                    continue;
            }
            if ($mention['tags']) {
                $conditions[] = $this->getTextCondition('tag_id', $mention['tags'], '=', true, false);
            }
            if ($conditions) {
                $clause .= ' NATURAL JOIN (SELECT contract_id FROM mentions WHERE type_id = '
                    . $this->getTypeId($mention['type']) . ' AND ' . implode(' AND ', $conditions) . ') r'
                    . ++$subclauseId;
            }
        }

        return $clause;
    }

    /**
     * @param array $criteria
     * @return array
     */
    protected function processSearchCriteria(array $criteria): array
    {
        $uuidPattern = '/' . REGEX_UUID . '/';
        $qNamePattern = '/' . REGEX_QNAME . '/';

        $mentions = [];
        $decodedMentions = isset($criteria['mentions']) ? json_decode($criteria['mentions'], true) : null;

        if (is_array($decodedMentions)) {
            foreach ($decodedMentions as $mention) {
                $type = $mention['type'] ?? '';
                $tag = $mention['tag'] ?? '';
                switch ($type) {
                    case 'grz:PersonMention':
                        $namedEntity = $mention['named_entity'] ?? '';
                        $gender = $mention['gender'] ?? '';
                        $geoOriginSestiere = $mention['geo_origin_sestiere'] ?? '';
                        $geoOriginParish = $mention['geo_origin_parish'] ?? '';
                        $mention = [
                            'gender' => preg_match($qNamePattern, $gender) ? $gender : '',
                            'ages' => splitValues($mention['age'] ?? ''),
                            'full_names' => splitValues($mention['full_name'] ?? ''),
                            'named_entity' => preg_match($uuidPattern, $namedEntity) ? $namedEntity : '',
                            'profession_standard_forms' => splitValues($mention['profession_standard_form'] ?? ''),
                            'profession_occupations' => splitValues($mention['profession_occupation'] ?? ''),
                            'profession_category' => intval($mention['profession_category'] ?? ''),
                            'profession_subcategory' => intval($mention['profession_subcategory'] ?? ''),
                            'profession_materials' => splitValues($mention['profession_material'] ?? ''),
                            'profession_products' => splitValues($mention['profession_product'] ?? ''),
                            'geo_origin_standard_forms' => splitValues($mention['geo_origin_standard_form'] ?? ''),
                            'geo_origin_names' => splitValues($mention['geo_origin_name'] ?? ''),
                            'geo_origin_sestiere' => preg_match($qNamePattern, $geoOriginSestiere) ? $geoOriginSestiere : '',
                            'geo_origin_parish' => preg_match($qNamePattern, $geoOriginParish) ? $geoOriginParish : '',
                            'geo_origin_provinces' => splitValues($mention['geo_origin_province'] ?? ''),
                            'geo_origin_countries' => splitValues($mention['geo_origin_country'] ?? ''),
                            'has_details' => ($mention['has_details'] ?? null) ?: 'any',
                            'details' => $mention['details'] ?? '',
                        ];
                        break;
                    case 'grz:WorkshopMention':
                        $sestiere = $mention['sestiere'] ?? '';
                        $parish = $mention['parish'] ?? '';
                        $mention = [
                            'insignias' => splitValues($mention['insignia'] ?? ''),
                            'sites' => splitValues($mention['site'] ?? ''),
                            'sestiere' => preg_match($qNamePattern, $sestiere) ? $sestiere : '',
                            'parish' => preg_match($qNamePattern, $parish) ? $parish : '',
                        ];
                        break;
                    case 'grz:EventMention':
                        $mention = [
                            'after' => $this->parseDate($mention['after'] ?? ''),
                            'before' => $this->parseDate(($mention['before'] ?? ''), 'P1M'),
                            'duration_years' => intval($mention['duration_years'] ?? ''),
                            'duration_months' => intval($mention['duration_months'] ?? ''),
                            'duration_days' => intval($mention['duration_days'] ?? ''),
                            'has_details' => ($mention['has_details'] ?? null) ?: 'any',
                            'details' => $mention['details'] ?? '',
                        ];
                        break;
                    case 'grz:HostingConditionMention':
                        $applicationRule = $mention['application_rule'] ?? '';
                        $mention = [
                            'paid_in_goods' => ($mention['paid_in_goods'] ?? null) ?: 'any',
                            'paid_by' => preg_grep($qNamePattern, splitValues($mention['paid_by'] ?? '')),
                            'periodization' => preg_grep($qNamePattern, splitValues($mention['periodization'] ?? '')),
                            'period' => $mention['period'] ?? '',
                            'application_rule' => preg_match($qNamePattern, $applicationRule) ? $applicationRule : '',
                            'clothing_types' => splitValues($mention['clothing_type'] ?? ''),
                            'has_details' => ($mention['has_details'] ?? null) ?: 'any',
                            'details' => $mention['details'] ?? '',
                        ];
                        break;
                    case 'grz:FinancialConditionMention':
                        $mention = [
                            'paid_in_goods' => ($mention['paid_in_goods'] ?? null) ?: 'any',
                            'paid_by' => preg_grep($qNamePattern, splitValues($mention['paid_by'] ?? '')),
                            'periodization' => preg_grep($qNamePattern, splitValues($mention['periodization'] ?? '')),
                            'period' => $mention['period'] ?? '',
                            'partial_amount' => $mention['partial_amount'] ?? '',
                            'total_amount' => $mention['total_amount'] ?? '',
                            'currency_units' => preg_grep($qNamePattern, splitValues($mention['currency_unit'] ?? '')),
                            'money_information' => splitValues($mention['money_information'] ?? ''),
                            'has_details' => ($mention['has_details'] ?? null) ?: 'any',
                            'details' => $mention['details'] ?? '',
                        ];
                        break;
                    default:
                        continue;
                }
                $mention['tags'] = splitValues($tag, '|', null, $uuidPattern);
                $mention['type'] = $type;
                $mentions[] = $mention;
            }
        }

        return [
            'created_after' => $this->parseDate($criteria['created_after'] ?? ''),
            'created_before' => $this->parseDate(($criteria['created_before'] ?? ''), 'P1M'),
            'on_multiple_pages' => ($criteria['on_multiple_pages'] ?? null) ?: 'any',
            'has_margin' => ($criteria['has_margin'] ?? null) ?: 'any',
            'has_details' => ($criteria['has_details'] ?? null) ?: 'any',
            'details' => $criteria['details'] ?? '',
            'mentions' => $mentions,
        ];
    }
}

// -- End of file
