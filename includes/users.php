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
