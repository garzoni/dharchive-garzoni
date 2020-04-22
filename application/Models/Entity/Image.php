<?php

declare(strict_types=1);

namespace Application\Models\Entity;

use Application\Core\Database\Database;
use Application\Models\Entity;

/**
 * Class Image
 * @package Application\Models\Entity
 */
class Image extends Entity
{
    const ENTITY_TYPE = 'dhc:Image';
    const IMAGE_NUMBER_LENGTH = 6;

    /**
     * Initializes the class properties.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    /**
     * @param string $manifestId
     * @param int $number
     * @return string
     */
    public function getImageResourceId(string $manifestId, int $number): string
    {
        return $manifestId . '-' . str_pad(
                (string) $number,
                self::IMAGE_NUMBER_LENGTH,
                '0',
                STR_PAD_LEFT
            );
    }

    /**
     * @param string $manifestId
     * @param int $number
     * @param string $fileExtension
     * @return string
     */
    public function getImageResourceFilePath(
        string $manifestId,
        int $number,
        string $fileExtension
    ): string {
        return $manifestId . '/'
            . (int) ($number / 10000) . '/'
            . (int) (($number % 10000) / 100) . '/'
            . $number . '.' . $fileExtension;
    }
}

// -- End of file
