<?php
/**
 * WCSH_Logger is our logger for the plugin.
 *
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */
class WCSH_Logger {

	/**
	 * It's log, it's better than bad, it's good!
	 * 
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param   string $log | String to be logged.
	 */
	static function log( $log ) {

		// Uses WooCommerce's logger, so if it's not found, bail.
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}
		
		$logger = wc_get_logger();
		$logger->debug( $log, [ 'source' => 'woocommerce-support-helper' ] );
	}
}
