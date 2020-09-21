<?php
/**
 * WCSH_Dependencies class handles dependencies for the plugin.
 *
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */
class WCSH_Dependencies {

	/**
	 * Plugin's filename.
	 *
	 * @var string
	 */
	public $plugin_filename;

	/**
	 * Constructor.
	 * 
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param   string $plugin_filename The plugin's main file name.
	 */
	public function __construct( $plugin_filename ) {
		$this->plugin_filename = $plugin_filename;
	}
	
	/**
	 * Function to check if Bookings is active.
	 * 
	 * @since   1.0.0
	 * @version 1.0.0
	 * @return  bool
	 */
	public function is_bookings_active() {
		return class_exists( 'WC_Bookings' );
	}

	/**
	 * Function to deactivate plugin, if needed.
	 * 
	 * @since   1.0.0
	 * @version 1.0.0
	 * @return  bool
	 */
	public function maybe_deactivate_plugin() {
		
		// Check if Bookings is active.
		if ( ! $this->is_bookings_active() ) {

			// If not, we deactivate the plugin.
			if ( function_exists( 'deactivate_plugins' ) ) {
				deactivate_plugins( plugin_basename( $this->plugin_filename ) );
			}

			// And add a notice that we've deactivated it.
			add_action( 'admin_notices', [ $this, 'deactivation_notice' ] );

			return true;
		}

		return false;
	}

	/**
	 * Function to display deactivation notice.
	 * 
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function deactivation_notice() {
		$error_message = __( 'Instructor Helper For WooCommerce Bookings requires WooCommerce Bookings to be active.', 'woocommerce-support-helper' );
		echo wp_kses_post( sprintf( '<div class="error">%s %s</div>', wpautop( $error_message ), wpautop( 'Plugin <strong>deactivated</strong>.' ) ) );
	}
}
