<?php

use Application\Core\Type\Map;

$personUrl = $this->app->getUrl('module', 'person/view');
$htmlOptions = [
    'list_attributes' => ['class' => 'ui list'],
    'table_attributes' => ['class' => 'ui compact celled table details'],
    'escape_special_chars' => false,
];

?>

<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="sixteen wide column">
        <div class="ui segment">
            <h3>Person Entity Details</h3>
            <?php echo (new Map($this->person->get('properties')))->toHtml($htmlOptions); ?>
            <?php if (!$this->contracts->isEmpty()) : ?>
                <h3>Mentioned in Contracts</h3>
                <?php foreach ($this->contracts->toArray() as $contract) : ?>
                    <?php
                        $mentions = json_decode($contract['mentions'], true);
                        $contractUrl = $this->app->getUrl('module', 'contract/view/' . $contract['contract_id']);
                        $summary = $this->contract_model->getSummaryTable($mentions, $personUrl, $contractUrl);
                        echo (new Map($summary))->toHtml($htmlOptions);
                    ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
