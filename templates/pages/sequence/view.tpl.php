<?php

use function Application\createText as _;
use function Application\getThumbnailUrl;

$from = ($this->current_page - 1) * $this->pages_per_page + 1;
$to = $from + $this->pages_per_page - 1;
if ($to > $this->pages_count) {
    $to = $this->pages_count;
}

?>

<?php ob_start(); ?>

<?php if ($this->app->hasPermission('delete_documents')) : ?>
    <div class="ui small modal">
        <i class="close icon"></i>
        <div class="header"><?php echo $this->text->get('page.delete_confirmation'); ?></div>
        <div class="content">
            <p><?php echo $this->text->get('page.message.delete_confirmation'); ?></p>
        </div>
        <div class="actions">
            <div class="ui negative button">No</div>
            <div class="ui approve button">Yes</div>
        </div>
    </div>
<?php endif; ?>

<script>
    $(document).ready(function() {
        var cards = $('.ui.card'),
            actionIcons = cards.find('.meta.actions');
        cards.find('.action').popup({
            position: 'top center'
        });
        cards.on('mouseenter', function () {
            $(this).find('.meta.actions').show();
        });
        cards.on('mouseleave', function () {
            $(this).find('.meta.actions').hide();
        });

        cards.find('.annotation-info').popup();

        <?php if ($this->app->hasPermission('delete_documents')) : ?>

        actionIcons.find('.delete').on('click', function() {
            var card = $(this).closest('.ui.card');
            $('.small.modal').modal({
                onApprove: function() {
                    jQuery.post('<?php echo $this->canvas_index_url . '/delete'; ?>', {
                        'canvas_id': card.attr('data-id')
                    }).done(function(response) {
                        if (response.status === true) {
                            card.remove();
                        }
                    });
                }
            }).modal('show');
        });

        <?php endif; ?>
    });
</script>

<?php $this->addSnippet(ob_get_clean()); ?>

<?php $this->include('layouts/default/begin.tpl.php'); ?>

<?php if ($this->pages) : ?>
<div class="row">
    <div class="sixteen wide column">
        <p>
            <strong><?php echo $this->text->pluralize('sequence.page_count',
                    $this->pages_count); ?></strong>
            (<?php echo $this->text->get('app.displaying')
                . ' ' . $from . ' &ndash; ' . $to;  ?>)
        </p>
    </div>
</div>
<div class="row">
    <div class="sixteen wide column">
        <div class="ui five cards">
        <?php
            foreach ($this->pages as $page) :
                $properties = json_decode($page['properties'], true);
                $title = _($this->text->resolve($properties['label']))->htmlEncode();
                $thumbnailUrl = getThumbnailUrl(($properties['thumbnail']['@id'] ?? ''), 0, 300);
                $pageViewUrl = $this->canvas_index_url . '/view/'
                    . $this->document->get('id') . '/' . $properties['code'];

                $annotationInfo = '';
                $annotationStats = $this->annotation_stats->{$properties['code']} ?? [];
                if (!empty($annotationStats)) {
                    $annotationInfo = '<h5>' . $this->text->get('app.annotations') . '</h5>'
                        . '<ul class=\'ui list\'>'
                        . '<li>' . $this->text->get('app.transcriptions') . ': ' . ($annotationStats['transcriptions'] ?: 0) . '</li>'
                        . '<li>' . $this->text->get('app.mentions') . ': ' . $annotationStats['mentions'] . '</li>'
                        . '<li>' . $this->text->get('app.tags') . ': ' . $annotationStats['tags'] . '</li>'
                        . '<li>' . $this->text->get('app.identifications') . ': ' . $annotationStats['identifications'] . '</li>'
                        . '</ul>';
                }
        ?>
                <div class="ui card" data-id="<?php echo $page['id']; ?>">
                    <div class="content">
                        <div class="header">
                            <a href="<?php echo $pageViewUrl; ?>">
                                <?php echo $title; ?>
                            </a>
                            <?php if ($this->app->hasPermission('edit_documents')) : ?>
                            <div class="right floated meta actions">
                                <span class="action delete" data-content="<?php
                                    echo $this->text->get('app.delete'); ?>">
                                    <i class="remove icon"></i>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php if ($thumbnailUrl) : ?>
                        <a href="<?php echo $pageViewUrl; ?>">
                            <img class="ui centered lazy-load image" alt=""
                                 src="<?php echo $this->blank_image_url; ?>"
                                 data-src="<?php echo $thumbnailUrl; ?>"
                                 style="margin-top: 10px;">
                        </a>
                    <?php endif; ?>
                    </div>
                    <div class="extra content">
                        <?php if (!empty($annotationInfo)) : ?>
                            <span class="annotation-info" data-html="<?php echo $annotationInfo; ?>" style="float:right;">
                                <i class="at icon"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
        <?php
            endforeach;
        ?>
        </div>
    </div>
</div>
<div class="row">
    <div class="sixteen wide column">
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
                        echo $this->current_sequence_url . '?page=' . $i;
                    ?>"><?php echo $i ?></a>
                    <?php
                endif;
            endfor;
            ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $this->include('layouts/default/end.tpl.php'); ?>
