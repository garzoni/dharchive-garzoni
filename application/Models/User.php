<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Database\Database;
use Application\Core\Text\TextGenerator;
use Application\Core\Type\Map;
use Exception;

/**
 * Class User
 * @package Application\Models
 */
class User extends Agent
{
    const VIEW = 'agents_users';
    const TABLE = 'users';
    const PARENT_TABLE = 'agents';
    const FIELDS = [
        'id' => [
            'column' => 'id',
            'type' => 'int',
        ],
        'username' => [
            'column' => 'username',
        ],
        'email' => [
            'column' => 'email',
        ],
        'details' => [
            'column' => 'details',
            'type' => 'jsonb',
            'inherited' => true,
        ],
        'registration_time' => [
            'column' => 'registration_time',
            'type' => 'timestamp',
            'inherited' => true,
        ],
        'is_active' => [
            'column' => 'is_active',
            'type' => 'boolean',
            'inherited' => true,
        ],
        'first_name' => [
            'expression' => "details->>'firstName'",
            'inherited' => true,
        ],
        'last_name' => [
            'expression' => "details->>'lastName'",
            'inherited' => true,
        ],
        'full_name' => [
            'expression' => "coalesce(details->>'firstName') || ' ' "
                . " || coalesce(details->>'lastName')",
            'inherited' => true,
        ],
    ];
    const PRIMARY_KEY = 'id';
    const USERNAME_REGEX = '^[a-z]{1}[a-z0-9_]{3,50}$';

    /**
     * Initializes the class properties.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        parent::__construct($db);

        $this->table = self::VIEW;
        $this->fields = self::FIELDS;
        $this->key = self::PRIMARY_KEY;
    }

    /**
     * @param string $uid
     * @return Map
     */
    public function findByLoginUid(string $uid): Map
    {
        $email = filter_var($uid, FILTER_VALIDATE_EMAIL);
        if ($email !== false) {
            return $this->findByEmail($email);
        }

        $username = filter_var($uid, FILTER_VALIDATE_REGEXP,
            ['options' => ['regexp' => '/' . self::USERNAME_REGEX . '/']]
        );
        if ($username !== false) {
            return $this->findByUsername($username);
        }

        return new Map();
    }

    /**
     * @param string $username
     * @param array $fields
     * @return Map
     */
    public function findByUsername(string $username, array $fields = []): Map
    {
        return $this->find([['username', '=', $username]], $fields);
    }

    /**
     * @param string $email
     * @param array $fields
     * @return Map
     */
    public function findByEmail(string $email, array $fields = []): Map
    {
        return $this->find([['email', '=', $email]], $fields);
    }

    /**
     * @param int $id
     * @return Map
     */
    public function getCredentials(int $id): Map
    {
        $credentials = new UserCredential($this->db);
        return $credentials->fetch($id);
    }

    /**
     * @param int $id
     * @return string
     */
    public function getPasswordHash(int $id): string
    {
        return $this->getCredentials($id)->get('password_hash');
    }

    /**
     * @param int $id
     * @return string
     */
    public function getResetKey(int $id): string
    {
        return $this->getCredentials($id)->get('reset_key');
    }

    /**
     * @param array $data
     * @return int|null
     */
    public function create(array $data)
    {

        $agent = new Agent($this->db);
        $credentials = new UserCredential($this->db);

        $this->db->startTransaction();

        $agentType = (new AgentType($this->db))->find([
            ['prefix', '=', 'prov'],
            ['name', '=', 'Person'],
        ]);
        try {
            $agent->insertRecord([
                'agent_type_id' => $agentType->get('id'),
                'details' => json_encode([
                    'firstName' => $data['first_name'],
                    'lastName' => $data['last_name'],
                ]),
                'registration_time' => date(DATE_ATOM),
                'is_active' => true,
            ]);

            $userId = $this->db->fetch('scalar', 'SELECT MAX(id) FROM agents');

            $this->insertRecord([
                'id' => $userId,
                'username' => $data['username'],
                'email' => $data['email'],
            ]);

            $passwordHash = (new Password($data['password']))->getHash();
            $resetKey  = (new TextGenerator())->getHex(32);
            $credentials->insertRecord([
                'user_id' => $userId,
                'password_hash' => $passwordHash,
                'reset_key' => $resetKey,
            ]);
        }
        catch(Exception $exception) {
            $this->db->abortTransaction();
            trigger_error($exception->getMessage(), E_USER_WARNING);
            return null;
        }

        $this->db->commitTransaction();

        return (int) $userId;
    }

    public function update(int $userId, array $data): bool
    {
        return $this->updateRecord($userId, [
            'details' => json_encode([
                'firstName' => $data['first_name'],
                'lastName' => $data['last_name'],
            ])
        ]);
    }

    public function changePassword(int $userId, array $data): bool
    {
        $credentials = new UserCredential($this->db);

        $passwordHash = (new Password($data['password']))->getHash();
        return $credentials->updateRecord($userId, [
            'password_hash' => $passwordHash,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function insert(array $values): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function updateMany(array $criteria, array $values): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMany(array $criteria): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    protected function insertRecord(array $values): bool
    {
        $this->table = self::TABLE;
        $status = parent::insertRecord($values);
        $this->table = self::VIEW;

        return $status;
    }

    /**
     * @inheritdoc
     */
    protected function updateMultipleRecords(
        array $criteria,
        array $values
    ): bool {
        $status = false;
        $updates = $this->groupUpdates($values);
        foreach ($updates as $table => $values) {
            $this->table = $table;
            $status = parent::updateMultipleRecords($criteria, $values);
        }
        $this->table = self::VIEW;

        return $status;
    }

    /**
     * @inheritdoc
     */
    protected function updateMultipleJsonFields(
        array $criteria,
        string $column,
        string $action,
        string $path,
        $value = null
    ): bool {
        $isInherited = $this->fields[$column]['inherited'] ?? false;
        $this->table = $isInherited ? self::PARENT_TABLE : self::TABLE;
        $status = parent::updateMultipleJsonFields(
            $criteria,
            $column,
            $action,
            $path,
            $value
        );
        $this->table = self::VIEW;

        return $status;
    }

    /**
     * @inheritdoc
     */
    protected function deleteMultipleRecords(array $criteria): bool
    {
        $this->table = self::PARENT_TABLE;
        $status = parent::deleteMultipleRecords($criteria);
        $this->table = self::VIEW;

        return $status;
    }

    /**
     * @param array $values
     * @return array
     */
    protected function groupUpdates(array $values): array
    {
        $updates = [];
        foreach ($values as $column => $value) {
            if (!array_key_exists($column, $this->fields)) {
                continue;
            }
            $isInherited = $this->fields[$column]['inherited'] ?? false;
            $table = $isInherited ? self::PARENT_TABLE : self::TABLE;
            $updates[$table][$column] = $value;
        }

        return $updates;
    }
}

// -- End of file
