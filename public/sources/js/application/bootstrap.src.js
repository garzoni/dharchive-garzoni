$(document).ready(function() {
    'use strict';

    var menu = new App.SideMenu({
        id: 'main-menu'
    });

    App.storage = {};

    /* Page Header */

    $('#pagelet-header').find('.ui.dropdown').dropdown({
        transition: 'fade up',
        on: 'hover'
    });

    $('.main-menu-toggler').on('click', function() {
        if (menu.isExpanded) {
            menu.collapse();
        } else {
            menu.expand();
        }
    });

    /* Main Content */

    $('#main-content').find('.ui.dropdown').dropdown({
        transition: 'fade up'
    });

    $('.box .header > .tools .close').on('click', function() {
        var target = $(this).closest('.ui.segment.box');
        if ((target.siblings().length === 0)
            && (target.closest('.column').siblings().length === 0)) {
            target = target.closest('.row');
        }
        target.slideUp(250, function() {
            $(this).remove();
        });
    });

    $('.box .header .ui.dropdown').dropdown({
        action: 'hide'
    });

    $('.box .header > .menu .item').tab();

    $('.ui.accordion').accordion({
        exclusive: false
    });

    $('.message .close').on('click', function() {
        $(this).closest('.message').transition('fade');
    });

    $('.tooltipped').popup({
        variation: 'tiny wide'
    });

    $('.popup-target').popup({
        variation: 'tiny wide',
        inline: true
    });

    $('.lazy-load').visibility({
        type: 'image',
        transition: 'fade in',
        duration: 1000
    });

    document.addEventListener('contextmenu', function(e) {
        if (e.target.tagName === 'IMG' || e.target.tagName === 'CANVAS') {
          e.preventDefault();
        }
    });

    document.addEventListener('dragstart', function (e) {
      if (e.target.tagName === 'IMG') {
        e.preventDefault();
      }
    });
});
