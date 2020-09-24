<?php
/**
 * 
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */
class WCSH_Payment_Import extends WCSH_Import {

	/**
	 * Constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function __construct() {

	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function payment_data_import() {
		

		// Go through each gateway being imported.
		foreach ( $this->file_data['gateways'] as $gateway => $settings ) {

			// Set the plugin_id to use for the db option.
			$plugin_id = 'woocommerce_';

			// This should never happen, but just in case. 
			if ( ! empty( $settings['plugin_id'] ) ) {
				$plugin_id = $settings['plugin_id'];
				unset( $settings['plugin_id'] );
			}

			update_option( $plugin_id . $gateway . '_settings', $settings, 'yes' );

			$notice = 'Added settings for: ' . $settings['title'];
			WCSH_Logger::log( $notice );
		}
	}
}
