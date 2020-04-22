<?php ob_start(); ?>
<script>
    $(document).ready(function() {
        var corpusStartDate = '1525-01-01',
            corpusEndDate = '1772-12-31',
            defaultOptions = {
                dropdown: {
                    forceSelection: false,
                    delimiter: '|',
                    keys: {
                        delimiter: 220
                    },
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
                },
                calendar: {
                    type: 'month',
                    firstDayOfWeek: 1,
                    formatter: {
                        date: function (date) {
                            return App.formatDate(date, 'Y-M');
                        }
                    },
                    onChange: function(date) {
                        var calendar = $(this).closest('.ui.calendar'),
                            input = calendar.find('.ui.input'),
                            icon = input.find('.icon.delete');
                        if (!date) {
                            return;
                        }
                        if (!icon.length) {
                            icon = $('<i class="delete icon"></i>');
                            icon.on('click', function(event) {
                                calendar.calendar('clear');
                                $(this).remove();
                                event.stopImmediatePropagation();
                            });
                            input.append(icon);
                        }
                    }
                }
            };

        var dtTable = $('#contract-errors-table'),
            dtFilter = $('#contract-errors-table-filter'),
            dtFilterBox = dtFilter.closest('.segment.box'),
            dtFilterControls = dtFilterBox.find('.header .tools [data-action]'),
            dtFilterTools = {
                createdAfter: dtFilter.find('.cal-created-after'),
                createdBefore: dtFilter.find('.cal-created-before'),
                errorStatus: dtFilter.find('.dl-error-status'),
                errorTypes: dtFilter.find('.cb-error-types')
            };

        var dtContractErrors = dtTable.DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?php echo $this->app->getUrl('controller', 'get-contract-errors'); ?>',
                type: 'POST',
                data: function (data) {
                    var errorTypes = [];
                    $.each(dtFilterTools.errorTypes.find('input'), function () {
                        if ($(this).is(':checked')) {
                            errorTypes.push($(this).val());
                        }
                    });
                    data.filters = {
                        created_after: App.formatDate(dtFilterTools.createdAfter.calendar('get date')),
                        created_before: App.formatDate(dtFilterTools.createdBefore.calendar('get date')),
                        error_status: dtFilterTools.errorStatus.dropdown('get value'),
                        error_types: errorTypes
                    };
                }
            },
            columns: [
                {
                    data: 'contract_id',
                    className: 'center aligned',
                    width: '3rem',
                    render: function (data, type, row) {
                        var contractUrl = '<?php echo $this->app->getUrl('module', 'contract/view'); ?>/' + data,
                            pageUrl = '<?php echo $this->app->getUrl('module', 'page/view'); ?>/'
                                + row.manifest_id + '/' + row.canvas_code + '?highlight=' + row.target_id,
                            links = '<a href="' + contractUrl + '"><i class="ui newspaper outline icon"></i></a>'
                                + ' <a href="' + pageUrl + '"><i class="ui edit outline icon"></i></a>';
                        return (type === 'export') ? contractUrl : links;
                    }
                },
                {
                    data: 'date',
                    className: 'center aligned',
                    width: '6rem'
                },
                {
                    data: 'master',
                    render: function (data) {
                        return data || '&mdash;';
                    }
                },
                {
                    data: 'error_type'
                },
                {
                    data: 'status',
                    className: 'center aligned error-status',
                    width: '3rem',
                    render: function(data, type, row) {
                        var classNames = 'ui circle icon ';
                        <?php if ($this->app->hasPermission('edit_annotations')) : ?>
                            classNames += 'link ';
                        <?php endif; ?>
                        if (data === true) {
                            classNames += 'check';
                        } else if (data === false) {
                            classNames += 'check outline';
                        } else {
                            classNames += 'outline';
                        }
                        return '<i class="' + classNames + '" data-entity-id="'
                            + row.contract_id + '" data-error-type-id="'
                            + row.error_type_id + '"></i>';
                    }
                },
                {
                    data: 'reviewer',
                    className: 'nobr',
                    render: function (data) {
                        return data || '&mdash;';
                    }
                },
                {
                    data: 'review_time',
                    className: 'center aligned',
                    width: '6rem',
                    render: function (data) {
                        return data || '&mdash;';
                    }
                }
            ],
            ordering: false,
            searching: false,
            lengthMenu: [
                [15, 25, 50],
                [15, 25, 50]
            ],
            dom:
            '<"ui stackable grid"' +
                '<"row"<"ten wide column"i><"right aligned six wide column"f>>' +
                '<"row dt-table"<"sixteen wide column"tr>>' +
                '<"row"<"six wide column"l><"right aligned ten wide column"p>>' +
            '>',
            drawCallback: function() {
                $('.ui.rating').rating();
            }
        });

        var search = $.debounce(250, function() {
            dtContractErrors.draw();
        });

        <?php if ($this->app->hasPermission('edit_annotations')) : ?>
        var updateErrorStatus = function(row, entityId, errorTypeId, errorStatus) {
            $.ajax({
                method: 'POST',
                url: '<?php echo $this->app->getUrl('controller', 'update-error-status'); ?>',
                data: {
                    entity_id: entityId,
                    error_type_id: errorTypeId,
                    error_status: errorStatus
                }
            }).done(function(data) {
                var rowData = dtContractErrors.row(row).data();
                rowData.status = data.status;
                rowData.reviewer_user_id = data.reviewer_user_id;
                rowData.reviewer = data.reviewer;
                rowData.review_time = data.review_time;
                dtContractErrors.row(row).data(rowData);
                row.find('.error-status i').on('click', function() {
                    bindStatusIconEvents($(this));
                });
            });
        };

        var bindStatusIconEvents = function (icon) {
            var row = icon.closest('tr'),
                status;
            if (icon.hasClass('check')) {
                if (icon.hasClass('outline')) {
                    status = 'corrected';
                } else {
                    status = 'unreviewed';
                }
            } else {
                status = 'reviewed';
            }
            updateErrorStatus(row, icon.data('entity-id'), icon.data('error-type-id'), status);
        };

        dtContractErrors.on('draw', function() {
            dtTable.find('.error-status i').on('click', function() {
                bindStatusIconEvents($(this));
            });
        });
        <?php endif; ?>

        dtFilter.form();

        dtFilterTools.createdAfter.calendar(
            App.merge(true, defaultOptions.calendar, {
                initialDate: new Date(corpusStartDate),
                endCalendar: dtFilterTools.createdBefore,
                onChange: function (date) {
                    defaultOptions.calendar.onChange.call(this, date);
                    search();
                }
            })
        );

        dtFilterTools.createdBefore.calendar(
            App.merge(true, defaultOptions.calendar, {
                initialDate: new Date(corpusEndDate),
                startCalendar: dtFilterTools.createdAfter,
                onChange: function (date) {
                    defaultOptions.calendar.onChange.call(this, date);
                    search();
                }
            })
        );

        dtFilter.find('.ui.dropdown').dropdown(
            App.merge(true, defaultOptions.dropdown, {
                onChange: function (value) {
                    defaultOptions.dropdown.onChange.call(this, value);
                    search();
                }
            })
        );

        dtFilterTools.errorTypes.find('input').on('change', function () {
            search();
        });

        dtFilterTools.errorTypes.find('.actions [data-action]').on('click', function () {
            var action = $(this).attr('data-action');
            switch (action) {
                case 'select-all':
                    dtFilterTools.errorTypes.find('input').prop('checked', true);
                    break;
                case 'deselect-all':
                    dtFilterTools.errorTypes.find('input').prop('checked', false);
                    break;
                default:
                    console.log('Undefined field option: ' + action);
                    return;
            }
            search();
        });

        dtFilterControls.on('click', function () {
            var action = $(this).attr('data-action');
            switch (action) {
                case 'reset':
                    dtFilterTools.createdAfter.find('.icon.delete').trigger('click');
                    dtFilterTools.createdBefore.find('.icon.delete').trigger('click');
                    dtFilterTools.errorStatus.find('.icon.delete').trigger('click');
                    dtFilterTools.errorTypes.find('input').prop('checked', true);
                    search();
                    break;
                default:
                    console.log('Undefined filter control: ' + action);
            }
        });

        dtTable.find('th.status').popup({
            popup: $('#status-legend'),
            variation: 'tiny wide',
            inline: true
        });

        $('.ui .checkbox').checkbox();
    });
