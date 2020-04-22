(function($, undefined) {
    'use strict';

    var module = {
            options: {
                minWidth: 200,
                minHeight: 200
            }
        },
        components = {
            titleBar: {
                className: 'title-bar'
            }
        };

    /**
     * @class Window
     * @classdesc
     *
     * @memberof Seanote
     */
    $.Window = function(node, userOptions) {
        $.InteractiveObject.call(this, node, $.merge(module.options, userOptions));
    };

    $.Window.prototype = Object.create($.InteractiveObject.prototype);

    $.Window.prototype.constructor = $.Window;

    $.Window.prototype.getTitle = function() {
        var titleBar = $.getNode(components.titleBar.className, this.node);
        return titleBar.find('.title').text();
    };

    $.Window.prototype.setTitle = function(title) {
        var titleBar = $.getNode(components.titleBar.className, this.node);
        titleBar.find('.title').text(title);
    };

}(Seanote));
