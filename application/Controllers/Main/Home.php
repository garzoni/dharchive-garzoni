<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

/**
 * Class Home
 * @package Application\Controllers\Main
 */
class Home extends Base
{
    public function index()
    {
        echo $this->view->render('pages/home/index.tpl.php');
    }

    public function page()
    {
        $title = '';
        $template = '';
        switch ($this->request->getAttribute(0)) {
            case 'map':
                $title = 'Map';
                $template = 'pages/home/map.tpl.php';
                $this->view->addStyleSheets(
                    $this->getAssetBundleUrls('map.css')
                );
                $this->view->addScripts(
                    $this->getAssetBundleUrls('map.js')
                );
                break;
            case 'query':
                $title = 'SPARQL Queries';
                $template = 'pages/home/query.tpl.php';
                $this->view->addStyleSheets(
                    $this->getAssetBundleUrls('sparql.css')
                );
                $this->view->addScripts(
                    $this->getAssetBundleUrls('sparql.js')
                );
                break;
            case 'about-project':
                $title = 'About the Project';
                $template = 'pages/home/about_project.tpl.php';
                break;
            case 'historical-source':
                $title = 'Historical Source';
                $template = 'pages/home/historical_source.tpl.php';
                break;
            case 'data-acquisition':
                $title = 'Data Acquisition';
                $template = 'pages/home/data_acquisition.tpl.php';
                break;
            case 'data-model':
                $title = 'Data Model';
                $template = 'pages/home/data_model.tpl.php';
                break;
            case 'terms-of-use':
                $title = 'Terms of Use and Citation';
                $template = 'pages/home/terms_of_use.tpl.php';
                break;
            case 'data-exploration':
                $title = 'Data Exploration';
                $template = 'pages/home/data_exploration.tpl.php';
                break;
            case 'faceted-search':
                $title = 'Faceted Search';
                $template = 'pages/home/faceted_search.tpl.php';
                break;
            case 'full-text-search':
                $title = 'Full-Text Search';
                $template = 'pages/home/full_text_search.tpl.php';
                break;
            case 'sparql':
                $title = 'SPARQL';
                $template = 'pages/home/sparql.tpl.php';
                break;
            case 'data-exports':
                $title = 'Data Exports';
                $template = 'pages/home/data_exports.tpl.php';
                break;
            case 'workflows':
                $title = 'Workflows';
                $template = 'pages/home/workflows.tpl.php';
                break;
            case 'faq':
                $title = 'Frequently Asked Questions';
                $template = 'pages/home/faq.tpl.php';
                break;
            default:
                $this->request->abort(404);
        }
        if ($title) {
            $this->view->page_title = $title;
        }
        if ($template) {
            echo $this->view->render($template);
        }
    }
}

// -- End of file
