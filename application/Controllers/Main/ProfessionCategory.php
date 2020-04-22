<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Core\Type\Map;
use Application\Models\ProfessionCategory as ProfessionCategoryModel;
use Application\Providers\Exporter;
use Application\Providers\SemanticUi;

use function Application\implodeJsonArray;

use const Application\MEMORY_LIMIT;

/**
 * Class ProfessionCategory
 * @package Application\Controllers\Main
 */
class ProfessionCategory extends Base
{
    public function index() {}

    public function getValueList()
    {
        $categoryId = $this->request->getQuery('parent_id');
        if (!is_null($categoryId)) {
            $categoryId = (int) $categoryId;
        }

        $professionCategoryModel = new ProfessionCategoryModel($this->db);
        $categories = $professionCategoryModel->getChildren($categoryId);

        $this->setContentType('json');

        $sui = new SemanticUi();
        echo $sui->getValueList($categories->toArray(), ['key' => 'id', 'value' => 'label']);
    }

    public function export()
    {
        $fileFormat = $this->request->getParam('format', 'json');

        if (!in_array($fileFormat, ['xlsx', 'ods', 'json'])) {
            $this->request->abort(400);
        }

        ini_set('memory_limit', MEMORY_LIMIT);

        $professionCategoryModel = new ProfessionCategoryModel($this->db);

        $extendedLabels = $professionCategoryModel->getLabels([], true);
        $categories = $professionCategoryModel->fetchAll()->toArray();

        foreach ($categories as &$category) {
            $category['extended_label'] = $extendedLabels[$category['id']] ?? null;
            if (isset($category['sector'])) {
                $category['sector'] = implodeJsonArray($category['sector']);
            }
        }

        $fileName = 'profession_categories';

        switch($fileFormat) {
            case 'ods':
            case 'xlsx':
                $this->exportSpreadsheet($categories, $fileName, $fileFormat);
                break;
            default:
                $this->exportJson($categories, $fileName);
        }
    }

    protected function exportSpreadsheet(array $categories, string $fileName, string $fileFormat)
    {
        $recordsetSchemas = [
            [
                'name' => 'Profession Categories',
                'columns' => [
                    'ID',
                    'Extended Label',
                    'Label',
                    'Description',
                    'Sectors',
                    'Parent Category ID',
                ]
            ],
        ];

        $callback = function (&$category) use (&$implode) {
            $category = new Map($category);
            $data = [
                [
                    $category->get('id'),
                    $category->get('extended_label'),
                    $category->get('label'),
                    $category->get('description'),
                    $category->get('sector'),
                    $category->get('parent_id'),
                ]
            ];
            $category = [$data];
        };

        $exporter = new Exporter();
        $exporter->exportTable($categories, $recordsetSchemas, $fileName, $fileFormat, $callback);
    }

    protected function exportJson(array $categories, string $fileName)
    {
        $exporter = new Exporter();
        $exporter->exportList($categories, '', $fileName, 'json');
    }
}

// -- End of file
