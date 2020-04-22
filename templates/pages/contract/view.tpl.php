<?php

use Application\Core\Type\Map;
use Application\Core\Type\JsonObject;
use Application\Core\Type\Json\Schema as JsonSchema;

$htmlOptions = [
    'list_attributes' => ['class' => 'ui list'],
    'table_attributes' => ['class' => 'ui compact celled table'],
    'escape_special_chars' => false,
];

$printTag = function(array $tag)
{
    $label = $tag['labels']['preferred']['en'] ?? '';
    echo '<div class="ui label pull-right">' . htmlspecialchars($label) . '</div>';
};

$printMention = function(array $mention, JsonSchema $schema) use ($htmlOptions)
{
    array_walk_recursive($mention, function(&$item) {
        if (is_string($item) && isset($this->entity_names[$item])) {
            $item = $this->entity_names[$item];
        }
    });
    try {
        $mention = new JsonObject($mention, $schema);
        $mention->setTranslator($this->text);
        echo $mention->toHtml($htmlOptions);
    } catch (Exception $exception) {
        var_export($mention);
    }
};

$printMentions = function(array $mentions, array $callbacks) use ($printTag)
{
    if (empty($mentions)) {
        return;
    }
    ?>
    <div class="row">
        <div class="sixteen wide column">
            <div class="ui segment box">
                <div class="header">
                    <?php if (isset($callbacks['header'])) $callbacks['header'](); ?>
                </div>
                <div class="content">
                    <div class="ui styled labeled fluid accordion">
                        <?php foreach($mentions as $index => $mention) : ?>
                            <div class="title">
                                <i class="dropdown icon"></i>
                                <?php
                                    if (isset($callbacks['title'])) {
                                        $callbacks['title']($mention, $index);
                                    }
                                ?>
                                <?php $printTag($mention['tag']); ?>
                            </div>
                            <div class="content data-object">
                                <?php
                                    if (isset($callbacks['content'])) {
                                        $callbacks['content']($mention, $index);
                                    }
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
};

$iiifUrl = $this->config->iiif->image->server->url . '/' . $this->contract['manifest_id']
    . '-' . str_pad(strval($this->contract['page_number']), 6, '0', STR_PAD_LEFT);
$imageUrl = $iiifUrl . '/'
    . ((substr($this->contract['target_bbox'], -7) === '150,150') ? 'full' : $this->contract['target_bbox'])
    . '/,300/0/default.jpg';

$sequenceUrl = $this->app->getUrl('module', 'sequence/view/' . $this->contract['manifest_id'] . '/normal');
$pageUrl = $this->app->getUrl('module', 'page/view/' . $this->contract['manifest_id'] . '/' . $this->contract['canvas_code']);
$personUrl = $this->app->getUrl('module', 'person/view');

$mentions = [];

foreach (json_decode($this->contract['mentions'], true) as $mention) {
    if (isset($mention['instanceOf'])) {
        $mentions[$mention['instanceOf']][] = $mention;
    }
}

$document = $this->document->get('properties.metadata');
$documentDetails = (new Map([
    'Fund' => $document['fund'],
    'Series' => $document['series'],
    'Box' => $document['box'],
    'Register' => '<a href="' . $sequenceUrl . '">' . $document['register'] . '</a>',
    'Start Date' =>  $document['startDate'],
    'End Date' =>  $document['endDate'],
    'Page' => '<a href="' . $pageUrl . '">' . $this->contract['page_number'] . '</a> / ' . $document['pageCount'],
]))->toHtml($htmlOptions);

?>

<?php ob_start(); ?>
<script>
    $(document).ready(function() {
        function toggleAccordions(display) {
            var action = 'toggle';
            if (display === true) {
                action = 'open'
            } else if (display === false) {
                action = 'close'
            }
            $('.ui.accordion').each(function() {
                var sectionCount = $(this).find('> .content').length;
                for (var i = 0; i < sectionCount; i++) {
                    $(this).accordion(action, i);
                }
            });
        }
        var menu = $('.contract-menu');
        menu.find('.item').on('click', function () {
            var action = jQuery(this).data('action');
            switch (action) {
                case 'print':
                    window.print();
                    break;
                case 'expand-all':
                    toggleAccordions(true);
                    break;
                case 'collapse-all':
                    toggleAccordions(false);
                    break;
            }
            menu.dropdown('hide');
        });
        menu.find('.item').each(function() {
            var key = $(this).find('.description').text(),
                menuItem = $(this);
            if (key.length === 1) {
                Mousetrap.bind(key, function() {
                    menuItem.trigger('click');
                    return false;
                });
            }
        });
    });
</script>
<?php $this->addSnippet(ob_get_clean()); ?>

