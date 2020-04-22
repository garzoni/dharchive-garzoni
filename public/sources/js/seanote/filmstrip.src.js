(function($, undefined) {
    'use strict';

    var module = {
            options: {
                className: 'seanote-filmstrip',
                slideDuration: 600,
                scrollDuration: 600,
                lazyImageLoading: {
                    isEnabled: true,
                    fadeDuration: 200,
                    srcAttribute: 'data-src'
                }
            },
            commands: {
                showFilmstrip: 'show',
                hideFilmstrip: 'hide'
            },
            events: [
                $.on('[resize]', 'loadImages'),
                $.on(':initialize', 'display'),
                $.on(':showFilmstrip', 'scrollToCurrentPage')
            ]
        },
        sequences = {
            normal: {
                className: 'list',
                events: $.on('scroll', {call: '.loadImages', debounce: 100,
                    onlyIf: 'lazyImageLoading.isEnabled'})
            }
        },
        pages = {
            current: {
                className: 'current'
            }
        },
        buttons = {
            scrollBackward: {
                className: 'scroll-backward',
                events: $.on('click', '.scroll', true)
            },
            scrollForward: {
                className: 'scroll-forward',
                events: $.on('click', '.scroll')
            },
            scrollToCurrentPage: {
                className: 'scroll-to-current-page',
                events: $.on('click', '.scrollToCurrentPage')
            },
            dismiss: {
                className: 'close',
                events: $.on('click', ':hideFilmstrip')
            }
        },
        menuItems = {
            showFilmstrip: {
                className: 'show-filmstrip',
                events: [
                    $.on('click', {check: 'isActive',
                        onTrue: ':hideFilmstrip', onFalse: ':showFilmstrip'}),
                    $.on(':showFilmstrip', 'activate'),
                    $.on(':hideFilmstrip', 'deactivate')
                ],
                keyboardShortcut: 'mod+alt+f'
            }
        };

    /**
     * @class Filmstrip
     * @classdesc
     *
     * @memberof Seanote
     * @param {Seanote.Viewer} viewer
     * @param {Object} userOptions
     */
    $.Filmstrip = function(viewer, userOptions) {
        Object.defineProperty(this, 'options', {
            value: $.merge(true, module.options, userOptions)
        });
        this.node = viewer.getNode(this.options.className);
        this.viewer = viewer;
        this.buttons = {};
        this.isVisible = false;
        this.initialize();
    };

    $.Filmstrip.prototype = {
        initialize: function() {
            if (!this.node.length) {
                throw new Error($.getFormattedText('msg_dom_element_not_found',
                    this.node.selector));
            }
            this.loadButtons();
            this.viewer.addCommands(module.commands, this);
            $.bindEvents(this.viewer, this, this, module);
            $.bindEvents(this.viewer, this, null, sequences.normal);
            $.applyForEach(menuItems, this.viewer.menu, 'addItem');
        },

        getNode: function(o) {
            return $.getNode(o, this.node);
        },

        loadButtons: function() {
            $.forEach(buttons, function(key, button) {
                Object.defineProperty(this.buttons, key, {
                    value: new $.Button(this.getNode(button)),
                    configurable: true,
                    enumerable: true
                });
                $.bindEvents(this.viewer, this, this.buttons[key], button);
            }, this);
        },

        loadImages: function() {
            var opt = this.options.lazyImageLoading;
            if (opt.isEnabled !== true) return;
            this.node.find('img[' + opt.srcAttribute + ']').each(function() {
                var image = jQuery(this);
                if ($.inViewport(image)) {
                    image.fadeOut(function() {
                        image.attr('src', image.attr(opt.srcAttribute))
                            .fadeIn(opt.fadeDuration);
                        image.removeAttr(opt.srcAttribute);
                    });
                }
            });
        },

        scroll: function(reverse) {
            var list = this.getNode(sequences.normal),
                offset = list.scrollLeft() + (reverse === true ? -1 : 1) * this.node.width();
            list.scrollTo(offset, this.options.scrollDuration);
        },

        scrollToCurrentPage: function() {
            var list = this.getNode(sequences.normal),
                currentPage = this.getNode(pages.current),
                offset = -(this.node.width() / 2 - currentPage.width() / 2);
            list.scrollTo(currentPage, this.options.scrollDuration, {offset: offset});
        },

        display: function() {
            if (localStorage.getItem('showFilmstrip') !== 'no') {
                this.viewer.executeCommand('showFilmstrip');
            }
        },

        show: function() {
            var self = this;
            this.node.slideDown(this.options.slideDuration, function() {
                localStorage.setItem('showFilmstrip', 'yes');
                self.loadImages();
            });
            this.isVisible = true;
        },

        hide: function() {
            this.node.slideUp(this.options.slideDuration, function() {
                localStorage.setItem('showFilmstrip', 'no');
            });
            this.isVisible = false;
        }
    };

}(Seanote));
