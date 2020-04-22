<div class="seanote-modal seanote actionable modal window" tabindex="-1">
    <div class="container">
        <div class="title-bar">
            <?php echo $this->text->get('seanote.modal.title'); ?>
            <div class="actions">
                <i class="close link icon tipped" data-content="<?php
                    echo $this->text->get('seanote.modal.button.close'); ?>"></i>
            </div>
        </div>
        <div class="content"></div>
        <div class="action-bar">
            <button class="ui close button">
                <?php echo $this->text->get('seanote.modal.button.no'); ?>
            </button>
            <button class="ui green save button">
                <?php echo $this->text->get('seanote.modal.button.yes'); ?>
            </button>
        </div>
    </div>
</div>
