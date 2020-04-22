(function($, undefined) {
    'use strict';

    /**
     * @class CanvasSegmentGroup
     * @classdesc
     *
     * @memberof Seanote
     */
    $.CanvasSegmentGroup = function(canvas, properties) {
        $.CanvasObject.call(this, canvas, jQuery.extend(true, {
            type: 'segmentGroup',
            className: 'canvas-segment-group',
            lineId: 0,
            members: [],
            isAnnotated: false
        }, properties));
    };

    $.CanvasSegmentGroup.prototype = Object.create($.CanvasObject.prototype);

    $.CanvasSegmentGroup.prototype.constructor = $.CanvasSegmentGroup;

    $.CanvasSegmentGroup.prototype.forEachMember = function(method) {
        var args = Array.prototype.slice.call(arguments, 1),
            members = this.canvas.getObjectsById(this.members, 'segment'),
            i;
        for (i = 0; i < members.length; i++) {
            if (typeof members[i][method] === 'function') {
                members[i][method].apply(members[i], args);
            }
        }
    };

    $.CanvasSegmentGroup.prototype.select = function() {
        $.CanvasObject.prototype.select.call(this);
        this.forEachMember('selectGroup', this.id);
    };

    $.CanvasSegmentGroup.prototype.deselect = function() {
        $.CanvasObject.prototype.deselect.call(this);
        this.forEachMember('deselectGroup', this.id);
    };

    $.CanvasSegmentGroup.prototype.toggleSelection = function() {
        $.CanvasObject.prototype.toggleSelection.call(this);
        this.forEachMember('toggleGroupSelection', this.id);
    };

}(Seanote));
