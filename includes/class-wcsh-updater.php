<?php
/**
 * WCSH_Updater handles updating the plugin directly from the GitHub repo.
 *
 * @package WooCommerce_Support_Helper
 * @since   1.1.1
 */
if ( ! class_exists( 'WCSH_Updater' ) ) {
	class WCSH_Updater {

		/**
		 * Our plugin file.
		 *
		 * @since   1.1.1
		 * @version 1.1.1
		 * @var
		 */
		protected $file;

		/**
		 * Our plugin.
		 *
		 * @since   1.1.1
		 * @version 1.1.1
		 * @var
		 */
		protected $plugin;

		/**
		 * Basename of our plugin.
		 *
		 * @since   1.1.1
		 * @version 1.1.1
		 * @var
		 */
		protected $basename;

		/**
		 * Active status of plugin.
		 *
		 * @since   1.1.1
		 * @version 1.1.1
		 * @var
		 */
		protected $active;

		/**
		 * Username for connecting to GitHub.
		 *
		 * @since   1.1.1
		 * @version 1.1.1
		 * @var
		 */
		private $username;

		/**
		 * The plugin's repo on GitHub.
		 *
		 * @since   1.1.1
		 * @version 1.1.1
		 * @var
		 */
		private $repository;

		/**
		 * Token for connecting to GitHub.
		 *
		 * @since   1.1.1
		 * @version 1.1.1
		 * @var
		 */
		private $authorize_token;

		/**
		 * Response from GitHub.
		 *
		 * @since   1.1.1
		 * @version 1.1.1
		 * @var
		 */
		private $github_response;


		/**
		 * Constructor.
		 * 
		 * @since   1.1.1
		 * @version 1.1.1
		 * @param   str   $file The filename of the plugin.
		 */
		public function __construct( $file ) {

			add_action( 'admin_init', array( $this, 'set_plugin_properties' ) );

			// Set the file of the plugin via what's passed.
			$this->file = $file;
			return $this;
		}

		/**
		 * Initialize our filters.
		 * 
		 * @since   1.1.1
		 * @version 1.1.1
		 */
		public function initialize() {
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'modify_transient' ), 10, 1 );
			add_filter( 'plugins_api', array( $this, 'plugin_popup' ), 10, 3 );
			add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
		}

		/** 
		 * Sets up the properties of our plugin.
		 * 
		 * @since   1.1.1
		 * @version 1.1.1
		 */
		public function set_plugin_properties() {
			$this->plugin   = get_plugin_data( $this->file );
			$this->basename = plugin_basename( $this->file );
			$this->active   = is_plugin_active( $this->basename );
		}

		/** 
		 * Sets username.
		 * 
		 * @since   1.1.1
		 * @version 1.1.1
		 * @param   str   $username The username for the GitHub repo.
		 */
		public function set_username( $username ) {
			$this->username = $username;
		}

		/** 
		 * Sets repo name.
		 * 
		 * @since   1.1.1
		 * @version 1.1.1
		 * @param   str   $repository The GitHub repo name.
		 */
		public function set_repository( $repository ) {
			$this->repository = $repository;
		}

		/** 
		 * Sets token.
		 * 
		 * @since   1.1.1
		 * @version 1.1.1
		 * @param   str   $token The GitHub token.
		 */
		public function authorize( $token ) {
			$this->authorize_token = $token;
		}

		/** 
		 * Gets the repo info from GitHub.
		 * 
		 * @since   1.1.1
		 * @version 1.1.1
		 */
		private function get_repository_info() {

			// If there's currently no GitHub response set. 
			if ( is_null( $this->github_response ) ) { 
				
				// Create the uri.
				$request_uri = sprintf( 'https://api.github.com/repos/%s/%s/releases', $this->username, $this->repository );

				// If there is a token, we add that to the uri.
				if ( $this->authorize_token ) {
					$request_uri = add_query_arg( 'access_token', $this->authorize_token, $request_uri );
				}        

				// Connect to GitHub, parse JSON response.
				$response = json_decode( wp_remote_retrieve_body( wp_remote_get( $request_uri ) ), true );
				$response = is_array( $response ) ? current( $response ) : $response;

				// If there's a token set. 
				if ( $this->authorize_token ) {
					// Update zip url with the token.
					$response['zipball_url'] = add_query_arg( 'access_token', $this->authorize_token, $response['zipball_url'] );
				}

				// Set our GitHub response property.
				$this->github_response = $response;
			}
		}

		/** 
		 * Modifies the transient for update purposes.
		 * 
		 * @since   1.1.1
		 * @version 1.1.1
		 * @param   str   $transient The transient we are going to be using.
		 */
		public function modify_transient( $transient ) {

			// Check if the transient has a checked property.
			if ( property_exists( $transient, 'checked') ) {

				// Has WordPress checked for updates?
				if ( $checked = $transient->checked ) {

					// Get the repo info, determine if we're out of date.
					$this->get_repository_info();
					$out_of_date = version_compare( $this->github_response['tag_name'], $checked[ $this->basename ], '>' );


					// If it's out of date.
					if ( $out_of_date ) {

						// Get the zip and slug.
						$new_files = $this->github_response['zipball_url'];
						$slug      = current( explode( '/', $this->basename ) );

						// Set up the plugin info.
						$plugin = array(
							'url'         => $this->plugin['PluginURI'],
							'slug'        => $slug,
							'package'     => $new_files,
							'new_version' => $this->github_response['tag_name'],
						);

						// Set the transient response.
						$transient->response[ $this->basename ] = (object) $plugin;
					}
				}
			}

			return $transient;
		}

		/** 
		 * Filters the result of the pop up on the Plugins page.
		 * 
		 * @since   1.1.1
		 * @version 1.1.1
		 * @param   str   $result
		 * @param   str   $action
		 * @param   arr   $args   Arguments sent to query the pop up.
		 */
		public function plugin_popup( $result, $action, $args ) {

			// If a slug is set
			if(  ! empty( $args->slug ) ) {

				// And if it's our slug.
				if ( $args->slug === current( explode( '/' , $this->basename ) ) ) {

					// Get the repo info.
					$this->get_repository_info();

					// Set it to an array
					$plugin = array(
						'name'              => $this->plugin['Name'],
						'slug'              => $this->basename,
						'version'           => $this->github_response['tag_name'],
						'author'            => $this->plugin['AuthorName'],
						'author_profile'    => $this->plugin['AuthorURI'],
						'last_updated'      => $this->github_response['published_at'],
						'homepage'          => $this->plugin['PluginURI'],
						'short_description' => $this->plugin['Description'],
						'sections'          => array( 
							'Description'   => $this->plugin['Description'],
							'Updates'       => $this->github_response['body'],
							),
						'download_link'     => $this->github_response['zipball_url']
					);

					// And then return it.
					return (object) $plugin;
				}
			}

			return $result;
		}

		/** 
		 * Copies the files in for the update process.
		 * 
		 * @since   1.1.1
		 * @version 1.1.1
		 * @param   str   $response   
		 * @param   str   $hook_extra 
		 * @param   arr   $result     
		 */
		public function after_install( $response, $hook_extra, $result ) {
			// Global filesystem object.
			global $wp_filesystem;

			// Our plugin directory.
			$install_directory = plugin_dir_path( $this->file );

			// Move the files to our plugin directory.
			$wp_filesystem->move( $result['destination'], $install_directory );

			// Set the destination for the plugin files.
			$result['destination'] = $install_directory;

			// If the plugin was active, reactivate it.
			if ( $this->active ) {
				activate_plugin( $this->basename );
			}
			return $result;
		}
	}
}
