(function($, undefined) {
    'use strict';

    var module = {
            options: {}
        };

    /**
     * @class Button
     * @classdesc
     *
     * @memberof Seanote
     */
    $.Button = function(node, userOptions) {
        $.Object.call(this, node, $.merge(module.options, userOptions));
    }

    $.Button.prototype = Object.create($.Object.prototype);

    $.Button.prototype.constructor = $.Button;

}(Seanote));
