<?php
/**
 * 
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */
class WCSH_Export {

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @var
	 */
	public $export_data;

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
	public function export( $type = null ) {

		if ( null !== $type ) {
			call_user_func( [ $this, $type ] );
		}
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function export_file( $type ) {

		// Convert to json and export.
		$export_json  = json_encode( $this->export_data );
		$file_handler = new WCSH_File_Handler();
		$file_handler->trigger_download( $export_json, $type );
	}
}
