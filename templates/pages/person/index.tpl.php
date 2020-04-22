<?php ob_start(); ?>

<script>
    $(document).ready(function() {

        App.storage.filters = {
            relations: {}
        };

        var defaultOptions = {
                dropdown: {
                    forceSelection: false,
                    onChange: function(value) {
                        var dropdown = $(this).closest('.ui.dropdown'),
                            icon = dropdown.find('.icon.dropdown');
                        if (!value) {
                            return;
                        }
                        icon.removeClass('dropdown');
                        icon.addClass('delete');
                        icon.on('click', function(event) {
                            dropdown.dropdown('clear');
                            $(this).removeClass('delete');
                            $(this).addClass('dropdown');
                            event.stopImmediatePropagation();
                        });
                    }
                }
            };

        function createFilter(options, callbacks) {
            var id = App.getRandomUuid(),
                palette = $($('#tpl-filter-window').html()).attr('id', id),
                fields = $('#persons-table-filter'),
                field = fields.find('.field[data-id="' + id + '"]');
            palette.insertAfter($('body > div').last());
            options = App.merge(true, {
                selector: {
                    id: '#' + id
                },
                window: {
                    width: 350,
                    minWidth: 300,
                    height: 295,
                    minHeight: 295
                }
            }, options || {});

            palette = new App.FilterWindow(options);

            palette.node.find('.ui.dropdown').dropdown(defaultOptions.dropdown);
            palette.node.find('.ui.calendar').calendar(defaultOptions.calendar);

            palette.node.find('.apply.button').on('click', function() {
                var form = palette.node.find('.ui.form'),
                    table = $($('#tpl-filter-table').html()),
                    tbody = table.find('tbody'),
                    tr = tbody.find('tr').detach(),
                    formData = form.serializeObject(),
                    tableData = [];

                App.compact(formData);

                if (!field.length) {
                    field = $($('#tpl-filter-field').html());

                    field.find('[data-action="edit"]').on('click', function() {
                        palette.window.center();
                        palette.open();
                    });

                    field.find('[data-action="delete"]').on('click', function() {
                        delete App.storage.filters.relations[id];
                        search();
                        field.slideUp(250, function() {
                            $(this).remove();
                            palette.destroy();
                        });
                    });

                    field.attr('data-id', id);
                    field.find('label').text(palette.window.getTitle());

                    fields.append(field);
                }

                if ($.isPlainObject(callbacks) && (typeof callbacks.onApply === 'function')) {
                    callbacks.onApply(form, formData, tableData);
                }

                for (var i = 0; i < tableData.length; i++) {
                    App.insertTableRow(tbody, tr, tableData[i]);
                }

                App.storage.filters.relations[id] = formData;

                if (!tableData.length) {
                    table = '<input type="text" value="Any" disabled>';
                }

                field.find('.content').html(table);

                palette.close();
                search();
            });

            palette.node.find('.close').on('click', function() {
                if (!field.length) {
                    palette.destroy();
                }
            });

            palette.window.center();
            palette.open();

            return palette;
        }

        function addRelationFilter(title) {
            var options = {
                    title: title,
                    content: $('#tpl-fl-relation').html(),
                    window: {
                        height: 450,
                        minHeight: 450
                    }
                },
                callbacks = {
                    onApply: function(form, formData, tableData) {
                        if (formData.type) {
                            tableData.push({
                                property: 'Type',
                                value: form.find('.dl-type').dropdown('get text')
                            });
                        }
                        if (formData.person) {
                            tableData.push({
                                property: 'Person',
                                value: form.find('.dl-person').dropdown('get text')
                            });
                        }
                    }
                },
                palette = createFilter(options, callbacks);
            palette.node.find('.dl-person').dropdown(App.merge(true, defaultOptions.dropdown, {
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'value-list/get?label={query}'); ?>',
                    data: {
                        listQName: 'grz:Person',
                        labelProperty: 'name',
                        keyProperty: 'id',
                        language: 'en'
                    }
                }
            }));
        }

        function addFilter(type, title) {
            switch (type) {
                case 'relation':
                    addRelationFilter(title);
                    break;
                default:
                    return;
            }
        }

        var dtFilter = $('#persons-table-filter'),
            dtFilterBox = dtFilter.closest('.segment.box'),
            dtFilterControls = dtFilterBox.find('.header .tools [data-action]'),
            dtFilterTools = {
                name: dtFilter.find('[name="name"]')
            },
            dtPersons = new App.DataTable({
                id: 'persons-table',
                ajax: {
                    url: '<?php echo $this->app->getUrl('controller', 'get'); ?>',
                    type: 'POST',
                    data: function (data) {
                        var relations = [];
                        dtFilter.find('.field[data-id]').each(function() {
                            var rule = App.getPropertyValue(App.storage, 'filters.relations.' + $(this).attr('data-id'));
                            if (rule) {
                                relations.push(rule);
                            }
                        });
                        data.filters = {
                            name: dtFilterTools.name.val(),
                            relations: JSON.stringify(relations)
                        };
                    }
                },
                title: 'Persons',
                filename: 'persons',
                table: {
                    serverSide: true,
                    processing: true,
                    searching: false,
                    fixedHeader: {
                        header: true,
                        headerOffset: $('#pagelet-header').height()
                    },
                    columns: [
                        {
                            data: 'id',
                            orderable: false,
                            className: 'center aligned',
                            width: '2rem',
                            render: function (data, type) {
                                var url = '<?php echo $this->app->getUrl('controller', 'view'); ?>/' + data,
                                    link = '<a href="' + url + '" target="_blank"><i class="ui user outline icon"></i></a>';
                                return (type === 'export') ? url : link;
                            }
                        },
                        {
                            data: 'name',
                            render: function (data, type, row) {
                                var url = '<?php echo $this->app->getUrl('controller', 'view'); ?>/' + row.id,
                                    link = '<a href="' + url + '" target="_blank">' + data + '</a>';
                                return (type === 'export') ? data : link;
                            }
                        }
                    ],
                    order: [
                        [2, 'asc']
                    ],
                    drawCallback: function() {
                        $('.tooltipped').popup({
                            variation: 'tiny wide'
                        });
                    }
                },
                export: {
                    selectionProperty: 'id'
                }
            });

        var search = $.debounce(250, function () {
            dtPersons.table.draw();
        });

        dtFilter.form();

        dtFilterTools.name.on('input', function () {
            search();
        });

        dtFilterControls.on('click', function () {
            var action = $(this).attr('data-action');
            switch (action) {
                case 'reset':
                    dtFilter.form('reset');
                    dtFilter.find('.field[data-type="relation"]').each(function () {
                        $(this).find('[data-action="delete"]').trigger('click');
                    });
                    search();
                    break;
                case 'add-filter':
                    addFilter($(this).attr('data-filter'), $(this).text());
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
                    <?php echo $this->text->get('app.persons'); ?>
                </h4>
                <div class="tools">
                    <?php $this->include('components/data_table_menu.tpl.php'); ?>
                </div>
            </div>
            <div class="content">
                <table id="persons-table" class="ui compact celled table" width="100%">
                    <thead>
                    <tr>
                        <th></th>
                        <th>Link</th>
                        <th>Name</th>
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
                            <div class="header">
                                <i class="filter icon"></i> Add Filter
                            </div>
                            <div class="divider"></div>
                            <div class="item" data-action="add-filter"
                                 data-filter="relation">Relation</div>
                            <div class="divider"></div>
                            <div class="item" data-action="reset">
                                <i class="undo icon"></i> Reset
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content">
                <form id="persons-table-filter" class="ui small form">
                    <div class="field">
                        <label>Name</label>
                        <input type="text" name="name">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<template id="tpl-filter-window">
    <div class="actionable palette window filter">
        <div class="container">
            <div class="title-bar move-handle">
                <span class="title move-handle">Filter</span>
                <div class="actions">
                    <i class="close link icon tipped" data-content="Close"></i>
                </div>
            </div>
            <div class="content"></div>
            <div class="action-bar">
                <button class="ui basic close button">Dismiss</button>
                <button class="ui green apply button">Apply</button>
            </div>
        </div>
    </div>
</template>

<template id="tpl-filter-field">
    <div class="field" data-type="relation">
        <span class="actions">
            <i class="write link icon" data-action="edit"></i>
            <i class="close link icon" data-action="delete"></i>
        </span>
        <label></label>
        <div class="content"></div>
    </div>
</template>

<template id="tpl-filter-table">
    <table class="ui very compact small table">
        <tbody>
        <tr>
            <td data-column="property"></td>
            <td data-column="value"></td>
        </tr>
        </tbody>
    </table>
</template>

<template id="tpl-fl-relation">
    <form class="ui small form">
        <div class="field">
            <label>Type</label>
            <div class="ui selection search fluid dropdown dl-type">
                <input type="hidden" name="type">
                <i class="dropdown icon"></i>
                <div class="default text">Any</div>
                <div class="menu">
                    <?php foreach($this->relation_types as $qualifiedName => $label) : ?>
                        <div class="item" data-value="<?php echo $qualifiedName; ?>">
                            <?php echo $this->text->resolve($label); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="field">
            <label>Person</label>
            <div class="ui selection search fluid dropdown dl-person">
                <input type="hidden" name="person">
                <i class="dropdown icon"></i>
                <div class="default text">Any</div>
                <div class="menu"></div>
            </div>
        </div>
    </form>
</template>

<?php $this->include('layouts/default/end.tpl.php'); ?>
