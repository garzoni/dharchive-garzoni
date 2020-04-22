(function($, undefined) {
    'use strict';

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

    $.matchScreen = function(preset, overrides) {
        var query = 'only screen',
            features = {
                minWidth: 0,
                maxWidth: 0
            };

        switch(preset) {
            case 'mobile':
                features.maxWidth = '768px';
                break;
            case 'tablet':
                features.minWidth = '768px';
                features.maxWidth = '991px';
                break;
            case 'small-monitor':
                features.minWidth = '992px';
                features.maxWidth = '1200px';
                break;
            case 'large-monitor':
                features.minWidth = '1200px';
                break;
        }

        if (typeof overrides === 'object') {
            jQuery.extend(true, features, overrides);
        }

        if (!features.minWidth && !features.maxWidth) {
            return false;
        }

        if (features.minWidth) {
            query += ' and (min-width: ' + features.minWidth + ')';
        }
        if (features.maxWidth) {
            query += ' and (max-width: ' + features.maxWidth + ')';
        }

        return window.matchMedia(query).matches;
    };

    $.insertTableRow = function(tbody, rowTemplate, data) {
        var tr = rowTemplate.clone(),
            td, property;
        for (property in data) {
            if (data.hasOwnProperty(property)) {
                td = tr.find('[data-column="' + property + '"]');
                if (td.length) {
                    td.html(data[property]);
                }
            }
        }
        tbody.append(tr);
    };

    $.toHtmlList = function(values, ordered, listClassNames, itemClassNames) {
        if (!jQuery.isArray(values)) {
            return '';
        }
        if (values.length === 1) {
            return values[0];
        }
        ordered = (ordered === true) || false;
        var list = jQuery('<' + (ordered ? 'ol' : 'ul') + '/>');
        if (listClassNames) {
            list.addClass(listClassNames);
        }
        jQuery.each(values, function(i, value) {
            var item = jQuery('<li/>').text(value);
            if (itemClassNames) {
                item.addClass(itemClassNames);
            }
            item.appendTo(list);
        });
        return list;
    };

    $.padLeft = function (padText, number) {
        number = number.toString();
        if (number.length < padText.length) {
            number = (padText + number).slice(-padText.length);
        }
        return number;
    };

    $.formatDate = function(date, format) {
        if (!date) {
            return '';
        }
        format = format || 'Y-M-D';
        var year = date.getFullYear(),
            month = '' + (date.getMonth() + 1),
            day = '' + date.getDate(),
            formatComponents, char, i;
        if (month.length < 2) {
            month = '0' + month;
        }
        if (day.length < 2) {
            day = '0' + day;
        }
        date = '';
        formatComponents = format.split('');
        for (i = 0; i < formatComponents.length; i++) {
            char = formatComponents[i];
            switch (char) {
                case 'Y': date += year; break;
                case 'M': date += month; break;
                case 'D': date += day; break;
                case '/':
                case '.':
                case '-':
                case ' ':
                    date += char;
                    break;
                default:
                    throw 'Invalid date format';
            }
        }
        return date;
    };

    $.getFormattedText = function(key) {
        var args = Array.prototype.slice.call(arguments, 1);
        args.splice(0, 0, $.getText(key));
        return $.format.apply(this, args);
    };

    $.getRandomUuid = function() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0,
                v = (c === 'x') ? r : (r&0x3 | 0x8);
            return v.toString(16);
        });
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

    $.getNode = function(o, node) {
        var className = jQuery.isPlainObject(o) ? o.className : o;
        if (!node) {
            return jQuery();
        }
        return node.find('.' + className);
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

    $.center = function (element) {
        element.css('position', 'absolute');
        element.css('top', Math.max(0, ((jQuery(window).height() - jQuery(element).outerHeight()) / 2) +
            jQuery(window).scrollTop()) + 'px');
        element.css('left', Math.max(0, ((jQuery(window).width() - jQuery(element).outerWidth()) / 2) +
            jQuery(window).scrollLeft()) + 'px');
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
}(window.App = window.App || {}));