<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="eleven wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">Contract Details</h4>
                <div class="tools">
                    <div class="ui dropdown contract-menu">
                        <i class="ellipsis vertical icon link"></i>
                        <div class="menu">
                            <div class="item" data-action="print">
                                <span class="description">p</span>
                                <i class="print icon"></i>
                                Print
                            </div>
                            <div class="item" data-action="expand-all">
                                <i class="plus square outline icon"></i>
                                <span class="description">e</span>
                                Expand All
                            </div>
                            <div class="item" data-action="collapse-all">
                                <i class="minus square outline icon"></i>
                                <span class="description">c</span>
                                Collapse All
                            </div>
                            <div class="item">
                                <i class="download icon"></i>
                                Export
                                <i class="dropdown icon"></i>
                                <div class="menu">
                                    <a class="item" href="<?php echo $this->export_url . '&format=xlsx'; ?>">
                                        <i class="file excel outline icon"></i>
                                        Excel File (.xlsx)
                                    </a>
                                    <a class="item" href="<?php echo $this->export_url . '&format=ods'; ?>">
                                        <i class="file outline icon"></i>
                                        ODS File (.ods)
                                    </a>
                                    <a class="item" href="<?php echo $this->export_url . '&format=json'; ?>">
                                        <i class="file alternate outline icon"></i>
                                        JSON File (.json)
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content data-object">
            <?php
                $contract = new JsonObject(
                    $mentions['grz:ContractMention'][0] ?? [],
                    $this->schemas['contract_mention']
                );
                $contract->setTranslator($this->text);
                echo $contract->toHtml($htmlOptions);
            ?>
            </div>
        </div>
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">Contract Preview</h4>
            </div>
            <div class="content">
                <a href="<?php echo $pageUrl . '?highlight=' . $this->contract['target_id']; ?>">
                    <img class="ui image" src="<?php echo $imageUrl; ?>" />
                </a>
            </div>
        </div>
    </div>
    <div class="five wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">Primary Source</h4>
            </div>
            <div class="content">
                <?php echo $documentDetails; ?>
            </div>
        </div>
    </div>
</div>

<?php

$printMentions($mentions['grz:PersonMention'] ?? [], [
    'header' => function () {
        echo '<h4 class="title">Person Mentions</h4>';
    },
    'title' => function ($mention) use ($personUrl) {
        echo ($mention['fullName'] ?? '') . PHP_EOL;
        echo $this->contract_model->getPersonLink($mention, $personUrl) . PHP_EOL;
        echo $this->contract_model->getPersonMentionGender($mention) . PHP_EOL;
        echo $this->contract_model->getPersonMentionProfessions($mention) . PHP_EOL;
    },
    'content' => function ($mention) use ($printMention) {
        $standardForm = $mention['geoOrigin']['standardForm'] ?? '';
        if ($this->locations->has($standardForm)) {
            $location = $this->locations->get($standardForm);
            $mention['geoOrigin'] += array_filter([
                'province' => $location['province'],
                'country' => $location['country'],
            ]);
        }
        $professions = $mention['professions'] ?? [];
        foreach ($professions as $index => $profession) {
            $standardForm = $profession['standardForm'] ?? '';
            if ($this->professions->has($standardForm)) {
                $profession = $this->professions->get($standardForm);
                $mention['professions'][$index] += array_filter([
                    'occupation' => $profession['occupation'],
                    'category' => $profession['category_name'],
                    'materials' => $profession['material'],
                    'products' => $profession['product'],
                ]);
            }
        }
        $printMention($mention, $this->schemas['person_mention']);
    },
]);

$printMentions($mentions['grz:EventMention'] ?? [], [
    'header' => function () {
        echo '<h4 class="title">Events</h4>';
    },
    'title' => function ($mention, $index) {
        echo 'Event ' . ($index + 1);
    },
    'content' => function ($mention) use ($printMention) {
        $printMention($mention, $this->schemas['event_mention']);
    },
]);

$printMentions($mentions['grz:FinancialConditionMention'] ?? [], [
    'header' => function () {
        echo '<h4 class="title">Financial Conditions</h4>';
    },
    'title' => function ($mention, $index) {
        echo 'Financial Condition ' . ($index + 1);
    },
    'content' => function ($mention) use ($printMention) {
        $printMention($mention, $this->schemas['financial_condition_mention']);
    },
]);

$printMentions($mentions['grz:HostingConditionMention'] ?? [], [
    'header' => function () {
        echo '<h4 class="title">Hosting Conditions</h4>';
    },
    'title' => function ($mention, $index) {
        echo 'Hosting Condition ' . ($index + 1);
    },
    'content' => function ($mention) use ($printMention) {
        $printMention($mention, $this->schemas['hosting_condition_mention']);
    },
]);

?>

<?php $this->include('layouts/default/end.tpl.php'); ?>
