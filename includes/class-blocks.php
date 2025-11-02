<?php
defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Class for integrating with WooCommerce Blocks
 */
class WPCleverWooco_Blocks_IntegrationInterface implements IntegrationInterface {
	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wooco-blocks';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		wp_enqueue_style(
			'wooco-blocks',
			$this->get_url( 'blocks', 'css' ),
			[],
			WOOCO_VERSION
		);

		wp_register_script(
			'wooco-blocks',
			$this->get_url( 'blocks', 'js' ),
			[ 'wc-blocks-checkout' ],
			WOOCO_VERSION,
			true
		);

		wp_set_script_translations(
			'wooco-blocks',
			'wpc-composite-products',
			WOOCO_DIR . 'languages'
		);
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return [ 'wooco-blocks' ];
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return [];
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		return [];
	}

	public function get_url( $file, $ext ) {
		return plugins_url( $this->get_path( $ext ) . $file . '.' . $ext, WOOCO_FILE );
	}

	protected function get_path( $ext ) {
		return 'css' === $ext ? 'assets/css/' : 'assets/js/';
	}
}

if ( ! class_exists( 'WPCleverWooco_Blocks' ) ) {
	class WPCleverWooco_Blocks {
		function __construct() {
			add_filter( 'rest_request_after_callbacks', [ $this, 'cart_item_data' ], 10, 3 );
			add_filter( 'woocommerce_hydration_request_after_callbacks', [ $this, 'cart_item_data' ], 10, 3 );
			add_action(
				'woocommerce_blocks_mini-cart_block_registration',
				function ( $integration_registry ) {
					$integration_registry->register( new WPCleverWooco_Blocks_IntegrationInterface() );
				}
			);
			add_action(
				'woocommerce_blocks_cart_block_registration',
				function ( $integration_registry ) {
					$integration_registry->register( new WPCleverWooco_Blocks_IntegrationInterface() );
				}
			);
			add_action(
				'woocommerce_blocks_checkout_block_registration',
				function ( $integration_registry ) {
					$integration_registry->register( new WPCleverWooco_Blocks_IntegrationInterface() );
				}
			);
		}

		function cart_item_data( $response, $server, $request ) {
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			if ( ! str_contains( $request->get_route(), 'wc/store' ) ) {
				return $response;
			}

			$data = $response->get_data();

			if ( empty( $data['items'] ) ) {
				return $response;
			}

			$cart_contents       = WC()->cart->get_cart();
			$hide_composite_name = WPCleverWooco()::get_setting( 'hide_composite_name', 'no' ) !== 'no';
			$hide_component      = WPCleverWooco()::get_setting( 'hide_component', 'no' ) !== 'no';

			foreach ( $data['items'] as &$item_data ) {
				$cart_item_key = $item_data['key'];
				$cart_item     = $cart_contents[ $cart_item_key ] ?? null;

				if ( ! empty( $cart_item['wooco_ids'] ) ) {
					$item_data['wooco_composite'] = true;
				}

				if ( ! empty( $cart_item['wooco_parent_id'] ) ) {
					$item_data['wooco_component']           = true;
					$item_data['quantity_limits']->editable = false;

					if ( ! $hide_composite_name ) {
						$item_data['name'] = get_the_title( $cart_item['wooco_parent_id'] ) . apply_filters( 'wooco_name_separator', ' &rarr; ' ) . $item_data['name'];
					}

					if ( $hide_component ) {
						$item_data['wooco_hide_component'] = true;
					}
				}
			}

			$response->set_data( $data );

			return $response;
		}
	}

	new WPCleverWooco_Blocks();
}