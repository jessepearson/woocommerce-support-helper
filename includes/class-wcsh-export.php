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
	 * The instance of our class.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @var
	 */
	private static $instance = null;

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @var
	 */
	public $export_data = [];

	/**
	 * Our handlers for handling the different export types.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @var
	 */
	public $export_handlers = null;

	/**
	 * Types of exports that have been confirmed.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @var
	 */
	public $confirmed_exports = null;	

	/**
	 * Constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function __construct() {

	}

	/**
	 * Creates and returns instance of the class.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @return  obj   Intance of our class.
	 */
	public static function instance() {
		if( ! self::$instance ) {
			self::$instance = new WCSH_Export();
		}

		return self::$instance;
	}

	/**
	 * Gets our export handlers.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @return  
	 */
	public function get_export_handlers() {

		// If they are already set, return them.
		if ( null !== $this->export_handlers ) {
			return $this->export_handlers;
		}

		// Use the filter to get registered handlers and return them. 
		$this->export_handlers = apply_filters( 'wcsh_export_handlers', [] );
		return $this->export_handlers;
	}

	/**
	 * Checks to see if there's an handler for the setting type.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function has_export_handler( $type = null ) {

		// Get the handler names.
		$export_handlers = array_keys( $this->get_export_handlers() );

		// Return if we have a handler or not. 
		if ( in_array( $type, $export_handlers ) ) {
			return true;
		}
		
		WCSH_Logger::log( 'No export handler found for type of: '. $type );
		return false;
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function export() {

		// Go through each type in the file.
		foreach ( $this->confirmed_exports as $type ) {
			
			// Check to see if there's a handler.
			if ( $this->has_export_handler( $type ) ) {
				
				// Hand off for processing.
				$this->handle_export( $type );
			}
		}

		// And now export it.
		$this->export_file();
	}

	/**
	 * Hands off to the export handlers for the different settings.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function handle_export( $type = null ) {

		// Shouldn't get here without a type, but throw error if we do. 
		if ( null === $type ) {
			throw new Exception( 'Null type found in handle_export.' );
		}

		// Set the class and method.
		$class  = $this->export_handlers[ $type ]['class'];
		$method = $this->export_handlers[ $type ]['method'];

		// Make sure our class exists.
		if ( ! class_exists( $class ) ) {
			throw new Exception( 'The ' . $class . ' class does not exist.' );
		}

		// Get our handler object, make sure the method exists.
		$handler = $class::instance();
		if ( ! method_exists( $handler, $method ) ) {
			throw new Exception( 'The ' . $method . ' method does not exist in the ' . $class . 'class.' );
		}
		
		// Handle the export, merge the returned data.
		$export = $handler->$method();
		$this->export_data = array_merge( $this->export_data, $export );
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function export_file() {

		// Convert to json and export.
		$export_json  = json_encode( $this->export_data );
		$file_handler = new WCSH_File_Handler();
		$file_handler->trigger_download( $export_json, 'woocommerce-support-helper-export' );
	}
}

WCSH_Export::instance();