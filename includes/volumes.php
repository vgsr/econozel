<?php

/**
 * Econozel Volumes Functions
 * 
 * @package Econozel
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Query *********************************************************************/

/**
 * Setup and run the Volumes query
 *
 * @since 1.0.0
 *
 * @todo Ordering by issue.
 *
 * @param array $args Query arguments.
 * @return bool Has the query returned any results?
 */
function econozel_query_volumes( $args = array() ) {

	// Get query object
	$query = econozel()->volume_query;

	// Reset query defaults
	$query->in_the_loop  = false;
	$query->current_term = -1;
	$query->term_count   = 0;
	$query->term         = null;

	// Define query args
	$query_args = wp_parse_args( $args, array(
		'taxonomy'        => econozel_get_volume_tax_id(),
		'number'          => get_option( 'posts_per_page' ),
		'paged'           => econozel_get_paged(),
		'fields'          => 'all',
		'hide_empty'      => true
	) );

	// Pagination
	if ( $query_args['number'] != -1 ) {
		$query_args['paged'] = absint( $query_args['paged'] );
		if ( $query_args['paged'] == 0 ) {
			$query_args['paged'] = 1;
		}
		$query_args['offset'] = absint( ( $query_args['paged'] - 1 ) * $query_args['number'] );
	}

	// Run query to get the taxonomy terms
	if ( class_exists( 'WP_Term_Query' ) ) {
		$query->query( $query_args );
	} else {
		$query->terms = get_terms( $query_args['taxonomy'], $query_args );
	}

	// Set query results
	$query->term_count = count( $query->terms );
	if ( $query->term_count > 0 ) {
		$query->term = $query->terms[0];
	}

	// Determine the total term count
	if ( isset( $query_args['offset'] ) && ! $query->term_count < $query_args['number'] ) {
		$query->found_terms = econozel_query_terms_found_rows( $query_args );
	} else {
		$query->found_terms = $query->term_count;
	}
	if ( $query->found_terms > $query->term_count ) {
		$query->max_num_pages = (int) ceil( $query->found_terms / $query_args['number'] );
	} else {
		$query->max_num_pages = 1;
	}

	// Return whether the query has returned results
	return econozel_has_volumes();
}

/**
 * Return whether the query has Volumes to loop over
 *
 * @since 1.0.0
 *
 * @return bool Query has Volumes
 */
function econozel_has_volumes() {

	// Get query object
	$query = econozel()->volume_query;

	// Get array keys
	$term_keys = array_keys( $query->terms );

	// This was not the last element
	$has_next = ( $query->current_term != end( $term_keys ) );

	// We're in the loop when there are still posts
	if ( ! $has_next ) {
		$query->in_the_loop = false;

		// Clean up after the loop
		econozel_rewind_volumes();
	}

	return $has_next;
}

/**
 * Setup next Volume in the current loop
 *
 * @since 1.0.0
 *
 * @return bool Are we still in the loop?
 */
function econozel_the_volume() {

	// Get query object
	$query = econozel()->volume_query;

	// We're looping
	$query->in_the_loop = true;

	// Increase current term index
	$query->current_term++;

	// Get next term in list
	$query->term = $query->terms[ $query->current_term ];

	return $query->term;
}

/**
 * Rewind the volumes and reset term index
 *
 * @since 1.0.0
 */
function econozel_rewind_volumes() {

	// Get query object
	$query = econozel()->volume_query;

	// Reset current term index
	$query->current_term = -1;

	if ( $query->term_count > 0 ) {
		$query->term = $query->terms[0];
	}
}

/**
 * Return whether we're in the Volume loop
 *
 * @since 1.0.0
 *
 * @return bool Are we in the Volume loop?
 */
function econozel_in_the_volume_loop() {
	return isset( econozel()->volume_query->in_the_loop ) ? econozel()->volume_query->in_the_loop : false;
}

/** Template ******************************************************************/

/**
 * Return the Volume taxonomy term
 *
 * @since 1.0.0
 *
 * @param WP_Post|string|int $volume Optional. Defaults to the current Volume.
 * @param string $by Optional. Method to fetch term through `get_term_by()`. Defaults to 'id'.
 * @return WP_Term|bool Volume term object when found, else False.
 */
function econozel_get_volume( $volume = 0, $by = 'id' ) {

	// Default empty parameter to ...
	if ( empty( $volume ) ) {

		// ... the Volume in the loop
		if ( econozel_in_the_volume_loop() ) {
			$volume = econozel()->volume_query->term;

		// ... the queried object on Volume pages
		} elseif ( econozel_is_volume() ) {
			$volume = get_queried_object();

		// ... Volume by Edition
		} elseif ( econozel_is_edition() ) {
			$volume = econozel_get_edition_volume( null, true );
		}

	// Get Volume by Article
	} elseif ( ( empty( $volume ) && econozel_is_article() ) || ( $volume instanceof WP_Post ) ) {
		$article = econozel_get_article( $volume );
		$volume  = econozel_get_article_volume( $article, true );

	// Get the term by id or slug
	} elseif ( ! $volume instanceof WP_Term ) {
		$volume = get_term_by( $by, $volume, econozel_get_volume_tax_id() );
	}

	// Reduce error to false
	if ( ! $volume || is_wp_error( $volume ) ) {
		$volume = false;
	}

	return $volume;
}

