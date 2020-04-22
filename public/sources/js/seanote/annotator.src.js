(function($, undefined) {
    'use strict';

    var module = {
            options: {
                classNames: {
                    component: 'seanote-annotator',
                    loading: 'loading'
                },
                className: 'seanote-annotator',
                entityTypes: {
                    'segment': 'dhc:CanvasSegment',
                    'segmentGroup': 'dhc:CanvasSegmentGroup'
                },
                transcriptionLanguage: 'en',
                fadeDuration: 200,
                window: {
                    minWidth: 400,
                    minHeight: 300
                }
            },
            commands: {
                openAnnotator: 'open',
                closeAnnotator: 'close'
            }
        },
        buttons = {
            previousSegment: {
                className: 'previous-segment',
                events: $.on('click', '.loadObject', 'previous')
            },
            nextSegment: {
                className: 'next-segment',
                events: $.on('click', '.loadObject', 'next')
            },
            addTranscription: {
                className: 'add-transcription',
                events: $.on('click', {call: '.addTranscription', attach: 'event'})
            },
            addMention: {
                className: 'add-mention',
                events: $.on('click', {call: '.addMention', attach: 'event'})
            },
            dismiss: {
                className: 'close',
                events: $.on('click', ':closeAnnotator')
            }
        };

    /**
     * @class Annotator
     * @classdesc
     *
     * @memberof Seanote
     */
    $.Annotator = function(viewer, userOptions) {
        Object.defineProperty(this, 'options', {
            value: $.merge(true, module.options, userOptions)
        });
        this.node = viewer.getNode(this.options.classNames.component);
        this.window = new $.Window(this.node, this.options.window);
        this.viewer = viewer;
        this.entityTypes = {};
        this.target = {};
        this.annotations = {};
        this.initialize();
    };

    $.Annotator.prototype = {
        initialize: function() {
            var url = this.viewer.options.sources.entityTypeUrl + '/get',
                self = this;
            this.viewer.addCommands(module.commands, this);
            $.bindEventsForEach(this.viewer, this, null, buttons);
            jQuery.getJSON(url, function(entityTypes) {
                self.entityTypes = entityTypes;
                self.node.find('.tab-actions .menu .item').each(function() {
                    var typeQName = jQuery(this).attr('data-qname');
                    if (typeQName) {
                        jQuery(this).text(self.entityTypes[typeQName]['label']);
                    }
                });
            });
        },

        reset: function() {
            var transcriptionsTab = this.node.find('.tabular .item[data-tab="transcriptions"]'),
                mentionsTab = this.node.find('.tabular .item[data-tab="mentions"]');
            if (this.target.type === 'segmentGroup') {
                transcriptionsTab.hide();
                mentionsTab.trigger('click');
            } else {
                transcriptionsTab.show();
                transcriptionsTab.trigger('click');
                this.node.find('.selection-preview').html('<img src="'
                    + this.target.getImageUrl() + '" alt="" />');
            }
            jQuery('#transcription-list').find('.item').not('.template').remove();
            jQuery('#mention-list').find('.item').not('.template').remove();
        },

        open: function(obj) {
            var self = this;
            this.target = obj;
            this.annotations = {
                index: {},
                list: []
            };
            this.reset();
            this.loadAnnotations(function() {
                var annotation, i;
                for (i = 0; i < self.annotations.list.length; i++) {
                    annotation = self.annotations.list[i];
                    if (annotation.type === 'transcription') {
                        self.insertTranscription(annotation.id);
                    } else if (annotation.type === 'mention') {
                        self.insertMention(annotation.id);
                    }
                }
                self.show();
            });
        },

        close: function() {
            this.target = {};
            this.annotations = {};
            this.hide();
        },

        show: function() {
            this.window.fadeIn(this.options.fadeDuration);
        },

        hide: function() {
            this.window.fadeOut(this.options.fadeDuration);
        },

        loadObject: function(specifier) {
            var object;
            switch (specifier) {
                case 'previous':
                    object = this.viewer.canvas.getPreviousObject(this.target.id);
                    break;
                case 'next':
                    object = this.viewer.canvas.getNextObject(this.target.id);
                    break;
                default:
                    object = null;
            }
            if (object) {
                object.node.trigger('click');
                object.node.trigger('dblclick');
            }
        },

        loadAnnotations: function(callback) {
            var self = this;
            jQuery.post(this.viewer.options.sources.idxAnnotations + '/get', {
                'target_entity_id': this.target.id
            }).done(function(annotations) {
                var annotationId;
                if (!jQuery.isPlainObject(annotations)) {
                    console.log('Invalid data type. An object is expected.');
                    return;
                }
                for (annotationId in annotations) {
                    if (annotations.hasOwnProperty(annotationId)) {
                        self.annotations.list.push(annotations[annotationId]);
                    }
                }
                self.sortAnnotations();
                if (typeof callback === 'function') {
                    callback();
                }
            }).fail(function(jqXhr, textStatus, error) {
                console.log(error);
            });
        },

        sortAnnotations: function() {
            this.annotations.list.sort(function(a, b) {
                return new Date(a.created).getTime()
                    - new Date(b.created).getTime();
            });
            this.rebuildAnnotationIndex();
        },

        rebuildAnnotationIndex: function() {
            var annotation, i;
            this.annotations.index = {};
            for (i = 0; i < this.annotations.list.length; i++) {
                annotation = this.annotations.list[i];
                this.annotations.index[annotation.id] = i;
            }
        },

        getAnnotation: function(annotationId) {
            var i = this.annotations.index[annotationId];
            if (i !== undefined) {
                return this.annotations.list[i];
            } else {
                return undefined;
            }
        },

        addAnnotation: function(annotationType, bodyEntity, callback) {
            var self = this,
                targetEntityType = this.options.entityTypes[this.target.type];
            jQuery.post(self.viewer.options.sources.idxAnnotations + '/create', {
                type: annotationType,
                target_entity_id: this.target.id,
                target_entity_type: targetEntityType,
                body_entity_id: bodyEntity.id,
                body_entity_type: bodyEntity.type
            }).done(function(annotation) {
                self.annotations.index[annotation.id] =
                    self.annotations.list.push(jQuery.extend(annotation, {
                        type: annotationType,
                        body: bodyEntity
                    })) - 1;
                if (self.target.type === 'segment') {
                    self.target.node.addClass('annotated');
                } else if (self.target.type === 'segmentGroup') {
                    jQuery.each(self.target.members, function(i, segmentId) {
                        self.target.canvas.getObjectById(segmentId, 'segment')
                            .node.addClass('annotated-group');
                    });
                }
                if (typeof callback === 'function') {
                    callback(annotation.id);
                }
            });
        },

        deleteAnnotation: function(annotationId) {
            var self = this;
            jQuery.post(self.viewer.options.sources.idxAnnotations + '/delete', {
                annotation_id: annotationId,
                cascade: 'shallow'
            }).done(function(response) {
                var annotation = self.node.find('.item[data-annotation-id="'
                        + annotationId + '"]'),
                    i = self.annotations.index[annotationId];
                if (response.status !== true) {
                    console.log('An error occurred while deleting the annotation.');
                    return;
                }
                if (i !== undefined) {
                    self.annotations.list.splice(i, 0);
                    self.rebuildAnnotationIndex();
                }
                annotation.slideUp('slow', function() {
                    annotation.remove();
                });
            });
        },

        addEntity: function(event, callback) {
            var entityTypeQName = event.target.dataset.qname,
                entityTypeLabel = this.entityTypes[entityTypeQName]['label'],
                entityEditor = new $.EntityEditor(
                    this.viewer, {}, {type: entityTypeQName}
                );
            if (entityTypeLabel) {
                entityEditor.setWindowTitle(entityTypeLabel);
            }
            entityEditor.on('save', callback);
            entityEditor.open();
            return entityEditor;
        },

        editEntity: function(entity, callback) {
            var entityTypeLabel = this.entityTypes[entity.type]['label'],
                entityEditor = new $.EntityEditor(this.viewer, {}, entity);
            if (entityTypeLabel) {
                entityEditor.setWindowTitle(entityTypeLabel);
            }
            entityEditor.on('save', callback);
            entityEditor.open();
            return entityEditor;
        },

        addTranscription: function(event) {
            var self = this;
            return this.addEntity(event, function(entity) {
                if (!entity.id) {
                    return;
                }
                self.addAnnotation('transcription', entity, function(annotationId) {
                    self.insertTranscription(annotationId);
                });
            });
        },

        addMention: function(event) {
            var self = this;
            var entityEditor = this.addEntity(event, function(entity) {
                if (!entity.id) {
                    return;
                }
                self.addAnnotation('mention', entity, function(annotationId) {
                    self.insertMention(annotationId);
                });
            });
            entityEditor.setWindowTitle(
                entityEditor.getWindowTitle() + ' (Mention)'
            );
            return entityEditor;
        },

        insertTranscription: function(annotationId) {
            var list = jQuery('#transcription-list'),
                item = list.find('.item[data-annotation-id="' + annotationId + '"]'),
                updateExisting = Boolean(item.length),
                annotation = this.getAnnotation(annotationId),
                entity = annotation.body,
                title = 'Transcription';
            if (!updateExisting) {
                item = list.find('.template').clone();
                item.attr('data-annotation-id', annotationId);
                item.removeClass('template');
            }
            item.find('.header .title').text(title);
            item.find('.description .content').text(
                entity.properties.transcript.content
            );
            if (!updateExisting) {
                item.appendTo(list);
                this.bindAnnotationEvents(item);
            }
        },

        insertMention: function(annotationId) {
            var list = jQuery('#mention-list'),
                item = list.find('.item[data-annotation-id="' + annotationId + '"]'),
                updateExisting = Boolean(item.length),
                annotation = this.getAnnotation(annotationId),
                entity = annotation.body,
                entityAnnotations = {},
                title = 'Mention',
                self = this,
                tagList, entityList;
            if (!updateExisting) {
                item = list.find('.template').clone();
                item.attr('data-annotation-id', annotationId);
                item.removeClass('template');
            }
            if (entity.properties.name) {
                title = (entity.properties.name.forename || '') + ' '
                    + (entity.properties.name.surname || '');
            }
            item.find('.header').attr('data-mention-id', entity.id);
            item.find('.header .title').text(title);
            item.find('.description .mention-type').text(
                this.entityTypes[entity.type]['label']
            );
            tagList = item.find('.description .tag-list');
            entityList = item.find('.description .entity-list');
            tagList.empty();
            entityList.empty();
            if (entity.annotations) {
                entityAnnotations = this.getEntityAnnotations(entity);
                if (entityAnnotations.tags.length > 0) {
                    jQuery.each(entityAnnotations.tags, function(i, tag) {
                        var label = tag.properties.name;
                        if (!label) {
                            label = tag.properties.labels
                                .preferred[self.viewer.options.language];
                        }
                        tagList.append(' &bull; ' + label);
                    });
                } else {
                    tagList.hide();
                }
                if (entityAnnotations.entities.length > 0) {
                    jQuery.each(entityAnnotations.entities, function(i, entity) {
                        var entityItem = jQuery('<a>', {href: "#"});
                        entityItem.text(entity.properties.name);
                        if (entityList.html().length > 0) {
                            entityList.append(' &bull; ');
                        }
                        entityList.append(entityItem);
                        entityItem.on('click', function(event) {
                            event.preventDefault();
                            self.editEntity(entity, function(e) {
                                if (e.id) {
                                    entity.properties = e.properties;
                                }
                            });
                        });
                    });
                } else {
                    entityList.hide();
                }
            } else {
                tagList.hide();
                entityList.hide();
            }
            if (!updateExisting) {
                item.appendTo(list);
                this.bindAnnotationEvents(item);
            }
        },

        getEntityAnnotations: function(entity) {
            var annotations = {
                    tags: [],
                    entities: []
                },
                annotation = {},
                annotationId;
            if (entity.annotations) {
                for (annotationId in entity.annotations) {
                    if (!entity.annotations.hasOwnProperty(annotationId)) {
                        continue;
                    }
                    annotation = entity.annotations[annotationId];
                    if (annotation.type === 'tag') {
                        annotations.tags.push(annotation.body);
                    } else if (annotation.type === 'identification') {
                        annotations.entities.push(annotation.body);
                    }
                }
            }
            return annotations;
        },

        bindAnnotationEvents: function(annotItem) {
            var self = this,
                annotationId = annotItem.attr('data-annotation-id'),
                annotationInfo = '',
                annotation = this.getAnnotation(annotationId),
                list = annotItem.closest('.ui.list'),
                actionBar = annotItem.find('.header .actions'),
                buttons = {
                    info: actionBar.find('i[data-action="get-info"]'),
                    edit: actionBar.find('i[data-action="edit"]'),
                    delete: actionBar.find('i[data-action="delete"]')
                };
            if (annotation.creator && annotation.creator.name) {
                annotationInfo = 'Created by <strong>'
                    + annotation.creator.name + '</strong>';
            }
            if (annotation.created) {
                if (annotationInfo) {
                    annotationInfo += '<br>';
                } else {
                    annotationInfo = 'Created ';
                }
                annotationInfo += 'at ' + annotation.created.substring(0, 16);
            }
            buttons.edit.on('click', function() {
                self.editEntity(annotation.body, function(entity) {
                    var annot = self.getAnnotation(annotationId);
                    if (entity.id) {
                        annot.body.properties = entity.properties;
                        annot.body.annotations = entity.annotations;
                    }
                    switch (annotation.type) {
                        case 'transcription':
                            self.insertTranscription(annotationId);
                            break;
                        case 'mention':
                            self.insertMention(annotationId);
                            break;
                    }
                });
            });
            buttons.delete.on('click', function() {
                self.deleteAnnotation(annotationId);
            });
            if (annotationInfo) {
                buttons.info.popup({
                    html: annotationInfo,
                    position: 'top center',
                    variation: 'tiny'
                });
            } else {
                buttons.info.remove();
            }
            annotItem.find('.tipped').popup({
                position: 'top center',
                variation: 'tiny inverted'
            });
            annotItem.hover(
                function() {actionBar.show();},
                function() {actionBar.hide();}
            );
        }
    }

}(Seanote));
