(function($, undefined) {
    'use strict';

    var module = {
            options: {
                classNames: {
                    dimmed: 'dimmed',
                    moveHandle: 'title-bar'
                },
                isResizable: false
            }
        };

    /**
     * @class Modal
     * @classdesc
     *
     * @memberof Seanote
     */
    $.Modal = function(node, userOptions) {
        $.InteractiveObject.call(this, node, $.merge(module.options, userOptions));
    };

    $.Modal.prototype = Object.create($.InteractiveObject.prototype);

    $.Modal.prototype.constructor = $.Modal;

}(Seanote));
