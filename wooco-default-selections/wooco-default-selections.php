<?php
/**
 * Plugin Name: WPC Composite Defaults Enhancer
 * Description: Adds opinionated defaults for WPC Composite Products by automatically selecting all component products and hiding the selection button.
 * Version: 1.1.0
 * Author: Codex
 * License: GPL2
 * Text Domain: wpc-composite-defaults
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPC_Composites_Defaults_Enhancer' ) ) {
    class WPC_Composites_Defaults_Enhancer {
        private $select_all_components = false;
        private $hide_selection_button = false;

        public function __construct() {
            add_action( 'plugins_loaded', [ $this, 'bootstrap' ] );
        }

        public function bootstrap() {
            if ( ! class_exists( 'WPCleverWooco' ) ) {
                return;
            }

            $settings                     = get_option( 'wooco_settings', [] );
            $this->select_all_components  = ( $settings['select_all_components'] ?? 'no' ) === 'yes';
            $this->hide_selection_button  = ( $settings['hide_selection_button'] ?? 'no' ) === 'yes';

            add_action( 'admin_footer', [ $this, 'inject_admin_settings_fields' ] );

            add_filter( 'wooco_components_data_attributes', [ $this, 'filter_components_data_attributes' ], 20, 2 );
            add_filter( 'wooco_components_class', [ $this, 'filter_components_class' ], 20, 2 );

            if ( $this->select_all_components ) {
                add_filter( 'wooco_component_default', [ $this, 'maybe_select_first_product' ], 20, 2 );
                add_filter( 'wooco_component_product_selected', [ $this, 'mark_multiple_components_selected' ], 20, 3 );
                add_filter( 'wooco_component_checkbox_checked', [ $this, 'check_component_checkbox' ], 20, 2 );
            }

            if ( $this->hide_selection_button ) {
                add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ], 20 );
            }
        }

        public function inject_admin_settings_fields() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            $page = sanitize_key( $_GET['page'] ?? '' );

            if ( $page !== 'wpclever-wooco' ) {
                return;
            }

            $tab = sanitize_key( $_GET['tab'] ?? 'settings' );

            if ( $tab !== 'settings' ) {
                return;
            }

            $settings                = get_option( 'wooco_settings', [] );
            $select_all_value        = $settings['select_all_components'] ?? 'no';
            $hide_selection_value    = $settings['hide_selection_button'] ?? 'no';

            ob_start();
            ?>
            <tr class="wooco_settings_select_all_components">
                <th><?php esc_html_e( 'Select component products by default', 'wpc-composite-products' ); ?></th>
                <td>
                    <label>
                        <select name="wooco_settings[select_all_components]">
                            <option value="yes" <?php selected( $select_all_value, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-composite-products' ); ?></option>
                            <option value="no" <?php selected( $select_all_value, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-composite-products' ); ?></option>
                        </select>
                    </label>
                    <span class="description"><?php esc_html_e( 'Automatically preselect every product inside each component when the composite loads.', 'wpc-composite-products' ); ?></span>
                </td>
            </tr>
            <?php
            $select_all_row = ob_get_clean();

            ob_start();
            ?>
            <tr class="wooco_settings_hide_selection_button">
                <th><?php esc_html_e( 'Hide selection button', 'wpc-composite-products' ); ?></th>
                <td>
                    <label>
                        <select name="wooco_settings[hide_selection_button]">
                            <option value="yes" <?php selected( $hide_selection_value, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-composite-products' ); ?></option>
                            <option value="no" <?php selected( $hide_selection_value, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-composite-products' ); ?></option>
                        </select>
                    </label>
                    <span class="description"><?php esc_html_e( 'Hide the circular selection button that appears before component products in list layouts.', 'wpc-composite-products' ); ?></span>
                </td>
            </tr>
            <?php
            $hide_button_row = ob_get_clean();
            ?>
            <script>
                ( function( $ ) {
                    var $table = $( '.wpclever_settings_page_content form table.form-table' );

                    if ( ! $table.length ) {
                        return;
                    }

                    var $checkedRow = $table.find( 'select[name="wooco_settings[checked]"]' ).closest( 'tr' );

                    if ( ! $checkedRow.length ) {
                        return;
                    }

                    var selectAllRow = <?php echo wp_json_encode( $select_all_row ); ?>;
                    var hideButtonRow = <?php echo wp_json_encode( $hide_button_row ); ?>;

                    if ( $table.find( '.wooco_settings_select_all_components' ).length === 0 ) {
                        $checkedRow.after( selectAllRow );
                    }

                    if ( $table.find( '.wooco_settings_hide_selection_button' ).length === 0 ) {
                        $table.find( '.wooco_settings_select_all_components' ).after( hideButtonRow );
                    }
                } )( jQuery );
            </script>
            <?php
        }

        public function filter_components_data_attributes( $attributes, $product ) {
            $attributes['select-all-components'] = $this->select_all_components ? 'yes' : 'no';

            if ( $this->hide_selection_button ) {
                $attributes['hide-selection-button'] = 'yes';
            }

            return $attributes;
        }

        public function filter_components_class( $classes, $product ) {
            if ( $this->hide_selection_button ) {
                $classes .= ' wooco-components-hide-selection-button';
            }

            return $classes;
        }

        public function maybe_select_first_product( $default, $component ) {
            if ( ! $this->select_all_components || $this->has_request_override() ) {
                return $default;
            }

            $component_multiple = isset( $component['multiple'] ) && $component['multiple'] === 'yes';

            if ( $component_multiple ) {
                return $default;
            }

            $default_id = absint( $component['default'] ?? $default );

            if ( $default_id > 0 ) {
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
            if ( ! $this->select_all_components || $this->has_request_override() ) {
                return $selected;
            }

            $component_multiple = isset( $component['multiple'] ) && $component['multiple'] === 'yes';

            if ( ! $component_multiple ) {
                return $selected;
            }

            return true;
        }

        public function check_component_checkbox( $checked, $component ) {
            if ( ! $this->select_all_components || $this->has_request_override() ) {
                return $checked;
            }

            return true;
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
            $type       = $component['type'] ?? 'products';
            $source     = $type === 'products' ? ( $component['products'] ?? [] ) : ( $component['other'] ?? [] );
            $orderby    = (string) ( $component['orderby'] ?? 'default' );
            $order      = (string) ( $component['order'] ?? 'default' );
            $exclude    = $component['exclude'] ?? [];
            $qty        = isset( $component['qty'] ) ? (float) $component['qty'] : 1;
            $price      = isset( $component['price'] ) ? WPCleverWooco::format_price( $component['price'] ) : '';
            $custom_qty = isset( $component['custom_qty'] ) && $component['custom_qty'] === 'yes';

            return WPCleverWooco::instance()->get_products( $type, $source, $orderby, $order, $exclude, $default, $qty, $price, $custom_qty );
        }

        private function has_request_override() {
            return ! empty( $_GET['edit'] ) || ! empty( $_GET['df'] );
        }
    }

    new WPC_Composites_Defaults_Enhancer();
}
