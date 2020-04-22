<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Core\Type\Map;
use Application\Models\Entity\Annotation;
use Application\Models\Entity\Canvas;
use Application\Models\Entity\CanvasSequence;
use Application\Models\Entity\Image;
use Application\Models\Entity\Manifest;
use Imagick;

use const Application\REGEX_UUID;

/**
 * Class Page
 * @package Application\Controllers\Main
 */
class Page extends Base
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

        $this->view->addScripts(
            $this->getAssetBundleUrls('annotation.js'),
            ['position' => 'head']
        );

        $manifest = new Manifest($this->db);
        $sequence = new CanvasSequence($this->db);

        $manifestId = $this->request->getAttribute(0);
        $canvasCode = $this->request->getAttribute(1);

        if (!$manifestId || !$canvasCode) {
            $this->request->abort(404);
        }

        $document = new Map;
        if (strlen($manifestId) === 9) {
            $document = $manifest->findByCode($manifestId);
            $manifestId = $document->get('id');
        } elseif (preg_match('/' . REGEX_UUID . '/', $manifestId)) {
            $document = $manifest->fetch($manifestId);
        } else {
            $this->request->abort(400);
        }

        if ($document->isEmpty()) {
            $this->request->abort(404);
        }

        $document->decodeJsonValue('properties');

        $this->view->document = $manifest->fetch($manifestId)
            ->decodeJsonValue('properties');
        $documentLabel = $this->view->document->get('properties.label');

        $this->view->sequence = $sequence->find([
            ['manifest_id', '=', $manifestId],
            ['code', '=', 'normal'],
        ]);

        $pageRecords = $sequence->getCanvases(
            $this->view->sequence->get('id')
        )->toArray();

        $pages = [];
        $pageMarkers = [];
        $currentPage = null;
        foreach ($pageRecords as $page) {
            $page['properties'] = json_decode($page['properties'], true);
            $pageCode = $page['properties']['code'];
            if (!is_null($currentPage) && !isset($pageMarkers['next'])) {
                $pageMarkers['next'] = $pageCode;
            }
            if ($pageCode === $canvasCode) {
                $currentPage = new Map($page);
            }
            if (!isset($pageMarkers['first'])) {
                $pageMarkers['first'] = $pageCode;
            } elseif ($pageCode === $canvasCode) {
                end($pages);
                $pageMarkers['previous'] = key($pages);
            }
            $pageMarkers['last'] = $pageCode;
            $pages[$pageCode] = $page;
        }

        if (is_null($currentPage)) {
            $this->request->abort(404);
        }

        $keywords = $this->request->getQuery('find') ?: '';
        $highlights = [];
        foreach (explode(';', $this->request->getQuery('highlight') ?: '') as $targetId) {
            if (preg_match('/' . REGEX_UUID . '/', $targetId)) {
                $highlights[] = $targetId;
            }
        }

        $annotationModel = new Annotation($this->db);

        $searchResults = $keywords ? $annotationModel->search(
            $keywords,
            ['canvas_id' => $currentPage['id']],
            'canvas_object',
            true
        ) : [];

        $this->view->search_keywords = $keywords;
        $this->view->highlights = array_merge($highlights, array_column($searchResults, 'target_id'));

        $this->view->pages = $pages;

        $this->view->first_page = $pageMarkers['first'];
        $this->view->last_page = $pageMarkers['last'];
        $this->view->previous_page = $pageMarkers['previous'] ?? null;
        $this->view->next_page = $pageMarkers['next'] ?? null;

        $this->view->canvas = $currentPage;
        $this->view->canvas_code = $canvasCode;

        $this->view->page_title = $this->view->title = $documentLabel
            . ', ' . $canvasCode;
        $this->view->document_title = $documentLabel;
        $this->view->documents_list_url = $this->getUrl('module', 'documents');
        $this->view->segment_index_url = $this->getUrl('module', 'segment');
        $this->view->annotation_index_url = $this->getUrl('module', 'annotation');
        $this->view->entity_index_url = $this->getUrl('module', 'entity');
        $this->view->canvas_view_url = $this->getUrl('module',
            'page/view/' . $manifestId . '/'
        );
        $this->view->canvas_segments_url = $this->getUrl('language',
            'iiif/pres/' . $manifestId . '/segments/' . $canvasCode
        );

        $this->view->export_url = $this->getUrl('controller',
            'export/' . $manifestId . '/' . $canvasCode
        );
        $this->view->canvas_image = $currentPage->get('properties.thumbnail.@id');

        $this->view->entity_type_url = $this->getUrl('module', 'entity-type');
        $this->view->value_list_url = $this->getUrl('module', 'value-list');
        $this->view->annotation_rules = $this->config->annotation->rules->toArray();

        echo $this->view->render('pages/page/view.tpl.php');
    }

    public function export()
    {
        if (!$this->hasAnyPermission('view_documents')) {
            $this->request->abort(403);
        }

        $manifest = new Manifest($this->db);
        $canvas = new Canvas($this->db);

        $manifestId = $this->request->getAttribute(0);
        $canvasCode = $this->request->getAttribute(1);
        $exportType = $this->request->getAttribute(2);

        if (!$manifestId || !$canvasCode || !$exportType) {
            $this->request->abort(400);
        }

        $page = $canvas->findByManifestKeys(
            $manifestId,
            $canvasCode,
            ['id', 'code', 'thumbnail']
        );

        if (!$page) {
            $this->request->abort(404);
        }

        $document = $manifest->fetch($manifestId, ['code']);
        $fileName = $document->get('code') . '_'
            . str_pad(ltrim($page->get('code', ''), 'p'), 4, '0', STR_PAD_LEFT);
        $dataUrl = $this->getUrl('base', 'iiif/pres/' . $manifestId);
        $contentType = 'json';
        $content = '';

        switch ($exportType) {
            case 'image':
                if ($this->request->getAttributeCount() !== 7) {
                    $this->request->abort(400);
                }
                $region = $this->request->getAttribute(3);
                $size = $this->request->getAttribute(4);
                $rotation = $this->request->getAttribute(5);
                list($quality, $contentType) = explode('.',
                    $this->request->getAttribute(6));

                $page->decodeJsonValue('thumbnail');
                $imageUrl = $page->get('thumbnail.@id') . '/' . $region
                    . '/' . $size . '/' . $rotation . '/' . $quality;

                switch ($contentType) {
                    case 'pdf':
                        $imageUrl .= '.jpg';
                        $handle = fopen($imageUrl, 'rb');
                        $content = new Imagick();
                        $content->readImageFile($handle);
                        $content->setImageFormat($contentType);
                        break;
                    default:
                        $imageUrl .= '.' . $contentType;
                        $content = file_get_contents($imageUrl);
                }
                break;
            case 'manifest':
                $content = $this->downloadJson($dataUrl . '/manifest');
                $fileName = $document->get('code') . '_manifest';
                break;
            case 'canvas':
                $content = $this->downloadJson($dataUrl . '/canvas/' . $canvasCode);
                $fileName .= '_canvas';
                break;
            case 'canvas-objects':
                $content = $this->downloadJson($dataUrl . '/segments/' . $canvasCode);
                $fileName .= '_canvas_objects';
                break;
            case 'annotations':
                $content = $this->downloadJson($dataUrl . '/list/' . $canvasCode);
                $fileName .= '_annotations';
                break;
            default:
                $this->request->abort(400);
        }

        $fileName .= '.' . $contentType;
        $this->setContentType($contentType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        echo $content;
    }

    public function delete()
    {
        if (!$this->hasPermission('edit_documents')) {
            $this->request->abort(403);
        }

        $canvasId = $this->request->getPost('canvas_id');

        if (!$canvasId) {
            $this->request->abort(400);
        }

        $canvasModel = new Canvas($this->db);
        $canvas = $canvasModel->fetch($canvasId, ['manifest_id', 'code']);

        $isDeleted = $canvasModel->delete($canvasId);

        if ($isDeleted) {
            $manifestModel = new Manifest($this->db);
            $imageModel = new Image($this->db);

            $manifest = $manifestModel->fetch(
                $canvas['manifest_id'],
                ['id', 'properties']
            )->decodeJsonValue('properties');

            // Update document page count
            $manifestModel->setProperty(
                $manifest->get('id'),
                'metadata.pageCount',
                ($manifest->get('properties.metadata.pageCount') - 1),
                $this->session->get('auth_user')->get('id')
            );

            $number = (int) substr($canvas['code'], 1);

            // Delete page thumbnail
            if ($number === 1) {
                $manifestModel->deleteProperty(
                    $manifest->get('id'),
                    'thumbnail',
                    $this->session->get('auth_user')->get('id')
                );
            }

            $filePath = $this->config->iiif->image->server->dir . '/'
                . $imageModel->getImageResourceFilePath(
                    $canvas['manifest_id'], $number, 'jpg'
                );
            $cacheDir = $this->config->iiif->image->server->cache_dir . '/'
                . $imageModel->getImageResourceId(
                    $canvas['manifest_id'], $number
                );
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            if (file_exists($cacheDir)) {
                exec('sudo -u loris rm -R ' . $cacheDir);
            }
        }

        $this->setContentType('json');
        echo json_encode(['status' => $isDeleted]);
    }

    /**
     * @param string $url
     * @return string
     */
    protected function downloadJson(string $url): string
    {
        $content = file_get_contents($url) ?: '{}';
        return json_encode(
            json_decode($content),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }
}

// -- End of file
