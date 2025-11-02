<?php
/**
 * Plugin Name: WPC Composite Defaults Enhancer
 * Description: Adds opinionated defaults for WPC Composite Products by automatically selecting all component products and hiding the selection button.
 * Version: 1.0.0
 * Author: Codex
 * License: GPL2
 * Text Domain: wpc-composite-defaults
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPC_Composites_Defaults_Enhancer' ) ) {
    class WPC_Composites_Defaults_Enhancer {
        public function __construct() {
            add_action( 'plugins_loaded', [ $this, 'bootstrap' ] );
        }

        public function bootstrap() {
            if ( ! class_exists( 'WPCleverWooco' ) ) {
                return;
            }

            add_filter( 'wooco_component_default', [ $this, 'maybe_select_first_product' ], 20, 2 );
            add_filter( 'wooco_component_product_selected', [ $this, 'mark_multiple_components_selected' ], 20, 3 );
            add_filter( 'wooco_component_checkbox_checked', [ $this, 'check_component_checkbox' ], 20, 2 );
            add_filter( 'wooco_components_data_attributes', [ $this, 'add_components_data_attributes' ], 20, 2 );
            add_filter( 'wooco_components_class', [ $this, 'add_components_class' ], 20, 2 );
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ], 20 );
        }

        public function maybe_select_first_product( $default, $component ) {
            if ( $this->has_request_override() ) {
                return $default;
            }

            if ( $this->is_multiple_component( $component ) ) {
                return $default;
            }

            if ( absint( $default ) > 0 ) {
                return $default;
            }

            $products = $this->get_component_products( $component, $default );

            if ( empty( $products ) ) {
                return $default;
            }

            $first_product = reset( $products );

            if ( ! is_array( $first_product ) || empty( $first_product['id'] ) ) {
                return $default;
            }

            return absint( $first_product['id'] );
        }

        public function mark_multiple_components_selected( $selected, $component_product, $component ) {
            if ( $this->has_request_override() ) {
                return $selected;
            }

            if ( $this->is_multiple_component( $component ) ) {
                return true;
            }

            return $selected;
        }

        public function check_component_checkbox( $checked, $component ) {
            if ( $this->has_request_override() ) {
                return $checked;
            }

            return true;
        }

        public function add_components_data_attributes( $attributes ) {
            $attributes['select-all-components'] = 'yes';
            $attributes['hide-selection-button'] = 'yes';

            return $attributes;
        }

        public function add_components_class( $classes, $product ) {
            return $classes . ' wooco-components-hide-selection-button';
        }

        public function enqueue_assets() {
            $css = '.wooco-components-hide-selection-button .wooco_component_product_selection_list_item_choose { display: none !important; }';

            if ( wp_style_is( 'wooco-frontend', 'enqueued' ) ) {
                wp_add_inline_style( 'wooco-frontend', $css );
            } else {
                wp_register_style( 'wooco-defaults-inline', false );
                wp_enqueue_style( 'wooco-defaults-inline' );
                wp_add_inline_style( 'wooco-defaults-inline', $css );
            }
        }

        private function get_component_products( $component, $default ) {
            $type        = $component['type'] ?? 'products';
            $source      = $type === 'products' ? ( $component['products'] ?? [] ) : ( $component['other'] ?? [] );
            $orderby     = (string) ( $component['orderby'] ?? 'default' );
            $order       = (string) ( $component['order'] ?? 'default' );
            $exclude     = $component['exclude'] ?? [];
            $qty         = isset( $component['qty'] ) ? (float) $component['qty'] : 1;
            $price       = isset( $component['price'] ) ? WPCleverWooco::format_price( $component['price'] ) : '';
            $custom_qty  = isset( $component['custom_qty'] ) && $component['custom_qty'] === 'yes';

            return WPCleverWooco::instance()->get_products( $type, $source, $orderby, $order, $exclude, $default, $qty, $price, $custom_qty );
        }

        private function has_request_override() {
            return ! empty( $_GET['edit'] ) || ! empty( $_GET['df'] );
        }

        private function is_multiple_component( $component ) {
            return isset( $component['multiple'] ) && $component['multiple'] === 'yes';
        }
    }

    new WPC_Composites_Defaults_Enhancer();
}
