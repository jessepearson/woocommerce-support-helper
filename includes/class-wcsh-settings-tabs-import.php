<?php
/**
 * 
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */
class WCSH_Settings_Tabs_Import extends WCSH_Import {

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
	public function general_tab_import() {
		
		// Go through each gateway being imported.
		foreach ( $this->file_data['general_tab'] as $option => $value ) {

			update_option( $option, $value, 'yes' );

			// $notice = 'Added settings for: ' . $settings['title'];
			// WCSH_Logger::log( $notice );
		}
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function products_tab_import() {
		
		// Go through each gateway being imported.
		foreach ( $this->file_data['products_tab'] as $option => $value ) {

			update_option( $option, $value, 'yes' );

			// $notice = 'Added settings for: ' . $settings['title'];
			// WCSH_Logger::log( $notice );
		}
	}
}
