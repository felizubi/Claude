/* Copyright (C) YOOtheme GmbH, YOOtheme Proprietary Use License (http://www.yootheme.com/license) */

jQuery(function($) {

    var config = $('html').data('config') || {};

    $('.tm-slideset-avion').each(function() {
        var $this = $(this);

        UIkit.$win.on('load resize', function() {
            $('ul.uk-slideset li', $this).removeClass('tm-border-none').filter('.uk-active').last().addClass('tm-border-none');
        });

        $this.on('show.uk.slideset', function(e, set) {
            $(set).last().addClass('tm-border-none');
        });
    });

    // Delete grid-divider border on first item in row
    $('.uk-grid.tm-grid-divider').each(function() {
        var $this = $(this),
            items = $this.children().filter(':visible'), pos;

        if (items.length > 0) {
            pos_cache = items.first().position().left;

            UIkit.$win.on('load resize', UIkit.Utils.debounce((function(fn) {

                fn = function () {

                    items.each(function() {

                        pos = $(this).position();

                        $(this)[pos.left == pos_cache ? 'addClass':'removeClass']('tm-border-none');
                    });

                    return fn;
                }

                return fn();

            })(), 80));
        }

    });

});
