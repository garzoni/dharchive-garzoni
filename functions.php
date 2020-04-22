<?php

declare(strict_types=1);

namespace Application;

use Application\Core\Type\Map;
use Application\Core\Type\Text;
use FilesystemIterator;
use RegexIterator;

/*----------------------------------------------------------------------------
   Factory Functions
  ----------------------------------------------------------------------------*/

if (!function_exists(__NAMESPACE__ . '\createMap')) {
    /**
     * Creates a Map object and returns it on success.
     *
     * @param  array $data
     * @return Map
     */
    function createMap(array $data = []): Map
    {
        return new Map($data);
    }
}

if (!function_exists(__NAMESPACE__ . '\createText')) {
    /**
     * Creates a Text object and returns it on success.
     *
     * @param  mixed $string Value to modify, after being cast to a string
     * @param  string $encoding The character encoding
     * @return Text
     */
    function createText($string, string $encoding = null): Text
    {
        return new Text($string, $encoding);
    }
}

/*----------------------------------------------------------------------------
   Text Functions
  ----------------------------------------------------------------------------*/

if (!function_exists(__NAMESPACE__ . '\filterIntegerList')) {
    /**
     * @param string $list
     * @param string $delimiter
     * @return string
     */
    function filterIntegerList(string $list, string $delimiter = ','): string
    {
        $values = [];
        foreach (explode($delimiter, $list) as $value) {
            $value = trim($value);
            if (is_numeric($value)) {
                $values[] = (int) $value;
            }
        }
        return implode($delimiter, $values);
    }
}

/*----------------------------------------------------------------------------
   Array Functions
  ----------------------------------------------------------------------------*/

if (!function_exists(__NAMESPACE__ . '\sortArrayByKey')) {
    /**
     * Sorts a multi-dimensional associative array by key recursively.
     *
     * @param array $array
     * @param int $flags
     * @return bool
     */
    function sortArrayByKey(&$array, int $flags = SORT_REGULAR): bool
    {
        if (!is_array($array)) {
            return false;
        }
        ksort($array, $flags);
        foreach ($array as &$subarray) {
            sortArrayByKey($subarray, $flags);
        }
        return true;
    }
}

if (!function_exists(__NAMESPACE__ . '\removeElements')) {
    /**
     * Removes values from an array by given keys.
     *
     * @param array $array
     * @param array $keys
     */
    function removeElements(array &$array, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                unset($array[$key]);
            }
        }
    }
}

if (!function_exists(__NAMESPACE__ . '\insertBefore')) {
    /**
     * Inserts new elements into an array before a given offset.
     *
     * @param array $array
     * @param string $offset
     * @param array $newElements
     */
    function insertBefore(array &$array, string $offset, array $newElements)
    {
        if (array_key_exists($offset, $array) && !empty($newElements)) {
            $newArray = [];
            foreach ($array as $key => $value) {
                if ($key === $offset) {
                    foreach ($newElements as $newKey => $newValue) {
                        $newArray[$newKey] = $newValue;
                    }
                }
                $newArray[$key] = $value;
            }
            $array = $newArray;
        }
    }
}

if (!function_exists(__NAMESPACE__ . '\insertAfter')) {
    /**
     * Inserts new elements into an array after a given offset.
     *
     * @param array $array
     * @param string $offset
     * @param array $newElements
     */
    function insertAfter(array &$array, string $offset, array $newElements)
    {
        if (array_key_exists($offset, $array) && !empty($newElements)) {
            $newArray = [];
            foreach ($array as $key => $value) {
                $newArray[$key] = $value;
                if ($key === $offset) {
                    foreach ($newElements as $newKey => $newValue) {
                        $newArray[$newKey] = $newValue;
                    }
                }
            }
            $array = $newArray;
        }
    }
}

if (!function_exists(__NAMESPACE__ . '\splitValues')) {
    /**
     * @param string $text
     * @param string $separator
     * @param string|null $cast
     * @param string|null $pattern
     * @return array
     */
    function splitValues(
        string $text,
        string $separator = '|',
        string $cast = null,
        string $pattern = null
    ): array
    {
        $values = [];
        foreach (explode($separator, $text) as $value) {
            if (!strlen($value) || ($pattern && !preg_match($pattern, $value))) {
                continue;
            }
            switch ($cast) {
                case 'integer':
                    $value = (int) $value;
                    break;
                case 'boolean':
                    $value = (bool) $value;
                    break;
                case 'double':
                    $value = (float) $value;
                    break;
                default:
                    $value = (string) $value;
            }
            $values[] = $value;
        }
        return $values;
    }
}

