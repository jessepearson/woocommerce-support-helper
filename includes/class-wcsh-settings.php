<?php
/**
 * WCSH_Settings class handles settings for the plugin.
 *
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */
class WCSH_Settings {

	/**
	 * Constructor.
	 * 
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ], 99 );
	}
	
	/**
	 * Function to add the helper option to the Bookings menu in the admin.
	 * 
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function admin_menu() {
		add_submenu_page( 
			'woocommerce',
			__( 'Support Helper', 'woocommerce-support-helper' ),
			__( 'Support Helper', 'woocommerce-support-helper' ),
			'manage_options',
			'wcsh_settings',
			[ $this, 'wcsh_settings' ]
			);
	}

	/**
	 * Renders the settings page.
	 * 
	 * @since   1.0.0
	 * @version 1.0.0 
	 */
	public function wcsh_settings() {

		?>
		<div class="wrap">
			<style>

				#wcsh_settings label {
					font-size: 1.2em;
					font-weight: bold;
					display: inline-block;
					min-width: 10em;
					line-height: 1.2em;
				}

				#wcsh_settings span {
					font-size: 1.1em;
				}
			</style>
		<?php
		// If they cannot manage options, they shouldn't be here. 
		if ( ! current_user_can( 'manage_options' ) ) {
			esc_attr_e( 'Sorry, you are not allowed to access this page.', 'woocommerce-support-helper' );
		} else {

		// Process anything that was saved.
		$this->process_settings();

		?>
			<h2><?php _e( 'WooCommerce Support Helper', 'woocommerce-support-helper' ); ?></h2>
			<div id="content">
				<form method="post" action="" id="wcsh_settings">
					<div id="poststuff">
						<div class="inside">
							<p class="form-field wcsh_logging_enabled_field">
								<label for="wcsh_logging_enabled"><?php _e( 'Enable logging', 'woocommerce-support-helper' ); ?></label>
								<input type="checkbox" class="checkbox" style="" name="wcsh_logging_enabled" id="wcsh_logging_enabled" value="yes" <?php checked( 'yes', get_option( 'wcsh_logging_enabled', false ), true );?>> 
								<span class="description"><?php _e( 'Enables logging for debugging purposes.', 'woocommerce-support-helper' ); ?></span>
							</p>

						</div>
					</div>
					<p class="submit">
						<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'woocommerce-support-helper' ); ?>" />
						<?php wp_nonce_field( 'submit_wcsh_settings', 'submit_wcsh_settings_nonce' ); ?>
					</p>
				</form>
			</div>
		<?php } ?>
		</div>
		<?php
	}

	/**
	 * Processes the settings that were saved.
	 * 
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	private function process_settings() {
		// We need to check a lot before saving.
		if (
			isset( $_POST['Submit'] )
			&& isset( $_POST['submit_wcsh_settings_nonce'] )
			&& wp_verify_nonce( wc_clean( wp_unslash( $_POST['submit_wcsh_settings_nonce'] ) ), 'submit_wcsh_settings' )
			&& current_user_can( 'manage_options' )
		) {
			// Save the field values.
			$logging = ( null === $_POST['wcsh_logging_enabled'] ) ? 'no' : 'yes';
			update_option( 'wcsh_logging_enabled', $logging );
		}
	}
}

new WCSH_Settings();
