<?php

declare(strict_types=1);

namespace Application\Core\Foundation;

use Application\Core\Cache\CacheInterface;
use Application\Core\Configuration\Repository;
use Application\Core\Database\Database;
use Application\Core\Log\LogWriter;
use Application\Core\Session\Session;
use Application\Core\Text\Translator;

/**
 * Class Controller
 * @package Application\Core\Foundation
 */
class Controller
{
    /**
     * @var array Error messages
     */
    protected static $errors = [
        'invalid_segment_level' => 'Invalid URL segment level: "%s"',
        'file_not_readable'     => 'The file "%s" is not readable',
        'assets_type_error'     => 'Assets should be returned as an array',
        'mime_types_type_error' => 'MIME types should be returned as an array',
        'mime_types_not_found'  => 'No MIME types for ".%s" files are defined',
    ];

    /**
     * @var Repository
     */
    protected $config;

    /**
     * @var LogWriter
     */
    protected $logger;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Translator
     */
    protected $text;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var Database
     */
    protected $db;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var View
     */
    protected $view;

    /**
     * @var array
     */
    protected $assets;

	/**
	 * @var array
	 */
    protected $mimeTypes;

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
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->request = $request;
        $this->text = $text;
        $this->cache = $cache;
        $this->db = $db;
        $this->session = $session;
        $this->view = $view;
        $this->assets = [];
        $this->mimeTypes = [];