</script>

<?php $this->addSnippet(ob_get_clean()); ?>

<?php $this->include('layouts/default/begin.tpl.php'); ?>

<div class="row">
    <div class="twelve wide column">
        <div class="ui segment box">
            <div class="header">
                <h4 class="title">Contract Errors</h4>
            </div>
            <div class="content">
                <table id="contract-errors-table" class="ui compact celled table" width="100%">
                    <thead>
                    <tr>
                        <th class="center aligned" style="width: 3rem;">Contract</th>
                        <th class="center aligned" style="width: 6rem;">Date</th>
                        <th>Master</th>
                        <th>Error Type</th>
                        <th class="center aligned status" style="width: 3em; cursor: help;">Status</th>
                        <th>Reviewer</th>
                        <th>Last Updated</th>
                    </tr>
                    </thead>
                </table>
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
                <form id="contract-errors-table-filter" class="ui form">
                    <div class="field">
                        <label>Registered After</label>
                        <div class="ui calendar cal-created-after">
                            <div class="ui input left icon">
                                <i class="calendar alternate outline icon"></i>
                                <input type="text" placeholder="Any Date" />
                            </div>
                        </div>
                    </div>
                    <div class="field">
                        <label>Registered Before</label>
                        <div class="ui calendar cal-created-before">
                            <div class="ui input left icon">
                                <i class="calendar alternate outline icon"></i>
                                <input type="text" placeholder="Any Date" />
                            </div>
                        </div>
                    </div>
                    <div class="field">
                        <label>Error Status</label>
                        <div class="ui selection fluid dropdown dl-error-status">
                            <i class="dropdown icon"></i>
                            <div class="default text">Any</div>
                            <div class="menu">
                                <div class="item" data-value="unreviewed">Unreviewed</div>
                                <div class="item" data-value="reviewed">Reviewed</div>
                                <div class="item" data-value="corrected">Corrected</div>
                            </div>
                        </div>
                    </div>
                    <div class="grouped fields cb-error-types">
                        <label>
                            Error Type
                            <span class="actions">
                                <i class="ui check square outline icon link" data-action="select-all"></i>
                                <i class="ui square outline icon link" data-action="deselect-all"></i>
                            </span>
                        </label>
                        <?php foreach ($this->errors as $error) : ?>
                            <div class="field">
                                <div class="ui checkbox">
                                    <input type="checkbox" tabindex="0" class="hidden" checked
                                           name="error-type" value="<?php echo $error['error_type_id']; ?>">
                                    <label><?php echo $error['error_type']; ?></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="status-legend" class="ui flowing popup">
    <div class="header">Error Status</div>
    <div class="content">
        <div class="ui list">
            <div class="item">
                <i class="ui circle outline icon link"></i>
                <div class="content">unreviewed</div>
            </div>
            <div class="item">
                <i class="ui check circle outline icon link"></i>
                <div class="content">reviewed</div>
            </div>
            <div class="item">
                <i class="ui check circle icon link"></i>
                <div class="content">corrected</div>
            </div>
        </div>
    </div>
</div>

<?php $this->include('layouts/default/end.tpl.php'); ?>
