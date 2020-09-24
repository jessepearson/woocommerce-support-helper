<?php
/**
 * 
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */
class WCSH_Settings_Tabs_Export extends WCSH_Export {

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
	public function general_tab_export() {

		// We need to make sure our class is available to get settings from.
		if ( ! class_exists( 'WC_Settings_General' ) ) {
			// This does just that.
			$settings_pages = WC_Admin_Settings::get_settings_pages();
		}

		// Create a new settings instance, and get the settings array.
		$settings_obj = new WC_Settings_General();
		$sections     = $settings_obj->get_sections();
		$settings     = [];

		if ( 0 === count( $sections ) ) {
			$sections = [ '' => 'General' ];
		}

		WCSH_Logger::log( 'sections: ' . print_r( $sections, true ) );

		foreach ( $sections as $section => $title ) {
			$settings_arr = $settings_obj->get_settings( $section );

			// Go through each option and get its data from the database.
			foreach( $settings_arr as $option ) {
				if ( 'title' !== $option['type'] && 'sectionend' !== $option['type']  ) {
					$settings[ $option['id'] ] = get_option( $option['id'], $option['default'] );
				}
			}
		}
		
		WCSH_Logger::log( 'settings: ' . print_r( $settings, true ) );

		$this->export_data = [ 
			'general_tab' => $settings,
		];

		$this->export_file( 'general-tab-settings' );
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function products_tab_export() {

		// We need to make sure our class is available to get settings from.
		if ( ! class_exists( 'WC_Settings_Products' ) ) {
			// This does just that.
			$settings_pages = WC_Admin_Settings::get_settings_pages();
		}

		// Create a new settings instance, and get the settings array.
		$settings_obj = new WC_Settings_Products();
		$sections     = $settings_obj->get_sections();
		$settings     = [];

		if ( 0 === count( $sections ) ) {
			$sections = [ '' => 'General' ];
		}

		WCSH_Logger::log( 'sections: ' . print_r( $sections, true ) );

		foreach ( $sections as $section => $title ) {
			$settings_arr = $settings_obj->get_settings( $section );

			// Go through each option and get its data from the database.
			foreach( $settings_arr as $option ) {
				if ( 'title' !== $option['type'] && 'sectionend' !== $option['type']  ) {
					$settings[ $option['id'] ] = get_option( $option['id'], $option['default'] );
				}
			}
		}
		
		WCSH_Logger::log( 'settings: ' . print_r( $settings, true ) );

		$export = [ 
			'products_tab' => $settings,
		];

		// Convert to json and export.
		$export_json  = json_encode( $export );
		$file_handler = new WCSH_File_Handler();
		$file_handler->trigger_download( $export_json, 'products-tab-settings' );
	}
}
