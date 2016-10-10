<?php

/**
 * Econozel Volume Functions
 * 
 * @package Econozel
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Taxonomy ******************************************************************/

/**
 * Return the Volume taxonomy id
 *
 * @since 1.0.0
 *
 * @return string Taxonomy id
 */
function econozel_get_volume_tax_id() {
	return 'econozel_volume';
}

/**
 * Return the labels for the Volume taxonomy
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_volume_tax_labels'
 * @return array Volume taxonomy labels
 */
function econozel_get_volume_tax_labels() {
	return apply_filters( 'econozel_get_volume_tax_labels', array(
		'name'          => __( 'Econozel Volumes',  'econozel' ),
		'menu_name'     => __( 'Volumes',           'econozel' ),
		'singular_name' => __( 'Econozel Volume',   'econozel' ),
		'search_items'  => __( 'Search Volumes',    'econozel' ),
		'popular_items' => null, // Disable tagcloud
		'all_items'     => __( 'All Volumes',       'econozel' ),
		'no_items'      => __( 'No Volume',         'econozel' ),
		'edit_item'     => __( 'Edit Volume',       'econozel' ),
		'update_item'   => __( 'Update Volume',     'econozel' ),
		'add_new_item'  => __( 'Add New Volume',    'econozel' ),
		'new_item_name' => __( 'New Volume Name',   'econozel' ),
		'view_item'     => __( 'View Volume',       'econozel' )
	) );
}

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
	$query->terms        = array();

	// Define query args
	$r = wp_parse_args( $args, array(
		'taxonomy'        => econozel_get_volume_tax_id(),
		'number'          => econozel_get_volumes_per_page(),
		'paged'           => econozel_get_paged(),
		'fields'          => 'all',
		'hide_empty'      => true
	) );

	// Pagination
	if ( $r['number'] != -1 ) {
		$r['paged'] = absint( $r['paged'] );
		if ( $r['paged'] == 0 ) {
			$r['paged'] = 1;
		}
		$r['offset'] = absint( ( $r['paged'] - 1 ) * $r['number'] );
	}

	// Run query to get the taxonomy terms
	if ( class_exists( 'WP_Term_Query' ) ) {
		$query->query( $r );
	} else {
		$query->terms = get_terms( $r['taxonomy'], $r );
	}

	// Set query results
	$query->term_count = count( $query->terms );
	if ( $query->term_count > 0 ) {
		$query->term = $query->terms[0];
	}

	// Determine the total term count
	if ( isset( $r['offset'] ) && ! $query->term_count < $r['number'] ) {
		$query->found_terms = econozel_query_terms_found_rows( $r );
	} else {
		$query->found_terms = $query->term_count;
	}
	if ( $query->found_terms > $query->term_count ) {
		$query->max_num_pages = (int) ceil( $query->found_terms / $r['number'] );
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

	// Current element is not the last
	$has_next = $query->term_count && $query->current_term < end( $term_keys );

	// We're in the loop when there are still elements
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

		// ... the query var on Volume or Edition pages
		} elseif ( get_query_var( 'econozel_volume' ) ) {
			$volume = get_term( (int) get_query_var( 'econozel_volume' ), econozel_get_volume_tax_id() );
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
 * Return the current Volume's adjacent Volume
 *
 * @see get_adjacent_post()
 *
 * @since 1.0.0
 *
 * @global WPDB $wpdb
 *
 * @param bool $previous Whether to get the previous Volume. Defaults to False.
 * @return WP_Term|bool The adjacent Volume or False when not found.
 */
function econozel_get_adjacent_volume( $previous = false ) {
	global $wpdb;

	// Define return value
	$volume = false;

	// Define local variable(s)
	$_volume = econozel_get_volume();
	$order   = $previous ? 'DESC' : 'ASC';
	$op      = $previous ? '<' : '>';

	/**
	 * Define term query clauses.
	 *
	 * Volumes are ordered by numeric slug. Only non-empty Volumes with
	 * Editions having Articles are valid to be listed.
	 */
	$join  = " INNER JOIN {$wpdb->term_taxonomy} tt ON ( t.term_id = tt.term_taxonomy_id )"; // Volume taxonomy
	$join .= " INNER JOIN {$wpdb->term_relationships} AS editions_tr ON ( t.term_id = editions_tr.term_taxonomy_id )"; // Edition relationship
	$join .= " INNER JOIN {$wpdb->term_taxonomy} AS editions ON ( editions_tr.object_id = editions.term_taxonomy_id )"; // Edition taxonomy
	$where = $wpdb->prepare( "WHERE ( tt.taxonomy = %s ) AND editions.count > 0 AND ( CAST( t.slug AS SIGNED ) $op CAST( %d AS SIGNED ) )", econozel_get_volume_tax_id(), (int) $_volume->slug );
	$sort  = "ORDER BY CAST( t.slug AS SIGNED ) $order LIMIT 1";

	// Construct query, use caching as in `get_adjacent_post()`.
	$query = "SELECT t.term_id FROM {$wpdb->terms} AS t $join $where $sort";
	$query_key = 'econozel_adjacent_term_' . md5( $query );
	$result = wp_cache_get( $query_key, 'counts' );
	if ( false !== $result ) {
		if ( $result ) {
			$volume = econozel_get_volume( $result );
		}
	}

	$result = $wpdb->get_var( $query );
	if ( null === $result ) {
		$result = '';
	}

	wp_cache_set( $query_key, $result, 'counts' );

	if ( $result ) {
		$volume = econozel_get_volume( $result );
	}

	return $volume;
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
				esc_attr( sprintf( esc_html__( 'View articles in %s', 'econozel' ), econozel_get_volume_title( $volume ) ) ),
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
	echo esc_url( econozel_get_volume_url( $volume ) );
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
			$url = get_term_link( $volume );
		}

		return $url;
	}

/**
 * Return the Volume archive url
 *
 * @since 1.0.0
 *
 * @return string Volume archive url
 */
function econozel_get_volume_archive_url() {
	return home_url( user_trailingslashit( econozel_get_volume_slug() ) );
}
