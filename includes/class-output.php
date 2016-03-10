<?php
/**
 * Shopify Buy Button Output
 * @version 0.1.0
 * @package Shopify Buy Button
 */

class SBB_Output {

	/**
	 * Parent plugin class
	 *
	 * @var   class
	 * @since NEXT
	 */
	protected $plugin = null;

	/**
	 * Has the shopify js been added?
	 *
	 * @var boolean
	 * @since NEXT
	 */
	private $js_added = false;

	/**
	 * The current shop.
	 *
	 * @var boolean
	 * @since NEXT
	 */
	private $shop = false;

	/**
	 * Constructor
	 *
	 * @since  NEXT
	 * @param  object $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks
	 *
	 * @since  NEXT
	 */
	public function hooks() {
		add_action( 'cmb2_init', array( $this, 'button_endpoint' ), 30 );
		add_action( 'wp_footer', array( $this, 'embed_cart' ), 30 );
	}

	/**
	 * Convert array of attributes to string of html data attributes.
	 *
	 * @since NEXT
	 * @param  array $args Array of attributes to convert to data attributes.
	 * @return string      HTML attributes.
	 */
	public function array_to_data_attributes( $args ) {
		$attributes = '';
		foreach ( $args as $key => $value ) {
			if ( ! empty( $value ) ) {
				$attributes .= sprintf( ' data-%s="%s"', esc_html( $key ), esc_attr( $value ) );
			}
		}

		return $attributes;
	}

	/**
	 * Handle missing or multiple shop parameters.
	 *
	 * If shop parameter is missing set it to the same as the first embed on
	 * the page or the saved option. If shop parameter is different from first
	 * embed and 'redirect_to' set to cart change redirect_to to checkout.
	 *
	 * @since NEXT
	 * @param  array $args Embed arguments.
	 * @return array       Modified embed arguments.
	 */
	public function handle_shop( $args ) {
		if ( empty( $args['shop'] ) ) {
			// Is the shop param unset?
			// Assign the shop param to the same as the first embed on the page.
			$args['shop'] = empty( $this->shop ) ? get_option( 'sbb_shop', '' ) : $this->shop;
		} else if ( empty( $this->shop ) ) {
			// Is the shop param set, but no shop param stored?
			// Save the shop to an option and the class.
			$this->shop = $args['shop'];
			update_option( 'sbb_shop', $this->shop );
		}

		// Is the shop param different from the first embed's shop param?
		if ( $args['shop'] !== $this->shop && 'cart' === $args['redirect_to'] ) {
			$args['redirect_to'] = 'checkout';
		}

		return $args;
	}

	/**
	 * Get shopify embed markup.
	 *
	 * @since NEXT
	 * @param  array $args data arguments.
	 * @return string      HTML markup.
	 */
	public function get_embed( $args ) {
		$args = $this->handle_shop( $args );

		ob_start();
		?>
		<div class="sbb-embed sbb-embed-<?php echo esc_attr( $args['embed_type'] ) ?>"<?php echo $this->array_to_data_attributes( $args ); ?>></div>
		<?php

		if ( ! $this->js_added ) {
			?><script type="text/javascript"> document.getElementById('ShopifyEmbedScript') || document.write('<script type="text/javascript" src="https://widgets.shopifyapps.com/assets/widgets/embed/client.js" id="ShopifyEmbedScript"><\/script>'); </script><?php
			$this->js_added = true;
		}

		return ob_get_clean();
	}

