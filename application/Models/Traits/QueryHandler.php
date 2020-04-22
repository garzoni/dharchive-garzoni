<?php

declare(strict_types=1);

namespace Application\Models\Traits;

use DomainException;
use InvalidArgumentException;
use PDO;

use const Application\VALUE_LIST_LIMIT;

/**
 * Trait QueryHandler
 * @package Application\Models\Traits
 */
trait QueryHandler
{
    protected function getDistinctValueList(
        string $table,
        string $field,
        string $pattern = null,
        string $withClause = null,
        bool $caseSensitive = false,
        bool $ignoreDiacritics = true,
        int $limit = VALUE_LIST_LIMIT
    ): array {
        $db = $this->getDatabaseManager();

        $query = $withClause ? ($withClause . ' ') : '';

        $query .= 'SELECT DISTINCT ' . $field . ' AS value FROM ' . $table;
        $parameters = [];

        if (!empty($pattern)) {
            $operator = $caseSensitive ? 'LIKE' : 'ILIKE';
            $value = ':pattern';
            if ($ignoreDiacritics) {
                $field = 'UNACCENT(' . $field . ')';
                $value = 'UNACCENT(' . $value . ')';
            }
            $query .= ' WHERE ' . $field . ' ' . $operator . ' ' . $value;
            $parameters[] = [':pattern', '%' . $pattern . '%', PDO::PARAM_STR];
        }

        $query .= ' ORDER BY value';

        if ($limit > 0) {
            $query .= ' LIMIT ' . $limit;
        }

        return $db->fetch('list', $query, $parameters, true);
    }

    protected function getBooleanCondition(string $field, string $value): string
    {
        switch ($value) {
            case 'true':
            case 'yes':
            case 'only':
                $value = 'TRUE';
                break;
            case 'false':
            case 'no':
            case 'except':
                $value = 'FALSE';
                break;
            default:
                $value = 'UNKNOWN';
        }
        return '(' . $field . ')::boolean IS ' . $value;
    }

    protected function getTextCondition(
        string $field,
        $value,
        string $operator = '=',
        bool $caseSensitive = false,
        bool $ignoreDiacritics = true
    ): string {
        if (is_string($value)) {
            if ($operator === '=~') {
                if ($value[0] === '~') {
                    $operator = '~';
                    $value = substr($value, 1);
                } else {
                    $operator = '=';
                }
            }
            $values = [$value];
        } elseif (is_array($value)) {
            $values = $value;
        } else {
            throw new InvalidArgumentException('Invalid value type');
        }

        if (!in_array($operator, ['<', '>', '<=', '>=', '=', '!=', '<>', '~'])) {
            throw new DomainException("Invalid operator '$operator'");
        }

        $field = '(' . $field . ')::text';
        if ($ignoreDiacritics) {
            $field = 'UNACCENT(' . $field . ')';
        }
        if (!$caseSensitive) {
            $field = 'LOWER(' . $field . ')';
        }

        $db = $this->getDatabaseManager();

        $quotedValues = [];
        foreach ($values as $value) {
            if (!is_string($value) || empty($value)) {
                continue;
            }
            if ($operator === '~') {
                $value = str_replace('*', '%', $value);
                $value = $db->quote('%' . $value . '%');
            } else {
                $value = $db->quote($value);
            }
            if ($ignoreDiacritics) {
                $value = 'UNACCENT(' . $value . ')';
            }
            if (!$caseSensitive) {
                $value = 'LOWER(' . $value . ')';
            }
            $quotedValues[] = $value;
        }

        if (empty($quotedValues)) {
            throw new InvalidArgumentException('Missing values');
        }

        if (count($quotedValues) === 1) {
            $values = $quotedValues[0];
        } else {
            $values = 'ANY(ARRAY[' . implode(', ', $quotedValues) . '])';
        }

        if ($operator === '~') {
            return $field . ' LIKE ' . $values;
        } else {
            return $field . ' ' . $operator . ' ' . $values;
        }
    }

    protected function getMultipleTextConditions(
        string $field,
        array $values,
        bool $caseSensitive = false,
        bool $ignoreDiacritics = true
    ): string {
        $exactValues = [];
        $approximateValues = [];
        foreach ($values as $value) {
            if (!is_string($value) || empty($value)) {
                continue;
            }
            if ($value[0] === '~') {
                $value = substr($value, 1);
                $approximateValues[] = $value;
            } else {
                $exactValues[] = $value;
            }
        }
        $conditions = [];
        if ($exactValues) {
            $conditions[] = '(' . $this->getTextCondition($field, $exactValues, '=', $caseSensitive, $ignoreDiacritics) . ')';
        }
        if ($approximateValues) {
            $conditions[] = '(' . $this->getTextCondition($field, $approximateValues, '~', $caseSensitive, $ignoreDiacritics) . ')';
        }
        return !empty($conditions) ? implode(' OR ', $conditions) : ($field . 'IS NOT NULL');
    }

    protected function getJsonCondition(
        string $field,
        $value,
        string $operator = '@>',
        bool $isBinary = true
    ): string {
        if (!in_array($operator, ['@>', '<@'])) {
            throw new DomainException("Invalid operator '$operator'");
        }

        $db = $this->getDatabaseManager();
        $value = $db->quote(json_encode($value));

        return $field . ' ' . $operator . ' ' . '(' . $value . ')::' . ($isBinary ? 'jsonb' : 'json');
    }

    protected function getMultipleJsonConditions(
        string $field,
        array $values,
        string $operator = '@>',
        bool $isBinary = true
    ): string {
        $conditions = [];

        foreach ($values as $value) {
            $conditions[] = '(' . $this->getJsonCondition($field, $value, $operator, $isBinary) . ')';
        }

        return !empty($conditions) ? implode(' OR ', $conditions) : ($field . 'IS NOT NULL');
    }

    abstract protected function getDatabaseManager();
}

// -- End of file
