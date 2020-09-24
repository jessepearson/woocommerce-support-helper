<?php
/**
 * 
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */
class WCSH_Payment_Export extends WCSH_Export {

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
	public function payment_data_export() {

		// Get all of the payment gateways.
		$gateways = WC()->payment_gateways->payment_gateways();
		$settings = [];
		
		// Log how many we have.
		$notice = count( $gateways ) . ' Payment Gateways have been found.';
		WCSH_Logger::log( $notice );

		// Get each gateway's settings.
		foreach ( $gateways as $gateway ) {
			if ( isset( $gateway->settings ) && 0 < count( $gateway->settings ) ) {
				$settings[ $gateway->id ] = $gateway->settings;

				if ( 'woocommerce_' !== $gateway->plugin_id ) {
					$settings[ $gateway->id ]['import_plugin_id'] = $gateway->plugin_id;
				}
			}
		}

		$this->export_data = [ 
			'gateways' => $settings,
		];

		$this->export_file( 'payment-gateways' );
	}
}
