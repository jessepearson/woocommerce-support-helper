<?php
/**
 * WCSH_Product_Filters_Import handles importing Product Filters.
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.1.4
 */
if ( ! class_exists( 'WCSH_Product_Filters_Import' ) ) {
	class WCSH_Product_Filters_Import {

		/**
		 * The instance of our class.
		 *
		 * @since   1.1.4
		 * @version 1.1.4
		 * @var
		 */
		private static $instance = null;

		/**
		 * The instance of our importer.
		 *
		 * @since   1.1.4
		 * @version 1.1.4
		 * @var
		 */
		public $importer = null;

		/**
		 * Constructor.
		 *
		 * @since   1.1.4
		 * @version 1.1.4
		 */
		private function __construct() {
			add_filter( 'wcsh_import_handlers', array( $this, 'register_import_handlers' ) );
			$this->importer = WCSH_Import::instance();
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
		 * Registers our import handlers for this class.
		 *
		 * @since   1.1.4
		 * @version 1.1.4
		 * @param   arr   $import_handlers The current import handlers we're adding to.
		 * @return  arr   The updated array of import handlers.
		 */
		public function register_import_handlers( $import_handlers ) {

			// Add our handlers and return. 
			$import_handlers['product_filters'] = array(
				'class'  => __CLASS__,
				'method' => 'product_filters_import',
				'notice' => 'This will import Product Filters.',
			);

			return $import_handlers;
		}

		/**
		 * Handles inporting Product Filters.
		 *
		 * @since   1.1.4
		 * @version 1.1.4
		 * @param   arr   $data The settings import array.
		 */
		public function product_filters_import( $data ) {

			// We use this to add items later.
			$processed = array();
			
			// Go through each filter and add it.
			foreach( $data['product_filters']['filters'] as $filter ) {
				$args = array( 
					'post_title'  => $filter['post_title'],
					'post_status' => $filter['post_status'],
					'post_name'   => $filter['post_name'],
					'post_type'   => $filter['post_type'],
				);
				$id = wp_insert_post( $args );

				// If there's no error...
				if ( 0 !== $id ) {
					// Add the meta data for the filter post.
					foreach( $data['product_filters']['filter_meta'][ $filter['ID'] ] as $meta_key => $meta_value ) {
						$updated = update_post_meta( $id, $meta_key, $meta_value[0] );
					}

					// We add the old and new ids to the processed array.
					$processed[ $filter['ID'] ] = $id;

				} else {
					// If there was an error, log it.
					WCSH_Logger::log( 'Inserting of Product Filter post failed: ' . print_r( $args, true ) );
				}
			}

			// Go through each item and add it.
			foreach( $data['product_filters']['items'] as $item ) {
				// If for some reason the filter post wasn't add it, don't add it's items.
				if ( ! isset( $processed[ $item['post_parent'] ] ) ) {
					continue;
				}

				$args = array( 
					'post_title'  => $item['post_title'],
					'post_status' => $item['post_status'],
					'post_name'   => $item['post_name'],
					'post_type'   => $item['post_type'],
					'menu_order'  => $item['menu_order'],
					'post_parent' => $processed[ $item['post_parent'] ],
				);
				$id = wp_insert_post( $args );

				// If there's no error...
				if ( 0 !== $id ) {
					// Add the meta data for the filter post.
					foreach( $data['product_filters']['item_meta'][ $item['ID'] ] as $meta_key => $meta_value ) {
						$updated = update_post_meta( $id, $meta_key, $meta_value[0] );
					}
				} else {
					// If there was an error, log it.
					WCSH_Logger::log( 'Inserting of Product Filter Item post failed: ' . print_r( $args, true ) );
				}
			}
		}
	}

	WCSH_Product_Filters_Import::instance();
}
