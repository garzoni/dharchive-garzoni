<?php

declare(strict_types=1);

namespace Application\Providers;

use Application\Core\Database\Database;
use Application\Core\Session\Session;
use Application\Core\Text\Translator;

/**
 * Class Authorizer
 * @package Application\Providers
 */
class Authorizer
{
    /**
     * @var Database
     */
    protected $db;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Translator
     */
    protected $text;

    /**
     * @var array
     */
    protected $permissions;

    /**
     * Initializes the class properties.
     *
     * @param Database $db
     * @param Session $session
     * @param Translator $text
     */
    public function __construct(
        Database $db,
        Session $session,
        Translator $text
    ) {
        $this->db = $db;
        $this->session = $session;
        $this->text = $text;
        $this->permissions = [];
    }

    /**
     * @param string $code
     * @return bool
     */
    public function hasPermission(string $code): bool
    {
        return array_key_exists($code, $this->permissions);
    }
}

// -- End of file
