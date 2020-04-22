<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Models\Entity;
use Application\Models\Entity\Annotation as AnnotationModel;
use Application\Models\EntityRelation;
use Application\Models\Entity\Canvas;
use Application\Models\Entity\Contract;
use Application\Models\Entity\Manifest;

use const Application\DATETIME_APP;

/**
 * Class Annotation
 * @package Application\Controllers\Main
 */
class Annotation extends Base
{
    public function index() {}

    public function get()
    {
        if (!$this->hasPermission('view_annotations')) {
            $this->request->abort(403);
        }

        $targetEntityId = $this->request->getPost('target_entity_id');

        if (!$targetEntityId) {
            $this->request->abort(400);
        }

        $time_start = microtime(true);
        $annotationModel = new AnnotationModel($this->db);
        $annotations = $annotationModel->findByTarget($targetEntityId);
        $time_end = microtime(true);
        $time = $time_end - $time_start;

        //echo "Execution Time:  $time seconds\n";

        $this->setContentType('json');
        echo !empty($annotations) ? json_encode($annotations) : '{}';
    }

    public function create()
    {
        if (!$this->hasPermission('create_annotations')) {
            $this->request->abort(403);
        }

        $this->setContentType('json');
        $userId = $this->session->get('auth_user')->get('id');

        $type = $this->request->getPost('type');
        $targetEntityId = $this->request->getPost('target_entity_id');
        $targetEntityType = $this->request->getPost('target_entity_type');
        $bodyEntityId = $this->request->getPost('body_entity_id');
        $bodyEntityType = $this->request->getPost('body_entity_type');

        if (!$type || !$targetEntityId || !$targetEntityType
            || !$bodyEntityId || !$bodyEntityType) {
            $this->request->abort(400);
        }

        $annotation = new AnnotationModel($this->db);

        $annotationData = [
            'motivation' => $annotation->getMotivationQNameByType($type),
        ];

        $annotationId = $annotation->create(
            json_encode($annotationData, JSON_UNESCAPED_SLASHES),
            $userId
        );

        if (!is_null($annotationId)) {
            $annotation->addTarget(
                $annotationId,
                $targetEntityId,
                $targetEntityType,
                $userId
            );
            $annotation->addBody(
                $annotationId,
                $bodyEntityId,
                $bodyEntityType,
                $userId
            );
        }

        echo json_encode([
            'id' => $annotationId,
            'created' => date(DATETIME_APP),
            'creator' => [
                'id' => $this->session->get('auth_user')->get('id'),
                'name' => $this->session->get('auth_user')->get('full_name'),
                'type' => 'Person',
            ],
        ]);
    }

    public function delete()
    {
        if (!$this->hasPermission('delete_annotations')) {
            $this->request->abort(403);
        }

        $annotationId = $this->request->getPost('annotation_id');
        $cascade = $this->request->getPost('cascade');

        if (!$annotationId) {
            $this->request->abort(400);
        }

        $entity = new Entity($this->db);

        if ($cascade === 'shallow') {
            $propertyId = $entity->getPropertyId('dhc:hasBody');
            $entityIds = (new EntityRelation($this->db))->getList(
                'range_entity_id',
                [
                    ['domain_entity_id', '=', $annotationId],
                    ['entity_property_id', '=', $propertyId]
                ]
            );
            if (!empty($entityIds)) {
                $entity->deleteMany([['id', 'in', $entityIds]]);
            }
        }
        $status = $entity->delete($annotationId);

        $this->setContentType('json');
        echo json_encode(['status' => $status]);
    }

    public function export()
    {
        ini_set('memory_limit','256M');
        $this->setContentType('json');
        $fileName = 'annotations_' . date('Ymd_His') . '.json';
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        $cte = '
            WITH annotations AS (
                SELECT id, target_id, body_id FROM canvas_object_annotations
                UNION
                SELECT id, target_id, body_id FROM mention_annotations
            )
        ';

        echo "{" . PHP_EOL;
        echo "\t\"annotations\": [" . PHP_EOL;

        $query = $cte . '
            SELECT row_to_json(records)
            FROM (SELECT * FROM annotations) records
        ';
        $annotations = $this->db->fetch('list', $query);
        $n = count($annotations);
        foreach ($annotations as $i => $annotation) {
            echo "\t\t" . $annotation . (($i + 1 < $n) ? ',' : '') . PHP_EOL;
        }

        echo "\t]," . PHP_EOL;
        echo "\t\"entities\": [" . PHP_EOL;

        $query = $cte . ",
            entities AS (
                SELECT id FROM manifests
                UNION
                SELECT id FROM annotations
                UNION
                SELECT target_id FROM annotations
                UNION
                SELECT body_id FROM annotations
            )
            SELECT row_to_json(records)
            FROM (
                SELECT
                    entity_details.id,
                    entity_types.prefix || ':' || entity_types.name AS type,
                    entity_details.properties,
                    entity_details.creation_time,
                    entity_details.last_update_time
                FROM entities, entity_details, entity_types
                WHERE entities.id = entity_details.id
                    AND entity_details.entity_type_id = entity_types.id
                    AND entity_details.is_active = true
                ORDER BY type, creation_time
            ) records
        ";
        $entities = $this->db->fetch('list', $query);
        $n = count($entities);
        foreach ($entities as $i => $entity) {
            echo "\t\t" . $entity . (($i + 1 < $n) ? ',' : '') . PHP_EOL;
        }

        echo "\t]" . PHP_EOL;
        echo "}" . PHP_EOL;
    }

