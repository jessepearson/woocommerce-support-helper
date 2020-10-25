<?php
/**
 * WCSH_Settings_Tabs_Import handles importing settings from most of the tabs under WooCommerce > Settings.
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
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
			$import_handlers['general_tab'] = array(
				'class'  => __CLASS__,
				'method' => 'general_tab_import',
				'notice' => 'This will import (overwrite) settings on the WooCommerce > Settings > General page.',
			);

			$import_handlers['products_tab'] = array(
				'class'  => __CLASS__,
				'method' => 'products_tab_import',
				'notice' => 'This will import (overwrite) settings on the WooCommerce > Settings > Products pages.',
			);

			$import_handlers['tax_tab'] = array(
				'class'  => __CLASS__,
				'method' => 'tax_tab_import',
				'notice' => 'This will import (overwrite) settings on the WooCommerce > Settings > Tax page, but does not include tax rates.',
			);

			$import_handlers['accounts_tab'] = array(
				'class'  => __CLASS__,
				'method' => 'accounts_tab_import',
				'notice' => 'This will import (overwrite) settings on the WooCommerce > Settings > Accounts &amp; Privacy page.',
			);

			return $import_handlers;
		}

		/**
		 * Handles updating settings under WooCommerce > Settings > General.
		 *
		 * @since   1.0.0
		 * @version 1.1.0
		 * @param   arr   $data The settings import array.
		 */
		public function general_tab_import( $data ) {
			// Hand off to generic handler.
			$this->importer->update_generic_settings( $data['general_tab'] );
		}

		/**
		 * Handles updating settings under WooCommerce > Settings > Products. 
		 *
		 * @since   1.0.0
		 * @version 1.1.0
		 * @param   arr   $data The settings import array.
		 */
		public function products_tab_import( $data ) {
			// Hand off to generic handler.
			$this->importer->update_generic_settings( $data['products_tab'] );
		}

		/**
		 * Handles updating settings under WooCommerce > Settings > Tax. 
		 *
		 * @since   1.0.0
		 * @version 1.1.4
		 * @param   arr   $data The settings import array.
		 */
		public function tax_tab_import( $data ) {

			// We need to make sure our class is available to get settings from.
			if ( ! class_exists( 'WC_Settings_Tax' ) ) {
				// This does just that.
				$settings_pages = WC_Admin_Settings::get_settings_pages();
			}

			// Tax classes are set separately than the rest of the settings. We use WC core methods to add them.
			$wc_tax      = new WC_Settings_Tax();
			$tax_classes = array_merge( $data['tax_tab']['woocommerce_tax_classes'], WC_Tax::get_tax_classes() );
			$wc_tax->save_tax_classes( implode( "\n", $tax_classes ) );

			// We no longer need this, and the default seems to be empty so, empty.
			$data['tax_tab']['woocommerce_tax_classes'] = '';

			// Hand off to generic handler.
			$this->importer->update_generic_settings( $data['tax_tab'] );
		}

		/**
		 * Handles updating settings under WooCommerce > Settings > Accounts & Privacy. 
		 *
		 * @since   1.0.0
		 * @version 1.1.0
		 * @param   arr   $data The settings import array.
		 */
		public function accounts_tab_import( $data ) {
			// Hand off to generic handler.
			$this->importer->update_generic_settings( $data['accounts_tab'] );
		}
	}

	WCSH_Settings_Tabs_Import::instance();
}
