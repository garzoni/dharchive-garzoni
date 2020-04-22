(function($, undefined) {
    'use strict';

    /**
     * @class CanvasSelection
     * @classdesc
     *
     * @memberof Seanote
     */
    $.CanvasSelection = function(canvas, properties) {
        $.CanvasObject.call(this, canvas, jQuery.extend(true, {
            type: 'selection'
        }, properties));
    }

    $.CanvasSelection.prototype = Object.create($.CanvasObject.prototype);

    $.CanvasSelection.prototype.constructor = $.CanvasSelection;

}(Seanote));
