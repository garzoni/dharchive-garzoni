(function($, undefined) {
    'use strict';

    var module = {
            options: {
                minWidth: 100,
                minHeight: 100,
                classNames: {
                    moving: 'moving',
                    resizing: 'resizing',
                    backdrop: 'backdrop',
                    sizingContainer: 'sizing-handles',
                    sizingHandle: 'sizing-handle',
                    moveHandle: 'move-handle'
                },
                sizingHandles: {
                    policy: 'disable',
                    list: []
                },
                isMovable: true,
                isResizable: true,
                enforceBounds: true
            }
        },
        handles = {
            sizing: {
                all: ['n', 'ne', 'e', 'se', 's', 'sw', 'w', 'nw'],
                top: ['n', 'nw', 'ne'],
                left: ['w', 'nw', 'sw'],
                bottom: ['s', 'sw', 'se'],
                right: ['e', 'ne', 'se']
            }
        },
        ePos = {},
        cPos = {};

    function isOnEdge(edge, handle) {
        if (edge === 'any') edge = 'all';
        return handles.sizing[edge]
            ? (handles.sizing[edge].indexOf(handle) !== -1) : undefined;
    }

    function getCursorPositionChange() {
        if (!cPos.start || !cPos.current) return {};
        return {
            x: cPos.current.x - cPos.start.x,
            y: cPos.current.y - cPos.start.y
        }
    }

    /**
     * @class InteractiveObject
     * @classdesc
     *
     * @memberof Seanote
     */
    $.InteractiveObject = function(node, userOptions) {
        $.Object.call(this, node, $.merge(module.options, userOptions));
        this.parentNode = this.node.parent();
        this.addBackdrop();
        this.addInteractions();
    };

    $.InteractiveObject.prototype = Object.create($.Object.prototype);

    $.InteractiveObject.prototype.constructor = $.InteractiveObject;

    $.InteractiveObject.prototype.addBackdrop = function() {
        var c = this.options.classNames;
        this.backdrop = this.parentNode.find('.' + c.backdrop);
        if (!this.backdrop.length) {
            this.parentNode.prepend('<div class="' + c.backdrop + '"></div>');
            this.backdrop = this.parentNode.find('.' + c.backdrop);
        }
    };

    $.InteractiveObject.prototype.addInteractions = function() {
        var c = this.options.classNames;
        if (this.options.isMovable === true) {
            this.node.find('.' + c.moveHandle).css('cursor', 'move');
            this.node.find('.' + c.moveHandle)
                .on('mousedown.move', this.startMoving.bind(this));
        }
        if (this.options.isResizable === true) {
            this.addSizingHandles();
            this.sizingHandles.find('.' + c.sizingHandle)
                .on('mousedown.resize', this.startResizing.bind(this));
        }
    };

    $.InteractiveObject.prototype.addSizingHandles = function() {
        var c = this.options.classNames,
            handle, i;
        this.node.prepend('<div class="' + c.sizingContainer + '"></div>');
        this.sizingHandles = this.node.find('.' + c.sizingContainer);
        for (i = 0; i < handles.sizing.all.length; i++) {
            handle = handles.sizing.all[i];
            if ((handle !== 'se') && $.isDisabledByOption(handle,
                this.options.sizingHandles)) continue;
            this.sizingHandles.append('<div class="' + c.sizingHandle + ' '
                + handle + '"></div>');
        }
        this.updateSizingHandles();
    };

    $.InteractiveObject.prototype.updateSizingHandles = function() {
        var offset = this.sizingHandles.find('.se').height(),
            height = this.node.outerHeight() - 2 * offset;
        this.sizingHandles.find('.w, .e').height(height);
        this.sizingHandles.find('.s, .sw, .se').css('top', height + offset);
    };

    $.InteractiveObject.prototype.removeSizingHandles = function() {
        this.sizingHandles.remove();
        delete(this.sizingHandles);
    };

    $.InteractiveObject.prototype.getCursorPosition = function(event) {
        var parentOffset = this.parentNode.offset();
        return {
            x: event.pageX - parentOffset.left,
            y: event.pageY - parentOffset.top
        };
    };

    $.InteractiveObject.prototype.getElementPosition = function() {
        var offset = this.node.offset(),
            parentOffset = this.parentNode.offset();
        return {
            x: offset.left - parentOffset.left,
            y: offset.top - parentOffset.top,
            w: this.node.width(),
            h: this.node.height()
        };
    };

    $.InteractiveObject.prototype.startMoving = function(event) {
        if(!jQuery(event.target).hasClass(this.options.classNames.moveHandle)) return;
        cPos.start = this.getCursorPosition(event);
        ePos.start = this.getElementPosition();
        this.startAction(this.options.classNames.moving, 'move');
        jQuery(window).on('mousemove.move', this.move.bind(this));
        jQuery(window).on('mouseup.move', this.stopMoving.bind(this));
        event.preventDefault();
    };

    $.InteractiveObject.prototype.move = function(event) {
        var delta, x, y, rightOffset, bottomOffset;
        cPos.current = this.getCursorPosition(event);
        delta = getCursorPositionChange();
        x = ePos.start.x + delta.x;
        y = ePos.start.y + delta.y;
        if (this.options.enforceBounds) {
            rightOffset = this.parentNode.innerWidth() - (x + this.node.outerWidth());
            bottomOffset = this.parentNode.innerHeight() - (y + this.node.outerHeight());
            if (x < 0) x = 0;
            if (y < 0) y = 0;
            if (rightOffset < 0) x += rightOffset;
            if (bottomOffset < 0) y += bottomOffset;
        }
        this.node.css('left', x + 'px');
        this.node.css('top', y + 'px');
    };

    $.InteractiveObject.prototype.stopMoving = function(event) {
        this.stopAction(this.options.classNames.moving);
        jQuery(window).off('mousemove.move');
        jQuery(window).off('mouseup.move');
    };

    $.InteractiveObject.prototype.startResizing = function(event) {
        var handle = event.target.className.split(' ')[1];
        if (!handle) return;
        cPos.start = this.getCursorPosition(event);
        ePos.start = this.getElementPosition();
        this.startAction(this.options.classNames.resizing,
            this.sizingHandles.find('.' + handle).css('cursor'));
        jQuery(window).on('mousemove.resize', this.resize.bind(this, handle));
        jQuery(window).on('mouseup.resize', this.stopResizing.bind(this));
        event.preventDefault();
    };

    $.InteractiveObject.prototype.resize = function(handle, event) {
        var delta, currentWidth, currentHeight;
        cPos.current = this.getCursorPosition(event);
        if (this.options.enforceBounds && (cPos.current.x < 0 || cPos.current.y < 0
            || cPos.current.x > this.parentNode.innerWidth()
            || cPos.current.y > this.parentNode.innerHeight())) {
            return;
        }
        delta = getCursorPositionChange();
        if (isOnEdge('right', handle)) {
            this.node.width(Math.max(ePos.start.w + delta.x, this.options.minWidth));
        }
        if (isOnEdge('bottom', handle)) {
            this.node.height(Math.max(ePos.start.h + delta.y, this.options.minHeight));
        }
        if (isOnEdge('left', handle)) {
            currentWidth = ePos.start.w - delta.x;
            if (currentWidth > this.options.minWidth) {
                this.node.width(currentWidth);
                this.node.css('left', (ePos.start.x + delta.x) + 'px');
            }
        }
        if (isOnEdge('top', handle)) {
            currentHeight = ePos.start.h - delta.y;
            if (currentHeight > this.options.minHeight) {
                this.node.height(currentHeight);
                this.node.css('top', (ePos.start.y + delta.y) + 'px');
            }
        }
        this.node.trigger('resize', false);
        this.updateSizingHandles();
    };

    $.InteractiveObject.prototype.stopResizing = function(event) {
        this.stopAction(this.options.classNames.resizing);
        jQuery(window).off('mousemove.resize');
        jQuery(window).off('mouseup.resize');
    };

    $.InteractiveObject.prototype.startAction = function(className, backdropCursor) {
        this.node.addClass(className);
        this.backdrop.css('cursor', (backdropCursor || 'wait'));
        this.backdrop.show();
    };

    $.InteractiveObject.prototype.stopAction = function(className, backdropCursor) {
        this.node.removeClass(className);
        this.backdrop.css('cursor', (backdropCursor || 'default'));
        this.backdrop.hide();
        cPos = {};
        ePos = {};
    }

}(App));
