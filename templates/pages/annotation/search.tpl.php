<?php

use Application\Core\Type\Map;
use function Application\createText as _;

$personUrl = $this->app->getUrl('module', 'person/view');
$htmlOptions = [
    'list_attributes' => ['class' => 'ui list'],
    'table_attributes' => ['class' => 'ui fixed compact celled table details'],
    'escape_special_chars' => false,
];

?>

<?php ob_start(); ?>

<script>
    function getImageMask(width, height, scaleFactor, segments) {
        var content = '<defs><mask id="mask"><g transform="scale(' + scaleFactor + ')">'
            + '<rect width="' + width + '" height="' + height + '" fill="#999" />';
        $.each(segments, function(i, bbox) {
            content += '<rect' + ' x="' + bbox.x + '"' + ' y="' + bbox.y + '"'
                + ' width="' + bbox.w + '"' + ' height="' + bbox.h + '"' + ' fill="white" />';
        });
        content += '</g></mask></defs>';
        return content;
    }

    function getImagePreview(width, height, maxWidth, maxHeight, url, segments) {
        var originalWidth = width,
            originalHeight = height,
            ratio = 0,
            queryStartPos = url.indexOf('?'),
            htmlContent = '',
            params = '';
        if (width > maxWidth) {
            ratio = maxWidth / width;
            height = Math.round(height * ratio);
            width = Math.round(width * ratio);
        }
        if (height > maxHeight) {
            ratio = maxHeight / height;
            height = Math.round(height * ratio);
            width = Math.round(width * ratio);
        }

        params += 'full/' + width + ',' + height + '/0/default.jpg';

        if (queryStartPos === -1) {
            url += '/' + params;
        } else {
            url = url.substring(0, queryStartPos) + '/' + params
                + url.substring(queryStartPos);
        }

        if ($.isArray(segments) && (segments.length > 0)) {
            htmlContent = '<svg width="' + width + '" height="' + height + '" class="image">'
                + getImageMask(originalWidth, originalHeight, (width / originalWidth), segments)
                + '<image xlink:href="' + url + '" mask="url(#mask)" />'
                + '</svg>';
        } else {
            htmlContent = '<img src="' + url + '" width="' + width + '" height="' + height + '">';
        }

        return htmlContent;
    }

    function triggerPreview() {
        $('.ui.active.tab').find('.search-result').first().trigger('mouseenter');
    }

    function exportAll(format) {
        var form = jQuery('#search-results .data-exporter');
        form.trigger('reset');
        form.find('.format').val(format);
        form.find('.query').val(btoa(jQuery('#search-form input[name="q"]').val()));
        form.submit();
    }

    function bindMenuActions() {
        jQuery('#search-results .menu .item').on('click', function () {
            var action = jQuery(this).data('action');
            switch (action) {
                case 'export-all-xlsx':
                    exportAll('xlsx');
                    break;
                case 'export-all-ods':
                    exportAll('ods');
                    break;
                case 'export-all-csv':
                    exportAll('csv');
                    break;
                case 'export-all-json':
                    exportAll('json');
                    break;
            }
        });
    }

    $(document).ready(function() {
        var searchResults = $('.search-result'),
            preview = $('#page-preview');
        searchResults.on('mouseenter', function () {
            var maxWidth = 450,
                maxHeight = 600,
                width = parseInt($(this).attr('data-width')),
                height = parseInt($(this).attr('data-height')),
                url = $(this).attr('data-image'),
                segments = [],
                htmlContent = '';
            $.each($(this).attr('data-segments').split(';'), function(key, value) {
                var bbox = value.split(',');
                if (bbox.length !== 4) {
                    return;
                }
                segments.push({
                    x: parseInt(bbox[0]),
                    y: parseInt(bbox[1]),
                    w: parseInt(bbox[2]),
                    h: parseInt(bbox[3])
                });
            });
            if (width && height && url) {
                htmlContent = getImagePreview(width, height, maxWidth, maxHeight, url, segments);
            }
            preview.find('.header > .title').text($(this).attr('data-title') || 'Page Preview');
            preview.find('.content').html(htmlContent);
            preview.css('visibility', 'visible');
            searchResults.find('> .icon:not(.outline)').addClass('outline');
            $(this).find('> .icon').removeClass('outline');
        });
        $('.tabular.menu .item').tab({
            onVisible: function() {
                triggerPreview();
            }
        });
        triggerPreview();
        bindMenuActions();
    });

    $(document).ready(function () {
        var box = $('#page-preview'),
            top = box.offset().top;
        $(window).scroll(function () {
            var y = $(this).scrollTop();
            if (y >= (top - 60)) {
                box.addClass('fixed');
            } else {
                box.removeClass('fixed');
            }
            box.width(box.parent().width());
        });
    });
</script>

<?php $this->addSnippet(ob_get_clean()); ?>

<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="sixteen wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">Terms</h4>
            </div>
            <div class="content">
                <form id="search-form" class="ui form" autocomplete="off">
                    <div class="fields">
                        <div class="fourteen wide field">
                            <input name="q" type="text" value="<?php
                                echo _($this->keywords)->htmlEncode(); ?>">
                        </div>
                        <div class="two wide field">
                            <div class="field">
                                <button class="ui primary button" type="submit"><?php
                                    echo $this->text->get('app.search'); ?></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($this->keywords) : ?>
