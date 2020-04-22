<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="sixteen wide column">
        <div class="ui padded text container segment">
            <div class="ui styled accordion">
                <div class="title active">
                    <i class="dropdown icon"></i>
                    How to build a contract hyperlink in Excel?
                </div>
                <div class="content active">
                    <ol>
                        <li>Select the cell where you want the <strong>hyperlink</strong>.</li>
                        <li>Insert the formula <code>=HYPERLINK("https://garzoni.dhlab.epfl.ch/contract/view/"&A1)</code>,
                            where A1 is the address of the cell containing the contract ID.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
