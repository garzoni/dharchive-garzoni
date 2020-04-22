<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Core\Text\TextGenerator;
use Application\Models\Role;
use Application\Models\User as UserModel;
use Application\Providers\Authenticator;
use Application\Providers\Filter;

use const Application\REGEX_EMAIL;

/**
 * Class User
 * @package Application\Controllers\Main
 */
class User extends Base
{
    const GUEST_USERNAME = 'guest';

    /**
     * @inheritdoc
     */
    protected function initialize()
    {
        parent::initialize();

        $this->attributeLabels = [
            'username' => $this->text->get('user.username'),
            'email' => $this->text->get('user.email'),
            'first_name' => $this->text->get('user.first_name'),
            'last_name' => $this->text->get('user.last_name'),
            'password' => $this->text->get('user.password'),
            'password_repeat' => $this->text->get('user.password_repeat'),
        ];
    }

    public function index()
    {
        if (!$this->hasPermission('view_users')) {
            $this->request->abort(403);
        }

        $this->view->page_title = $this->text->get('app.users');

        $userModel = new UserModel($this->db);

        $this->view->users = $userModel->fetchAll(
            ['id', 'username', 'email', 'full_name', 'registration_time', 'is_active'],
            ['id']
        )->toArray();

        $this->view->user_index_url = $this->getUrl('controller');
        $this->view->user_view_url = $this->getUrl('controller', 'view');
        $this->view->user_list_url = $this->getUrl('module', 'users');

        echo $this->view->render('pages/user/index.tpl.php');
    }

    public function login()
    {
        $this->view->title = $this->text->get('app.login');

        if ($this->session->isAuthenticated()) {
            $this->request->redirect($this->getUrl('module'));
        }

        if ($this->request->getPost('action') == 'login') {
            $authenticator = new Authenticator(
                $this->db,
                $this->session,
                $this->text
            );
            $isAuthenticated = $authenticator->authenticate(
                $this->request->getPost('login-uid'),
                $this->request->getPost('password'),
                $this->request->getUserHostAddress()
            );
            if ($isAuthenticated) {
                $isGuest = ($this->session->get('auth_user')->get('username') === self::GUEST_USERNAME);
                $this->session->set('is_guest', $isGuest);
                $this->request->redirect(
                    $this->session->steal('requested_url')
                        ?: $this->getUrl('module')
                );
            }
        }

        $this->view->form_handler = $this->getUrl('action');

        echo $this->view->render('pages/user/login.tpl.php');
    }

    public function logout()
    {
        $authenticator = new Authenticator(
            $this->db,
            $this->session,
            $this->text
        );
        $authenticator->invalidate();
        $this->request->redirect($this->getUrl('module', 'login'));
    }

    public function create()
    {
        if (!$this->hasPermission('create_users')) {
            $this->request->abort(403);
        }

        if ($this->request->isPostRequest()) {
            $submittedData = [
                'username' => $this->request->getPost('username'),
                'email' => $this->request->getPost('email'),
                'first_name' => $this->request->getPost('first_name'),
                'last_name' => $this->request->getPost('last_name'),
                'password' => $this->request->getPost('password'),
                'password_repeat' => $this->request->getPost('password_repeat'),
            ];

            $filter = new Filter($this->session, $this->text);
            $filter->sanitize($submittedData, [
                [['username', 'first_name', 'last_name'], 'string'],
                ['email', 'email']
            ]);
            $filter->format($submittedData, [
                [['username', 'first_name', 'last_name', 'email'], 'trim'],
                ['email', 'lower_case'],
            ]);
            $isValidData = $filter->validate($submittedData, [
                [['username', 'email', 'password', 'password_repeat'], 'required'],
                ['username', '~*', UserModel::USERNAME_REGEX],
                ['email', '~*', REGEX_EMAIL],
            ], $this->getAttributeLabels(), true);
            if ($isValidData) {
                if ($submittedData['password'] !== $submittedData['password_repeat']) {
                    $this->session->addMessage('error',
                        $this->text->get('user.error.password_mismatch')
                    );
                    $isValidData = false;
                }
            }
            $userModel = new UserModel($this->db);
            if ($isValidData) {
                if (!$userModel
                    ->findByUsername($submittedData['username'], ['id'])
                    ->isEmpty()) {
                    $this->session->addMessage('error',
                        $this->text->get('user.error.username_exists')
                    );
                    $isValidData = false;
                } elseif (!$userModel
                    ->findByEmail($submittedData['email'], ['id'])
                    ->isEmpty()) {
                    $this->session->addMessage('error',
                        $this->text->get('user.error.email_exists')
                    );
                    $isValidData = false;
                }
            }
            if ($isValidData && $userModel->create($submittedData)) {
                $this->request->redirect($this->getUrl('module', 'users'));
            }
        } else {
            $submittedData = [];
        }

        $this->view->page_title = $this->text->get('user.new');
        $this->addBreadcrumbs([
            ['title' => $this->view->page_title]
        ]);

        $textGenerator = new TextGenerator();
        $this->view->random_password = $textGenerator->getAsciiAlphanumeric(12);
        $this->view->submitted_data = $submittedData;

        echo $this->view->render('pages/user/create.tpl.php');
    }

