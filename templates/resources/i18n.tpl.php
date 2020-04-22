<?php

$indent = str_repeat(' ', 2);

echo 'var translations = {' . PHP_EOL;

$i = 0;
foreach ($this->languages as $languageCode) :
    $i++;
    echo $indent . $languageCode . ': {' . PHP_EOL;

    $this->text->changeLanguage($languageCode);
    $rules = $this->text->export($this->namespaces);

    if ($this->compact && count($this->namespaces) === 1) :
        $rules = array_pop($rules);
    endif;

    $j = 0;
    foreach ($rules as $code => $value) :
        $j++;
        echo str_repeat($indent, 2) . $code . ': ';
        echo json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            . ($j < count($rules) ? ',' : '') . PHP_EOL;
    endforeach;

    echo $indent . '}' . ($i < count($this->languages) ? ',' : '') . PHP_EOL;
endforeach;

echo '};';

// -- End of file
