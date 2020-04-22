<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Core\Type\Map;
use Application\Core\Type\Table;
use Application\Models\Entity\Contract as ContractModel;
use Application\Models\Entity\Person as PersonModel;
use Application\Providers\DataTablesManager;
use Application\Providers\Exporter;

use function Application\splitValues;

use const Application\MEMORY_LIMIT;
use const Application\REGEX_UUID;

/**
 * Class Person
 * @package Application\Controllers\Main
 */
class Person extends Base
{
    /**
     * @inheritdoc
     */
    protected function initialize()
    {
        parent::initialize();

        $this->cacheTableData();
    }

    public function index()
    {
        $this->view->addStyleSheets(
            $this->getAssetBundleUrls('datatables.css')
        );
        $this->view->addScripts(
            $this->getAssetBundleUrls('datatables.js')
        );

        $personModel = new PersonModel($this->db);

        $this->view->page_title = $this->text->get('app.persons');
        $this->view->persons = new Map;
        $this->view->relation_types = $personModel->getRelationTypes();

        $this->view->export_url = $this->getUrl('controller', 'export');
        $this->view->custom_export = true;

        echo $this->view->render('pages/person/index.tpl.php');
    }

    public function get()
    {
        $dt = new DataTablesManager($this->db, $this->request);
        $personModel = new PersonModel($this->db);

        $personIds = $personModel->search(
            $dt->getCriteria(),
            $dt->getOrder(),
            $dt->getLimit(),
            $dt->getOffset()
        );
        $persons = array_flip($personIds);

        if ($persons) {
            foreach ($personModel->findAll([['id', 'in', $personIds]])->toArray() as $person) {
                $id = $person['id'];
                $persons[$id] = json_decode($person['properties'], true);
            }
        }

        $output = [
            'draw' => $dt->getDrawCount(),
            'recordsTotal' => $personModel->getRecordCount([['is_active', '=', true]]),
            'recordsFiltered' => $personModel->getSearchResultCount($dt->getCriteria()),
            'data' => [],
        ];

        foreach ($persons as $id => $person) {
            if (!is_array($person)) {
                continue;
            }
            $output['data'][] = [
                'DT_RowId' => 'row_' . $id,
                'DT_RowData' => [
                    'pkey' => $id,
                ],
                'id' => $id,
                'name' => $person['name'],
            ];
        }

        $this->setContentType('json');
        echo json_encode($output);
    }

    public function getData()
    {
        $personModel = new PersonModel($this->db);

        $personId = $this->request->getAttribute(0);

        if (!$personId) {
            $this->request->abort(404);
        }

        $person = $personModel->fetch($personId, ['id', 'properties']);

        if ($person->isEmpty()) {
            $this->request->abort(404);
        }

        $person->decodeJsonValue('properties');

        $data = [
            'id' => $personId,
            'url' => $this->getUrl('module', 'person/view/' . $personId),
            'name' => $person->get('properties.name'),
        ];

        $data = array_merge($data, $this->getLocalData($personId));

        foreach ($data['mentions'] as $i => $mention) {
            $data['mentions'][$i]['url'] =
                $this->buildMentionUrl($mention, $data['name']) ?: null;
        }

        $this->setContentType('json');

        echo json_encode($data);
    }