    public function update()
    {
        $userId = (int) $this->request->getAttribute(0);

        if (!$userId) {
            $userId = $this->session->get('auth_user')->get('id');
        }

        if (!$this->hasAnyPermission('edit_users')
            && ($this->session->get('auth_user')->get('id') !== $userId)) {
            $this->request->abort(403);
        }

        if ($this->session->get('is_guest')) {
            $this->request->abort(403);
        }

        $userModel = new UserModel($this->db);

        $user = $userModel->fetch($userId);

        if ($user->isEmpty()) {
            $this->request->abort(404);
        }

        $userPageUrl = $this->getUrl('module', 'user/view/' . $userId);

        if ($this->request->isPostRequest()) {
            $submittedData = [
                'username' => $user->get('username'),
                'email' => $user->get('email'),
                'first_name' => $this->request->getPost('first_name'),
                'last_name' => $this->request->getPost('last_name'),
            ];

            $filter = new Filter($this->session, $this->text);
            $filter->sanitize($submittedData, [
                [['first_name', 'last_name'], 'string'],
            ]);
            $filter->format($submittedData, [
                [['first_name', 'last_name'], 'trim'],
            ]);
            $isValidData = true;
            $userModel = new UserModel($this->db);
            if ($isValidData && $userModel->update($userId, $submittedData)) {
                $this->request->redirect($userPageUrl);
            }
        } else {
            $submittedData = [
                'username' => $user->get('username'),
                'email' => $user->get('email'),
                'first_name' => $user->get('first_name'),
                'last_name' => $user->get('last_name'),
            ];
        }

        $this->view->page_title = $user['full_name'];
        $this->addBreadcrumbs([
            ['title' => $user['username'], 'url' => $userPageUrl],
            ['title' => $this->text->get('app.edit')]
        ]);

        $this->view->submitted_data = $submittedData;

        echo $this->view->render('pages/user/update.tpl.php');
    }

    public function changePassword()
    {
        $userId = (int) $this->request->getAttribute(0);

        if (!$userId) {
            $this->request->abort(404);
        }

        if (!$this->hasAnyPermission('edit_users')
            && ($this->session->get('auth_user')->get('id') !== $userId)) {
            $this->request->abort(403);
        }

        if ($this->session->get('is_guest')) {
            $this->request->abort(403);
        }

        $userModel = new UserModel($this->db);

        $user = $userModel->fetch($userId);

        if ($user->isEmpty()) {
            $this->request->abort(404);
        }

        $userPageUrl = $this->getUrl('module', 'user/view/' . $userId);

        if ($this->request->isPostRequest()) {
            $submittedData = [
                'password' => $this->request->getPost('password'),
                'password_repeat' => $this->request->getPost('password_repeat'),
            ];

            $filter = new Filter($this->session, $this->text);
            $isValidData = $filter->validate($submittedData, [
                [['password', 'password_repeat'], 'required'],
            ], $this->getAttributeLabels(), true);
            if ($isValidData) {
                if ($submittedData['password'] !== $submittedData['password_repeat']) {
                    $this->session->addMessage('error',
                        $this->text->get('user.error.password_mismatch')
                    );
                    $isValidData = false;
                }
            }
            $userModel = new UserModel($this->db);
            if ($isValidData && $userModel->changePassword($userId, $submittedData)) {
                $this->request->redirect($userPageUrl);
            }
        } else {
            $submittedData = [];
        }

        $textGenerator = new TextGenerator();
        $this->view->random_password = $textGenerator->getAsciiAlphanumeric(12);
        $this->view->submitted_data = $submittedData;

        $this->view->page_title = $user['full_name'];
        $this->addBreadcrumbs([
            ['title' => $user['username'], 'url' => $userPageUrl],
            ['title' => $this->text->get('user.change_password')]
        ]);

        echo $this->view->render('pages/user/change_password.tpl.php');
    }

    public function view()
    {
        $userId = (int) $this->request->getAttribute(0);

        if (!$userId) {
            $userId = $this->session->get('auth_user')->get('id');
        }

        if (!$this->hasPermission('view_users')
            && ($this->session->get('auth_user')->get('id') !== $userId)) {
            $this->request->abort(403);
        }

        if ($this->session->get('is_guest')) {
            $this->request->abort(403);
        }

        $userModel = new UserModel($this->db);
        $role = new Role($this->db);

        $user = $userModel->fetch($userId);

        if ($user->isEmpty()) {
            $this->request->abort(404);
        }

        $this->view->page_title = $user['full_name'];
        $this->addBreadcrumbs([
            ['title' => $user['username']]
        ]);

        $this->view->user = $user;
        $this->view->roles = $role->fetchAll([], ['id'])->toArray();
        $this->view->user_roles= array_flip($userModel->getRoles($userId));
        $this->view->user_index_url = $this->getUrl('controller');

        echo $this->view->render('pages/user/view.tpl.php');
    }

    public function setStatus()
    {
        if (!$this->hasPermission('edit_users')) {
            $this->request->abort(403);
        }

        $userId = (int) $this->request->getPost('user_id');
        $isActive = (bool) $this->request->getPost('is_active');

        if (!$userId) {
            $this->request->abort(400);
        }

        $userModel = new UserModel($this->db);

        if ($isActive) {
            $status = $userModel->restore($userId);
        } else {
            $status = $userModel->invalidate($userId);
        }

        $this->setContentType('json');
        echo json_encode(['status' => $status]);
    }

    public function setRole()
    {
        if (!$this->hasPermission('edit_users')) {
            $this->request->abort(403);
        }

        $userId = (int) $this->request->getPost('user_id');
        $roleId = (int) $this->request->getPost('role_id');
        $action = $this->request->getPost('action');

        if (!$userId || !$roleId || !in_array($action, ['add', 'remove'])) {
            $this->request->abort(400);
        }

        $userModel = new UserModel($this->db);

        if ($action === 'add') {
            $status = $userModel->addRole($userId, $roleId);
        } elseif ($action === 'remove') {
            $status = $userModel->removeRole($userId, $roleId);
        } else {
            $status = false;
        }

        $this->setContentType('json');
        echo json_encode(['status' => $status]);
    }
}

// -- End of file