	/**
	 * Get markup for frontend buy button iframe.
	 *
	 * @since NEXT
	 * @param  array $args Arguments for buy button.
	 * @return string      HTML markup.
	 */
	public function get_button( $args ) {
		if ( isset( $args['text_color'] ) ) {
			$args['product_title_color'] = $args['text_color'];
		}

		if ( isset( $args['background'] ) && ! $args['background'] ) {
			$args['background_color'] = 'transparent';
		}

		if ( ! empty( $args['show'] ) && 'button-only' === $args['show'] ) {
			$args['has_image'] = 'false';
		}

		/**
		 * Arguments for buy button data attributes
		 *
		 * @see https://docs.shopify.com/manual/sell-online/buy-button/edit-delete
		 * @var array
		 */
		$args = wp_parse_args( $args, array(
			// * Provided by iframe -- product/collection
			'embed_type'                          => 'product',
			// * Provided by iframe -- The myshopify domain (such as storename.myshopify.com) connected to the button. Your Shopify domain
			'shop'                                => '',
			// * Provided by iframe -- The product_handle of the featured product, which is based on the product's title. Each of your products has a unique handle in Shopify.
			'product_handle'                      => '',
			'product_name'                        => '',
			'display_size'                        => 'compact',
			'has_image'                           => 'true',
			'redirect_to'                         => cmb2_get_option( 'shopify_buy_button_appearance', 'redirect_to' ),
			'buy_button_text'                     => cmb2_get_option( 'shopify_buy_button_appearance', 'buy_button_text' ),
			'button_background_color'             => substr( cmb2_get_option( 'shopify_buy_button_appearance', 'button_background_color' ), 1 ),
			'button_text_color'                   => substr( cmb2_get_option( 'shopify_buy_button_appearance', 'button_text_color' ), 1 ),
			'background_color'                    => cmb2_get_option( 'shopify_buy_button_appearance', 'background' ) ? substr( cmb2_get_option( 'shopify_buy_button_appearance', 'background_color' ), 1 ) : 'transparent',
			'buy_button_out_of_stock_text'        => __( 'Out of Stock', 'shopify' ),
			'buy_button_product_unavailable_text' => __( 'Unavailable', 'shopify' ),
			'product_title_color'                 => substr( cmb2_get_option( 'shopify_buy_button_appearance', 'text_color' ), 1 ),
		) );

		if ( 'collection' === $args['embed_type'] ) {
			$args['redirect_to'] = 'modal';
			$args['product_modal'] = 'true';
		}

		if ( empty( $args['shop'] ) || empty( $args['product_handle'] ) ) {
			// No button if there is no product id or shop url.
			return;
		}

		if ( 'collection' === $args['embed_type'] ) {
			$args['collection_handle'] = $args['product_handle'];
		}

		// Override for whether or not to display the product price. Can be true or false.	The current value of data-has_image.
		$args['show_product_price'] = ! empty( $args['show_product_price'] ) ? $args['show_product_price'] : $args['has_image'];
		// Override for whether or not to display the product title. Can be true or false.	The current value of data-has_image.
		$args['show_product_title'] = ! empty( $args['show_product_title'] ) ? $args['show_product_title'] : $args['has_image'];

		$args = apply_filters( 'sbb_product_output_args', $args );

		return $this->get_embed( $args );
	}

	/**
	 * Get the cart embed
	 *
	 * @since NEXT
	 * @param  array $args Cart arguments.
	 * @return string      HTML embed markup.
	 */
	public function get_cart( $args ) {
		$args = wp_parse_args( $args, array(
			'shop' => '',
			'checkout_button_text' => cmb2_get_option( 'shopify_buy_button_appearance', 'checkout_button_text' ),
			'button_text_color' => substr( cmb2_get_option( 'shopify_buy_button_appearance', 'button_text_color' ), 1 ),
			'button_background_color' => substr( cmb2_get_option( 'shopify_buy_button_appearance', 'button_background_color' ), 1 ),
			'background_color' => cmb2_get_option( 'shopify_buy_button_appearance', 'background' ) ? substr( cmb2_get_option( 'shopify_buy_button_appearance', 'background_color' ), 1 ) : 'transparent',
			'text_color' => substr( cmb2_get_option( 'shopify_buy_button_appearance', 'text_color' ), 1 ),
			'accent_color' => substr( cmb2_get_option( 'shopify_buy_button_appearance', 'accent_color' ), 1 ),
			'cart_title' => cmb2_get_option( 'shopify_buy_button_appearance', 'cart_title' ),
			'cart_total_text' => '',
			'discount_notice_text' => '',
			'sticky' => '',
			'empty_cart_text' => '',
			'next_page_button_text' => '',
		) );

		$args['embed_type'] = 'cart';
		$args['sticky'] = 'true';

		$args = apply_filters( 'sbb_cart_output_args', $args );

		return $this->get_embed( $args );
	}

	/**
	 * Handle endpoint for preview elements
	 *
	 * @since NEXT
	 */
	public function button_endpoint() {
		if ( ! current_user_can( 'edit_posts' )
			|| empty( $_GET['product_handle'] ) ) {
			return;
		}

		$args = array(
			'product_handle' => sanitize_text_field( wp_unslash( $_GET['product_handle'] ) ),
		);

		$other_args = array(
			'shop',
			'embed_type',
			'buy_button_text',
			'button_background_color',
			'button_text_color',
			'background',
			'background_color',
			'text_color',
			'cart_title',
			'checkout_button_text',
			'redirect_to',
			'show',
		);

		foreach ( $other_args as $arg ) {
			if ( isset( $_GET[ $arg ] ) ) {
				if ( 'false' === $_GET[ $arg ] ) {
					$args[ $arg ] = false;
				} else {
					$args[ $arg ] = sanitize_text_field( wp_unslash( $_GET[ $arg ] ) );
				}
			}
		}

		$args = apply_filters( 'sbb_preview_args', $args );

		?>
		<style type="text/css">
		body {
			text-align: center;
		}
		.sbb-embed-product {
			position: relative;
			top: 50%;
			transform: translateY(-50%);
		}
		</style>
		<?php

		echo $this->get_button( $args );

		if ( ! empty( $_GET['show_cart'] ) ) {
			echo $this->get_cart( $args );
		}

		do_action( 'sbb_preview_output', $args );

		die();
	}

	/**
	 * Embed the cart in the footer
	 *
	 * @since NEXT
	 */
	function embed_cart() {
		echo $this->get_cart( array() );
	}
}
