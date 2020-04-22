<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Locale;

use function Application\createText as _;

/**
 * Class Translation
 * @package Application\Controllers\Main
 */
class Translation extends Base
{
    public function index()
    {
        $languages = [];
        foreach ($this->config->languages->toArray() as $code => $properties) {
            $languages[$code] = _(Locale::getDisplayLanguage($code, $this->request->getLanguage()))->toTitleCase();
        }
        asort($languages);

        $this->view->page_title = $this->text->get('app.translations');
        $this->view->languages = $languages;
        echo $this->view->render('pages/translation/index.tpl.php');
    }

    public function view()
    {
        if (!$this->hasPermission('view_translations')) {
            $this->request->abort(403);
        }

        $languageCode = $this->request->getAttribute(0);

        if (!$languageCode) {
            $this->request->abort(404);
        }

        $rules = $this->text->getDictionary($languageCode);

        if (!$rules) {
            $this->request->abort(404);
        }

        $this->view->addStyleSheets(
            $this->getAssetBundleUrls('datatables.css')
        );
        $this->view->addScripts(
            $this->getAssetBundleUrls('datatables.js')
        );

        $this->view->rules = $rules->flatten(true)->toArray();
        echo $this->view->render('pages/translation/view.tpl.php');
    }
}

// -- End of file
