<?php
/**
 * Plugin Name: WooCommerce Support Helper
 * Plugin URI: https://github.com/jessepearson/woocommerce-support-helper
 * Description: A plugin to export and import many settings in WooCommerce, along with other things.
 * Author: Jesse Pearson
 * Author URI: https://github.com/jessepearson/
 * Text Domain: woocommerce-support-helper
 * Version: 1.1.4
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
	 */
	class WooCommerce_Support_Helper {

		/**
		 * Constructor.
		 * 
		 * @since   1.0.0
		 * @version 1.1.3
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'includes' ) );
			add_action( 'init', array( $this, 'plugin_updater' ), 20 );
		}

		/**
		 * Includes needed files.
		 * 
		 * @since   1.0.0
		 * @version 1.1.4
		 */
		public function includes() {

			// Updater class is needed outside of the admin.
			require_once( dirname( __FILE__ ) .'/includes/class-wcsh-updater.php' );
		
			// Files only the admin needs.
			if ( is_admin() ) {
				require_once( dirname( __FILE__ ) .'/includes/class-wcsh-logger.php' );
				require_once( dirname( __FILE__ ) .'/includes/class-wcsh-tools.php' );
				require_once( dirname( __FILE__ ) .'/includes/class-wcsh-file-handler.php' );
				require_once( dirname( __FILE__ ) .'/includes/class-wcsh-export.php' );
				require_once( dirname( __FILE__ ) .'/includes/class-wcsh-import.php' );
				require_once( dirname( __FILE__ ) .'/includes/class-wcsh-shipping-export.php' );
				require_once( dirname( __FILE__ ) .'/includes/class-wcsh-shipping-import.php' );
				require_once( dirname( __FILE__ ) .'/includes/class-wcsh-payment-export.php' );
				require_once( dirname( __FILE__ ) .'/includes/class-wcsh-payment-import.php' );
				require_once( dirname( __FILE__ ) .'/includes/class-wcsh-settings-tabs-export.php' );
				require_once( dirname( __FILE__ ) .'/includes/class-wcsh-settings-tabs-import.php' );

				/**
				 * Subscriptions support. 
				 * We only export if Subs is active, but we import if the data is there regardless.
				 */
				if ( class_exists( 'WC_Subscriptions_Admin' ) ) {
					require_once( dirname( __FILE__ ) .'/includes/class-wcsh-subscriptions-tab-export.php' );
				}
				require_once( dirname( __FILE__ ) .'/includes/class-wcsh-subscriptions-tab-import.php' );

				/**
				 * Product Filters support. 
				 * We only export if PF is active, but we import if the data is there regardless.
				 */
				if ( defined( 'WC_PRODUCT_FILTER_VERSION' ) ) {
					require_once( dirname( __FILE__ ) .'/includes/class-wcsh-product-filters-export.php' );
				}
				require_once( dirname( __FILE__ ) .'/includes/class-wcsh-product-filters-import.php' );
			}
		}

		/**
		 * Initializes our plugin updater.
		 * 
		 * @since   1.0.0
		 * @version 1.1.1
		 */
		public function plugin_updater() {
			
			// Get our updater object, set user and repo, and initialize the updater.
			$updater = new WCSH_Updater( __FILE__ );
			$updater->set_username( 'jessepearson' );
			$updater->set_repository( 'woocommerce-support-helper' );
			$updater->initialize();
		}
	}

	new WooCommerce_Support_Helper();
}
