(function($, undefined) {
    'use strict';

    var module = {
            options: {}
        };

    /**
     * @class MenuItem
     * @classdesc
     *
     * @memberof Seanote
     */
    $.MenuItem = function(node, userOptions) {
        $.Object.call(this, node, $.merge(module.options, userOptions));
    };

    $.MenuItem.prototype = Object.create($.Object.prototype);

    $.MenuItem.prototype.constructor = $.MenuItem;

    $.MenuItem.prototype.assignKeyboardShortcut = function(keyboardShortcut) {
        var self = this,
            padding = parseInt(self.node.css('padding-right')) || 0,
            textElement = jQuery('<span class="description">'
                + $.getFormattedKeyboardShortcut(keyboardShortcut) + '</span>'),
            textWidth, textOffset;
        self.node.prepend(textElement);
        textWidth = $.getTextWidth(textElement.text(), textElement.css('font'));
        textOffset = parseInt(textElement.css('right')) || 0;
        padding += textOffset + Math.ceil(textWidth);
        self.node.attr('style', 'padding-right: ' + padding + 'px !important');
        Mousetrap.bind(keyboardShortcut, function(e) {
            self.node.trigger('click');
            return false;
        });
    }

}(Seanote));
