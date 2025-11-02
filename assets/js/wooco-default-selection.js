(function ($) {
    'use strict';

    if (typeof wooco_selected !== 'function' || typeof wooco_init !== 'function') {
        return;
    }

    var settings = window.CompositeProductsDefaultSelection || {};
    var selectAll = settings.selectAllComponents;

    if (typeof selectAll === 'string') {
        var normalized = selectAll.toLowerCase();

        if (normalized === '1' || normalized === 'true') {
            selectAll = true;
        } else if (normalized === '0' || normalized === 'false') {
            selectAll = false;
        }
    }

    selectAll = Boolean(selectAll);

    if (!selectAll) {
        return;
    }

    var SELECTOR_ITEM = '.wooco_component_product_selection_item';
    var SELECTOR_PLACEHOLDER = '.wooco_component_product_selection_list_item_choose';

    function findSelectableItems($selection) {
        return $selection
            .find(SELECTOR_ITEM)
            .not(SELECTOR_PLACEHOLDER)
            .filter(function () {
                var $item = $(this);

                if ($item.hasClass('wooco_disabled')) {
                    return false;
                }

                var id = parseInt($item.attr('data-id'), 10);

                return !isNaN(id) && id > 0;
            });
    }

    function applyDefaultSelection($wrap) {
        if (!$wrap || !$wrap.length) {
            return;
        }

        $wrap.find('.wooco_component_product').each(function () {
            var $component = $(this);

            var $selection = $component.find('.wooco_component_product_selection');

            if (!$selection.length) {
                return;
            }

            if ($selection.find(SELECTOR_ITEM + '.wooco_item_selected').length) {
                return;
            }

            var $items = findSelectableItems($selection);

            if (!$items.length) {
                return;
            }

            $items.each(function () {
                var $item = $(this);

                if ($item.hasClass('wooco_item_selected')) {
                    return;
                }

                $item.addClass('wooco_item_selected');
                wooco_selected($item, $selection, $component);
                wooco_init($wrap, 'default_select', $item);
            });
        });
    }

    function applyDefaultSelectionToAll($context) {
        var $wraps = $context ? $context : $('.wooco-wrap');

        $wraps.each(function () {
            applyDefaultSelection($(this));
        });
    }

    $(function () {
        applyDefaultSelectionToAll();
    });

    $(document).on('wooco_init', function (event, $wrap, context) {
        if (context === 'loaded' || context === 'woosq_loaded') {
            applyDefaultSelection($wrap);
        }
    });
})(jQuery);
