<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Models\Entity\Annotation;
use Application\Models\Entity\Manifest;
use Application\Models\Entity\ManifestCollection;
use stdClass;

use function Application\deleteDirectory;

/**
 * Class Document
 * @package Application\Controllers\Main
 */
class Document extends Base
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
        if (!$this->hasPermission('view_documents')) {
            $this->request->abort(403);
        }

        $this->view->page_title = $this->text->get('app.documents');

        $currentPage = (int) $this->request->getQuery('page', 1);

        $limit = 10;
        $offset = $limit * ($currentPage - 1);

        $manifest = new Manifest($this->db);
        $collection = new ManifestCollection($this->db);

        $filter = [];
        if ($this->request->getQuery('filter-collection')) {
            $filter['collection'] = $this->request->getQuery('filter-collection');
            $criteria = [['collection_id', '=', $filter['collection']]];
        } else {
            $criteria = [];
        }

        $criteria[] = ['is_active', '=', true];

        $documents = $manifest->findAll(
            $criteria,
            ['id', 'properties', 'code'],
            ['code'],
            $limit, $offset
        )->toArray();

        $allDocumentsCount = $manifest->getRecordCount($criteria);

        if (!$allDocumentsCount) {
            $this->session->addMessage('info',
                $this->text->get('msg_no_documents_found')
            );
        }

        $annotationModel = new Annotation($this->db);
        $annotationStats = new stdClass();
        foreach ($annotationModel->getStatistics('manifest', array_column($documents, 'code')) as $d) {
            $annotationStats->{$d['manifest_code']} = $d;
        }

        $this->view->documents = $documents;
        $this->view->document_groups = $collection->getList('code');
        $this->view->annotation_stats = $annotationStats;

        $this->view->filter = $filter;
        $this->view->current_page = $currentPage;
        $this->view->last_page = ceil($allDocumentsCount / $limit);
        $this->view->documents_per_page = $limit;
        $this->view->documents_count = $allDocumentsCount;
        $this->view->blank_image_url = $this->getImageUrl('square-image.png');
        $this->view->sequence_view_url = $this->getUrl('module', 'sequence/view');
        $this->view->document_view_url = $this->getUrl('controller', 'view');
        $this->view->document_delete_url = $this->getUrl('controller', 'delete');
        $this->view->document_list_url = $this->getUrl('module', 'documents');

        echo $this->view->render('pages/document/index.tpl.php');
    }

    public function create()
    {
        if (!$this->hasPermission('create_documents')) {
            $this->request->abort(403);
        }
        $this->request->redirect($this->getUrl('controller'));
    }

    public function update()
    {
        if (!$this->hasPermission('edit_documents')) {
            $this->request->abort(403);
        }
        $this->request->redirect($this->getUrl('controller'));
    }

    public function view()
    {
        if (!$this->hasAnyPermission('view_documents')) {
            $this->request->abort(403);
        }
        $this->request->redirect($this->getUrl('module', 'documents'));
    }

    public function delete()
    {
        if (!$this->hasPermission('delete_documents')) {
            $this->request->abort(403);
        }

        $manifestId = $this->request->getPost('manifest_id');

        if (!$manifestId) {
            $this->request->abort(400);
        }

        $manifest = new Manifest($this->db);

        $isDeleted = $manifest->delete($manifestId);

        if ($isDeleted) {
            $dir = $this->config->iiif->image->server->dir . '/' . $manifestId;
            if (file_exists($dir) && is_writable($dir)) {
                deleteDirectory($dir);
            }
        }

        $this->setContentType('json');
        echo json_encode(['status' => $isDeleted]);
    }
}

// -- End of file
