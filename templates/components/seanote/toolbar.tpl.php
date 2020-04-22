<div id="seanote-toolbar" class="seanote-toolbar">
    <div class="ui inverted vertical icon menu" data-group="basic-commands">
        <a class="item zoom-in tipped" data-content="<?php
            echo $this->text->get('seanote.toolbar.button.zoom_in'); ?>">
            <i class="zoom icon"></i>
        </a>
        <a class="item zoom-out tipped" data-content="<?php
            echo $this->text->get('seanote.toolbar.button.zoom_out'); ?>">
            <i class="zoom out icon"></i>
        </a>
        <a class="item toggle-segments tipped" data-content="<?php
            echo $this->text->get('seanote.toolbar.button.toggle_segments'); ?>">
            <i class="browser icon"></i>
        </a>
    </div>
    <?php if ($this->app->hasPermission('create_annotations')) : ?>
    <div class="ui clearing"></div>
    <div class="ui inverted vertical icon menu" data-group="segment-commands">
        <a class="item create-segment tipped" data-content="<?php
            echo $this->text->get('seanote.toolbar.button.create_segment'); ?>">
            <i class="circle thin icon"></i>
        </a>
        <a class="item resize-segment tipped" data-content="<?php
            echo $this->text->get('seanote.toolbar.button.resize_segment'); ?>">
            <i class="sun o icon"></i>
        </a>
        <a class="item delete-segments tipped" data-content="<?php
            echo $this->text->get('seanote.toolbar.button.delete_segment'); ?>">
            <i class="remove icon"></i>
        </a>
        <!--
        <a class="item merge-segments tipped" data-content="<?php
            echo $this->text->get('seanote.toolbar.button.merge_segments'); ?>">
            <i class="radio icon"></i>
        </a>
        <a class="item unmerge-segments tipped" data-content="<?php
            echo $this->text->get('seanote.toolbar.button.unmerge_segments'); ?>">
            <i class="toggle off icon"></i>
        </a>
        -->
        <a class="item group-segments tipped" data-content="<?php
            echo $this->text->get('seanote.toolbar.button.group_segments'); ?>">
            <i class="linkify icon"></i>
        </a>
        <a class="item ungroup-segments tipped" data-content="<?php
            echo $this->text->get('seanote.toolbar.button.ungroup_segments'); ?>">
            <i class="unlinkify icon"></i>
        </a>
    </div>
    <?php endif; ?>
</div>
