<?php

/**
 * Econozel User Functions
 *
 * @package Econozel
 * @subpackage Users
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Output the user's display name
 *
 * @since 1.0.0
 *
 * @param int $user_id User ID
 */
function econozel_the_user_displayname( $user_id ) {
	echo econozel_get_user_displayname( $user_id );
}

	/**
	 * Return the user's display name
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID
	 * @return string User display name
	 */
	function econozel_get_user_displayname( $user_id ) {

		// Define return value
		$name = '';

		// Get the user
		if ( $user = get_userdata( $user_id ) ) {
			$name = $user->display_name;
		}

		return apply_filters( 'econozel_get_user_displayname', $name, $user_id );
	}

/**
 * Act when a user is about to be deleted
 *
 * @since 1.0.0
 *
 * @uses WPDB $wpdb
 *
 * @param int $user_id User ID
 * @param int $reassign Reassign user ID
 */
function econozel_delete_user( $user_id, $reassign ) {
	global $wpdb;

	// No reassignment, so shift post authorship
	if ( null === $reassign ) {

		// Query all posts by author
		$query    = $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_author = %d", econozel_get_article_post_type(), $user_id );
		$post_ids = $query->get_col( $query );

		// Walk all author's articles
		foreach ( $post_ids as $post_id ) {

			// Get all article co-authors
			$co_authors = array_values( array_diff( econozel_get_article_author( $post_id ), array( $user_id ) ) );

			// Reassign this post to the first co-author. When there's a single author
			// the post will be processed by WP the usual way.
			if ( $co_authors ) {
				$wpdb->update( $wpdb->posts, array( 'post_author' => $co_authors[0] ), array( 'ID' => $post_id ), array( '%d' ), array( '%d' ) );
			}
		}
	}
}

/**
 * Act when a user was deleted
 *
 * @since 1.0.0
 *
 * @param int $user_id User ID
 * @param int $reassign Reassign user ID
 */
function econozel_deleted_user( $user_id, $reassign ) {
	global $wpdb;

	// No reassignment, so remove post author meta
	if ( null === $reassign ) {

		// Delete article post authors in metadata
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'post_author', 'meta_value' => $user_id ) );

	// Reassign to provided user
	} else {

		// Update article post authors in metadata
		$wpdb->update( $wpdb->postmeta, array( 'meta_key' => 'post_author', 'meta_value' => $user_id ), array( 'meta_value' => $reassign ) );
	}
}

/** Admin Bar *****************************************************************/

/**
 * Register hooks for modifying the admin bar menus
 *
 * @since 1.0.0
 */
function econozel_add_admin_bar_menus() {
	add_action( 'admin_bar_menu', 'econozel_admin_bar_menu_for_editors', 90 );
}

/**
 * Modify the admin bar menus
 *
 * @since 1.0.0
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function econozel_admin_bar_menu_for_editors( $wp_admin_bar ) {

	// For Econozel Editors on plugin pages
	if ( ! is_admin() && is_econozel() && current_user_can( 'edit_others_econozel_articles' ) ) {

		// Add Manage Articles item
		$wp_admin_bar->add_node( array(
			'id'    => 'econozel-articles',
			'title' => esc_html__( 'Manage Articles', 'econozel' ),
			'href'  => admin_url( add_query_arg( 'post_type', econozel_get_article_post_type(), 'edit.php' ) )
		) );
	}
}
