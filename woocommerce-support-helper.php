<?php
/**
 * Plugin Name: WooCommerce Support Helper
 * Plugin URI: 
 * Description: 
 * Author: Jesse Pearson
 * Author URI: https://jessepearson.net
 * Text Domain: woocommerce-support-helper
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooCommerce_Support_Helper' ) ) {
	/**
	 * Main class.
	 *
	 * @package WooCommerce_Support_Helper
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	class WooCommerce_Support_Helper {

		/**
		 * Constructor.
		 * 
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function __construct() {
			// add_action( 'init', [ $this, 'check_dependencies' ] );
			add_action( 'init', [ $this, 'includes' ] );
		}

		/**
		 * Checks dependencies.
		 * 
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function check_dependencies() {
			// Get dependencies class file.
			require_once( 'includes/class-wcsh-dependencies.php' );
			
			// Check to see if we need to deactivate the plugin.
			$dependencies = new WCSH_Dependencies( __FILE__ );
			$deactivated  = $dependencies->maybe_deactivate_plugin();

			// If we didn't deactivate, include everything else.
			if ( ! $deactivated ) {
				$this->includes();
			}
		}

		/**
		 * Includes needed files.
		 * 
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function includes() {
			// Files we always need.
			require_once( 'includes/class-wcsh-logger.php' );
		
			// Files only the admin needs.
			if ( is_admin() ) {
				// require_once( 'includes/class-wcsh-settings.php' );
				require_once( 'includes/class-wcsh-tools.php' );
				require_once( 'includes/class-wcsh-file-handler.php' );
				require_once( 'includes/class-wcsh-shipping-export.php' );
				require_once( 'includes/class-wcsh-shipping-import.php' );
			}
		}
	}

	new WooCommerce_Support_Helper();
}
