<?php

/**
 * Main file of the extension.
 *
 * ---------------------------------------------------------------------------------|
 * Copyright 2014-17  J.D. Grimes  (email : jdg@codesymphony.co)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or later, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * ---------------------------------------------------------------------------------|
 *
 * @package WordPoints_Beta_Tester
 * @version 1.1.1
 * @license https://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright 2014-17 J.D. Grimes
 */

wordpoints_register_extension(
	'
		Extension Name: Beta Tester
		Author:         WordPoints
		Author URI:     https://wordpoints.org/
		Extension URI:  https://wordpoints.org/extensions/beta-tester
		Version:        1.1.1
		License:        GPLv2+
		Description:    Beta test the latest changes to the WordPoints plugin.
		Text Domain:    wordpoints-beta-tester
		Domain Path:    /languages
		Server:         wordpoints.org
		ID:             316
	'
	, __FILE__
);

/**
 * Update the WordPoints plugin from the latest commits on GitHub.
 *
 * Largely inspired by: https://github.com/afragen/github-updater
 *
 * @since 1.0.0
 */
class WordPoints_GitHub_Updater {

	/**
	 * The basic config for the updates.
	 *
	 * The basename is dynamic, so it is set by the constructor.
	 *
	 * @since 1.0.0
	 *
	 * @type array $config {
	 *       @type int    $id         The plugin's ID on WordPress.org.
	 *       @type string $slug       The plugin's slug on WordPress.org.
	 *       @type string $basename   The plugin's path relative to the plugins dir.
	 *       @type string $github_url The URL of the repo on GitHub.
	 *       @type string $zip_url    The URL of the zip archive of the GitHub repo.
	 * }
	 */
	public $config = array(
		'id'         => 43839,
		'slug'       => 'wordpoints',
		'basename'   => 'wordpoints/wordpoints.php',
		'github_url' => 'https://github.com/WordPoints/wordpoints/',
		'zip_url'    => 'https://github.com/WordPoints/wordpoints/archive/develop.zip',
	);

	/**
	 * The current commit being upgraded to.
	 *
	 * Used during the upgrade process.
	 *
	 * @since 1.0.0
	 *
	 * @type object $upgrade_commit
	 */
	private $upgrade_commit;

	//
	// Public Methods.
	//

	/**
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->config['basename'] = plugin_basename( WORDPOINTS_DIR . '/wordpoints.php' );

		// Check for new commits when WordPress checks for plugin updates.
		add_filter( 'site_transient_update_plugins', array( $this, 'api_check' ) );

		// Hook into the plugin details screen
		add_filter( 'plugins_api', array( $this, 'get_plugin_info' ), 10, 3 );

		// Change the source directory to just the /src/ folder.
		add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 4 );

		// Change the destination directory to the correct one.
		add_filter( 'upgrader_package_options', array( $this, 'upgrader_package_options' ) );

		// Set the timeout for the HTTP request.
		add_filter( 'http_request_timeout', array( $this, 'http_request_timeout' ) );
	}

	/**
	 * Check whether or not the transients need to be overruled.
	 *
	 * When the transients are ignored, the API will be called for every single
	 * page load.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether to overrule the transients or not.
	 */
	public function overrule_transients() {

		/**
		 * Whether to overrule the transients and check for updates every page load.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $overrule Whether to overrule the transients. Default: false.
		 */
		return apply_filters( 'wordpoints_beta_tester_overrule_transients', false );
	}

	/**
	 * Set the timeout length for an HTTP request.
	 *
	 * @since 1.0.0
	 *
	 * @filter http_request_timeout Added by the constructor.
	 *
	 * @return int Timeout length.
	 */
	public function http_request_timeout() {
		return 2;
	}

	/**
	 * Set the 'sslverify' argument for an HTTP request.
	 *
	 * @since 1.0.0
	 * @deprecated 1.1.0 No longer needed.
	 *
	 * @filter http_request_args Added by the constructor.
	 *
	 * @param array  $args The request arguments.
	 * @param string $url  The request URI.
	 *
	 * @return array The args, with 'sslverify' set to true.
	 */
	public function http_request_sslverify( $args, $url ) {

		if ( $this->config['zip_url'] === $url ) {
			$args['sslverify'] = true;
		}

		return $args;
	}

	/**
	 * Interact with GitHub.
	 *
	 * @since 1.0.0
	 *
	 * @param string $query The URL of the query to run.
	 *
	 * @return mixed The raw response from this query.
	 */
	public function remote_get( $query ) {

		$raw_response = wp_safe_remote_get( $query, array(
			'headers'   => array( 'accept' => 'application/vnd.github.v3+json' ),
		) );

		return $raw_response;
	}

	/**
	 * Get the data for the latest commit to the GitHub repo.
	 *
	 * @since 1.0.0
	 *
	 * @return object|bool The data for the last commit, or false on failure.
	 */
	public function get_latest_commit() {

		$last_commit = get_site_transient( 'wordpoints_beta_tester_last_commit' );

		if ( $this->overrule_transients() || empty( $last_commit ) ) {

			$latest_commits = $this->get_latest_commits();

			if ( ! is_array( $latest_commits ) ) {
				return false;
			}

			$last_commit = array_shift( $latest_commits );

			// Refresh every six hours.
			if ( $last_commit ) {
				set_site_transient( 'wordpoints_beta_tester_last_commit', $last_commit, 6 * HOUR_IN_SECONDS );
			}
		}

		return $last_commit;
	}

