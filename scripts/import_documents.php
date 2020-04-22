<?php

declare(strict_types=1);

namespace Application;

use Application\Core\Database\Database;
use Application\Models\Entity\Annotation;
use Application\Models\Entity\Canvas;
use Application\Models\Entity\CanvasSequence;
use Application\Models\Entity\Image;
use Application\Models\Entity\Manifest;
use Application\Models\Entity\ManifestCollection;
use Application\Models\Robot;
use Imagick;

/*----------------------------------------------------------------------------
   Includes
  ----------------------------------------------------------------------------*/

// Constants
require realpath('../constants.php');

// Class autoloading
require ROOT_DIR . 'autoload.php';

// Common functions
require ROOT_DIR . 'functions.php';

/*----------------------------------------------------------------------------
   Configuration
  ----------------------------------------------------------------------------*/

// Load application configuration
$config = require ROOT_DIR . 'configuration/main.conf.php';

// Constants
const VERBOSE = false;
const IMPORTER_BOTNAME = 'image_importer';
const COLLECTION_DIR_REGEX = '/^[0-9]{4}$/';
const DOCUMENT_DIR_REGEX = '/^[0-9]{4}_[0-9]{4}$/';
const IMAGE_FILE_REGEX = '/^[0-9]{4}_[0-9]{4}_[0-9]{4}.tif$/';
const IMAGE_ID_DIGIT_LENGTH = 6;
const JPEG_COMPRESSION_QUALITY = 80;

/*----------------------------------------------------------------------------
   Execution
  ----------------------------------------------------------------------------*/

// Disable execution time limit
set_time_limit(0);

// Instantiate a PDO wrapper
$db = new Database(
    $config->db->dsn,
    $config->db->username,
    $config->db->password
);

// Instantiate model classes
$robot = new Robot($db);
$manifest = new Manifest($db);
$collection = new ManifestCollection($db);
$canvas = new Canvas($db);
$sequence = new CanvasSequence($db);
$annotation = new Annotation($db);
$image = new Image($db);

// Get importer agent identifier
$agentId = $robot->findByBotname(IMPORTER_BOTNAME)->get('id');
if (is_null($agentId)) {
    echo 'ERROR: Robot "' . IMPORTER_BOTNAME . '" does not exist.' . PHP_EOL;
    exit(1);
}

// Retrieve imported collections
$importedCollections = $collection->findAll([], ['id', 'code'], ['code'])
    ->setKeyColumn('id')->toArray();

// Build a collection index
$importedCollectionCodes = [];
foreach ($importedCollections as $id => $properties) {
    $importedCollectionCodes[$properties['code']] = $id;
}

// Retrieve imported documents
$importedManifests = $manifest->findAll([], ['id', 'code'], ['code'])
    ->setKeyColumn('id')->toArray();

// Build a document index
$importedManifestCodes = [];
foreach ($importedManifests as $id => $properties) {
    $importedManifestCodes[$properties['code']] = $id;
}

// Get page sequences
$importedSequences = [];
foreach ($sequence->findAll([], ['id', 'manifest_id'])->toArray() as $s) {
    $importedSequences[$s['manifest_id']] = $s['id'];
}

// Retrieve imported pages
$importedCanvases = [];
foreach ($canvas->findAll([], ['id', 'manifest_id', 'code'])->toArray() as $c) {
    $importedCanvases[$c['manifest_id']][$c['code']] = $c['id'];
}

