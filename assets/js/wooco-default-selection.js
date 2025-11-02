(function ($) {
    'use strict';

    if (typeof wooco_selected !== 'function' || typeof wooco_init !== 'function') {
        return;
    }

    var SELECTOR_ITEM = '.wooco_component_product_selection_item';
    var SELECTOR_PLACEHOLDER = '.wooco_component_product_selection_list_item_choose';

    function findDefaultItem($selection) {
        return $selection
            .find(SELECTOR_ITEM)
            .not(SELECTOR_PLACEHOLDER)
            .not('.wooco_item_selected')
            .filter(function () {
                var $item = $(this);

                if ($item.hasClass('wooco_disabled')) {
                    return false;
                }

                var id = parseInt($item.attr('data-id'), 10);

                return !isNaN(id) && id > 0;
            })
            .first();
    }

    function applyDefaultSelection($wrap) {
        if (!$wrap || !$wrap.length) {
            return;
        }

        $wrap.find('.wooco_component_product').each(function () {
            var $component = $(this);

            if ($component.attr('data-multiple') === 'yes') {
                return;
            }

            var $selection = $component.find('.wooco_component_product_selection');

            if (!$selection.length) {
                return;
            }

            if ($selection.find(SELECTOR_ITEM + '.wooco_item_selected').length) {
                return;
            }

            var $defaultItem = findDefaultItem($selection);

            if (!$defaultItem.length) {
                return;
            }

            $defaultItem.addClass('wooco_item_selected');
            wooco_selected($defaultItem, $selection, $component);
            wooco_init($wrap, 'default_select', $defaultItem);
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
