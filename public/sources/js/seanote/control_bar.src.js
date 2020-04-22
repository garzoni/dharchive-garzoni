(function($, undefined) {
    'use strict';

    var defaultOptions = {
            className: 'seanote-control-bar'
        };

    /**
     * @class ControlBar
     * @classdesc
     *
     * @memberof Seanote
     */
    $.ControlBar = function(viewer, userOptions) {
        Object.defineProperty(this, 'options', {
            value: $.merge(true, defaultOptions, userOptions)
        });
        this.node = viewer.getNode(this.options.className);
        this.viewer = viewer;
        this.items = {};
        this.isVisible = false;
        this.initialize();
    }

    $.ControlBar.prototype = {
        initialize: function() {
            if (!this.node.length) {
                throw new Error($.getText('msg_dom_element_not_found'));
            }
            this.isVisible = this.node.is(':visible');
        }
    }

}(Seanote));
