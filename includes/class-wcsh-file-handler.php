<?php
/**
 * 
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */
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
		$this->temp_dir = get_temp_dir() .'woocommerce-support-helper';
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public static function can_use_zip() {
		if ( null !== self::$ziparchive_available ) {
			return self::$ziparchive_available;
		}

		self::$ziparchive_available = class_exists( 'ZipArchive' ) ? true : false;
		return self::$ziparchive_available;
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function check_file( $type = null ) {

		if ( null === $type ) {
			throw new Exception( 'No import type passed.' );
		}

		if ( empty( $_FILES ) || empty( $_FILES[ $type ] ) || 0 !== $_FILES[ $type ]['error'] || empty( $_FILES[ $type ]['tmp_name'] ) ) {
			throw new Exception( 'There is no file or file is not valid.' );
		}

		if ( $_FILES[ $type ]['size'] > 1000000 ) {
			throw new Exception( 'The file exceeds 1MB.' );
		}
	}

	/**
	 * 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function get_file_data( $type = null ) {
		WCSH_Logger::log( $type );
		$this->check_file( $type );

		if ( $this->can_use_zip() ) {
			$file_data = $this->open_zip( $_FILES[ $type ]['tmp_name'] );
		} else {
			$file_data = file_get_contents( $_FILES[ $type ]['tmp_name'] );
		}

		if ( ! $this->is_json( $file_data ) ) {
			throw new Exception( 'The file is not in a valid JSON format.' );
		}

		$file_data = json_decode( $file_data, true );

		// Sanitize.
		array_walk_recursive( $file_data, 'wc_clean' );

		$this->clean_up();

		return $file_data;
	}

	/**
	 * Triggers the download feature of the browser.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param   string $data
	 * @param   string $prefix
	 */
	public function trigger_download( $data = '', $prefix = '' ) {
		if ( empty( $data ) ) {
			return;
		}

		@set_time_limit(0);

		// Disable GZIP
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', 1 );
		}

		@ini_set( 'zlib.output_compression', 'Off' );
		@ini_set( 'output_buffering', 'Off' );
		@ini_set( 'output_handler', '' );

		$filename_prefix = $prefix;

		if ( $this->can_use_zip() ) {
			$filename = sprintf( '%1$s-%2$s', $filename_prefix, date( 'Y-m-d', current_time( 'timestamp' ) ) );

			$this->prep_transfer();

			$this->render_headers( $filename );

			if ( $this->create_zip( $data, $filename ) ) {
				readfile( $this->temp_dir . '/' . $filename . '.zip' );

				$this->clean_up();

				exit;
			} else {
				throw new Exception( 'Unable to export!' );
			}
		} else {
			$filename = sprintf( '%1$s-%2$s', $filename_prefix, date( 'Y-m-d', current_time( 'timestamp' ) ) );

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
	 */
	public function clean_up( $path = null ) {
		if ( null === $path ) {
			$path = $this->temp_dir;
		}

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
	 * @param   JSON string $data | Data to be zipped
	 * @param   string $filename
	 */
	public function create_zip( $data = false, $filename ) {
		$zip_file = $this->temp_dir . '/' . $filename . '.zip';

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
	 */
	public function open_zip( $file ) {
		$zip = new ZipArchive();

		if ( true === $zip->open( $file ) ) {
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

			if ( ! file_exists( $this->temp_dir . '/' . $json_file ) ) {
				throw new Exception( 'Unable to open zip file' );
			}

			return file_get_contents( $this->temp_dir . '/' . $json_file );
		} else {
			throw new Exception( 'Unable to open zip file' );
		}
	}

	/**
	 * Checks if string is valid JSON.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $string
	 * @return bool
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
	 * @param   string $filename | Path to file
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