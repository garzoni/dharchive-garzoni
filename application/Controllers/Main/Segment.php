<?php

declare(strict_types=1);

namespace Application\Controllers\Main;
use Application\Models\Entity\CanvasSegment;
use Application\Models\Entity\CanvasSegmentGroup;

/**
 * Class Segment
 * @package Application\Controllers\Main
 */
class Segment extends Base
{
    public function index() {}

    public function create()
    {
        if (!$this->hasAnyPermission('create_annotations', 'edit_annotations')) {
            $this->request->abort(403);
        }

        $manifestId = $this->request->getPost('manifest_id');
        $canvasCode = $this->request->getPost('canvas_id');
        $segmentId = $this->request->getPost('segment_id', '');
        $boundingBox = $this->request->getPost('bounding_box');

        if (!$manifestId || !$canvasCode || !$boundingBox) {
            $this->request->abort(404);
        }

        $segment = new CanvasSegment($this->db);

        $properties = [
            'manifestUuid' => $manifestId,
            'canvasCode' => $canvasCode,
            'bbox' => array_map('intval', $boundingBox),
        ];

        if (!$properties['bbox']['w'] || !$properties['bbox']['h']) {
            $this->request->abort(400);
        }

        $segmentId = $segment->create(
            json_encode($properties),
            $this->session->get('auth_user')->get('id'),
            $segmentId
        );

        $this->setContentType('json');

        echo json_encode(['id' => $segmentId]);
    }

    public function resize()
    {
        if (!$this->hasAnyPermission('create_annotations', 'edit_annotations')) {
            $this->request->abort(403);
        }

        $segmentId = $this->request->getPost('segment_id');
        $boundingBox = $this->request->getPost('bounding_box');

        if (!$segmentId || !$boundingBox) {
            $this->request->abort(404);
        }

        $segment = new CanvasSegment($this->db);

        $status = $segment->setProperty(
            $segmentId,
            'bbox',
            array_map('intval', $boundingBox),
            $this->session->get('auth_user')->get('id')
        );

        $this->setContentType('json');
        echo json_encode(['status' => $status]);
    }

    public function group()
    {
        if (!$this->hasAnyPermission('create_annotations', 'edit_annotations')) {
            $this->request->abort(403);
        }

        $segmentIds = $this->request->getPost('segment_ids');

        if (!$segmentIds) {
            $this->request->abort(404);
        }

        $manifestId = null;
        $canvasCode = null;

        $this->setContentType('json');

        $segments = (new CanvasSegment($this->db))->findAll(
            [['id', 'in', $segmentIds]],
            ['id', 'manifest_id', 'canvas_code']
        )->toArray();

        foreach ($segments as $segment) {
            if (is_null($manifestId)) {
                $manifestId = $segment['manifest_id'];
                $canvasCode = $segment['canvas_code'];
            } elseif (($segment['manifest_id'] !== $manifestId)
                || ($segment['canvas_code'] !== $canvasCode)) {
                echo json_encode(['id' => null]);
                return;
            }
        }

        $segmentGroup = new CanvasSegmentGroup($this->db);
        $properties = [
            'manifestUuid' => $manifestId,
            'canvasCode' => $canvasCode,
        ];
        $segmentGroupId = $segmentGroup->create(
            json_encode($properties),
            $this->session->get('auth_user')->get('id')
        );

        if (!is_null($segmentGroupId)) {
            foreach ($segments as $segment) {
                $segmentGroup->addMember(
                    $segmentGroupId,
                    $segment['id'],
                    $this->session->get('auth_user')->get('id')
                );
            }
        }

        echo json_encode(['id' => $segmentGroupId]);
    }

    public function ungroup()
    {
        if (!$this->hasAnyPermission('create_annotations', 'edit_annotations')) {
            $this->request->abort(403);
        }

        $segmentGroupId = $this->request->getPost('segment_group_id');

        if (!$segmentGroupId) {
            $this->request->abort(404);
        }

        $segmentGroup = new CanvasSegmentGroup($this->db);

        $status = $segmentGroup->delete($segmentGroupId);

        $this->setContentType('json');
        echo json_encode(array('status' => $status));
    }

    public function delete()
    {
        if (!$this->hasPermission('delete_annotations')) {
            $this->request->abort(403);
        }

        $segmentId = $this->request->getPost('segment_id');

        if (!$segmentId) {
            $this->request->abort(404);
        }

        $segment = new CanvasSegment($this->db);

        $status = $segment->delete($segmentId);

        $this->setContentType('json');
        echo json_encode(['status' => $status]);
    }
}

// -- End of file
