<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Models\EntityType;
use Application\Models\EntityError;
use Application\Models\Entity\Contract as ContractModel;
use Application\Models\User;
use Application\Providers\DataTablesManager;

use const Application\DATETIME_APP;

/**
 * Class Home
 * @package Application\Controllers\Main
 */
class Data extends Base
{
    protected $entityTypes;

    /**
     * @inheritdoc
     */
    protected function initialize()
    {
        parent::initialize();

        $this->entityTypes = (new EntityType($this->db))->getIndex();
    }

    public function index() {}

    public function download()
    {
        $this->view->page_title = 'Data Download';
        echo $this->view->render('pages/data/download.tpl.php');
    }

    public function validate()
    {
        $category = $this->request->getAttribute(0);

        $this->view->page_title = 'Data Validation';

        if (!$category) {
            echo $this->view->render('pages/data/validate.tpl.php');
        } else {

            $this->view->addStyleSheets(
                $this->getAssetBundleUrls('datatables.css')
            );
            $this->view->addScripts(
                $this->getAssetBundleUrls('datatables.js')
            );

            switch ($category) {
                case 'pages':
                    $this->validatePages();
                    break;
                case 'segments':
                    $this->validateSegments();
                    break;
                case 'contracts':
                    $this->validateContracts();
                    break;
                default:
                    $this->request->abort(404);
            }
        }
    }

    public function getPageErrors()
    {
        $dt = new DataTablesManager($this->db, $this->request);
        $entityErrorModel = new EntityError($this->db);

        $entityTypeId = $this->entityTypes['dhc:Canvas'];

        $errors = $entityErrorModel->get(
            $entityTypeId,
            $dt->getCriteria(),
            $dt->getLimit(),
            $dt->getOffset()
        );

        $users = [];
        foreach ((new User($this->db))->findAll([], ['id', 'full_name'])->toArray() as $user) {
            $users[$user['id']] = $user['full_name'];
        }

        $output = [
            'draw' => $dt->getDrawCount(),
            'recordsTotal' => $entityErrorModel->getCount($entityTypeId, ['error_types' => 'any']),
            'recordsFiltered' => $entityErrorModel->getCount($entityTypeId, $dt->getCriteria()),
            'data' => [],
        ];

        foreach ($errors as $index => $error) {
            $id = $error['canvas_id'] . '_' . $error['error_type_id'];
            $output['data'][] = array_merge(
                $error,
                [
                    'DT_RowId' => 'row_' . $id,
                    'DT_RowData' => ['pkey' => $id],
                    'reviewer' => $users[$error['reviewer_user_id']] ?? '',
                ]
            );
        }

        $this->setContentType('json');
        echo json_encode($output);
    }

    public function getSegmentErrors()
    {
        $dt = new DataTablesManager($this->db, $this->request);
        $entityErrorModel = new EntityError($this->db);

        $entityTypeId = $this->entityTypes['dhc:CanvasSegment'];

        $errors = $entityErrorModel->get(
            $entityTypeId,
            $dt->getCriteria(),
            $dt->getLimit(),
            $dt->getOffset()
        );

        $users = [];
        foreach ((new User($this->db))->findAll([], ['id', 'full_name'])->toArray() as $user) {
            $users[$user['id']] = $user['full_name'];
        }

        $output = [
            'draw' => $dt->getDrawCount(),
            'recordsTotal' => $entityErrorModel->getCount($entityTypeId, ['error_types' => 'any']),
            'recordsFiltered' => $entityErrorModel->getCount($entityTypeId, $dt->getCriteria()),
            'data' => [],
        ];

        foreach ($errors as $index => $error) {
            $id = $error['segment_id'] . '_' . $error['error_type_id'];
            $output['data'][] = array_merge(
                $error,
                [
                    'DT_RowId' => 'row_' . $id,
                    'DT_RowData' => ['pkey' => $id],
                    'reviewer' => $users[$error['reviewer_user_id']] ?? '',
                ]
            );
        }

        $this->setContentType('json');
        echo json_encode($output);
    }

