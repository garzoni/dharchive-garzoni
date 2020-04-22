<?php

declare(strict_types=1);

namespace Application\Controllers\Main;
use Application\Models\Entity;
use Application\Models\EntityType as EntityTypeModel;

/**
 * Class EntityType
 * @package Application\Controllers\Main
 */
class EntityType extends Base
{
    public function index()
    {
        $this->view->page_title = $this->text->get('app.entity_types');

        $entityTypeModel = new EntityTypeModel($this->db);
        $this->view->entity_types = $entityTypeModel->fetchAll(
            ['id', 'qualified_name'],
            ['qualified_name']
        )->toArray();

        $this->view->entity_type_view_url = $this->getUrl('controller', 'view');

        echo $this->view->render('pages/entity-type/index.tpl.php');
    }

    public function view()
    {
        $typeId = $this->request->getAttribute(0, 'integer');

        if (!$typeId) {
            $this->request->abort(404);
        }

        $this->view->addStyleSheets($this->getAssetBundleUrls('prism.css'));
        $this->view->addScripts($this->getAssetBundleUrls('prism.js'));
        $this->view->addScripts($this->getAssetBundleUrls('annotation.js'));

        $entityTypeModel = new EntityTypeModel($this->db);
        $this->view->entity_types = $entityTypeModel->fetchAll(
            ['id', 'qualified_name'],
            ['qualified_name']
        );

        $entityType = $entityTypeModel->fetch($typeId)->decodeJsonValue('details');
        $this->view->qualified_name = $entityType->get('qualified_name');
        $this->view->schema = json_encode($entityType->get('details.schema'));

        $this->view->value_list_url = $this->getUrl('module', 'value-list');

        echo $this->view->render('pages/entity-type/view.tpl.php');
    }

    public function get()
    {
        $dataset = $this->request->getAttribute(0);

        $this->setContentType('json');

        if (is_null($dataset)) {
            $entityType = new EntityTypeModel($this->db);
            $types = [];
            foreach ($entityType->findAll([],
                ['qualified_name', 'details'])->toArray() as $t) {
                $details = json_decode($t['details'], true);
                $types[$t['qualified_name']]['label'] =
                    $this->text->resolve($details['label']);
                if (isset($details['display'])) {
                    $types[$t['qualified_name']]['display'] = $details['display'];
                };
            }
            echo json_encode($types);
        } elseif ($dataset === 'schema') {
            $entityTypeQName = $this->request->getPost('entity_type');
            $entity = new Entity($this->db);
            echo $entity->getTypeSchema($entityTypeQName);
        } else {
            echo '{}';
        }
    }
}

// -- End of file