	/**
	 * Get the data for the most recent commits to the GitHub repo.
	 *
	 * @since 1.0.0
	 *
	 * @return array|bool The API response, or false on failure.
	 */
	public function get_latest_commits() {

		$api_query = 'commits?path=src';

		$current_commit = get_site_option( 'wordpoints_beta_version' );

		if ( $current_commit ) {
			$api_query .= '&since=' . $current_commit->commit->committer->date;
		}

		$commits = $this->github_api( $api_query );

		if ( ! is_array( $commits ) ) {
			return false;
		}

		return $commits;
	}

	/**
	 * Check the build status of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return string|bool The build status of the plugin, or false on failure.
	 */
	public function get_build_status() {

		$build_status = get_site_transient( 'wordpoints_beta_tester_build_status' );

		if ( $this->overrule_transients() || empty( $build_status ) ) {

			$commit = $this->github_api( 'statuses/develop' );

			if ( ! is_array( $commit ) || ! isset( $commit[0]->state ) ) {
				return false;
			}

			$build_status = $commit[0]->state;

			set_site_transient( 'wordpoints_beta_tester_build_status', $build_status, HOUR_IN_SECONDS );
		}

		return $build_status;
	}

	/**
	 * Get the version of the plugin from the GitHub repo.
	 *
	 * @since 1.0.0
	 *
	 * @return string|bool The version from GitHub, or false on failure.
	 */
	public function get_version() {

		$version = get_site_transient( 'wordpoints_beta_tester_github_version' );

		if ( $this->overrule_transients() || empty( $version ) ) {

			$plugin = $this->github_api( 'contents/src/wordpoints.php' );

			if ( ! $plugin || ! isset( $plugin->content ) ) {
				return false;
			}

			preg_match( '/Version:\s+([a-zA-Z0-9\.]+)\n/', base64_decode( $plugin->content ), $matches );

			if ( ! empty( $matches[1] ) ) {

				$version = $matches[1];

				set_site_transient( 'wordpoints_beta_tester_github_version', $version, HOUR_IN_SECONDS );
			}
		}

		return $version;
	}

	/**
	 * Get the result of a request to the GitHub API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $query The API query.
	 *
	 * @return object|bool The API data, or false on failure.
	 */
	public function github_api( $query ) {

		$raw_response = $this->remote_get( "https://api.github.com/repos/WordPoints/wordpoints/{$query}" );

		if ( is_wp_error( $raw_response ) ) {
			return false;
		}

		$response = json_decode( $raw_response['body'] );

		if ( ! is_array( $response ) && ! is_object( $response ) ) {
			return false;
		}

		return $response;
	}

	/**
	 * Hook into the plugin update check and connect to GitHub.
	 *
	 * Filters the 'update_plugins' transient before it is set. This transient is
	 * used by WordPress to store the info about plugins needing updates. We run our
	 * own check here to see if there are new changes to the development version of
	 * WordPoints, and update the transient data accordingly.
	 *
	 * @since 1.0.0
	 *
	 * @filter pre_set_site_transient_update_plugins Added by the constructor.
	 *
	 * @param object $transient The 'update_plugins' transient.
	 *
	 * @return object $transient The modified transient.
	 */
	public function api_check( $transient ) {

		// If the transient doesn't contain the 'checked' for info, return it.
		if ( empty( $transient->checked ) || false === $transient->response ) {
			return $transient;
		}

		unset( $transient->response[ $this->config['basename'] ] );

		$current_commit = get_site_option( 'wordpoints_beta_version' );
		$latest_commit  = $this->get_latest_commit();

		if ( $latest_commit && ( ! $current_commit || $current_commit->sha !== $latest_commit->sha ) ) {

			// Check the build status.
			if ( 'success' !== $this->get_build_status() ) {
				return $transient;
			}

			$this->upgrade_commit = $latest_commit;

			$version = $this->get_version();

			if ( ! $version ) {
				$version = WORDPOINTS_VERSION;
			}

			$response = new stdClass();
			$response->new_version = $version . '-#' . substr( $latest_commit->sha, 0, 8 );
			$response->id          = $this->config['id'];
			$response->slug        = $this->config['slug'];
			$response->plugin      = $this->config['basename'];
			$response->url         = $this->config['github_url'];
			$response->package     = $this->config['zip_url'];
			$response->upgrade_notice = '';
			$response->tested         = $GLOBALS['wp_version'];
			$response->compatibility  = (object) array( 'scalar' => (object) array( 'scalar' => false ) );

			$transient->response[ $this->config['basename'] ] = $response;
		}

		return $transient;
	}

