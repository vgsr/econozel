<?php

/**
 * The Econozel Plugin
 * 
 * @package Econozel
 * @subpackage Main
 */

/**
 * Plugin Name:       Econozel
 * Description:       Present d' Econozel on your site with Articles, Editions, and Volumes.
 * Plugin URI:        https://github.com/vgsr/econozel/
 * Version:           1.0.0
 * Author:            Laurens Offereins
 * Author URI:        https://github.com/vgsr/
 * Text Domain:       econozel
 * Domain Path:       /languages/
 * GitHub Plugin URI: vgsr/econozel
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Econozel' ) ) :
/**
 * The main plugin class
 *
 * @since 1.0.0
 */
final class Econozel {

	/**
	 * Setup and return the singleton pattern
	 *
	 * @since 1.0.0
	 *
	 * @uses Econozel::setup_globals()
	 * @uses Econozel::setup_actions()
	 * @return The single Econozel
	 */
	public static function instance() {

		// Store instance locally
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Econozel;
			$instance->setup_globals();
			$instance->includes();
			$instance->setup_actions();
		}

		return $instance;
	}

	/**
	 * Prevent the plugin class from being loaded more than once
	 */
	private function __construct() { /* Nothing to do */ }

	/** Private methods *************************************************/

	/**
	 * Setup default class globals
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {

		/** Versions **********************************************************/

		$this->version      = '1.0.0';

		/** Paths *************************************************************/

		// Setup some base path and URL information
		$this->file         = __FILE__;
		$this->basename     = plugin_basename( $this->file );
		$this->plugin_dir   = plugin_dir_path( $this->file );
		$this->plugin_url   = plugin_dir_url ( $this->file );

		// Includes
		$this->includes_dir = trailingslashit( $this->plugin_dir . 'includes' );
		$this->includes_url = trailingslashit( $this->plugin_url . 'includes' );

		// Themes
		$this->themes_dir   = trailingslashit( $this->plugin_dir . 'templates' );
		$this->themes_url   = trailingslashit( $this->plugin_url . 'templates' );

		// Languages
		$this->lang_dir     = trailingslashit( $this->plugin_dir . 'languages' );

		/** Queries ***********************************************************/

		// `WP_Term_Query` since WP 4.6
		if ( class_exists( 'WP_Term_Query' ) ) {
			$this->volume_query  = new WP_Term_Query(); // Main Volume query
			$this->edition_query = new WP_Term_Query(); // Main Edition query

		// Fallback for WP pre-4.6
		} else {
			$this->volume_query  = new stdClass(); // Main Volume query
			$this->edition_query = new stdClass(); // Main Edition query
		}

		/** Misc **************************************************************/

		$this->theme_compat  = new stdClass();
		$this->extend        = new stdClass();
		$this->domain        = 'econozel';
	}

	/**
	 * Include the required files
	 *
	 * @since 1.0.0
	 */
	private function includes() {
		require( $this->includes_dir . 'actions.php'      );
		require( $this->includes_dir . 'articles.php'     );
		require( $this->includes_dir . 'capabilities.php' );
		require( $this->includes_dir . 'editions.php'     );
		require( $this->includes_dir . 'functions.php'    );
		require( $this->includes_dir . 'taxonomy.php'     );
		require( $this->includes_dir . 'template.php'     );
		require( $this->includes_dir . 'theme-compat.php' );
		require( $this->includes_dir . 'volumes.php'      );

		// Administration
		if ( is_admin() ) {
			require( $this->includes_dir . 'admin.php' );
		}
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {

		// Add actions to plugin activation and deactivation hooks
		add_action( 'activate_'   . $this->basename, 'econozel_activation'   );
		add_action( 'deactivate_' . $this->basename, 'econozel_deactivation' );

		// Bail when VGSR is not acive or plugin is being deactivated
		if ( ! function_exists( 'vgsr' ) || econozel_is_deactivation() )
			return;

		// Load textdomain
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ), 20 );

		// Register entities
		add_action( 'vgsr_init', array( $this, 'register_post_types' ) );
		add_action( 'vgsr_init', array( $this, 'register_taxonomies' ) );

		// Permalinks
		add_action( 'vgsr_init', array( $this, 'add_rewrite_tags'  ), 20 );
		add_action( 'vgsr_init', array( $this, 'add_rewrite_rules' ), 30 );
	}

	/** Plugin **********************************************************/

	/**
	 * Load the translation file for current language. Checks the languages
	 * folder inside the plugin first, and then the default WordPress
	 * languages folder.
	 *
	 * Note that custom translation files inside the plugin folder will be
	 * removed on plugin updates. If you're creating custom translation
	 * files, please use the global language folder.
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'plugin_locale' with {@link get_locale()} value
	 * @uses load_textdomain() To load the textdomain
	 * @uses load_plugin_textdomain() To load the textdomain
	 */
	public function load_textdomain() {

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/econozel/' . $mofile;

		// Look in global /wp-content/languages/econozel folder
		load_textdomain( $this->domain, $mofile_global );

		// Look in local /wp-content/plugins/econozel/languages/ folder
		load_textdomain( $this->domain, $mofile_local );

		// Look in global /wp-content/languages/plugins/
		load_plugin_textdomain( $this->domain );
	}

	/** Public methods **************************************************/

	/**
	 * Register the plugin post types
	 *
	 * @since 1.0.0
	 */
	public function register_post_types() {

		// Define local vars
		$is_user_vgsr = is_user_vgsr();

		// Register Econozel Article post type
		register_post_type(
			econozel_get_article_post_type(),
			array(
				'labels'              => econozel_get_article_post_type_labels(),
				'supports'            => econozel_get_article_post_type_supports(),
				'description'         => __( 'Econozel articles', 'econozel' ),
				'capabilities'        => econozel_get_article_post_type_caps(),
				'capability_type'     => array( 'econozel', 'econozels' ),
				'hierarchical'        => false,
				'public'              => $is_user_vgsr,
				'rewrite'             => econozel_get_article_post_type_rewrite(),
				'query_var'           => true,
				'exclude_from_search' => ! $is_user_vgsr,
				'show_ui'             => current_user_can( 'econozel_articles_admin' ),
				'show_in_nav_menus'   => $is_user_vgsr,
				'can_export'          => $is_user_vgsr,
				'taxonomies'          => array( 'post_tag' ),
				'menu_icon'           => 'dashicons-format-aside'
			)
		);
	}

	/**
	 * Register the plugin taxonomies
	 *
	 * @since 1.0.0
	 */
	public function register_taxonomies() {

		// Define local vars
		$is_user_vgsr = is_user_vgsr();

		// Register Edition taxonomy for Article post type
		register_taxonomy(
			econozel_get_edition_tax_id(),
			econozel_get_article_post_type(),
			array(
				'labels'                => econozel_get_edition_tax_labels(),
				'capabilities'          => econozel_get_edition_tax_caps(),
				'update_count_callback' => '_update_post_term_count',
				'hierarchical'          => false,
				'public'                => $is_user_vgsr,
				'rewrite'               => false, // We have our own rewrite rules
				'query_var'             => false, // We have our own query vars
				'show_tagcloud'         => false,
				'show_in_quick_edit'    => true,
				'show_admin_column'     => true,
				'show_in_nav_menus'     => $is_user_vgsr,
				'show_ui'               => current_user_can( 'econozel_edition_admin' ),
				'meta_box_cb'           => false // We have our own metabox
			)
		);

		// Register Volume taxonomy for Edition taxonomy
		register_taxonomy(
			econozel_get_volume_tax_id(),
			econozel_get_edition_tax_id(),
			array(
				'labels'                => econozel_get_volume_tax_labels(),
				'capabilities'          => econozel_get_volume_tax_caps(),
				'update_count_callback' => '_update_generic_term_count',
				'hierarchical'          => false,
				'public'                => $is_user_vgsr,
				'rewrite'               => false, // We have our own rewrite rules
				'query_var'             => false, // We have our own query vars
				'show_tagcloud'         => false,
				'show_in_quick_edit'    => false,
				'show_admin_column'     => false,
				'show_in_nav_menus'     => $is_user_vgsr,
				'show_ui'               => current_user_can( 'econozel_volume_admin' ),
				'meta_box_cb'           => false // We have our own metabox
			)
		);
	}

	/**
	 * Register plugin rewrite tags
	 *
	 * @since 1.0.0
	 */
	public function add_rewrite_tags() {
		add_rewrite_tag( '%' . econozel_get_root_rewrite_id()           . '%', '([1]{1,})' ); // Root Page tag
		add_rewrite_tag( '%' . econozel_get_volume_archive_rewrite_id() . '%', '([1]{1,})' ); // Volume Archive tag
		add_rewrite_tag( '%' . econozel_get_volume_rewrite_id()         . '%', '([^/]+)'   ); // Volume Page tag
		add_rewrite_tag( '%' . econozel_get_edition_issue_rewrite_id()  . '%', '([^/]+)'   ); // Edition Page tag
	}

	/**
	 * Register plugin rewrite rules
	 *
	 * Setup rules to create the following structures:
	 * - `/{root}/`
	 * - `/{root}/{volumes}/{volume}/
	 * - `/{root}/{volumes}/{volume}/page/{#}/`
	 * - `/{root}/{volumes}/{volume}/{issue}/`
	 * - `/{root}/{volumes}/{volume}/{issue}/page/{#}/`
	 *
	 * @since 1.0.0
	 */
	public function add_rewrite_rules() {

		// Priority
		$priority       = 'top';

		// Slugs
		$root_slug      = econozel_get_root_slug();
		$volume_slug    = econozel_get_volume_slug();
		$paged_slug     = econozel_get_paged_slug();

		// Unique rewrite ID's
		$root_id        = econozel_get_root_rewrite_id();
		$volume_root_id = econozel_get_volume_archive_rewrite_id();
		$volume_id      = econozel_get_volume_rewrite_id();
		$issue_id       = econozel_get_edition_issue_rewrite_id();
		$paged_id       = 'paged';

		// Generic rules
		$root_rule      = '/?$';
		$paged_rule     = '/' . $paged_slug .'/?([0-9]{1,})/?$';

		/** Add ***************************************************************/

		// Base rules
		$volume_rule    = $volume_slug . '/([0-9]{1,})';
		$edition_rule   = $volume_rule . '/([^/]+)';

		// Root rules
		add_rewrite_rule( $root_slug    . $root_rule,   'index.php?' . $root_id . '=1', $priority );

		// Volume rules
		add_rewrite_rule( $volume_slug  . $paged_rule,  'index.php?' . $volume_root_id . '=1&'           . $paged_id . '=$matches[1]', $priority );
		add_rewrite_rule( $volume_slug  . $root_rule,   'index.php?' . $volume_root_id . '=1',                                         $priority );
		add_rewrite_rule( $volume_rule  . $paged_rule,  'index.php?' . $volume_id      . '=$matches[1]&' . $paged_id . '=$matches[2]', $priority );
		add_rewrite_rule( $volume_rule  . $root_rule,   'index.php?' . $volume_id      . '=$matches[1]',                               $priority );

		// Edition rules
		add_rewrite_rule( $edition_rule . $paged_rule,  'index.php?' . $volume_id . '=$matches[1]&' . $issue_id . '=$matches[2]&' . $paged_id . '=$matches[3]', $priority );
		add_rewrite_rule( $edition_rule . $root_rule,   'index.php?' . $volume_id . '=$matches[1]&' . $issue_id . '=$matches[2]',                               $priority );
	}
}

/**
 * Return single instance of this main plugin class
 *
 * @since 1.0.0
 * 
 * @return Econozel
 */
function econozel() {
	return Econozel::instance();
}

// Initiate plugin on load
econozel();

endif; // class_exists
