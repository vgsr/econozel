<?php

/**
 * Econozel Articles Functions
 * 
 * @package Econozel
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Query *********************************************************************/

/**
 * Setup and run the Articles query
 *
 * @since 1.0.0
 *
 * @param array $args Query arguments.
 * @return bool Has the query returned any results?
 */
function econozel_query_articles( $args = array() ) {

	// Get query object
	$query = econozel()->article_query;

	// Reset query defaults
	$query->in_the_loop  = false;
	$query->current_post = -1;
	$query->post_count   = 0;
	$query->post         = null;
	$query->posts        = array();

	// Define query args
	$query_args = wp_parse_args( $args, array(
		'econozel_edition' => econozel_get_edition_id(),
		'post_type'        => econozel_get_article_post_type(),
		'posts_per_page'   => -1,
		'paged'            => econozel_get_paged(),
		'fields'           => 'all'
	) );

	// Run query to get the posts
	$query->query( $query_args );

	// Return whether the query has returned results
	return $query->have_posts();
}

/**
 * Return whether the query has Articles to loop over
 *
 * @since 1.0.0
 *
 * @return bool Query has Articles
 */
function econozel_has_articles() {

	// Has query a next post?
	$has_next = econozel()->article_query->have_posts();

	// Clean up after ourselves
	if ( ! $has_next ) {
		wp_reset_postdata();
	}

	return $has_next;
}

/**
 * Setup next Article in the current loop
 *
 * @since 1.0.0
 *
 * @return bool Are we still in the loop?
 */
function econozel_the_article() {
	return econozel()->article_query->the_post();
}

/**
 * Return whether we're in the Article loop
 *
 * @since 1.0.0
 *
 * @return bool Are we in the Article loop?
 */
function econozel_in_the_article_loop() {
	return econozel()->article_query->in_the_loop;
}

/** Template ******************************************************************/

/**
 * Return the Article's post
 *
 * @since 1.0.0
 *
 * @param WP_Post|int $article Optional. Defaults to the current post.
 * @return WP_Post|bool Post object when it is an Article, else False.
 */
function econozel_get_article( $article = 0 ) {

	// Get the post
	$article = get_post( $article );

	// Verify Aritcle post type
	if ( ! $article || econozel_get_article_post_type() != $article->post_type ) {
		$article = false;
	}

	return $article;
}

/**
 * Return the Article's Volume
 *
 * @since 1.0.0
 *
 * @param WP_Post|int $article Optional. Defaults to the current post.
 * @param bool $object Optional. Whether to return term object or ID. Defaults to ID.
 * @return WP_Term|int|bool Volume term object or ID when found, else False.
 */
function econozel_get_article_volume( $article = 0, $object = false ) {

	// Bail when Edition does not exist
	if ( ! $edition = econozel_get_article_edition( $article ) )
		return false;

	// Get the Edition's Volume
	$volume = econozel_get_edition_volume( $edition, $object );

	return $volume;
}

/**
 * Return the Article's Edition
 *
 * @since 1.0.0
 *
 * @param WP_Post|int $article Optional. Defaults to the current post.
 * @param bool $object Optional. Whether to return term object or ID. Defaults to ID.
 * @return WP_Term|int|bool Edition term object or ID when found, else False.
 */
function econozel_get_article_edition( $article = 0, $object = false ) {

	// Bail when post does not exist
	if ( ! $article = econozel_get_article( $article ) )
		return false;

	// Define return var
	$edition = false;

	// Get the Article's Edition terms
	$term_args = array( 'fields' => ( false === $object ) ? 'ids' : 'all' );
	$terms     = wp_get_object_terms( $article->ID, econozel_get_edition_tax_id(), $term_args );

	// Assign term ID when found
	if ( ! empty( $terms ) ) {
		$edition = $terms[0];
	}

	return $edition;
}

/**
 * Return whether the Article has a Edition
 *
 * @since 1.0.0
 *
 * @param WP_Post|int $article Optional. Defaults to the current post.
 * @return bool Article has a Edition
 */
function econozel_has_article_edition( $article = 0 ) {
	return ! empty( econozel_get_article_edition( $article ) );
}

/**
 * Output the Article Edition label
 *
 * @since 1.0.0
 *
 * @param WP_Post|int $article Optional. Defaults to the current post.
 */
function econozel_the_article_edition_label( $article = 0 ) {
	echo econozel_get_article_edition_label( $article );
}

	/**
	 * Return the Article Edition label
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post|int $article Optional. Defaults to the current post.
	 * @return string Article Edition label
	 */
	function econozel_get_article_edition_label( $article = 0 ) {

		// Get Article's Edition
		$edition = econozel_get_article_edition( $article );

		// Get Edition label
		$label = econozel_get_edition_label( $edition );

		return $label;
	}
