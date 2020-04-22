(function($, undefined) {
    'use strict';

    /**
     * @class Validator
     * @classdesc
     *
     * @memberof Seanote
     */
    $.Validator = function(criteria) {
        this.criteria = Array.isArray(criteria) ? criteria : [];
        this.initialize();
    };

    $.Validator.prototype = {
        initialize: function() {
            for (var i = 0; i < this.criteria.length; i++) {
                this.checkCriterion(this.criteria[i]);
            }
        },

        checkCriterion: function(criterion) {
            if (!Array.isArray(criterion) || (criterion.length !== 3)) {
                throw new Error(
                    $.getText('msg_invalid_filter_criterion')
                );
            }
        },

        addCriterion: function(criterion) {
            this.checkCriterion(criterion);
            this.criteria.push(criterion);
        },

        removeCriteria: function(start, deleteCount) {
            this.criteria.splice(start, deleteCount);
        },

        test: function(obj) {
            var criterion = [],
                value, i;
            for (i = 0; i < this.criteria.length; i++) {
                criterion = this.criteria[i];
                value = this.getPropertyValue(obj, criterion[0]);
                if (typeof value === 'function') {
                    value = value();
                }
                if (!this.compare(criterion[1], value, criterion[2])) {
                    return false;
                }
            }
            return true;
        },

        compare: function(op, a, b) {
            switch (op) {
                case '===':
                    return a === b;
                case '!==':
                    return a !== b;
                case '>':
                    return a > b;
                case '<':
                    return a < b;
                case '>=':
                    return a >= b;
                case '<=':
                    return a <= b;
                case '==':
                    return a == b;
                case '!=':
                    return a != b;
                default:
                    throw new Error(
                        $.getText('msg_undefined_comparison_operator')
                    );
            }
        },

        getPropertyValue: function(obj, prop) {
            return prop.split('.').reduce(function(o, p) {
                return o[p];
            }, obj);
        }
    };

}(Seanote));
