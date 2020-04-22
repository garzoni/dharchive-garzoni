<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Models\Entity\HostingConditionMention as HostingConditionMentionModel;
use Application\Providers\SemanticUi;

/**
 * Class HostingConditionMention
 * @package Application\Controllers\Main
 */
class HostingConditionMention extends Base
{
    public function index() {}

    public function getValueList()
    {
        $id = $this->request->getQuery('id');
        $pattern = $this->request->getQuery('pattern');

        $sui = new SemanticUi();
        $hostingConditionMentionModel = new HostingConditionMentionModel($this->db);

        $this->setContentType('json');

        switch ($id) {
            case 'clothing_types':
                $values = $hostingConditionMentionModel->getClothingTypeList($pattern);
                break;
            default:
                echo $sui->getErrorResponse('Invalid list identifier');
                exit(0);
        }

        echo $sui->getValueList($sui->getKeyValuePairs($values, true));
    }
}

// -- End of file
