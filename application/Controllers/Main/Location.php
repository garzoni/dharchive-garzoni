<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Core\Type\Map;
use Application\Models\Location as LocationModel;
use Application\Providers\Exporter;
use Application\Providers\SemanticUi;

use const Application\MEMORY_LIMIT;

/**
 * Class Location
 * @package Application\Controllers\Main
 */
class Location extends Base
{
    public function index() {}

    public function getValueList()
    {
        $id = $this->request->getQuery('id');
        $pattern = $this->request->getQuery('pattern');

        $sui = new SemanticUi();
        $locationModel = new LocationModel($this->db);

        $this->setContentType('json');

        switch ($id) {
            case 'names':
                $values = $locationModel->getNameList($pattern);
                break;
            case 'types':
                $values = $locationModel->getTypeList($pattern);
                break;
            case 'provinces':
                $values = $locationModel->getProvinceList($pattern);
                break;
            case 'countries':
                $values = $locationModel->getCountryList($pattern);
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

        if (!in_array($fileFormat, ['xlsx', 'ods', 'json'])) {
            $this->request->abort(400);
        }

        ini_set('memory_limit', MEMORY_LIMIT);

        $locationModel = new LocationModel($this->db);
        $locations = $locationModel->fetchAll()->toArray();

        $fileName = 'locations';

        switch($fileFormat) {
            case 'ods':
            case 'xlsx':
                $this->exportSpreadsheet($locations, $fileName, $fileFormat);
                break;
            default:
                $this->exportJson($locations, $fileName);
        }
    }

    protected function exportSpreadsheet(array $locations, string $fileName, string $fileFormat)
    {
        $recordsetSchemas = [
            [
                'name' => 'Locations',
                'columns' => [
                    'ID',
                    'Historical Name',
                    'Contemporary Name',
                    'Type',
                    'Province',
                    'Country',
                    'Coordinates',
                    'GeoNames ID',
                ]
            ],
        ];

        $callback = function (&$location) {
            $location = new Map($location);
            $data = [
                [
                    $location->get('id'),
                    $location->get('standard_form'),
                    $location->get('name'),
                    $location->get('type'),
                    $location->get('province'),
                    $location->get('country'),
                    $location->get('coordinates'),
                    $location->get('geonames_id'),
                ]
            ];
            $location = [$data];
        };

        $exporter = new Exporter();
        $exporter->exportTable($locations, $recordsetSchemas, $fileName, $fileFormat, $callback);
    }

    protected function exportJson(array $locations, string $fileName)
    {
        $exporter = new Exporter();
        $exporter->exportList($locations, '', $fileName, 'json');
    }
}

// -- End of file
