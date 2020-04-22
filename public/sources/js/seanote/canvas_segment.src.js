(function($, undefined) {
    'use strict';

    var module = {
            events: [
                $.on('click', {call: 'click', attach: 'event'}),
                $.on('dblclick', {call: 'doubleClick', attach: 'event'}),
                $.on('mouseenter', 'showGroupButtons', true),
                $.on('mouseleave', 'hideGroupButtons', true)
            ]
        },
        buttons = {
            group: {
                className: 'group',
                events: [
                    $.on('click', {call: '.clickOnGroup', attach: 'event'}),
                    $.on('dblclick', {call: '.doubleClickOnGroup', attach: 'event'})
                ]
            }
        };

    /**
     * @class CanvasSegment
     * @classdesc
     *
     * @memberof Seanote
     */
    $.CanvasSegment = function(canvas, properties) {
        $.CanvasObject.call(this, canvas, jQuery.extend(true, {
            type: 'segment',
            className: 'canvas-segment',
            lineId: 0,
            groups: [],
            isGrouped: false,
            isMerged: false,
            isAnnotated: false
        }, properties));
        this.isGrouped = !!this.groups.length;
    };

    $.CanvasSegment.prototype = Object.create($.CanvasObject.prototype);

    $.CanvasSegment.prototype.constructor = $.CanvasSegment;

    $.CanvasSegment.prototype.toElement = function() {
        var element = document.createElement('div'),
            groupList = document.createElement('ul'),
            segmentClasses = [this.className];
        element.id = this.id;
        if (this.isMerged) {
            segmentClasses.push('merged');
        }
        if (this.isGrouped) {
            segmentClasses.push('grouped');
        }
        if (this.isAnnotated) {
            segmentClasses.push('annotated');
        }
        element.className = segmentClasses.join(' ');
        groupList.className = this.canvas.options.groupListClass;
        element.appendChild(groupList);
        return element;
    };

    $.CanvasSegment.prototype.forEachComember = function(method) {
        var args = Array.prototype.slice.call(arguments, 1),
            groupList = args[0] ? [args[0]] : this.groups,
            groups = this.canvas.getObjectsById(groupList, 'segmentGroup'),
            members, i, j;
        for (i = 0; i < groups.length; i++) {
            members = this.canvas.getObjectsById(groups[i].members, 'segment');
            for (j = 0; j < members.length; j++) {
                if (members[j] === this.id) continue;
                if (typeof members[j][method] === 'function') {
                    members[j][method].apply(members[j], args);
                }
            }
        }
    };

    $.CanvasSegment.prototype.belongsToGroup = function(groupId) {
        return this.groups.indexOf(groupId) !== -1;
    };

    $.CanvasSegment.prototype.addToGroup = function(groupId) {
        if (this.belongsToGroup(groupId)) return;
        this.groups.push(groupId);
        this.addGroupButton(groupId);
        if (!this.isGrouped) this.isGrouped = true;
    };

    $.CanvasSegment.prototype.removeFromGroup = function(groupId) {
        var index = this.groups.indexOf(groupId);
        if (index === -1) return;
        this.groups.splice(index, 1);
        this.removeGroupButton(groupId);
        if (!this.groups.length) this.isGrouped = false;
    };

    $.CanvasSegment.prototype.getGroupButton = function(groupId) {
        return this.node.find('[data-group="' + groupId + '"]');
    };

    $.CanvasSegment.prototype.addGroupButton = function(groupId) {
        var groupLoc = this.canvas.getObjectById(groupId, 'segmentGroup', true);
        if (!this.belongsToGroup(groupId)) return;
        this.node.find('.' + this.canvas.options.groupListClass).append(
            '<li class="' + this.canvas.options.groupClass + '" style="display:none" data-group="'
                + groupId + '">'+ $.getLetterCode(groupLoc.index) + '</li>');
    };

    $.CanvasSegment.prototype.addGroupButtons = function() {
        for (var i = 0; i < this.groups.length; i++) {
            this.addGroupButton(this.groups[i]);
        }
    };

    $.CanvasSegment.prototype.removeGroupButton = function(groupId) {
        this.getGroupButton(groupId).remove();
    };

    $.CanvasSegment.prototype.removeGroupButtons = function() {
        this.node.find('.' + this.canvas.options.groupListClass).empty();
    };

    $.CanvasSegment.prototype.showGroupButtons = function(propagate) {
        this.node.find('.' + this.canvas.options.groupClass).show();
        if (propagate === true) this.forEachComember('showGroupButtons');
    };

    $.CanvasSegment.prototype.hideGroupButtons = function(propagate) {
        this.node.find('.' + this.canvas.options.groupClass).hide();
        if (propagate === true) this.forEachComember('hideGroupButtons');
    };

    $.CanvasSegment.prototype.toggleGroupButtons = function(propagate) {
        this.node.find('.' + this.canvas.options.groupClass).toggle();
        if (propagate === true) this.forEachComember('toggleGroupButtons');
    };

    $.CanvasSegment.prototype.selectGroup = function(groupId) {
        if (!this.belongsToGroup(groupId)) return;
        this.getGroupButton(groupId).addClass('selected');
        this.node.addClass('selected-group');
    };

    $.CanvasSegment.prototype.deselectGroup = function(groupId) {
        if (!this.belongsToGroup(groupId)) return;
        this.getGroupButton(groupId).removeClass('selected');
        if (!this.canvas.getObjectsById(this.groups, 'segmentGroup',
            new $.Validator([['isSelected', '===', true]])).length) {
            this.node.removeClass('selected-group');
        }
    };

    $.CanvasSegment.prototype.toggleGroupSelection = function(groupId) {
        if (!this.belongsToGroup(groupId)) return;
        this.getGroupButton(groupId).toggleClass('selected');
        if (this.canvas.getObjectsById(this.groups, 'segmentGroup',
            new $.Validator([['isSelected', '===', true]])).length) {
            this.node.addClass('selected-group');
        }
        else {
            this.node.removeClass('selected-group');
        }
    };

    $.CanvasSegment.prototype.addAnnotatedGroupClass = function() {
        var group, i;
        for (i = 0; i < this.groups.length; i++) {
            group = this.canvas.getObjectById(this.groups[i], 'segmentGroup');
            if (group.isAnnotated) {
                this.getGroupButton(group.id).addClass('annotated');
                this.node.addClass('annotated-group');
            }
        }
    };

    $.CanvasSegment.prototype.click = function(e) {
        if (!e.shiftKey && !e.altKey) {
            this.canvas.deselectObjects(this.canvas.findObjects('selected', 'segment'));
        }
        if (e.altKey) {
            this.deselect();
        }
        else {
            this.select();
        }
    };

    $.CanvasSegment.prototype.doubleClick = function(e) {
        this.canvas.viewer.executeCommand('openAnnotator', this);
    };

    $.CanvasSegment.prototype.clickOnGroup = function(e) {
        var group = this.canvas.getObjectById(e.target.dataset.group);
        if (!e.shiftKey && !e.altKey) {
            this.canvas.deselectObjects(this.canvas.findObjects('selected', 'segmentGroup'));
        }
        if (e.altKey) {
            group.deselect();
        }
        else {
            group.select();
        }
        e.stopPropagation();
    };

    $.CanvasSegment.prototype.doubleClickOnGroup = function(e) {
        this.canvas.viewer.annotator.open(
            this.canvas.getObjectById(e.target.dataset.group));
        e.stopPropagation();
    };

    $.CanvasSegment.prototype.bindEvents = function() {
        $.bindEvents(this.canvas.viewer, this, this, module);
        $.bindEventsForEach(this.canvas.viewer, this, null, buttons);
    };

}(Seanote));
