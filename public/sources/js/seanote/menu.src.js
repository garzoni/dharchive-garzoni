(function($, undefined) {
    'use strict';

    var module = {
            options: {
                className: 'seanote-menu',
                ignoreMissingItems: false,
                items: {
                    policy: 'disable',
                    list: [
                        'merge-segments',
                        'unmerge-segments'
                    ]
                }
            }
        };

    /**
     * @class Menu
     * @classdesc
     *
     * @memberof Seanote
     */
    $.Menu = function(viewer, userOptions) {
        Object.defineProperty(this, 'options', {
            value: $.merge(true, module.options, userOptions)
        });
        this.node = viewer.getNode(this.options.className);
        this.viewer = viewer;
        this.items = {};
        this.isVisible = false;
        this.initialize();
    };

    $.Menu.prototype = {
        initialize: function() {
            if (!this.node.length) {
                throw new Error($.getText('msg_dom_element_not_found'));
            }
            this.isVisible = this.node.is(':visible');
        },

        getNode: function(o) {
            return $.getNode(o, this.node);
        },

        addItem: function(key, item) {
            var node = this.getNode(item.className);
            if ($.isDisabledByOption(key, this.options.items)) return;
            if (!node.length && this.options.ignoreMissingItems) return;
            if (this.hasItem(key)) {
                throw new Error($.getFormattedText('msg_menu_item_exists', key));
            }
            Object.defineProperty(this.items, key, {
                value: new $.MenuItem(node),
                configurable: true,
                enumerable: true
            });
            $.bindEvents(this.viewer, this, this.items[key], item);
            if (item.keyboardShortcut) {
                this.items[key].assignKeyboardShortcut(item.keyboardShortcut);
            }
        },

        hasItem: function(key) {
            return this.items.hasOwnProperty(key);
        },

        show: function() {
            this.node.show();
            this.isVisible = true;
        },

        hide: function() {
            this.node.hide();
            this.isVisible = false;
        },

        toggleVisibility: function() {
            this.node.toggle();
            this.isVisible = !this.isVisible;
        }
    }

}(Seanote));
