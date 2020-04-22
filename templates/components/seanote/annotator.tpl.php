<div id="seanote-annotator" class="seanote-annotator seanote actionable window"
     tabindex="-1">
    <div class="container">
        <div class="title-bar move-handle">
            <span class="title move-handle">
                <?php echo $this->text->get('seanote.annotator.title'); ?>
            </span>
            <div class="actions">
                <i class="close link icon tipped" data-content="<?php
                echo $this->text->get('seanote.annotator.button.close');
                ?>"></i>
            </div>
        </div>
        <div class="content">
            <div class="ui tabular menu">
                <a class="active item" data-tab="transcriptions"><?php
                    echo $this->text->get('seanote.annotator.label.transcriptions');
                    ?></a>
                <a class="item" data-tab="mentions"><?php
                    echo $this->text->get('seanote.annotator.label.mentions');
                    ?></a>
            </div>
            <div class="ui tab active" data-tab="transcriptions">
                <div class="selection-preview"></div>
                <div class="ui relaxed divided list" id="transcription-list">
                    <div class="item template">
                        <div class="content">
                            <div class="header">
                                <span class="title"><?php
                                    echo $this->text->get('seanote.annotator.label.title');
                                    ?></span>
                                <span class="actions">
                                    <i class="help icon" data-action="get-info"></i>
                                    <i class="write link icon tipped" data-content="<?php
                                        echo $this->text->get('seanote.annotator.button.edit');
                                    ?>" data-action="edit"></i>
                                    <i class="close link icon tipped" data-content="<?php
                                        echo $this->text->get('seanote.annotator.button.delete');
                                    ?>" data-action="delete"></i>
                                </span>
                            </div>
                            <div class="description">
                                <p class="content"><?php
                                    echo $this->text->get('seanote.annotator.label.content');
                                    ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ui tab" data-tab="mentions">
                <div class="ui relaxed divided list" id="mention-list">
                    <div class="item template">
                        <div class="content">
                            <div class="header">
                                <span class="title"><?php
                                    echo $this->text->get('seanote.annotator.label.title');
                                    ?></span>
                                <span class="actions">
                                    <i class="help icon" data-action="get-info"></i>
                                    <i class="write link icon tipped" data-content="<?php
                                        echo $this->text->get('seanote.annotator.button.edit');
                                    ?>" data-action="edit"></i>
                                    <i class="close link icon tipped" data-content="<?php
                                        echo $this->text->get('seanote.annotator.button.delete');
                                    ?>" data-action="delete"></i>
                                </span>
                            </div>
                            <div class="description">
                                <p>
                                    <span class="mention-type"><?php
                                        echo $this->text->get('seanote.annotator.label.type');
                                        ?></span>
                                    <span class="tag-list"></span>
                                </p>
                                <p class="entity-list"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="action-bar">
            <div class="tab-actions">
                <?php if (array_key_exists('transcriptions', $this->annotation_rules)) : ?>
                    <?php $rules = $this->annotation_rules['transcriptions']; ?>
                    <?php if (count($rules['types']) === 1) : ?>
                        <button class="ui basic button add-transcription" data-tab="transcriptions"
                                data-qname="<?php echo $rules['types'][0]; ?>"><?php
                            echo $this->text->get('seanote.annotator.button.add_transcription');
                            ?></button>
                    <?php else : ?>
                        <div class="ui top left pointing basic dropdown button" data-tab="transcriptions">
                            <i class="caret up icon"></i>
                            <span class="text"><?php
                                echo $this->text->get('seanote.annotator.button.add_transcription');
                                ?></span>
                            <div class="menu">
                            <?php foreach ($rules['types'] as $typeQName) : ?>
                                <div class="add-transcription item" data-qname="<?php
                                    echo $typeQName; ?>"><?php echo $typeQName; ?></div>
                            <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (array_key_exists('mentions', $this->annotation_rules)) : ?>
                    <?php $rules = $this->annotation_rules['mentions']; ?>
                    <?php if (count($rules['types']) === 1) : ?>
                        <button class="ui basic button add-mention" data-tab="mentions"
                                data-qname="<?php echo $rules['types'][0]; ?>"><?php
                            echo $this->text->get('seanote.annotator.button.add_mention');
                            ?></button>
                    <?php else : ?>
                        <div class="ui top left pointing basic dropdown button" data-tab="mentions"
                             style="display: none;">
                            <i class="caret up icon"></i>
                            <span class="text"><?php
                                echo $this->text->get('seanote.annotator.button.add_mention');
                                ?></span>
                            <div class="menu">
                                <?php foreach ($rules['types'] as $typeQName) : ?>
                                    <div class="add-mention item" data-qname="<?php
                                        echo $typeQName; ?>"><?php echo $typeQName; ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="ui icon buttons">
                <button class="ui icon button previous-segment tipped" data-content="<?php
                    echo $this->text->get('seanote.annotator.button.previous_segment');
                ?>"><i class="chevron left icon"></i>
                </button>
                <button class="ui icon button next-segment tipped" data-content="<?php
                    echo $this->text->get('seanote.annotator.button.next_segment');
                ?>"><i class="chevron right icon"></i>
                </button>
            </div>
        </div>
    </div>
</div>
