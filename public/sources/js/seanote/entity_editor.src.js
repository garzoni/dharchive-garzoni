(function($, undefined) {
    'use strict';

    var module = {
            options: {
                classNames: {
                    component: 'seanote-entity-editor',
                    loading: 'loading'
                },
                fadeDuration: 200,
                window: {
                    minWidth: 200,
                    minHeight: 150
                }
            }
        },
        buttons = {
            addEntity: {
                className: 'add-entity',
                events: $.on('click', {call: '.addEntity', attach: 'event'})
            },
            save: {
                className: 'save',
                events: $.on('click', '.save')
            },
            dismiss: {
                className: 'close',
                events: $.on('click', '.close')
            }
        };

    function initDropdownFieldset(fieldset, rules, options, selectedValues) {
        var label = fieldset.find('label'),
            select = fieldset.find('select'),
            button = fieldset.find('.add-entity'),
            list, listQName, typeQName;
        if (rules) {
            if ($.isNonEmptyArray(rules.lists)) {
                list = rules.lists[0];
            } else if ($.isNonEmptyArray(rules.types)) {
                list = rules.types[0];
            }
            if (typeof list === 'string') {
                listQName = typeQName = list;
            } else if (jQuery.isPlainObject(list)) {
                listQName = list.qualifiedName;
                typeQName = list.itemType;
            }
        }
        if (!listQName || !typeQName) {
            fieldset.hide();
            return;
        }
        if (rules.allowMultiple) {
            select.attr('multiple', true);
        }
        initDropdownLabel(rules, label, select);
        initDropdownList(select, listQName, options, selectedValues);
        if (rules.allowAdditions) {
            button.attr('data-qname', typeQName);
            button.show();
        } else {
            button.removeAttr('data-qname');
            button.hide();
        }
        select.attr('data-qname', typeQName);
        fieldset.show();
    }

    function initDropdownLabel(rules, label) {
        if (rules.allowMultiple) {
            label.text(label.attr('data-multiple'));
        } else {
            label.text(label.attr('data-single'));
        }
    }

    function initDropdownList(select, listQName, options, selectedValues) {
        var dropdown, value;
        options.apiSettings.url = options.request.url + '?'
            + 'listQName=' + listQName + '&'
            + 'labelProperty=' + options.request.labelProperty + '&'
            + 'keyProperty=' + options.request.keyProperty + '&'
            + 'language=' + options.request.language + '&'
            + 'label={query}';
        options.onChange = function(value) {
            if (!value) {
                return;
            }
            addClearSelectionButton(jQuery(this).closest('.ui.dropdown'));
        };
        delete options.request;
        if (jQuery.isPlainObject(selectedValues)) {
            for (value in selectedValues) {
                if (selectedValues.hasOwnProperty(value)) {
                    select.append(
                        '<option value="' + value + '" selected="selected">'
                            + selectedValues[value] + '</option>'
                    );
                }
            }
        }
        dropdown = select.dropdown(options);
        addClearSelectionButton(dropdown);
    }

    function addClearSelectionButton(dropdown) {
        var select = dropdown.find('select'),
            icon = dropdown.find('.icon.dropdown');
        if (select.is('[multiple]') || !dropdown.dropdown('get value')) {
            return;
        }
        icon.removeClass('dropdown');
        icon.addClass('delete');
        icon.on('click', function(event) {
            dropdown.dropdown('clear');
            jQuery(this).removeClass('delete');
            jQuery(this).addClass('dropdown');
            event.stopImmediatePropagation();
        });
    }

    function getDropdownSelection(dropdown) {
        var values = dropdown.dropdown('get value'),
            isMultiSelect = dropdown.hasClass('multiple'),
            selection = {};
        if (!values) {
            values = [];
        } else if (!jQuery.isArray(values)) {
            values = [values];
        }
        jQuery.each(values, function(index, value) {
            var labelSelector = isMultiSelect ? '.ui.label' : '.item';
            if (value) {
                labelSelector += '[data-value="' + value + '"]';
                selection[value] = dropdown.find(labelSelector).text();
            }
        });
        return selection;
    }

    function getEntityValueList(entities, language) {
        var values = {};
        if (jQuery.isArray(entities)) {
            jQuery.each(entities, function(index, entity) {
                values[entity.id] = entity.properties.name
                    || entity.properties.labels.preferred[language];
            });
        }
        return values;
    }

    function displayErrors(errors) {
        var count = errors.length,
            message = '',
            i = 0;
        for (; i < count; i++) {
            message += errors[i].property + ': ' + errors[i].message + "\n";
        }
        alert(message);
    }

    /**
     * @class EntityEditor
     * @classdesc
     *
     * @memberof Seanote
     */
    $.EntityEditor = function(viewer, userOptions, entity) {
        Object.defineProperty(this, 'options', {
            value: $.merge(true, module.options, userOptions)
        });
        var editorNodes = viewer.getNode(this.options.classNames.component);
        this.node = editorNodes.first().clone().insertAfter(editorNodes.last());
        this.window = new $.Window(this.node, this.options.window);
        this.viewer = viewer;
        this.requests = [];
        this.callbacks = {
            save: null
        };
        this.entity = $.merge(
            {
                id: null,
                type: null,
                properties: {},
                annotations: {}
            },
            entity
        );
        this.defaults = {
            tags: {},
            entities: {}
        };
        this.editor = null;
        this.schema = null;
        this.initialize();
    };

    $.EntityEditor.prototype = {
        initialize: function() {
            this.viewer.addCommands(module.commands, this);
            $.bindEventsForEach(this.viewer, this, null, buttons);
        },

        getNode: function(o) {
            return $.getNode(o, this.node);
        },

        on: function(event, callback) {
            if (this.callbacks.hasOwnProperty(event)
                && (typeof callback === 'function')) {
                this.callbacks[event] = callback;
            }
        },

        open: function() {
            var url = this.viewer.options.sources.entityTypeUrl + '/get/schema',
                formData = {'entity_type': this.entity.type},
                self = this;
            jQuery.post(url, formData).done(function(schema) {
                self.schema = schema;
                self.viewer.publish('initEntityEditor:start', self);
                self.initPropertyForm();
                self.initAnnotationForm();
                self.editor.on('ready', function() {
                    self.viewer.publish('initEntityEditor:end', self);
                });
                self.show();
            });
        },

        close: function() {
            this.hide();
            this.node.remove();
        },

        show: function() {
            this.window.fadeIn(this.options.fadeDuration);
        },

        hide: function() {
            this.window.fadeOut(this.options.fadeDuration);
        },

        initPropertyForm: function() {
            var form = this.node.find('.entity-properties'),
                editorOptions = {
                    iconlib: 'semantic',
                    disable_collapse: false,
                    disable_edit_json: true,
                    disable_properties: true,
                    schema: this.schema,
                    choiceList: {
                        url: this.viewer.options.sources.valueListUrl + '/get'
                    }
                };
            this.editor = new JSONEditor(form[0], editorOptions);
            if (!jQuery.isEmptyObject(this.entity.properties)) {
                this.editor.setValue($.merge(
                    this.editor.getValue(),
                    this.entity.properties
                ));
            }
        },

        initAnnotationForm: function() {
            var form = this.node.find('.entity-annotations'),
                language = this.viewer.options.language,
                annotations = this.getAnnotations(),
                dropdownOptions = {
                    request: {
                        url: this.viewer.options.sources.valueListUrl + '/get',
                        labelProperty: 'name',
                        keyProperty: 'id',
                        language: this.viewer.options.language
                    },
                    apiSettings: {
                        cache: false
                    },
                    forceSelection: false,
                    saveRemoteData: false,
                    preserveHTML: false
                },
                tags,
                entities;
            if (this.schema.annotationRules) {
                tags = getEntityValueList(annotations.tags, language);
                entities = getEntityValueList(annotations.entities, language);
                if (jQuery.isEmptyObject(tags)) {
                    tags = this.getDefaults('tags');
                }
                if (jQuery.isEmptyObject(entities)) {
                    entities = this.getDefaults('entities');
                }
                initDropdownFieldset(
                    form.find('.semantic-tags'),
                    this.schema.annotationRules.semanticTags,
                    $.merge(dropdownOptions, {
                        request: {
                            labelProperty: 'preferred_label'
                        }
                    }),
                    tags
                );
                initDropdownFieldset(
                    form.find('.entities'),
                    this.schema.annotationRules.entities,
                    $.merge(dropdownOptions, {
                        minCharacters: 2
                    }),
                    entities
                );
                form.show();
            } else {
                form.hide();
            }
        },

        save: function() {
            var form = this.getNode('entity-annotations'),
                saveButton = this.getNode(buttons.save),
                properties = this.editor.getValue(),
                url = this.viewer.options.sources.idxEntities,
                self = this,
                params;
            $.compact(properties);
            params = {
                properties: $.serialize(properties),
                type: this.entity.type
            };
            if (this.entity.id) {
                url += '/update';
                params.id = this.entity.id;
            } else {
                url += '/create';
            }
            saveButton.addClass(this.options.classNames.loading);
            jQuery.post(url, params).done(function(entity) {
                if (entity.errors) {
                    displayErrors(entity.errors);
                } else if (entity.id) {
                    self.entity.id = entity.id;
                    self.entity.properties = properties;
                    self.saveAnnotations(form.find('.semantic-tags'), 'tag');
                    self.saveAnnotations(form.find('.entities'), 'identification');
                }
                saveButton.removeClass(self.options.classNames.loading);
                jQuery.when.apply(null, self.requests).done(function() {
                    if (self.callbacks.save) {
                        self.callbacks.save(self.entity);
                    }
                    self.requests = [];
                });
                if (entity.errors === undefined) {
                    self.close();
                }
            });
        },

        getAnnotations: function() {
            return this.viewer.annotator.getEntityAnnotations(this.entity);
        },

        saveAnnotations: function(fieldset, annotationType) {
            var dropdown = fieldset.find('.ui.dropdown'),
                typeQName = dropdown.find('select').attr('data-qname'),
                annotations = this.entity.annotations,
                newEntities = getDropdownSelection(dropdown),
                oldEntities = {},
                annotation, annotationId, entityId;
            if (!typeQName) {
                return;
            }
            for (annotationId in annotations) {
                if (!annotations.hasOwnProperty(annotationId)) {
                    continue;
                }
                annotation = annotations[annotationId];
                if (annotation.type === annotationType) {
                    oldEntities[annotation.body.id] = annotation.id;
                }
            }
            for (entityId in oldEntities) {
                if (oldEntities.hasOwnProperty(entityId)
                    && !newEntities.hasOwnProperty(entityId)) {
                    this.deleteAnnotation(oldEntities[entityId]);
                }
            }
            for (entityId in newEntities) {
                if (newEntities.hasOwnProperty(entityId)
                    && !oldEntities.hasOwnProperty(entityId)) {
                    this.addAnnotation(annotationType, {
                        id: entityId,
                        type: typeQName,
                        properties: {
                            name: newEntities[entityId]
                        }
                    });
                }
            }
        },

        addAnnotation: function(annotationType, bodyEntity, callback) {
            var url = this.viewer.options.sources.idxAnnotations + '/create',
                self = this;
            this.requests.push(jQuery.post(url, {
                type: annotationType,
                target_entity_id: this.entity.id,
                target_entity_type: this.entity.type,
                body_entity_id: bodyEntity.id,
                body_entity_type: bodyEntity.type
            }).done(function(annotation) {
                self.entity.annotations[annotation.id] = jQuery.extend(
                    annotation, {
                        type: annotationType,
                        body: bodyEntity
                    });
                console.log('Created #' + annotation.id);
                if (typeof callback === 'function') {
                    callback(annotation.id);
                }
            }));
        },

        deleteAnnotation: function(annotationId) {
            var url = this.viewer.options.sources.idxAnnotations + '/delete',
                self = this;
            this.requests.push(jQuery.post(url, {
                annotation_id: annotationId
            }).done(function(response) {
                if (response.status !== true) {
                    console.log('An error occurred while deleting the annotation.');
                    return;
                }
                delete self.entity.annotations[annotationId];
                console.log('Deleted #' + annotationId);
            }));
        },

        addEntity: function(event) {
            var self = this;
            return this.viewer.annotator.addEntity(event, function(entity) {
                var form = self.node.find('.entity-annotations'),
                    dropdown = form.find('.entities .ui.dropdown'),
                    menu = dropdown.find('.menu');
                if (!entity.id) {
                    return;
                }
                menu.append(
                    '<div class="item" data-value="' + entity.id + '">'
                        + entity.properties.name + '</div>'
                );
                dropdown.dropdown('refresh');
                dropdown.dropdown('set selected', entity.id);
            });
        },

        getWindowTitle: function() {
            return this.window.getTitle();
        },

        setWindowTitle: function(title) {
            this.window.setTitle(title);
        },

        getDefaults: function(type) {
            if (this.defaults.hasOwnProperty(type)) {
                return this.defaults[type];
            } else {
                return {};
            }
        },

        addDefault: function(type, id, value) {
            switch (type) {
                case 'tag':
                    type = 'tags';
                    break;
                case 'entity':
                    type = 'entities';
                    break;
                default:
                    return;
            }
            this.defaults[type][id] = value;
        },

        deleteDefault: function(type, id) {
            if (this.defaults[type].hasOwnProperty(id)) {
                delete this.defaults[type][id];
            }
        }
    }

}(Seanote));
