<?php

/**
 * Econozel Extension for BuddyPress
 *
 * @package Econozel
 * @subpackage BuddyPress
 */

// Exit when accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Econozel_BuddyPress' ) ) :
/**
 * The Econozel BuddyPress class
 *
 * @since 1.0.0
 */
class Econozel_BuddyPress {

	/**
	 * Setup this class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Setup default class globals
	 *
	 * @since 1.0.0
	 */
	public function setup_globals() {

		/** Paths *************************************************************/

		$this->includes_dir      = trailingslashit( econozel()->extend_dir . 'buddypress' );
		$this->includes_url      = trailingslashit( econozel()->extend_url . 'buddypress' );

		/** Identifiers *******************************************************/

		$this->article_post_type = econozel_get_article_post_type();
		$this->edition_tax_id    = econozel_get_edition_tax_id();
	}

	/**
	 * Include required files
	 *
	 * @since 1.0.0
	 */
	public function includes() {
		require( $this->includes_dir . 'activity.php'  );
		require( $this->includes_dir . 'functions.php' );
	}

	/**
	 * Setup default hooks and filters
	 *
	 * @since 1.0.0
	 */
	public function setup_actions() {

		// Activity component
		if ( bp_is_active( 'activity' ) ) {
			add_action( 'bp_loaded', array( $this, 'setup_activity_actions' ), 20 );
		}
	}

	/** Activity **************************************************************/

	/**
	 * Setup actions for the activity component
	 *
	 * @since 1.0.0
	 */
	public function setup_activity_actions() {

		// After the post type is registered
		add_action( 'econozel_init', 'econozel_bp_activity_setup_post_type_tracking', 15 );

		// Modify queried activities
		add_filter( 'bp_activity_get',                  array( $this, 'bp_activity_get'                    ), 10, 2 );
		add_filter( 'bp_activity_get_where_conditions', array( $this, 'activity_filter_where_conditions'   ), 10, 2 );

		// Generate post summary
		add_filter( 'econozel_get_article_description', array( $this, 'get_post_summary' ), 10, 2 );
	}

	/**
	 * Modify the queried activity items
	 *
	 * @since 1.0.0
	 *
	 * @param array $activity Activity query result
	 * @param array $args Activity query arguments
	 * @return array Activity query result
	 */
	public function bp_activity_get( $activity, $args ) {

		// Walk queried activities
		foreach ( $activity['activities'] as $k => $_activity ) {

			// This is a New Article activity item
			if ( "new_{$this->article_post_type}" == $_activity->type ) {

				// Skip when Article does not exist
				if ( ! $article = econozel_get_article( (int) $_activity->secondary_item_id ) )
					continue;

				// Redefine action string since it was inserted in the DB
				if ( $action = bp_activity_generate_action_string( $_activity ) ) {
					$_activity->action = $action;
				}

				// Add from Article description
				if ( $content = econozel_get_article_description( $article ) ) {
					$_activity->content = $content;
				}
			}

			$activity['activities'][ $k ] = $_activity;
		}

		return $activity;
	}

	/**
	 * Modify the activity query WHERE clause statements
	 *
	 * @since 1.0.0
	 *
	 * @global WPDB $wpdb
	 *
	 * @param array $where Query WHERE clause statements
	 * @param array $args Query arguments
	 * @return array Query WHERE clause statements
	 */
	public function activity_filter_where_conditions( $where, $args ) {
		global $wpdb;

		// Define comment query condition part
		$_part = "a.type IN ( 'new_blog_comment'";

		// This is an activity comment query
		if ( isset( $where['filter_sql'] ) && false !== ( $pos = strpos( $where['filter_sql'], $_part ) ) ) {

			// Query also comment
			$where['filter_sql'] = substr_replace( $where['filter_sql'], $wpdb->prepare( ', %s', "new_{$this->article_post_type}_comment" ), $pos + strlen( $_part ), 0 );

			// Get synced Article comment activities
			$activity_ids = $wpdb->get_col( $wpdb->prepare( "SELECT activity_id FROM {$bp->activity->table_name_meta} WHERE meta_key = %s", "bp_blogs_{$this->article_post_type}_comment_id" ) );

			// OR query for specific comment activities
			if ( ! empty( $activity_ids ) ) {
				$where['filter_sql'] = '(' . $where['filter_sql'] . ' OR a.id IN ( ' . implode( ',', $activity_ids ) . ' ) )';
			}
		}

		return $where;
	}

	/**
	 * Return a generated summary for the given post object
	 *
	 * @since 1.0.0
	 *
	 * @global WPDB $wpdb
	 *
	 * @param string $content Post content
	 * @param WP_Post $post Post object
	 * @return string Post content summary
	 */
	public function get_post_summary( $content, $article ) {
		global $wpdb;

		// Bail when BP's summaries are not enabled
		if ( ! econozel_bp_enable_summary() ) {
			return $content;
		}

		// Get BuddyPress
		$bp = buddypress();

		// Define activity item identifiers
		$activity          = false;
		$type              = 'new_' . $this->article_post_type;
		$item_id           = get_current_blog_id();
		$secondary_item_id = $article->ID;

		// Check whether there was an activity item registered
		$activity_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->activity->table_name} WHERE type = %s AND item_id = %d AND secondary_item_id = %d", $type, $item_id, $secondary_item_id ) );

		// Get the original activity object
		if ( $activity_id ) {
			$activity = bp_activity_get_specific( array( 'activity_ids' => (int) $activity_id ) );
			$activity = (array) $activity['activities'][0];
		}

		// When not found, setup fake activity object data
		if ( ! $activity ) {
			$activity = array(
				'id'                => 0,
				'user_id'           => $article->post_author,
				'component'         => bp_is_active( 'blogs' ) ? $bp->blogs->id : 'blogs',
				'type'              => $type,
				'content'           => $content,
				'primary_link'      => get_home_url( null, '?p=' . $article->ID ),
				'item_id'           => $item_id,
				'secondary_item_id' => $secondary_item_id,
				'date_recorded'     => $article->post_date,
				'hide_sitewide'     => 0,
				'mptt_left'         => 0,
				'mptt_right'        => 0,
				'is_spam'           => 0,
				'user_email'        => '',
				'user_nicename'     => '',
				'user_login'        => '',
				'display_name'      => '',
				'user_fullname'     => '',
				'children'          => false,
			);
		}

		// Generate summary from activity data for this post's summary
		$content = bp_activity_create_summary( $article, $activity );

		return $content;
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
	econozel()->extend->buddypress = new Econozel_BuddyPress;
}

endif; // class_exists
