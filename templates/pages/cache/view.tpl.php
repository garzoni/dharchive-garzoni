<?php

use Application\Core\Type\Table;

$htmlOptions = [
    'table_attributes' => ['class' => 'ui compact celled table'],
];

?>

<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="sixteen wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">
                    <?php echo $this->text->get('cache.content'); ?>
                </h4>
            </div>
            <div class="content">
            <?php if ($this->item instanceof Table) : ?>
                <?php echo $this->item->toHtml($htmlOptions); ?>
            <?php else : ?>
                <pre class="line-numbers"><code class="language-php"><?php
                     var_export($this->item); ?></code></pre>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
