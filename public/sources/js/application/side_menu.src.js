(function($, undefined) {
    'use strict';

    var module = {
        options: {
            id: 'side-menu'
        }
    };

    /**
     * @class SideMenu
     * @classdesc
     *
     * @memberof App
     */
    $.SideMenu = function(userOptions) {
        Object.defineProperty(this, 'options', {
            value: jQuery.extend(true, {}, module.options, userOptions)
        });
        this.node = jQuery('#' + this.options.id);
        this.items = {};
        this.isVisible = false;
        this.isExpanded = false;
        this.initialize();
    };

    $.SideMenu.prototype = {
        initialize: function() {
            if (!this.node.length) {
                return;
            }
            this.isVisible = this.node.is(':visible');
            this.node.find('.content > .current.item')
                .first().closest('.ui.menu > .item')
                .addClass('current');
            if (this.node.hasClass('icon')
                || $.matchScreen('tablet', {minWidth: 0})) {
                this.initializeDropdownMenus();
                this.isExpanded = false;
            } else {
                this.initializeAccordion();
                this.isExpanded = true;
            }
            this.bindEvents();
        },

        initializeAccordion: function() {
            var currentItem = this.node.children('.current').first();
            this.reset();
            this.node.addClass('accordion');
            currentItem.find('.content').addClass('active');
            this.node.accordion({
                exclusive: false
            });
        },

        initializeDropdownMenus: function() {
            this.reset();
            this.node.addClass('icon');
            this.node.children().each(function () {
                var item = jQuery(this),
                    title = item.find('.title span').text(),
                    menu = item.find('.menu'),
                    content = '<div class="header">' + title + '</div>';
                if (menu.children().length) {
                    content += '<div class="ui divider"></div>';
                } else {
                    content += '<div class="placeholder item"></div>';
                }
                menu.prepend(content);
            });
            this.node.children().addClass('ui dropdown').dropdown({
                on: 'hover'
            });
        },

        bindEvents: function() {
            var self = this;
            jQuery(window).resize(function() {
                var isTabletScreen = $.matchScreen('tablet', {minWidth: 0});
                if (isTabletScreen && self.isExpanded) {
                    self.collapse();
                } else if (!isTabletScreen && !self.isExpanded) {
                    self.expand();
                }
            });
        },

        getNode: function(o) {
            return $.getNode(o, this.node);
        },

        reset: function() {
            this.node.find('.menu, .item').removeClass('transition hidden');
            this.node.find('.active').removeClass('active');
        },

        expand: function() {
            if (this.isExpanded) {
                return;
            }
            this.node.children().removeClass('ui dropdown').dropdown('destroy');
            this.node.find('.header, .divider, .placeholder').remove();
            this.node.removeClass('icon');
            this.initializeAccordion();
            this.isExpanded = true;
        },

        collapse: function() {
            if (!this.isExpanded) {
                return;
            }
            this.node.accordion('destroy');
            this.node.removeClass('accordion');
            this.initializeDropdownMenus();
            this.isExpanded = false;
        },

        show: function() {
            this.node.show();
            this.isVisible = true;
        },

        hide: function() {
            this.node.hide();
            this.isVisible = false;
        },

        toggleVisibility: function() {
            this.node.toggle();
            this.isVisible = !this.isVisible;
        }
    }

}(App));
