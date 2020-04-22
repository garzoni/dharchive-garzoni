<?php

declare(strict_types=1);

namespace Application\Providers;

/**
 * Class SemanticUi
 * @package Application\Providers
 */
class SemanticUi
{
    public function getKeyValuePairs(array $values, bool $useValueAsKey = false): array
    {
        $pairs = [];
        foreach ($values as $key => $value) {
            $pairs[] = [
                'key' => ($useValueAsKey ? $value : $key),
                'value' => $value,
            ];
        }
        return $pairs;
    }

    public function getValueList(array $data, array $schema = []): string
    {
        $keyProperty = $schema['key'] ?? 'key';
        $valueProperty = $schema['value'] ?? 'value';

        $results = [];

        foreach ($data as $record) {
            if (!is_array($record)
                || !isset($record[$keyProperty])
                || !isset($record[$valueProperty])) {
                continue;
            }
            $results[] = [
                'name' => $record[$valueProperty],
                'value' => $record[$keyProperty],
            ];
        }

        return json_encode([
            'success' => true,
            'results' => $results,
        ]);
    }

    public function getErrorResponse(string $message): string {
        return json_encode([
            'success' => false,
            'message' => $message,
        ]);
    }
}

// -- End of file
