<div id="seanote-controls" class="seanote-control-bar">
    <div class="icon ui buttons">
        <a class="ui button previous-page tipped <?php if (!$this->previous_page) echo ' disabled'; ?>"
            href="<?php echo $this->canvas_view_url . '/' . $this->previous_page; ?>"
            data-content="<?php
                echo $this->text->get('seanote.controls.button.previous_page'); ?>">
            <i class="left chevron icon"></i>
        </a>
        <div class="ui dropdown compact selection button page-selector tipped"
            data-content="<?php
            echo $this->text->get('seanote.controls.button.current_page'); ?>">
            <span class="current-page-number"><?php
                echo $this->canvas->get('sequence_number'); ?></span>
            <span class="page-count-separator">/</span>
            <span class="total-page-count"><?php
                echo count($this->pages); ?></span>
            <div class="menu">
            <?php
                foreach ($this->pages as $pageCode => $page) :
                    $canvasUrl = $this->canvas_view_url . '/' . $pageCode;
                    $isCurrentPage = $page['sequence_number'] === $this->canvas->get('sequence_number');
            ?>
                <a class="item<?php if ($isCurrentPage) echo ' active'; ?>"
                   href="<?php echo $canvasUrl; ?>"><?php echo $page['sequence_number']; ?></a>
            <?php
                endforeach;
            ?>
            </div>
        </div>
        <a class="ui button next-page tipped <?php if (!$this->next_page) echo ' disabled'; ?>"
            href="<?php echo $this->canvas_view_url . '/' . $this->next_page ?>"
            data-content="<?php
                echo $this->text->get('seanote.controls.button.next_page'); ?>">
            <i class="right chevron icon"></i>
        </a>
    </div>
    <div class="icon ui buttons right floated">
        <div class="ui compact dropdown selection button document-selector tipped"
            data-content="<?php
            echo $this->text->get('seanote.controls.button.current_document'); ?>"
            data-position="top right">
            <span class="current-document-title"><?php echo $this->document_title ?></span>
            <div class="menu"></div>
        </div>
        <a href="<?php echo $this->documents_list_url ?>">
            <button class="ui button close-document tipped" data-position="top right"
                data-content="<?php
                echo $this->text->get('seanote.controls.button.close'); ?>">
                <i class="remove icon"></i>
            </button>
        </a>
    </div>
</div>