    public function view()
    {
        $personId = $this->request->getAttribute(0);

        if (!$personId) {
            $this->request->abort(404);
        }

        $this->view->page_title = $this->text->get('app.persons');

        $contractModel = new ContractModel($this->db);
        $personModel = new PersonModel($this->db);

        $person = $personModel->fetch($personId);
        $properties = json_decode($person->get('properties'), true);

        if (isset($properties['relationships'])) {
            $relations = [];
            $relatedPersonIds = [];
            foreach($properties['relationships'] as $relation) {
                if (isset($relation['person'])) {
                    $relatedPersonIds[] = $relation['person'];
                }
            }
            if ($relatedPersonIds) {
                $relatedPersons = [];
                foreach ($personModel->findAll(
                    [['id', 'in', $relatedPersonIds]], ['id', 'name']
                    )->toArray() as $p) {
                    $relatedPersons[$p['id']] = $p['name'];
                }
                $relationTypes = $personModel->getRelationTypes();
                foreach($properties['relationships'] as $relation) {
                    $type = $this->text->resolve($relationTypes[$relation['relationType']]);
                    $personId = $relation['person'] ?? '';
                    $personName = $relatedPersons[$personId] ?? '';
                    $relations[] = $type . ' <a href="'
                        . $this->getUrl('action', $personId)
                        . '">' . $personName . '</a>';
                }
            }
            $properties['relationships'] = $relations;
        }

        $person->set('properties', $properties);

        $contracts = new Table($contractModel->getDetailsOfMany($personModel->getContracts($personId)));

        if (empty($person)) {
            $this->request->abort(404);
        }

        $this->view->person = $person;
        $this->view->contracts = $contracts;
        $this->view->contract_model = $contractModel;

        echo $this->view->render('pages/person/view.tpl.php');
    }

    public function export()
    {
        $fileFormat = $this->request->getParam('format', 'json');
        $selection = $this->request->getParam('id', '');
        $filters = $this->request->getDecodedParam('filters', true);

        if (!in_array($fileFormat, ['xlsx', 'ods', 'json'])) {
            $this->request->abort(400);
        }

        $filters = array_filter($filters, 'strlen');
        if (($filters['relations'] ?? '') === '[]') {
            unset($filters['relations']);
        }

        $personModel = new PersonModel($this->db);
        $personIds = [];

        if ($selection) {
            $personIds = splitValues($selection, ',', null, '/' . REGEX_UUID . '/');
        } elseif ($filters) {
            $personIds = $personModel->search($filters);
        }

        ini_set('memory_limit', MEMORY_LIMIT);

        $criteria = !empty($personIds) ? [['id', 'in', $personIds]] : [];
        $persons = $personModel->findAll($criteria)->toArray();

        $fileName = 'persons';

        switch($fileFormat) {
            case 'ods':
            case 'xlsx':
                $this->exportSpreadsheet($persons, $fileName, $fileFormat);
                break;
            default:
                $this->exportJson($persons, $fileName);
        }
    }

    public function exportRelationships()
    {
        $fileFormat = $this->request->getParam('format', 'json');

        if (!in_array($fileFormat, ['xlsx', 'ods', 'json'])) {
            $this->request->abort(400);
        }

        ini_set('memory_limit', MEMORY_LIMIT);

        $personModel = new PersonModel($this->db);
        $relationships = $personModel->getAllRelationships()->toArray();

        $fileName = 'person_relationships';

        switch($fileFormat) {
            case 'ods':
            case 'xlsx':
                $this->exportRelationshipsSpreadsheet($relationships, $fileName, $fileFormat);
                break;
            default:
                $this->exportRelationshipsJson($relationships, $fileName);
        }
    }

    protected function exportSpreadsheet(array $persons, string $fileName, string $fileFormat)
    {
        $recordsetSchemas = [
            [
                'name' => 'Persons',
                'columns' => [
                    'ID',
                    'Name',
                ]
            ],
        ];

        $callback = function (&$person) {
            $id = $person['id'];
            $person = new Map(json_decode($person['properties'], true));
            $person->set('id', $id);
            $data = [
                [
                    $person->get('id'),
                    $person->get('name'),
                ]
            ];
            $person = [$data];
        };

        $exporter = new Exporter();
        $exporter->exportTable($persons, $recordsetSchemas, $fileName, $fileFormat, $callback);
    }

    protected function exportJson(array $persons, string $fileName)
    {
        $callback = function (&$person) {
            if (isset($person['properties'])) {
                $id = $person['id'] ?? null;
                $person = json_decode($person['properties'], true);
                $person['id'] = $id;
            }
        };
        $exporter = new Exporter();
        $exporter->exportList($persons, '', $fileName, 'json', $callback);
    }

