<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Foundation\Model;
use InvalidArgumentException;
use ZxcvbnPhp\Zxcvbn;

/**
 * Class Password
 * @package Application\Core\Text
 */
class Password extends Model
{
    const MIN_LENGTH = 6;
    const MAX_LENGTH = 50;
    const SALT_LENGTH = 64;
    const HASHING_ALGORITHM = 'sha256';

    /**
     * @var string
     */
    protected $content;

    /**
     * @var array
     */
    protected $strength;

    /**
     * Initializes the class properties.
     *
     * @param string $content
     */
    public function __construct(string $content){
        $length = strlen($content);
        if ($length < static::MIN_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'The password must be at least %d characters long',
                static::MIN_LENGTH
            ));
        } elseif ($length > static::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'The password must be at most %d characters long',
                static::MAX_LENGTH
            ));
        } elseif (!mb_ereg_match('^[\x20-\x7E]+$', $content)) {
            throw new InvalidArgumentException(
                'The password must contain only printable ASCII characters'
            );
        }
        $this->content = $content;
        $this->strength = [];
    }

    /**
     * Returns the password.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->content;
    }

    /**
     * Returns the password's length.
     *
     * @return int
     */
    public function getLength(): int
    {
        return strlen($this->content);
    }

    /**
     * Returns the password's strength estimation result array or
     * a single entry from it.
     *
     * @param string $attribute
     * @return mixed
     */
    public function getStrength(string $attribute = null)
    {
        if (empty($this->strength)) {
            $this->computeStrength();
        }
        if (is_null($attribute)) {
            return $this->strength;
        } elseif (array_key_exists($attribute, $this->strength)) {
            return $this->strength[$attribute];
        } else {
            return null;
        }
    }

    /**
     * Returns the password's strength score (an integer from 0 to 4).
     *
     * 0 -> too guessable: risky password (guesses < 10^3)
     * 1 -> very guessable: protection from throttled online attacks
     *      (guesses < 10^6)
     * 2 -> somewhat guessable: protection from unthrottled online attacks
     *      (guesses < 10^8)
     * 3 -> safely unguessable: moderate protection from an offline
     *      slow-hash scenario. (guesses < 10^10)
     * 4 -> very unguessable: strong protection from an offline
     *      slow-hash scenario. (guesses >= 10^10)
     *
     * @return int
     */
    public function getStrengthScore(): int
    {
        return $this->getStrength('score');
    }

    /**
     * Returns an estimated entropy of the password (in bits).
     * @return int
     */
    public function getEntropy(): int
    {
        return (int) round($this->getStrength('entropy'));
    }

    /**
     * Returns an estimation of actual crack time for the password (in seconds).
     *
     * @return float
     */
    public function getCrackTime(): float
    {
        return (float) $this->getStrength('crack_time');
    }

    /**
     * Returns a salted hash of the password.
     *
     * @return string
     */
    public function getHash(): string
    {
        $byteLength = (int) static::SALT_LENGTH / 2;
        $salt = bin2hex(random_bytes($byteLength));
        $hash = hash(static::HASHING_ALGORITHM, $salt . $this->content);
        $hash = $salt . $hash;

        return $hash;
    }

    /**
     * Validates the password against a salted password hash.
     *
     * @param string $hash A salted hash created by the getHash() method
     * @return bool
     */
    public function validate(string $hash): bool
    {
        $salt = substr($hash, 0, static::SALT_LENGTH);
        $validHash = substr($hash, static::SALT_LENGTH);
        $testHash = hash(static::HASHING_ALGORITHM, $salt . $this->content);

        return $testHash === $validHash;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Computes the password's strength using Zxcvbn-PHP.
     *
     * @link https://github.com/bjeavons/zxcvbn-php
     */
    protected function computeStrength() {
        $zxcvbn = new Zxcvbn();
        $this->strength = $zxcvbn->passwordStrength($this->content);
    }
}

// -- End of file
