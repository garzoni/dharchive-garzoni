/*!
 * Seanote v0.1.0
 * Copyright 2014-2017 Orlin Topalov
 * Licensed under MIT (http://opensource.org/licenses/MIT)
 */

(function($, undefined) {
    'use strict';

    var version = '0.1.0',
        options = {
            customEvents: {
                prefix: 'exec',
                separator: ':'
            }
        },
        canvas = document.createElement('canvas'),
        dictionary = {};

    function bindEvent(viewer, module, self, node, binder) {
        var handler, validator;
        if (!jQuery.isPlainObject(binder)) {
            return;
        }
        if (binder.onlyIf) {
            validator = new $.Validator(
                Array.isArray(binder.onlyIf)
                    ? binder.onlyIf : [[binder.onlyIf, '===', true]]
            );
            if (!validator.test(module.options)) {
                return;
            }
        }
        if (!binder.event.node) {
            binder.event.node = node;
        }
        handler = function(event) {
            var validator;
            if (binder.check && self) {
                validator = new $.Validator(
                    Array.isArray(binder.check)
                        ? binder.check : [[binder.check, '===', true]]
                );
                binder.call = validator.test(self)
                    ? binder.onTrue : binder.onFalse;
            }
            if (binder.attach === 'event') {
                binder.call.args.splice(0, 0, event);
            }
            if (binder.call.type === 'command') {
                viewer.executeCommand.apply(
                    viewer,
                    [binder.call.name].concat(binder.call.args)
                );
            } else if (binder.call.type === 'module.method') {
                module[binder.call.name].apply(module, binder.call.args);
            } else if (binder.call.type === 'method') {
                self[binder.call.name].apply(self, binder.call.args);
            }
        };
        if (binder.debounce) {
            handler = jQuery.debounce(binder.debounce, handler);
        } else if (binder.throttle) {
            handler = jQuery.throttle(binder.throttle, handler);
        }
        if (binder.event.type === 'custom') {
            viewer.subscribe(binder.event.name, handler);
        } else if ((binder.event.type === 'native') && binder.event.node) {
            binder.event.node.on(binder.event.name, handler);
        }
    }

    function getBinderCall(call, args) {
        var type, name;
        if (jQuery.isPlainObject(call)) {
            if (!call.args) {
                call.args = args;
            }
            return call;
        } else if ((typeof call === 'string') && call.length) {
            if (call.charAt(0) === ':') {
                type = 'command';
                name = call.substring(1);
            } else if (call.charAt(0) === '.') {
                type = 'module.method';
                name = call.substring(1);
            } else {
                type = 'method';
                name = call;
            }
        }
        return {
            type: type,
            name: name,
            args: args
        };
    }

    function getBinderEvent(event) {
        var opt = options.customEvents,
            type = 'native',
            name, node;
        if (jQuery.isPlainObject(event)) {
            return event;
        } else if ((typeof event === 'string') && event.length) {
            if (event.charAt(0) === ':') {
                type = 'custom';
                name = opt.prefix + opt.separator + event.substring(1);
            } else if ((event.charAt(0) === '[')
                && (event.charAt(event.length - 1) === ']')) {
                node = jQuery(window);
                name = event.substring(1, event.length - 1);
            } else {
                name = event;
            }
        }
        return {
            type: type,
            name: name,
            node: node
        };
    }

    $.on = function(event, action) {
        var args = Array.prototype.slice.call(arguments, 2);
        if (jQuery.isPlainObject(action)) {
            if (action.check) {
                action.onTrue = getBinderCall(action.onTrue);
                action.onFalse = getBinderCall(action.onFalse);
            } else {
                action.call = getBinderCall(action.call, args);
            }
        } else {
            action = {call: getBinderCall(action, args)};
        }
        action.event = getBinderEvent(event);
        return action;
    };

    $.isNonEmptyArray = function(array) {
        return jQuery.isArray(array) && (array.length > 0);
    };

    $.isDisabledByOption = function(key, opt) {
        if (opt.policy === 'disable') {
            return opt.list.indexOf(key) !== -1;
        } else if (opt.policy === 'enable') {
            return opt.list.indexOf(key) === -1;
        }
        return undefined;
    };

    $.inViewport = function(element, full) {
        var rect = element[0].getBoundingClientRect();
        return (
            (full ? rect.top : rect.bottom) >= 0 &&
            (full ? rect.left : rect.right) >= 0 &&
            (full ? rect.bottom : rect.top) <= jQuery(window).height() &&
            (full ? rect.right : rect.left) <= jQuery(window).width()
        );
    };

    $.toTitleCase = function(text) {
        return text.replace(/\w\S*/g, function(text){
            return text.charAt(0).toUpperCase() + text.substr(1).toLowerCase();
        });
    };

    $.getOperatingSystem = function() {
        var userAgent = window.navigator.userAgent,
            platform = window.navigator.platform,
            macosPlatforms = ['Macintosh', 'MacIntel', 'MacPPC', 'Mac68K'],
            windowsPlatforms = ['Win32', 'Win64', 'Windows', 'WinCE'],
            iosPlatforms = ['iPhone', 'iPad', 'iPod'],
            os = null;

        if (macosPlatforms.indexOf(platform) !== -1) {
            os = 'macOS';
        } else if (iosPlatforms.indexOf(platform) !== -1) {
            os = 'iOS';
        } else if (windowsPlatforms.indexOf(platform) !== -1) {
            os = 'Windows';
        } else if (/Android/.test(userAgent)) {
            os = 'Android';
        } else if (!os && /Linux/.test(platform)) {
            os = 'Linux';
        }

        return os;
    };

    $.getVersion = function() {
        return version;
    };

    $.getOption = function(opt) {
        return $.getPropertyValue(options, opt);
    };

    $.getPropertyValue = function(obj, property) {
        var p;
        if (!jQuery.isPlainObject(obj)) {
            return undefined;
        }
        property = property.split('.');
        p = obj[property.shift()];
        while (p && property.length) {
            p = p[property.shift()];
        }
        return (p && (typeof p === 'object') && !Array.isArray(p))
            ? jQuery.extend(true, {}, p) : p;
    };

    $.getText = function(key) {
        return dictionary.hasOwnProperty(key) ? dictionary[key] : key;
    };

    $.getTextWidth = function(text, font) {
        var context = canvas.getContext('2d');
        context.font = font;
        return context.measureText(text).width;
    };

    $.getFormattedKeyboardShortcut = function(keys) {
        keys = keys.toLowerCase();
        if ($.getOperatingSystem() === 'macOS') {
            keys = keys.replace('mod+', '⌘');
            keys = keys.replace('alt+', '⌥');
            keys = keys.replace('shift+', '⇧');
            keys = keys.replace('ctrl+', '⌃');
        } else {
            keys = keys.replace('mod+', 'ctrl+');
        }
        keys = keys.replace(/\+/g, ' + ');
        return $.toTitleCase(keys);
    };

    $.getFormattedText = function(key) {
        var args = Array.prototype.slice.call(arguments, 1);
        args.splice(0, 0, $.getText(key));
        return $.format.apply(this, args);
    };

    $.getLetterCode = function(n) {
        var ordA = 'A'.charCodeAt(0),
            ordZ = 'Z'.charCodeAt(0),
            len = ordZ - ordA + 1,
            s = '';
        while (n >= 0) {
            s = String.fromCharCode(n % len + ordA) + s;
            n = Math.floor(n / len) - 1;
        }
        return s;
    };

    $.getRandomUuid = function() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0,
                v = (c === 'x') ? r : (r&0x3 | 0x8);
            return v.toString(16);
        });
    };

    $.getNode = function(o, node) {
        var className = jQuery.isPlainObject(o) ? o.className : o;
        if (!node) {
            return jQuery();
        }
        return node.find('.' + className);
    };

    $.getBoundingBox = function(node) {
        return node[0].getBoundingClientRect();
    };

    $.loadDictionary = function(i18n) {
        dictionary = i18n;
    };

    $.forEach = function(obj, callback, context) {
        var args = Array.prototype.slice.call(arguments, 2),
            properties = jQuery.isPlainObject(obj) ? Object.keys(obj) : [],
            i;
        for (i = 0; i < properties.length; i++) {
            if (typeof callback === 'function') {
                callback.apply(context || this,
                    [properties[i], obj[properties[i]]].concat(args));
            }
        }
    };

    $.applyForEach = function(obj, context, methodName) {
        if (!jQuery.isPlainObject(context)
            || (typeof context[methodName] !== 'function')) {
            return;
        }
        $.forEach(obj, context[methodName].bind(context));
    };

    $.bindEvents = function(viewer, module, self, opt) {
        var node, i;
        if (!jQuery.isPlainObject(opt)) {
            return;
        }
        if (!Array.isArray(opt.events)) {
            opt.events = [opt.events];
        }
        if (self && (typeof self === 'object') && self.node) {
            node = self.node;
        } else if (opt.className && module.node) {
            node = $.getNode(opt.className, module.node);
        }
        for (i = 0; i < opt.events.length; i++) {
            bindEvent(viewer, module, self, node, $.clone(opt.events[i]));
        }
    };

    $.bindEventsForEach = function(viewer, module, self, options) {
        $.forEach(options, function(key, opt) {
            if (jQuery.isPlainObject(self)) {
                self = self[key];
            }
            $.bindEvents(viewer, module, self, opt);
        });
    };

    $.merge = function() {
        var args = Array.prototype.slice.call(arguments),
            immutable = (args[0] === true),
            obj;
        if (typeof args[0] === 'boolean') {
            args.splice(0, 1);
        }
        obj = jQuery.extend.apply(this, [true, {}].concat(args));
        return immutable ? Object.freeze(obj) : obj;
    };

    $.clone = function(obj, shallow) {
        if (shallow === true) {
            return jQuery.extend({}, obj);
        } else {
            return jQuery.extend(true, {}, obj);
        }
    };

    $.format = function(string) {
        var args = Array.prototype.slice.call(arguments, 1);
        return string.replace(/{(\d+)}/g, function(placeholder, index) {
            return typeof args[index] !== 'undefined' ? args[index] : placeholder;
        });
    };

    $.trim = function(str) {
        return str.replace(/^\s+|\s+$/g, '');
    };

	$.serialize = function(value) {
		return JSON.stringify(value);
	};

	$.deserialize = function(value) {
		if (typeof value !== 'string') return undefined;
        return JSON.parse(value);
	};

    $.compact = function(obj) {
        if (typeof obj === 'string') {
            return;
        }
        jQuery.each(obj, function(key, value) {
            if (value === "" || value === null) {
                delete obj[key];
            } else if (jQuery.isArray(value)) {
                if (value.length === 0) {
                    delete obj[key];
                    return;
                }
                jQuery.each(value, function(k,v) {
                    $.compact(v);
                });
            } else if (typeof value === 'object') {
                $.compact(value);
                if (Object.keys(value).length === 0) {
                    delete obj[key];
                }
            }
        });
    };
}(Seanote));