    protected function exportRelationshipsSpreadsheet(array $relationships, string $fileName, string $fileFormat)
    {
        $recordsetSchemas = [
            [
                'name' => 'Person Relationships',
                'columns' => [
                    'Person 1 ID',
                    'Person 1 Name',
                    'Relationship Type',
                    'Person 2 ID',
                    'Person 2 Name',
                ]
            ],
        ];

        $callback = function (&$relationship) {
            $relationship = new Map($relationship);
            $data = [
                [
                    $relationship->get('person1_id'),
                    $relationship->get('person1_name'),
                    $relationship->get('relationship'),
                    $relationship->get('person2_id'),
                    $relationship->get('person2_name'),
                ]
            ];
            $relationship = [$data];
        };

        $exporter = new Exporter();
        $exporter->exportTable($relationships, $recordsetSchemas, $fileName, $fileFormat, $callback);
    }

    protected function exportRelationshipsJson(array $relationships, string $fileName)
    {
        $exporter = new Exporter();
        $exporter->exportList($relationships, '', $fileName, 'json');
    }

    /**
     * @param string $personId
     * @return array
     */
    protected function getLocalData(string $personId): array
    {
        $personModel = new PersonModel($this->db);

        $mentionIds = $personModel->getMentionList($personId);
        $mentionTags = $personModel->getMentionTags($mentionIds);
        $mentionContexts = $personModel->getMentionContexts($mentionIds);

        $data = [
            'fatherName' => $this->getFatherName($personId) ?: null,
            'mentions' => [],
        ];

        foreach($mentionContexts->findAll(
            [['id', 'in', $mentionIds]])->toArray() as $mention) {
            $properties = new Map(
                json_decode($mention['properties'], true)
            );
            $contractMention = $mentionContexts->find(
                [
                    ['target_id', '=', $mention['target_id']],
                    ['type', '=', 'grz:ContractMention']
                ],
                ['id', 'properties']
            )->decodeJsonValue('properties');
            $tag = $mentionTags->find(
                [['mention_id', '=', $mention['id']]],
                ['id', 'properties']
            )->decodeJsonValue('properties');
            $data['mentions'][] = [
                'date' => $contractMention->get('properties.date'),
                'role' => strtolower(
                    $tag->get('properties.labels.preferred.en', '')
                ),
                'profession' => $properties->get('professions.0.transcript'),
                'insigna' => $properties->get('workshop.insigna'),
                'parish' => $properties->get('workshop.parish'),
                'geoOrigin' => $properties->get('geoOrigin.transcript'),
                'manifestUuid' => $mention['manifest_id'],
                'canvasCode' => $mention['canvas_code'],
            ];
        };

        return $data;
    }

    /**
     * @param string $personId
     * @return string
     */
    protected function getFatherName(string $personId): string
    {
        $personModel = new PersonModel($this->db);

        $relationships = $personModel->getRelationships($personId);
        if ($relationships->isEmpty()) {
            return '';
        }
        $relList = $relationships->getList('person1_name', [
            ['relationship', '=', 'grz:isFatherOf'],
            ['person2_id', '=', $personId],
        ]);
        if (empty($relList)) {
            $relList = $relationships->getList('person2_name', [
                ['relationship', '=', 'grz:isSonOf'],
                ['person1_id', '=', $personId],
            ]);
        }

        return reset($relList) ?: '';
    }

    /**
     * @param array $mention
     * @param string $name
     * @return string
     */
    protected function buildMentionUrl(array $mention, string $name = ''): string
    {
        if (empty($mention)
            || empty($mention['manifestUuid'])
            || empty($mention['canvasCode'])) {
            return '';
        }
        $url = $this->getUrl('module', 'page/view/'
            . $mention['manifestUuid'] . '/' . $mention['canvasCode']);
        if (!empty($name)) {
            $url .= '?find=' . urlencode($name);
        }
        return $url;
    }
}

// -- End of file
