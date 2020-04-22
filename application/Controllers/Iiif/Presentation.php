<?php

declare(strict_types=1);

namespace Application\Controllers\Iiif;

use Application\Core\Cache\CacheInterface;
use Application\Core\Configuration\Repository;
use Application\Core\Database\Database;
use Application\Core\Foundation\Request;
use Application\Core\Foundation\View;
use Application\Core\Log\LogWriter;
use Application\Core\Session\Session;
use Application\Core\Text\Translator;

use Application\Core\Type\Text;
use Application\Models\Entity\Annotation;
use Application\Models\Entity\Canvas;
use Application\Models\Entity\CanvasSegment;
use Application\Models\Entity\CanvasSegmentGroup;
use Application\Models\Entity\CanvasSequence;
use Application\Models\Entity\Manifest;
use const Application\REGEX_UUID;

/**
 * Class Presentation
 * @package Application\Controllers\Iiif
 */
class Presentation extends Base
{
    /**
     * @var array Error messages
     */
    protected static $errors = array(
        'resource_not_found'        => 'Resource not found',
        'missing_request_params'    => 'Undefined request parameters',
        'invalid_request_params'    => 'Invalid request parameters',
        'invalid_uuid'              => '%s is not a valid UUID',
        'invalid_name'              => '%s is not a valid %s name',
    );

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

