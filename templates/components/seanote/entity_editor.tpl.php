<div class="seanote-entity-editor seanote actionable window" tabindex="-1">
    <div class="container">
        <div class="title-bar move-handle">
            <span class="title move-handle">
                <?php echo $this->text->get('seanote.entity_editor.title');?>
            </span>
            <div class="actions">
                <i class="close link icon tipped" data-content="<?php
                echo $this->text->get('seanote.entity_editor.button.close');?>"></i>
            </div>
        </div>
        <div class="content">
            <div class="ui small entity-annotations form">
                <div class="semantic-tags">
                    <div class="field">
                        <label data-single="<?php echo $this->text->get('seanote.entity_editor.label.tag'); ?>"
                        data-multiple="<?php echo $this->text->get('seanote.entity_editor.label.tags'); ?>"></label>
                        <select class="ui search dropdown"></select>
                    </div>
                </div>
                <div class="entities">
                    <div class="field">
                        <label data-single="<?php echo $this->text->get('seanote.entity_editor.label.entity'); ?>"
                        data-multiple="<?php echo $this->text->get('seanote.entity_editor.label.entities'); ?>"></label>
                        <select class="ui search dropdown"></select>
                    </div>
                    <div class="field">
                        <button class="ui basic button add-entity">
                            <?php echo $this->text->get('seanote.entity_editor.button.add_new_entity'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="ui small entity-properties form"></div>
        </div>
        <div class="action-bar">
            <button class="ui basic close button">
                <?php echo $this->text->get('seanote.entity_editor.button.dismiss');?>
            </button>
            <button class="ui green save button">
                <?php echo $this->text->get('seanote.entity_editor.button.save');?>
            </button>
        </div>
    </div>
</div>
