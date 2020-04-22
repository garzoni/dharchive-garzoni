<?php ob_start(); ?>
<script>
    $(document).ready(function() {
        var dtRules = new App.DataTable({
            id: 'rules-table',
            table: {
                stateSave: false,
                fixedHeader: {
                    header: true,
                    headerOffset: $('#pagelet-header').height()
                },
                columns: [
                    {
                        name: 'code'
                    },
                    {
                        name: 'label'
                    }
                ],
                drawCallback: function() {
                    $('.tooltipped').popup({
                        variation: 'tiny wide'
                    });
                }
            }
        });
    });
</script>

<?php $this->addSnippet(ob_get_clean()); ?>

<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="sixteen wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">
                    <?php echo $this->text->get('app.translations'); ?>
                </h4>
            </div>
            <div class="content">
                <table id="rules-table" class="ui compact celled table" width="100%">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Code</th>
                            <th>Label</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->rules as $code => $label): ?>
                        <tr>
                            <td></td>
                            <td><?php echo $code; ?></td>
                            <td contenteditable="true"><?php echo $label; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
