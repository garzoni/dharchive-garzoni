<?php

declare(strict_types=1);

namespace Application;

use Application\Core\Cache\ApcStore;
use Application\Core\Database\Database;
use Application\Core\Exception\ExceptionHandler;
use Application\Core\Foundation\Request;
use Application\Core\Foundation\View;
use Application\Core\Log\LogWriter;
use Application\Core\Session\PdoSessionHandler;
use Application\Core\Session\Session;
use Application\Core\Text\Translator;

use function Application\createText as _;

/*----------------------------------------------------------------------------
   Includes
  ----------------------------------------------------------------------------*/

// Constants
require 'constants.php';

// Class autoloading
require 'autoload.php';

// Common functions
require 'functions.php';

/*----------------------------------------------------------------------------
   Configuration
  ----------------------------------------------------------------------------*/

// Load application configuration
$config = require ROOT_DIR . 'configuration/main.conf.php';

/*----------------------------------------------------------------------------
   Error Handling
  ----------------------------------------------------------------------------*/

// Set error reporting and configuration options
error_reporting($config->env->error_reporting);
ini_set('display_errors', $config->env->display_errors ? '1' : '0');

// Instantiate a log writer
$logWriter = new LogWriter($config->dir->logs, $config->log->date_format);

// Error pages directory
$errorPagesDir = $config->dir->public . 'errors/';

// Instantiate an exception handler
$exceptionHandler = new ExceptionHandler(
    $logWriter,
    $errorPagesDir . '500.html'
);

// Set error and exception handlers
set_error_handler(array($exceptionHandler, 'reportError'));
set_exception_handler(array($exceptionHandler, 'reportException'));

/*----------------------------------------------------------------------------
   Cache
  ----------------------------------------------------------------------------*/

// Instantiate a cache store
$cacheStore = new ApcStore($config->cache->lifetime);
$cacheStore->setPrefix($config->cache->prefix);

/*----------------------------------------------------------------------------
   Request
  ----------------------------------------------------------------------------*/

// Load route rules
$routes = require $config->files->routes;

// Instantiate a request manager
$request = new Request(
    $config->request->language,
    $config->request->module,
    $config->request->controller,
    $config->request->action,
    $errorPagesDir
);

// Parse the current request
$request->parseRequestQuery($config->languages, $config->modules, $routes);

/*----------------------------------------------------------------------------
   Localization
  ----------------------------------------------------------------------------*/

$language = $config->languages[$request->getLanguage()];

// Set timezone
date_default_timezone_set($config->timezone);

// Set character encoding
mb_internal_encoding($config->charset);
mb_regex_encoding($config->charset);

// Set locale
setlocale(LC_ALL, $language['locale'] . '.' . $config->charset);

/*----------------------------------------------------------------------------
   Translation
  ----------------------------------------------------------------------------*/

// Instantiate a translator
$translator = new Translator(
    $config->dir->lang,
    $request->getLanguage(),
    $config->request->language,
    $cacheStore
);

/*----------------------------------------------------------------------------
   Database
  ----------------------------------------------------------------------------*/

// Instantiate a PDO wrapper
$database = new Database(
    $config->db->dsn,
    $config->db->username,
    $config->db->password
);

/*----------------------------------------------------------------------------
   Session
  ----------------------------------------------------------------------------*/

// Send the session ID cookie only through an encrypted connection
if ($config->protocol === 'https') {
    ini_set('session.cookie_secure', '1');
}

// Mark the session ID cookie as accessible only through the HTTP protocol
ini_set('session.cookie_httponly', '1');

// Instantiate a session handler
$sessionHandler = new PdoSessionHandler(
    $database->getDataObject(),
    $config->session->handler->toArray()
);

// Instantiate a session manager
$session = new Session(
    $sessionHandler,
    $config->session->options->toArray()
);

// Start the Session
$session->start();

/*----------------------------------------------------------------------------
   Request Processing
  ----------------------------------------------------------------------------*/

// Instantiate a view object
$view = new View($config->dir->templates);

// Resolve the controller's class name
$controller = implode(NAMESPACE_SEPARATOR, [
    __NAMESPACE__,
    'Controllers',
    _($request->getModule())->pascalize(),
    _($request->getController())->pascalize()
]);

if (!class_exists($controller)) {
    $request->abort(404);
    exit(1);
}

// Instantiate a request handler
$requestHandler = new $controller(
    $config,
    $logWriter,
    $request,
    $translator,
    $cacheStore,
    $database,
    $session,
    $view
);

// Resolve the action method's name
$method = (string) _($request->getAction())->camelize();

// Check whether the method is defined
if (!is_callable([$requestHandler, $method])) {
    $request->abort(404);
}

// Handle the request
$requestHandler->$method();

// -- End of file
