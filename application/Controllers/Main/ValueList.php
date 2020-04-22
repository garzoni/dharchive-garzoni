<?php

declare(strict_types=1);

namespace Application\Controllers\Main;

use Application\Models\Entity\EntityList;

/**
 * Class ValueList
 * @package Application\Controllers\Main
 */
class ValueList extends Base
{
    public function index() {}

    public function get()
    {
        $listQName = $this->request->getQuery('listQName');
        $keyProperty = $this->request->getQuery('keyProperty');
        $labelProperty = $this->request->getQuery('labelProperty');
        $key = $this->request->getQuery('key');
        $label = $this->request->getQuery('label');
        $language = $this->request->getQuery('language');
        $fallbackLanguage = $this->request->getQuery('fallbackLanguage')
            ?: $this->config->request->language;

        $keys = !is_null($key) ? [$key] : [];

        $list = [
            'success' => true,
            'results' => [],
        ];

        $entityList = new EntityList($this->db);
        $entities = $entityList->getEntities(
            $listQName,
            $keyProperty,
            $labelProperty,
            $label,
            $language,
            $keys
        );

        if (!empty($keys)) {
            $entity = reset($entities);
            $this->setContentType('text');
            if ($entity) {
                echo $entityList->getLocalizedLabel($entity, $labelProperty, $fallbackLanguage);
            }
            return;
        }

        foreach ($entities as $entity) {
            $value = $entity[$keyProperty];
            $list['results'][] = [
                'name' => $entityList->getLocalizedLabel($entity, $labelProperty, $fallbackLanguage),
                'value' => $value,
            ];
        }

        $this->setContentType('json');

        echo json_encode($list);
    }
}

// -- End of file
