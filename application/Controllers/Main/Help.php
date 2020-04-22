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
use Markdown\ParsedownExtra;

use function Application\createText as _;

/**
 * Class Help
 * @package Application\Controllers\Main
 */
class Help extends Base
{
    /**
     * @var string
     */
    protected $documentDir;

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

        $this->documentDir = $this->config->dir->templates . 'documents/';
    }

    public function index()
    {
        if (!$this->hasPermission('view_help')) {
            $this->request->abort(403);
        }

        $this->view->page_title = $this->text->get('app.help');

        chdir($this->documentDir);

        $docs = [];
        foreach (glob('*.' . $this->request->getLanguage() . '.md') as $file) {
            $filename = explode('.', pathinfo($file, PATHINFO_FILENAME));
            $filename = array_shift($filename);
            $url = $this->getUrl('controller', 'view/' . $filename);
            $docs[$url] = $this->text->get('help.' . _($filename)->underscorize()->toString());
        }
        asort($docs);

        $this->view->docs = $docs;

        echo $this->view->render('pages/help/index.tpl.php');
    }

    public function view()
    {
        if (!$this->hasPermission('view_help')) {
            $this->request->abort(403);
        }

        $this->view->page_title = $this->text->get('app.help');

        $filename = $this->request->getAttribute(0);
        $file = $this->documentDir . $filename . '.' . $this->request->getLanguage() . '.md';

        if (!file_exists($file)) {
            $this->request->abort(404);
        }

        $parser = new ParsedownExtra();
        $this->view->content = $parser->text(file_get_contents($file) ?: '');

        echo $this->view->render('pages/help/view.tpl.php');
    }

}

// -- End of file