    public function search()
    {
        if (!$this->hasPermission('view_annotations')) {
            $this->request->abort(403);
        }

        $this->view->page_title = $this->text->get('app.search');

        $query = $this->request->getQuery('q') ?: '';
        $currentPage = (int) $this->request->getQuery('page', 1);

        $limit = 10;
        $offset = $limit * ($currentPage - 1);

        $records = [];
        $allRecordCount = 0;

        $annotationModel = new AnnotationModel($this->db);

        if ($query) {
            $matchedPages = $annotationModel->search($query, [], 'canvas');
            if (!empty($matchedPages)) {
                $granularity = 'canvas_object';
                $filter = [
                    'canvas_id' => [
                        array_column($matchedPages, 'canvas_id'),
                        true,
                    ],
                ];
                $records = $annotationModel->search($query, $filter, $granularity, true, $limit, $offset);
                $allRecordCount = $annotationModel->getSearchResultCount($query, $filter, $granularity, true);
            }
        }

        $canvasObjectIds = [];
        foreach ($records as $r) {
            $canvasObjectIds[] = $r['target_id'];
        }

        $contractModel = new Contract($this->db);
        $contractIds = array_values($contractModel->getContractIds($canvasObjectIds));
        $contracts = [];

        foreach($contractModel->getDetailsOfMany($contractIds) as $contract) {
            $contracts[$contract['target_id']] = $contract;
        }

        $results = [];
        $manifestIds = [];
        $canvasIds = [];

        foreach ($records as $r) {
            $mId = $r['manifest_id'];
            $cId = $r['canvas_id'];
            $manifestIds[$mId] = true;
            $canvasIds[$cId] = true;
            if (!isset($results[$mId][$cId])) {
                $results[$mId][$cId] = [
                    'targets' => [],
                    'contracts' => [],
                ];
            }
            $results[$mId][$cId]['targets'][] = $r['target_bbox'];
            if(isset($contracts[$r['target_id']])) {
                $results[$mId][$cId]['contracts'][] = $contracts[$r['target_id']];
            }
        }

        $manifestIds = array_keys($manifestIds);
        $canvasIds = array_keys($canvasIds);

        $documents = [];
        $pages = [];

        if (!empty($records)) {
            $manifest = new Manifest($this->db);
            $canvas = new Canvas($this->db);

            $fetchedDocuments = $manifest->findAll(
                [
                    ['id', 'in', $manifestIds],
                    ['is_active', '=', true],
                ],
                ['id', 'properties']
            )->toArray();

            foreach ($fetchedDocuments as $document) {
                $id = $document['id'];
                $documents[$id] = json_decode($document['properties'], true);
                $documents[$id]['id'] = $id;
            }

            $fetchedPages = $canvas->findAll(
                [['id', 'in', $canvasIds]], ['id', 'properties']
            )->toArray();

            foreach ($fetchedPages as $page) {
                $id = $page['id'];
                $pages[$id] = json_decode($page['properties'], true);
                $pages[$id]['id'] = $id;
            }
        }

        $this->view->external = [];

        $this->view->keywords = $query;

        $this->view->documents = $documents;
        $this->view->pages = $pages;
        $this->view->results = $results;
        $this->view->result_count = $allRecordCount;
        $this->view->contract_model = $contractModel;

        $this->view->current_page = $currentPage;
        $this->view->last_page = ceil($allRecordCount / $limit);
        $this->view->results_per_page = $limit;

        $this->view->canvas_view_url = $this->getUrl('module', 'page/view');
        $this->view->sequence_view_url = $this->getUrl('module', 'sequence/view');
        $this->view->document_view_url = $this->getUrl('controller', 'view');
        $this->view->document_list_url = $this->getUrl('module', 'documents');

        echo $this->view->render('pages/annotation/search.tpl.php');
    }
}