        $this->setContentType('json');
        header('Access-Control-Allow-Origin: *');
    }

    public function index() {}

    public function export()
    {
        if ($this->request->getAttributeCount() < 2) {
            $this->exit(self::$errors['missing_request_params']);
        }

        // Assign a handler for a collection or a content resource

        $param1 = $this->request->getAttribute(0);
        $param2 = $this->request->getAttribute(1);

        if ($param1 == 'collection') {
            if (!strlen($param2)) {
                $this->exit(self::$errors['invalid_request_params']);
            }
            $collectionName = $this->resolveName($param2, 'collection');
            $this->exportCollection($collectionName);
            exit;
        }
        elseif ($param1 == 'resource') {
            if (!preg_match($this->config->regex->uuid, $param2)) {
                $this->exit(sprintf(self::$errors['invalid_uuid'], $param2));
            }
            $this->exportResource($param2);
            exit;
        }

        // Assign a handler for a manifest

        $manifestId = $param1;
        $resourceType = $param2;

        if (!preg_match('/' . REGEX_UUID . '/', $manifestId)) {
            $this->exit(sprintf(self::$errors['invalid_uuid'], $manifestId));
        }

        if ($resourceType == 'manifest') {
            $this->exportManifest($manifestId);
            exit;
        }

        // Assign a handler for another type of a resource

        $resourceName = $this->request->getAttribute(2);

        if (!$resourceName) {
            $this->exit(self::$errors['missing_request_params']);
        }

        switch ($resourceType) {
            case 'sequence':
                $sequenceName = $this->resolveName($resourceName, $resourceType);
                $this->exportSequence($manifestId, $sequenceName);
                break;
            case 'canvas':
                $canvasCode = $this->resolveName($resourceName, $resourceType);
                $this->exportCanvas($manifestId, $canvasCode);
                break;
            case 'annotation':
                list($canvasCode, $annotationId) = $this->resolveName($resourceName, $resourceType);
                $this->exportAnnotation($manifestId, $canvasCode, $annotationId);
                break;
            case 'list':
                $canvasCode = $this->resolveName($resourceName, $resourceType);
                $this->exportAnnotationList($manifestId, $canvasCode);
                break;
            case 'layer':
                $layerName = $this->resolveName($resourceName, $resourceType);
                $this->exportLayer($manifestId, $layerName);
                break;
            case 'range':
                $rangeName = $this->resolveName($resourceName, $resourceType);
                $this->exportRange($manifestId, $rangeName);
                break;
            case 'segments':
                $canvasCode = $this->resolveName($resourceName, 'canvas');
                $this->exportCanvasObjects($manifestId, $canvasCode);
                break;
            default:
                $this->exit(self::$errors['invalid_request_params']);
        }
    }

    /**
     * @param string $collectionName
     */
    protected function exportCollection(string $collectionName)
    {
        if ($collectionName !== 'top') {
            $this->exit(self::$errors['resource_not_found'], 404);
        }

        $manifest = new Manifest($this->db);

        $documents = $manifest->findAll([],
            ['id', 'properties', 'code'],
            ['code']
        )->toArray();

        $output = [
            '@context' => $this->config->iiif->pres->context,
            '@id' => $this->request->getCurrentUrl(),
            '@type' => 'sc:Collection',
            'label' => 'Top Level Collection',
            'manifests' => []
        ];

        foreach ($documents as $document) {
            $properties = json_decode($document['properties'], true);
            $output['manifests'][] = [
                '@id' => $this->config->iiif->pres->base_url
                    . $document['id'] . '/manifest',
                '@type' => 'sc:Manifest',
                'label' => $properties['label'],
            ];
        }

        echo json_encode($output, JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param string $manifestId
     */
    protected function exportManifest(string $manifestId)
    {
        $manifestModel = new Manifest($this->db);
        $sequenceModel = new CanvasSequence($this->db);

        $document = $manifestModel->fetch($manifestId);

        if ($document->isEmpty()) {
            $this->exit(self::$errors['resource_not_found'], 404);
        }

        $document->decodeJsonValue('properties');

        $output = [
            '@context' => $this->config->iiif->pres->context,
            '@id' => $this->request->getCurrentUrl(),
            '@type' => 'sc:Manifest',
            'label' => $document->get('properties.label'),
            'metadata' => $this->normalizeMetadata(
                $document->get('properties.metadata', [])
            ),
            'description' => $this->normalizeMultilingualValue(
                $document->get('properties.description', '')
            ),
            'thumbnail' => [
                '@id' => $document->get('properties.thumbnail.@id')
                    . '/full/,300/0/default.jpg',
                '@type' => 'dctypes:Image',
                'format' => 'image/jpeg',
                'service' => $document->get('properties.thumbnail'),
            ],
            'viewingDirection' => 'right-to-left',
            'viewingHint' => 'paged',
            'within' => $this->config->iiif->pres->base_url . 'collection/top',
            'sequences' => []
        ];

        $sequence = [
            '@id' => $this->config->iiif->pres->base_url
                . $manifestId . '/sequence/normal',
            '@type' => 'sc:Sequence',
            'label' => 'Default Page Order',
            'canvases' => [],
        ];

        $defaultSequence = $sequenceModel->find([
            ['manifest_id', '=', $manifestId],
            ['code', '=', 'normal'],
        ]);

        if (!$defaultSequence->isEmpty()) {
            $canvases = $sequenceModel->getCanvases(
                $defaultSequence->get('id')
            )->toArray();
        } else {
            $canvases = [];
        }

        foreach ($canvases as $canvas) {
            $sequence['canvases'][] = $this->buildCanvas($manifestId, $canvas);
        }

        $output['sequences'][] = $sequence;

        echo json_encode($output, JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param string $manifestId
     * @param string $sequenceName
     */
    protected function exportSequence(string $manifestId, string $sequenceName)
    {
        echo json_encode(array(
            'manifest_id' => $manifestId,
            'sequence_name' => $sequenceName,
        ));
    }

    /**
     * @param string $manifestId
     * @param string $canvasCode
     */
    protected function exportCanvas(string $manifestId, string $canvasCode)
    {
        $canvasModel = new Canvas($this->db);

        $canvas = $canvasModel->findByManifestKeys(
            $manifestId,
            $canvasCode,
            ['id', 'properties']
        );

        echo json_encode(
            $this->buildCanvas($manifestId, $canvas->toArray()),
            JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * @param string $manifestId
     * @param string $canvasId
     * @param string $annotationId
     */
    protected function exportAnnotation(
        string $manifestId,
        string $canvasId,
        string $annotationId
    ) {
        echo json_encode(array(
            'manifest_id' => $manifestId,
            'canvas_id' => $canvasId,
            'annotation_id' => $annotationId,
        ));
    }

    /**
     * @param string $manifestId
     * @param string $canvasCode
     */
    protected function exportAnnotationList(
        string $manifestId,
        string $canvasCode
    ) {
        $annotationModel = new Annotation($this->db);
        $canvasModel = new Canvas($this->db);

        $output = [
            '@context' => $this->config->iiif->pres->context,
            '@id' => $this->request->getCurrentUrl(),
            '@type' => 'sc:AnnotationList',
            'resources' => []
        ];

        $canvasObjects = [];
        foreach ($canvasModel->getObjects($manifestId, $canvasCode)->toArray() as $obj) {
            $properties = json_decode($obj['properties'], true);
            $boundingBox = $properties['bbox'] ?? [];
            $canvasObjects[$obj['id']] = [
                'id' => $obj['id'],
                'boundingBox' => $boundingBox
            ];
        }

        foreach ($annotationModel->findByTargets(array_keys($canvasObjects))
                    as $targetId => $annotations) {
            foreach ($annotations as $annotation) {
                $creator = $annotation['creator'];
                $creator['id'] = $this->getUrl('base', 'user/view/' . $creator['id']);
                $output['resources'][] = [
                    '@id' => $this->config->iiif->pres->base_url
                        . 'annotation/' . $annotation['id'],
                    '@type' => 'oa:Annotation',
                    'motivation' => $annotation['motivation'],
                    'resource' => [
                        '@id' => 'urn:uuid:' . $annotation['body']['id'],
                        '@type' => $annotation['body']['type'],
                        'properties' => $annotation['body']['properties']
                    ],
                    'created' => str_replace('+00:00', 'Z', gmdate('c',
                        strtotime($annotation['created']))),
                    'creator' => $creator,
                    'on' => 'urn:uuid:' . $targetId
                ];
            }
        }
        echo json_encode($output, JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param string $manifestId
     * @param string $layerName
     */
    protected function exportLayer(string $manifestId, string $layerName)
    {
        echo json_encode(array(
            'manifest_id' => $manifestId,
            'layer_name' => $layerName,
        ));
    }

    /**
     * @param string $manifestId
     * @param string $rangeName
     */
    protected function exportRange(string $manifestId, string $rangeName)
    {
        echo json_encode(array(
            'manifest_id' => $manifestId,
            'range_name' => $rangeName,
        ));
    }

    /**
     * @param string $resourceId
     */
    protected function exportResource(string $resourceId)
    {
        echo json_encode(array(
            'resource_id' => $resourceId,
        ));
    }

    /**
     * @param string $manifestId
     * @param string $canvasCode
     */
    protected function exportCanvasObjects(
        string $manifestId,
        string $canvasCode
    ) {
        $output = [
            'manifestId' => $manifestId,
            'canvasCode' => $canvasCode,
            'objects' => [],
        ];
        $canvas = new Canvas($this->db);
        $canvasObjects = $canvas->getObjects($manifestId, $canvasCode, true);
        $segmentTypeId = $canvas->getTypeId(CanvasSegment::ENTITY_TYPE);
        $segmentGroupTypeId = $canvas->getTypeId(
            CanvasSegmentGroup::ENTITY_TYPE
        );

        $groups = [];
        $members = [];
        $segmentGroup = new CanvasSegmentGroup($this->db);
        $segmentGroupIds = array_column(
            $canvasObjects->query(
                [['type_id', '=', $segmentGroupTypeId]]
            )->toArray(),
            'id'
        );
        $segmentGroupMembers = $segmentGroup->getMemberTable($segmentGroupIds);
        foreach ($segmentGroupMembers->toArray() as $g) {
            $groups[$g['range_entity_id']][] = $g['domain_entity_id'];
            $members[$g['domain_entity_id']][] = $g['range_entity_id'];
        }

        $objects = [
            'segments' => [],
            'segment_groups' => [],
        ];

        foreach ($canvasObjects->toArray() as $obj) {
            $properties = json_decode($obj['properties'], true);
            $boundingBox = $properties['bbox'] ?? [];
            $object = [
                'id' => $obj['id'],
                'lineId' => 0,
                'boundingBox' => $boundingBox,
                'isAnnotated' => $obj['is_annotated'],
                'type' => 'segment'
            ];
            if ($obj['type_id'] === $segmentGroupTypeId) {
                if (array_key_exists($obj['id'], $members)) {
                    $object['members'] = $members[$obj['id']];
                }
                $object['type'] = 'segmentGroup';
                $objects['segment_groups'][] = $object;
            } elseif ($obj['type_id'] === $segmentTypeId) {
                if (array_key_exists($obj['id'], $groups)) {
                    $object['groups'] = $groups[$obj['id']];
                }
                $object['type'] = 'segment';
                $objects['segments'][] = $object;
            }
        }

        $output['objects'] = array_merge(
            $objects['segment_groups'],
            $objects['segments']
        );

        echo json_encode($output, JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param string $manifestId
     * @param array $canvas
     * @return array
     */
    protected function buildCanvas(string $manifestId, array $canvas): array
    {
        $properties = json_decode($canvas['properties'], true);
        $canvasId = $this->config->iiif->pres->base_url
            . $manifestId . '/canvas/' . $properties['code'];
        return [
            '@id' => $canvasId,
            '@type' => 'sc:Canvas',
            'label' => $this->normalizeMultilingualValue(
                $properties['label']
            ),
            'width' => $properties['width'],
            'height' => $properties['height'],
            'images' => [[
                '@id' => $this->config->iiif->pres->base_url
                    . $manifestId . '/annotation/' . $properties['code'] . '-image',
                '@type' => 'oa:Annotation',
                'motivation' => 'sc:painting',
                'resource' => [
                    '@id' => $properties['thumbnail']['@id']
                        . '/full/full/0/default.jpg',
                    '@type' => 'dctypes:Image',
                    'format' => 'image/jpeg',
                    'service' => $properties['thumbnail'],
                    'width' => $properties['width'],
                    'height' => $properties['height'],
                ],
                'on' => $canvasId,
            ]],
            'otherContent' => [
                [
                    '@id' => $this->config->iiif->pres->base_url
                        . $manifestId . '/list/' . $properties['code'],
                    '@type' => 'sc:AnnotationList',
                ],
            ],
        ];
    }

    /**
     * @param string $name
     * @param string $type
     * @return mixed
     */
    protected function resolveName(string $name, string $type)
    {
        $name = strtolower($name);
        switch ($type) {
            case 'collection':
            case 'sequence':
                if (!preg_match('/^[a-z]{1}[0-9a-z-]+$/', $name)) {
                    $this->exit(sprintf(self::$errors['invalid_name'], $name, $type));
                }
                return $name;
                break;
            case 'canvas':
                if (!preg_match('/^[a-z]{1}[0-9a-z-]+$/', $name)) {
                    $this->exit(sprintf(self::$errors['invalid_name'], $name, $type));
                }
                return $name;
                break;
            case 'annotation':
                if (!preg_match('/^[a-z]{1}[0-9]+-[0-9]+$/', $name)) {
                    $this->exit(sprintf(self::$errors['invalid_name'], $name, $type));
                }
                list($canvasId, $annotationId) = explode('-', substr($name, 1), 2);
                return array((int) $canvasId, (int) $annotationId);
                break;
            case 'list':
                if (!preg_match('/^[a-z]{1}[0-9a-z-]+$/', $name)) {
                    $this->exit(sprintf(self::$errors['invalid_name'], $name, $type));
                }
                return $name;
                break;
            case 'layer':
                if (!preg_match('/^[a-z]{1}[0-9a-z-]+$/', $name)) {
                    $this->exit(sprintf(self::$errors['invalid_name'], $name, $type));
                }
                return $name;
                break;
            case 'range':
                if (!preg_match('/^[a-z]{1}[0-9-]+$/', $name)) {
                    $this->exit(sprintf(self::$errors['invalid_name'], $name, $type));
                }
                return substr($name, 1);
                break;
            default:
                return null;
        }
    }

    /**
     * @param $value
     * @return mixed
     */
    protected function normalizeMultilingualValue($value)
    {
        if (is_array($value)) {
            $values = [];
            foreach ($value as $lang => $val) {
                $values[] = [
                    '@value' => $val,
                    '@language' => $lang,
                ];
            }
            return (count($values) === 1) ? array_shift($values) : $values;
        }
        return (string) $value;
    }

    /**
     * @param array $metadata
     * @return array
     */
    protected function normalizeMetadata(array $metadata)
    {
        $values = [];
        foreach ($metadata as $property => $value) {
            $label = new Text($property);
            $values[] = [
                'label' => $label->spacify()->toTitleCase()->toString(),
                'value' => $this->normalizeMultilingualValue($value),
            ];
        }
        return $values;
    }

    /**
     * Terminates the current request and returns an error object.
     *
     * @param int $statusCode
     * @param string $errorMessage an error message
     */
    protected function exit(string $errorMessage, int $statusCode = 400)
    {
        $statusResponse = array_key_exists($statusCode, Request::$messages)
            ? $statusCode . ' ' . Request::$messages[$statusCode] : null;

        if ($statusResponse && !headers_sent()) {
            header('HTTP/1.0 ' . $statusResponse);
        }

        $response = array(
            'errors' => array(
                array(
                    'status' => $statusResponse,
                    'detail' => $errorMessage,
                ),
            ),
        );

        echo json_encode($response, JSON_UNESCAPED_SLASHES);

        exit(1);
    }
}

// -- End of file
