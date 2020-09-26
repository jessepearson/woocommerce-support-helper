<?php
/**
 * 
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */
class WCSH_Payment_Import {

	/**
	 * The instance of our class.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @var
	 */
	private static $instance = null;

	/**
	 * Constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	private function __construct() {
		add_filter( 'wcsh_import_handlers', [ $this, 'register_import_handlers' ] );
	}

	/**
	 * Creates and returns instance of the class.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @return  obj   Instance of our class.
	 */
	public static function instance() {
		if( ! self::$instance ) {
			self::$instance = new WCSH_Payment_Import();
		}

		return self::$instance;
	}

	/**
	 * Registers our import handlers for this class.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param   arr   $import_handlers | The current import handlers we're adding to.
	 * @return  arr   The updated array of import handlers.
	 */
	public function register_import_handlers( $import_handlers ) {

		// Add our handlers and return. 
		$import_handlers['gateways'] = [
			'class'  => 'WCSH_Payment_Import',
			'method' => 'payment_data_import',
			'notice' => 'This will import (overwrite) Payment Method settings.',
		];

		return $import_handlers;
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function payment_data_import( $data ) {
		

		// Go through each gateway being imported.
		foreach ( $data['gateways'] as $gateway => $settings ) {

			// Set the plugin_id to use for the db option.
			$plugin_id = 'woocommerce_';

			// This should never happen, but just in case. 
			if ( ! empty( $settings['plugin_id'] ) ) {
				$plugin_id = $settings['plugin_id'];
				unset( $settings['plugin_id'] );
			}

			// Update the option and add log it.
			update_option( $plugin_id . $gateway . '_settings', $settings, 'yes' );

			$notice = 'Added settings for: ' . $gateway . ' / ' . $settings['title'];
			WCSH_Logger::log( $notice );
		}
	}
}

WCSH_Payment_Import::instance();