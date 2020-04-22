<?php

function insertTextField(
    string $label,
    string $name,
    array $classNames = [],
    array $containerClassNames = []
) {
    $classList = '';
    if (!empty($classNames)) {
        $classList .= ' ' . implode(' ', $classNames);
    }
    $containerClassList = 'field';
    if (!empty($containerClassNames)) {
        $containerClassList .= ' ' . implode(' ', $containerClassNames);
    }
    ?>
        <div class="<?php echo $containerClassList; ?>">
            <label><?php echo $label; ?></label>
            <input type="text" name="<?php echo $name; ?><?php if ($classList) { echo ' class="' . $classList . '"'; } ?>">
        </div>
    <?php
}

function insertDateField(
    string $label,
    string $name,
    array $classNames = [],
    array $containerClassNames = []
) {
    $classList = 'ui calendar';
    if (!empty($classNames)) {
        $classList .= ' ' . implode(' ', $classNames);
    }
    $containerClassList = 'field';
    if (!empty($containerClassNames)) {
        $containerClassList .= ' ' . implode(' ', $containerClassNames);
    }
    ?>
    <div class="<?php echo $containerClassList; ?>">
        <label><?php echo $label; ?></label>
        <div class="<?php echo $classList; ?>">
            <div class="ui input left icon">
                <i class="calendar alternate outline icon"></i>
                <input type="text" placeholder="Any Date" name="<?php echo $name; ?>">
            </div>
        </div>
    </div>
    <?php
}

function insertDropdownField(
    string $label,
    string $name,
    array $classNames = [],
    array $options = [],
    string $keyProperty = null,
    string $valueProperty = null,
    array $containerClassNames = []
) {
    $classList = 'ui fluid selection dropdown';
    if (!empty($classNames)) {
        $classList .= ' ' . implode(' ', $classNames);
    }
    $containerClassList = 'field';
    if (!empty($containerClassNames)) {
        $containerClassList .= ' ' . implode(' ', $containerClassNames);
    }
    ?>
    <div class="<?php echo $containerClassList; ?>">
        <label><?php echo $label; ?></label>
        <div class="<?php echo $classList; ?>">
            <input type="hidden" name="<?php echo $name; ?>">
            <i class="dropdown icon"></i>
            <div class="default text">Any</div>
            <div class="menu">
            <?php
                foreach($options as $key => $option) :
                    if (is_array($option)) {
                        if ($keyProperty && isset($option[$keyProperty])) {
                            $key = $option[$keyProperty];
                        }
                        if ($valueProperty && isset($option[$valueProperty])) {
                            $value = $option[$valueProperty];
                        } else {
                            continue;
                        }
                    } else {
                        $value = (string) $option;
                    }
            ?>
                <div class="item" data-value="<?php echo $key; ?>"><?php echo $value; ?></div>
            <?php
                endforeach;
            ?>
            </div>
        </div>
    </div>
    <?php
}

?>

