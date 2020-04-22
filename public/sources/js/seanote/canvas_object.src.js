(function($, undefined) {
    'use strict';

    var resizingHandles = ['n', 'ne', 'e', 'se', 's', 'sw', 'w', 'nw'],
		activeResizingHandle = null,
		cursor = {
            start: null,
            current: null,
            path: []
        },
        dragging = false;

    /**
     * @class CanvasObject
     * @classdesc
     *
     * @memberof Seanote
     */
    $.CanvasObject = function(canvas, properties) {
        jQuery.extend(true, this, {
            id: null,
            node: null,
			type: 'object',
			className: 'canvas-object',
            boundingBox: {
                x: 0,
                y: 0,
                w: 0,
                h: 0
            },
            isSelected: false,
            isVisible: false
        }, properties);
        this.canvas = canvas;
        this.initialize();
    };

    $.CanvasObject.prototype = {
        initialize: function() {
            var i;
            if (!this.canvas) {
                throw new Error($.getText('msg_undefined_canvas'));
            }
        },

        getSelector: function() {
            if (this.node.length) {
                return this.node[0];
            }
            return this.id ? jQuery('#' + this.id) : null;
        },

        getBounds: function() {
            if (this.boundingBox.w && this.boundingBox.h) {
                return this.canvas.viewer.osd.viewport.imageToViewportRectangle(
                    new OpenSeadragon.Rect(this.boundingBox.x, this.boundingBox.y,
                        this.boundingBox.w, this.boundingBox.h));
            }
            return null;
        },

        getOverlay: function() {
            return this.canvas.viewer.osd.currentOverlays[this.node.index()];
        },

        getOverlayBounds: function() {
            return this.getOverlay().bounds;
        },

        getOverlayBoundingBox: function() {
            var imageRect = this.canvas.viewer.osd.viewport.viewportToImageRectangle(
                    this.getOverlayBounds());
			return {
				x: parseInt(imageRect.x, 10),
				y: parseInt(imageRect.y, 10),
				w: parseInt(imageRect.width, 10),
				h: parseInt(imageRect.height, 10)
			};
        },

        getImageUrl: function() {
            var url = this.canvas.viewer.options.sources.image,
                expand = this.canvas.options.objectImageExpansion,
                queryStartPos = url.indexOf('?'),
                x = this.boundingBox.x,
                y = this.boundingBox.y,
                w = this.boundingBox.w,
                h = this.boundingBox.h,
                xCorr, yCorr, wCorr, hCorr, params;
            if (typeof expand !== 'undefined') {
                xCorr = parseInt(expand.x);
                yCorr = parseInt(expand.y);
                wCorr = ((typeof expand.x === 'string') && (expand.x.slice(-1) == '%'))
                    ? Math.round(w * (xCorr / 100)) : xCorr;
                hCorr = ((typeof expand.y === 'string') && (expand.y.slice(-1) == '%'))
                    ? Math.round(h * (yCorr / 100)) : yCorr;
                x = x - Math.round(wCorr / 2);
                y = y - Math.round(hCorr / 2);
                w = w + wCorr;
                h = h + hCorr;
                if (x < 0) x = 0;
                if (y < 0) y = 0;
                if (w < 1) w = 1;
                if (h < 1) h = 1;
            }
            params = x + ',' + y + ',' + w + ',' + h + '/full/0/default.jpg';
            if (queryStartPos === -1) {
                url += '/' + params;
            } else {
                url = url.substring(0, queryStartPos) + '/' + params
                    + url.substring(queryStartPos);
            }
            return url;
        },

        toElement: function() {
            var element = document.createElement('div');
            element.id = this.id;
            element.className = this.className;
            return element;
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

        select: function() {
            this.node.addClass('selected');
            this.isSelected = true;
        },

        deselect: function() {
            this.node.removeClass('selected');
            this.isSelected = false;
        },

        toggleSelection: function() {
            this.node.toggleClass('selected');
            this.isSelected = !this.isSelected;
        },

        createBoundingBox: function() {
            this.canvas.viewer.addCallback('canvas-drag', this.startBoundingBoxCreation, this.id, this);
            this.canvas.viewer.addCallback('canvas-release', this.endBoundingBoxCreation, this.id, this);
        },

        resizeBoundingBox: function() {
            var handleClassName = this.canvas.options.resizingHandleClass,
                i;
            this.canvas.viewer.addCallback('canvas-drag', this.startBoundingBoxResizing, this.id, this);
            this.canvas.viewer.addCallback('canvas-release', this.endBoundingBoxResizing, this.id, this);
            for (i = 0; i < resizingHandles.length; i++) {
                this.node.append('<div class="' + handleClassName + ' ' + handleClassName + '-'
                    + resizingHandles[i] + '"></div>');
            }
        },

        startBoundingBoxCreation: function(event) {
            if (!dragging) {
                this.canvas.viewer.applyCallbacks('box-creation:before', this);
                dragging = true;
                cursor.start = this.canvas.viewer.getCursorPosition(event);
                this.boundingBox.x = cursor.start.x;
                this.boundingBox.y = cursor.start.y;
            }
            else {
                cursor.current = this.canvas.viewer.getCursorPosition(event);
                this.updateBoundingBox(this.getOverlayBoundsOnCreate());
                this.canvas.viewer.applyCallbacks('box-creation', this);
            }
        },

        endBoundingBoxCreation: function(event) {
            dragging = false;
            this.canvas.viewer.applyCallbacks('box-creation:after', this);
        },

        startBoundingBoxResizing: function(event) {
            var handleClassName = this.canvas.options.resizingHandleClass,
                regex = new RegExp(handleClassName + '-' + '([a-z]{1,2})'),
                classList, matched, i;
            if (jQuery(event.originalEvent.target).hasClass(handleClassName)
                    && !dragging) {
                this.canvas.viewer.applyCallbacks('box-resizing:before', this);
                dragging = true;
                cursor.start = this.canvas.viewer.getCursorPosition(event);
                classList = jQuery(event.originalEvent.target).attr('class').toString().split(' ');
                for (i = 0; i < classList.length; i++) {
                    matched = regex.exec(classList[i]);
                    if (matched) activeResizingHandle = matched[1];
                }
            }
            else if (dragging) {
                cursor.current = this.canvas.viewer.getCursorPosition(event);
                this.updateBoundingBox(this.getOverlayBoundsOnResize(), true);
                this.canvas.viewer.applyCallbacks('box-resizing', this);
            }
        },

        endBoundingBoxResizing: function(event) {
            dragging = false;
			activeResizingHandle = null;
            this.node.removeClass('resizable');
            this.node.empty();
            this.canvas.viewer.applyCallbacks('box-resizing:after', this);
        },

        getOverlayBoundsOnCreate: function() {
            var originalPosition = {
                    topLeft: {
                        x: cursor.start.x,
                        y: cursor.start.y
                    },
                    bottomRight: {
                        x: cursor.start.x,
                        y: cursor.start.y
                    }
                };
            return this.computeOverlayBounds(originalPosition);
        },

        getOverlayBoundsOnResize: function() {
            var bounds = this.getOverlayBounds(),
                origPos = {
                    topLeft: {
                        x: bounds.x,
                        y: bounds.y
                    },
                    bottomRight: {
                        x: bounds.x + bounds.width,
                        y: bounds.y + bounds.height
                    }
                };
            switch (activeResizingHandle) {
                case 'n':
                    cursor.current.x = origPos.topLeft.x;
                    origPos.topLeft.y = Math.max(origPos.topLeft.y, cursor.current.y);
                    break;
                case 'ne':
                    origPos.bottomRight.x = Math.max(origPos.topLeft.x, cursor.current.x);
                    origPos.topLeft.y = Math.max(origPos.topLeft.y, cursor.current.y);
                    break;
                case 'e':
                    origPos.bottomRight.x = Math.max(origPos.topLeft.x, cursor.current.x);
                    cursor.current.y = origPos.topLeft.y;
                    break;
                case 'se':
                    origPos.bottomRight.x = Math.min(origPos.bottomRight.x, cursor.current.x);
                    origPos.bottomRight.y = Math.min(origPos.bottomRight.y, cursor.current.y);
                    break;
                case 's':
                    cursor.current.x = origPos.topLeft.x;
                    origPos.bottomRight.y = Math.max(origPos.topLeft.y, cursor.current.y);
                    break;
                case 'sw':
                    origPos.topLeft.x = Math.max(origPos.topLeft.x, cursor.current.x);
                    origPos.bottomRight.y = Math.max(origPos.topLeft.y, cursor.current.y);
                    break;
                case 'w':
                    origPos.topLeft.x = Math.max(origPos.topLeft.x, cursor.current.x);
                    cursor.current.y = origPos.topLeft.y;
                    break;
                case 'nw':
                    origPos.topLeft.x = Math.max(origPos.topLeft.x, cursor.current.x);
                    origPos.topLeft.y = Math.max(origPos.topLeft.y, cursor.current.y);
                    break;
                default:
                    throw new Error($.getText('msg_undefined_resizing_handler'));
            }
            bounds = this.computeOverlayBounds(origPos);
            if (bounds.width < 0.01) bounds.width = 0.01;
            if (bounds.height < 0.01) bounds.height = 0.01;
            return bounds;
        },

        computeOverlayBounds: function(originalPosition) {
            var currentPosition = {
                    topLeft: {
                        x: Math.min(originalPosition.topLeft.x, cursor.current.x),
                        y: Math.min(originalPosition.topLeft.y, cursor.current.y)
                    },
                    bottomRight: {
                        x: Math.max(originalPosition.bottomRight.x, cursor.current.x),
                        y: Math.max(originalPosition.bottomRight.y, cursor.current.y)
                    }
                };
            return new OpenSeadragon.Rect(
                currentPosition.topLeft.x, currentPosition.topLeft.y,
                currentPosition.bottomRight.x - currentPosition.topLeft.x,
                currentPosition.bottomRight.y - currentPosition.topLeft.y
            );
        },

        updateBoundingBox: function(overlayBounds, forceRedraw) {
            this.canvas.viewer.osd.updateOverlay(this.getSelector(), overlayBounds);
            this.boundingBox = this.getOverlayBoundingBox();
            if (forceRedraw === true) {
                this.canvas.viewer.osd.forceRedraw();
            }
        },

        bindEvents: function() {},

        unbindEvents: function() {
            this.node.off();
        }
    }

}(Seanote));
