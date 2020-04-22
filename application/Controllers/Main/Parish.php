<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Models\Entity\Parish as ParishModel;
use Application\Providers\SemanticUi;

use function Application\createText as _;
use const Application\REGEX_QNAME;

/**
 * Class Parish
 * @package Application\Controllers\Main
 */
class Parish extends Base
{
    public function index() {}

    public function getValueList()
    {
        $sestiere = $this->request->getQuery('sestiere');
        $pattern = $this->request->getQuery('pattern');

        $criteria = [];
        if (!empty($sestiere) && preg_match('/' . REGEX_QNAME . '/', $sestiere)) {
            $criteria[] = ['sestiere', '=', $sestiere];
        }
        if (!empty($pattern)) {
            $criteria[] = ['unaccented_name', '~*', _($pattern)->transliterate('Latin-ASCII')->toString()];
        }

        $parishModel = new ParishModel($this->db);
        $parishes = $parishModel->findAll($criteria, ['qualified_name', 'name'], ['name']);

        $this->setContentType('json');

        $sui = new SemanticUi();
        echo $sui->getValueList($parishes->toArray(), ['key' => 'qualified_name', 'value' => 'name']);
    }
}

// -- End of file
