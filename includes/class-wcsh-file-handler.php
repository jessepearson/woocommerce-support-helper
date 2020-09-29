<?php
/**
 * WCSH_File_Handler handles the importing and exporting of files for the plugin.
 * Mostly taken from Bookings Helper, thanks Roy!
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */
if ( ! class_exists( 'WCSH_File_Handler' ) ) {
	class WCSH_File_Handler {

		/**
		 * Temporary directory path.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @var
		 */
		public $temp_dir;

		/**
		 * Checks to see if ZipArchive library exists.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @var
		 */
		public static $ziparchive_available;

		/**
		 * Constructor.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function __construct() {
			// Set the temp directory. 
			$this->temp_dir = get_temp_dir() . 'woocommerce-support-helper';
		}

		/**
		 * Checks to see if we're able to use zip files or not.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public static function can_use_zip() {
			// Return if it's already set.
			if ( null !== self::$ziparchive_available ) {
				return self::$ziparchive_available;
			}

			// Check for ZipArchive class to see if we can zip/unzip.
			self::$ziparchive_available = class_exists( 'ZipArchive' ) ? true : false;
			return self::$ziparchive_available;
		}

		/**
		 * Checks the file being imported.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @param   str   $type Type is the file being imported.
		 */
		public function check_file( $type = null ) {

			// If no type, bail.
			if ( null === $type ) {
				throw new Exception( 'No import type passed.' );
			}

			// If file not found, bail.
			if ( empty( $_FILES ) || empty( $_FILES[ $type ] ) || 0 !== $_FILES[ $type ]['error'] || empty( $_FILES[ $type ]['tmp_name'] ) ) {
				throw new Exception( 'There is no file or file is not valid.' );
			}

			// If file too big, bail.
			if ( $_FILES[ $type ]['size'] > 1000000 ) {
				throw new Exception( 'The file exceeds 1MB.' );
			}
		}

		/**
		 * Gets the data out of the file. 
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @param   str   $type The type of file being imported.
		 * @return  arr   Data from JSON file as an array.
		 */
		public function get_file_data( $type = 'import_settings' ) {
			
			// Make sure the file is good.
			$this->check_file( $type );

			// Open zip, or just read the file.
			if ( $this->can_use_zip() ) {
				$file_data = $this->open_zip( $_FILES[ $type ]['tmp_name'] );
			} else {
				$file_data = file_get_contents( $_FILES[ $type ]['tmp_name'] );
			}

			// If it's not JSON, bail.
			if ( ! $this->is_json( $file_data ) ) {
				throw new Exception( 'The file is not in a valid JSON format.' );
			}

			// Decode it and sanitize it. 
			$file_data = json_decode( $file_data, true );
			array_walk_recursive( $file_data, 'wc_clean' );

			// Delete temp files, and return the data.
			$this->clean_up();
			return $file_data;
		}

		/**
		 * Triggers the download feature of the browser.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @param   str   $data   Data being added to the file.
		 * @param   str   $prefix Beginning of the file name.
		 */
		public function trigger_download( $data = '', $prefix = '' ) {

			// If no data, exit.
			if ( empty( $data ) ) {
				return;
			}

			// Set environment variables.
			@set_time_limit(0);
			if ( function_exists( 'apache_setenv' ) ) {
				@apache_setenv( 'no-gzip', 1 );
			}
			@ini_set( 'zlib.output_compression', 'Off' );
			@ini_set( 'output_buffering', 'Off' );
			@ini_set( 'output_handler', '' );

			// Set the file name.
			$filename = sprintf( '%1$s-%2$s', $prefix, date( 'Y-m-d', current_time( 'timestamp' ) ) );

			// If we're using zip.
			if ( $this->can_use_zip() ) {

				// Create temp dir, set the headers.
				$this->prep_transfer();
				$this->render_headers( $filename );

				// Create the zip file and clean up.
				if ( $this->create_zip( $data, $filename ) ) {

					readfile( $this->temp_dir . '/' . $filename . '.zip' );
					$this->clean_up();

					exit;

				} else {
					throw new Exception( 'Unable to export!' );
				}

			} else {

				// Render the headers and put the data in the file. 
				$this->render_headers( $filename );
				file_put_contents( 'php://output', $data );

				exit;
			}
		}

		/**
		 * Prepares the directory for file transfer.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function prep_transfer() {
			if ( ! is_dir( $this->temp_dir ) ) {
				return mkdir( $this->temp_dir );
			}
		}

		/**
		 * Cleans up lingering files and folder during transfer.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @param   str   $path Path to clean up.
		 */
		public function clean_up( $path = null ) {

			// Set to temp dir if nothing passed.
			if ( null === $path ) {
				$path = $this->temp_dir;
			}

			// Go through each item in the dir and remove it.
			if ( is_dir( $path ) ) {
				$objects = scandir( $path );

				foreach ( $objects as $object ) {
					if ( '.' !== $object && '..' !== $object ) {
						if ( is_dir( $path . '/' . $object ) ) {
							$this->clean_up( $path . '/' . $object );
						} else {
							unlink( $path . '/' . $object );
						}
					}
				}

				rmdir( $path );
			}
		}

		/**
		 * Creates the zip file.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @param   str   $data     JSON data to be zipped
		 * @param   str   $filename
		 * @return  bool  True on success, false on fail.
		 */
		public function create_zip( $data = false, $filename ) {

			// Set the zip's file name.
			$zip_file = $this->temp_dir . '/' . $filename . '.zip';

			// Create the zip, add the data to it.
			$zip = new ZipArchive();
			$zip->open( $zip_file, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE );
			$zip->addFromString( $filename . '.json', $data );
			$zip->close();

			if ( file_exists( $this->temp_dir . '/' . $filename . '.zip' ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Opens the zip file.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @param   obj   $file The file that was uploaded.
		 * @return  str   The data from the file.
		 */
		public function open_zip( $file ) {

			// New zip object.
			$zip = new ZipArchive();

			// Open the zip file uploaded.
			if ( true === $zip->open( $file ) ) {

				// Extract the data to temp dir.
				$zip->extractTo( $this->temp_dir );
				$zip->close();

				$dir       = scandir( $this->temp_dir );
				$json_file = '';

				/**
				 * The zip may or may not contain other hidden
				 * system files so we must only extract the .json file.
				 */
				foreach ( $dir as $file ) {
					if ( preg_match( '/.json/', $file ) ) {
						$json_file = $file;
						break;
					}
				}

				// If nothing found, bail. 
				if ( ! file_exists( $this->temp_dir . '/' . $json_file ) ) {
					throw new Exception( 'Unable to open zip file' );
				}

				// Return the data.
				return file_get_contents( $this->temp_dir . '/' . $json_file );
			} else {
				throw new Exception( 'Unable to open zip file' );
			}
		}

		/**
		 * Checks if string is valid JSON.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @param   str   $string String to be checked.
		 * @return  bool  True if JSON, false if not. 
		 */
		public function is_json( $string = '' ) {
			json_decode( $string );
			
			return ( JSON_ERROR_NONE === json_last_error() );
		}

		/**
		 * Renders the HTTP headers
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 * @param   str   $filename Path to file.
		 */
		public function render_headers( $filename ) {
			$type = 'json';

			if ( $this->can_use_zip() ) {
				$type = 'zip';
			}

			header( 'Content-Type: application/' . $type . '; charset=UTF-8' );
			header( 'Content-Disposition: attachment; filename=' . $filename . '.' . $type );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );
		}
	}
}