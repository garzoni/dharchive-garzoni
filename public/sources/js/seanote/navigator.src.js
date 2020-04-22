(function($, undefined) {
    'use strict';

    var module = {
            options: {
                className: 'seanote-navigator',
                containerId: 'openseadragon-navigator',
                window: {
                    minWidth: 200,
                    minHeight: 200
                }
            },
            commands: {
                showNavigator: 'show',
                hideNavigator: 'hide'
            },
            events: [
                $.on('resize', ':updateNavigator'),
                $.on(':initialize', 'display')
            ]
        },
        buttons = {
            dismiss: {
                className: 'close',
                events: $.on('click', ':hideNavigator')
            }
        },
        menuItems = {
            showNavigator: {
                className: 'show-navigator',
                events: [
                    $.on('click', {check: 'isActive',
                        onTrue: ':hideNavigator', onFalse: ':showNavigator'}),
                    $.on(':showNavigator', 'activate'),
                    $.on(':hideNavigator', 'deactivate')
                ],
                keyboardShortcut: 'mod+alt+n'
            }
        };

    /**
     * @class Navigator
     * @classdesc
     *
     * @memberof Seanote
     */
    $.Navigator = function(viewer, userOptions) {
        Object.defineProperty(this, 'options', {
            value: $.merge(true, module.options, userOptions)
        });
        this.node = viewer.getNode(this.options.className);
        this.window = new $.Window(this.node, this.options.window);
        this.viewer = viewer;
        this.initialize();
    }

    $.Navigator.prototype = {
        initialize: function() {
            this.viewer.addCommands(module.commands, this);
            $.bindEvents(this.viewer, this, this, module);
            $.bindEvents(this.viewer, this, null, buttons.dismiss);
            $.applyForEach(menuItems, this.viewer.menu, 'addItem');
        },

        getNode: function(o) {
            return $.getNode(o, this.node);
        },

        display: function() {
            if (localStorage.getItem('showNavigator') !== 'no') {
                this.viewer.executeCommand('showNavigator');
            }
        },

        show: function() {
            this.window.fadeIn(function() {
                localStorage.setItem('showNavigator', 'yes');
            });
        },

        hide: function() {
            this.window.fadeOut(function() {
                localStorage.setItem('showNavigator', 'no');
            });
        }
    }

}(Seanote));
