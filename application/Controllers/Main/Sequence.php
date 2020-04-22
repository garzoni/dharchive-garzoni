<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Models\Entity\CanvasSequence;
use Application\Models\Entity\Annotation;
use Application\Models\Entity\Manifest;
use stdClass;

/**
 * Class Sequence
 * @package Application\Controllers\Main
 */
class Sequence extends Base
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
        if (!$this->hasAnyPermission('view_documents')) {
            $this->request->abort(403);
        }
    }

    public function create()
    {
        if (!$this->hasPermission('edit_documents')) {
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

        $manifestId = $this->request->getAttribute(0);
        $sequenceCode = $this->request->getAttribute(1);

        if (!$manifestId || !$sequenceCode) {
            $this->request->abort(404);
        }

        $currentPage = (int) $this->request->getQuery('page', 1);

        $limit = 10;
        $offset = $limit * ($currentPage - 1);

        $manifestModel = new Manifest($this->db);
        $sequenceModel = new CanvasSequence($this->db);

        $manifest = $manifestModel->fetch($manifestId)
            ->decodeJsonValue('properties');
        $documentLabel = $manifest->get('properties.label');

        $sequence = $sequenceModel->find([
            ['manifest_id', '=', $manifestId],
            ['code', '=', $sequenceCode],
        ]);

        $pages = $sequenceModel->getCanvases(
            $sequence->get('id'), $limit, $offset
        )->toArray();

        $allPagesCount = $sequenceModel->getCanvasCount($sequence->get('id'));

        if (!$allPagesCount) {
            $this->session->addMessage('info',
                $this->text->get('msg_no_pages_found')
            );
        }

        $annotationModel = new Annotation($this->db);
        $annotationStats = new stdClass();
        foreach ($annotationModel->getStatistics('canvas', [$manifest->get('properties.code')]) as $d) {
            $annotationStats->{$d['canvas_code']} = $d;
        }

        $this->view->page_title = $this->view->title = $documentLabel;

        $this->view->document = $manifest;
        $this->view->sequence = $sequence;
        $this->view->pages = $pages;

        $this->view->annotation_stats = $annotationStats;

        $this->view->current_page = $currentPage;
        $this->view->last_page = ceil($allPagesCount / $limit);
        $this->view->pages_per_page = $limit;
        $this->view->pages_count = $allPagesCount;
        $this->view->blank_image_url = $this->getImageUrl('square-image.png');
        $this->view->current_sequence_url = $this->getUrl('attributes');
        $this->view->canvas_index_url = $this->getUrl('module', 'page');

        echo $this->view->render('pages/sequence/view.tpl.php');
    }
}

// -- End of file
