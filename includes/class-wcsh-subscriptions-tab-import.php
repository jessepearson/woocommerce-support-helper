<?php
/**
 * WCSH_Subscriptions_Tab_Import handles importing settings under WooCommerce > Settings > Subscriptions.
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.1.0
 */
if ( ! class_exists( 'WCSH_Subscriptions_Tab_Import' ) ) {
	class WCSH_Subscriptions_Tab_Import {

		/**
		 * The instance of our class.
		 *
		 * @since   1.1.0
		 * @version 1.1.0
		 * @var
		 */
		private static $instance = null;

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
		 * @since   1.1.0
		 * @version 1.1.1
		 */
		private function __construct() {
			add_filter( 'wcsh_import_handlers', array( $this, 'register_import_handlers' ) );
			$this->importer = WCSH_Import::instance();
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
		 * Registers our import handlers for this class.
		 *
		 * @since   1.1.0
		 * @version 1.1.1
		 * @param   arr   $import_handlers The current import handlers we're adding to.
		 * @return  arr   The updated array of import handlers.
		 */
		public function register_import_handlers( $import_handlers ) {

			// Add our handlers and return. 
			$import_handlers['subscriptions_tab'] = array(
				'class'  => __CLASS__,
				'method' => 'subscriptions_tab_import',
				'notice' => 'This will import (overwrite) settings on the WooCommerce > Settings > Subscriptions page.',
			);

			return $import_handlers;
		}

		/**
		 * Handles updating settings under WooCommerce > Settings > Subscriptions.
		 *
		 * @since   1.1.0
		 * @version 1.1.0
		 * @param   arr   $data The settings import array.
		 */
		public function subscriptions_tab_import( $data ) {
			// Hand off to generic handler.
			$this->importer->update_generic_settings( $data['subscriptions_tab'] );
		}
	}

	WCSH_Subscriptions_Tab_Import::instance();
}
