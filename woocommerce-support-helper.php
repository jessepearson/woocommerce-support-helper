<?php
/**
 * Plugin Name: WooCommerce Support Helper
 * Plugin URI: https://github.com/jessepearson/woocommerce-support-helper
 * Description: A plugin to export and import many settings in WooCommerce, along with other things.
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
			add_action( 'init', [ $this, 'includes' ] );
		}

		/**
		 * Includes needed files.
		 * 
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function includes() {
		
			// Files only the admin needs.
			if ( is_admin() ) {
				require_once( 'includes/class-wcsh-logger.php' );
				require_once( 'includes/class-wcsh-tools.php' );
				require_once( 'includes/class-wcsh-file-handler.php' );
				require_once( 'includes/class-wcsh-export.php' );
				require_once( 'includes/class-wcsh-import.php' );
				require_once( 'includes/class-wcsh-shipping-export.php' );
				require_once( 'includes/class-wcsh-shipping-import.php' );
				require_once( 'includes/class-wcsh-payment-export.php' );
				require_once( 'includes/class-wcsh-payment-import.php' );
				require_once( 'includes/class-wcsh-settings-tabs-export.php' );
				require_once( 'includes/class-wcsh-settings-tabs-import.php' );
			}
		}
	}

	new WooCommerce_Support_Helper();
}
