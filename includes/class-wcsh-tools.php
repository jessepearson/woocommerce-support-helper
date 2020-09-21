<?php
/**
 * WCSH_Tools renders the tools page and handles form submissions on it.
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 * @version 1.0.0
 */

class WCSH_Tools {

	/**
	 * Notices.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public $notice;

	/**
	 * Constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ), 99 );
		add_action( 'init', array( $this, 'catch_requests' ), 20 );
	}

	/**
	 * Adds submenu page to tools.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function add_submenu_page() {
		add_submenu_page( 
			'woocommerce',
			__( 'Support Helper', 'woocommerce-support-helper' ),
			__( 'Support Helper', 'woocommerce-support-helper' ),
			'manage_options',
			'woocommerce-support-helper',
			[ $this, 'wcsh_tools_page' ]
			);
	}

	/**
	 * Renders the tool page.
	 * 
	 * In order to create another tool on the page, copy and paste the form, then add/modify needed fields.
	 * Once new form is added move to catch_requests() to add your new action. 
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function wcsh_tools_page() {

		// Start output.
		?>
		<style>

			#wcsh_settings label {
				font-size: 1.2em;
				font-weight: bold;
				display: inline-block;
				min-width: 10em;
				line-height: 1.2em;
			}

			#wcsh_settings span {
				font-size: 1.1em;
			}

			form {
				margin-bottom: 20px; 
				border: 1px solid #ccc;
				padding: 5px;
			}
		</style>
		<div class="wrap">
			<h1>WooCommerce Support Helper</h1>
			<hr />
			<div>
		<?php 

		// Check to see that WooCommerce is active.
		$woocommerce_inactive = false;
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->print_notice( 'WooCommerce is not active.', 'error' );
			$woocommerce_inactive = true;
		}

		// Output any notices or errors. 
		if ( ! empty( $this->notice ) ) {
			echo $this->notice;
		}

		// If we have an error, ask to fix it and exit. 
		if ( $woocommerce_inactive ) {	
			?>
				<p>Please correct the errors listed above, and then the tools will be available.</p>
			</div>
		</div>

			<?php
			exit;
		} 

		// Set the form action url.
		$action_url = add_query_arg( [ 'page' => 'woocommerce-support-helper' ], admin_url( 'admin.php' ) );
		$file_ext   = WCSH_File_Handler::can_use_zip() ? '.zip' : '.json';
		?>

				<h3>Export Shipping Data</h3>
				<form action="<?php echo $action_url; ?>" method="post">
					<table>
						<tr>
							<td>
								<input type="submit" class="button" value="Export Shipping Data" /> <label>Exports Shipping Zones, Methods, and Settings.</label>
								<input type="hidden" name="action" value="export_shipping_zones" />
								<?php wp_nonce_field( 'export_shipping_zones' ); ?>
							</td>
						</tr>
					</table>
				</form>

				<h3>Import Shipping Data</h3>
				<form enctype="multipart/form-data" action="<?php echo $action_url; ?>" method="post">
					<table>
						<tr>
							<td>
								<label>Choose a file (<?php echo $file_ext; ?>).</label><input type="file" name="shipping_zone_import" />
							</td>
						</tr>

						<tr>
							<td>
								<input type="submit" class="button" value="Import Shipping Data" /> <label>Imports shipping data.</label>
								<input type="hidden" name="action" value="import_shipping_zones" />
								<?php wp_nonce_field( 'import_shipping_zones' ); ?>
							</td>
						</tr>
					</table>
				</form>

				<h3>Delete ALL Shipping Zones</h3>
				<form action="<?php echo $action_url; ?>" method="post">
					<table>
						<tr>
							<td>
								<input type="submit" class="button" value="Delete ALL Shipping Zones" /> <label>Deletes ALL shipping zones.</label>
								<input type="hidden" name="action" value="delete_shipping_zones" />
								<?php wp_nonce_field( 'delete_shipping_zones' ); ?>
							</td>
						</tr>
					</table>
				</form>

			</div>
		</div>
		<?php
	}

	/**
	 * Catches form requests.
	 *
	 * Here you will need to add your action to the $actions array. 
	 * Next your action will need to be added to the switch statement to call your processing function.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function catch_requests() {

		// Check to make sure we're on the proper page.
		if ( ! isset( $_GET['page'] ) || 'woocommerce-support-helper' !== $_GET['page'] ) {
			return;
		}

		// If there's no action or nonce, exit quietly. 
		if ( ! isset( $_POST['action'] ) || ! isset( $_POST['_wpnonce'] ) ) {
			return;
		}

		// Actions correspond to the tools page.
		$actions = [
			'export_shipping_zones',
			'import_shipping_zones',
			'delete_shipping_zones',
		];

		if ( ! in_array( $_POST['action'], $actions ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], $_POST['action'] ) ) {
			wp_die( 'Cheatin&#8217; huh?' );
		}

		// Hand off to the proper handler.
		call_user_func( [ $this, $_POST['action'] ] );
	}

	/**
	 * An exmple processing function.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function example_processing_function() {
		WCSH_Logger::log( 'Begin example processing.' );

		try {

			if ( true ) {
				WCSH_Logger::log( 'That worked.' );
				$this->print_notice( 'That worked.' );

			} else  {
				WCSH_Logger::log( 'That failed.' );
				throw new Exception( 'That failed!' );
			}

			$this->print_notice( 'Done processing.' );

		} catch ( Exception $e ) {

			$this->print_notice( $e->getMessage() );
			return;
		}
	}

	/**
	 * Deletes all of the shipping zones.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function delete_shipping_zones() {
		WCSH_Logger::log( 'Begin deleting shipping zones.' );

		$zones = WC_Shipping_Zones::get_zones( 'json' );
		foreach ( $zones as $z ) {
			$zone = new WC_Shipping_Zone( $z['id'] );
			$zone->delete();
		}

		$notice = count( $zones ) . ' Shipping Zones have been deleted.';
		WCSH_Logger::log( $notice );
		$this->print_notice( $notice, 'success' );
	}

	/**
	 * Exports the Shipping Zones, their methods, and even Table Rates.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function export_shipping_zones() {
		WCSH_Logger::log( 'Export of shipping zones requested.' );

		try {
			// Fire up our exporter and hand off.
			$exporter = new WCSH_Shipping_Export();
			$exporter->export( 'shipping_zone_export' );

		} catch ( Exception $e ) {

			$notice = 'Shipping zone export failed: '. $e->getMessage();
			WCSH_Logger::log( $notice );
			$this->print_notice( $notice, 'error' );
			return;
		}

		$notice = 'Shipping Zones, Methods, and Settings exported successfully.';
		WCSH_Logger::log( $notice );
		$this->print_notice( $notice, 'success' );
	}

	/**
	 * Handler for importing the Shipping Zone data.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function import_shipping_zones() {
		WCSH_Logger::log( 'Import of shipping zones requested.' );

		try {
			// Fire up our importer and hand off.
			$importer = new WCSH_Shipping_Import();
			$importer->import( 'shipping_zone_import' );

		} catch ( Exception $e ) {
			
			$notice = 'Import failed: ' . $e->getMessage();
			WCSH_Logger::log( $notice );
			$this->print_notice( $notice, 'error' );
			return;
		}

		$notice = 'Shipping Zones, Methods, and Settings imported successfully.';
		WCSH_Logger::log( $notice );
		$this->print_notice( $notice, 'success' );
	}

	/**
	 * Prints notices.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param string $message
	 * @param string $type
	 */
	public function print_notice( $message = '', $type = 'warning' ) {

		$notice = '<div class="notice notice-' . esc_attr( $type ) . '"><p>' . esc_html( $message ) . '</p></div>';

		if ( '' !== $this->notice ) {
			$this->notice = $this->notice ."\n". $notice;
		} else {
			$this->notice = $notice;
		}
	}
}

new WCSH_Tools();