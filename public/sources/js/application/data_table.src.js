(function($, undefined) {
    'use strict';

    var module = {
        options: {
            id: 'data-table',
            ajax: '',
            title: 'Data Records',
            filename: 'data_records',
            dataExport: {
                columns: ':visible:not(.checkbox):not(.actions)',
                rows: {
                    selected: true
                },
                stripHtml: false,
                orthogonal: 'export'
            },
            containerSelector: '.segment.box',
            menuSelector: '.header .tools .table-menu',
            allowColumnSelection: true,
            allowRowSelection: true,
            enableRowActions: false,
            enableMenuActions: true,
            enableMenuKeys: true,
            table: {
                columns: [],
                lengthMenu: [
                    [15, 25, 50],
                    [15, 25, 50]
                ],
                order: [
                    [1, 'asc']
                ],
                stateSave: true,
                stateDuration: 0,
                fixedHeader: {
                    header: true,
                    headerOffset: 0
                },
                keys: {
                    columns: ':not(.checkbox, .actions)'
                },
                select: {
                    style: 'os',
                    selector: 'td:first-child'
                },
                language: {
                    paginate: {
                        next: '<i class="angle double right icon">',
                        previous: '<i class="angle double left icon">'
                    }
                },
                dom:
                    '<"ui stackable grid"' +
                        '<"row"<"ten wide column"i><"right aligned six wide column"f>>' +
                        '<"row dt-table"<"sixteen wide column"tr>>' +
                        '<"row"<"six wide column"l><"right aligned ten wide column"p>>' +
                    '>'
            },
            columns: {
                selection: {
                    data: null,
                    className: 'checkbox',
                    searchable: false,
                    orderable: false,
                    defaultContent: ''
                }
            },
            export: {
                formSelector: '.data-exporter',
                selectionProperty: 'id'
            }
        }
    };

    /**
     * @class DataTable
     * @classdesc
     *
     * @memberof App
     */
    $.DataTable = function(userOptions) {
        Object.defineProperty(this, 'options', {
            value: jQuery.extend(true, {}, module.options, userOptions)
        });
        this.node = jQuery('#' + this.options.id);
        this.container = this.node.closest(this.options.containerSelector);
        this.menu = this.container.find(this.options.menuSelector);
        this.table = {};
        this.initialize();
    };

    $.DataTable.prototype = {
        initialize: function() {
            if (!this.node.length) {
                throw new Error('DOM element not found');
            }
            this.initializeTable();
            if (!this.options.enableMenuKeys) {
                this.menu.find('.item .description').remove();
            }
            if (this.options.enableMenuActions) {
                this.addButtons();
            }
            if (this.options.allowColumnSelection) {
                this.addColumnSelector();
            }
            this.bindTableEvents();
        },

        initializeTable: function() {
            var options = this.options.table,
                self = this;
            options.ajax = this.options.ajax;
            if (this.options.allowRowSelection) {
                options.columns.unshift(this.options.columns.selection);
            }
            if (this.options.enableRowActions) {
                options.columns.push(this.options.columns.actions);
            }
            options.initComplete = function() {
                if (self.options.enableMenuActions) {
                    self.bindMenuActions(self.menu.find('.item[data-action]'));
                }
                if (self.options.enableRowActions) {
                    self.bindRowActions();
                }
            };
            this.table = this.node.DataTable(options);
        },

        addButtons: function() {
            var filename = this.options.filename + '_'
                + DateFormat.format.date(new Date(), 'yyyyMMdd_HHmmss'),
                buttons = [
                    {
                        extend: 'print',
                        title: this.options.title,
                        filename: filename,
                        exportOptions: jQuery.extend({}, this.options.dataExport, {orthogonal: 'print'}),
                        autoPrint: true,
                        customize: function (win) {
                            jQuery(win.document.body).css('background', 'white');
                            jQuery(win.document.body).find('table')
                                .addClass('compact');
                        },
                        enabled: false,
                        key: 'p'
                    },
                    {
                        extend: 'copy',
                        title: null,
                        messageTop: null,
                        messageBottom: null,
                        exportOptions: this.options.dataExport,
                        enabled: false,
                        key: 'c'
                    },
                    {
                        extend: 'excel',
                        title: this.options.title,
                        filename: filename,
                        exportOptions: this.options.dataExport,
                        enabled: false
                    },
                    {
                        extend: 'csv',
                        title: null,
                        filename: filename,
                        exportOptions: this.options.dataExport,
                        enabled: false
                    }
                ];

            if (!this.options.enableMenuKeys) {
                for (var i = 0; i < buttons.length; i++) {
                    if (buttons[i].hasOwnProperty('key')) {
                        delete buttons[i]['key'];
                    }
                }
            }

            new jQuery.fn.dataTable.Buttons(this.node, buttons);

            this.table.buttons().container().appendTo(
                this.table.table().container()
            );
        },

        addColumnSelector: function() {
            var columnNumbers = this.table.columns(':not(.checkbox):not(.actions)')[0],
                self = this;
            if (jQuery.isEmptyObject(columnNumbers)) {
                return;
            }
            this.buildColumnSelector(columnNumbers).insertBefore(this.menu);
            this.menu.siblings('.column-selector').dropdown({
                action: function(text, value) {
                    var column = self.table.column(value),
                        item = jQuery(this).find('[data-value="' + value + '"]'),
                        icon = item.find('.icon').attr('class', 'icon');
                    column.visible(!column.visible());
                    icon.addClass(column.visible() ? 'checkmark box' : 'square outline');
                }
            });
        },

        bindTableEvents: function() {
            if (this.options.allowRowSelection) {
                this.bindSelectionEvents();
            }
        },

        bindMenuActions: function(target) {
            var buttons = this.container.find('.content .dt-buttons'),
                self = this;
            target.on('click', function () {
                var action = jQuery(this).data('action'),
                    button;
                switch (action) {
                    case 'export-xlsx':
                        self.exportSelected('xlsx');
                        break;
                    case 'export-ods':
                        self.exportSelected('ods');
                        break;
                    case 'export-csv':
                        self.exportSelected('csv');
                        break;
                    case 'export-json':
                        self.exportSelected('json');
                        break;
                    case 'export-all-xlsx':
                        self.exportAll('xlsx');
                        break;
                    case 'export-all-ods':
                        self.exportAll('ods');
                        break;
                    case 'export-all-csv':
                        self.exportAll('csv');
                        break;
                    case 'export-all-json':
                        self.exportAll('json');
                        break;
                    default:
                        button = buttons.find('.buttons-' + action);
                        if (button.length) {
                            button.trigger('click');
                        }
                }
                self.menu.dropdown('hide');
                return false;
            });
        },

        bindRowActions: function() {},

        bindSelectionEvents: function() {
            var self = this;
            this.table.on('click', '> thead th.checkbox', function() {
                var cell = jQuery(this);
                if (cell.hasClass('selected')) {
                    self.table.rows().deselect();
                    cell.removeClass('selected');
                } else {
                    self.table.rows({search: 'applied'}).select();
                    cell.addClass('selected');
                }
            });
            this.table.on('select deselect', function() {
                var cell = self.node.find('> thead th.checkbox');
                if (self.table.rows({
                    selected: true
                }).count() !== self.table.rows().count()) {
                    cell.removeClass('selected');
                } else {
                    cell.addClass('selected');
                }
            });
            if (this.options.enableMenuActions) {
                this.table.on('select deselect', function () {
                    var items = self.menu.find('.togglable.item'),
                        buttons = self.table.buttons();
                    if (self.table.rows({
                        selected: true
                    }).count() > 0) {
                        items.removeClass('disabled');
                        buttons.enable();
                    } else {
                        items.addClass('disabled');
                        buttons.disable();
                    }
                });
            }
        },

        buildColumnSelector: function(columnNumbers) {
            if (!columnNumbers.length) {
                return;
            }
            var template = jQuery('#tpl-column-selector').html(),
                dropdown = jQuery(template),
                menu = dropdown.find('.menu'),
                itemTemplate = menu.find('.item').detach(),
                self = this;
            jQuery.each(columnNumbers, function(index, number) {
                var column = self.table.column(number),
                    title = jQuery(column.header()).html(),
                    item = itemTemplate.clone();
                item.attr('data-value', number);
                item.find('.icon').addClass(column.visible() ? 'checkmark box' : 'square outline');
                item.find('.title').text(title);
                menu.append(item);
            });
            return dropdown;
        },

        exportAll: function(format) {
            var form = this.container.find(this.options.export.formSelector);
            form.trigger('reset');
            form.find('.format').val(format);
            form.find('.selection').val('');
            form.find('.filters').val(btoa(JSON.stringify(this.table.ajax.params().filters)));
            form.submit();
        },

        exportSelected: function(format) {
            var form = this.container.find(this.options.export.formSelector),
                selectionProperty = this.options.export.selectionProperty,
                selection = [];

            form.trigger('reset');

            this.table.rows({selected: true}).every(function() {
                var selected = $.getPropertyValue(this.data(), selectionProperty);
                if (selected) {
                    selection.push(selected);
                }
            });

            if (selection.length > 0) {
                form.find('.format').val(format);
                form.find('.selection').val(selection.join(','));
                form.find('.filters').val('');
                form.submit();
            }
        }
    }

}(App));
