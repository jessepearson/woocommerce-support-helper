<?php
/**
 * WCSH_Shipping_Export class handles importing shipping zones, methods, and settings.
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 */
if ( ! class_exists( 'WCSH_Shipping_Export' ) ) {
	class WCSH_Shipping_Export {

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
		 * @version 1.1.1
		 */
		private function __construct() {
			add_filter( 'wcsh_export_handlers', array( $this, 'register_export_handlers' ) );
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
		 * @version 1.1.1
		 * @param   arr   $export_handlers The current export handlers we're adding to.
		 * @return  arr   The updated array of import handlers.
		 */
		public function register_export_handlers( $export_handlers ) {

			// Add our handlers and return. 
			$export_handlers['shipping_zones'] = array(
				'class'  => __CLASS__,
				'method' => 'shipping_zone_export',
				'notice' => 'Export the Shipping Zones, their Shipping Methods, Shipping Classes, and Shipping Options under the WooCommerce > Settings > Shipping page.',
			);

			return $export_handlers;
		}

		/**
		 * Main handler to export shipping zones, classes, and settings.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @return  arr   Array of shipping zones, classes, and settings.
		 */
		public function shipping_zone_export() {

			// Get shipping object, then classes.
			$wc_shipping = WC_Shipping::instance();
			$classes     = $wc_shipping->get_shipping_classes();

			// Get the shipping zones, throw error if nothing returned.
			$zones = WC_Shipping_Zones::get_zones( 'json' );
			if ( empty( $zones ) ) {
				throw new Exception( 'There are no zones to export.' );					
			}

			WCSH_Logger::log( count( $zones ) . ' Shipping Zones have been found.' );

			// Get the base shipping settings.
			$settings = $this->get_shipping_settings();

			$export = [ 
				'shipping_classes'  => $classes,
				'shipping_zones'    => $zones,
				'shipping_settings' => $settings,
			];

			// Check for Table Rates and add them, if needed.
			if ( $this->has_table_rates() ) {
				$export['shipping_table_rates']           = $this->get_table_rates();
				$export['shipping_table_rate_priorities'] = $this->get_table_rate_priorities( $export['shipping_table_rates'] );

				WCSH_Logger::log( 'Table Rates were found, including them.' );
			}

			return $export;
		}

		/**
		 * Gets the shipping settings from WooCommerce > Settings > Shipping.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @return  arr   Returns an array of all the shipping settings.
		 */
		public function get_shipping_settings() {

			// We need to make sure our class is available to get settings from.
			if ( ! class_exists( 'WC_Settings_Shipping' ) ) {
				// This does just that.
				$settings_pages = WC_Admin_Settings::get_settings_pages();
			}

			// Create a new shipping settings instance, and get the settings array.
			$shipping_object   = new WC_Settings_Shipping();
			$shipping_settings = $shipping_object->get_settings();

			// Go through each option and get its data from the database.
			foreach( $shipping_settings as $option ) {
				if ( 'title' !== $option['type'] && 'sectionend' !== $option['type']  ) {
					$settings[ $option['id'] ] = get_option( $option['id'], $option['default'] );
				}
			}

			return $settings;
		}

		/**
		 * Checks to see if Table Rates table exists.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @return  bool|int 0 for fail, int for success.
		 */
		public function has_table_rates() {
			global $wpdb;

			// Suppress errors and see if we have the table rates table.
			$wpdb->suppress_errors( true );
			$result = $wpdb->query( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_table_rates LIMIT 1;" );
			return $result;
		}

		/**
		 * Gets Table Rates for exporting.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @return  arr   Returns an array of all of the returned results.
		 */
		public function get_table_rates() {
			global $wpdb;

			$result = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_table_rates" );
			return $result;
		}

		/**
		 * Gets the priorites set within the Table Rates instances.
		 *
		 * @since   1.0.0
		 * @version 1.1.1
		 * @param   arr   $table_rates Array of all of the Table Rates.
		 * @return  arr   Array of the priorities for the Table Rates.
		 */
		public function get_table_rate_priorities( $table_rates ) {

			$processed  = array();
			$priorities = array();

			// Go through each Table Rate and query its priorities.
			foreach ( $table_rates as $rate ) {
				if ( ! in_array( $rate->shipping_method_id, $processed ) ) {
					$id = $rate->shipping_method_id;
					$priorities[ $id ]['default'] = get_option( 'woocommerce_table_rate_default_priority_' . $id, 10 );
					$priorities[ $id ]['classes'] = get_option( 'woocommerce_table_rate_priorities_' . $id );
				}
			}
			return $priorities;
		}
	}

	WCSH_Shipping_Export::instance();
}
