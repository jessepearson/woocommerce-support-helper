<?php
/**
 * 
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */
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
	 * Constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	private function __construct() {
		add_filter( 'wcsh_export_handlers', [ $this, 'register_export_handlers' ] );
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
			self::$instance = new WCSH_Settings_Tabs_Export();
		}

		return self::$instance;
	}

	/**
	 * Registers our export handlers for this class.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param   arr   $export_handlers | The current export handlers we're adding to.
	 * @return  arr   The updated array of import handlers.
	 */
	public function register_export_handlers( $export_handlers ) {

		// Add our handlers and return. 
		$export_handlers['general_tab'] = [
			'class'  => __CLASS__,
			'method' => 'general_tab_export',
			'notice' => 'Export settings from the WooCommerce > Settings > General page.',
		];

		$export_handlers['products_tab'] = [
			'class'  => __CLASS__,
			'method' => 'products_tab_export',
			'notice' => 'Export settings from the WooCommerce > Settings > Products pages.',
		];

		$export_handlers['tax_tab'] = [
			'class'  => __CLASS__,
			'method' => 'tax_tab_export',
			'notice' => 'Export settings from the WooCommerce > Settings > Tax page, but not tax rates.',
		];

		$export_handlers['accounts_tab'] = [
			'class'  => __CLASS__,
			'method' => 'accounts_tab_export',
			'notice' => 'Export settings from the WooCommerce > Settings > Accounts &amp; Privacy page, but not tax rates.',
		];

		return $export_handlers;
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function generic_tab_export( $class ) {

		// We need to make sure our class is available to get settings from.
		if ( ! class_exists( $class ) ) {
			// This does just that.
			$settings_pages = WC_Admin_Settings::get_settings_pages();
		}

		// Create a new settings instance, and get the sections array.
		$settings_obj = new $class();
		$sections     = $settings_obj->get_sections();
		$settings     = [];

		// If no sections returned, add a general one.
		if ( 0 === count( $sections ) ) {
			$sections = [ '' => 'General' ];
		}

		// Get settings from each section and return them.
		$settings = $this->get_section_settings( $settings_obj, $sections );
		return $settings;
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function get_section_settings( $settings_obj, $sections ) {

		// Go through each section and get settings.
		foreach ( $sections as $section => $title ) {
			$settings_arr = $settings_obj->get_settings( $section );

			// Go through each option and get its data from the database.
			foreach( $settings_arr as $option ) {

				// We skip certain ones we don't need.
				if ( 'title' !== $option['type'] && 'sectionend' !== $option['type']  ) {
					$settings[ $option['id'] ] = get_option( $option['id'], $option['default'] );
				}
			}
		}

		return $settings;
	}


	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function general_tab_export() {

		$settings = $this->generic_tab_export( 'WC_Settings_General' );
		return [ 'general_tab' => $settings ];
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function products_tab_export() {

		$settings = $this->generic_tab_export( 'WC_Settings_Products' );
		return [ 'products_tab' => $settings ];
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function tax_tab_export() {

		// We need to make sure our class is available to get settings from.
		if ( ! class_exists( 'WC_Settings_Tax' ) ) {
			// This does just that.
			$settings_pages = WC_Admin_Settings::get_settings_pages();
		}

		// Create a new settings instance, and get the sections array.
		$settings_obj = new WC_Settings_Tax();
		$sections     = [ '' => 'General' ];

		// Get the settings from the sections.
		$settings = $this->get_section_settings( $settings_obj, $sections );

		// Add settings to the export. 
		return [ 'tax_tab' => $settings ];
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function accounts_tab_export() {

		$settings = $this->generic_tab_export( 'WC_Settings_Accounts' );
		return [ 'accounts_tab' => $settings ];
	}
}
WCSH_Settings_Tabs_Export::instance();