<?php
/**
 * WCSH_Tools renders the tools page and handles form submissions on it.
 * 
 * @package WooCommerce_Support_Helper
 * @since   1.0.0
 */
if ( ! class_exists( 'WCSH_Tools' ) ) {
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
			add_action( 'admin_menu', [ $this, 'add_submenu_page' ], 99 );
			add_action( 'init', [ $this, 'catch_requests' ], 20 );
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
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function wcsh_tools_page() {

			// Start output.
			
			// TODO: Style this better.
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

			// Set the form action url and file type.
			$action_url = add_query_arg( [ 'page' => 'woocommerce-support-helper' ], admin_url( 'admin.php' ) );
			$file_ext   = WCSH_File_Handler::can_use_zip() ? '.zip' : '.json';

			// If an import has been submitted.
			if ( isset( $_GET['action'] ) && 'confirm_import' == $_GET['action'] ) {

				// Get the types of settings that are in the data.
				$import_data  = get_option( 'wcsh_import_temp' );
				$import_types = array_keys( $import_data );

				// Get import handlers.
				$importer = WCSH_Import::instance();
				$handlers = $importer->get_import_handlers();
				WCSH_Logger::log( print_r( $handlers, true ) );
				?>

				<h3>Confirm Settings Import</h3>
				<form action="<?php echo $action_url; ?>" method="post">
					<table>
						<tr>
							<td>
								You are about to import settings for:
							</td>
						</tr>
						<tr>
							<td>
								<ul>
								<?php
									foreach ( $handlers as $handler => $options ) {
										if ( in_array( $handler, $import_types ) ) {
											?>
											<li>
												<input type="checkbox" name="import[]" id="<?php echo $handler; ?>" value="<?php echo $handler; ?>" checked="checked">
												&nbsp;<label for="<?php echo $handler; ?>"><?php echo $handler; ?></label> - <?php echo $options['notice']; ?>
											</li>
											<?php
										}
										
									}
								?>
								</ul>
							</td>
						</tr>
						<tr>
							<td>
								<input type="submit" class="button" value="Confirm import" />
								<input type="hidden" name="action" value="confirm_import" />
								<?php wp_nonce_field( 'confirm_import' ); ?>
							</td>
						</tr>
					</table>
				</form>
				<?php
			}



			// Get export handlers.
			$exporter = WCSH_Export::instance();
			$handlers = $exporter->get_export_handlers();
			?>

					<h3>Settings To Export</h3>
					<form action="<?php echo $action_url; ?>" method="post">
						<table>
							<tr>
								<td>
									Export the below selected settings:
								</td>
							</tr>
							<tr>
								<td>
									<ul>
									<?php
										foreach ( $handlers as $handler => $options ) {
											?>
											<li>
												<input type="checkbox" name="export[]" id="<?php echo $handler; ?>" value="<?php echo $handler; ?>" checked="checked">
												&nbsp;<label for="<?php echo $handler; ?>"><?php echo $handler; ?></label> - <?php echo $options['notice']; ?>
											</li>
											<?php
										}
									?>
									</ul>
								</td>
							</tr>
							<tr>
								<td>
									<input type="submit" class="button" value="Export Settings" />
									<input type="hidden" name="action" value="export_settings" />
									<?php wp_nonce_field( 'export_settings' ); ?>
								</td>
							</tr>
						</table>
					</form>

					<h3>Import Settings</h3>
					<form enctype="multipart/form-data" action="<?php echo $action_url; ?>" method="post">
						<table>
							<tr>
								<td>
									<label>Choose a file (<?php echo $file_ext; ?>).</label><input type="file" name="import_settings" />
								</td>
							</tr>

							<tr>
								<td>
									<input type="submit" class="button" value="Import settings" /> <label>Imports settings file.</label>
									<input type="hidden" name="action" value="import_settings" />
									<?php wp_nonce_field( 'import_settings' ); ?>
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

			// Actions correspond to the tools on the page.
			$actions = [
				'delete_shipping_zones',
				'import_settings',
				'confirm_import',
				'export_settings',
			];

			// If it's not a good action, exit.
			if ( ! in_array( $_POST['action'], $actions ) ) {
				return;
			}

			// Nonce fail? Bail.
			if ( ! wp_verify_nonce( $_POST['_wpnonce'], $_POST['action'] ) ) {
				wp_die( 'Nonce error.' );
			}

			// Hand off to the proper handler.
			call_user_func( [ $this, $_POST['action'] ] );
		}

		/**
		 * Gets the handlers going to export the settings. 
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function export_settings() {

			try {
				// If nothing was selected, then bail.
				if ( ! isset( $_POST['export'] ) || empty( $_POST['export'] ) ) {
					throw new Exception( 'Export submitted, but nothing selected.' );
				}

				WCSH_Logger::log( 'Export of settings requested.' );

				// Fire up our exporter and hand off.
				$exporter = WCSH_Export::instance();
				$exporter->confirmed_exports = $_POST['export']; 
				$exporter->export();

			} catch ( Exception $e ) {
				
				$notice = 'Export failed: ' . $e->getMessage();
				WCSH_Logger::log( $notice );
				$this->print_notice( $notice, 'error' );
				return;
			}

			// This doesn't actually happen due to exit call in export.
			$notice = 'Settings exported successfully.';
			WCSH_Logger::log( $notice );
			$this->print_notice( $notice, 'success' );
		}

		/**
		 * If the import as been confirmed, this hands off to the import handlers.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function confirm_import() {

			try {
				// If it was confirmed, but nothing selected, bail. 
				if ( ! isset( $_POST['import'] ) || empty( $_POST['import'] ) ) {
					throw new Exception( 'Import confirmed, but nothing selected.' );
				}

				WCSH_Logger::log( 'Import Settings confirmed.' );

				// Fire up our importer and hand off.
				$importer = WCSH_Import::instance();
				$importer->confirmed_imports = $_POST['import']; 
				$importer->complete_import();

			} catch ( Exception $e ) {
				
				$notice = 'Import failed: ' . $e->getMessage();
				WCSH_Logger::log( $notice );
				$this->print_notice( $notice, 'error' );
				return;
			}

			$notice = 'Settings import complete.';
			WCSH_Logger::log( $notice );
			$this->print_notice( $notice, 'success' );
		}

		/**
		 * Initial import request to upload file, returns to confirmation page.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 */
		public function import_settings() {
			WCSH_Logger::log( 'Import Settings requested.' );

			try {
				// Fire up our importer and hand off.
				$importer = WCSH_Import::instance();
				$importer->import();

			} catch ( Exception $e ) {
				
				$notice = 'Import failed: ' . $e->getMessage();
				WCSH_Logger::log( $notice );
				$this->print_notice( $notice, 'error' );
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

			// Get all the zones, then delete them all.
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
}
