<?php

declare(strict_types=1);

namespace Application\Controllers\Api;

use Application\Core\Cache\CacheInterface;
use Application\Core\Configuration\Repository;
use Application\Core\Database\Database;
use Application\Core\Foundation\Request;
use Application\Core\Foundation\View;
use Application\Core\Log\LogWriter;
use Application\Core\Session\Session;
use Application\Core\Text\Translator;

/**
 * Class Script
 * @package Application\Controllers\Iiif
 */
class Script extends Base
{
    /**
     * @var array Error messages
     */
    protected static $errors = array(
        'missing_request_params'    => 'Undefined request parameters',
        'invalid_request_params'    => 'Invalid request parameters',
        'invalid_uuid'              => '%s is not a valid UUID',
        'invalid_name'              => '%s is not a valid %s name',
    );

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

        $this->setContentType('js');
    }

    public function index() {}

    public function export()
    {
        $scriptId = $this->request->getAttribute(0);

        switch ($scriptId) {
            case 'i18n.js':
                $this->exportI18n();
                break;
            default:
                $this->exit(self::$errors['missing_request_params']);
        }
    }

    protected function exportI18n()
    {
        $systemLanguages = $this->config->languages->toArray();
        $requestedLanguages = [];

        $langParam = $this->request->getQuery('lang');
        if ($langParam) {
            foreach (explode(',', $langParam) as $lang) {
                if (array_key_exists($lang, $systemLanguages)) {
                    $requestedLanguages[] = $lang;
                }
            }
        }

        $namespaces = $this->request->getQuery('ns');
        $namespaces = $namespaces ? explode(',', $namespaces) : [];

        $this->view->namespaces = !empty($namespaces) ? $namespaces : ['validation'];

        $this->view->languages = !empty($requestedLanguages)
            ? $requestedLanguages : array_keys($systemLanguages);

        $this->view->compact = !is_null($this->request->getQuery('compact'))
            ? (boolean) $this->request->getQuery('compact') : true;

        echo $this->view->render('resources/i18n.tpl.php');
    }

    /**
     * Terminates the current request and returns an error object.
     *
     * @param int $statusCode
     * @param string $errorMessage an error message
     */
    private function exit(string $errorMessage, int $statusCode = 400)
    {
        $statusResponse = null;
        if (array_key_exists($statusCode, Request::$messages)) {
            $statusResponse = $statusCode . ' ' . Request::$messages[$statusCode];
        }

        if ($statusResponse && !headers_sent()) {
            header('HTTP/1.0 ' . $statusResponse);
        }

        $response = [
            'errors' => [
                [
                    'status' => $statusResponse,
                    'detail' => $errorMessage,
                ],
            ],
        ];

        echo json_encode($response);

        exit(1);
    }
}

// -- End of file
