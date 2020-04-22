<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Core\Type\Map;
use Application\Models\ProfessionCategory as ProfessionCategoryModel;
use Application\Models\Profession as ProfessionModel;
use Application\Providers\Exporter;
use Application\Providers\SemanticUi;

use function Application\implodeJsonArray;

use const Application\MEMORY_LIMIT;

/**
 * Class Profession
 * @package Application\Controllers\Main
 */
class Profession extends Base
{
    public function index() {}

    public function getValueList()
    {
        $id = $this->request->getQuery('id');
        $pattern = $this->request->getQuery('pattern');

        $sui = new SemanticUi();
        $professionModel = new ProfessionModel($this->db);

        $this->setContentType('json');

        switch ($id) {
            case 'occupations':
                $values = $professionModel->getOccupationList($pattern);
                break;
            case 'materials':
                $values = $professionModel->getMaterialList($pattern);
                break;
            case 'products':
                $values = $professionModel->getProductList($pattern);
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

        $professionCategoryModel = new ProfessionCategoryModel($this->db);
        $professionModel = new ProfessionModel($this->db);

        $professionCategories = $professionCategoryModel->getLabels([], true);
        $professions = $professionModel->fetchAll()->toArray();

        foreach ($professions as &$profession) {
            if (isset($profession['material'])) {
                $profession['material'] = implodeJsonArray($profession['material']);
            }
            if (isset($profession['product'])) {
                $profession['product'] = implodeJsonArray($profession['product']);
            }
            if (isset($profession['category_id'])) {
                $profession['category_name'] = $professionCategories[$profession['category_id']] ?? null;
            }
        }

        $fileName = 'professions';

        switch($fileFormat) {
            case 'ods':
            case 'xlsx':
                $this->exportSpreadsheet($professions, $fileName, $fileFormat);
                break;
            default:
                $this->exportJson($professions, $fileName);
        }
    }

    protected function exportSpreadsheet(array $professions, string $fileName, string $fileFormat)
    {
        $recordsetSchemas = [
            [
                'name' => 'Professions',
                'columns' => [
                    'ID',
                    'Standard Form',
                    'Occupation',
                    'Materials',
                    'Products',
                    'Category ID',
                    'Category',
                ]
            ],
        ];

        $callback = function (&$profession) use (&$implode) {
            $profession = new Map($profession);
            $data = [
                [
                    $profession->get('id'),
                    $profession->get('standard_form'),
                    $profession->get('occupation'),
                    $profession->get('material'),
                    $profession->get('product'),
                    $profession->get('category_id'),
                    $profession->get('category_name'),
                ]
            ];
            $profession = [$data];
        };

        $exporter = new Exporter();
        $exporter->exportTable($professions, $recordsetSchemas, $fileName, $fileFormat, $callback);
    }

    protected function exportJson(array $professions, string $fileName)
    {
        $exporter = new Exporter();
        $exporter->exportList($professions, '', $fileName, 'json');
    }
}

// -- End of file
