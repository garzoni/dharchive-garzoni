(function($, undefined) {
    'use strict';

    var module = {
            options: {
                id: 'seanote',
                components: {
                    policy: 'disable',
                    list: []
                },
                openSeadragon: {
                    id: 'openseadragon-viewer',
                    preserveViewport: true,
                    visibilityRatio: 1,
                    showNavigationControl: false,
                    gestureSettingsMouse: {
                        clickToZoom: false,
                        dblClickToZoom: false
                    }
                },
                classNames: {
                    viewer: 'seanote-viewer'
                },
                language: 'en'
            },
            components: [
                {
                    key: 'canvas',
                    instance: $.Canvas,
                    isRequired: true
                },
                {
                    key: 'menu',
                    instance: $.Menu,
                    isRequired: true
                },
                {
                    key: 'controlBar',
                    instance: $.ControlBar,
                    isRequired: true
                },
                {
                    key: 'toolbar',
                    instance: $.Toolbar
                },
                {
                    key: 'filmstrip',
                    instance: $.Filmstrip
                },
                {
                    key: 'navigator',
                    instance: $.Navigator
                },
                {
                    key: 'annotator',
                    instance: $.Annotator
                }
            ],
            commands: {
                zoomIn: 'zoomIn',
                zoomOut: 'zoomOut',
                zoomToActualSize: 'zoomToActualSize',
                fitOnViewer: 'fitOnViewer',
                enablePanning: 'enablePanning',
                disablePanning: 'disablePanning',
                rotate: 'rotate',
                updateNavigator: 'updateNavigator'
            }
        },
        menuItems = {
            zoomIn: {
                className: 'zoom-in',
                events: $.on('click', ':zoomIn'),
                keyboardShortcut: 'shift+up'
            },
            zoomOut: {
                className: 'zoom-out',
                events: $.on('click', ':zoomOut'),
                keyboardShortcut: 'shift+down'
            },
            zoomToActualSize: {
                className: 'zoom-to-actual-size',
                events: $.on('click', ':zoomToActualSize'),
                keyboardShortcut: 'mod+shift+up'
            },
            fitOnViewer: {
                className: 'fit-on-viewer',
                events: $.on('click', ':fitOnViewer'),
                keyboardShortcut: 'mod+shift+down'
            },
            rotate90: {
                className: 'rotate-90-cw',
                events: $.on('click', ':rotate', 90),
                keyboardShortcut: 'alt+right'
            },
            rotate180: {
                className: 'rotate-180',
                events: $.on('click', ':rotate', 180),
                keyboardShortcut: 'alt+down'
            },
            rotate270: {
                className: 'rotate-90-ccw',
                events: $.on('click', ':rotate', 270),
                keyboardShortcut: 'alt+left'
            }
        },
        toolbarButtons = {
            zoomIn: {
                className: 'zoom-in',
                events: $.on('click', ':zoomIn')
            },
            zoomOut: {
                className: 'zoom-out',
                events: $.on('click', ':zoomOut')
            }
        },
        openSeadragonEvents = [
            'add-item-failed',
            'add-overlay',
            'animation',
            'animation-finish',
            'animation-start',
            'canvas-click',
            'canvas-double-click',
            'canvas-drag',
            'canvas-drag-end',
            'canvas-enter',
            'canvas-exit',
            'canvas-nonprimary-press',
            'canvas-nonprimary-release',
            'canvas-pinch',
            'canvas-press',
            'canvas-release',
            'canvas-scroll',
            'clear-overlay',
            'close',
            'constrain',
            'container-enter',
            'container-exit',
            'controls-enabled',
            'full-page',
            'full-screen',
            'home',
            'mouse-enabled',
            'navigator-scroll',
            'open',
            'open-failed',
            'page',
            'pan',
            'pre-full-page',
            'pre-full-screen',
            'remove-overlay',
            'reset-size',
            'resize',
            'rotate',
            'tile-drawing',
            'tile-drawn',
            'update-level',
            'update-overlay',
            'update-tile',
            'update-viewport',
            'viewport-change',
            'visible',
            'zoom'
        ],
        nextCallbackId = 1;

    function getNewCallbackId() {
        var pad = '000000',
            id = nextCallbackId++;
        return '#' + (pad + id).slice(-pad.length);
    }

    function isValidCallbackId(id) {
        var regex = /#[0-9]{6}/;
        return regex.test(id);
    }

    /**
     * @class Viewer
     * @classdesc
     *
     * @memberof Seanote
     */
    $.Viewer = function(userOptions) {
        Object.defineProperty(this, 'options', {
            value: $.merge(true, module.options, userOptions.viewer)
        });
        this.callbacks = {};
        this.commands = {};
        this.node = jQuery('#' + this.options.id);
        this.loadComponents(module.components, userOptions);
        this.initialize();
    };

    $.Viewer.prototype = {
        initialize: function() {
            this.addCommands(module.commands);
            this.initializeOpenSeadragon();
            $.applyForEach(menuItems, this.menu, 'addItem');
            $.applyForEach(toolbarButtons, this.toolbar, 'addButton');
            this.publish('initialize');
            //console.log(this);
        },

        initializeOpenSeadragon: function() {
            var options = this.options.openSeadragon;
            options.tileSources = [this.options.sources.image];
            if (this.navigator) {
                options.showNavigator = true;
                options.navigatorId = this.navigator.options.containerId;
            }
            this.osd = OpenSeadragon(options);
        },

        loadComponents: function(components, userOptions) {
            var key, i;
            for (i = 0; i < components.length; i++) {
                key = components[i].key;
                if ((components[i].isRequired !== true)
                    && $.isDisabledByOption(key, this.options.components)) {
                    continue;
                }
                Object.defineProperty(this, key, {
                    value: new components[i]['instance'](this, userOptions[key]),
                    configurable: true,
                    enumerable: true
                });
            }
        },

        getNode: function(o) {
            return $.getNode(o, this.node);
        },

        // Command Methods

        addCommands: function(commands, thisArg) {
            thisArg = thisArg || this;
            $.forEach(commands, function(key, method) {
                if (typeof thisArg[method] === 'function') {
                    this.addCommand(key, thisArg[method].bind(thisArg));
                }
            }, this);
        },

        addCommand: function(key, command) {
            if (this.hasCommand(key)) {
                throw new Error($.getFormattedText('msg_command_exists', key));
            }
            Object.defineProperty(this.commands, key, {
                value: command,
                enumerable: true
            });
        },

        hasCommand: function(key) {
            return this.commands.hasOwnProperty(key);
        },

        executeCommand: function(cmd) {
            var args = Array.prototype.slice.call(arguments, 1);
            if (typeof this.commands[cmd] === 'function') {
                this.commands[cmd].apply(this, args);
                this.publish(cmd);
            }
            else {
                //throw new Error($.getText('msg_undefined_command'));
                console.warn('The command "' + cmd + '" has not been defined.');
            }
        },

        // Callback Methods

        addCallback: function(eventName, callback, assigner, context, parameters) {
            var id = getNewCallbackId();
            callback = context ? jQuery.proxy(callback, context) : callback;
            if (!this.hasCallbacks(eventName)) {
                this.callbacks[eventName] = {};
            }
            this.callbacks[eventName][id] = {
                callback: callback,
                parameters: parameters || null,
                assigner: assigner || null
            };
            if (this.isOpenSeadragonEvent(eventName)) {
                this.osd.addHandler(eventName, callback, parameters);
            }
            return id;
        },

        removeCallback: function(id) {
            this.removeCallbacks([id]);
        },

        removeCallbacks: function(idArray) {
            var eventNames = Object.keys(this.callbacks),
                isCallbackId, callbacks, keys, i, j, k;
            for (i = 0; i < eventNames.length; i++) {
                callbacks = this.callbacks[eventNames[i]];
                keys = Object.keys(callbacks);
                for (j = 0; j < keys.length; j++) {
                    for (k = 0; k < idArray.length; k++) {
                        isCallbackId = isValidCallbackId(idArray[k]);
                        if ((!isCallbackId && idArray[k] !== callbacks[keys[j]].assigner)
                            || (isCallbackId && idArray[k] !== keys[j])) {
                            continue;
                        }
                        if (this.isOpenSeadragonEvent(eventNames[i])) {
                            this.osd.removeHandler(eventNames[i], callbacks[keys[j]].callback);
                        }
                        delete(callbacks[keys[j]]);
                    }
                }
                if (!Object.keys(callbacks).length) {
                    delete(this.callbacks[eventNames[i]]);
                }
            }
        },

        removeAllCallbacks: function(eventName) {
            if (this.isOpenSeadragonEvent(eventName)) {
                this.osd.removeAllHandlers(eventName);
            }
            if (eventName) {
                delete(this.callbacks[eventName]);
            }
            else {
                this.callbacks = {};
            }
        },

        applyCallbacks: function(eventName, object) {
            var cb = this.callbacks[eventName],
                idArray, i;
            if (!cb) return;
            idArray = Object.keys(cb).sort();
            for (i = 0; i < idArray.length; i++) {
                cb[idArray[i]].callback(object, cb[idArray[i]].parameters);
            }
        },

        hasCallbacks: function(eventName) {
            return this.callbacks.hasOwnProperty(eventName);
        },

        isOpenSeadragonEvent: function(eventName) {
            return openSeadragonEvents.indexOf(eventName) !== -1;
        },

        // OpenSeadragon Wrapper Methods

        getCursorPosition: function(event) {
            return this.osd.viewport.pointFromPixel(event.position);
        },

        zoomIn: function() {
            this.osd.viewport.zoomBy(1.1);
            this.osd.viewport.applyConstraints();
        },

        zoomOut: function() {
            this.osd.viewport.zoomBy(0.9);
            this.osd.viewport.applyConstraints();
        },

        zoomToActualSize: function() {
            this.osd.viewport.zoomTo(this.osd.viewport.imageToViewportZoom(1));
        },

        fitOnViewer: function() {
            this.osd.viewport.goHome();
        },

        enablePanning: function() {
            this.osd.panHorizontal = true;
            this.osd.panVertical = true;
        },

        disablePanning: function() {
            this.osd.panHorizontal = false;
            this.osd.panVertical = false;
        },

        rotate: function(newAngle) {
            var currentAngle = parseInt(this.osd.viewport.getRotation(), 10);
            this.osd.viewport.setRotation(currentAngle + newAngle);
        },

        updateNavigator: function() {
            this.osd.navigator.update(this.osd.viewport);
        },

        // Pub/Sub Methods

        subscribe: function() {
            this.node.on.apply(this.node, arguments);
        },

        unsubscribe: function() {
            this.node.off.apply(this.node, arguments);
        },

        publish: function() {
            var args = Array.prototype.slice.call(arguments, 0),
                opt = $.getOption('customEvents');
            if (!args.length) return;
            args[0] = opt.prefix + opt.separator + args[0];
            this.node.trigger.apply(this.node, args);
        }
    }

}(Seanote));
