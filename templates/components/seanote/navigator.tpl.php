<div id="seanote-navigator" class="seanote-navigator seanote window" tabindex="-1">
    <div class="container">
        <div class="title-bar move-handle">
            <span class="title move-handle">
                <?php echo $this->text->get('seanote.navigator.title'); ?>
            </span>
            <div class="actions">
                <i class="close link icon tipped" data-content="<?php
                    echo $this->text->get('seanote.navigator.button.close'); ?>"></i>
            </div>
        </div>
        <div class="content">
            <div id="openseadragon-navigator"></div>
        </div>
    </div>
</div>
