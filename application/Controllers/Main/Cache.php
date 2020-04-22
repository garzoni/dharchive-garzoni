<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Models\ApcCache;

/**
 * Class Cache
 * @package Application\Controllers\Main
 */
class Cache extends Base
{
    public function index()
    {
        if (!$this->hasPermission('view_cache')) {
            $this->request->abort(403);
        }

        $this->view->page_title = $this->text->get('cache.apc_cache');

        $apcCache = new ApcCache($this->text);

        $this->view->general_info = $apcCache->getGeneralInfo();
        $this->view->cache_info = $apcCache->getCacheInfo();
        $this->view->cached_items = $apcCache->getItems(
            $this->config->cache->prefix
        )->sort(['key']);

        echo $this->view->render('pages/cache/index.tpl.php');
    }

    public function view()
    {
        if (!$this->hasPermission('view_cache')) {
            $this->request->abort(403);
        }

        $key = $this->request->getAttribute(0);
        $item = $this->cache->get($key);
        if (empty($item)) {
            $this->request->abort(404);
        }

        $this->view->addStyleSheets($this->getAssetBundleUrls('prism.css'));
        $this->view->addScripts($this->getAssetBundleUrls('prism.js'));

        $this->view->page_title = $key;
        $this->addBreadcrumbs([
            ['title' => $this->text->get('cache.cached_item')]
        ]);

        $this->view->key = $key;
        $this->view->item = $item;

        echo $this->view->render('pages/cache/view.tpl.php');
    }

    public function delete()
    {
        if (!$this->hasPermission('delete_cache')) {
            $this->request->abort(403);
        }

        $key = $this->request->getAttribute(0);
        if (empty($key)) {
            $apcCache = new ApcCache($this->text);
            $items = $apcCache->getItems(
                $this->config->cache->prefix
            )->toArray();
            foreach ($items as $item) {
                $this->cache->delete($item['key']);
            }
            $this->session->addMessage('info',
                $this->text->get('cache.info.cache_flushed')
            );
        } else {
            $this->cache->delete($key);
            $this->session->addMessage('info',
                $this->text->interpolate(
                    'cache.info.item_deleted',
                    ['key' => '<b>' . $key . '</b>']
                )
            );
        }
        $this->request->redirect($this->getUrl('controller'));
    }

    public function flush()
    {
        if (!$this->hasPermission('delete_cache')) {
            $this->request->abort(403);
        }

        $this->cache->flush();
        $this->session->addMessage('info',
            $this->text->get('cache.info.cache_flushed')
        );
        $this->request->redirect($this->getUrl('controller'));
    }
}

// -- End of file