        $this->initialize();
    }

    /**
     * Executed right after the class properties are initialized.
     */
    protected function initialize() {}

    /**
     * A default action to be performed.
     * This method must be overridden by child classes, otherwise it
     * will terminate the current request with an error response:
     * 500 Internal Server Error
     */
    public function index()
    {
        $this->request->abort(500);
    }

    /**
     * Returns a local URL.
     *
     * @param string $level URL segment level
     * @param string $path A path to be appended
	 * @return string
     */
	public function getUrl(
        string $level = '',
        string $path = ''
    ): string {
        // Validate URL segment level
        if (empty($level)) {
            return $this->request->getCurrentUrl();
        } elseif (array_key_exists($level, Request::URL_SEGMENT_LEVELS)) {
            $levelCode = Request::URL_SEGMENT_LEVELS[$level];
        } else {
            throw new \InvalidArgumentException(
                sprintf(self::$errors['invalid_segment_level'], $level)
            );
        }

        // Normalize path
        $path = trim($path, '/');

        // Base URL
        $url = $this->config->url->base;

        // Defaults
        $defaultLanguage = $this->config->request->language;
        $defaultModule = $this->config->request->module;
        $defaultController = $this->config->request->controller;
        $defaultAction = $this->config->request->action;

		// Request parameters
		$language = $this->request->getLanguage();
		$module = $this->request->getModule();
		$controller = $this->request->getController();
		$action = $this->request->getAction();
        $attributes = $this->request->getAttributes();

		if (($levelCode >= 1) && ($language != $defaultLanguage)) {
			$url .= $language . '/';
		}
		if (($levelCode >= 2) && ($module != $defaultModule)) {
			$url .= $module . '/';
		}
		if (($levelCode >= 3) && (($controller != $defaultController)
			    || ($action != $defaultAction) || ($levelCode >= 5))) {
			$url .= $controller . '/';
		}
		if (($levelCode >= 4) && (($action != $defaultAction)
                || ($levelCode >= 5))) {
			$url .= $action . '/';
		}
		if ($levelCode >= 5) {
			foreach ($attributes as $attribute) {
				$url .=  $attribute . '/';
			}
		}

		return trim($url . $path, '/');
	}

    /**
     * Returns a URL to a static resource.
     *
     * @param string $path A file path
     * @param bool $isCompiled Is the resource compiled?
	 * @return string
     */
	public function getAssetUrl(
        string $path = '',
        bool $isCompiled = true
    ): string {
		$url = $isCompiled
            ? $this->config->url->assets : $this->config->url->src;
        return $url . $path;
	}

	/**
	 * Returns an array of asset URLs.
	 *
	 * @param string $resource An asset's file name
	 * @return array
	 */
	public function getAssetBundleUrls(string $resource): array
	{
		if (empty($this->assets)) {
			$this->loadAssets();
		}

		$assets = [];

        $fileExtension = pathinfo($resource, PATHINFO_EXTENSION);

        if ($this->config->env->load_compiled_assets) {
            $fileName = array_key_exists($resource, $this->assets)
                ? $this->assets[$resource] : $resource;
            $assets[] = $this->getAssetUrl($fileExtension . '/' . $fileName);
        } else {
            $url = $this->getAssetUrl($fileExtension, false) . '/';
            if (array_key_exists($resource, $this->assets)) {
                foreach ($this->assets[$resource] as $assetName) {
                    if (filter_var($assetName, FILTER_VALIDATE_URL)) {
                        $assets[] = $assetName;
                    }
                    elseif (substr($assetName, 0, 1) == '/') {
                        $assets[] = $this->config->url->base
                            . ltrim($assetName, '/');
                    }
                    else {
                        $assets[] = $url . $assetName . '?' . time();
                    }
                }
            }
        }

		return $assets;
	}

    /**
     * Returns a URL to an image.
     *
     * @param string $path A file path
     * @return string
     */
    public function getImageUrl(string $path = ''): string
    {
        return $this->config->url->img . $path;
    }

    /**
     * Returns an array of MIME types corresponding to a file extension.
     *
     * @param string $extension A file extension
     * @return array
     */
    public function getMimeTypes(string $extension): array
    {
        if (empty($this->mimeTypes)) {
            $this->loadMimeTypes();
        }

        return array_key_exists($extension, $this->mimeTypes)
            ? $this->mimeTypes[$extension] : [];
    }

    /**
     * Returns a content type corresponding to a file extension.
     *
     * @param string $extension A file extension
	 * @param string $default A default return value
	 * @return string
     */
    public function getContentType(
        string $extension,
        string $default = ''
    ): string {
		$mimeTypes = $this->getMimeTypes($extension);
		return empty($mimeTypes) ? $default : array_shift($mimeTypes);
    }

    /**
     * Sets a content type header.
     *
     * @param string $extension A file extension
     * @param string $charset The charset of the content (default: utf-8)
     */
    public function setContentType(string $extension, string $charset = 'utf-8')
    {
        if (headers_sent()) {
            return;
        }

        $contentType = $this->getContentType($extension);

        if (!empty($contentType)) {
            header('Content-Type: ' . $contentType . '; charset=' . $charset);
        }
        else {
            throw new \RuntimeException(
                sprintf(self::$errors['mime_types_not_found'], $extension)
            );
        }
    }

	/**
	 * Sends a file to user.
	 *
	 * @param string $fileName A file name
	 * @param string $content Content to be send.
	 * @param string $contentType The MIME type of the content.
	 * @param string $charset The charset of the content. Default: utf-8.
	 * @param bool $terminate Whether to terminate the current application
	 */
	public function sendFile(
        string $fileName,
        string $content,
        string $contentType,
        string $charset = 'utf-8',
        bool $terminate = true
    ) {
        if (headers_sent()) {
            return;
        }

		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Type: ' . $contentType . '; charset=' . $charset);
		header('Content-Disposition: attachment; filename="' . $fileName . '"');
		header('Content-Transfer-Encoding: binary');

		if (ob_get_length() === false) {
			header('Content-Length: ' . mb_strlen($content, '8bit'));
        }

		echo $content;

		if ($terminate) {
			exit(0);
        }
	}

    /**
     * Loads asset definitions from a file
     */
    protected function loadAssets()
    {
        $file = $this->config->env->load_compiled_assets
            ? $this->config->files->compiled_assets
            : $this->config->files->asset_sources;

        if (is_readable($file)) {
            $this->assets = include $file;
        } else {
            throw new \RuntimeException(
                sprintf(self::$errors['file_not_readable'], $file)
            );
        }

        if (!is_array($this->assets)) {
            throw new \LogicException(self::$errors['assets_type_error']);
        }
    }

	/**
	 * Loads mime types from a file
	 */
    protected function loadMimeTypes()
    {
        $file = $this->config->files->mime_types;

        if (is_readable($file)) {
            $this->mimeTypes = include $file;
        } else {
            throw new \RuntimeException(
                sprintf(self::$errors['file_not_readable'], $file)
            );
        }

        if (!is_array($this->mimeTypes)) {
            throw new \LogicException(self::$errors['mime_types_type_error']);
        }
    }
}

// -- End of file
