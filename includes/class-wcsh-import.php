<?php
/**
 * 
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */
class WCSH_Import {

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @var
	 */
	public $file_data;

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
	public function import( $type = null ) {

		$file_handler    = new WCSH_File_Handler();
		$this->file_data = $file_handler->get_file_data( $type );

		if ( null !== $this->file_data ) {
			call_user_func( [ $this, $type ] );
		}
	}
}