	/**
	 * Override the WordPress.org plugin API as needed.
	 *
	 * The plugin API is used by WordPress to get information about plugins. We
	 * override the info for WordPoints here.
	 *
	 * @since 1.0.0
	 *
	 * @filter plugins_api
	 *
	 * @param bool   $false    False, unless the API is being overridden.
	 * @param string $action   The API function being performed.
	 * @param object $response The plugin's info.
	 *
	 * @return bool|object The plugin info if this is the WordPoints plugin.
	 */
	public function get_plugin_info( $false, $action, $response ) {

		// Check if this call API is for the right plugin
		if ( ! isset( $response->slug ) || $response->slug !== $this->config['slug'] ) {
			return $false;
		}

		$latest_commit = $this->get_latest_commit();

		if ( $latest_commit ) {
			$response->last_updated = $latest_commit->commit->author->date;
		}

		$response->name = 'WordPoints'; // Make sure that this is always set.
		$response->download_link = $this->config['zip_url'];

		// Display plugin information in the update modal.
		if ( 'plugin_information' === $action && defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST ) {

			$latest_commits = $this->get_latest_commits();

			$log = '';

			if ( is_array( $latest_commits ) && ! empty( $latest_commits ) ) {

				$log .= '<ul>';

				foreach ( $latest_commits as $commit ) {

					$log .= '<li><a href="' . esc_url( $commit->html_url ) . '">' . esc_html( substr( $commit->sha, 0, 8 ) ) . '</a>: ' . esc_html( $commit->commit->message ) . '</li>';
				}

				$log .= '</ul>';

			} else {

				// translators: URL to view list of commits on GitHub.
				$log .= sprintf( __( 'Unable to get a log of the latest commits. Try <a href="%s">viewing the log on GitHub</a> instead.', 'wordpoints-beta-tester' ), esc_url( $this->config['github_url'] . 'commits/develop/' ) );
			}

			$response->sections = array(
				esc_html__( 'Commit Log', 'wordpoints-beta-tester' ) => $log,
			);
		}

		return $response;
	}

	/**
	 * Set the destination to be the correct directory.
	 *
	 * @since 1.0.4
	 *
	 * @WordPress\filter upgrader_package_options Added by the constructor.
	 *
	 * @param array $options The options for this upgrade.
	 *
	 * @return array The filtered options.
	 */
	public function upgrader_package_options( $options ) {

		if ( $options['hook_extra']['plugin'] === $this->config['basename'] ) {
			$options['destination'] = trailingslashit( $options['destination'] )
				. trailingslashit( dirname( $this->config['basename'] ) );
		}

		return $options;
	}

	/**
	 * Get the /src dir instead of the whole thing.
	 *
	 * @since 1.0.0
	 *
	 * @WordPress\filter upgrader_source_selection Added by the class constructor.
	 *
	 * @param string $source        The path to the plugin source.
	 * @param string $remote_source The "remote" path to the plugin source.
	 * @param object $upgrader      The WP_Upgrader instance.
	 * @param array  $args          Other arguments.
	 *
	 * @return string The path to the /src dir instead.
	 */
	public function upgrader_source_selection( $source, $remote_source, $upgrader, $args ) {

		if (
			$args['plugin'] === $this->config['basename']
			&& basename( $source ) === 'wordpoints-develop'
		) {
			$source .= 'src/';
		}

		return $source;
	}

	/**
	 * Move the plugin to the correct location after upgrading.
	 *
	 * The default location would be /wp-content/plugins/src/.
	 *
	 * @since 1.0.0
	 * @deprecated 1.0.4 See self::upgrader_package_options().
	 *
	 * @param boolean $true       Always true.
	 * @param mixed   $hook_extra Extra info about the upgrade. Not used.
	 * @param array   $result     The result of the upgrade.
	 *
	 * @return array $result the result of the move
	 */
	public function upgrader_post_install( $true, $hook_extra, $result ) {

		_deprecated_function( __FUNCTION__, '1.0.4', 'self::upgrader_package_options()' );

		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->config['basename'] ) {
			return $true;
		}

		global $wp_filesystem;

		// Move the plugin to the proper location.
		$wp_filesystem->move( $result['destination'], WORDPOINTS_DIR );

		$result['destination'] = WORDPOINTS_DIR;

		// Activate it.
		$activate = activate_plugin( WP_PLUGIN_DIR . '/' . $this->config['basename'] );

		// Update the current commit in the database.
		update_site_option( 'wordpoints_beta_version', $this->upgrade_commit );

		// Output the update message.
		if ( is_wp_error( $activate ) ) {
			esc_html_e( 'The plugin has been updated, but could not be reactivated. Please reactivate it manually.', 'wordpoints-beta-tester' );
		} else {
			esc_html_e( 'Plugin reactivated successfully.', 'wordpoints-beta-tester' );
		}

		return $result;
	}
}

/**
 * Get the one true instance of the WordPoints GitHub Updater.
 *
 * @since 1.0.0
 *
 * @return WordPoints_GitHub_Updater The one and only.
 */
function wordpoints_beta_tester() {

	static $instance;

	if ( ! $instance ) {
		$instance = new WordPoints_GitHub_Updater();
	}

	return $instance;
}

wordpoints_beta_tester();
