<?php

declare(strict_types=1);

namespace Application\Providers;

use Application\Core\Filter\Formatter;
use Application\Core\Filter\Sanitizer;
use Application\Core\Filter\Validation\Validator;
use Application\Core\Session\Session;
use Application\Core\Text\Translator;

/**
 * Class Filter
 * @package Application\Providers
 */
class Filter
{
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
     * @param Session $session
     * @param Translator $text
     */
    public function __construct(
        Session $session,
        Translator $text
    ) {
        $this->session = $session;
        $this->text = $text;
    }

    /**
     * @param array $data
     * @param array $criteria
     * @param array $attributeLabels
     * @param bool $highlight
     * @return bool
     */
    public function validate(
        array $data,
        array $criteria,
        array $attributeLabels = [],
        bool $highlight = false
    ): bool {
        $validator = new Validator($criteria);
        $hasPassed = $validator->test($data);
        if (!$hasPassed) {
            $errorMessages = $validator->getValidationErrors(
                $this->text,
                $attributeLabels,
                $highlight
            );
            array_unshift($errorMessages, 'Validation Errors');
            $this->session->addMessage('error', $errorMessages);
        }

        return $hasPassed;
    }

    /**
     * @param array $data
     * @param array $rules
     */
    public function sanitize(array &$data, array $rules)
    {
        $sanitizer = new Sanitizer();
        $sanitizer->apply($data, $rules);
    }

    /**
     * @param array $data
     * @param array $rules
     */
    public function format(array &$data, array $rules)
    {
        $formatter = new Formatter();
        $formatter->apply($data, $rules);
    }
}

// -- End of file
