<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Models\Entity\FinancialConditionMention as FinancialConditionMentionModel;
use Application\Providers\SemanticUi;

/**
 * Class FinancialConditionMention
 * @package Application\Controllers\Main
 */
class FinancialConditionMention extends Base
{
    public function index() {}

    public function getValueList()
    {
        $id = $this->request->getQuery('id');
        $pattern = $this->request->getQuery('pattern');

        $sui = new SemanticUi();
        $financialConditionMentionModel = new FinancialConditionMentionModel($this->db);

        $this->setContentType('json');

        switch ($id) {
            case 'money_information':
                $values = $financialConditionMentionModel->getMoneyInformationList($pattern);
                break;
            default:
                echo $sui->getErrorResponse('Invalid list identifier');
                exit(0);
        }

        echo $sui->getValueList($sui->getKeyValuePairs($values, true));
    }
}

// -- End of file
