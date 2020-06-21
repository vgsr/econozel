<?php

/**
 * Econozel Extension for BuddyPress
 *
 * @package Econozel
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Econozel_BP_Component' ) ) :
/**
 * Loads Econozel Component
 *
 * @since 1.0.0
 *
 * @package Econozel
 * @subpackage BuddyPress
 */
class Econozel_BP_Component extends BP_Component {

	/**
	 * Start the Econozel component creation process
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::start(
			'econozel',
			__( 'Econozel', 'econozel' ),
			econozel()->extend_dir . 'buddypress/'
		);
		$this->includes();
		$this->setup_globals();
		$this->setup_actions();
		$this->fully_loaded();
	}

	/**
	 * Include BuddyPress classes and functions
	 *
	 * @since 1.0.0
	 *
	 * @param array $includes Files to include
	 */
	public function includes( $includes = array() ) {

		// Helper BuddyPress functions
		$includes[] = 'functions.php';

		// Members modifications
		$includes[] = 'members.php';

		// BuddyPress Activity Extension class
		if ( bp_is_active( 'activity' ) ) {
			$includes[] = 'activity.php';
		}

		parent::includes( $includes );
	}

	/**
	 * Setup globals
	 *
	 * @since 1.0.0
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		// All arguments for Econozel component
		$args = array(
			'path'          => BP_PLUGIN_DIR,
			'slug'          => $this->id,
			'root_slug'     => isset( $bp->pages->econozel->slug ) ? $bp->pages->econozel->slug : $this->id,
			'has_directory' => false,
			'search_string' => esc_html__( 'Search Articles...', 'econozel' ),
		);

		parent::setup_globals( $args );
	}

	/**
	 * Setup the actions
	 *
	 * @since 1.0.0
	 *
	 * @link http://bbpress.trac.wordpress.org/ticket/2176
	 */
	public function setup_actions() {

		// Setup the components
		add_action( 'bp_init', array( $this, 'setup_components' ), 7 );

		parent::setup_actions();
	}

	/**
	 * Instantiate classes for BuddyPress integration
	 *
	 * @since 1.0.0
	 */
	public function setup_components() {

		// Always load the members component
		econozel()->extend->buddypress->members = new Econozel_BP_Members;

		// Create new activity class
		if ( bp_is_active( 'activity' ) ) {
			econozel()->extend->buddypress->activity = new Econozel_BP_Activity;
		}
	}

	/**
	 * Allow the variables, actions, and filters to be modified by third party
	 * plugins and themes.
	 *
	 * @since 1.0.0
	 */
	private function fully_loaded() {
		do_action_ref_array( 'econozel_buddypress_loaded', array( $this ) );
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @since 1.0.0
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		// Bail when there is no user displayed or logged in
		if ( ! is_user_logged_in() && ! bp_displayed_user_id() )
			return;

		// Bail when the loggedin user or displayed user has no access
		if ( ! econozel_check_access() || ! econozel_check_access( bp_displayed_user_id() ) )
			return;

		// Define local variable(s)
		$user_domain = '';

		// Add 'Forums' to the main navigation
		$main_nav = array(
			'name'                => __( 'Econozel', 'econozel' ),
			'slug'                => $this->slug,
			'position'            => 70,
			'screen_function'     => 'econozel_bp_member_screen_articles',
			'default_subnav_slug' => econozel_bp_get_article_slug(), // No subnavs
			'item_css_id'         => $this->id
		);

		// Determine user to use
		if ( bp_displayed_user_id() )
			$user_domain = bp_displayed_user_domain();
		elseif ( bp_loggedin_user_domain() )
			$user_domain = bp_loggedin_user_domain();
		else
			return;

		// User link
		$forums_link = trailingslashit( $user_domain . $this->slug );

		// Topics started
		$sub_nav[] = array(
			'name'            => __( 'Articles published', 'econozel' ),
			'slug'            => econozel_bp_get_article_slug(),
			'parent_url'      => $forums_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'econozel_bp_member_screen_articles',
			'position'        => 20,
			'item_css_id'     => 'articles'
		);

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the admin bar
	 *
	 * @since 1.0.0
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user when having access
		if ( econozel_check_access() ) {

			// Setup the logged in user variables
			$user_domain = bp_loggedin_user_domain();
			$plugin_link = trailingslashit( $user_domain . $this->slug );

			// Add the "My Account" sub menus
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'Econozel', 'econozel' ),
				'href'   => trailingslashit( $plugin_link )
			);

			// Topics
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-articles',
				'title'  => __( 'Articles published', 'econozel' ),
				'href'   => trailingslashit( $plugin_link . econozel_bp_get_article_slug() )
			);
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Sets up the title for pages and <title>
	 *
	 * @since 1.0.0
	 */
	public function setup_title() {
		$bp = buddypress();

		// Adjust title based on view
		if ( bp_is_econozel_component() ) {
			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'Econozel', 'econozel' );
			} elseif ( bp_is_user() ) {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => bp_displayed_user_id(),
					'type'    => 'thumb'
				) );
				$bp->bp_options_title = bp_get_displayed_user_fullname();
			}
		}

		parent::setup_title();
	}
}

/**
 * Initiate the BuddyPress extension
 *
 * @since 1.0.0
 *
 * @uses Econozel_BuddyPress
 */
function econozel_buddypress() {
	econozel()->extend->buddypress = new Econozel_BP_Component;
}

endif; // class_exists
