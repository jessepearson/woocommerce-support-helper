<?php
/**
 * WCSH_Import is our main import class. It handles the import feature and retrieves the data for the registered handlers.
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */
if ( ! class_exists( 'WCSH_Import' ) ) {
	class WCSH_Import {

		/**
		 * The instance of our class.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @var
		 */
		private static $instance = null;

		/**
		 * The imported file data.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @var
		 */
		public $file_data = null;

		/**
		 * Our handlers for handling the different import types.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @var
		 */
		public $import_handlers = null;

		/**
		 * Types of imports that have been confirmed.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @var
		 */
		public $confirmed_imports = null;	

		/**
		 * Constructor.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		private function __construct() {
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
		 * Handles the first step of importing by saving the data to the database, then sending back to confirmation form.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function import() {

			// Get the file handler and read the data from the file.
			$file_handler    = new WCSH_File_Handler();
			$this->file_data = $file_handler->get_file_data();

			// Add the information into the db for us to get later.
			update_option( 'wcsh_import_temp', $this->file_data );

			// Send back to the form to display the confirmation for import.
			$redirect_url = add_query_arg( [ 
				'page'   => 'woocommerce-support-helper',
				'action' => 'confirm_import', 
				], 
				admin_url( 'admin.php' ) 
			);
			wp_safe_redirect( $redirect_url );
			exit;
		}

		/**
		 * Completes the import once it is confirmed by the user.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function complete_import() {

			// Get the import data from the database.
			$this->file_data = get_option( 'wcsh_import_temp' );
			
			// Go through each type in the file.
			foreach ( $this->file_data as $type => $data ) {
				
				// Check to see if this type was confirmed and if there's a handler.
				if ( in_array( $type, $this->confirmed_imports ) && $this->has_import_handler( $type ) ) {

					// Hand off for processing.
					$this->handle_import( $type );
				}
			}

			// Delete option?
			// delete_option( 'wcsh_import_temp' );
		}

		/**
		 * Gets our import handlers.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @return  
		 */
		public function get_import_handlers() {

			// If they are already set, return them.
			if ( null !== $this->import_handlers ) {
				return $this->import_handlers;
			}

			// Use the filter to get registered handlers and return them. 
			$this->import_handlers = apply_filters( 'wcsh_import_handlers', [] );
			return $this->import_handlers;
		}

		/**
		 * Checks to see if there's an import handler for the setting type being imported.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function has_import_handler( $type = null ) {

			// Get the import handler names.
			$import_handlers = array_keys( $this->get_import_handlers() );

			// Return if we have a handler or not. 
			if ( in_array( $type, $import_handlers ) ) {
				return true;
			}
			
			WCSH_Logger::log( 'No import handler found for type of: '. $type );
			return false;
		}

		/**
		 * Hands off to the import handlers for the different settings.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function handle_import( $type = null ) {

			// Shouldn't get here without a type, but throw error if we do. 
			if ( null === $type ) {
				throw new Exception( 'Null type found in handle_import.' );
			}

			// Set the class and method.
			$class  = $this->import_handlers[ $type ]['class'];
			$method = $this->import_handlers[ $type ]['method'];

			// Make sure our class exists.
			if ( ! class_exists( $class ) ) {
				throw new Exception( 'The ' . $class . ' class does not exist.' );
			}

			// Get our handler object, make sure the method exists.
			$handler = $class::instance();
			if ( ! method_exists( $handler, $method ) ) {
				throw new Exception( 'The ' . $method . ' method does not exist in the ' . $class . 'class.' );
			}

			// Handle the import.
			$handler->$method( $this->file_data );
		}
	}

	add_action( 'plugins_loaded', 'WCSH_Import::instance' );
	WCSH_Import::instance();
}