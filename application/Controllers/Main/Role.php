<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Models\Permission;
use Application\Models\Role as RoleModel;

/**
 * Class Role
 * @package Application\Controllers\Main
 */
class Role extends Base
{
    public function index()
    {
        if (!$this->hasPermission('view_roles')) {
            $this->request->abort(403);
        }

        $this->view->page_title = $this->text->get('app.roles');

        $roleModel = new RoleModel($this->db);
        $this->view->roles = $roleModel->fetchAll([], ['code'])->toArray();

        $this->view->role_index_url = $this->getUrl('controller');
        $this->view->role_view_url = $this->getUrl('controller', 'view');
        $this->view->role_list_url = $this->getUrl('module', 'roles');

        echo $this->view->render('pages/role/index.tpl.php');
    }

    public function create()
    {
        if (!$this->hasPermission('create_roles')) {
            $this->request->abort(403);
        }

        if ($this->request->isPostRequest()) {
            $submittedData = [
                'code' => $this->request->getPost('code'),
            ];

            $roleModel = new RoleModel($this->db);
            if ($roleModel->create($submittedData)) {
                $this->request->redirect($this->getUrl('module', 'roles'));
            }
        } else {
            $submittedData = [];
        }

        $this->view->page_title = $this->text->get('role.new');
        $this->addBreadcrumbs([
            ['title' => $this->view->page_title]
        ]);

        $this->view->submitted_data = $submittedData;

        echo $this->view->render('pages/role/create.tpl.php');
    }

    public function update()
    {
        if (!$this->hasPermission('edit_roles')) {
            $this->request->abort(403);
        }

        $this->request->redirect($this->getUrl('controller'));
    }

    public function delete()
    {
        if (!$this->hasPermission('delete_roles')) {
            $this->request->abort(403);
        }

        $roleId = (int) $this->request->getPost('role_id');

        if (!$roleId) {
            $this->request->abort(400);
        }

        $roleModel = new RoleModel($this->db);

        $status = $roleModel->delete($roleId);

        $this->setContentType('json');
        echo json_encode(['status' => $status]);
    }

    public function view()
    {
        if (!$this->hasPermission('view_roles')) {
            $this->request->abort(403);
        }

        $roleId = (int) $this->request->getAttribute(0);

        if (!$roleId) {
            $this->request->abort(404);
        }

        $roleModel = new RoleModel($this->db);
        $permission = new Permission($this->db);

        $role = $roleModel->fetch($roleId);

        if ($role->isEmpty()) {
            $this->request->abort(404);
        }

        $this->view->page_title = $this->text->get('role.' . $role['code']);
        $this->addBreadcrumbs([
            ['title' => $this->view->page_title]
        ]);

        $this->view->role = $role;
        $this->view->is_admin_role = ($role['code'] === RoleModel::ADMIN_ROLE_CODE);
        $this->view->permissions = $permission->fetchAll([], ['id'])->toArray();
        $this->view->role_permissions = array_flip($roleModel->getPermissions($roleId));
        $this->view->role_index_url = $this->getUrl('controller');

        echo $this->view->render('pages/role/view.tpl.php');
    }

    public function setPermission()
    {
        if (!$this->hasPermission('edit_roles')) {
            $this->request->abort(403);
        }

        $roleId = (int) $this->request->getPost('role_id');
        $permissionId = (int) $this->request->getPost('permission_id');
        $action = $this->request->getPost('action');

        if (!$roleId || !$permissionId || !in_array($action, ['add', 'remove'])) {
            $this->request->abort(400);
        }

        $roleModel = new RoleModel($this->db);

        if ($action === 'add') {
            $status = $roleModel->addPermission($roleId, $permissionId);
        } elseif ($action === 'remove') {
            $status = $roleModel->removePermission($roleId, $permissionId);
        } else {
            $status = false;
        }

        $this->setContentType('json');
        echo json_encode(['status' => $status]);
    }
}

// -- End of file