/**
 * Return the Volume's Editions
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $volume Optional. Defaults to the current Volume.
 * @param bool $object Optional. Whether to return term objects. Defaults to false.
 * @return array Volume Edition term objects or ID's.
 */
function econozel_get_volume_editions( $volume = 0, $object = false ) {

	// Define return var
	$editions = array();

	// Get Volume term object
	if ( $volume = econozel_get_volume( $volume ) ) {

		// Use `get_terms()` to enable query filtering
		$editions = get_terms( econozel_get_edition_tax_id(), array(
			'econozel_volume' => $volume->term_id, // Implements WP_Tax_Query
			'include'         => $editions,
			'fields'          => $object ? 'all' : 'ids'
		) );
	}

	return $editions;
}

/**
 * Return the Volume's Articles
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $volume Optional. Defaults to the current Volume.
 * @param bool $object Optional. Whether to return post objects. Defaults to false.
 * @return array Volume Article post objects or ID's.
 */
function econozel_get_volume_articles( $volume = 0, $object = false ) {

	// Define return var
	$articles = array();

	// Get Volume term object
	if ( $volume = econozel_get_volume( $volume ) ) {

		// Get the Volume's editions
		$editions = econozel_get_volume_editions( $volume );

		// Use `WP_Query` to enable query filtering
		if ( ! empty( $editions ) && ( $query = new WP_Query( array(
			'post_type' => econozel_get_article_post_type(),
			'fields'    => $object ? 'all' : 'ids',
			'tax_query' => array(
				array(
					'taxonomy' => econozel_get_edition_tax_id(),
					'terms'    => $editions,
					'field'    => 'term_id'
				)
			)
		) ) ) ) {
			$articles = $query->posts;
		}
	}

	return $articles;
}

/**
 * Output the current Volume's term ID
 *
 * @since 1.0.0
 */
function econozel_the_volume_id() {
	echo econozel_get_volume_id();
}

	/**
	 * Return the current Volume's term ID
	 *
	 * @since 1.0.0
	 *
	 * @return int|bool Volume ID or False when not found.
	 */
	function econozel_get_volume_id() {
		if ( $volume = econozel_get_volume() ) {
			return $volume->term_id;
		}

		return false;
	}

/**
 * Output the Volume's title
 *
 * @since 1.0.0
 *
 * @param WP_Term|WP_Post|int $volume Optional. Defaults to the current volume.
 */
function econozel_the_volume_title( $volume = 0 ) {
	echo econozel_get_volume_title( $volume );
}

	/**
	 * Return the Volume's title
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term|WP_Post|int $volume Optional. Defaults to the current volume.
	 * @return string Volume title.
	 */
	function econozel_get_volume_title( $volume = 0 ) {

		// Define return var
		$title = '';

		// Get Volume term object
		if ( $volume = econozel_get_volume( $volume ) ) {
			$title = $volume->name;
		}

		// Prepend title with 'Volume'
		if ( ! empty( $title ) && econozel_prepend_volume_title() ) {
			$title = sprintf( esc_html__( 'Volume %s', 'econozel' ), $title );
		}

		return $title;
	}

/**
 * Output the Volume's permalink
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $volume Optional. Defaults to the current post's Volume.
 */
function econozel_the_volume_link( $volume = 0 ) {
	echo econozel_get_volume_link( $volume );
}

	/**
	 * Return the Volume's permalink
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term|int $volume Optional. Defaults to the current post's Volume.
	 * @return string Volume permalink
	 */
	function econozel_get_volume_link( $volume = 0 ) {

		// Define return var
		$link = '';

		if ( $volume = econozel_get_volume( $volume ) ) {
			$link = sprintf(
				'<a href="%1$s" title="%2$s" rel="collection">%3$s</a>',
				esc_url( econozel_get_volume_url( $volume ) ),
				esc_attr( sprintf( esc_html__( 'Articles in %s', 'econozel' ), econozel_get_volume_title( $volume ) ) ),
				econozel_get_volume_title( $volume )
			);
		}

		return $link;
	}

/**
 * Output the Volume's url
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $volume Optional. Defaults to the current post's Volume.
 */
function econozel_the_volume_url( $volume = 0 ) {
	echo econozel_get_volume_url( $volume );
}

	/**
	 * Return the Volume's url
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term|int $volume Optional. Defaults to the current post's Volume.
	 * @return string Volume url
	 */
	function econozel_get_volume_url( $volume = 0 ) {

		// Define return var
		$url = '';

		if ( $volume = econozel_get_volume( $volume ) ) {
			$url = sprintf( '/%s/%s', econozel_get_volume_slug(), $volume->slug );
			$url = home_url( user_trailingslashit( $url ) );
		}

		return $url;
	}
