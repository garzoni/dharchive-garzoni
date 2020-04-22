<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Core\Cache\CacheInterface;
use Application\Core\Configuration\Repository;
use Application\Core\Database\Database;
use Application\Core\Foundation\Request;
use Application\Core\Foundation\View;
use Application\Core\Log\LogWriter;
use Application\Core\Session\Session;
use Application\Core\Text\Translator;
use Application\Models\Entity\ManifestCollection;


/**
 * Class Dashboard
 * @package Application\Controllers\Main
 */
class Statistics extends Base
{
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

        $this->view->page_stylesheets = array_merge(
            $this->view->page_stylesheets,
            $this->getAssetUrls('visualization.css')
        );

        $this->view->page_scripts = array_merge(
            $this->view->page_scripts,
            $this->getAssetUrls('visualization.js')
        );

        $this->cacheTableData();
    }

    public function index()
    {
        if (!$this->hasAnyPermission('view_statistics', 'export_statistics')) {
            $this->request->abort(403);
        }

        $type = $this->request->getAttribute(0);

        $collection = new ManifestCollection($this->db);

        $this->view->type = $type;
        $this->view->page_title = $this->text->get('app.statistics');
        $this->view->collections = $collection->getStatistics();
        $this->view->chart_css_url = $this->getAssetUrl('css/taucharts.src.css', false);

        echo $this->view->render('pages/statistics/index.tpl.php');
    }

    public function view()
    {
        if (!$this->hasAnyPermission('view_statistics', 'export_statistics')) {
            $this->request->abort(403);
        }
        $this->request->redirect($this->getUrl('module', 'statistics'));
    }
}

// -- End of file
