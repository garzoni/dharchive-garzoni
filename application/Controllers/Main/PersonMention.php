<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Core\Type\Map;
use Application\Providers\DataTablesManager;
use Application\Models\Entity\PersonMention as PersionMentionModel;
use Application\Providers\Exporter;
use Application\Providers\SemanticUi;

use function Application\splitValues;

use const Application\MEMORY_LIMIT;
use const Application\REGEX_UUID;

/**
 * Class PersonMention
 * @package Application\Controllers\Main
 */
class PersonMention extends Base
{
    public function index()
    {
        $this->view->addStyleSheets(
            $this->getAssetBundleUrls('datatables.css')
        );
        $this->view->addScripts(
            $this->getAssetBundleUrls('datatables.js')
        );

        $this->view->page_title = $this->text->get('app.person_mentions');

        $this->view->export_url = $this->getUrl('controller', 'export');
        $this->view->custom_export = true;

        echo $this->view->render('pages/person-mention/index.tpl.php');
    }

    public function get()
    {
        $dt = new DataTablesManager($this->db, $this->request);
        $personMentionModel = new PersionMentionModel($this->db);

        $personMentionIds = $personMentionModel->search(
            $dt->getCriteria(),
            $dt->getOrder(),
            $dt->getLimit(),
            $dt->getOffset()
        );
        $personMentions = array_flip($personMentionIds);

        if ($personMentions) {
            foreach ($personMentionModel->getDetailsOfMany($personMentionIds) as $personMention) {
                $id = $personMention['id'];
                $personMention['properties'] = json_decode($personMention['properties'], true);
                $personMentions[$id] = $personMention;
            }
        }

        $output = [
            'draw' => $dt->getDrawCount(),
            'recordsTotal' => $personMentionModel->getRecordCount([['is_active', '=', true]]),
            'recordsFiltered' => $personMentionModel->getSearchResultCount($dt->getCriteria()),
            'data' => [],
        ];

        foreach ($personMentions as $id => $personMention) {
            if (!is_array($personMention)) {
                continue;
            }
            $output['data'][] = [
                'DT_RowId' => 'row_' . $id,
                'DT_RowData' => [
                    'pkey' => $id,
                ],
                'id' => $id,
                'tag' => $personMention['properties']['tag']['labels']['preferred']['en'] ?? '',
                'full_name' => $personMention['properties']['fullName'],
                'person_id' => $personMention['properties']['entity']['id'] ?? '',
                'person_name' => $personMention['properties']['entity']['name'] ?? '',
                'contract_id' => $personMention['contract_id'],
                'manifest_id' => $personMention['manifest_id'],
                'canvas_code' => $personMention['canvas_code'],
                'target_id' => $personMention['target_id'],
            ];
        }

        $this->setContentType('json');
        echo json_encode($output);
    }

    public function getValueList()
    {
        $id = $this->request->getQuery('id');
        $pattern = $this->request->getQuery('pattern');

        $sui = new SemanticUi();
        $personMentionModel = new PersionMentionModel($this->db);

        $this->setContentType('json');

        switch ($id) {
            case 'names':
                $values = $personMentionModel->getNameList($pattern);
                break;
            case 'ages':
                $values = $personMentionModel->getAgeList($pattern);
                break;
            case 'geo_origin_transcripts':
                $values = $personMentionModel->getGeoOriginTranscriptList($pattern);
                break;
            case 'geo_origin_standard_forms':
                $values = $personMentionModel->getGeoOriginStandardFormList($pattern);
                break;
            case 'profession_transcripts':
                $values = $personMentionModel->getProfessionTranscriptList($pattern);
                break;
            case 'profession_standard_forms':
                $values = $personMentionModel->getProfessionStandardFormList($pattern);
                break;
            case 'workshop_sites':
                $values = $personMentionModel->getWorkshopSiteList($pattern);
                break;
            case 'workshop_insignias':
                $values = $personMentionModel->getWorkshopInsigniaList($pattern);
                break;
            default:
                echo $sui->getErrorResponse('Invalid list identifier');
                exit(0);
        }

        echo $sui->getValueList($sui->getKeyValuePairs($values, true));
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

        $personMentionModel = new PersionMentionModel($this->db);
        $personMentionIds = [];

        if ($selection) {
            $personMentionIds = splitValues($selection, ',', null, '/' . REGEX_UUID . '/');
        } elseif ($filters) {
            $personMentionIds = $personMentionModel->search($filters);
        }

        ini_set('memory_limit', MEMORY_LIMIT);

        $personMentions = $personMentionModel->getDetailsOfMany($personMentionIds, true);

        $fileName = 'person_mentions';

        switch($fileFormat) {
            case 'ods':
            case 'xlsx':
                $this->exportSpreadsheet($personMentions, $fileName, $fileFormat);
                break;
            default:
                $this->exportJson($personMentions, $fileName);
        }
    }

    protected function exportSpreadsheet(array $mentions, string $fileName, string $fileFormat)
    {
        $recordsetSchemas = [
            [
                'name' => 'Person Mentions',
                'columns' => [
                    'ID',
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
                    'Contract ID',
                    'Tag',
                ]
            ],
        ];

        $personMentionModel = new PersionMentionModel($this->db);

        $callback = function (&$mention) use ($personMentionModel) {
            $id = $mention['id'];
            $contractId = $mention['contract_id'] ?? null;
            $mention = new Map(json_decode($mention['properties'], true));
            $mention->set('id', $id);
            $mention->set('contract_id', $contractId);
            $data = [
                [
                    $mention->get('id'),
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
                    $mention->get('contract_id'),
                    $mention->get('tag.qualifiedName'),
                ]
            ];
            $mention = [$data];
        };

        $exporter = new Exporter();
        $exporter->exportTable($mentions, $recordsetSchemas, $fileName, $fileFormat, $callback);
    }

    protected function exportJson(array $mentions, string $fileName)
    {
        $callback = function (&$mention) {
            if (isset($mention['properties'])) {
                $id = $mention['id'];
                $contractId = $mention['contract_id'] ?? null;
                $mention = json_decode($mention['properties'], true);
                $mention['id'] = $id;
                $mention['tag'] = $mention['tag']['qualifiedName'] ?? null;
                $mention['entity'] = isset($mention['entity'])
                    ? array_intersect_key($mention['entity'], array_flip(['id', 'name'])) : null;
                $mention['contract_id'] = $contractId;
            }
        };
        $exporter = new Exporter();
        $exporter->exportList($mentions, '', $fileName, 'json', $callback);
    }
}

// -- End of file