// Scan collection folders
$collectionDirs = scanDirectory($config->dir->images, COLLECTION_DIR_REGEX);
foreach ($collectionDirs as $collectionDirPath => $collectionCode) {

    if (array_key_exists($collectionCode, $importedCollectionCodes)) {
        $collectionId = $importedCollectionCodes[$collectionCode];
    } else {
        $collectionData = [
            'label' => $collectionCode,
            'code' => $collectionCode,
        ];

        $collectionId = $collection->create(
            json_encode($collectionData, JSON_UNESCAPED_SLASHES),
            $agentId
        );
        if (is_null($collectionId)) {
            continue;
        }
    }

    // Scan document folders
    $documentDirs = scanDirectory($collectionDirPath, DOCUMENT_DIR_REGEX);
    foreach ($documentDirs as $documentDirPath => $manifestCode) {

        if (substr($manifestCode, 0, strlen($collectionCode)) !== $collectionCode) {
            echo 'NOTICE: Incorrect directory path ['
                . $documentDirPath . ']' . PHP_EOL;
            continue;
        }
        $documentRelativePath = $collectionCode . '/' . $manifestCode;

        // Scan image files
        $imageFiles = scanDirectory($documentDirPath, IMAGE_FILE_REGEX);

        $newImageCount = 0;

        if (array_key_exists($manifestCode, $importedManifestCodes)) {
            $manifestId = $importedManifestCodes[$manifestCode];
            $sequenceId = $importedSequences[$manifestId] ?? '';
            $imageCount = isset($importedCanvases[$manifestId])
                ? count($importedCanvases[$manifestId]) : 0;
        } else {
            $imageCount = 0;
            $manifestData = [
                'label' => $manifestCode,
                'code' => $manifestCode,
                'metadata' => [
                    'originalRelativePath' => $documentRelativePath,
                    'pageCount' => $imageCount,
                ],
            ];

            $manifestId = $manifest->create(
                json_encode($manifestData, JSON_UNESCAPED_SLASHES),
                $agentId
            );
            if (is_null($manifestId)) {
                continue;
            }

            $sequenceData = [
                'manifestUuid' => $manifestId,
                'code' => 'normal',
            ];

            $sequenceId = $sequence->create(
                json_encode($sequenceData, JSON_UNESCAPED_SLASHES),
                $agentId
            );
            if (is_null($sequenceId)) {
                continue;
            }
            if (!$manifest->addSequence($manifestId, $sequenceId, $agentId)) {
                continue;
            }
            if (!$collection->addManifest($collectionId, $manifestId, $agentId)) {
                continue;
            }
        }

        if (VERBOSE) {
            echo PHP_EOL
                . 'Processing ' . $documentDirPath . PHP_EOL
                . 'Manifest ID: ' . $manifestId . PHP_EOL
                . 'Image Files (' . count($imageFiles) . '):' . PHP_EOL;
        }

        if (empty($sequenceId)) {
            echo 'ERROR: Missing sequence [Manifest ID: '
                . $manifestId . ']' . PHP_EOL;
            continue;
        }

        foreach ($imageFiles as $imageFilePath => $imageFileName) {
            $canvasCode = basename($imageFileName, '.tif');

            if (substr($canvasCode, 0, strlen($manifestCode)) !== $manifestCode) {
                echo 'NOTICE: Incorrect image file path ['
                    . $imageFilePath . ']' . PHP_EOL;
                continue;
            }

            $sequenceNumber = (int) substr($canvasCode, -4);
            if ($sequenceNumber < 1) {
                continue;
            }

            $canvasCode = 'p' . $sequenceNumber;

            if (isset($importedCanvases[$manifestId][$canvasCode])) {
                if (VERBOSE) {
                    echo '  - ' . $imageFileName . PHP_EOL;
                }
                continue;
            } else {
                if (VERBOSE) {
                    echo '  + ' . $imageFileName . PHP_EOL;
                }
            }

            $imageUrl = $config->iiif->image->server->url . '/'
                . $image->getImageResourceId($manifestId, $sequenceNumber)
                . '?v=' . time();
            list($imageWidth, $imageHeight) = getimagesize($imageFilePath);

            $imageService = [
                'profile' => $config->iiif->image->profile,
                '@context' => $config->iiif->image->context,
                '@id' => $imageUrl,
            ];

            $canvasData = [
                'manifestUuid' => $manifestId,
                'code' => $canvasCode,
                'label' => ['en' => 'Page ' . $sequenceNumber],
                'width' => $imageWidth,
                'height' => $imageHeight,
                'thumbnail' => $imageService,
                'metadata' => [
                    'originalFileName' => $imageFileName,
                ],
            ];

            $canvasId = $canvas->create(
                json_encode($canvasData, JSON_UNESCAPED_SLASHES),
                $agentId
            );
            if (is_null($canvasId)) {
                continue;
            }
            if (!$sequence->addCanvas(
                    $sequenceId,
                    $canvasId,
                    $agentId,
                    $sequenceNumber
                )) {
                continue;
            }

            $newImageFilePath = $config->iiif->image->server->dir . '/'
                . $image->getImageResourceFilePath(
                    $manifestId, $sequenceNumber, 'jpg'
                );

            if (!file_exists(dirname($newImageFilePath))) {
                $umask = umask(0);
                mkdir(dirname($newImageFilePath), 0777, true);
                umask($umask);
            }

            $originalImage = new Imagick($imageFilePath);
            $newImage = clone $originalImage;

            $newImage->setImageFormat('jpeg');
            $newImage->setImageCompression(Imagick::COMPRESSION_JPEG);
            $newImage->setImageCompressionQuality(JPEG_COMPRESSION_QUALITY);

            $newImage->writeImage($newImageFilePath);

            $imageData = [
                'format' => 'image/jpeg',
                'width' => $imageWidth,
                'height' => $imageHeight,
                'service' => [$imageService],
            ];

            $imageId = $image->create(
                json_encode($imageData, JSON_UNESCAPED_SLASHES),
                $agentId
            );

            if (is_null($imageId)) {
                continue;
            }

            $newImageCount++;

            $annotationData = [
                'motivation' => 'sc:painting',
            ];

            $annotationId = $annotation->create(
                json_encode($annotationData, JSON_UNESCAPED_SLASHES),
                $agentId
            );

            if (is_null($annotationId)) {
                continue;
            }

            $annotation->addTarget(
                $annotationId,
                $canvasId,
                Canvas::ENTITY_TYPE,
                $agentId
            );

            $annotation->addBody(
                $annotationId,
                $imageId,
                Image::ENTITY_TYPE,
                $agentId
            );

            if ($sequenceNumber === 1) {
                $manifest->setProperty(
                    $manifestId,
                    'thumbnail',
                    $imageService,
                    $agentId
                );
            }
        }

        if ($newImageCount > 0) {
            $manifest->setProperty(
                $manifestId,
                'metadata.pageCount',
                ($imageCount + $newImageCount),
                $agentId
            );
        }
    }
}

// -- End of file
