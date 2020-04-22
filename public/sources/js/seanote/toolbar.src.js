(function($, undefined) {
    'use strict';

    var module = {
            options: {
                className: 'seanote-toolbar',
                fadeDuration: 300,
                ignoreMissingButtons: false,
                buttons: {
                    policy: 'disable',
                    list: ['mergeSegments', 'unmergeSegments']
                }
            },
            commands: {
                showToolbar: 'show',
                hideToolbar: 'hide'
            },
            events: $.on(':initialize', 'display')
        },
        menuItems = {
            showToolbar: {
                className: 'show-toolbar',
                events: [
                    $.on('click', {check: 'isActive',
                        onTrue: ':hideToolbar', onFalse: ':showToolbar'}),
                    $.on(':showToolbar', 'activate'),
                    $.on(':hideToolbar', 'deactivate')
                ],
                keyboardShortcut: 'mod+alt+t'
            }
        };

    /**
     * @class Toolbar
     * @classdesc
     *
     * @memberof Seanote
     */
    $.Toolbar = function(viewer, userOptions) {
        Object.defineProperty(this, 'options', {
            value: $.merge(true, module.options, userOptions)
        });
        this.node = viewer.getNode(this.options.className);
        this.viewer = viewer;
        this.buttons = {};
        this.isVisible = false;
        this.initialize();
    }

    $.Toolbar.prototype = {
        initialize: function() {
            if (!this.node.length) {
                throw new Error($.getFormattedText('msg_dom_element_not_found',
                    this.node.selector));
            }
            this.viewer.addCommands(module.commands, this);
            $.bindEvents(this.viewer, this, this, module);
            $.applyForEach(menuItems, this.viewer.menu, 'addItem');
        },

        getNode: function(o) {
            return $.getNode(o, this.node);
        },

        addButton: function(key, button) {
            var node = this.getNode(button.className);
            if ($.isDisabledByOption(key, this.options.buttons)) return;
            if (!node.length && this.options.ignoreMissingButtons) return;
            if (this.hasButton(key)) {
                throw new Error($.getFormattedText('msg_toolbar_button_exists', key));
            }
            Object.defineProperty(this.buttons, key, {
                value: new $.Button(node),
                configurable: true,
                enumerable: true
            });
            $.bindEvents(this.viewer, this, this.buttons[key], button);
        },

        hasButton: function(key) {
            return this.buttons.hasOwnProperty(key);
        },

        display: function() {
            if (localStorage.getItem('showToolbar') !== 'no') {
                this.viewer.executeCommand('showToolbar');
            }
        },

        show: function() {
            this.node.fadeIn(this.options.fadeDuration, function() {
                localStorage.setItem('showToolbar', 'yes');
            });
            this.isVisible = true;
        },

        hide: function() {
            this.node.fadeOut(this.options.fadeDuration, function() {
                localStorage.setItem('showToolbar', 'no');
            });
            this.isVisible = false;
        }
    }

}(Seanote));
