<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Core\Foundation\Controller;
use Application\Models\User;
use DataTables\Database;
use Locale;

use function Application\createText as _;

/**
 * Class Base
 * @package Application\Controllers\Main
 */
class Base extends Controller
{
    /**
     * @var array
     */
    protected $permissions = [];

    /**
     * @var array
     */
    protected $attributeLabels = [];

    /**
     * @inheritdoc
     */
    protected function initialize()
    {
        parent::initialize();

        if (($this->request->getController() !== 'user')
            || !in_array($this->request->getAction(), ['login', 'logout'])) {
            $this->checkAuthentication();
            $userModel = new User($this->db);
            $this->permissions = $userModel->getPermissions(
                $this->session->get('auth_user')->get('id')
            );
        }

        $this->view->app = $this;
        $this->view->config = $this->config;
        $this->view->request = $this->request;
        $this->view->session = $this->session;
        $this->view->cache = $this->cache;
        $this->view->text = $this->text;

        $this->addPageResources();
        $this->definePageComponents();
    }

    /**
     * Checks whether the session has been authenticated.
     */
    public function checkAuthentication()
    {
        if (!$this->session->isAuthenticated()) {
            $requestedUrl = $this->request->getCurrentUrl();
            $this->session->set('requested_url', $requestedUrl);
            $this->request->redirect($this->getUrl('module', 'login'));
        }
    }

