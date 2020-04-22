(function($, undefined) {
    'use strict';

    var module = {
            options: {
                className: 'seanote-canvas',
                defaultMode: 'default',
                objectImageExpansion: {
                    x: 0,
                    y: 0
                },
                currentDocumentId: null,
                currentPageId: 0,
                resizingHandleClass: 'resizing-handle',
                groupListClass: 'group-list',
                groupClass: 'group'
            },
            objects: {
                segment: {
                    instance: $.CanvasSegment,
                    set: 'segments',
                    isSortable: true
                },
                segmentGroup: {
                    instance: $.CanvasSegmentGroup,
                    set: 'segmentGroups',
                    isSortable: true
                },
                selection: {
                    instance: $.CanvasSelection,
                    set: 'selections',
                    isSortable: false
                }
            },
            commands: {
                displaySegments: 'displaySegments',
                showAllSegments: 'showAllSegments',
                hideAllSegments: 'hideAllSegments',
                selectAllSegments: 'selectAllSegments',
                deselectSegments: 'deselectSegments',
                invertSegmentSelection: 'invertSegmentSelection',
                createSegment: 'createSegment',
                cancelSegmentCreation: 'cancelSegmentCreation',
                resizeSegment: 'resizeSegment',
                cancelSegmentResizing: 'cancelSegmentResizing',
                mergeSegments: 'mergeSegments',
                unmergeSegments: 'unmergeSegments',
                groupSegments: 'groupSegments',
                ungroupSegments: 'ungroupSegments',
                deleteSegments: 'deleteSegments'
            },
            events: [
                $.on('click', {call: 'click', attach: 'event'}),
                $.on(':loadObjects:end', ':displaySegments'),
                $.on(':createSegment:start', ':showAllSegments')
            ]
        },
        menuItems = {
            showSegments: {
                className: 'show-segments',
                events: [
                    $.on('click', {check: 'isActive',
                        onTrue: ':hideAllSegments', onFalse: ':showAllSegments'}),
                    $.on(':showAllSegments', 'activate'),
                    $.on(':hideAllSegments', 'deactivate')
                ],
                keyboardShortcut: '\\'
            },
            selectAllSegments: {
                className: 'select-all-segments',
                events: $.on('click', ':selectAllSegments'),
                keyboardShortcut: 'mod+a'
            },
            deselectAllSegments: {
                className: 'deselect-segments',
                events: $.on('click', ':deselectSegments'),
                keyboardShortcut: 'mod+shift+a'
            },
            invertSegmentSelection: {
                className: 'invert-segment-selection',
                events: $.on('click', ':invertSegmentSelection'),
                keyboardShortcut: 'mod+i'
            },
            createSegment: {
                className: 'create-segment',
                events: $.on('click', ':createSegment'),
                keyboardShortcut: 's'
            },
            createMultipleSegments: {
                className: 'create-multiple-segments',
                events: $.on('click', ':createSegment', true),
                keyboardShortcut: 'shift+s'
            },
            resizeSegment: {
                className: 'resize-segment',
                events: $.on('click', ':resizeSegment'),
                keyboardShortcut: 'r'
            },
            mergeSegments: {
                className: 'merge-segments',
                events: $.on('click', ':mergeSegments')
            },
            unmergeSegments: {
                className: 'unmerge-segments',
                events: $.on('click', ':unmergeSegments')
            },
            groupSegments: {
                className: 'group-segments',
                events: $.on('click', ':groupSegments'),
                keyboardShortcut: 'g'
            },
            ungroupSegments: {
                className: 'ungroup-segments',
                events: $.on('click', ':ungroupSegments'),
                keyboardShortcut: 'shift+g'
            },
            deleteSegments: {
                className: 'delete-segments',
                events: $.on('click', ':deleteSegments'),
                keyboardShortcut: 'mod+backspace'
            }
        },
        toolbarButtons = {
            toggleSegments: {
                className: 'toggle-segments',
                events: [
                    $.on('click', {check: 'isActive',
                        onTrue: ':hideAllSegments', onFalse: ':showAllSegments'}),
                    $.on(':showAllSegments', 'activate'),
                    $.on(':hideAllSegments', 'deactivate')
                ]
            },
            createSegment: {
                className: 'create-segment',
                events: [
                    $.on('click', {check: 'isActive',
                        onTrue: ':cancelSegmentCreation', onFalse: ':createSegment'}),
                    $.on(':createSegment:start', 'activate'),
                    $.on(':createSegment:end', 'deactivate')
                ]
            },
            resizeSegment: {
                className: 'resize-segment',
                events: [
                    $.on('click', {check: 'isActive',
                        onTrue: ':cancelSegmentResizing', onFalse: ':resizeSegment'}),
                    $.on(':resizeSegment:start', 'activate'),
                    $.on(':resizeSegment:end', 'deactivate')
                ]
            },
            mergeSegments: {
                className: 'merge-segments',
                events: $.on('click', ':mergeSegments')
            },
            unmergeSegments: {
                className: 'unmerge-segments',
                events: $.on('click', ':unmergeSegments')
            },
            groupSegments: {
                className: 'group-segments',
                events: $.on('click', ':groupSegments')
            },
            ungroupSegments: {
                className: 'ungroup-segments',
                events: $.on('click', ':ungroupSegments')
            },
            deleteSegments: {
                className: 'delete-segments',
                events: $.on('click', ':deleteSegments')
            }
        };

    /**
     * @class Canvas
     * @classdesc
     *
     * @memberof Seanote
     */
    $.Canvas = function(viewer, userOptions) {
        Object.defineProperty(this, 'options', {
            value: $.merge(true, module.options, userOptions)
        });
        this.node = null;
        this.viewer = viewer;
        this.objects = {
            index: {},
            list: {},
            active: []
        };
        this.pendingObjectsCount = 0;
        this.mode = this.options.defaultMode;
        this.viewer.subscribe('exec:initialize', this.initialize.bind(this));
    };

    $.Canvas.prototype = {
        initialize: function() {
            this.node = jQuery(this.viewer.osd.canvas);
            this.addDefaultCallbacks();
            this.reset();
            this.viewer.addCommands(module.commands, this);
            $.bindEvents(this.viewer, this, this, module);
            $.applyForEach(menuItems, this.viewer.menu, 'addItem');
            $.applyForEach(toolbarButtons, this.viewer.toolbar, 'addButton');
        },

        reset: function() {
            var type, set;
            for (type in module.objects) {
                if (module.objects.hasOwnProperty(type)) {
                    set = module.objects[type].set;
                    this.objects.index[set] = {};
                    this.objects.list[set] = [];
                }
            }
            this.clear();
        },

        clear: function() {
            this.viewer.osd.clearOverlays();
        },

        focus: function() {
            this.node.focus();
        },

        addDefaultCallbacks: function() {
            var self = this;
            this.viewer.addCallback('open', function(e) {
                self.loadObjects(true);
                self.focus();
            }, this.options.className, this);
            this.viewer.addCallback('add-overlay', function(e) {
                self.cacheObjectSelector(e.element);
                if (self.pendingObjectsCount > 0) {
                    if (--self.pendingObjectsCount === 0) {
                        self.viewer.publish('loadObjects:end');
                    }
                }
            }, this.options.className, this);
            this.viewer.addCallback('remove-overlay', function(e) {
                self.uncacheObjectSelector(e.element);
            }, this.options.className, this);
            this.viewer.addCallback('box-creation:after', function(object) {
                if (object.type === 'segment') {
                    self.completeSegmentCreation(object);
                }
            }, this.options.className, this);
            this.viewer.addCallback('box-resizing:after', function(object) {
                if (object.type === 'segment') {
                    self.completeSegmentResizing(object);
                }
            }, this.options.className, this);
        },

        switchMode: function(mode) {
            mode = mode || this.options.defaultMode;
            switch (mode) {
                case 'default':
                    this.viewer.removeCallbacks(this.objects.active);
                    this.viewer.enablePanning();
                    this.objects.active = [];
                    break;
                case 'object-creation':
                case 'object-creation:multiple':
                case 'object-resizing':
                    this.viewer.disablePanning();
                    break;
                default:
                    throw new Error($.getText('msg_undefined_canvas_mode'));
            }
            this.mode = mode;
        },

        loadObjects: function(draw, display) {
            var self = this;
            if (typeof display !== 'boolean') {
                display = false;
            }
            jQuery.getJSON(this.viewer.options.sources.segments, function (data) {
                self.viewer.publish('loadObjects:start');
                self.pendingObjectsCount = data.objects.length;
                jQuery.each(data.objects, function (i, properties) {
                    self.addObject(properties, draw, display);
                });
            }).fail(function(jqXhr, textStatus, error) {
                console.log(error);
            });
        },

        reloadObjects: function(draw) {
            this.reset();
            this.loadObjects(draw, true);
        },

        addObject: function(prop, draw, display) {
            var set = module.objects[prop.type].set,
                instance = module.objects[prop.type].instance,
                index = this.objects.list[set].push(new instance(this, prop)) - 1;
            Object.defineProperty(this.objects.index[set], prop.id, {
                __proto__: null,
                enumerable: true,
                configurable: true,
                value: index
            });
            if (draw) {
                this.drawObject(prop.id, display);
            }
        },

        removeObject: function(o) {
            var obj = (typeof o === 'object') ? o : this.getObjectById(o);
            this.removeObjects([obj], obj.type);
        },

        removeObjects: function(oArray, type) {
            var objArray = (oArray.length && (typeof oArray[0] === 'object'))
                    ? oArray : this.getObjectsById(oArray, type),
                set, index, i;
            for (i = 0; i < objArray.length; i++) {
                set = module.objects[objArray[i].type].set;
                index = this.objects.index[set][objArray[i].id];
                this.eraseObject(objArray[i]);
                this.objects.list[set].splice(index, 1);
            }
            this.rebuildObjectIndex(type);
        },

        sortObjects: function(type) {
            var types = type ? [type] : Object.keys(module.objects),
                objType, i;
            for (i = 0; i < types.length; i++) {
                objType = module.objects[types[i]];
                if (!objType.isSortable) continue;
                this.objects.list[objType.set].sort(function(a, b) {
                    if (a.lineId != b.lineId) {
                        return a.lineId - b.lineId;
                    }
                    return a.boundingBox.x - b.boundingBox.x;
                });
                this.rebuildObjectIndex(types[i]);
            }
        },

        rebuildObjectIndex: function(type) {
            var types = type ? [type] : Object.keys(module.objects),
                set, i, j;
            for (i = 0; i < types.length; i++) {
                set = module.objects[types[i]].set;
                this.objects.index[set] = {};
                for (j = 0; j < this.objects.list[set].length; j++) {
                    Object.defineProperty(this.objects.index[set],
                        this.objects.list[set][j].id, {
                        __proto__: null,
                        enumerable: true,
                        configurable: true,
                        value: j
                    });
                }
            }
        },

        drawObject: function(o, display) {
            var obj = (typeof o === 'object') ? o : this.getObjectById(o),
                el = obj.toElement();
            if (display) {
                obj.isVisible = true;
            }
            else {
                obj.isVisible = false;
                el.style.display = 'none';
            }
            this.viewer.osd.addOverlay({
                element: el,
                location: obj.getBounds()
            });
            if ((obj.type === 'segment') && obj.isGrouped) {
                obj.addGroupButtons();
                obj.addAnnotatedGroupClass();
            }
            obj.bindEvents();
        },

        eraseObject: function(o) {
            var obj = (typeof o === 'object') ? o : this.getObjectById(o);
            obj.unbindEvents();
            obj.isVisible = false;
            this.viewer.osd.removeOverlay(obj.id);
        },

        cacheObjectSelector: function(selector) {
            var id = jQuery(selector).attr('id'),
                obj = this.getObjectById(id);
            if (obj) obj.node = jQuery(selector);
        },

        uncacheObjectSelector: function(o) {
            var id = (typeof o === 'string') ? o : jQuery(o).attr('id'),
                obj = this.getObjectById(id);
            if (obj) obj.node = null;
        },

        getObjectById: function(id, type, location) {
            var types = type ? [type] : Object.keys(module.objects),
                set, index, i;
            for (i = 0; i < types.length; i++) {
                set = module.objects[types[i]].set;
                if (this.objects.index[set].hasOwnProperty(id)) {
                    index = this.objects.index[set][id];
                    return (location === true)
                        ? {'list': this.objects.list[set], 'index': index}
                        : this.objects.list[set][index];
                }
            }
            return null;
        },

        getSiblingObject: function(id, type, offset) {
            var objLocation = this.getObjectById(id, type, true),
                index = objLocation ? objLocation.index + offset : -1;
            if ((index >= objLocation.list.length) || (index < 0)) {
                return null;
            }
            else {
                return objLocation.list[index];
            }
        },

        getPreviousObject: function(id, type) {
            return this.getSiblingObject(id, type, -1);
        },

        getNextObject: function(id, type) {
            return this.getSiblingObject(id, type, 1);
        },

        getObjectsById: function(oArray, type, filter) {
            var objArray = [],
                obj, i;
            for (i = 0; i < oArray.length; i++) {
                obj = this.getObjectById(oArray[i], type);
                if (filter && !filter.test(obj)) continue;
                objArray.push(obj);
            }
            return objArray;
        },

        getObjectsByType: function(type, filter) {
            var set = module.objects[type].set,
                objects = this.objects.list[set],
                selectedObjects = [],
                i;
            if (filter) {
                for (i = 0; i < objects.length; i++) {
                    if (filter.test(objects[i])) {
                        selectedObjects.push(objects[i]);
                    }
                }
                return selectedObjects;
            }
            else {
                return objects;
            }
        },

        getObjects: function(filter) {
            var types = Object.keys(module.objects),
                objects = [],
                selectedObjects = [],
                set, i, j;
            for (i = 0; i < types.length; i++) {
                set = module.objects[types[i]].set;
                objects = this.objects.list[set];
                for (j = 0; j < objects.length; j++) {
                    if (filter) {
                        if (filter.test(objects[j])) {
                            selectedObjects.push(objects[j]);
                        }
                    }
                    else {
                        selectedObjects.push(objects[j]);
                    }
                }
            }
            return selectedObjects;
        },

        findObjects: function(criteria, type) {
            var filter = new $.Validator(),
                i;
            if (!Array.isArray(criteria)) criteria = [criteria];
            for (i = 0; i < criteria.length; i++) {
                switch (criteria[i]) {
                    case 'selected':
                        filter.addCriterion(['isSelected', '===', true]);
                        break;
                    case 'unselected':
                        filter.addCriterion(['isSelected', '!==', true]);
                        break;
                    case 'annotated':
                        filter.addCriterion(['isAnnotated', '===', true]);
                        break;
                    case 'merged':
                        filter.addCriterion(['isMerged', '===', true]);
                        break;
                    case 'grouped':
                        filter.addCriterion(['isGrouped', '===', true]);
                        break;
                    case 'hidden':
                        filter.addCriterion(['isHidden', '===', true]);
                        break;
                    case 'visible':
                        filter.addCriterion(['isHidden', '===', false]);
                        break;
                    default:
                        throw new Error($.getText('msg_undefined_filter_criteria'));
                }
            }
            return type ?
                this.getObjectsByType(type, filter) : this.getObjects(filter);
        },

        getObjectsCount: function(type) {
            var types = [],
                count = 0,
                set, i;
            if (type) {
                return this.getObjectsByType(type).length;
            }
            else {
                types = Object.keys(module.objects);
                for (i = 0; i < types.length; i++) {
                    set = module.objects[types[i]].set;
                    count += this.objects.list[set].length;
                }
                return count;
            }
        },

        hasObject: function(id) {
            return this.getObjectById(id) !== null;
        },

        showObject: function(o) {
            this.applyObjectMethod([o], 'show');
        },

        showObjects: function(oArray) {
            this.applyObjectMethod(oArray, 'show');
        },

        hideObject: function(o) {
            this.applyObjectMethod([o], 'hide');
        },

        hideObjects: function(oArray) {
            this.applyObjectMethod(oArray, 'hide');
        },

        toggleObjectVisibility: function(o) {
            this.applyObjectMethod(Array.isArray(o) ? o : [o], 'toggleVisibility');
        },

        selectObject: function(o) {
            this.applyObjectMethod([o], 'select');
        },

        selectObjects: function(oArray) {
            this.applyObjectMethod(oArray, 'select');
        },

        deselectObject: function(o) {
            this.applyObjectMethod([o], 'deselect');
        },

        deselectObjects: function(oArray) {
            this.applyObjectMethod(oArray, 'deselect');
        },

        toggleObjectSelection: function(o) {
            this.applyObjectMethod(Array.isArray(o) ? o : [o], 'toggleSelection');
        },

        applyObjectMethod: function(oArray, method) {
            var objArray = (oArray.length && (typeof oArray[0] === 'object'))
                    ? oArray : this.getObjectsById(oArray), i;
            for (i = 0; i < objArray.length; i++) {
                if (typeof objArray[i][method] === 'function') {
                    objArray[i][method]();
                }
            }
        },

        displaySegments: function() {
            if (localStorage.getItem('displaySegments') !== 'no') {
                this.viewer.executeCommand('showAllSegments');
            }
        },

        showAllSegments: function() {
            this.showObjects(this.getObjectsByType('segment'));
            localStorage.setItem('displaySegments', 'yes');
        },

        hideAllSegments: function() {
            this.hideObjects(this.getObjectsByType('segment'));
            localStorage.setItem('displaySegments', 'no');
        },

        selectAllSegments: function() {
            this.selectObjects(this.getObjectsByType('segment'));
        },

        deselectSegments: function() {
            this.deselectObjects(this.findObjects('selected', 'segment'));
        },

        invertSegmentSelection: function() {
            this.toggleObjectSelection(this.getObjectsByType('segment'));
        },

        createObject: function(type, multiple) {
            var uuid = $.getRandomUuid(),
                properties = {
                    id: uuid,
                    boundingBox: {
                        x: 0,
                        y: 0,
                        w: 0,
                        h: 0
                    },
                    type: type
                };
            if (multiple === true) {
                if (this.mode !== 'object-creation:multiple') {
                    this.switchMode('object-creation:multiple');
                }
            }
            else {
                this.switchMode('object-creation');
            }
            this.addObject(properties, true, true);
            this.getObjectById(uuid, type).createBoundingBox();
            this.objects.active.push(uuid);
        },

        cancelObjectCreation: function() {
            this.switchMode();
            this.removeObjects(this.objects.active);
        },

        createSegment: function(multiple) {
            this.viewer.publish('createSegment:start');
            this.createObject('segment', multiple);
        },

        cancelSegmentCreation: function() {
            this.cancelObjectCreation();
            this.viewer.publish('createSegment:end');
        },

        completeSegmentCreation: function(segment) {
            jQuery.post(this.viewer.options.sources.idxSegments + '/create', {
                'manifest_id': this.options.currentDocumentId,
                'canvas_id': this.options.currentPageId,
                'segment_id': segment.id,
                'bounding_box': segment.boundingBox
            });
            if (this.mode === 'object-creation:multiple') {
                this.viewer.removeCallbacks([segment.id]);
                this.viewer.executeCommand('createSegment', true);
            }
            else {
                this.switchMode();
                this.viewer.publish('createSegment:end');
            }
        },

        resizeSegment: function() {
            var segments = this.findObjects('selected', 'segment');
            if (segments.length > 1) {
                alert($.getText('msg_multiple_segments_selected') + "\n"
                    + $.getText('msg_resize_segments_separately'));
            }
            else if (!segments.length) {
                alert($.getText('msg_no_selected_segments'));
            }
            else {
                this.switchMode('object-resizing');
                this.viewer.publish('resizeSegment:start');
                segments[0].resizeBoundingBox();
                this.objects.active.push(segments[0].id);
            }
        },

        completeSegmentResizing: function(segment) {
            jQuery.post(this.viewer.options.sources.idxSegments + '/resize', {
                'segment_id': segment.id,
                'bounding_box': segment.boundingBox
            });
            this.switchMode();
            this.viewer.publish('resizeSegment:end');
        },

        deleteSegments: function() {
            var self = this,
                segments = this.findObjects('selected', 'segment');
            if (segments.length > 1) {
                alert($.getText('msg_multiple_segments_selected') + "\n"
                    + $.getText('msg_delete_segments_separately'));
            }
            else if (!segments.length) {
                alert($.getText('msg_no_selected_segments'));
            }
            else {
                jQuery.post(this.viewer.options.sources.idxSegments + '/delete', {
                    'segment_id': segments[0].id
                }).done(function(response) {
                    self.removeObject(segments[0]);
                });
            }
        },

        groupSegments: function() {
            var self = this,
                segments = this.findObjects('selected', 'segment'),
                segmentIds = [],
                i;
            if (segmentIds.length == 1) {
                alert($.getText('msg_single_segment_selected') + "\n"
                    + $.getText('msg_expand_segment_selection'));
            }
            else if (!segments.length) {
                alert($.getText('msg_no_selected_segments'));
            }
            else {
                for (i = 0; i < segments.length; i++) {
                    segmentIds.push(segments[i].id);
                }
                jQuery.post(this.viewer.options.sources.idxSegments + '/group', {
                    'segment_ids[]': segmentIds
                }).done(function(response) {
                    self.reloadObjects(true);
                });
            }
        },

        ungroupSegments: function() {
            var self = this,
                segmentGroups = this.findObjects('selected', 'segmentGroup');
            if (segmentGroups.length > 1) {
                alert($.getText('msg_segments_of_multiple_groups') + "\n"
                    + $.getText('msg_ungroup_segments_separately'));
            }
            else if (!segmentGroups.length) {
                alert($.getText('msg_no_selected_segment_groups'));
            }
            else {
                jQuery.post(this.viewer.options.sources.idxSegments + '/ungroup', {
                    'segment_group_id': segmentGroups[0].id
                }).done(function(response) {
                    self.reloadObjects(true);
                });
            }
        },

        mergeSegments: function() {},

        unmergeSegments: function() {},

        click: function(event) {
            if (!this.hasObject(event.target.id)) {
                this.deselectObjects(this.findObjects('selected'));
            }
        }
    }

}(Seanote));
