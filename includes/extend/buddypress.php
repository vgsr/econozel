<?php

/**
 * Econozel BuddyPress Functions
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
		$this->setup_actions();
	}

	/**
	 * Setup default class globals
	 *
	 * @since 1.0.0
	 */
	public function setup_globals() {
		$this->article_post_type = econozel_get_article_post_type();
		$this->edition_tax_id    = econozel_get_edition_tax_id();
	}

	/**
	 * Setup default hooks and filters
	 *
	 * @since 1.0.0
	 */
	public function setup_actions() {

		// Add activity settings
		add_filter( 'econozel_admin_get_settings_fields', array( $this, 'add_settings_fields' ) );

		// Activity component
		if ( bp_is_active( 'activity' ) ) {
			add_action( 'bp_loaded', array( $this, 'setup_activity_actions' ), 20 );
		}
	}

	/** General ***************************************************************/

	/**
	 * Register additional admin settings fields
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields Settings fields
	 * @return array Settings fields
	 */
	public function add_settings_fields( $fields ) {

		// Activity component
		if ( bp_is_active( 'activity' ) ) {

			// Enable BP summaries
			$fields['econozel_settings_general']['_econozel_bp_enable_summary'] = array(
				'title'             => esc_html__( 'Article summaries', 'econozel' ),
				'callback'          => 'econozel_admin_bp_setting_callback_enable_summary',
				'sanitize_callback' => 'intval',
				'args'              => array()
			);
		}

		return $fields;
	}

	/** Activity **************************************************************/

	/**
	 * Setup actions for the activity component
	 *
	 * @since 1.0.0
	 */
	public function setup_activity_actions() {

		// After the post type is registered
		add_action( 'econozel_init', array( $this, 'activity_setup_post_type_tracking' ), 15 );

		// Modify queried activities
		add_filter( 'bp_activity_get',                  array( $this, 'bp_activity_get'                    ), 10, 2 );
		add_filter( 'bp_activity_get_where_conditions', array( $this, 'activity_filter_where_conditions'   ), 10, 2 );

		// Generate post summary
		add_filter( 'econozel_get_article_description', array( $this, 'get_post_summary' ), 10, 2 );
	}

	/**
	 * Initial logic for the activity component
	 *
	 * @since 1.0.0
	 */
	public function activity_setup_post_type_tracking() {

		// Register post type activity support
		add_post_type_support( $this->article_post_type, 'buddypress-activity' );

		// Register Article tracking args
		bp_activity_set_post_type_tracking_args( $this->article_post_type, array(

			// Register as part of the Blogs component
			'component_id'                      => bp_is_active( 'blogs' ) ? buddypress()->blogs->id : 'blogs',

			// Our own format logic
			'format_callback'                   => array( $this, 'activity_new_post_action' ),
			'contexts'                          => array( 'activity', 'member' ),
			'position'                          => 10,

			// Post labels
			'bp_activity_admin_filter'          => esc_html__( 'New Econozel Article',                                           'econozel' ),
			'bp_activity_front_filter'          => esc_html__( 'Econozel Articles',                                              'econozel' ),
			'bp_activity_new_post'              =>         __( '%1$s posted the <a href="%2$s">article</a>',                     'econozel' ),
			'bp_activity_new_post_ms'           =>         __( '%1$s posted the <a href="%2$s">article</a>, on the site %3$s',   'econozel' ),
			'new_article_action'                => esc_html__( '%1$s posted the article %2$s',                                   'econozel' ),
			'new_article_action_ms'             => esc_html__( '%1$s posted the article %2$s, on the site %3$s',                 'econozel' ),
			'new_article_in_edition_action'     => esc_html__( '%1$s posted the article %2$s in edition %3$s',                   'econozel' ),
			'new_article_in_edition_action_ms'  => esc_html__( '%1$s posted the article %2$s in edition %3$s, on the site %4$s', 'econozel' ),

			// Enable comment tracking. Should this be separate from normal comments?
			'comment_action_id'                 => "new_{$this->article_post_type}_comment",
			'comment_format_callback'           => array( $this, 'activity_new_comment_action' ),

			// Comment labels
			'bp_activity_comments_admin_filter' => esc_html__( 'New Article Comment',                                                'econozel' ),
			'bp_activity_comments_front_filter' => esc_html__( 'Comments'                                                                       ),
			'bp_activity_new_comment'           =>         __( '%1$s commented on the <a href="%2$s">article</a>',                   'econozel' ),
			'bp_activity_new_comment_ms'        =>         __( '%1$s commented on the <a href="%2$s">article</a>, on the site %3$s', 'econozel' ),
		) );

		// Filter arguments that cannot be set above
		add_filter( 'bp_activity_get_post_type_tracking_args', array( $this, 'activity_filter_post_type_tracking_args' ), 10, 2 );
	}

	/**
	 * Modify the activity post type tracking arguments
	 *
	 * @since 1.0.0
	 *
	 * @param object $tracking_args Tracking arguments
	 * @param string $post_type Post type name
	 * @return object Tracking arguments
	 */
	public function activity_filter_post_type_tracking_args( $args, $post_type ) {

		// Article post type tracking
		if ( $post_type == $this->article_post_type ) {

			/**
			 * Disable comment list filtering for Article comments when Blogs component is
			 * active, because Article comments are jointly presented with Blog comments.
			 */
			$args->comments_tracking->contexts = ! bp_is_active( 'blogs' ) ? array( 'activity', 'member' ) : array();

			// Set additional comments action strings
			$args->comments_tracking->new_article_comment_action    = esc_html__( '%1$s commented on the article %2$s',                   'econozel' );
			$args->comments_tracking->new_article_comment_action_ms = esc_html__( '%1$s commented on the article %2$s, on the site %3$s', 'econozel' );
		}

		return $args;
	}

	/**
	 * Define the activity New Post action text for Articles
	 *
	 * @see bp_activity_format_activity_action_custom_post_type_post()
	 * 
	 * @since 1.0.0
	 *
	 * @param string $action Activity action text
	 * @param object $activity Activity data object
	 * @return string Activity action text
	 */
	public function activity_new_post_action( $action, $activity ) {
		$bp = buddypress();

		// Fetch all the tracked post types once.
		if ( empty( $bp->activity->track ) ) {
			$bp->activity->track = bp_activity_get_post_types_tracking_args();
		}

		// Bail when the activity is invalid
		if ( empty( $activity->type ) || empty( $bp->activity->track[ $activity->type ] ) ) {
			return $action;
		}

		// Get the Article
		if ( $article = econozel_get_article( $activity->secondary_item_id ) ) {

			// Get tracking arguments for the post type which is stored by the action/type
			$track = $bp->activity->track[ $activity->type ];

			// Setup action elements. @todo Handle multiple authors
			$user_link = bp_core_get_userlink( $activity->user_id );
			$post_link = '<a href="' . esc_url( get_permalink( $article ) ) . '">' . get_the_title( $article ) . '</a>';
			$blog_link = '<a href="' . esc_url( get_home_url( $activity->item_id ) ) . '">' . get_blog_option( $activity->item_id, 'blogname' ) . '</a>';

			/*
			 * VGSR: do not use the ms action string when displaying the activity
			 * item on the original site where the post was published.
			 */
			$multisite = function_exists( 'vgsr' ) && is_multisite() && get_current_blog_id() !== (int) $activity->item_id;

			// Posted in an Edition
			if ( $edition = econozel_get_article_edition( $article ) ) {
				$edition_link = econozel_get_edition_link( $edition );

				if ( $multisite && ! empty( $track->new_article_in_edition_action_ms ) ) {
					$action = sprintf( $track->new_article_in_edition_action_ms, $user_link, $post_link, $edition_link, $blog_link );
				} elseif ( ! empty( $track->new_article_in_edition_action ) ) {
					$action = sprintf( $track->new_article_in_edition_action, $user_link, $post_link, $edition_link );
				}
			} else {
				if ( $multisite && ! empty( $track->new_article_action_ms ) ) {
					$action = sprintf( $track->new_article_action_ms, $user_link, $post_link, $blog_link );
				} elseif ( ! empty( $track->new_article_action ) ) {
					$action = sprintf( $track->new_article_action, $user_link, $post_link );
				}
			}

		// Default to the generic formatter
		} else {
			$action = bp_activity_format_activity_action_custom_post_type_post( $action, $activity );
		}

		return $action;
	}

	/**
	 * Define the activity New Comment action text for Article comments
	 *
	 * @since 1.0.0
	 *
	 * @param string $action Activity action text
	 * @param object $activity Activity data object
	 * @return string Activity action text
	 */
	public function activity_new_comment_action( $action, $activity ) {
		$bp = buddypress();

		// Fetch all the tracked post types once.
		if ( empty( $bp->activity->track ) ) {
			$bp->activity->track = bp_activity_get_post_types_tracking_args();
		}

		// Bail when the activity is invalid
		if ( empty( $activity->type ) || empty( $bp->activity->track[ $activity->type ] ) ) {
			return $action;
		}

		// Get the Article comment
		$comment = (int) $activity->secondary_item_id;
		$comment = get_comment( $comment );

		// Get the Article
		if ( $comment && $article = econozel_get_article( $comment->comment_post_ID ) ) {

			// Get tracking arguments for the post type which is stored by the action/type
			$track = $bp->activity->track[ $activity->type ];

			// Setup action elements. @todo Handle multiple authors
			$user_link = bp_core_get_userlink( $activity->user_id );
			$post_link = '<a href="' . esc_url( get_permalink( $article ) ) . '">' . get_the_title( $article ) . '</a>';
			$blog_link = '<a href="' . esc_url( get_home_url( $activity->item_id ) ) . '">' . get_blog_option( $activity->item_id, 'blogname' ) . '</a>';

			/*
			 * VGSR: do not use the ms action string when displaying the activity
			 * item on the original site where the post was published.
			 */
			$multisite = function_exists( 'vgsr' ) && is_multisite() && get_current_blog_id() !== (int) $activity->item_id;

			if ( $multisite && ! empty( $track->new_article_comment_action_ms ) ) {
				$action = sprintf( $track->new_article_comment_action_ms, $user_link, $post_link, $blog_link );
			} elseif ( ! empty( $track->new_article_comment_action ) ) {
				$action = sprintf( $track->new_article_comment_action, $user_link, $post_link );
			}

		// Default to the generic formatter
		} else {
			$action = bp_activity_format_activity_action_custom_post_type_comment( $action, $activity );
		}

		return $action;
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

		// Get BuddyPress
		$bp = buddypress();

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

/** Options ******************************************************************/

/**
 * Return whether to use BuddyPress to create Article summaries
 *
 * @since 1.0.0
 *
 * @param bool $default Optional. Defaults to False.
 * @return bool Are BP summaries enabled?
 */
function econozel_bp_enable_summary( $default = false ) {
	return (bool) apply_filters( 'econozel_bp_enable_summary', get_option( '_econozel_bp_enable_summary', $default ) );
}

/** Settings *****************************************************************/

/**
 * Display the content of the Article summaries settings field
 *
 * @since 1.0.0
 */
function econozel_admin_bp_setting_callback_enable_summary() { ?>

	<input name="_econozel_bp_enable_summary" id="_econozel_bp_enable_summary" type="checkbox" value="1" <?php checked( econozel_bp_enable_summary() ); ?>>
	<label for="_econozel_bp_enable_summary"><?php esc_html_e( 'Display more fancy article summaries when they contain media', 'econozel' ); ?></label>

	<?php
}

endif; // class_exists