    public function getContractErrors()
    {
        $dt = new DataTablesManager($this->db, $this->request);
        $contractModel = new ContractModel($this->db);
        $entityErrorModel = new EntityError($this->db);

        $entityTypeId = $this->entityTypes['grz:ContractMention'];

        $personUrl = $this->getUrl('module', 'person/view');

        $errors = $entityErrorModel->get(
            $entityTypeId,
            $dt->getCriteria(),
            $dt->getLimit(),
            $dt->getOffset()
        );

        $contractIds = array_values(array_unique(array_column($errors, 'contract_id')));

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
                $contractModel->getSummary($mentions, $personUrl, false)
            );
        }

        $users = [];
        foreach ((new User($this->db))->findAll([], ['id', 'full_name'])->toArray() as $user) {
            $users[$user['id']] = $user['full_name'];
        }

        $output = [
            'draw' => $dt->getDrawCount(),
            'recordsTotal' => $entityErrorModel->getCount($entityTypeId, ['error_types' => 'any']),
            'recordsFiltered' => $entityErrorModel->getCount($entityTypeId, $dt->getCriteria()),
            'data' => [],
        ];

        foreach ($errors as $index => $error) {
            if (isset($contracts[$error['contract_id']])) {
                $contract = $contracts[$error['contract_id']];
            } else {
                continue;
            }
            $id = $error['contract_id'] . '_' . $error['error_type_id'];

            $output['data'][] = array_merge(
                $error,
                [
                    'DT_RowId' => 'row_' . $id,
                    'DT_RowData' => ['pkey' => $id],
                    'date' => $contract['date'],
                    'master' => implode('<br />', $contract['masters'] ?: []),
                    'apprentice' => implode('<br />', $contract['apprentices'] ?: []),
                    'guarantor' => implode('<br />', $contract['guarantors'] ?: []),
                    'manifest_id' => $contract['manifest_id'],
                    'canvas_code' => $contract['canvas_code'],
                    'target_id' => $contract['target_id'],
                    'reviewer' => $users[$error['reviewer_user_id']] ?? '',
                ]
            );
        }

        $this->setContentType('json');
        echo json_encode($output);
    }

    public function updateErrorStatus()
    {
        if (!$this->hasPermission('edit_annotations')) {
            $this->request->abort(403);
        }

        $entityId = $this->request->getPost('entity_id');
        $errorTypeId = $this->request->getPost('error_type_id');
        $errorStatus = $this->request->getPost('error_status');

        if (!$entityId || !$errorTypeId || !$errorStatus) {
            $this->request->abort(404);
        }

        $entityErrorModel = new EntityError($this->db);

        switch ($errorStatus) {
            case 'corrected':
                $errorStatus = true;
                break;
            case 'reviewed':
                $errorStatus = false;
                break;
            case 'unreviewed':
                $errorStatus = null;
                break;
            default:
                $this->request->abort(404);
        }

        $entityErrorModel->updateStatus(
            $entityId,
            (int) $errorTypeId,
            $errorStatus,
            $this->session->get('auth_user')->get('id')
        );

        $this->setContentType('json');
        echo json_encode([
            'status' => $errorStatus,
            'reviewer_user_id' => $this->session->get('auth_user')->get('id'),
            'reviewer' => $this->session->get('auth_user')->get('full_name'),
            'review_time' => date(DATETIME_APP)
        ]);
    }

    protected function validatePages()
    {
        $this->addBreadcrumbs([
            ['title' => 'Data Validation', 'url' => $this->getUrl('controller', 'validate')],
            ['title' => 'Pages']
        ]);
        $entityErrorModel = new EntityError($this->db);
        $entityTypeId = $this->entityTypes['dhc:Canvas'];
        $this->view->errors = $entityErrorModel->getCountByType($entityTypeId);
        echo $this->view->render('pages/data/validate_pages.tpl.php');
    }

    protected function validateSegments()
    {
        $this->addBreadcrumbs([
            ['title' => 'Data Validation', 'url' => $this->getUrl('controller', 'validate')],
            ['title' => 'Segments']
        ]);
        $entityErrorModel = new EntityError($this->db);
        $entityTypeId = $this->entityTypes['dhc:CanvasSegment'];
        $this->view->errors = $entityErrorModel->getCountByType($entityTypeId);
        echo $this->view->render('pages/data/validate_segments.tpl.php');
    }

    protected function validateContracts()
    {
        $this->addBreadcrumbs([
            ['title' => 'Data Validation', 'url' => $this->getUrl('controller', 'validate')],
            ['title' => 'Contracts']
        ]);
        $entityErrorModel = new EntityError($this->db);
        $entityTypeId = $this->entityTypes['grz:ContractMention'];
        $this->view->errors = $entityErrorModel->getCountByType($entityTypeId);
        echo $this->view->render('pages/data/validate_contracts.tpl.php');
    }
}

// -- End of file
