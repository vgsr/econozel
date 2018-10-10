<?php

/**
 * Econozel BuddyPress Functions
 *
 * @package Econozel
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Options ******************************************************************/

/**
 * Return whether to use BuddyPress to create Article summaries
 *
 * @since 1.0.0
 *
 * @return bool Are BP summaries enabled?
 */
function econozel_bp_enable_summary() {
	return (bool) apply_filters( 'econozel_bp_enable_summary', false );
}

/**
 * Return whether to point author urls to their published articles
 *
 * @since 1.0.1
 *
 * @param int $user_id Optional. User ID. Defaults to none.
 * @return bool Link author urls to published articles?
 */
function econozel_bp_published_articles_author_url( $user_id = 0 ) {
	return (bool) apply_filters( 'econozel_bp_published_articles_author_url', true, $user_id );
}

/** Components ***************************************************************/

/**
 * Return the Econozel component name
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_bp_get_component'
 * @return string Component name
 */
function econozel_bp_get_component() {

	// Use existing ID
	if ( ! empty( econozel()->extend->buddypress->id ) ) {
		$retval = econozel()->extend->buddypress->id;

	// Use default
	} else {
		$retval = 'econozel';
	}

	return apply_filters( 'econozel_bp_get_component', $retval );
}

/**
 * Check whether the current page is part of the Econozel component
 *
 * @since 1.0.0
 *
 * @return bool Is this a member's Econozel page?
 */
function bp_is_econozel_component() {
	return (bool) bp_is_current_component( econozel_bp_get_component() );
}

/**
 * Return whether this is the member's Articles page
 *
 * @since 1.0.0
 *
 * @return bool Is this the member's Articles page?
 */
function econozel_bp_is_member_articles() {
	return bp_is_econozel_component() && bp_is_current_action( econozel_bp_get_article_slug() );
}

/**
 * Return the profile Article slug
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_bp_get_article_slug'
 * @return string Profile article slug
 */
function econozel_bp_get_article_slug() {
	return apply_filters( 'econozel_bp_get_article_slug', get_option( '_econozel_article_slug', 'articles' ) );
}

/** Screens ******************************************************************/

/**
 * Hook Articles template into plugins template
 *
 * @since 1.0.0
 */
function econozel_bp_member_screen_articles() {
	add_action( 'bp_template_content', 'econozel_bp_member_articles_content' );
	bp_core_load_template( apply_filters( 'econozel_bp_member_screen_articles', 'members/single/plugins' ) );
}

/**
 * Get the member's articles template part
 *
 * @since 1.0.0
 *
 * @todo Use a template file?
 */
function econozel_bp_member_articles_content() {

	// Query author articles
	if ( econozel_query_articles( array(
		'author'         => bp_displayed_user_id(),
		'paged'          => econozel_get_paged(),
		'posts_per_page' => 5
	) ) ) : ?>

		<div id="econozel-articles">
			<h2 class="entry-title"><?php esc_html_e( 'Articles published', 'econozel' ); ?></h2>

			<div class="econozel-pagination pagination">
				<div class="econozel-pagination-count pag-count">
					<?php econozel_the_article_pagination_count(); ?>
				</div>

				<div class="econozel-pagination-links pagination-links">
					<?php econozel_the_article_pagination_links(); ?>
				</div>
			</div>

			<?php while ( econozel_has_articles() ) : econozel_the_article(); ?>

			<?php econozel_get_template_part( 'content', 'econozel-article' ); ?>

			<?php endwhile; ?>
		</div>

		<?php

		// Override paging options
		$GLOBALS['wp_query']->max_num_pages = econozel()->article_query->max_num_pages;

		// Display native posts navigation
		econozel_the_posts_navigation( array(
			'prev_text'          => esc_html__( 'Older articles',      'econozel' ),
			'next_text'          => esc_html__( 'Newer articles',      'econozel' ),
			'screen_reader_text' => esc_html__( 'Articles navigation', 'econozel' )
		) );

		// Reset paging options
		$GLOBALS['wp_query']->max_num_pages = 1;

		?>

	<?php else : ?>

		<p><?php bp_is_my_profile() ? esc_html_e( 'You have not published any articles.', 'econozel' ) : esc_html_e( 'This user has not published any articles.', 'econozel' ); ?></p>

	<?php endif;
}

/** Activity *****************************************************************/

/**
 * Get the registered Article activity ids
 *
 * @since 1.0.0
 *
 * @uses WPDB $wpdb
 *
 * @param string $type Activity type. Either 'post' or 'comment'.
 * @return array Article activity ids
 */
function econozel_bp_activity_get_article_activities( $type = '' ) {
	global $wpdb;

	/**
	 * Cache activity ids per type, activity post or comment in static var
	 * so we only have to fetch those once per activity type.
	 */
	static $activity_ids = null;

	if ( null === $activity_ids ) {

		// Setup local variable
		$activity_ids = array( 'post' => array(), 'comment' => array() );

		// Get BuddyPress
		$bp = buddypress();

		// Activity post args
		$activity_post_object = bp_activity_get_post_type_tracking_args( econozel_get_article_post_type() );

		// Load both post and comment activities
		foreach ( array( 'post', 'comment' ) as $activity_type ) {

			// Get the action id for the Article activity type
			$action_id = $activity_type === 'comment'
				? $activity_post_object->comments_tracking->action_id
				: $activity_post_object->action_id;

			// Get activity ids of tye type by a custom SQL query
			$result = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$bp->activity->table_name} WHERE type = %s", $action_id ) );

			// Sanitize activity ids and store in static array
			$activity_ids[ $activity_type ] = array_values( array_unique( array_map( 'intval', $result ) ) );
		}
	}

	// Get activity ids by type
	if ( ! in_array( $type, array( 'post', 'comment' ) ) ) {
		$retval = array();
	} else {
		$retval = $activity_ids[ $type ];
	}

	return $retval;
}