    /**
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        return array_key_exists($permission, $this->permissions);
    }

    /**
     * @param string ...$permissions
     * @return bool
     */
    public function hasAnyPermission(string ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string ...$permissions
     * @return bool
     */
    public function hasAllPermissions(string ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    public function getAttributeLabels(): array
    {
        return $this->attributeLabels;
    }

    public function initDataTables()
    {
        define('DATATABLES', true, true);

        $drivers = [
            'mysql' => 'Mysql',
            'oci8' => 'Oracle',
            'pgsql' => 'Postgres',
            'sqlite' => 'Sqlite',
            'mssql' => 'Sqlserver',
        ];

        return new Database([
            'type' => $drivers[$this->config->db->driver] ?? null,
            'pdo' => $this->db->getDataObject()
        ]);
    }

    protected function cacheTableData()
    {
        /*
        $classes = [
            'XmlNamespace',
            'LogActionType',
            'EntityType',
            'EntityProperty',
            'EntityRelationRule',
            'AgentType',
        ];
        foreach ($classes as $class) {
            $class = '\\Application\\Models\\' . $class;
            if (method_exists($class, 'cacheData')) {
                $class::cacheData($this->db, $this->cache);
            }
        }
        */
    }

    protected function addPageResources()
    {
        $this->view->addStyleSheets(
            $this->getAssetBundleUrls('app.css'),
            ['order' => 100]
        );
        $this->view->addStyleSheets(
            $this->getAssetBundleUrls('core.css')
        );
        $this->view->addScripts(
            $this->getAssetBundleUrls('app.js'),
            ['position' => 'head', 'order' => 100]
        );
        $this->view->addScripts(
            $this->getAssetBundleUrls('core.js'),
            ['position' => 'head']
        );
    }

    protected function addBreadcrumbs(
        array $breadcrumbs,
        bool $lastActive = true
    ) {
        $trail = $this->view->breadcrumbs ?: [];
        $i = 0;
        $n = count($breadcrumbs);
        foreach ($breadcrumbs as $breadcrumb) {
            $i++;
            $isLast = ($i === $n);
            $trail[] = [
                'title' => $breadcrumb['title'] ?? '',
                'url' => $breadcrumb['url'] ?? '',
                'active' => $isLast && $lastActive,
            ];
        }
        $this->view->breadcrumbs = $trail;
    }

    protected function definePageComponents()
    {
        $this->defineBreadcrumbs();
        $this->defineMainMenu();
        $this->defineLanguageMenu();
        $this->defineUserMenu();
        $this->defineFooterMenu();
    }

    protected function defineBreadcrumbs()
    {
        $this->view->breadcrumbs = [
            [
                'title' => /* #lang */ 'app.home',
                'url' => $this->getUrl('module'),
                'active' => $this->isActive('home', 'index'),
            ]
        ];
    }

    protected function defineMainMenu()
    {
        $menu = [
            [
                'title' => /* #lang */ 'app.home',
                'url' => $this->getUrl('module'),
                'active' => $this->isActive('home', 'index'),
                'icon' => 'home',
            ],
            [
                'title' => 'Archival Sources',
                'url' => $this->getUrl('module', 'documents'),
                'active' => $this->isActive('document'),
                'icon' => 'book',
            ],
            [   // Explore
                'title' => 'app.explore',
                'items' => [
                    [
                        'title' => /* #lang */ 'app.contracts',
                        'url' => $this->getUrl('module', 'contracts'),
                        'active' => $this->isActive('contract'),
                    ],
                    [
                        'title' => /* #lang */ 'app.persons',
                        'url' => $this->getUrl('module', 'persons'),
                        'active' => $this->isActive('person'),
                    ],
                    [
                        'title' => /* #lang */ 'app.person_mentions',
                        'url' => $this->getUrl('module', 'person-mentions'),
                        'active' => $this->isActive('person-mention'),
                    ],
                    [
                        'title' => 'Full-Text Search',
                        'url' => $this->getUrl('module', 'annotation/search'),
                        'active' => $this->isActive('annotation', 'search'),
                    ],
                    [
                        'title' => 'SPARQL Queries',
                        'url' => $this->getUrl('module', 'query'),
                        'active' => $this->isActive('home', 'page', ['query']),
                    ],
                    [
                        'title' => 'Map',
                        'url' => $this->getUrl('module', 'map'),
                        'active' => $this->isActive('home', 'page', ['map']),
                    ],
                ],
                'icon' => 'search',
                'pinned' => true,
            ],
            [
                'title' => /* #lang */ 'app.download',
                'url' => $this->getUrl('module', 'data/download'),
                'active' => $this->isActive('data', 'download'),
                'icon' => 'download',
            ],
            [
                'title' => /* #lang */ 'app.validate',
                'url' => $this->getUrl('module', 'data/validate'),
                'active' => $this->isActive('data', 'validate'),
                'icon' => 'check circle',
            ],
            [   // About
                'title' => 'About',
                'items' => [
                    [
                        'title' => 'About Project',
                        'url' => $this->getUrl('module', 'about-project'),
                        'active' => $this->isActive('home', 'page', ['about-project']),
                    ],
                    [
                        'title' => 'Historical Source',
                        'url' => $this->getUrl('module', 'historical-source'),
                        'active' => $this->isActive('home', 'page', ['historical-source']),
                    ],
                    [
                        'title' => 'Data Acquisition',
                        'url' => $this->getUrl('module', 'data-acquisition'),
                        'active' => $this->isActive('home', 'page', ['data-acquisition']),
                    ],
                    [
                        'title' => 'Data Model',
                        'url' => $this->getUrl('module', 'data-model'),
                        'active' => $this->isActive('home', 'page', ['data-model']),
                    ],
                    [
                        'title' => 'Terms of Use',
                        'url' => $this->getUrl('module', 'terms-of-use'),
                        'active' => $this->isActive('home', 'page', ['terms-of-use'])
                    ],
                ],
                'icon' => 'circle info',
                'pinned' => true,
            ],
            [   // Help
                'title' => 'Help',
                'items' => [
                    [
                        'title' => 'Data Exploration',
                        'url' => $this->getUrl('module', 'data-exploration'),
                        'active' => $this->isActive('home', 'page', ['data-exploration'])
                    ],
                    [
                        'title' => 'Faceted Search',
                        'url' => $this->getUrl('module', 'faceted-search'),
                        'active' => $this->isActive('home', 'page', ['faceted-search'])
                    ],
                    [
                        'title' => 'Full-Text Search',
                        'url' => $this->getUrl('module', 'full-text-search'),
                        'active' => $this->isActive('home', 'page', ['full-text-search'])
                    ],
                    [
                        'title' => 'SPARQL',
                        'url' => $this->getUrl('module', 'sparql'),
                        'active' => $this->isActive('home', 'page', ['sparql'])
                    ],
                    [
                        'title' => 'Data Exports',
                        'url' => $this->getUrl('module', 'data-exports'),
                        'active' => $this->isActive('home', 'page', ['data-exports'])
                    ],
                    [
                        'title' => 'Workflows',
                        'url' => $this->getUrl('module', 'workflows'),
                        'active' => $this->isActive('home', 'page', ['workflows'])
                    ],
                    [
                        'title' => 'FAQ',
                        'url' => $this->getUrl('module', 'faq'),
                        'active' => $this->isActive('home', 'page', ['faq'])
                    ],
                ],
                'icon' => 'question',
                'pinned' => true,
            ],
        ];

        // Internationalization
        /*
        $submenu = [];
        if ($this->hasPermission('view_translations')) {
            $submenu[] = [
                'title' => 'app.translations',
                'url' => $this->getUrl('module', 'translations'),
                'active' => $this->isActive('translation'),
            ];
        }
        if (!empty($submenu)) {
            $menu[] = [
                'title' => 'app.internationalization',
                'items' => $submenu,
                'icon' => 'world',
            ];
        }
        */

        // User Management
        $submenu = [];
        if ($this->hasPermission('view_users')) {
            $submenu[] = [
                'title' => /* #lang */ 'app.users',
                'url' => $this->getUrl('module', 'users'),
                'active' => $this->isActive('user'),
            ];
        }
        if ($this->hasPermission('view_roles')) {
            $submenu[] = [
                'title' => /* #lang */ 'app.roles',
                'url' => $this->getUrl('module', 'roles'),
                'active' => $this->isActive('role'),
            ];
        }
        if (!empty($submenu)) {
            $menu[] = [
                'title' => /* #lang */ 'app.user_management',
                'items' => $submenu,
                'icon' => 'users',
                'active' => false,
                'pinned' => true,
            ];
        }

        // Administrative Tools
        $submenu = [];
        if ($this->hasPermission('view_cache')) {
            $submenu[] = [
                'title' => /* #lang */ 'app.cache_manager',
                'url' => $this->getUrl('module', 'cache'),
                'active' => $this->isActive('cache'),
            ];
        }
        if (!empty($submenu)) {
            $menu[] = [
                'title' => /* #lang */ 'app.administrative_tools',
                'items' => $submenu,
                'icon' => 'configure',
            ];
        }

        foreach ($menu as $i => $item) {
            if (!isset($item['active'])) {
                $menu[$i]['active'] = false;
            }
            if (isset($item['items'])) {
                foreach ($item['items'] as $j => $subitem) {
                    if (!isset($subitem['active'])) {
                        $menu[$i][$j]['active'] = false;
                    } elseif ($subitem['active']) {
                        $menu[$i]['active'] = true;
                    }
                }
            }
        }

        $this->view->main_menu = $menu;
    }

    protected function defineUserMenu()
    {
        $menu = [];
        if ($this->session->isAuthenticated()) {
            if (!$this->session->get('is_guest')) {
                $menu = [
                    [
                        'title' => /* #lang */ 'app.profile',
                        'url' => $this->getUrl('module', 'profile'),
                        'icon' => 'user',
                    ],
                    [
                        'title' => /* #lang */ 'app.settings',
                        'url' => $this->getUrl('module', 'settings'),
                        'icon' => 'setting',
                    ],
                ];
            }
            $menu[] = [
                'title' => /* #lang */ 'app.logout',
                'url' => $this->getUrl('module', 'logout'),
                'icon' => 'sign out',
            ];
        }
        $this->view->user_menu = $menu;
    }

    protected function defineLanguageMenu()
    {
        $menu = [];
        $requestUri = ltrim($this->request->getRequestUri(), '/');
        $requestUri = preg_replace(
            '@^' . $this->request->getLanguage() . '(\/|$)@', '',
            $requestUri
        );
        foreach ($this->config->languages->toArray() as $code => $properties) {
            if ($code !== $this->config->request->language) {
                $url = $this->getUrl('base', $code . '/' . $requestUri);
            } else {
                $url = $this->getUrl('base', $requestUri);
            }
            $menu[$code] = [
                'title' => _(Locale::getDisplayLanguage($code, $code))->toTitleCase(),
                'url' => $url,
                'active' => ($code === $this->request->getLanguage())
            ];
        }
        $this->view->language_menu = $menu;
    }

    protected function defineFooterMenu()
    {
        /*
        $menu = [
            [
                'title' => 'app.language',
                'items' => $this->view->language_menu,
                'icon' => 'translate',
            ]
        ];
        */
        $menu = [];
        foreach ($this->view->main_menu as $item) {
            if (isset($item['pinned']) && ($item['pinned'] === true)) {
                $menu[] = $item;
            }
        }
        if (!empty($this->view->user_menu)) {
            $menu[] = [
                'title' => /* #lang */ 'app.account',
                'items' => $this->view->user_menu,
                'icon' => 'user',
            ];
        }
        $this->view->footer_menu = $menu;
    }

    protected function isActive(
        string $controller = '',
        string $action = '',
        array $attributes = []
    ): bool {
        if ($controller && ($this->request->getController() !== $controller)) {
            return false;
        }
        if ($action && ($this->request->getAction() !== $action)) {
            return false;
        }
        if ($attributes) {
            foreach ($this->request->getAttributes() as $index => $value) {
                if (isset($attributes[$index]) && ($attributes[$index] !== $value)) {
                    return false;
                }
            }
        }
        return true;
    }
}

// -- End of file
