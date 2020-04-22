<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Core\Cache\CacheInterface;
use Application\Core\Configuration\Repository;
use Application\Core\Database\Database;
use Application\Core\Foundation\Request;
use Application\Core\Foundation\View;
use Application\Core\Log\LogWriter;
use Application\Core\Session\Session;
use Application\Core\Text\Translator;
use Application\Models\Entity\Annotation;
use Application\Models\Entity\ManifestCollection;
use Application\Providers\Filter;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;
use stdClass;

/**
 * Class Sequence
 * @package Application\Controllers\Main
 */
class Collection extends Base
{
    /**
     * Initializes the class properties.
     *
     * @param Repository $config
     * @param LogWriter $logger
     * @param Request $request
     * @param Translator $text
     * @param CacheInterface $cache
     * @param Database $db
     * @param Session $session
     * @param View $view
     */
    public function __construct(
        Repository $config,
        LogWriter $logger,
        Request $request,
        Translator $text,
        CacheInterface $cache,
        Database $db,
        Session $session,
        View $view
    )
    {
        parent::__construct(
            $config,
            $logger,
            $request,
            $text,
            $cache,
            $db,
            $session,
            $view
        );

        $this->cacheTableData();
    }

    public function index()
    {
        if (!$this->hasAnyPermission('view_documents')) {
            $this->request->abort(403);
        }

        $this->view->page_title = $this->text->get('app.collections');

        $annotationModel = new Annotation($this->db);
        $collectionModel = new ManifestCollection($this->db);

        $annotationStats = new stdClass();
        foreach ($annotationModel->getStatistics('collection') as $c) {
            $annotationStats->{$c['collection_code']} = $c;
        }

        $this->view->collections = $collectionModel->fetchAll([], ['code'])->toArray();
        $this->view->statistics = $collectionModel->getStatistics()->setKeyColumn('code');
        $this->view->annotation_stats = $annotationStats;

        $this->view->collection_index_url = $this->getUrl('controller');
        $this->view->collection_list_url = $this->getUrl('module', 'collections');
        $this->view->document_list_url = $this->getUrl('module', 'documents');
        $this->view->dtlang_url = $this->getUrl('base',
            '/assets/js/datatables/i18n/' . $this->request->getLanguage() . '.json');

        echo $this->view->render('pages/collection/index.tpl.php');
    }