if (!function_exists(__NAMESPACE__ . '\implodeJsonArray')) {
    /**
     * @param string $value
     * @return string
     */
    function implodeJsonArray(string $value): string
    {
        $value = json_decode($value, true);
        if (is_array($value) && !empty($value)) {
            return implode(', ', $value);
        }
        return '';
    }
}

/*----------------------------------------------------------------------------
   Filesystem Functions
  ----------------------------------------------------------------------------*/

if (!function_exists(__NAMESPACE__ . '\scanDirectory')) {
    /**
     * List files and directories inside the specified path and optionally
     * filters them by a given regular expression.
     *
     * @param string $directory
     * @param string $pattern
     * @param int $sortFlags
     * @return array
     */
    function scanDirectory(
        string $directory,
        string $pattern = '',
        int $sortFlags = SORT_REGULAR
    ): array
    {
        $list = new FilesystemIterator(
            $directory,
            FilesystemIterator::KEY_AS_FILENAME |
            FilesystemIterator::CURRENT_AS_FILEINFO |
            FilesystemIterator::SKIP_DOTS
        );

        if (!empty($pattern)) {
            $list = new RegexIterator(
                $list,
                $pattern,
                RegexIterator::MATCH,
                RegexIterator::USE_KEY
            );
        }

        $entries = [];

        foreach ($list as $entry) {
            $entries[$entry->getPathname()] = $entry->getFilename();
        }

        ksort($entries, $sortFlags);

        return $entries;
    }
}

if (!function_exists(__NAMESPACE__ . '\deleteDirectory')) {
    /**
     * Deletes a directory.
     *
     * @param string $directory
     */
    function deleteDirectory(string $directory)
    {
        $directory = rtrim($directory, '/');
        foreach (glob($directory . '/*') as $file)
        {
            if (is_dir($file)) {
                deleteDirectory($file);
            } else {
                unlink($file);
            }
        }
        rmdir($directory);
    }
}

/*----------------------------------------------------------------------------
   Variable Handling Functions
  ----------------------------------------------------------------------------*/

if (!function_exists(__NAMESPACE__ . '\putf')) {
    /**
     * Outputs parsable string representations of given variables, enclosed in
     * HTML <pre> tags.
     *
     * @param array $vars A variable number of expressions
     */
    function putf(...$vars)
    {
        echo '<pre><code class="language-php">' . PHP_EOL;
        call_user_func_array(__NAMESPACE__ . '\put', $vars);
        echo '</code></pre>' . PHP_EOL;
    }
}

if (!function_exists(__NAMESPACE__ . '\put')) {
    /**
     * Outputs parsable string representations of given variables.
     *
     * @param array $vars A variable number of expressions
     */
    function put(...$vars)
    {
        foreach ($vars as $var) {
            var_export($var);
            echo PHP_EOL;
        }
    }
}

/*----------------------------------------------------------------------------
   Other Functions
  ----------------------------------------------------------------------------*/

if (!function_exists(__NAMESPACE__ . '\getThumbnailUrl')) {
    /**
     * @param string $imageUrl
     * @param int $width
     * @param int $height
     * @return string
     */
    function getThumbnailUrl(
        string $imageUrl,
        int $width,
        int $height
    ): string {
        $version = null;
        $imageUrl = trim($imageUrl, "\t\n\r ?");
        $queryStartPos = strrpos($imageUrl, '?');
        if ($queryStartPos !== false) {
            $thumbnailUrl = substr($imageUrl, 0, $queryStartPos);
            $params = [];
            parse_str(substr($imageUrl, $queryStartPos + 1), $params);
            $version = $params['v'] ?? null;
        } else {
            $thumbnailUrl = $imageUrl;
        }
        $thumbnailUrl .= '/full/' . (($width > 0) ? $width : '')
            . ',' . (($height > 0) ? $height : '')
            . '/0/default.jpg' . (!is_null($version) ? ('?v=' . $version) : '');
        return $thumbnailUrl;
    }
}
