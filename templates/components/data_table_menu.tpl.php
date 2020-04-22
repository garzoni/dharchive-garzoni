
<div class="ui dropdown table-menu">
    <i class="ellipsis vertical icon link"></i>
    <div class="menu">
        <div class="togglable disabled item" data-action="print">
            <span class="description">p</span>
            <i class="print icon"></i>
            Print
        </div>
        <div class="togglable disabled item" data-action="copy">
            <i class="copy icon"></i>
            <span class="description">c</span>
            Copy
        </div>
        <div class="item">
            <i class="download icon"></i>
            Export
            <i class="dropdown icon"></i>
            <div class="menu">
                <div class="togglable disabled item export-menu">
                    <i class="dropdown icon"></i>
                    Selected
                    <div class="menu">
                        <div class="item" data-action="<?php echo $this->custom_export ? 'export-xlsx' : 'excel'; ?>">
                            <i class="file excel outline icon"></i>
                            Excel File (.xlsx)
                        </div>
                        <?php if ($this->custom_export) : ?>
                            <div class="item" data-action="export-ods">
                                <i class="file outline icon"></i>
                                ODS File (.ods)
                            </div>
                            <div class="item" data-action="export-json">
                                <i class="file alternate outline icon"></i>
                                JSON File (.json)
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($this->custom_export) : ?>
                <div class="item export-all-menu">
                    <i class="dropdown icon"></i>
                    All
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
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<template id="tpl-column-selector">
    <div class="ui dropdown column-selector">
        <i class="columns icon link"></i>
        <div class="menu">
            <div class="item column">
                <i class="icon"></i>
                <span class="title"></span>
            </div>
        </div>
    </div>
</template>
