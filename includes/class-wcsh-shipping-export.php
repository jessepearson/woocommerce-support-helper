<?php
/**
 * WCSH_Shipping_Export class handles importing shipping zones, methods, and settings.
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */
class WCSH_Shipping_Export {

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @var
	 */
	public $file_data;

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @var
	 */
	public $shipping_classes = [];

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @var
	 */
	public $instance_settings;

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @var
	 */
	public $instance_ids = [];

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
	public function export( $type = null ) {

		// $file_handler    = new WCSH_File_Handler();
		// $this->file_data = $file_handler->get_file_data( $type );

		if ( null !== $type ) {
			call_user_func( [ $this, $type ] );
		}
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function shipping_zone_export() {

		// Get shipping object, then classes.
		$wc_shipping = WC_Shipping::instance();
		$classes     = $wc_shipping->get_shipping_classes();

		// Get the shipping zones, throw error if nothing returned.
		$zones = WC_Shipping_Zones::get_zones( 'json' );
		if ( empty( $zones ) ) {
			$notice = 'There are no zones to export.';
			WCSH_Logger::log( $notice );
			throw new Exception( $notice );					
		}

		$notice = count( $zones ) . ' Shipping Zones have been found.';
		WCSH_Logger::log( $notice );

		// Get the base shipping settings.
		$settings = $this->get_shipping_settings();
		WCSH_Logger::log( '$settings: '. print_r( $settings, true ) );

		$export = [ 
			'classes'  => $classes,
			'zones'    => $zones,
			'settings' => $settings,
		];

		// Check for Table Rates and add them, if needed.
		if ( $this->has_table_rates() ) {
			$export['table_rates']           = $this->get_table_rates();
			$export['table_rate_priorities'] = $this->get_table_rate_priorities( $export['table_rates'] );

			$notice = 'Table Rates were found, including them.';
			WCSH_Logger::log( $notice );
		}
		
		// Convert to json and export.
		$export_json  = json_encode( $export );
		$file_handler = new WCSH_File_Handler();
		$file_handler->trigger_download( $export_json, 'shipping-zones' );
	}

	/**
	 * Gets the shipping settings from WooCommerce > Settings > Shipping.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @return  array | Returns an array of all the shipping settings.
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
	 * @return  bool|int | 0 for fail, int for success.
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
	 * @return  array | Returns an array of all of the returned results.
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
	 * @version 1.0.0
	 * @param   array $table_rates | Array of all of the Table Rates.
	 * @return  array | Array of the priorities for the Table Rates.
	 */
	public function get_table_rate_priorities( $table_rates ) {

		$processed  = [];
		$priorities = [];

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
