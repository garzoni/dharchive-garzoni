<?php ob_start(); ?>

<script>
    $(document).ready(function() {

        var dtFilter = $('#person-mentions-table-filter'),
            dtFilterBox = dtFilter.closest('.segment.box'),
            dtFilterControls = dtFilterBox.find('.header .tools [data-action]'),
            dtFilterTools = {
                full_name: dtFilter.find('[name="full_name"]')
            },
            dtPersons = new App.DataTable({
                id: 'person-mentions-table',
                ajax: {
                    url: '<?php echo $this->app->getUrl('controller', 'get'); ?>',
                    type: 'POST',
                    data: function (data) {
                        data.filters = {
                            full_name: dtFilterTools.full_name.val()
                        };
                    }
                },
                title: 'Person Mentions',
                filename: 'person_mentions',
                table: {
                    serverSide: true,
                    processing: true,
                    searching: false,
                    fixedHeader: {
                        header: true,
                        headerOffset: $('#pagelet-header').height()
                    },
                    lengthMenu: [
                        [25, 50, 100],
                        [25, 50, 100]
                    ],
                    columns: [
                        {
                            data: 'full_name',
                            render: function (data) {
                                return data || '&mdash;';
                            }
                        },
                        {
                            data: 'person_id',
                            orderable: false,
                            render: function (data, type, row) {
                                if (!data) {
                                    return '&mdash;';
                                }
                                var url = '<?php echo $this->app->getUrl('module', 'person/view'); ?>/' + data,
                                    link = '<a href="' + url + '" target="_blank">' + row.person_name + '</a>';
                                return (type === 'export') ? row.person_name : link;
                            }
                        },
                        {
                            data: 'tag',
                            orderable: false,
                            render: function (data) {
                                return data || '&mdash;';
                            }
                        },
                        {
                            data: 'contract_id',
                            orderable: false,
                            className: 'center aligned',
                            width: '2rem',
                            render: function (data, type, row) {
                                var contractUrl = '<?php echo $this->app->getUrl('module', 'contract/view'); ?>/' + data,
                                    pageUrl = '<?php echo $this->app->getUrl('module', 'page/view'); ?>/'
                                        + row.manifest_id + '/' + row.canvas_code + '?highlight=' + row.target_id,
                                    links = '<a href="' + contractUrl + '" target="_blank"><i class="ui newspaper outline icon"></i></a>'
                                        + ' <a href="' + pageUrl + '" target="_blank"><i class="ui edit outline icon"></i></a>';
                                return (type === 'export') ? contractUrl : links;
                            }
                        }
                    ],
                    order: [
                        [1, 'asc']
                    ],
                    drawCallback: function() {
                        $('.tooltipped').popup({
                            variation: 'tiny wide'
                        });
                    },
                    export: {
                        selectionProperty: 'id'
                    }
                }
            });

        var search = $.debounce(250, function () {
            dtPersons.table.draw();
        });

        dtFilter.form();

        dtFilterTools.full_name.on('input', function () {
            search();
        });

        dtFilterControls.on('click', function () {
            var action = $(this).attr('data-action');
            switch (action) {
                case 'reset':
                    dtFilter.form('reset');
                    search();
                    break;
                default:
                    console.log('Undefined filter control: ' + action);
            }
        });
    });
</script>

<?php $this->addSnippet(ob_get_clean()); ?>

<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="twelve wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">
                    <?php echo $this->text->get('app.person_mentions'); ?>
                </h4>
                <div class="tools">
                    <?php $this->include('components/data_table_menu.tpl.php'); ?>
                </div>
            </div>
            <div class="content">
                <table id="person-mentions-table" class="ui compact celled table" width="100%">
                    <thead>
                    <tr>
                        <th></th>
                        <th>Full Name</th>
                        <th>Person</th>
                        <th>Role</th>
                        <th>Contract</th>
                    </tr>
                    </thead>
                </table>
                <form class="data-exporter" method="post" action="<?php echo $this->export_url; ?>">
                    <input type="hidden" class="format" name="format" />
                    <input type="hidden" class="selection" name="id" />
                    <input type="hidden" class="filters" name="filters" />
                </form>
            </div>
        </div>
    </div>
    <div class="four wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">Filters</h4>
                <div class="tools">
                    <div class="ui dropdown">
                        <i class="ellipsis vertical icon link"></i>
                        <div class="menu">
                            <div class="item" data-action="reset">
                                <i class="undo icon"></i> Reset
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content">
                <form id="person-mentions-table-filter" class="ui small form">
                    <div class="field">
                        <label>Name</label>
                        <input type="text" name="full_name">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
