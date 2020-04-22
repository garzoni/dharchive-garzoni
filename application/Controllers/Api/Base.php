<?php

declare(strict_types=1);

namespace Application\Controllers\Api;

use Application\Core\Foundation\Controller;


/**
 * Class Base
 * @package Application\Controllers\Main
 */
class Base extends Controller
{
    /**
     * @inheritdoc
     */
    protected function initialize()
    {
        parent::initialize();

        $this->view->app = $this;
        $this->view->config = $this->config;
        $this->view->request = $this->request;
        $this->view->session = $this->session;
        $this->view->cache = $this->cache;
        $this->view->text = $this->text;
    }
}

// -- End of file