    public function create()
    {
        if (!$this->hasPermission('create_documents')) {
            $this->request->abort(403);
        }

        if ($this->request->isPostRequest()) {
            $submittedData = [
                'code' => $this->request->getPost('code'),
                'description' => $this->request->getPost('description'),
                'type' => $this->request->getPost('type'),
                'material' => $this->request->getPost('material'),
                'start_date' => $this->request->getPost('start_date'),
                'end_date' => $this->request->getPost('end_date'),
                'page_count' => $this->request->getPost('page_count'),
                'geo_origin' => $this->request->getPost('geo_origin'),
            ];

            $filter = new Filter($this->session, $this->text);
            $filter->sanitize($submittedData, [
                [['code', 'description', 'type', 'material', 'start_date', 'end_date', 'geo_origin'], 'string'],
                [['page_count'], 'integer'],
            ]);
            $filter->format($submittedData, [
                [['code', 'description', 'type', 'material', 'start_date', 'end_date', 'geo_origin'], 'trim'],
            ]);
            $isValidData = $filter->validate($submittedData, [
                [['code'], 'required'],
            ]);

            if ($isValidData) {
                $properties = json_encode(array_filter([
                    'code' => $submittedData['code'],
                    'label' => $submittedData['code'],
                    'description' => $submittedData['description'],
                    'metadata' => array_filter([
                        'type' => $submittedData['type'],
                        'material' => $submittedData['material'],
                        'startDate' => $submittedData['start_date'],
                        'endDate' => $submittedData['end_date'],
                        'pageCount' => $submittedData['page_count'],
                        'geoOrigin' => $submittedData['geo_origin'],
                    ]),
                ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $agentId = $this->session->get('auth_user')->get('id');
                $collectionModel = new ManifestCollection($this->db);

                if (!$collectionModel->find([['code', '=', $submittedData['code']]], ['id'])->isEmpty()) {
                    $this->session->addMessage('error', 'A collection with the same code already exists.');
                } elseif ($collectionModel->create($properties, $agentId)) {
                    $this->request->redirect($this->getUrl('module', 'collections'));
                }
            }
        } else {
            $submittedData = [
                'code' => '',
                'description' => '',
                'type' => '',
                'material' => '',
                'start_date' => '',
                'end_date' => '',
                'page_count' => 0,
                'geo_origin' => '',
            ];
        }

        $this->view->action = 'create';
        $this->view->submitted_data = $submittedData;

        echo $this->view->render('pages/collection/update.tpl.php');
    }

    public function update()
    {
        if (!$this->hasPermission('edit_documents')) {
            $this->request->abort(403);
        }

        $collectionId = $this->request->getAttribute(0);

        if (!$collectionId) {
            $this->request->abort(404);
        }

        $collectionModel = new ManifestCollection($this->db);

        $collection = $collectionModel->fetch($collectionId)->decodeJsonValue('properties');

        if ($collection->isEmpty()) {
            $this->request->abort(404);
        }

        if ($this->request->isPostRequest()) {
            $submittedData = [
                'code' => $collection->get('code'),
                'description' => $this->request->getPost('description'),
                'type' => $this->request->getPost('type'),
                'material' => $this->request->getPost('material'),
                'start_date' => $this->request->getPost('start_date'),
                'end_date' => $this->request->getPost('end_date'),
                'page_count' => $this->request->getPost('page_count'),
                'geo_origin' => $this->request->getPost('geo_origin'),
            ];

            $filter = new Filter($this->session, $this->text);
            $filter->sanitize($submittedData, [
                [['description', 'type', 'material', 'start_date', 'end_date', 'geo_origin'], 'string'],
                [['page_count'], 'integer'],
            ]);
            $filter->format($submittedData, [
                [['description', 'type', 'material', 'start_date', 'end_date', 'geo_origin'], 'trim'],
            ]);

            $properties = json_encode(array_filter([
                'code' => $submittedData['code'],
                'label' => $submittedData['code'],
                'description' => $submittedData['description'],
                'metadata' => array_filter([
                    'type' => $submittedData['type'],
                    'material' => $submittedData['material'],
                    'startDate' => $submittedData['start_date'],
                    'endDate' => $submittedData['end_date'],
                    'pageCount' => $submittedData['page_count'],
                    'geoOrigin' => $submittedData['geo_origin'],
                ]),
            ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $agentId = $this->session->get('auth_user')->get('id');

            if ($collectionModel->update($collectionId, ['properties' => $properties], $agentId)) {
                $this->request->redirect($this->getUrl('module', 'collections'));
            }
        } else {
            $metadata = $collection->get('properties.metadata');
            $submittedData = [
                'code' => $collection->get('code'),
                'description' => $collection->get('properties.description'),
                'type' => $metadata['type'] ?? '',
                'material' => $metadata['material'] ?? '',
                'start_date' => $metadata['startDate'] ?? '',
                'end_date' => $metadata['endDate'] ?? '',
                'page_count' => $metadata['pageCount'] ?? 0,
                'geo_origin' => $metadata['geoOrigin'] ?? '',
            ];
        }

        $this->view->action = 'update';
        $this->view->submitted_data = $submittedData;

        echo $this->view->render('pages/collection/update.tpl.php');
    }

    public function view()
    {
        if (!$this->hasAnyPermission('view_documents', 'view_annotations')) {
            $this->request->abort(403);
        }
    }

    public function delete()
    {
        if (!$this->hasPermission('delete_documents')) {
            $this->request->abort(403);
        }

        $collectionId = $this->request->getPost('collection_id');

        if (!$collectionId) {
            $this->request->abort(400);
        }

        $collectionModel = new ManifestCollection($this->db);

        $isDeleted = $collectionModel->delete($collectionId);

        $this->setContentType('json');
        echo json_encode(['status' => $isDeleted]);
    }

    public function import()
    {
        if (!$this->hasAllPermissions('create_documents', 'edit_documents', 'delete_documents')) {
            $this->request->abort(403);
        }

        $this->setContentType('txt');
        $errorMessage = '';
        $filePath = $this->config->dir->temp . 'collections_' . date('Ymd_His') . '.xlsx';
        $maxFileSize = 10 * 1024 * 1024;

        if ($_FILES['dataFile']['size'] > $maxFileSize) {
            $errorMessage = 'The data file is too large. The maximum allowed size is '
                . $this->text->getByteCount($maxFileSize) . '.';
        } elseif ($_FILES['dataFile']['type'] !== 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            $errorMessage = 'Only XLSX files are supported.';
        }

        if (empty($errorMessage)) {
            if (move_uploaded_file($_FILES['dataFile']['tmp_name'], $filePath)) {
                $collections = new stdClass();
                $reader = ReaderFactory::create(Type::XLSX);
                $reader->open($filePath);
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $data) {
                        if (count($data) < 8) {
                            continue;
                        }
                        $code = trim((string) $data[0]);
                        if (!preg_match('/^[0-9]{4}$/', $code)) {
                            continue;
                        }
                        $collections->{$code} = json_encode(array_filter([
                            'code' => $code,
                            'label' => $code,
                            'description' => trim($data[1]),
                            'metadata' => array_filter([
                                'type' => trim($data[2]),
                                'material' => trim($data[3]),
                                'startDate' => trim((string) $data[4]),
                                'endDate' => trim((string) $data[5]),
                                'pageCount' => intval($data[6]),
                                'geoOrigin' => trim($data[7]),
                            ]),
                        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                    if ($sheet->getIndex() === 0) {
                        break;
                    }
                }
                $reader->close();
                unlink($filePath);
                $collectionModel = new ManifestCollection($this->db);

                $importedCollections = new stdClass();
                foreach ($collectionModel->fetchAll(['id', 'code'], ['code'])->toArray() as $c) {
                    $importedCollections->{$c['code']} = $c['id'];
                }

                $agentId = $this->session->get('auth_user')->get('id');
                foreach ($collections as $code => $properties) {
                    $id = $importedCollections->{$code} ?? '';
                    if (empty($id)) {
                        $collectionModel->create($properties, $agentId);
                    } else {
                        $collectionModel->update($id, ['properties' => $properties], $agentId);
                    }
                }
            } else {
                $errorMessage = 'An error occured while uploading the data file.';
            }
        }
        if ($errorMessage) {
            $this->session->addMessage('error', $errorMessage);
        }
        $this->request->redirect($this->getUrl('module', 'collections'));
    }

    public function export()
    {
        $fileName = 'collections_' . date('Ymd_His') . '.xlsx';
        $writer = WriterFactory::create(Type::XLSX);

        $writer->openToBrowser($fileName);

        $collectionModel = new ManifestCollection($this->db);

        $writer->addRow([
            $this->text->get('collection.code'),
            $this->text->get('collection.description'),
            $this->text->get('collection.type'),
            $this->text->get('collection.material'),
            $this->text->get('collection.start_date'),
            $this->text->get('collection.end_date'),
            $this->text->get('collection.page_count'),
            $this->text->get('collection.geo_origin'),
        ]);

        foreach ($collectionModel->fetchAll(['id', 'code', 'properties'], ['code'])->toArray() as $collection) {
            $properties = json_decode($collection['properties'], true);
            $metadata = $properties['metadata'] ?? [];
            $writer->addRow([
                $collection['code'] ?? '',
                $properties['description'] ?? '',
                $metadata['type'] ?? '',
                $metadata['material'] ?? '',
                $metadata['startDate'] ?? '',
                $metadata['endDate'] ?? '',
                (int) ($metadata['pageCount'] ?? 0),
                $metadata['geoOrigin'] ?? '',
            ]);
        }

        $writer->close();
    }
}

// -- End of file
