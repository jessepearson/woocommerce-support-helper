<?php
/**
 * WCSH_Shipping_Import class handles importing shipping zones, methods, and settings.
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */
class WCSH_Shipping_Import {

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
	public function import( $type = null ) {

		$file_handler    = new WCSH_File_Handler();
		$this->file_data = $file_handler->get_file_data( $type );

		if ( null !== $this->file_data ) {
			call_user_func( [ $this, $type ] );
		}
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function shipping_zone_import() {
		
		// Load the data store for shipping zones.
		$this->data_store = WC_Data_Store::load( 'shipping-zone' );

		// Call each handler to handle the importing of the data.
		$this->import_shipping_classes();
		$this->import_shipping_zones();
		$this->import_shipping_settings();

		// Check to see if we need to deal with table rates.
		$this->maybe_fix_table_rates();

		// Clears all the shipping caches.
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function import_shipping_classes() {

		// Go through each shipping class being imported.
		foreach ( $this->file_data['classes'] as $class ) {
			
			// Set the proper args and insert the term.
			$args = [
				'slug'        => $class['slug'],
				'description' => $class['description'],
			];
			$term = wp_insert_term( $class['name'] . ' (imported)', $class['taxonomy'], $args );
			
			// If the term slug already exists, query that term for us to use.
			if ( is_wp_error( $term ) ) {
				//WCSH_Logger::log( 'Shipping class already exists: '. print_r( $c, true ) );
				$term = get_term_by( 'slug', $class['slug'], $class['taxonomy'], 'ARRAY_A' );
			}
			
			// Get the new term, then add both to the array for future usage.
			$term = get_term( $term['term_id'], $class['taxonomy'], 'ARRAY_A' );
			$this->shipping_classes[ $class['term_id'] ] = [
				'new'      => $term, 
				'original' => $class,
			];
		}
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function import_shipping_zones() {
		global $wpdb;

		// Go through each shipping zone being imported.
		foreach ( $this->file_data['zones'] as $izone ) {
			//WCSH_Logger::log( $izone[ 'zone_name' ] );

			// Create the new shipping zone object, set the name and the order. 
			$zone = new WC_Shipping_Zone( null );
			$zone->set_zone_name( $izone[ 'zone_name' ] .' (imported)' );
			$zone->set_zone_order( $izone['zone_order'] + 99 );

			// Go through each location and add it to the zone, then save the zone to get the id.
			foreach( $izone['zone_locations'] as $locations ) {
				$zone->add_location( $locations['code'], $locations['type'] );
			}
			$zone_id = $zone->save();

			// Now go through each of the zone's shipping methods.
			foreach( $izone['shipping_methods'] as $method ) {
				//$id = $zone->add_shipping_method( $method['id'] );

				// We set the instance settings in the object for further usage.
				$this->instance_settings = $method['instance_settings'];

				// We reset the instance_id and object each time.
				$instance_id = 0;
				$wc_shipping = WC_Shipping::instance();

				// Get the count of methods on the zone, then add the method. 
				$count       = $this->data_store->get_method_count( $zone_id );
				$instance_id = $this->data_store->add_method( $zone_id, $method['id'], $count + 1 );
				
				// If we got an instance created.
				if ( $instance_id ) {

					// Triggers other things, if needed.
					do_action( 'woocommerce_shipping_zone_method_added', $instance_id, $method['id'], $zone_id );

					// This will help us clean up settings before adding them to the database. 
					if ( method_exists( $this, 'fix_' . $method['id'] . '_settings' ) ) {
						call_user_func( [ $this, 'fix_' . $method['id'] . '_settings' ] );
					}
					
					// This adds settings for the instance under the method in the zone.
					update_option( $method['plugin_id'] . $method['id'] . '_' . $instance_id . '_settings', $this->instance_settings, 'yes' );

					// This will update global settings for the method, like FedEx and UPS. 
					if ( isset( $method['settings'] ) && 0 < count( $method['settings'] ) ) {
						update_option( $method['plugin_id'] . $method['id'] . '_settings', $method['settings'], 'yes' );
					}

					if ( isset( $method['enabled'] ) && 'no' == $method['enabled'] ) {
						//$is_enabled = absint( 'yes' === $method_data['enabled'] );
						$wpdb->update( "{$wpdb->prefix}woocommerce_shipping_zone_methods", [ 'is_enabled' => 0 ], [ 'instance_id' => absint( $instance_id ) ] );
					}


					// This allows us to correct other things, like Table Rates.
					$this->instance_ids[ $method['instance_id'] ] = $instance_id;
				}
			}
		}
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function import_shipping_settings() {

		// Go through each option and update it in the database.
		foreach ( $this->file_data['settings'] as $option => $value ) {
			update_option( $option, $value, 'yes' );
		}
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function fix_flat_rate_settings() {

		// Go through each of the instance settings. 
		foreach ( $this->instance_settings as $key => $value ) {

			// We're only looking to update the class ids.
			if ( false !== strpos( $key, 'class_cost_' ) ) {

				// Get the old class id.
				$explode = explode( '_', $key );

				// If we don't have the class/term set, it wasn't brought over, so skip adding it. 
				if ( isset( $this->shipping_classes[ $explode[2] ] ) ) {
					$new_key = 'class_cost_' . $this->shipping_classes[ $explode[2] ]['new']['term_id'];
					$this->instance_settings[ $new_key ] = $value;
				}
				
				// Remove the initial setting to prevent contamination.
				unset( $this->instance_settings[ $key ] );
			}
		}
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function maybe_fix_table_rates() {
		global $wpdb;

		// Do we have table rates?
		if ( 0 >= count( $this->file_data['table_rates'] ) ) {
			return;
		}

		// Need to create the table if it doesn't exist.
		$this->maybe_create_table_rates_table();
		
		// Go through each rule for import.
		foreach ( $this->file_data['table_rates'] as $rate ) {

			// There's a possibility that there are table rates for instances that don't exist.
			if ( ! isset( $this->instance_ids[ $rate['shipping_method_id'] ] ) ) {
				continue;
			}

			// Set all of the variables for each row.
			$rate_class                = '';
			$shipping_method_id        = $this->instance_ids[ $rate['shipping_method_id'] ];
			$rate_condition            = isset( $rate['rate_condition'] ) ? $rate['rate_condition'] : '';
			$rate_min                  = isset( $rate['rate_min'] ) ? $rate['rate_min'] : '';
			$rate_max                  = isset( $rate['rate_max'] ) ? $rate['rate_max'] : '';
			$rate_cost                 = isset( $rate['rate_cost'] ) ? $rate['rate_cost'] : '';
			$rate_cost_per_item        = isset( $rate['rate_cost_per_item'] ) ? $rate['rate_cost_per_item'] : '';
			$rate_cost_per_weight_unit = isset( $rate['rate_cost_per_weight_unit'] ) ? $rate['rate_cost_per_weight_unit'] : '';
			$rate_cost_percent         = isset( $rate['rate_cost_percent'] ) ? $rate['rate_cost_percent'] : '';
			$rate_label                = isset( $rate['rate_label'] ) ? $rate['rate_label'] : '';
			$rate_priority             = isset( $rate['rate_priority'] ) ? $rate['rate_priority'] : 0;
			$rate_order                = isset( $rate['rate_order'] ) ? $rate['rate_order'] : 0;
			$rate_abort                = isset( $rate['rate_abort'] ) ? $rate['rate_abort'] : 0;
			$rate_abort_reason         = isset( $rate['rate_abort_reason'] ) ? $rate['rate_abort_reason'] : '';

			if ( isset( $rate['rate_class'] ) && '' !== $rate['rate_class'] ) {
				$rate_class = $this->shipping_classes[ $rate['rate_class'] ]['new']['term_id'];
			}			

			//Insert row
			$result = $wpdb->insert(
				$wpdb->prefix . 'woocommerce_shipping_table_rates',
				[
					'rate_class'                => $rate_class,
					'rate_condition'            => sanitize_title( $rate_condition ),
					'rate_min'                  => $rate_min,
					'rate_max'                  => $rate_max,
					'rate_cost'                 => $rate_cost,
					'rate_cost_per_item'        => $rate_cost_per_item,
					'rate_cost_per_weight_unit' => $rate_cost_per_weight_unit,
					'rate_cost_percent'         => $rate_cost_percent,
					'rate_label'                => $rate_label,
					'rate_priority'             => $rate_priority,
					'rate_order'                => $rate_order,
					'shipping_method_id'        => $shipping_method_id,
					'rate_abort'                => $rate_abort,
					'rate_abort_reason'         => $rate_abort_reason,
				],
				[
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%d',
					'%d',
					'%d',
					'%s',
				]
			);
		}

		// Go through priorities and set them all.
		foreach ( $this->file_data['table_rate_priorities'] as $instance => $priority ) {

			// There's a possibility that there are priorities for instances that don't exist.
			if ( ! isset( $this->instance_ids[ $instance ] ) ) {
				continue;
			}

			// Set the values and update the options.
			$default = isset( $priority['default'] ) ? $priority['default'] : 10;
			$classes = isset( $priority['classes'] ) ? $priority['classes'] : [];
			update_option( 'woocommerce_table_rate_default_priority_' . $this->instance_ids[ $instance ], $default );
			update_option( 'woocommerce_table_rate_priorities_' . $this->instance_ids[ $instance ], $classes );
		}
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function maybe_create_table_rates_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}
		$sql = "
			CREATE TABLE {$wpdb->prefix}woocommerce_shipping_table_rates (
			rate_id bigint(20) NOT NULL auto_increment,
			rate_class varchar(200) NOT NULL,
			rate_condition varchar(200) NOT NULL,
			rate_min varchar(200) NOT NULL,
			rate_max varchar(200) NOT NULL,
			rate_cost varchar(200) NOT NULL,
			rate_cost_per_item varchar(200) NOT NULL,
			rate_cost_per_weight_unit varchar(200) NOT NULL,
			rate_cost_percent varchar(200) NOT NULL,
			rate_label longtext NULL,
			rate_priority int(1) NOT NULL,
			rate_order bigint(20) NOT NULL,
			shipping_method_id bigint(20) NOT NULL,
			rate_abort int(1) NOT NULL,
			rate_abort_reason longtext NULL,
			PRIMARY KEY  (rate_id)
			) $collate;
		";

		dbDelta( $sql );
	}
}