<?php
    $from = ($this->current_page - 1) * $this->results_per_page + 1;
    $to = $from + $this->results_per_page - 1;
    if ($to > $this->result_count) {
        $to = $this->result_count;
    }
?>
<div class="row">
    <div class="eight wide column" id="search-results">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">Results</h4>
                <div class="tools">
                    <div class="ui dropdown table-menu">
                        <i class="ellipsis vertical icon link"></i>
                        <div class="menu">
                            <div class="item">
                                Export All
                                <i class="dropdown icon"></i>
                                <div class="menu">
                                    <div class="item" data-action="export-all-xlsx">
                                        <i class="file excel outline icon"></i>
                                        Excel File (.xlsx)
                                    </div>
                                    <div class="item" data-action="export-all-ods">
                                        <i class="file outline icon"></i>
                                        ODS File (.ods)
                                    </div>
                                    <div class="item" data-action="export-all-json">
                                        <i class="file alternate outline icon"></i>
                                        JSON File (.json)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content">
                <form class="data-exporter" method="post" action="<?php echo $this->app->getUrl('module', 'contract/export'); ?>">
                    <input type="hidden" class="format" name="format" />
                    <input type="hidden" class="query" name="query" />
                </form>
                <p>
                    <?php echo 'Showing ' . $from . ' to ' . $to
                        . ' of ' . $this->text->pluralize('search.contract_count', $this->result_count);  ?>
                </p>
                <div class="ui fitted divider"></div>
                <div class="ui list">
                <?php
                foreach ($this->results as $manifestId => $canvases) :
                    if (!isset($this->documents[$manifestId])) continue;
                    $document = $this->documents[$manifestId];
                    $documentTitle = _($document['label'])->htmlEncode();
                    $documentUrl = $this->sequence_view_url . '/' . $document['id'] . '/normal';
                    $metadata = $document['metadata'];
                    ?>
                    <div class="item">
                        <i class="book icon"></i>
                        <div class="content" style="width:100%;">
                            <div class="header">
                                <a href="<?php echo $documentUrl ?>" target="_blank"><?php echo $documentTitle; ?></a>
                                <span class="note pull-right">
                                    <?php echo ($metadata['startDate'] ?? '')
                                        . ' &mdash; ' . ($metadata['endDate'] ?? ''); ?>
                                </span>
                            </div>
                            <div class="list">
                                <?php
                                foreach ($canvases as $canvasId => $result) :
                                    $page = $this->pages[$canvasId];
                                    $pageTitle = $page['label']['en'] ?? $page['code'];
                                    $pageUrl = $this->canvas_view_url . '/' . $document['id'] . '/' . $page['code']
                                        . '?find=' . urlencode($this->keywords);
                                    $thumbnailUrl = $page['thumbnail']['@id'] ?? '';
                                    $targetCount = count($result['targets']);
                                    $contracts = $result['contracts'];
                                    ?>
                                    <div class="item search-result"
                                         data-title="<?php echo $documentTitle . ', ' . $pageTitle; ?>"
                                         data-image="<?php echo $thumbnailUrl; ?>"
                                         data-width="<?php echo $page['width']; ?>"
                                         data-height="<?php echo $page['height']; ?>"
                                         data-segments="<?php echo implode(';', $result['targets']); ?>">
                                        <i class="file outline icon"></i>
                                        <div class="content">
                                            <a class="header">
                                                <a href="<?php echo $pageUrl ?>" target="_blank"><?php
                                                    echo $page['label']['en'] ?? $page['code']; ?></a>
                                                <?php
                                                echo ($targetCount > 1) ? '(' . $targetCount . ')' : '';
                                                ?>
                                            </a>
                                            <?php if (count($contracts)) : ?>
                                            <div class="list">
                                                <?php
                                                    foreach ($contracts as $contract) :
                                                        $mentions = json_decode($contract['mentions'], true) ?: [];
                                                        $contractUrl = $this->app->getUrl('module', 'contract/view/' . $contract['contract_id']);
                                                        $summary = $this->contract_model->getSummaryTable($mentions, $personUrl, $contractUrl);
                                                        echo (new Map($summary))->toHtml($htmlOptions);
                                                    endforeach;
                                                ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php
                                endforeach;
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php
                endforeach;
                ?>
                </div>
                <div class="ui pagination menu">
                    <?php
                    for ($i = 1; $i <= $this->last_page; $i++) :
                        if (($i > 4) && ($i < $this->last_page - 3)
                            && (($i < $this->current_page - 3) || ($i > $this->current_page + 3))) : continue;
                        elseif ((($i == 4) && ($this->current_page > 8))
                            || (($i == $this->last_page - 3) && ($this->current_page < $this->last_page - 7))) :
                            ?>
                            <div class="disabled item">...</div>
                        <?php   else : ?>
                            <a class="<?php if ($i == $this->current_page) echo 'active ' ?>item" href="<?php
                            echo $this->app->getUrl('action', '?q=' . urlencode($this->keywords) . '&page=' . $i);
                            ?>"><?php echo $i ?></a>
                        <?php
                        endif;
                    endfor;
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="eight wide column">
        <div id="page-preview" class="ui segment box">
            <div class="header">
                <h4 class="title">Page Preview</h4>
            </div>
            <div class="content"></div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $this->include('layouts/default/end.tpl.php'); ?>
