(function($, undefined) {
    'use strict';

    var module = {
            options: {
                selector: {
                    id: '#filter-window'
                },
                classNames: {
                    loading: 'loading'
                },
                fadeDuration: 200,
                window: {
                    minWidth: 300,
                    minHeight: 250,
                    enforceBounds: false
                },
                title: null,
                content: null
            }
        };

    /**
     * @class FilterWindow
     * @classdesc
     *
     * @memberof App
     */
    $.FilterWindow = function(userOptions) {
        Object.defineProperty(this, 'options', {
            value: $.merge(true, module.options, userOptions)
        });
        this.node = jQuery(this.options.selector.id);
        this.window = new $.Window(this.node, this.options.window);
        this.initialize();
    };

    $.FilterWindow.prototype = {
        initialize: function() {
            var self = this;
            if (this.options.title) {
                this.window.setTitle(this.options.title);
            }
            if (this.options.content) {
                this.window.setContent(this.options.content);
            }
            this.getNode('close').on('click', function() {
                self.close();
            });
        },

        getNode: function(o) {
            return $.getNode(o, this.node);
        },

        open: function() {
            this.show();
        },

        close: function() {
            this.hide();
        },

        show: function() {
            this.window.fadeIn(this.options.fadeDuration);
        },

        hide: function() {
            this.window.fadeOut(this.options.fadeDuration);
        },

        destroy: function() {
            this.window.destroy();
        }
    }

}(App));
