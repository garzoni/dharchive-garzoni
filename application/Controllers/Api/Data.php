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
use Application\Core\Type\Table;

/**
 * Class Data
 * @package Application\Controllers\Iiif
 */
class Data extends Base
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
    }

    public function index() {}

    public function export()
    {
        $scriptId = $this->request->getAttribute(0);
        $this->setContentType('csv');

        switch ($scriptId) {
            case 'apprentices':
                $query = "
                    SELECT
                        contracts.id AS contract_id,
                        contracts.date AS contract_date,
                        masters.workshop_parish,
                        masters.workshop_insignia,
                        masters.full_name AS master_name,
                        apprentices.full_name AS apprentice_name,
                        locations.name AS apprentice_origin_name,
                        locations.coordinates AS apprentice_origin_coordinates,
                        locations.geonames_id AS apprentice_origin_geonames_id
                    FROM
                        (
                            SELECT
                                contract_id,
                                get_full_name(properties->'name') AS full_name,
                                properties->'geoOrigin'->>'standardForm' AS origin_standard_form
                            FROM mentions
                            WHERE type_id = (
                                    SELECT id FROM entity_types
                                    WHERE prefix = 'grz' AND name = 'PersonMention'
                                )
                                AND tag_id = 'fb3f72eb-b1ae-4b95-81c8-8a5eb245e5fd'
                        ) apprentices
                        LEFT JOIN
                        (
                            SELECT
                                mentions.contract_id,
                                STRING_AGG(get_full_name(mentions.properties->'name'), '; ') AS full_name,
                                MIN(parishes.name) AS workshop_parish,
                                MIN(mentions.properties->'workshop'->>'insigna') AS workshop_insignia
                            FROM
                                mentions
                                LEFT JOIN
                                (
                                    SELECT
                                        properties->>'qualifiedName' AS qname,
                                        properties->>'name' AS name
                                    FROM entities
                                    WHERE entity_type_id = (
                                            SELECT id FROM entity_types
                                            WHERE prefix = 'grz' AND name = 'Parish'
                                        )
                                ) parishes ON mentions.properties->'workshop'->>'parish' = parishes.qname
                            WHERE mentions.type_id = (
                                    SELECT id FROM entity_types
                                    WHERE prefix = 'grz' AND name = 'PersonMention'
                                )
                                AND mentions.tag_id = '98be7009-c642-4790-8dc5-26d47cf3e321'
                            GROUP BY mentions.contract_id
                        ) masters ON apprentices.contract_id = masters.contract_id
                        LEFT JOIN
                        (
                            SELECT
                                id,
                                properties->>'date' AS date
                            FROM entities
                            WHERE entity_type_id = (
                                    SELECT id FROM entity_types
                                    WHERE prefix = 'grz' AND name = 'ContractMention'
                                )
                        ) contracts ON apprentices.contract_id = contracts.id
                        LEFT JOIN
                        locations ON apprentices.origin_standard_form = locations.standard_form
                    WHERE contracts.id IS NOT NULL
                    ORDER BY contract_date, contract_id, apprentice_name;
                ";
                $records = new Table($this->db->fetch('table', $query));
                echo $records->toCsv();
                break;
            default:
                $this->request->abort(404);
        }
    }
}

// -- End of file
