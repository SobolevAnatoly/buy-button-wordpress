<?php
/**
 * Shopify Buy Button Modal
 * @version 0.1.0
 * @package Shopify Buy Button
 */

class SBB_Modal {
	/**
	 * Parent plugin class
	 *
	 * @var   class
	 * @since 0.1.0
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 *
	 * @since  0.1.0
	 * @param  object $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Get the buy button creation modal
	 *
	 * @since 0.1.0
	 * @return string HTML markup of modal.
	 */
	public function get_modal() {
		$iframe_url = 'https://widgets.shopifyapps.com/embed_admin/embeds/picker';

		$site = get_option( 'sbb-connected-site', false );
		if ( $site ) {
			$iframe_url = add_query_arg( 'shop', $site, $iframe_url );
		}

		$iframe_url = apply_filters( 'sbb_modal_iframe_url', $iframe_url, $site );

		ob_start();
		?>
		<div class="sbb-modal-wrap">
			<div class="sbb-modal">
				<div class="sbb-modal-close"><div class="screen-reader-text"><?php esc_attr_e( 'Close', 'shopify-buy-button' ); ?></div></div>
				<iframe src="<?php echo esc_url( $iframe_url ); ?>" frameborder="0" class="sbb-modal-iframe"></iframe>
				<div class="sbb-modal-secondpage">
					<label><input class="sbb-show" type="radio" name="sbb-show" value="all"> <?php esc_html_e( 'Product image, price and button', 'shopify-buy-button' ); ?></label>
					<label><input class="sbb-show" type="radio" name="sbb-show" value="button-only"> <?php esc_html_e( 'Buy button only', 'shopify-buy-button' ); ?></label>
					<button class="sbb-modal-add-button"><?php esc_html_e( 'Add Button', 'shopify-buy-button' ); ?></button>
				</div>
			</div>
			<div class="sbb-modal-background"></div>
		</div>
		<?php
		return ob_get_clean();
	}
}
