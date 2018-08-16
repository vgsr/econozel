<?php

/**
 * Econozel BuddyPress Activity Functions
 *
 * @package Econozel
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Initial logic for the activity component
 *
 * @since 1.0.0
 */
function econozel_bp_activity_setup_post_type_tracking() {
	$post_type = econozel_get_article_post_type();

	// Register post type activity support
	add_post_type_support( $post_type, 'buddypress-activity' );

	// Register Article tracking args
	bp_activity_set_post_type_tracking_args( $post_type, array(

		/**
		 * Register as part of the Blogs component. This is done because
		 * it lists the activity filter with the other Blogs actions in
		 * the dropdown.
		 *
		 * TODO: other/better reason?
		 */
		'component_id'                      => bp_is_active( 'blogs' ) ? buddypress()->blogs->id : 'blogs',

		// Our own format logic
		'format_callback'                   => 'econozel_bp_activity_new_post_action',
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
		'comment_action_id'                 => "new_{$post_type}_comment",
		'comment_format_callback'           => 'econozel_bp_activity_new_comment_action',

		// Comment labels
		'bp_activity_comments_admin_filter' => esc_html__( 'New Article Comment',                                                'econozel' ),
		'bp_activity_comments_front_filter' => esc_html__( 'Comments'                                                                       ),
		'bp_activity_new_comment'           =>         __( '%1$s commented on the <a href="%2$s">article</a>',                   'econozel' ),
		'bp_activity_new_comment_ms'        =>         __( '%1$s commented on the <a href="%2$s">article</a>, on the site %3$s', 'econozel' ),
	) );

	// Filter arguments that cannot be set above
	add_filter( 'bp_activity_get_post_type_tracking_args', 'econozel_bp_activity_filter_post_type_tracking_args', 10, 2 );
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
function econozel_bp_activity_filter_post_type_tracking_args( $args, $post_type ) {

	// Article post type tracking
	if ( $post_type === econozel_get_article_post_type() ) {

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
function econozel_bp_activity_new_post_action( $action, $activity ) {
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
function econozel_bp_activity_new_comment_action( $action, $activity ) {
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