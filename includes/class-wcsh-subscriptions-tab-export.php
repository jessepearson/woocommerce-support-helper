<?php
/**
 * WCSH_Subscriptions_Tab_Export handles exporting settings from WooCommerce > Settings > Subscriptions.
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.1.0
 */
if ( ! class_exists( 'WCSH_Subscriptions_Tab_Export' ) ) {
	class WCSH_Subscriptions_Tab_Export extends WCSH_Settings_Tabs_Export {

		/**
		 * The instance of our class.
		 *
		 * @since   1.1.0
		 * @version 1.1.0
		 * @var
		 */
		private static $instance = null;

		/**
		 * The instance of our exporter.
		 *
		 * @since   1.1.0
		 * @version 1.1.0
		 * @var
		 */
		public $exporter = null;

		/**
		 * Constructor.
		 *
		 * @since   1.1.0
		 * @version 1.1.0
		 */
		private function __construct() {
			add_filter( 'wcsh_export_handlers', [ $this, 'register_export_handlers' ] );
			$this->exporter = WCSH_Export::instance();
		}

		/**
		 * Creates and returns instance of the class.
		 *
		 * @since   1.1.0
		 * @version 1.1.0
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
		 * @since   1.1.0
		 * @version 1.1.0
		 * @param   arr   $export_handlers | The current export handlers we're adding to.
		 * @return  arr   The updated array of import handlers.
		 */
		public function register_export_handlers( $export_handlers ) {

			// Add our handlers and return. 
			$export_handlers['subscriptions_tab'] = [
				'class'  => __CLASS__,
				'method' => 'subscriptions_tab_export',
				'notice' => 'Export settings from the WooCommerce > Settings > Subscriptions page.',
			];

			return $export_handlers;
		}

		/**
		 * Handler for the WooCommerce > Settings > General tab.
		 *
		 * @since   1.1.0
		 * @version 1.1.0
		 * @return  arr   Array of settings.
		 */
		public function subscriptions_tab_export() {
			
			$settings = $this->exporter->generic_tab_export( 'WC_Subscriptions_Admin' );
			return [ 'subscriptions_tab' => $settings ];
		}
	}
	
	WCSH_Subscriptions_Tab_Export::instance();
}