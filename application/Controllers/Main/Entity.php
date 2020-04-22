<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Models\Entity as EntityModel;

/**
 * Class Entity
 * @package Application\Controllers\Main
 */
class Entity extends Base
{
    public function index() {}

    public function create()
    {
        error_reporting(null);
        if (!$this->hasPermission('create_annotations')) {
            $this->request->abort(403);
        }

        $type = $this->request->getPost('type');
        $properties = $this->request->getPost('properties');

        if (!$type || !$properties) {
            $this->request->abort(400);
        }

        $entity = new EntityModel($this->db);

        $this->setContentType('json');

        $id = $entity->create(
            $properties,
            $this->session->get('auth_user')->get('id'),
            null,
            $type
        );

        if (!is_null($id)) {
            echo json_encode(['id' => $id]);
        } else {
            echo json_encode(
                ['errors' => $entity->getPropertyValidationErrors()]
            );
        }
    }

    public function update()
    {
        error_reporting(null);
        if (!$this->hasPermission('edit_annotations')) {
            $this->request->abort(403);
        }

        $id = $this->request->getPost('id');
        $properties = $this->request->getPost('properties');

        if (!$id || !$properties) {
            $this->request->abort(400);
        }

        $entity = new EntityModel($this->db);

        $this->setContentType('json');

        $status = $entity->update(
            $id,
            ['properties' => $properties],
            $this->session->get('auth_user')->get('id')
        );

        $response = ['id' => $id];
        if ($status === false) {
            $response['errors'] = $entity->getPropertyValidationErrors();
        }

        echo json_encode($response);
    }

    public function delete()
    {
        if (!$this->hasPermission('delete_annotations')) {
            $this->request->abort(403);
        }

        $entityId = $this->request->getPost('entity_id');

        if (!$entityId) {
            $this->request->abort(404);
        }

        $entity = new EntityModel($this->db);

        $status = $entity->delete($entityId);

        $this->setContentType('json');
        echo json_encode(['status' => $status]);
    }
}

// -- End of file
