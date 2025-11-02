<?php
/**
 * Plugin Name:       Composite Products Enhancements
 * Description:       Adds utilities for WPC Composite Products, including default selection controls.
 * Requires at least: 5.8
 * Requires PHP:      7.2
 * Version:           1.0.0
 * Author:            ChatGPT
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       composite-products
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Composite_Products_Enhancements' ) ) {
    class Composite_Products_Enhancements {
        private const OPTION_NAME      = 'composite_products_select_all_components';
        private const SETTINGS_PAGE    = 'composite-products-default-selection';
        private const SCRIPT_HANDLE    = 'composite-products-default-selection';
        private const OPTION_ENABLED   = 'yes';
        private const OPTION_DISABLED  = 'no';

        /**
         * Holds the plugin singleton instance.
         *
         * @var Composite_Products_Enhancements|null
         */
        private static $instance = null;

        /**
         * Returns the singleton instance.
         *
         * @return Composite_Products_Enhancements
         */
        public static function instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Bootstrap the plugin.
         */
        private function __construct() {
            add_action( 'admin_init', [ $this, 'register_settings' ] );
            add_action( 'admin_menu', [ $this, 'register_menu' ] );
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        }

        /**
         * Register the option that controls the default selection behaviour.
         */
        public function register_settings() {
            register_setting(
                self::SETTINGS_PAGE,
                self::OPTION_NAME,
                [
                    'type'              => 'string',
                    'sanitize_callback' => [ $this, 'sanitize_checkbox_value' ],
                    'default'           => self::OPTION_DISABLED,
                ]
            );

            add_settings_section(
                'composite_products_default_selection_section',
                '',
                '__return_false',
                self::SETTINGS_PAGE
            );

            add_settings_field(
                'composite_products_select_all_components_field',
                __( 'Selecionar todos os itens por padrão', 'composite-products' ),
                [ $this, 'render_checkbox_field' ],
                self::SETTINGS_PAGE,
                'composite_products_default_selection_section'
            );
        }

        /**
         * Register the submenu entry.
         */
        public function register_menu() {
            $parent_slug = 'options-general.php';
            $capability  = 'manage_options';

            if ( class_exists( 'WooCommerce' ) ) {
                $parent_slug = 'woocommerce';
                $capability  = 'manage_woocommerce';
            }

            add_submenu_page(
                $parent_slug,
                __( 'Composite Products', 'composite-products' ),
                __( 'Composite Products', 'composite-products' ),
                $capability,
                self::SETTINGS_PAGE,
                [ $this, 'render_settings_page' ]
            );
        }

        /**
         * Render the plugin settings page.
         */
        public function render_settings_page() {
            ?>
            <div class="wrap">
                <h1><?php esc_html_e( 'Composite Products', 'composite-products' ); ?></h1>
                <form method="post" action="options.php">
                    <?php
                    settings_fields( self::SETTINGS_PAGE );
                    do_settings_sections( self::SETTINGS_PAGE );
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }

        /**
         * Render the checkbox field.
         */
        public function render_checkbox_field() {
            $value = get_option( self::OPTION_NAME, self::OPTION_DISABLED );
            ?>
            <label for="<?php echo esc_attr( self::OPTION_NAME ); ?>">
                <input
                    type="checkbox"
                    name="<?php echo esc_attr( self::OPTION_NAME ); ?>"
                    id="<?php echo esc_attr( self::OPTION_NAME ); ?>"
                    value="<?php echo esc_attr( self::OPTION_ENABLED ); ?>"
                    <?php checked( self::OPTION_ENABLED, $value ); ?>
                />
                <?php esc_html_e( 'Selecionar automaticamente todos os itens disponíveis ao carregar o componente.', 'composite-products' ); ?>
            </label>
            <?php
        }

        /**
         * Sanitize the checkbox value into a "yes" or "no" string.
         *
         * @param mixed $value Raw value coming from the request.
         *
         * @return string
         */
        public function sanitize_checkbox_value( $value ) {
            return empty( $value ) ? self::OPTION_DISABLED : self::OPTION_ENABLED;
        }

        /**
         * Enqueue the front-end helper.
         */
        public function enqueue_scripts() {
            if ( ! class_exists( 'WPCleverWooco' ) ) {
                return;
            }

            $should_select_all = ( self::OPTION_ENABLED === get_option( self::OPTION_NAME, self::OPTION_DISABLED ) );

            if ( ! $should_select_all ) {
                return;
            }

            $script_path = 'assets/js/wooco-default-selection.js';
            $full_path   = plugin_dir_path( __FILE__ ) . $script_path;

            wp_enqueue_script(
                self::SCRIPT_HANDLE,
                plugins_url( $script_path, __FILE__ ),
                [ 'jquery' ],
                file_exists( $full_path ) ? filemtime( $full_path ) : false,
                true
            );

            wp_localize_script(
                self::SCRIPT_HANDLE,
                'CompositeProductsDefaultSelection',
                [
                    'selectAllComponents' => true,
                ]
            );
        }
    }
}

add_action( 'plugins_loaded', [ 'Composite_Products_Enhancements', 'instance' ] );
