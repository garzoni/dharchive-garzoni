<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Core\Type\Map;
use Application\Core\Type\Json\Schema as JsonSchema;
use Application\Models\Entity;
use Application\Models\Entity\Annotation as AnnotationModel;
use Application\Models\Entity\Contract as ContractModel;
use Application\Models\Entity\EntityList;
use Application\Models\Entity\Manifest;
use Application\Models\Entity\PersonMention;
use Application\Providers\DataTablesManager;
use Application\Providers\Exporter;

use const Application\MEMORY_LIMIT;
use const Application\REGEX_UUID;
use function Application\splitValues;

/**
 * Class Contract
 * @package Application\Controllers\Main
 */
class Contract extends Base
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

        $entityList = new EntityList($this->db);

        $lang = $this->request->getLanguage();

        $this->view->lists = [
            'sestiere' => $entityList->getEntities('grz:Sestriere', 'qualified_name', 'name', null, $lang),
            'currency_unit' => $entityList->getEntities('grz:CurrencyUnit', 'qualified_name', 'label', null, $lang),
            'gender' => $entityList->getEntities('grz:GenderList', 'qualified_name', 'label', null, $lang),
            'payer' => $entityList->getEntities('grz:PayerList', 'qualified_name', 'label', null, $lang),
            'periodization' => $entityList->getEntities('grz:PeriodizationList', 'qualified_name', 'label', null, $lang),
            'application_rule' => $entityList->getEntities('grz:ApplicationRuleList', 'qualified_name', 'label', null, $lang),
            'tag' => [
                'person' => $entityList->getEntities('grz:PersonMentionTagList', 'id', 'preferred_label', null, $lang),
                'event' => $entityList->getEntities('grz:EventMentionTagList', 'id', 'preferred_label', null, $lang),
                'hosting_condition' => $entityList->getEntities('grz:HostingConditionMentionTagList', 'id', 'preferred_label', null, $lang),
                'financial_condition' => $entityList->getEntities('grz:FinancialConditionMentionTagList', 'id', 'preferred_label', null, $lang),
            ],
        ];

        $this->view->page_title = $this->text->get('app.contracts');
        $this->view->export_url = $this->getUrl('controller', 'export');
        $this->view->custom_export = true;
        $this->view->contracts = new Map;

        echo $this->view->render('pages/contract/index.tpl.php');
    }

    public function get()
    {
        $personUrl = $this->getUrl('module', 'person/view');

        $dt = new DataTablesManager($this->db, $this->request);
        $contractModel = new ContractModel($this->db);

        $contractIds = $contractModel->search(
            $dt->getCriteria(),
            $dt->getOrder(),
            $dt->getLimit(),
            $dt->getOffset()
        );
        $contracts = array_flip($contractIds);

        foreach ($contractModel->getDetailsOfMany($contractIds) as $contract) {
            $id = $contract['contract_id'];
            $mentions = json_decode($contract['mentions'], true);
            $contracts[$id] = array_merge(
                [
                    'manifest_id' => $contract['manifest_id'],
                    'canvas_code' => $contract['canvas_code'],
                    'target_id' => $contract['target_id'],
                ],
                $contractModel->getSummary($mentions, $personUrl)
            );
        }

        $output = [
            'draw' => $dt->getDrawCount(),
            'recordsTotal' => $contractModel->getRecordCount([['is_active', '=', true]]),
            'recordsFiltered' => $contractModel->getSearchResultCount($dt->getCriteria()),
            'data' => [],
        ];

        foreach ($contracts as $id => $contract) {
            if (!is_array($contract)) {
                continue;
            }
            $output['data'][] = [
                'DT_RowId' => 'row_' . $id,
                'DT_RowData' => [
                    'pkey' => $id,
                ],
                'id' => $id,
                'date' => $contract['date'],
                'master' => implode('<br />', $contract['masters']),
                'apprentice' => implode('<br />', $contract['apprentices']),
                'guarantor' => implode('<br />', $contract['guarantors']),
                'manifest_id' => $contract['manifest_id'],
                'canvas_code' => $contract['canvas_code'],
                'target_id' => $contract['target_id'],
            ];
        }

        $this->setContentType('json');
        echo json_encode($output);
    }

    public function view()
    {
        $contractId = $this->request->getAttribute(0);

        if (!$contractId) {
            $this->request->abort(404);
        }

        $this->view->page_title = $this->text->get('app.contracts');

        $this->view->addStyleSheets($this->getAssetBundleUrls('prism.css'));
        $this->view->addScripts($this->getAssetBundleUrls('prism.js'));

        $entity = new Entity($this->db);
        $entityList = new EntityList($this->db);
        $manifest = new Manifest($this->db);
        $personMention = new PersonMention($this->db);
        $contractModel = new ContractModel($this->db);
        $contract = $contractModel->getDetails($contractId);

        if ($contract->isEmpty()) {
            $this->request->abort(404);
        }

        $contract = $contract->toArray();

        $schemas = [
            'person_mention' => $personMention->getDisplaySchema(),
        ];
        $mentionTypes = [
            'contract_mention' => 'grz:ContractMention',
            'event_mention' => 'grz:EventMention',
            'hosting_condition_mention' => 'grz:HostingConditionMention',
            'financial_condition_mention' => 'grz:FinancialConditionMention',
        ];
        foreach ($mentionTypes as $key => $qname) {
            $schema = new JsonSchema($entity->getTypeSchema($qname));
            $schemas[$key] = $schema->getBasicSchema();
        }

        $entityTypeLists = [
            ['dhc:Token', 'qualified_name', 'label'],
            ['grz:CurrencyUnit', 'qualified_name', 'label'],
            ['grz:Sestriere', 'qualified_name', 'name'],
            ['grz:Parish', 'qualified_name', 'name'],
        ];
        $entityNames = $entityList->getAssortedEntities($entityTypeLists, $this->request->getLanguage());

        $this->view->contract = $contract;
        $this->view->contract_model = $contractModel;
        $this->view->document = $manifest->fetch($contract['manifest_id'])->decodeJsonValue('properties');
        $this->view->schemas = $schemas;
        $this->view->entity_names = $entityNames;
        $this->view->professions = $contractModel->getMentionedProfessions($contractId);
        $this->view->locations = $contractModel->getMentionedLocations($contractId);
        $this->view->export_url = $this->getUrl('controller', 'export?id=' . $contractId);

        echo $this->view->render('pages/contract/view.tpl.php');
    }

    public function export()
    {
        $fileFormat = $this->request->getParam('format', 'json');
        $selection = $this->request->getParam('id', '');
        $filters = $this->request->getDecodedParam('filters', true);
        $query = $this->request->getDecodedParam('query');

        if (!in_array($fileFormat, ['xlsx', 'ods', 'json'])) {
            $this->request->abort(400);
        }

        $filters = array_filter($filters, 'strlen');
        if (($filters['mentions'] ?? '') === '[]') {
            unset($filters['mentions']);
        }

        $annotationModel = new AnnotationModel($this->db);
        $contractModel = new ContractModel($this->db);
        $contractIds = [];

        if ($selection) {
            $contractIds = splitValues($selection, ',', null, '/' . REGEX_UUID . '/');
        } elseif ($filters) {
            $contractIds = $contractModel->search($filters);
        } elseif ($query) {
            $canvasObjectIds = [];
            foreach ($annotationModel->search($query, [], 'canvas_object', true) as $r) {
                $canvasObjectIds[] = $r['target_id'];
            }
            $contractIds = array_values($contractModel->getContractIds($canvasObjectIds));
        }

        ini_set('memory_limit', MEMORY_LIMIT);

        $contracts = $contractModel->getDetailsOfMany($contractIds, true);
        $sortedContracts = array_flip(array_column($contractModel->findAll(
            (!empty($contractIds) ? [['id', 'in', $contractIds]] : []), ['id', 'date'], ['date', 'id']
        )->toArray(), 'id'));

        foreach ($contracts as $contract) {
            if (isset($sortedContracts[$contract['contract_id']])) {
                $sortedContracts[$contract['contract_id']] = $contract;
            }
        }

        $contracts = array_filter($sortedContracts, 'is_array');
        $fileName = 'contracts';

        switch($fileFormat) {
            case 'ods':
            case 'xlsx':
                $this->exportSpreadsheet($contracts, $fileName, $fileFormat);
                break;
            default:
                $this->exportJson($contracts, $fileName);
        }
    }

    protected function exportSpreadsheet(array $contracts, string $fileName, string $fileFormat)
    {
        $recordsetSchemas = [
            [
                'name' => 'Contracts',
                'columns' => [
                    'Contract ID',
                    'Register',
                    'Page',
                    'Date',
                ]
            ],
            [
                'name' => 'Person Mentions',
                'columns' => [
                    'Contract ID',
                    'Tag',
                    'Full Name',
                    'Gender',
                    'Age',
                    'Geo Origin - Transcript',
                    'Geo Origin - Standard Form',
                    'Geo Origin - Parish',
                    'Residence - Transcript',
                    'Residence - Standard Form',
                    'Residence - Parish',
                    'Workshop - Insigna',
                    'Workshop - Site',
                    'Workshop - Parish',
                    'Professions - Transcripts',
                    'Professions - Standard Forms',
                    'Details',
                    'Person ID',
                    'Person Name',
                    'Mention ID',
                ]
            ],
            [
                'name' => 'Hosting Conditions',
                'columns' => [
                    'Contract ID',
                    'Tag',
                    'Paid by',
                    'Paid in Goods',
                    'Application Rule',
                    'Periodization',
                    'Specific Period',
                    'Type of Clothing',
                    'Details',
                    'Mention ID',
                ]
            ],
            [
                'name' => 'Financial Conditions',
                'columns' => [
                    'Contract ID',
                    'Tag',
                    'Paid by',
                    'Paid in Goods',
                    'Periodization',
                    'Specific Period',
                    'Currency',
                    'Money Information',
                    'Partial Amount',
                    'Total Amount',
                    'Details',
                    'Mention ID',
                ]
            ],
            [
                'name' => 'Events',
                'columns' => [
                    'Contract ID',
                    'Tag',
                    'Start Date',
                    'End Date',
                    'Duration - Years',
                    'Duration - Months',
                    'Duration - Days',
                    'Denunciation Date',
                    'Details',
                    'Mention ID',
                ]
            ],
        ];

        $manifestModel = new Manifest($this->db);
        $personMentionModel = new PersonMention($this->db);

        $documents = [];
        foreach ($manifestModel->findAll([], ['id', 'properties'])->toArray() as $document) {
            $documents[$document['id']] = json_decode($document['properties'], true);
        }

        $callback = function (&$contract) use ($recordsetSchemas, $documents, $personMentionModel) {
            $contract = new Map($contract);
            $document = new Map($documents[$contract->get('manifest_id')]);
            $mentions = [];

            if (!$contract->get('contract_id')) {
                return [];
            }

            $records = [];
            foreach ($recordsetSchemas as $index => $schema) {
                $records[$index] = [];
            }

            foreach (json_decode($contract->get('mentions'), true) as $mention) {
                $type = $mention['instanceOf'] ?? '';
                if (empty($type)) {
                    continue;
                }
                if (!isset($mentions[$type])) {
                    $mentions[$type] = [];
                }
                $mentions[$type][] = new Map($mention);
            }

            $contractMention = $mentions['grz:ContractMention'][0] ?? new Map();

            $records[0][] = [
                $contract->get('contract_id'),
                $document->get('metadata.register'),
                $contract->get('canvas_code'),
                $contractMention->get('date'),
            ];
            if (isset($mentions['grz:PersonMention'])) {
                foreach ($mentions['grz:PersonMention'] as $mention) {
                    $records[1][] = [
                        $contract->get('contract_id'),
                        $mention->get('tag.qualifiedName'),
                        $mention->get('fullName'),
                        $mention->get('gender'),
                        $mention->get('age'),
                        $mention->get('geoOrigin.transcript'),
                        $mention->get('geoOrigin.standardForm'),
                        $mention->get('geoOrigin.parish'),
                        $mention->get('residence.transcript'),
                        $mention->get('residence.standardForm'),
                        $mention->get('residence.parish'),
                        $mention->get('workshop.insigna'),
                        $mention->get('workshop.site'),
                        $mention->get('workshop.parish'),
                        $personMentionModel->getProfessionSummary($mention->toArray(), false),
                        $personMentionModel->getProfessionSummary($mention->toArray(), true),
                        $mention->get('details'),
                        $mention->get('entity.id'),
                        $mention->get('entity.name'),
                        $mention->get('id'),
                    ];
                }
            }
            if (isset($mentions['grz:HostingConditionMention'])) {
                foreach ($mentions['grz:HostingConditionMention'] as $mention) {
                    $records[2][] = [
                        $contract->get('contract_id'),
                        $mention->get('tag.qualifiedName'),
                        $mention->get('paidBy'),
                        $mention->get('paidInGoods'),
                        $mention->get('applicationRule'),
                        $mention->get('periodization'),
                        $mention->get('period'),
                        $mention->get('clothingType'),
                        $mention->get('details'),
                        $mention->get('id'),
                    ];
                }
            }
            if (isset($mentions['grz:FinancialConditionMention'])) {
                foreach ($mentions['grz:FinancialConditionMention'] as $mention) {
                    $records[3][] = [
                        $contract->get('contract_id'),
                        $mention->get('tag.qualifiedName'),
                        $mention->get('paidBy'),
                        $mention->get('paidInGoods'),
                        $mention->get('periodization'),
                        $mention->get('period'),
                        $mention->get('currencyUnit'),
                        $mention->get('moneyInformation'),
                        $mention->get('partialAmount'),
                        $mention->get('totalAmount'),
                        $mention->get('details'),
                        $mention->get('id'),
                    ];
                }
            }
            if (isset($mentions['grz:EventMention'])) {
                foreach ($mentions['grz:EventMention'] as $mention) {
                    $records[4][] = [
                        $contract->get('contract_id'),
                        $mention->get('tag.qualifiedName'),
                        $mention->get('startDate'),
                        $mention->get('endDate'),
                        $mention->get('duration.years'),
                        $mention->get('duration.months'),
                        $mention->get('duration.days'),
                        $mention->get('denunciationDate'),
                        $mention->get('details'),
                        $mention->get('id'),
                    ];
                }
            }
            $contract = $records;
        };

        $exporter = new Exporter();
        $exporter->exportTable($contracts, $recordsetSchemas, $fileName, $fileFormat, $callback);
    }

    protected function exportJson(array $contracts, string $fileName)
    {
        $callback = function (&$contract) {
            if (isset($contract['mentions'])) {
                $mentions = json_decode($contract['mentions'], true);
                foreach ($mentions as &$mention) {
                    $mention['tag'] = $mention['tag']['qualifiedName'] ?? null;
                    $mention['entity'] = isset($mention['entity'])
                        ? array_intersect_key($mention['entity'], array_flip(['id', 'name'])) : null;
                }
                $contract['mentions'] = $mentions;
            }
        };
        $exporter = new Exporter();
        $exporter->exportList($contracts, '', $fileName, 'json', $callback);
    }
}

// -- End of file
