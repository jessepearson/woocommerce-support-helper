<?php
/**
 * WCSH_Settings_Tabs_Export handles exporting settings from tabs like General and Products.
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 */
if ( ! class_exists( 'WCSH_Settings_Tabs_Export' ) ) {
	class WCSH_Settings_Tabs_Export {

		/**
		 * The instance of our class.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
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
		 * @since   1.0.0
		 * @version 1.1.1
		 */
		private function __construct() {
			add_filter( 'wcsh_export_handlers', array( $this, 'register_export_handlers' ) );
			$this->exporter = WCSH_Export::instance();
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
		 * @param   arr   $export_handlers | The current export handlers we're adding to.
		 * @return  arr   The updated array of import handlers.
		 */
		public function register_export_handlers( $export_handlers ) {

			// Add our handlers and return. 
			$export_handlers['general_tab'] = array(
				'class'  => __CLASS__,
				'method' => 'general_tab_export',
				'notice' => 'Export settings from the WooCommerce > Settings > General page.',
			);

			$export_handlers['products_tab'] = array(
				'class'  => __CLASS__,
				'method' => 'products_tab_export',
				'notice' => 'Export settings from the WooCommerce > Settings > Products pages.',
			);

			$export_handlers['tax_tab'] = array(
				'class'  => __CLASS__,
				'method' => 'tax_tab_export',
				'notice' => 'Export settings from the WooCommerce > Settings > Tax page, but not tax rates.',
			);

			$export_handlers['accounts_tab'] = array(
				'class'  => __CLASS__,
				'method' => 'accounts_tab_export',
				'notice' => 'Export settings from the WooCommerce > Settings > Accounts &amp; Privacy page, but not tax rates.',
			);

			return $export_handlers;
		}

		/**
		 * Handler for the WooCommerce > Settings > General tab.
		 *
		 * @since   1.0.0
		 * @version 1.1.1
		 * @return  arr   Array of settings.
		 */
		public function general_tab_export() {
			// Hand off to generic exporter.
			$settings = $this->exporter->generic_tab_export( 'WC_Settings_General' );
			return array( 'general_tab' => $settings );
		}

		/**
		 * Handler for the WooCommerce > Settings > Products tab.
		 *
		 * @since   1.0.0
		 * @version 1.1.1
		 * @return  arr   Array of settings.
		 */
		public function products_tab_export() {
			// Hand off to generic exporter.
			$settings = $this->exporter->generic_tab_export( 'WC_Settings_Products' );
			return array( 'products_tab' => $settings );
		}

		/**
		 * Handler for the WooCommerce > Settings > Tax tab.
		 *
		 * @since   1.0.0
		 * @version 1.1.1
		 * @return  arr   Array of settings.
		 */
		public function tax_tab_export() {

			// We need to make sure our class is available to get settings from.
			if ( ! class_exists( 'WC_Settings_Tax' ) ) {
				// This does just that.
				$settings_pages = WC_Admin_Settings::get_settings_pages();
			}

			// Create a new settings instance, and set the sections array.
			$settings_obj = new WC_Settings_Tax();
			$sections     = array( '' => 'General' );

			// Get the settings from the sections.
			$settings = $this->exporter->get_section_settings( $settings_obj, $sections );

			// Add settings to the export. 
			return array( 'tax_tab' => $settings );
		}

		/**
		 * Handler for the WooCommerce > Settings > Accounts & Privacy tab.
		 *
		 * @since   1.0.0
		 * @version 1.1.1
		 * @return  arr   Array of settings.
		 */
		public function accounts_tab_export() {
			// Hand off to generic exporter.
			$settings = $this->exporter->generic_tab_export( 'WC_Settings_Accounts' );
			return array( 'accounts_tab' => $settings );
		}
	}
	
	WCSH_Settings_Tabs_Export::instance();
}
