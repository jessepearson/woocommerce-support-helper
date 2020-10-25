<?php
/**
 * WCSH_Product_Filters_Export handles exporting Product Filters.
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.1.4
 */
if ( ! class_exists( 'WCSH_Product_Filters_Export' ) ) {
	class WCSH_Product_Filters_Export {

		/**
		 * The instance of our class.
		 *
		 * @since   1.1.4
		 * @version 1.1.4
		 * @var
		 */
		private static $instance = null;

		/**
		 * The instance of our exporter.
		 *
		 * @since   1.1.4
		 * @version 1.1.4
		 * @var
		 */
		public $exporter = null;

		/**
		 * Constructor.
		 *
		 * @since   1.1.4
		 * @version 1.1.4
		 */
		private function __construct() {
			add_filter( 'wcsh_export_handlers', array( $this, 'register_export_handlers' ) );
			$this->exporter = WCSH_Export::instance();
		}

		/**
		 * Creates and returns instance of the class.
		 *
		 * @since   1.1.4
		 * @version 1.1.4
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
		 * @since   1.1.4
		 * @version 1.1.4
		 * @param   arr   $export_handlers | The current export handlers we're adding to.
		 * @return  arr   The updated array of import handlers.
		 */
		public function register_export_handlers( $export_handlers ) {

			// Add our handlers and return. 
			$export_handlers['product_filters'] = array(
				'class'  => __CLASS__,
				'method' => 'product_filters_export',
				'notice' => 'Export Product Filters from WooCommerce > Filters.',
			);

			return $export_handlers;
		}

		/**
		 * Handler for Product Filters from WooCommerce > Filters.
		 *
		 * @since   1.1.4
		 * @version 1.1.4
		 * @return  arr   Array of settings.
		 */
		public function product_filters_export() {
			
			// Set args for PF post type.
			$args = array(
				'numberposts' => -1,
				'post_type'   => 'wcpf_project',
			);

			// Query the filters and set up our meta array.
			$filters     = get_posts( $args );
			$filter_meta = array();

			// Go through each PF and get its meta.
			foreach( $filters as $filter ) {
				$filter_meta[ $filter->ID ] = get_post_meta( $filter->ID );
			}

			// Set args for PF Item post type.
			$args = array(
				'numberposts' => -1,
				'post_type'   => 'wcpf_item',
			);

			// Query the items and set up our meta array.
			$items     = get_posts( $args );
			$item_meta = array();

			// Go through each PF item and get its meta.
			foreach( $items as $item ) {
				$item_meta[ $item->ID ] = get_post_meta( $item->ID );
			}

			// Set our return array and return.
			$product_filters = array(
				'filters'     => $filters,
				'filter_meta' => $filter_meta,
				'items'       => $items,
				'item_meta'   => $item_meta,
			);

			return array( 'product_filters' => $product_filters );
		}
	}
	
	WCSH_Product_Filters_Export::instance();
}