<?php ob_start(); ?>
<script>
    $(document).ready(function() {

        App.storage.filters = {
            mentions: {}
        };

        var corpusStartDate = '1525-01-01',
            corpusEndDate = '1772-12-31',
            defaultOptions = {
                accordion: {
                    exclusive: false
                },
                dropdown: {
                    forceSelection: false,
                    saveRemoteData: false,
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

        function resetDropdown(dropdown) {
            dropdown.find('.icon.delete').trigger('click');
            dropdown.find('.menu').html('');
            dropdown.dropdown('refresh');
        }

        function createFilter(options, callbacks) {
            var id = App.getRandomUuid(),
                palette = $($('#tpl-filter-window').html()).attr('id', id),
                fields = $('#contracts-table-filter'),
                field = fields.find('.field[data-id="' + id + '"]'),
                filterSuggestionBlock = fields.find('.add-mention-filters.segment');
            palette.insertAfter($('body > div').last());
            options = App.merge(true, {
                selector: {
                    id: '#' + id
                },
                window: {
                    height: 480,
                    minHeight: 480,
                    width: 800,
                    minWidth: 650
                }
            }, options || {});

            palette = new App.FilterWindow(options);

            palette.node.find('.ui.accordion').accordion(defaultOptions.accordion);
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
                        delete App.storage.filters.mentions[id];
                        search();
                        if ($.isEmptyObject(App.storage.filters.mentions)) {
                            filterSuggestionBlock.show();
                            field.remove();
                            palette.destroy();
                        } else {
                            field.slideUp(250, function() {
                                $(this).remove();
                                palette.destroy();
                            });
                        }
                    });

                    field.attr('data-id', id);
                    field.find('label').text(palette.window.getTitle());

                    fields.append(field);

                    if (filterSuggestionBlock.is(':visible')) {
                        filterSuggestionBlock.hide();
                    }
                }

                if ($.isPlainObject(callbacks) && (typeof callbacks.onApply === 'function')) {
                    callbacks.onApply(form, formData, tableData);
                }

                for (var i = 0; i < tableData.length; i++) {
                    App.insertTableRow(tbody, tr, tableData[i]);
                }

                App.storage.filters.mentions[id] = formData;

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

        function addPersonFilter(title) {
            var options = {
                    title: title,
                    content: $('#tpl-fl-person-mention').html()
                },
                callbacks = {
                    onApply: function(form, formData, tableData) {
                        var valueList = [];
                        formData.type = 'grz:PersonMention';
                        if (formData.tag) {
                            valueList = [];
                            form.find('.dl-tag > .ui.label').each(function() {
                                valueList.push($(this).text());
                            });
                            tableData.push({
                                property: 'Role',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.gender) {
                            tableData.push({
                                property: 'Gender',
                                value: form.find('.dl-gender').dropdown('get text')
                            });
                        }
                        if (formData.age) {
                            valueList = form.find('.dl-age').dropdown('get value').split('|');
                            tableData.push({
                                property: 'Age',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.full_name) {
                            valueList = form.find('.dl-full-name').dropdown('get value').split('|');
                            tableData.push({
                                property: 'Name',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.named_entity) {
                            tableData.push({
                                property: 'Entity',
                                value: form.find('.dl-named-entity').dropdown('get text')
                            });
                        }
                        if (formData.has_details) {
                            tableData.push({
                                property: 'With Extra Info',
                                value: form.find('.dl-has-details').dropdown('get text')
                            });
                        }
                        if (formData.details) {
                            tableData.push({
                                property: 'Extra Info',
                                value: form.find('[name=details]').val()
                            });
                        }
                        if (formData.profession_standard_form) {
                            valueList = form.find('.dl-profession-standard-form').dropdown('get value').split('|');
                            tableData.push({
                                property: 'Profession - Standard Form',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.profession_occupation) {
                            valueList = form.find('.dl-profession-occupation').dropdown('get value').split('|');
                            tableData.push({
                                property: 'Profession - Occupation',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.profession_category) {
                            tableData.push({
                                property: 'Profession - Category',
                                value: form.find('.dl-profession-category').dropdown('get text')
                            });
                        }
                        if (formData.profession_subcategory) {
                            tableData.push({
                                property: 'Profession - Subcategory',
                                value: form.find('.dl-profession-subcategory').dropdown('get text')
                            });
                        }
                        if (formData.profession_material) {
                            valueList = form.find('.dl-profession-material').dropdown('get value').split('|');
                            tableData.push({
                                property: 'Profession - Material',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.profession_product) {
                            valueList = form.find('.dl-profession-product').dropdown('get value').split('|');
                            tableData.push({
                                property: 'Profession - Product',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.geo_origin_standard_form) {
                            valueList = form.find('.dl-geo-origin-standard-form').dropdown('get value').split('|');
                            tableData.push({
                                property: 'Origin - Hist. Name',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.geo_origin_name) {
                            valueList = form.find('.dl-geo-origin-name').dropdown('get value').split('|');
                            tableData.push({
                                property: 'Origin - Contemp. Name',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.geo_origin_sestiere) {
                            tableData.push({
                                property: 'Origin - Sestiere',
                                value: form.find('.dl-geo-origin-sestiere').dropdown('get text')
                            });
                        }
                        if (formData.geo_origin_parish) {
                            tableData.push({
                                property: 'Origin - Parish',
                                value: form.find('.dl-geo-origin-parish').dropdown('get text')
                            });
                        }
                        if (formData.geo_origin_province) {
                            valueList = form.find('.dl-geo-origin-province').dropdown('get value').split('|');
                            tableData.push({
                                property: 'Origin - Province',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.geo_origin_country) {
                            valueList = form.find('.dl-geo-origin-country').dropdown('get value').split('|');
                            tableData.push({
                                property: 'Origin - Country',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                    }
                },
                palette = createFilter(options, callbacks);
            palette.node.find('.dl-age').dropdown(App.merge(true, defaultOptions.dropdown, {
                allowAdditions: true,
                hideAdditions: false,
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'person-mention/get-value-list?id=ages&pattern={query}'); ?>'
                }
            }));
            palette.node.find('.dl-full-name').dropdown(App.merge(true, defaultOptions.dropdown, {
                allowAdditions: true,
                hideAdditions: false,
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'person-mention/get-value-list?id=names&pattern={query}'); ?>'
                }
            }));
            palette.node.find('.dl-named-entity').dropdown(App.merge(true, defaultOptions.dropdown, {
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
            palette.node.find('.dl-profession-standard-form').dropdown(App.merge(true, defaultOptions.dropdown, {
                allowAdditions: true,
                hideAdditions: false,
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'person-mention/get-value-list?id=profession_standard_forms&pattern={query}'); ?>'
                }
            }));
            palette.node.find('.dl-profession-occupation').dropdown(App.merge(true, defaultOptions.dropdown, {
                allowAdditions: true,
                hideAdditions: false,
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'profession/get-value-list?id=occupations&pattern={query}'); ?>'
                }
            }));
            palette.node.find('.dl-profession-category').dropdown(App.merge(true, defaultOptions.dropdown, {
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'profession-category/get-value-list'); ?>'
                },
                filterRemoteData: true,
                fullTextSearch: true,
                onChange: function (value)  {
                    defaultOptions.dropdown.onChange.call(this, value);
                    resetDropdown(palette.node.find('.dl-profession-subcategory'));
                }
            }));
            palette.node.find('.dl-profession-subcategory').dropdown(App.merge(true, defaultOptions.dropdown, {
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'profession-category/get-value-list?parent_id={parent_id}'); ?>',
                    beforeSend: function(settings) {
                        var dl = palette.node.find('.dl-profession-category'),
                            parent_id = dl.dropdown('get value');
                        if (!parent_id) {
                            return false;
                        }
                        settings.urlData.parent_id = parent_id;
                        return settings;
                    }
                },
                filterRemoteData: true,
                fullTextSearch: true
            }));
            palette.node.find('.dl-profession-material').dropdown(App.merge(true, defaultOptions.dropdown, {
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'profession/get-value-list?id=materials&pattern={query}'); ?>'
                }
            }));
            palette.node.find('.dl-profession-product').dropdown(App.merge(true, defaultOptions.dropdown, {
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'profession/get-value-list?id=products&pattern={query}'); ?>'
                }
            }));
            palette.node.find('.dl-geo-origin-standard-form').dropdown(App.merge(true, defaultOptions.dropdown, {
                allowAdditions: true,
                hideAdditions: false,
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'person-mention/get-value-list?id=geo_origin_standard_forms&pattern={query}'); ?>'
                }
            }));
            palette.node.find('.dl-geo-origin-name').dropdown(App.merge(true, defaultOptions.dropdown, {
                allowAdditions: true,
                hideAdditions: false,
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'location/get-value-list?id=names&pattern={query}'); ?>'
                }
            }));
            palette.node.find('.dl-geo-origin-sestiere').dropdown('setting', 'onChange', function(value) {
                defaultOptions.dropdown.onChange.call(this, value);
                resetDropdown(palette.node.find('.dl-geo-origin-parish'));
            });
            palette.node.find('.dl-geo-origin-parish').dropdown(App.merge(true, defaultOptions.dropdown, {
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'parish/get-value-list?sestiere={sestiere}'); ?>',
                    beforeSend: function(settings) {
                        var dl = palette.node.find('.dl-geo-origin-sestiere'),
                            sestiere = dl.dropdown('get value');
                        if (!sestiere) {
                            return false;
                        }
                        settings.urlData.sestiere = sestiere;
                        return settings;
                    }
                },
                filterRemoteData: true,
                fullTextSearch: true
            }));
            palette.node.find('.dl-geo-origin-province').dropdown(App.merge(true, defaultOptions.dropdown, {
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'location/get-value-list?id=provinces&pattern={query}'); ?>'
                }
            }));
            palette.node.find('.dl-geo-origin-country').dropdown(App.merge(true, defaultOptions.dropdown, {
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'location/get-value-list?id=countries&pattern={query}'); ?>'
                }
            }));
        }

        function addWorkshopFilter(title) {
            var options = {
                    title: title,
                    content: $('#tpl-fl-workshop-mention').html()
                },
                callbacks = {
                    onApply: function(form, formData, tableData) {
                        var valueList = [];
                        formData.type = 'grz:WorkshopMention';
                        if (formData.insignia) {
                            valueList = form.find('.dl-insignia').dropdown('get value').split('|');
                            tableData.push({
                                property: 'Insignia',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.site) {
                            valueList = form.find('.dl-site').dropdown('get value').split('|');
                            tableData.push({
                                property: 'Site',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.sestiere) {
                            tableData.push({
                                property: 'Sestiere',
                                value: form.find('.dl-sestiere').dropdown('get text')
                            });
                        }
                        if (formData.parish) {
                            tableData.push({
                                property: 'Parish',
                                value: form.find('.dl-parish').dropdown('get text')
                            });
                        }
                    }
                },
                palette = createFilter(options, callbacks);
            palette.node.find('.dl-insignia').dropdown(App.merge(true, defaultOptions.dropdown, {
                allowAdditions: true,
                hideAdditions: false,
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'person-mention/get-value-list?id=workshop_insignias&pattern={query}'); ?>'
                }
            }));
            palette.node.find('.dl-site').dropdown(App.merge(true, defaultOptions.dropdown, {
                allowAdditions: true,
                hideAdditions: false,
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'person-mention/get-value-list?id=workshop_sites&pattern={query}'); ?>'
                }
            }));
            palette.node.find('.dl-sestiere').dropdown('setting', 'onChange', function(value) {
                defaultOptions.dropdown.onChange.call(this, value);
                resetDropdown(palette.node.find('.dl-parish'));
            });
            palette.node.find('.dl-parish').dropdown(App.merge(true, defaultOptions.dropdown, {
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'parish/get-value-list?sestiere={sestiere}'); ?>',
                    beforeSend: function(settings) {
                        var dl = palette.node.find('.dl-sestiere'),
                            sestiere = dl.dropdown('get value');
                        if (!sestiere) {
                            return false;
                        }
                        settings.urlData.sestiere = sestiere;
                        return settings;
                    }
                },
                filterRemoteData: true,
                fullTextSearch: true
            }));
        }

        function addEventFilter(title) {
            var options = {
                    title: title,
                    content: $('#tpl-fl-event-mention').html()
                },
                callbacks = {
                    onApply: function(form, formData, tableData) {
                        var valueList = [];
                        formData.type = 'grz:EventMention';
                        if (formData.tag) {
                            valueList = [];
                            form.find('.dl-tag > .ui.label').each(function() {
                                valueList.push($(this).text());
                            });
                            tableData.push({
                                property: 'Type',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.after) {
                            tableData.push({
                                property: 'After',
                                value: form.find('[name=after]').val()
                            });
                        }
                        if (formData.before) {
                            tableData.push({
                                property: 'Before',
                                value: form.find('[name=before]').val()
                            });
                        }
                        if (formData.duration_years) {
                            tableData.push({
                                property: 'Duration - Years',
                                value: form.find('[name=duration_years]').val()
                            });
                        }
                        if (formData.duration_months) {
                            tableData.push({
                                property: 'Duration - Months',
                                value: form.find('[name=duration_months]').val()
                            });
                        }
                        if (formData.duration_days) {
                            tableData.push({
                                property: 'Duration - Days',
                                value: form.find('[name=duration_days]').val()
                            });
                        }
                        if (formData.has_details) {
                            tableData.push({
                                property: 'With Extra Info',
                                value: form.find('.dl-has-details').dropdown('get text')
                            });
                        }
                        if (formData.details) {
                            tableData.push({
                                property: 'Extra Info',
                                value: form.find('[name=details]').val()
                            });
                        }
                    }
                },
                palette = createFilter(options, callbacks),
                rangeStart = palette.node.find('.cal-after'),
                rangeEnd = palette.node.find('.cal-before');
            rangeStart.calendar(App.merge(true, defaultOptions.calendar, {
                initialDate: new Date(corpusStartDate),
                endCalendar: rangeEnd
            }));
            rangeEnd.calendar(App.merge(true, defaultOptions.calendar, {
                initialDate: new Date(corpusEndDate),
                startCalendar: rangeStart
            }));
        }

        function addHostingConditionFilter(title) {
            var options = {
                    title: title,
                    content: $('#tpl-fl-hosting-condition-mention').html()
                },
                callbacks = {
                    onApply: function(form, formData, tableData) {
                        var valueList = [];
                        formData.type = 'grz:HostingConditionMention';
                        if (formData.tag) {
                            valueList = [];
                            form.find('.dl-tag > .ui.label').each(function() {
                                valueList.push($(this).text());
                            });
                            tableData.push({
                                property: 'Type',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.paid_in_goods) {
                            tableData.push({
                                property: 'Paid in Goods',
                                value: form.find('.dl-paid-in-goods').dropdown('get text')
                            });
                        }
                        if (formData.application_rule) {
                            tableData.push({
                                property: 'Application Rule',
                                value: form.find('.dl-application-rule').dropdown('get text')
                            });
                        }
                        if (formData.clothing_type) {
                            valueList = [];
                            form.find('.dl-clothing-type > .ui.label').each(function() {
                                valueList.push($(this).text());
                            });
                            tableData.push({
                                property: 'Type of Clothing',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.paid_by) {
                            valueList = [];
                            form.find('.dl-payer > .ui.label').each(function() {
                                valueList.push($(this).text());
                            });
                            tableData.push({
                                property: 'Paid by',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.periodization) {
                            valueList = [];
                            form.find('.dl-periodization > .ui.label').each(function() {
                                valueList.push($(this).text());
                            });
                            tableData.push({
                                property: 'Periodization',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.period) {
                            tableData.push({
                                property: 'Specific Period',
                                value: form.find('[name=period]').val()
                            });
                        }
                        if (formData.has_details) {
                            tableData.push({
                                property: 'With Extra Info',
                                value: form.find('.dl-has-details').dropdown('get text')
                            });
                        }
                        if (formData.details) {
                            tableData.push({
                                property: 'Extra Info',
                                value: form.find('[name=details]').val()
                            });
                        }
                    }
                },
                palette = createFilter(options, callbacks);
            palette.node.find('.dl-clothing-type').dropdown(App.merge(true, defaultOptions.dropdown, {
                allowAdditions: true,
                hideAdditions: false,
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'hosting-condition-mention/get-value-list?id=clothing_types&pattern={query}'); ?>'
                }
            }));
        }

        function addFinancialConditionFilter(title) {
            var options = {
                    title: title,
                    content: $('#tpl-fl-financial-condition-mention').html()
                },
                callbacks = {
                    onApply: function(form, formData, tableData) {
                        var valueList = [];
                        formData.type = 'grz:FinancialConditionMention';
                        if (formData.tag) {
                            valueList = [];
                            form.find('.dl-tag > .ui.label').each(function() {
                                valueList.push($(this).text());
                            });
                            tableData.push({
                                property: 'Type',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.paid_in_goods) {
                            tableData.push({
                                property: 'Paid in Goods',
                                value: form.find('.dl-paid-in-goods').dropdown('get text')
                            });
                        }
                        if (formData.currency_unit) {
                            valueList = [];
                            form.find('.dl-currency-unit > .ui.label').each(function() {
                                valueList.push($(this).text());
                            });
                            tableData.push({
                                property: 'Currency Unit',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.partial_amount) {
                            tableData.push({
                                property: 'Partial Amount',
                                value: form.find('[name=partial_amount]').val()
                            });
                        }
                        if (formData.period) {
                            tableData.push({
                                property: 'Specific Period',
                                value: form.find('[name=period]').val()
                            });
                        }
                        if (formData.paid_by) {
                            valueList = [];
                            form.find('.dl-payer > .ui.label').each(function() {
                                valueList.push($(this).text());
                            });
                            tableData.push({
                                property: 'Paid by',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.periodization) {
                            valueList = [];
                            form.find('.dl-periodization > .ui.label').each(function() {
                                valueList.push($(this).text());
                            });
                            tableData.push({
                                property: 'Periodization',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.money_information) {
                            valueList = [];
                            form.find('.dl-money-information > .ui.label').each(function() {
                                valueList.push($(this).text());
                            });
                            tableData.push({
                                property: 'Money Information',
                                value: App.toHtmlList(valueList, false, 'ui list', 'item')
                            });
                        }
                        if (formData.total_amount) {
                            tableData.push({
                                property: 'Total Amount',
                                value: form.find('[name=total_amount]').val()
                            });
                        }
                        if (formData.has_details) {
                            tableData.push({
                                property: 'With Extra Info',
                                value: form.find('.dl-has-details').dropdown('get text')
                            });
                        }
                        if (formData.details) {
                            tableData.push({
                                property: 'Extra Info',
                                value: form.find('[name=details]').val()
                            });
                        }
                    }
                },
                palette = createFilter(options, callbacks);
            palette.node.find('.dl-money-information').dropdown(App.merge(true, defaultOptions.dropdown, {
                allowAdditions: true,
                hideAdditions: false,
                apiSettings: {
                    url: '<?php echo $this->app->getUrl('module', 'financial-condition-mention/get-value-list?id=money_information&pattern={query}'); ?>'
                }
            }));
        }

        function stripDetails(data) {
            var div = jQuery('<div>' + data + '</div>'),
                mentions = [];
            div.find('span.person-mention').each(function() {
                mentions.push($(this).text());
            });
            return mentions.join('; ');
        }

        function addFilter(type, title) {
            switch (type) {
                case 'person-mention':
                    addPersonFilter(title);
                    break;
                case 'workshop-mention':
                    addWorkshopFilter(title);
                    break;
                case 'event-mention':
                    addEventFilter(title);
                    break;
                case 'hosting-condition-mention':
                    addHostingConditionFilter(title);
                    break;
                case 'financial-condition-mention':
                    addFinancialConditionFilter(title);
                    break;
                default:
                    return;
            }
        }

        var dtFilter = $('#contracts-table-filter'),
            dtFilterBox = dtFilter.closest('.segment.box'),
            dtFilterControls = dtFilterBox.find('.header .tools [data-action]'),
            dtFilterTools = {
                createdAfter: dtFilter.find('.cal-created-after'),
                createdBefore: dtFilter.find('.cal-created-before'),
                onMultiplePages: dtFilter.find('.dl-on-multiple-pages'),
                hasMargin: dtFilter.find('.dl-has-margin'),
                hasDetails: dtFilter.find('.dl-has-details'),
                details: dtFilter.find('[name="details"]')
            },
            dtContracts = new App.DataTable({
                id: 'contracts-table',
                ajax: {
                    url: '<?php echo $this->app->getUrl('controller', 'get'); ?>',
                    type: 'POST',
                    data: function (data) {
                        var mentions = [],
                            filters;
                        $('#contracts-table-filter .field[data-id]').each(function() {
                            var rule = App.getPropertyValue(App.storage, 'filters.mentions.' + $(this).attr('data-id'));
                            if (rule) {
                                mentions.push(rule);
                            }
                        });
                        filters = {
                            created_after: App.formatDate(dtFilterTools.createdAfter.calendar('get date')),
                            created_before: App.formatDate(dtFilterTools.createdBefore.calendar('get date')),
                            on_multiple_pages: dtFilterTools.onMultiplePages.dropdown('get value'),
                            has_details: dtFilterTools.hasDetails.dropdown('get value'),
                            details: dtFilterTools.details.val(),
                            mentions: JSON.stringify(mentions)
                        };
                        data.filters = filters;
                    }
                },
                title: 'Contracts',
                filename: 'contracts',
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
                            width: '3rem',
                            render: function (data, type, row) {
                                var contractUrl = '<?php echo $this->app->getUrl('controller', 'view'); ?>/' + data,
                                    pageUrl = '<?php echo $this->app->getUrl('module', 'page/view'); ?>/'
                                        + row.manifest_id + '/' + row.canvas_code + '?highlight=' + row.target_id,
                                    links = '<a href="' + contractUrl + '" target="_blank"><i class="ui newspaper outline icon"></i></a>'
                                        + ' <a href="' + pageUrl + '" target="_blank"><i class="ui edit outline icon"></i></a>';
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
                            orderable: false,
                            render: function (data, type) {
                                data = data || '&mdash;';
                                return (type === 'export') ? stripDetails(data) : data;
                            }
                        },
                        {
                            data: 'apprentice',
                            orderable: false,
                            render: function (data, type) {
                                data = data || '&mdash;';
                                return (type === 'export') ? stripDetails(data) : data;
                            }
                        },
                        {
                            data: 'guarantor',
                            orderable: false,
                            visible: false,
                            render: function (data, type) {
                                data = data || '&mdash;';
                                return (type === 'export') ? stripDetails(data) : data;
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
            dtContracts.table.draw();
        });

        dtContracts.table.on('draw', function() {
            dtContracts.container.find('.popup-target').popup({
                variation: 'tiny wide',
                inline: true
            });
        });

        dtFilter.form();

        dtFilterTools.details.on('input', function () {
            search();
        });

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

        dtFilter.find('.add-mention-filters [data-filter]').on('click', function () {
            var filterType = $(this).attr('data-filter');
            dtFilterControls.filter('.item[data-filter="' + filterType + '"]').trigger('click');
        });

        dtFilterControls.on('click', function () {
            var action = $(this).attr('data-action');
            switch (action) {
                case 'reset':
                    dtFilterTools.createdAfter.find('.icon.delete').trigger('click');
                    dtFilterTools.createdBefore.find('.icon.delete').trigger('click');
                    dtFilterTools.onMultiplePages.find('.icon.delete').trigger('click');
                    dtFilterTools.hasMargin.find('.icon.delete').trigger('click');
                    dtFilterTools.hasDetails.find('.icon.delete').trigger('click');
                    dtFilter.form('reset');
                    dtFilter.find('.field[data-type="mention"]').each(function () {
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
                    <?php echo $this->text->get('app.contracts'); ?>
                </h4>
                <div class="tools">
                    <?php $this->include('components/data_table_menu.tpl.php'); ?>
                </div>
            </div>
            <div class="content">
                <table id="contracts-table" class="ui compact celled table" width="100%">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Links</th>
                            <th>Date</th>
                            <th>Master</th>
                            <th>Apprentice</th>
                            <th>Guarantor</th>
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
                                <i class="filter icon"></i> Add Mention Filter
                            </div>
                            <div class="divider"></div>
                            <div class="item" data-action="add-filter"
                                 data-filter="person-mention">Person</div>
                            <div class="item" data-action="add-filter"
                                 data-filter="workshop-mention">Workshop</div>
                            <div class="item" data-action="add-filter"
                                 data-filter="event-mention">Event</div>
                            <div class="item" data-action="add-filter"
                                 data-filter="hosting-condition-mention">Hosting Condition</div>
                            <div class="item" data-action="add-filter"
                                 data-filter="financial-condition-mention">Financial Condition</div>
                            <div class="divider"></div>
                            <div class="item" data-action="reset">
                                <i class="undo icon"></i> Reset
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content">
                <form id="contracts-table-filter" class="ui small form">
                    <?php insertDateField('Registered After', 'registered_after', ['cal-created-after']); ?>
                    <?php insertDateField('Registered Before', 'registered_before', ['cal-created-before']); ?>
                    <div class="ui styled accordion">
                        <div class="title">
                            <i class="dropdown icon"></i> Other Properties
                        </div>
                        <div class="content">
                            <?php insertDropdownField('On Multiple Pages', 'on_multiple_pages', ['dl-on-multiple-pages'],
                                ['yes' => 'Yes', 'no' => 'No']); ?>
                            <?php insertDropdownField('With Margin', 'has_margin', ['dl-has-margin'],
                                ['yes' => 'Yes', 'no' => 'No']); ?>
                            <?php insertDropdownField('With Extra Information', 'has_details', ['dl-has-details'],
                                ['yes' => 'Yes', 'no' => 'No']); ?>
                            <?php insertTextField('Extra Information', 'details'); ?>
                        </div>
                    </div>
                    <h4 class="ui small dividing header">
                        Mentions
                        <span class="tools add-mention-filters">
                            <i class="user icon link tooltipped" data-filter="person-mention"
                               data-content="Person" data-position="top left"></i>
                            <i class="wrench icon link tooltipped" data-filter="workshop-mention"
                               data-content="Workshop" data-position="top left"></i>
                            <i class="calendar icon link tooltipped" data-filter="event-mention"
                               data-content="Event" data-position="top center"></i>
                            <i class="home icon link tooltipped" data-filter="hosting-condition-mention"
                               data-content="Hosting Condition" data-position="top right"></i>
                            <i class="dollar sign icon link tooltipped" data-filter="financial-condition-mention"
                               data-content="Financial Condition" data-position="top right"></i>
                        </span>
                    </h4>
                    <div class="ui segment add-mention-filters">
                        <div class="ui top attached label">Add Mention Filter</div>
                        <div class="ui small bulleted link list">
                            <a class="item" data-filter="person-mention">Person</a>
                            <a class="item" data-filter="workshop-mention">Workshop</a>
                            <a class="item" data-filter="event-mention">Event</a>
                            <a class="item" data-filter="hosting-condition-mention">Hosting Condition</a>
                            <a class="item" data-filter="financial-condition-mention">Financial Condition</a>
                        </div>
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
    <div class="field" data-type="mention">
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
                <td data-column="property" style="width:40%;"></td>
                <td data-column="value"></td>
            </tr>
        </tbody>
    </table>
</template>

<template id="tpl-fl-person-mention">
    <form class="ui small form">
        <div class="equal width fields">
            <?php insertDropdownField('Role', 'tag', ['multiple', 'dl-tag'],
                $this->lists['tag']['person'], 'id', 'localized_label'); ?>
            <?php insertDropdownField('Name', 'full_name', ['search', 'multiple', 'dl-full-name']); ?>
        </div>
        <div class="fields">
            <?php insertDropdownField('Gender', 'gender', ['dl-gender'],
                $this->lists['gender'], 'qualified_name', 'localized_label', ['four', 'wide']); ?>
            <?php insertDropdownField('Age', 'age', ['search', 'multiple', 'dl-age'], [], null, null, ['four', 'wide']); ?>
            <?php insertDropdownField('Entity', 'named_entity', ['search', 'dl-named-entity'], [], null, null, ['eight', 'wide']); ?>
        </div>
        <div class="ui styled accordion">
            <div class="title">
                <i class="dropdown icon"></i> Profession
            </div>
            <div class="content">
                <div class="equal width fields">
                    <?php insertDropdownField('Standard Form', 'profession_standard_form',
                        ['search', 'multiple', 'dl-profession-standard-form']); ?>
                    <?php insertDropdownField('Category', 'profession_category',
                        ['search', 'dl-profession-category']); ?>
                </div>
                <div class="equal width fields">
                    <?php insertDropdownField('Occupation', 'profession_occupation',
                        ['search', 'multiple', 'dl-profession-occupation']); ?>
                    <?php insertDropdownField('Subcategory', 'profession_subcategory',
                        ['search', 'dl-profession-subcategory']); ?>
                </div>
                <div class="equal width fields">
                    <?php insertDropdownField('Material', 'profession_material',
                        ['search', 'multiple', 'dl-profession-material']); ?>
                    <?php insertDropdownField('Product', 'profession_product',
                        ['search', 'multiple', 'dl-profession-product']); ?>
                </div>
            </div>
            <div class="title">
                <i class="dropdown icon"></i> Place of Origin
            </div>
            <div class="content">
                <div class="equal width fields">
                    <?php insertDropdownField('Historical Name', 'geo_origin_standard_form',
                        ['search', 'multiple', 'dl-geo-origin-standard-form']); ?>
                    <?php insertDropdownField('Sestiere', 'geo_origin_sestiere', ['dl-geo-origin-sestiere'],
                        $this->lists['sestiere'], 'qualified_name', 'name'); ?>
                </div>
                <div class="equal width fields">
                    <?php insertDropdownField('Contemporary Name', 'geo_origin_name',
                        ['search', 'multiple', 'dl-geo-origin-name']); ?>
                    <?php insertDropdownField('Parish', 'geo_origin_parish',
                        ['search', 'dl-geo-origin-parish']); ?>
                </div>
                <div class="equal width fields">
                    <?php insertDropdownField('Province', 'geo_origin_province',
                        ['search', 'multiple', 'dl-geo-origin-province']); ?>
                    <?php insertDropdownField('Country', 'geo_origin_country',
                        ['search', 'multiple', 'dl-geo-origin-country']); ?>
                </div>
            </div>
            <div class="title">
                <i class="dropdown icon"></i> Other Properties
            </div>
            <div class="content">
                <div class="fields">
                    <?php insertDropdownField('With Extra Information', 'has_details', ['dl-has-details'],
                        ['yes' => 'Yes', 'no' => 'No'], null, null, ['four', 'wide']); ?>
                    <?php insertTextField('Extra Information', 'details', [], ['twelve', 'wide']); ?>
                </div>
            </div>
        </div>
    </form>
</template>

<template id="tpl-fl-workshop-mention">
    <form class="ui small form">
        <div class="equal width fields">
            <?php insertDropdownField('Insignia', 'insignia', ['search', 'multiple', 'dl-insignia']); ?>
            <?php insertDropdownField('Sestiere', 'sestiere', ['dl-sestiere'],
                $this->lists['sestiere'], 'qualified_name', 'name'); ?>
        </div>
        <div class="equal width fields">
            <?php insertDropdownField('Site', 'site', ['search', 'multiple', 'dl-site']); ?>
            <?php insertDropdownField('Parish', 'parish', ['search', 'dl-parish']); ?>
        </div>
    </form>
</template>

<template id="tpl-fl-event-mention">
    <form class="ui small form">
        <div class="equal width fields">
            <?php insertDropdownField('Type', 'tag', ['multiple', 'dl-tag'],
                $this->lists['tag']['event'], 'id', 'localized_label'); ?>
            <?php insertDateField('After', 'after', ['cal-after']); ?>
            <?php insertDateField('Before', 'before', ['cal-before']); ?>
        </div>
        <div class="ui styled accordion">
            <div class="active title">
                <i class="dropdown icon"></i> Duration
            </div>
            <div class="active content">
                <div class="equal width fields">
                    <?php insertTextField('Years', 'duration_years'); ?>
                    <?php insertTextField('Months', 'duration_months'); ?>
                    <?php insertTextField('Days', 'duration_days'); ?>
                </div>
            </div>
            <div class="title">
                <i class="dropdown icon"></i> Other Properties
            </div>
            <div class="content">
                <div class="fields">
                    <?php insertDropdownField('With Extra Information', 'has_details', ['dl-has-details'],
                        ['yes' => 'Yes', 'no' => 'No'], null, null, ['four', 'wide']); ?>
                    <?php insertTextField('Extra Information', 'details', [], ['twelve', 'wide']); ?>
                </div>
            </div>
        </div>
    </form>
</template>

<template id="tpl-fl-hosting-condition-mention">
    <form class="ui small form">
        <div class="equal width fields">
            <?php insertDropdownField('Type', 'tag', ['multiple', 'dl-tag'],
                $this->lists['tag']['hosting_condition'], 'id', 'localized_label'); ?>
            <?php insertDropdownField('Periodization', 'periodization', ['multiple', 'dl-periodization'],
                $this->lists['periodization'], 'qualified_name', 'localized_label'); ?>
        </div>
        <div class="fields">
            <?php insertDropdownField('Paid by', 'paid_by', ['multiple', 'dl-payer'],
                $this->lists['payer'], 'qualified_name', 'localized_label', ['four', 'wide']); ?>
            <?php insertDropdownField('Paid in Goods', 'paid_in_goods', ['dl-paid-in-goods'],
                ['yes' => 'Yes', 'no' => 'No'], null, null, ['four', 'wide']); ?>
            <?php insertTextField('Specific Period', 'period', [], ['eight', 'wide']); ?>
        </div>
        <div class="fields">
            <?php insertDropdownField('Application Rule', 'application_rule', ['dl-application-rule'],
                $this->lists['application_rule'], 'qualified_name', 'localized_label', ['four', 'wide']); ?>
            <?php insertDropdownField('Type of Clothing', 'clothing_type',
                ['search', 'multiple', 'dl-clothing-type'], [], null, null, ['twelve', 'wide']); ?>
        </div>
        <div class="ui styled accordion">
            <div class="title">
                <i class="dropdown icon"></i> Other Properties
            </div>
            <div class="content">
                <div class="fields">
                    <?php insertDropdownField('With Extra Information', 'has_details', ['dl-has-details'],
                        ['yes' => 'Yes', 'no' => 'No'], null, null, ['four', 'wide']); ?>
                    <?php insertTextField('Extra Information', 'details', [], ['twelve', 'wide']); ?>
                </div>
            </div>
        </div>
    </form>
</template>

<template id="tpl-fl-financial-condition-mention">
    <form class="ui small form">
        <div class="equal width fields">
            <?php insertDropdownField('Type', 'tag', ['multiple', 'dl-tag'],
                $this->lists['tag']['financial_condition'], 'id', 'localized_label'); ?>
            <?php insertDropdownField('Periodization', 'periodization', ['multiple', 'dl-periodization'],
                $this->lists['periodization'], 'qualified_name', 'localized_label'); ?>
        </div>
        <div class="fields">
            <?php insertDropdownField('Paid by', 'paid_by', ['multiple', 'dl-payer'],
                $this->lists['payer'], 'qualified_name', 'localized_label', ['four', 'wide']); ?>
            <?php insertDropdownField('Paid in Goods', 'paid_in_goods', ['dl-paid-in-goods'],
                ['yes' => 'Yes', 'no' => 'No'], null, null, ['four', 'wide']); ?>
            <?php insertTextField('Specific Period', 'period', [], ['eight', 'wide']); ?>
        </div>
        <div class="ui styled accordion">
            <div class="active title">
                <i class="dropdown icon"></i> Payment
            </div>
            <div class="active content">
                <div class="fields">
                    <?php insertTextField('Partial Amount', 'partial_amount', [], ['four', 'wide']); ?>
                    <?php insertTextField('Total Amount', 'total_amount', [], ['four', 'wide']); ?>
                    <?php insertDropdownField('Currency Unit', 'currency_unit', ['multiple', 'dl-currency-unit'],
                        $this->lists['currency_unit'], 'qualified_name', 'localized_label', ['eight', 'wide']); ?>
                </div>
                <div class="fields">
                    <?php insertDropdownField('Money Information', 'money_information',
                        ['search', 'multiple', 'dl-money-information'], [], null, null, ['eight', 'wide']); ?>
                </div>
            </div>
            <div class="title">
                <i class="dropdown icon"></i> Other Properties
            </div>
            <div class="content">
                <div class="fields">
                    <?php insertDropdownField('With Extra Information', 'has_details', ['dl-has-details'],
                        ['yes' => 'Yes', 'no' => 'No'], null, null, ['four', 'wide']); ?>
                    <?php insertTextField('Extra Information', 'details', [], ['twelve', 'wide']); ?>
                </div>
            </div>
        </div>
    </form>
</template>

<?php $this->include('layouts/default/end.tpl.php'); ?>
