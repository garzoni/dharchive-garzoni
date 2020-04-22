<?php

declare(strict_types=1);

namespace Application\Providers;

use Application\Core\Type\Text;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;
use InvalidArgumentException;

/**
 * Class Exporter
 * @package Application\Providers
 */
class Exporter
{
    const MIME_TYPES = [
        'csv' => 'text/csv',
        'html' => 'text/html',
        'json' => 'application/json',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'tsv' => 'text/tab-separated-values',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    const DEFAULT_OPTIONS = [
        'append_timestamp' => true,
        'default_recordset_name' => 'records',
        'max_recordset_name_length' => 30,
        'csv_delimiter' => ',',
        'csv_enclosure' => '"',
        'json_pretty_print' => true,
        'json_unescaped_slashes' => true,
        'json_unescaped_unicode' => true,
        'json_indent_character' => ' ',
        'json_indent_size' => 4,
        'json_property_name_style' => 'camel_case',
    ];

    /**
     * @var array Error messages
     */
    protected static $errors = [
        'invalid_file_format'       => 'Invalid file format "%"',
        'invalid_option'            => 'Invalid option "%"',
        'invalid_recordset_schema'  => 'Invalid recordset schema',
        'invalid_record_subset'     => 'Invalid record subset',
        'invalid_record'            => 'Invalid record',
    ];

    /**
     * @var array
     */
    protected $options;

    public function __construct(array $options = [])
    {
        $this->options = self::DEFAULT_OPTIONS;
        foreach ($options as $name => $value) {
            $this->setOption($name, $value);
        }
    }

    public function getOption(string $name)
    {
        if (!array_key_exists($name, $this->options)) {
            throw new InvalidArgumentException(sprintf(
                static::$errors['invalid_option'], $name
            ));
        }
        return $this->options[$name];
    }

    public function setOption(string $name, $value)
    {
        if (!array_key_exists($name, $this->options)
            || (gettype($value) !== gettype($this->options[$name]))) {
            throw new InvalidArgumentException(sprintf(
                static::$errors['invalid_option'], $name
            ));
        }
        $this->options[$name] = $value;
    }

    public function getContentType(string $fileFormat): string
    {
        return self::MIME_TYPES[$fileFormat] ?? '';
    }

    public function export(
        array $record,
        string $fileName,
        string $fileFormat,
        callable $callback = null
    ) {
        $fileFormat = $this->normalizeFileFormat($fileFormat);
        if ($fileFormat !== 'json') {
            throw new InvalidArgumentException(sprintf(
                static::$errors['invalid_file_format'], $fileFormat
            ));
        }
        $fileName = $this->normalizeFileName($fileName, $fileFormat);
        $options = $this->getJsonExportOptions();

        $this->sendHeaders($fileName, $fileFormat);

        if ($callback) {
            $callback($record);
        }
        echo json_encode($record, $options);
    }

    public function exportList(
        array $records,
        string $recordsetName,
        string $fileName,
        string $fileFormat,
        callable $callback = null
    ) {
        $fileFormat = $this->normalizeFileFormat($fileFormat);
        if ($fileFormat !== 'json') {
            throw new InvalidArgumentException(sprintf(
                static::$errors['invalid_file_format'], $fileFormat
            ));
        }
        $recordsetName = $this->normalizePropertyName($recordsetName ?: $fileName);
        if (empty($recordsetName)) {
            $recordsetName = $this->getOption('default_recordset_name');
        }
        $fileName = $this->normalizeFileName($fileName, $fileFormat);
        $options = $this->getJsonExportOptions();
        $indent = str_repeat(
            $this->getOption('json_indent_character'),
            $this->getOption('json_indent_size')
        );

        end($records);
        $lastRecordIndex = key($records);

        $this->sendHeaders($fileName, $fileFormat);

        echo '{' . PHP_EOL . $indent .  '"' . $recordsetName . '": [' . PHP_EOL;

        foreach ($records as $index => $record) {
            if ($callback) {
                $callback($record);
            }
            $content = json_encode($record, $options);
            if ($index !== $lastRecordIndex) {
                $content .= ',';
            }
            foreach (explode(PHP_EOL, $content) as $line) {
                echo str_repeat($indent, 2) . $line . PHP_EOL;
            }
            ob_flush();
            flush();
        }

        echo $indent . ']'  . PHP_EOL . '}' . PHP_EOL;
    }

    public function exportTable(
        array $records,
        array $recordsetSchemas,
        string $fileName,
        string $fileFormat,
        callable $callback = null
    ) {
        $fileFormat = $this->normalizeFileFormat($fileFormat);
        switch ($fileFormat) {
            case 'ods':
                $writerType = Type::ODS;
                break;
            case 'xlsx':
                $writerType = Type::XLSX;
                break;
            default:
                throw new InvalidArgumentException(sprintf(
                    static::$errors['invalid_file_format'], $fileFormat
                ));
        }
        $fileName = $this->normalizeFileName($fileName, $fileFormat);

        $defaultStyle = (new StyleBuilder())
            ->setFontName('Arial')
            ->setFontSize(11)
            ->setShouldWrapText(false)
            ->build();

        $headerRowStyle = (new StyleBuilder())
            ->setFontBold()
            ->build();

        $writer = WriterFactory::create($writerType);

        if ($fileFormat === 'xlsx') {
            $writer->setShouldUseInlineStrings(false);
        }

        $writer->setDefaultRowStyle($defaultStyle);
        $writer->openToBrowser($fileName);

        $recordsetSchemas = array_values($recordsetSchemas);
        foreach ($recordsetSchemas as $index => $schema) {
            $schema = $this->normalizeRecordsetSchema($schema);
            if ($index === 0) {
                $schema['#'] = $writer->getCurrentSheet();
            } else {
                $schema['#'] = $writer->addNewSheetAndMakeItCurrent();
            }
            $schema['#']->setName($schema['name']);
            $writer->addRowWithStyle($schema['columns'], $headerRowStyle);
            $recordsetSchemas[$index] = $schema;
        }

        foreach ($records as $subset) {
            if ($callback) {
                $callback($subset);
            }
            foreach ($subset as $recordsetIndex => $rows) {
                $schema = $recordsetSchemas[$recordsetIndex] ?? [];
                if (empty($schema) || !is_array($rows)) {
                    throw new InvalidArgumentException(static::$errors['invalid_record_subset']);
                }
                $columnCount = count($schema['columns']);
                $writer->setCurrentSheet($schema['#']);
                foreach ($rows as $row) {
                    if (!is_array($row) || (!empty($row) && (count($row) !== $columnCount))) {
                        throw new InvalidArgumentException(static::$errors['invalid_record']);
                    }
                    $writer->addRow($row);
                }
            }
        }

        $writer->setCurrentSheet($recordsetSchemas[0]['#']);

        $writer->close();
    }

    protected function normalizeFileFormat(string $fileFormat): string
    {
        $fileFormat = new Text($fileFormat);
        return $fileFormat->trim()->toLowerCase()->toString();
    }

    protected function normalizeFileName(
        string $fileName,
        string $fileExtension = '',
        bool $appendTimestamp = true
    ): string {
        $fileName = new Text($fileName);
        $fileName->slugify('_');
        if ($fileName->getLength() === 0) {
            $fileName->append($this->getOption('default_recordset_name'));
        }
        if ($appendTimestamp && $this->getOption('append_timestamp')) {
            $fileName->append('_' . date('Ymd_His'));
        }
        if ($fileExtension) {
            $fileName->append('.' . $fileExtension);
        }
        return $fileName->toString();
    }

    protected function normalizePropertyName(string $propertyName): string
    {
        $propertyName = new Text($propertyName);
        switch ($this->getOption('json_property_name_style')) {
            case 'camel_case':
                $propertyName->camelize();
                break;
            case 'snake_case':
                $propertyName->underscorize();
                break;
            case 'kebab_case':
                $propertyName->dasherize();
                break;
            case 'pascal_case':
                $propertyName->pascalize();
                break;
            default:
                $propertyName->trim();
        }
        return $propertyName->toString();
    }

    protected function normalizeRecordsetSchema(array $schema): array
    {
        list('name' => $name, 'columns' => $columns) = $schema;
        if (is_string($name)) {
            $name = preg_replace('@\\\/\?\*\:\[\]@', '', $name);
            $name = trim($name, ' \t\n\r\0\'\x0B"');
            $name = substr($name, 0, $this->getOption('max_recordset_name_length'));
        } else {
            $name = '';
        }
        if (!is_array($columns) || empty($columns) || empty($name)) {
            throw new InvalidArgumentException(static::$errors['invalid_recordset_schema']);
        }
        foreach ($columns as &$column) {
            if (is_string($column)) {
                $column = trim($column);
            } else {
                $column = '';
            }
        }

        return [
            'name' => $name,
            'columns' => $columns,
        ];
    }

    protected function getJsonExportOptions(): int
    {
        $options = 0;

        if ($this->getOption('json_pretty_print')) {
            $options |= JSON_PRETTY_PRINT;
        }
        if ($this->getOption('json_unescaped_slashes')) {
            $options |= JSON_UNESCAPED_SLASHES;
        }
        if ($this->getOption('json_unescaped_unicode')) {
            $options |= JSON_UNESCAPED_UNICODE;
        }

        return $options;
    }

    protected function sendHeaders(
        string $fileName,
        string $fileFormat,
        string $charset = 'utf-8'
    ): bool {
        if (headers_sent()) {
            return false;
        }

        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: ' . $this->getContentType($fileFormat) . '; charset=' . $charset);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Transfer-Encoding: binary');

        return true;
    }
}

// -- End of file
