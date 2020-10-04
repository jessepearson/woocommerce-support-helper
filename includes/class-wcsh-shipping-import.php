<?php
/**
 * WCSH_Shipping_Import class handles importing shipping zones, methods, and settings.
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 */
if ( ! class_exists( 'WCSH_Shipping_Import' ) ) {
	class WCSH_Shipping_Import {

		/**
		 * The instance of our class.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @var
		 */
		private static $instance = null;

		/**
		 * Shipping classes.
		 *
		 * @since   1.0.0
		 * @version 1.1.1
		 * @var
		 */
		public $shipping_classes = array();

		/**
		 * Shipping method instance settings.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @var
		 */
		public $instance_settings;

		/**
		 * Shippin gmethod instance ids.
		 *
		 * @since   1.0.0
		 * @version 1.1.1
		 * @var
		 */
		public $instance_ids = array();

		/**
		 * The instance of our importer.
		 *
		 * @since   1.1.0
		 * @version 1.1.0
		 * @var
		 */
		public $importer = null;

		/**
		 * Constructor.
		 *
		 * @since   1.0.0
		 * @version 1.1.1
		 */
		private function __construct() {
			add_filter( 'wcsh_import_handlers', array( $this, 'register_import_handlers' ) );
			$this->importer = WCSH_Import::instance();
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
		 * Registers our import handlers for this class.
		 *
		 * @since   1.0.0
		 * @version 1.1.1
		 * @param   arr   $import_handlers The current import handlers we're adding to.
		 * @return  arr   The updated array of import handlers.
		 */
		public function register_import_handlers( $import_handlers ) {

			// Add our handlers and return. 
			$import_handlers['shipping_zones'] = array(
				'class'  => __CLASS__,
				'method' => 'shipping_zone_import',
				'notice' => 'This will import Shipping Zones (append) and their methods, Shipping Classes (append), and Shipping Options (overwrite).',
			);

			return $import_handlers;
		}

		/**
		 * Main handler to import shipping zones, classes, and settings. 
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @param   arr   $data Array of data from import file.
		 */
		public function shipping_zone_import( $data ) {


			// Set the data so the object can use it.
			$this->file_data = $data;
			
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
		 * Handler for importing the shipping classes from the data.
		 *
		 * @since   1.0.0
		 * @version 1.1.1
		 */
		public function import_shipping_classes() {

			// Go through each shipping class being imported.
			foreach ( $this->file_data['shipping_classes'] as $class ) {
				
				// Set the proper args and insert the term.
				$args = array(
					'slug'        => $class['slug'],
					'description' => $class['description'],
				);
				$term = wp_insert_term( $class['name'] . ' (imported)', $class['taxonomy'], $args );
				
				// If the term slug already exists, query that term for us to use.
				if ( is_wp_error( $term ) ) {
					//WCSH_Logger::log( 'Shipping class already exists: '. print_r( $c, true ) );
					$term = get_term_by( 'slug', $class['slug'], $class['taxonomy'], 'ARRAY_A' );
				}
				
				// Get the new term, then add both to the array for future usage.
				$term = get_term( $term['term_id'], $class['taxonomy'], 'ARRAY_A' );
				$this->shipping_classes[ $class['term_id'] ] = array(
					'new'      => $term, 
					'original' => $class,
				);

				WCSH_Logger::log( 'Shipping class added: '. $class['name'] );
			}
		}

		/**
		 * Handler for importing the shipping zones.
		 *
		 * @since   1.0.0
		 * @version 1.1.1
		 */
		public function import_shipping_zones() {
			global $wpdb;

			// Go through each shipping zone being imported.
			foreach ( $this->file_data['shipping_zones'] as $izone ) {

				// Create the new shipping zone object, set the name and the order. 
				$zone = new WC_Shipping_Zone( null );
				$zone->set_zone_name( $izone[ 'zone_name' ] . ' (imported)' );
				$zone->set_zone_order( $izone['zone_order'] + 99 );
				WCSH_Logger::log( 'Shipping zone added: '. $izone[ 'zone_name' ] . ' (imported)' );

				// Go through each location and add it to the zone, then save the zone to get the id.
				foreach( $izone['zone_locations'] as $locations ) {
					$zone->add_location( $locations['code'], $locations['type'] );
					WCSH_Logger::log( 'Location added to zone: '. $locations['code'] . ' ' . $locations['type'] );
				}
				$zone_id = $zone->save();

				// Now go through each of the zone's shipping methods.
				foreach( $izone['shipping_methods'] as $method ) {

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
							call_user_func( array( $this, 'fix_' . $method['id'] . '_settings' ) );
						}
						
						// This adds settings for the instance under the method in the zone.
						update_option( $method['plugin_id'] . $method['id'] . '_' . $instance_id . '_settings', $this->instance_settings, 'yes' );

						// This will update global settings for the method, like FedEx and UPS. 
						if ( isset( $method['settings'] ) && 0 < count( $method['settings'] ) ) {
							update_option( $method['plugin_id'] . $method['id'] . '_settings', $method['settings'], 'yes' );
						}

						// Enable the instance, if enabled.
						if ( isset( $method['enabled'] ) && 'no' == $method['enabled'] ) {
							$wpdb->update( 
								"{$wpdb->prefix}woocommerce_shipping_zone_methods",
								array( 'is_enabled' => 0 ),
								array( 'instance_id' => absint( $instance_id ) )
							);
						}

						// This allows us to correct other things, like Table Rates.
						$this->instance_ids[ $method['instance_id'] ] = $instance_id;

						// Log what's been added.
						WCSH_Logger::log( 'Method added to zone: '. $instance_id . ' ' . $method['id'] );
					}
				}
			}
		}

		/**
		 * Imports the settings under WooCommerce > Settings > Shipping > Shipping Options.
		 *
		 * @since   1.0.0
		 * @version 1.1.0
		 */
		public function import_shipping_settings() {
			// Hand off to generic handler.
			$this->importer->update_generic_settings( $this->file_data['shipping_settings'] );
		}

		/**
		 * Fixes flat rate settings, meaning it set the proper rates to new classes.
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
		 * Will add table rates, if they are found.
		 *
		 * @since   1.0.0
		 * @version 1.1.1
		 */
		public function maybe_fix_table_rates() {
			global $wpdb;

			// Do we have table rates?
			if ( 0 >= count( $this->file_data['shipping_table_rates'] ) ) {
				return;
			}

			// Need to create the table if it doesn't exist.
			$this->maybe_create_table_rates_table();
			
			// Go through each rule for import.
			foreach ( $this->file_data['shipping_table_rates'] as $rate ) {

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

				// Insert row.
				$result = $wpdb->insert(
					$wpdb->prefix . 'woocommerce_shipping_table_rates',
					array(
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
					),
					array(
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
					)
				);

				WCSH_Logger::log( 'Table rates have been added to method id: ' . $shipping_method_id );
			}

			// Go through priorities and set them all.
			foreach ( $this->file_data['shipping_table_rate_priorities'] as $instance => $priority ) {

				// There's a possibility that there are priorities for instances that don't exist.
				if ( ! isset( $this->instance_ids[ $instance ] ) ) {
					continue;
				}

				// Set the values and update the options.
				$default = isset( $priority['default'] ) ? $priority['default'] : 10;
				$classes = isset( $priority['classes'] ) ? $priority['classes'] : array();
				update_option( 'woocommerce_table_rate_default_priority_' . $this->instance_ids[ $instance ], $default );
				update_option( 'woocommerce_table_rate_priorities_' . $this->instance_ids[ $instance ], $classes );
			}
		}

		/**
		 * Creates table rates table if needed.
		 * Taken from Table Rates extension.
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

			$result = dbDelta( $sql );

			if ( 0 < count( $result ) ) {
				WCSH_Logger::log( 'Table rates table created.' );
			}
		}
	}

	WCSH_Shipping_Import::instance();
}
