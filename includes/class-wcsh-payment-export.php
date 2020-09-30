<?php
/**
 * WCSH_Payment_Export handles exporting the payment gateways.
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 */
if ( ! class_exists( 'WCSH_Payment_Export' ) ) {
	class WCSH_Payment_Export {

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
			add_filter( 'wcsh_export_handlers', [ $this, 'register_export_handlers' ] );
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
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Registers our export handlers for this class.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @param   arr   $export_handlers | The current export handlers we're adding to.
		 * @return  arr   The updated array of import handlers.
		 */
		public function register_export_handlers( $export_handlers ) {

			// Add our handlers and return. 
			$export_handlers['gateways'] = [
				'class'  => __CLASS__,
				'method' => 'payment_data_export',
				'notice' => 'Export the payment gateways from the WooCommerce > Settings > Payments page.',
			];

			return $export_handlers;
		}

		/**
		 * Handler for exporting the payment data.
		 *
		 * @since   1.0.0
		 * @version 1.1.0
		 * @return  arr   Array of payment gateways and their settings.
		 */
		public function payment_data_export() {

			// Get all of the payment gateways.
			$gateways = WC()->payment_gateways->payment_gateways();
			$settings = [];
			
			// Log how many we have.
			WCSH_Logger::log( count( $gateways ) . ' Payment Gateways have been found.' );

			// Get each gateway's settings.
			foreach ( $gateways as $gateway ) {
				if ( isset( $gateway->settings ) && 0 < count( $gateway->settings ) ) {
					$settings[ $gateway->id ] = $gateway->settings;

					if ( 'woocommerce_' !== $gateway->plugin_id ) {
						$settings[ $gateway->id ]['import_plugin_id'] = $gateway->plugin_id;
					}
				}
			}

			return [ 'gateways' => $settings ];
		}
	}

	WCSH_Payment_Export::instance();
}