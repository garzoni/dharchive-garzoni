<?php

declare(strict_types=1);

namespace Application\Providers;

use Application\Core\Database\Database;
use Application\Core\Session\Session;
use Application\Core\Text\Translator;
use Application\Models\AgentLogin;
use Application\Models\AgentSession;
use Application\Models\Password;
use Application\Models\User;
use Exception;

/**
 * Class Authenticator
 * @package Application\Providers
 */
class Authenticator
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
    }

    /**
     * @param string $uid
     * @param string $password
     * @param string $ipAddress
     * @return bool
     */
    public function authenticate(
        string $uid,
        string $password,
        string $ipAddress
    ): bool {
        $agentLogin = new AgentLogin($this->db);
        $agentSession = new AgentSession($this->db);
        $userModel = new User($this->db);

        try {
            $password = new Password($password);
        } catch (Exception $e) {
            $this->session->addMessage('error', $this->text->get('auth.error.invalid_login'));
            return false;
        }

        $user = $userModel->findByLoginUid($uid);

        if ($user->isEmpty()) {
            $this->session->addMessage('error', $this->text->get('auth.error.invalid_login'));
            return false;
        }

        $isValidPassword = $password->validate(
            $userModel->getPasswordHash($user->get('id'))
        );

        if (!$isValidPassword) {
            $agentLogin->log($user->get('id'), $ipAddress, false);
            $this->session->addMessage('error', $this->text->get('auth.error.invalid_login'));
            return false;
        } elseif (!$user->get('is_active')) {
            $this->session->addMessage('error', $this->text->get('auth.error.deactivated_account'));
            return false;
        }

        $this->session->regenerate(true);

        $user->decodeJsonValue('details');
        $this->session->set('auth_user', $user);

        $agentLogin->log($user->get('id'), $ipAddress, true);
        $agentSession->assignAgent($this->session->getId(), $user->get('id'));

        return true;
    }

    /**
     * @return bool
     */
    public function invalidate(): bool
    {
        $this->session->destroy();
        $this->session->start();
        $this->session->regenerate(true);

        return true;
    }
}

// -- End of file
