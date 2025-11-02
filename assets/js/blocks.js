const woocoCheckoutFilters = window.wc.blocksCheckout.registerCheckoutFilters;

const woocoCartItemClass = (defaultValue, extensions, args) => {
    if (args?.cartItem?.wooco_composite) {
        defaultValue += ' wooco-composite';
    }

    if (args?.cartItem?.wooco_component) {
        defaultValue += ' wooco-component';
    }

    if (args?.cartItem?.wooco_hide_component) {
        defaultValue += ' wooco-hide-component';
    }

    return defaultValue;
};

const woocoShowRemoveItemLink = (defaultValue, extensions, args) => {
    const isCartContext = args?.context === 'cart';

    if (!isCartContext) {
        return defaultValue;
    }

    if (args?.cartItem?.wooco_component) {
        return false;
    }

    return defaultValue;
};

woocoCheckoutFilters('wooco-blocks', {
    cartItemClass: woocoCartItemClass, showRemoveItemLink: woocoShowRemoveItemLink,
});