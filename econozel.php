<?php

/**
 * The Econozel Plugin
 * 
 * @package Econozel
 * @subpackage Main
 */

/**
 * Plugin Name:       Econozel
 * Description:       Present d' Econozel on your site with articles, editions, and volumes.
 * Plugin URI:        https://github.com/vgsr/econozel/
 * Version:           1.0.2
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
	 * @uses Econozel::includes()
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

		$this->version      = '1.0.2';
		$this->db_version   = 20180917;

		/** Paths *************************************************************/

		// Setup some base path and URL information
		$this->file         = __FILE__;
		$this->basename     = plugin_basename( $this->file );
		$this->plugin_dir   = plugin_dir_path( $this->file );
		$this->plugin_url   = plugin_dir_url ( $this->file );

		// Includes
		$this->includes_dir = trailingslashit( $this->plugin_dir . 'includes' );
		$this->includes_url = trailingslashit( $this->plugin_url . 'includes' );

		// Assets
		$this->assets_dir   = trailingslashit( $this->plugin_dir . 'assets' );
		$this->assets_url   = trailingslashit( $this->plugin_url . 'assets' );

		// Extensions
		$this->extend_dir   = trailingslashit( $this->includes_dir . 'extend' );
		$this->extend_url   = trailingslashit( $this->includes_url . 'extend' );

		// Themes
		$this->themes_dir   = trailingslashit( $this->plugin_dir . 'templates' );
		$this->themes_url   = trailingslashit( $this->plugin_url . 'templates' );

		// Languages
		$this->lang_dir     = trailingslashit( $this->plugin_dir . 'languages' );

		/** Identifiers *************************************************/

		// Post type
		$this->article_post_type  = apply_filters( 'econozel_article_post_type', 'econozel' );

		// Taxonomy
		$this->volume_tax_id      = apply_filters( 'econozel_volume_tax',  'econozel_volume'  );
		$this->edition_tax_id     = apply_filters( 'econozel_edition_tax', 'econozel_edition' );

		// Status
		$this->featured_status_id = apply_filters( 'econozel_featured_post_status', 'econozel_featured' );

		/** Queries ***********************************************************/

		// Use `WP_Term_Query` since WP 4.6
		$term_query = class_exists( 'WP_Term_Query' ) ? 'WP_Term_Query' : 'stdClass';

		$this->volume_query  = new $term_query; // Main Volume query
		$this->edition_query = new $term_query; // Main Edition query
		$this->article_query = new WP_Query();  // Main Article query

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

		// Core
		require( $this->includes_dir . 'actions.php'      );
		require( $this->includes_dir . 'articles.php'     );
		require( $this->includes_dir . 'capabilities.php' );
		require( $this->includes_dir . 'editions.php'     );
		require( $this->includes_dir . 'functions.php'    );
		require( $this->includes_dir . 'sub-actions.php'  );
		require( $this->includes_dir . 'taxonomy.php'     );
		require( $this->includes_dir . 'update.php'       );
		require( $this->includes_dir . 'users.php'        );
		require( $this->includes_dir . 'volumes.php'      );

		// Theme
		require( $this->includes_dir . 'template.php'     );
		require( $this->includes_dir . 'theme-compat.php' );

		// Widgets
		require( $this->includes_dir . 'widgets/class-econozel-articles-widget.php' );
		require( $this->includes_dir . 'widgets/class-econozel-comments-widget.php' );

		// Utility
		require( $this->includes_dir . 'classes/class-econozel-walker-article.php' );

		// Administration
		if ( is_admin() ) {
			require( $this->includes_dir . 'admin.php'    );
			require( $this->includes_dir . 'settings.php' );
		}

		// Extensions
		require( $this->extend_dir . 'buddypress/buddypress.php' );
		require( $this->extend_dir . 'vgsr.php'                  );
		require( $this->extend_dir . 'woosidebars.php'           );
		require( $this->extend_dir . 'wordpress-seo.php'         );
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

		// Bail when plugin is being deactivated
		if ( econozel_is_deactivation() )
			return;

		// Load textdomain
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ), 20 );

		// Register assets
		add_action( 'econozel_init', array( $this, 'register_post_types'    ) );
		add_action( 'econozel_init', array( $this, 'register_taxonomies'    ) );
		add_action( 'econozel_init', array( $this, 'register_post_statuses' ) );

		// Permalinks
		add_action( 'econozel_init', array( $this, 'add_rewrite_tags'  ), 20 );
		add_action( 'econozel_init', array( $this, 'add_rewrite_rules' ), 30 );
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

		// Check current user access
		$access = econozel_check_access();

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
				'public'              => $access,
				'has_archive'         => true,
				'rewrite'             => econozel_get_article_post_type_rewrite(),
				'query_var'           => true,
				'exclude_from_search' => ! $access,
				'show_ui'             => current_user_can( 'econozel_articles_admin' ),
				'show_in_nav_menus'   => $access,
				'can_export'          => $access,
				'taxonomies'          => array( 'post_tag' ),
				'menu_icon'           => 'dashicons-format-aside',
				'vgsr'                => true
			)
		);
	}

	/**
	 * Register the plugin taxonomies
	 *
	 * @since 1.0.0
	 */
	public function register_taxonomies() {

		// Check current user access
		$access = econozel_check_access();

		// Register Edition taxonomy for Article post type
		register_taxonomy(
			econozel_get_edition_tax_id(),
			econozel_get_article_post_type(),
			array(
				'labels'                => econozel_get_edition_tax_labels(),
				'capabilities'          => econozel_get_edition_tax_caps(),
				'update_count_callback' => '_econozel_update_article_edition_count',
				'hierarchical'          => false,
				'public'                => $access,
				'rewrite'               => false, // We have our own rewrite rules
				'query_var'             => false, // We have our own query vars
				'show_tagcloud'         => false,
				'show_in_quick_edit'    => true,
				'show_admin_column'     => true,
				'show_in_nav_menus'     => $access,
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
				'public'                => $access,
				'rewrite'               => false, // We have our own rewrite rules
				'query_var'             => false, // We have our own query vars
				'show_tagcloud'         => false,
				'show_in_quick_edit'    => false,
				'show_admin_column'     => false,
				'show_in_nav_menus'     => $access,
				'show_ui'               => current_user_can( 'econozel_volume_admin' ),
				'meta_box_cb'           => false // We have our own metabox
			)
		);
	}

	/**
	 * Register post statuses
	 *
	 * @since 1.0.0
	 */
	public function register_post_statuses() {

		// Featured
		register_post_status(
			econozel_get_featured_status_id(),
			apply_filters( 'econozel_register_featured_post_status', array(
				'label'                     => _x( 'Featured', 'Post status', 'econozel' ),
				'label_count'               => _nx_noop( 'Featured <span class="count">(%s)</span>', 'Featured <span class="count">(%s)</span>', 'Post status', 'econozel' ),
				'protected'                 => false,
				'public'                    => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true
			) )
		);
	}

	/**
	 * Register plugin rewrite tags
	 *
	 * @since 1.0.0
	 */
	public function add_rewrite_tags() {
		add_rewrite_tag( '%' . econozel_get_root_rewrite_id()             . '%', '([1]{1,})' ); // Root Page tag
		add_rewrite_tag( '%' . econozel_get_volume_archive_rewrite_id()   . '%', '([1]{1,})' ); // Volume archives tag
		add_rewrite_tag( '%' . econozel_get_edition_archive_rewrite_id()  . '%', '([1]{1,})' ); // Edition archives tag
		add_rewrite_tag( '%' . econozel_get_volume_rewrite_id()           . '%', '([^/]+)'   ); // Single Volume tag
		add_rewrite_tag( '%' . econozel_get_edition_issue_rewrite_id()    . '%', '([^/]+)'   ); // Single Edition tag
		add_rewrite_tag( '%' . econozel_get_random_article_rewrite_id()   . '%', '([1]{1,})' ); // Random Article tag
		add_rewrite_tag( '%' . econozel_get_featured_archive_rewrite_id() . '%', '([1]{1,})' ); // Featured archives tag
	}

	/**
	 * Register plugin rewrite rules
	 *
	 * Setup rules to create the following structures:
	 * - /{root}/
	 * - /{root}/{volumes}/
	 * - /{root}/{volumes}/page/{#}/
	 * - /{root}/{volumes}/{volume}/
	 * - /{root}/{volumes}/{volume}/{issue}/
	 * - /{root}/{editions}/
	 * - /{root}/{editions}/page/{#}/
	 * - /{root}/{random}/
	 * - /{root}/{featured}/
	 *
	 * @since 1.0.0
	 */
	public function add_rewrite_rules() {

		// Priority
		$priority        = 'top';

		// Slugs
		$root_slug       = econozel_get_root_slug();
		$volume_slug     = econozel_get_volume_slug();
		$edition_slug    = econozel_get_edition_slug();
		$paged_slug      = econozel_get_paged_slug();
		$random_slug     = econozel_get_random_article_slug();
		$featured_slug   = econozel_get_featured_archive_slug();

		// Unique rewrite IDs
		$root_id         = econozel_get_root_rewrite_id();
		$volume_root_id  = econozel_get_volume_archive_rewrite_id();
		$volume_id       = econozel_get_volume_rewrite_id();
		$edition_root_id = econozel_get_edition_archive_rewrite_id();
		$issue_id        = econozel_get_edition_issue_rewrite_id();
		$random_id       = econozel_get_random_article_rewrite_id();
		$featured_id     = econozel_get_featured_archive_rewrite_id();
		$paged_id        = 'paged';

		// Generic rules
		$root_rule       = '/?$';
		$paged_rule      = '/' . $paged_slug .'/?([0-9]{1,})/?$';

		/** Add ***************************************************************/

		// Base rules
		$volume_rule     = $volume_slug . '/([0-9]{1,})';
		$edition_rule    = $volume_rule . '/([^/]+)';

		// Root rules
		add_rewrite_rule( $root_slug     . $root_rule,  'index.php?' . $root_id . '=1', $priority );

		// Volume rules
		add_rewrite_rule( $volume_slug   . $paged_rule, 'index.php?' . $volume_root_id . '=1&' . $paged_id . '=$matches[1]', $priority );
		add_rewrite_rule( $volume_slug   . $root_rule,  'index.php?' . $volume_root_id . '=1',                               $priority );
		add_rewrite_rule( $volume_rule   . $root_rule,  'index.php?' . $volume_id      . '=$matches[1]',                     $priority );

		// Edition rules
		add_rewrite_rule( $edition_slug  . $paged_rule, 'index.php?' . $edition_root_id . '=1&'           . $paged_id . '=$matches[1]', $priority );
		add_rewrite_rule( $edition_slug  . $root_rule,  'index.php?' . $edition_root_id . '=1',                                         $priority );
		add_rewrite_rule( $edition_rule  . $root_rule,  'index.php?' . $volume_id       . '=$matches[1]&' . $issue_id . '=$matches[2]', $priority );

		// Random rules
		add_rewrite_rule( $random_slug   . $root_rule,  'index.php?' . $random_id . '=1', $priority );

		// Featured rules
		add_rewrite_rule( $featured_slug . $paged_rule, 'index.php?' . $featured_id . '=1&' . $paged_id . '=$matches[1]', $priority );
		add_rewrite_rule( $featured_slug . $root_rule,  'index.php?' . $featured_id . '=1', $priority );
	}
}

/**
 * Return single instance of the plugin's main class
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
