(function($, undefined) {
    'use strict';

    var module = {
            options: {
                width: null,
                height: null,
                classNames: {
                    active: 'active',
                    disabled: 'disabled'
                }
            }
        };

    /**
     * @class Object
     * @classdesc
     *
     * @memberof Seanote
     */
    $.Object = function(node, userOptions) {
        Object.defineProperty(this, 'options', {
            value: $.merge(true, module.options, userOptions)
        });
        this.node = node;
        this.isEnabled = false;
        this.isActive = false;
        this.isVisible = false;
        this.initialize();
    };

    $.Object.prototype = {
        initialize: function() {
            if (!this.node.length) {
                throw new Error($.getFormattedText(
                    'msg_dom_element_not_found',
                    this.node.selector
                ));
            }
            this.isEnabled = !this.node.hasClass(this.options.classNames.disabled);
            this.isActive = this.node.hasClass(this.options.classNames.active);
            this.isVisible = this.node.is(':visible');
            if (this.options.width !== null) {
                this.node.width(this.options.width);
            }
            if (this.options.height !== null) {
                this.node.height(this.options.height);
            }
        },

        enable: function() {
            this.node.removeClass(this.options.classNames.disabled);
            this.isEnabled = true;
        },

        disable: function() {
            this.node.addClass(this.options.classNames.disabled);
            this.isEnabled = false;
        },

        toggleStatus: function() {
            this.node.toggleClass(this.options.classNames.disabled);
            this.isEnabled = !this.isEnabled;
        },

        activate: function() {
            this.node.addClass(this.options.classNames.active);
            this.isActive = true;
        },

        deactivate: function() {
            this.node.removeClass(this.options.classNames.active);
            this.isActive = false;
        },

        toggleActivation: function() {
            this.node.toggleClass(this.options.classNames.active);
            this.isActive = !this.isActive;
        },

        show: function() {
            jQuery.fn.show.apply(this.node, Array.prototype.slice.call(arguments));
            this.isVisible = true;
        },

        hide: function() {
            jQuery.fn.hide.apply(this.node, Array.prototype.slice.call(arguments));
            this.isVisible = false;
        },

        toggleVisibility: function() {
            jQuery.fn.toggle.apply(this.node, Array.prototype.slice.call(arguments));
            this.isVisible = !this.isVisible;
        },

        fadeIn: function() {
            jQuery.fn.fadeIn.apply(this.node, Array.prototype.slice.call(arguments));
            this.isVisible = true;
        },

        fadeOut: function() {
            jQuery.fn.fadeOut.apply(this.node, Array.prototype.slice.call(arguments));
            this.isVisible = false;
        },

        fadeToggle: function() {
            jQuery.fn.fadeToggle.apply(this.node, Array.prototype.slice.call(arguments));
            this.isVisible = !this.isVisible;
        },

        focus: function() {
            this.node.focus();
        },

        destroy: function() {
            this.node.remove();
            this.node = jQuery();
        }
    }

}(App));
