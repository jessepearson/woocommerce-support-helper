<?php
/**
 * WCSH_Export is our main export class. It handles the export feature and retrieves the data from the registered handlers.
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */
if ( ! class_exists( 'WCSH_Export' ) ) {
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
		 * Data that is going to be exported. 
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
		 * @return  obj   InStance of our class.
		 */
		public static function instance() {
			if( ! self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Gets our export handlers.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @return  arr   Returns an array of the registered export handlers.
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
		 * @param   str   $type The type that's being checked.
		 * @return  bool  True or false based on if the type has an export handler.
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
		 * Goes through confirmed exports, gets their data, then hands off to export the file.
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
		 * @param   str   $type The type of export being handled.
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
		 * Hands off the data to export the file.
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

	// add_action( 'plugins_loaded', 'WCSH_Export::instance' );
	WCSH_Export::instance();
}