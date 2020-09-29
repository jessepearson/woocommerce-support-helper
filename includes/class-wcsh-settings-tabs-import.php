<?php
/**
 * WCSH_Settings_Tabs_Import handles importing settings from most of the tabs under WooCommerce > Settings.
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */
if ( ! class_exists( 'WCSH_Settings_Tabs_Import' ) ) {
	class WCSH_Settings_Tabs_Import {

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
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Registers our import handlers for this class.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @param   arr   $import_handlers The current import handlers we're adding to.
		 * @return  arr   The updated array of import handlers.
		 */
		public function register_import_handlers( $import_handlers ) {

			// Add our handlers and return. 
			$import_handlers['general_tab'] = [
				'class'  => __CLASS__,
				'method' => 'general_tab_import',
				'notice' => 'This will import (overwrite) settings on the WooCommerce > Settings > General page.',
			];

			$import_handlers['products_tab'] = [
				'class'  => __CLASS__,
				'method' => 'products_tab_import',
				'notice' => 'This will import (overwrite) settings on the WooCommerce > Settings > Products pages.',
			];

			$import_handlers['tax_tab'] = [
				'class'  => __CLASS__,
				'method' => 'tax_tab_import',
				'notice' => 'This will import (overwrite) settings on the WooCommerce > Settings > Tax page, but does not include tax rates.',
			];

			$import_handlers['accounts_tab'] = [
				'class'  => __CLASS__,
				'method' => 'accounts_tab_import',
				'notice' => 'This will import (overwrite) settings on the WooCommerce > Settings > Accounts &amp; Privacy page.',
			];

			return $import_handlers;
		}

		/**
		 * Updates generic settings in the database via update_option.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @param   arr   $data The sub-array from the settings import array.
		 */
		public function update_generic_settings( $data ) {
			
			// Go through each setting being imported.
			foreach ( $data as $option => $value ) {

				// Update option in the database.
				update_option( $option, $value, 'yes' );

				// Log what was updated. 
				$notice = 'Updated setting option: ' . $option;
				WCSH_Logger::log( $notice );
			}
		}

		/**
		 * Handles updating settings under WooCommerce > Settings > General.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @param   arr   $data The settings import array.
		 */
		public function general_tab_import( $data ) {
			// Hand off to generic handler.
			$this->update_generic_settings( $data['general_tab'] );
		}

		/**
		 * Handles updating settings under WooCommerce > Settings > Products. 
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @param   arr   $data The settings import array.
		 */
		public function products_tab_import( $data ) {
			// Hand off to generic handler.
			$this->update_generic_settings( $data['products_tab'] );
		}

		/**
		 * Handles updating settings under WooCommerce > Settings > Tax. 
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @param   arr   $data The settings import array.
		 */
		public function tax_tab_import( $data ) {
			// Hand off to generic handler.
			$this->update_generic_settings( $data['tax_tab'] );
		}

		/**
		 * Handles updating settings under WooCommerce > Settings > Accounts & Privacy. 
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @param   arr   $data The settings import array.
		 */
		public function accounts_tab_import( $data ) {
			// Hand off to generic handler.
			$this->update_generic_settings( $data['accounts_tab'] );
		}
	}

	// add_action( 'plugins_loaded', 'WCSH_Settings_Tabs_Import::instance' );
	WCSH_Settings_Tabs_Import::instance();
}