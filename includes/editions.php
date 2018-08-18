<?php

/**
 * Econozel Edition Functions
 * 
 * @package Econozel
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Taxonomy ******************************************************************/

/**
 * Return the Edition taxonomy id
 *
 * @since 1.0.0
 *
 * @return string Taxonomy id
 */
function econozel_get_edition_tax_id() {
	return 'econozel_edition';
}

/**
 * Return the labels for the Edition taxonomy
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_edition_tax_labels'
 * @return array Edition taxonomy labels
 */
function econozel_get_edition_tax_labels() {
	return apply_filters( 'econozel_get_edition_tax_labels', array(
		'name'          => __( 'Econozel Editions',  'econozel' ),
		'menu_name'     => __( 'Editions',           'econozel' ),
		'singular_name' => __( 'Econozel Edition',   'econozel' ),
		'search_items'  => __( 'Search Editions',    'econozel' ),
		'popular_items' => null, // Disable tagcloud
		'all_items'     => __( 'All Editions',       'econozel' ),
		'no_items'      => __( 'No Edition',         'econozel' ),
		'edit_item'     => __( 'Edit Edition',       'econozel' ),
		'update_item'   => __( 'Update Edition',     'econozel' ),
		'add_new_item'  => __( 'Add New Edition',    'econozel' ),
		'new_item_name' => __( 'New Edition Name',   'econozel' ),
		'view_item'     => __( 'View Edition',       'econozel' )
	) );
}

/**
 * Add Edition details for taxonomy meta registration
 *
 * @since 1.0.0
 *
 * @param array $meta Meta fields to register
 * @return array Meta fields
 */
function econozel_add_edition_tax_meta( $meta ) {

	// Append Edition meta
	$meta[ econozel_get_edition_tax_id() ] = array(

		// Issue number
		'issue' => array(
			'labels'          => array(
				'singular'        => esc_html__( 'Issue', 'econozel' ),
				'plural'          => esc_html__( 'Issues', 'econozel' ),
				'description'     => esc_html__( "The Edition's issue type within the Volume.", 'econozel' ),
			),
			'type'            => 'select',
			'options'         => econozel_get_edition_issue_whitelist( false ),
			'sanitize_cb'     => 'econozel_edition_whitelist_issue',
			'admin_column_cb' => true,
			'inline_edit'     => true
		),

		// Document
		'document' => array(
			'labels'          => array(
				'singular'        => esc_html__( 'Document', 'econozel' ),
				'plural'          => esc_html__( 'Documents', 'econozel' ),
				'description'     => esc_html__( 'The published document as a PDF file.', 'econozel' ),

				// Help tab
				'help_title'      => esc_html__( 'Document', 'econozel' ),
				'help_content'    => esc_html__( 'The document is the digital reference to the physical publication.', 'econozel' ),

				// JS interface
				'setTermMedia'    => esc_html__( 'Set document', 'econozel' ),
				'termMediaTitle'  => esc_html__( '%s Document', 'econozel' ),
				'removeTermMedia' => esc_html__( 'Remove %s document', 'econozel' ),
				'error'           => esc_html__( "Could not set that as the document. Try a different attachment.", 'econozel' ),
			),
			'type'            => 'media',
			'mime_type'       => 'application/pdf',
			'admin_column_cb' => true,
		),
	);

	return $meta;
}

/** Query *********************************************************************/

/**
 * Setup and run the Editions query
 *
 * @since 1.0.0
 *
 * @param array $args Query arguments.
 * @return bool Has the query returned any results?
 */
function econozel_query_editions( $args = array() ) {

	// Get query object
	$query = econozel()->edition_query;

	// Reset query defaults
	$query->in_the_loop  = false;
	$query->current_term = -1;
	$query->term_count   = 0;
	$query->term         = null;
	$query->terms        = array();

	// Define query args
	$r = wp_parse_args( $args, array(
		'econozel_volume'    => econozel_get_volume_id(),
		'taxonomy'           => econozel_get_edition_tax_id(),
		'number'             => econozel_get_editions_per_page(),
		'paged'              => econozel_get_paged(),
		'fields'             => 'all',
		'hide_empty'         => true,
		'show_with_document' => false
	) );

	// When querying by Volume, query all
	if ( ! empty( $r['econozel_volume'] ) && term_exists( $r['econozel_volume'], econozel_get_volume_tax_id() ) ) {
		$r['number'] = 0;
	}

	// Pagination
	if ( (int) $r['number'] > 0 ) {
		$r['paged'] = absint( $r['paged'] );
		if ( $r['paged'] == 0 ) {
			$r['paged'] = 1;
		}
		$r['offset'] = absint( ( $r['paged'] - 1 ) * (int) $r['number'] );
	} else {
		$r['number'] = 0;
	}

	// Run query to get the taxonomy terms
	if ( is_a( $query, 'WP_Term_Query' ) ) {
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
	return econozel_has_editions();
}

/**
 * Return whether the query has Editions to loop over
 *
 * @since 1.0.0
 *
 * @return bool Query has Editions
 */
function econozel_has_editions() {

	// Get query object
	$query = econozel()->edition_query;

	// Get array keys
	$term_keys = array_keys( $query->terms );

	// Current element is not the last
	$has_next = $query->term_count && $query->current_term < end( $term_keys );

	// We're in the loop when there are still elements
	if ( ! $has_next ) {
		$query->in_the_loop = false;

		// Clean up after the loop
		econozel_rewind_editions();
	}

	return $has_next;
}

/**
 * Setup next Edition in the current loop
 *
 * @since 1.0.0
 */
function econozel_the_edition() {

	// Get query object
	$query = econozel()->edition_query;

	// We're looping
	$query->in_the_loop = true;

	// Increase current term index
	$query->current_term++;

	// Get next term in list
	if ( isset( $query->terms[ $query->current_term ] ) ) {
		$query->term = $query->terms[ $query->current_term ];
	}
}

/**
 * Rewind the editions and reset term index
 *
 * @since 1.0.0
 */
function econozel_rewind_editions() {

	// Get query object
	$query = econozel()->edition_query;

	// Reset current term index
	$query->current_term = -1;

	if ( $query->term_count > 0 ) {
		$query->term = $query->terms[0];
	}
}

/**
 * Return whether we're in the Edition loop
 *
 * @since 1.0.0
 *
 * @return bool Are we in the Edition loop?
 */
function econozel_in_the_edition_loop() {
	return isset( econozel()->edition_query->in_the_loop ) ? econozel()->edition_query->in_the_loop : false;
}

/** Template ******************************************************************/

/**
 * Return the Edition taxonomy term
 *
 * @since 1.0.0
 *
 * @param WP_Post|string|int $edition Optional. Article post object or Edition slug or ID. Defaults to the current Edition.
 * @param string $by Optional. Method to fetch term through `get_term_by()`. Defaults to 'id'.
 * @return WP_Term|bool Edition term object when found, else False.
 */
function econozel_get_edition( $edition = 0, $by = 'id' ) {

	// Get Edition by Article
	if ( ( empty( $edition ) && econozel_is_article() ) || ( $edition instanceof WP_Post ) ) {
		$article = econozel_get_article( $edition );
		$edition = econozel_get_article_edition( $article, true );

	// Default empty parameter to ...
	} elseif ( empty( $edition ) && ! econozel_is_article( true ) ) {

		// ... the Edition in the loop
		if ( econozel_in_the_edition_loop() ) {
			$edition = econozel()->edition_query->term;

		// ... the query var on Edition pages
		} elseif ( get_query_var( 'econozel_edition' ) ) {
			$edition = get_term( (int) get_query_var( 'econozel_edition' ), econozel_get_edition_tax_id() );
		}

	// Get the term by id or slug
	} elseif ( ! $edition instanceof WP_Term ) {
		$edition = get_term_by( $by, $edition, econozel_get_edition_tax_id() );
	}

	// Reduce error to false
	if ( ! $edition || is_wp_error( $edition ) ) {
		$edition = false;
	}

	return $edition;
}

/**
 * Return the Edition taxonomy term by issue and Volume
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_edition_by_issue'
 *
 * @param string|int $issue Edition issue.
 * @param WP_Term|int $volume Optional. Volume object or ID. Defaults to the current Volume.
 * @param bool $object Optional. Whether to return a term object. Defaults to false.
 * @return WP_Term|bool Edition term object when found, else False.
 */
function econozel_get_edition_by_issue( $issue, $volume = 0, $object = false ) {

	// Define return value
	$edition = false;

	// Require Edition Volume
	if ( $volume = econozel_get_volume( $volume ) ) {

		// Use `get_terms()` to enable query filtering
		$terms = get_terms( econozel_get_edition_tax_id(), array(
			'econozel_volume' => $volume->term_id, // Implements WP_Tax_Query
			'fields'          => $object ? 'all' : 'ids',
			'meta_query'      => array(
				array(
					'key'     => 'issue',
					'value'   => $issue,
					'compare' => '='
				)
			)
		) );

		// Assign term when found
		if ( ! empty( $terms ) ) {
			$edition = $terms[0];
		}
	}

	return apply_filters( 'econozel_get_edition_by_issue', $edition, $issue, $volume, $object );
}

/**
 * Return the Edition's Volume
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_edition_volume'
 *
 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current Edition.
 * @param bool $object Optional. Whether to return term object or ID. Defaults to ID.
 * @return WP_Term|int|bool Volume term object or ID when found, else False.
 */
function econozel_get_edition_volume( $edition = 0, $object = false ) {

	// Bail when term does not exist
	if ( ! $edition = econozel_get_edition( $edition ) )
		return false;

	// Define return value
	$volume = false;

	// Get the Edition's Volume terms
	$term_args = array( 'fields' => $object ? 'all' : 'ids' );
	$terms     = wp_get_object_terms( $edition->term_id, econozel_get_volume_tax_id(), $term_args );

	// Assign term ID when found
	if ( ! empty( $terms ) ) {
		$volume = $terms[0];
	}

	return apply_filters( 'econozel_get_edition_volume', $volume, $edition, $object );
}

/**
 * Return the Edition's issue number
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_edition_issue'
 *
 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current Edition.
 * @return int|bool Edition issue or False when empty.
 */
function econozel_get_edition_issue( $edition = 0 ) {

	// Bail when term does not exist
	if ( ! $edition = econozel_get_edition( $edition ) )
		return false;

	// Get issue from term meta
	$issue = get_term_meta( $edition->term_id, 'issue', true );

	// Sanitize value
	if ( $issue ) {
		$issue = econozel_edition_whitelist_issue( $issue );
	}

	return apply_filters( 'econozel_get_edition_issue', $issue, $edition );
}

/**
 * Check the Edition issue against a set of whitelisted issues
 *
 * @since 1.0.0
 *
 * @param mixed $issue Issue to whitelist
 * @return mixed|false Whitelisted issue or False when invalid.
 */
function econozel_edition_whitelist_issue( $issue = '' ) {

	// Get the Edition issue whitelist
	$whitelist = econozel_get_edition_issue_whitelist( false );

	// Invalidate non-whitelisted issue
	if ( empty( $issue ) || ! isset( $whitelist[ $issue ] ) ) {
		$issue = false;
	}

	return $issue;
}

/**
 * Return whether the Edition's issue is numeric
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current Edition.
 * @return bool Edition issue is numeric.
 */
function econozel_is_edition_issue_numeric( $edition = 0 ) {

	// Get Edition issue
	$issue = econozel_get_edition_issue( $edition );

	// Determine numeric-ness
	$is_numeric = $issue && is_numeric( $issue );

	return $is_numeric;
}

/**
 * Return the Edition's Articles
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_edition_articles'
 *
 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current Edition.
 * @param bool $object Optional. Whether to return post objects. Defaults to false.
 * @return array Edition Article post objects or ID's.
 */
function econozel_get_edition_articles( $edition = 0, $object = false ) {

	// Define return value
	$articles = array();

	// Get Edition term object
	if ( $edition = econozel_get_edition( $edition ) ) {

		// Use `WP_Query` to enable query filtering
		if ( $query = new WP_Query( array(
			'post_type' => econozel_get_article_post_type(),
			'fields'    => $object ? 'all' : 'ids',
			'tax_query' => array(
				array(
					'taxonomy' => econozel_get_edition_tax_id(),
					'terms'    => array( $edition->term_id ),
					'field'    => 'term_id'
				)
			)
		) ) ) {
			$articles = $query->posts;
		}
	}

	return (array) apply_filters( 'econozel_get_edition_articles', $articles, $edition, $object );
}

/**
 * Return the current Edition's adjacent Edition
 *
 * @see get_adjacent_post()
 *
 * @since 1.0.0
 *
 * @global WPDB $wpdb
 *
 * @uses apply_filters() Calls 'econozel_get_adjacent_edition'
 *
 * @param bool $previous Whether to get the previous Edition. Defaults to False.
 * @return WP_Term|bool The adjacent Edition or False when not found.
 */
function econozel_get_adjacent_edition( $previous = false ) {
	global $wpdb;

	// Define return value
	$edition = false;

	// Define local variable(s)
	$_volume = econozel_get_edition_volume( 0, true );
	$order   = $previous ? 'DESC' : 'ASC';
	$op      = $previous ? '<' : '>';

	/**
	 * Define term query clauses.
	 *
	 * Editions are ordered by issue (meta) within their respective Volumes.
	 * Only non-empty Editions with Articles are valid to be listed. The adjacent
	 * Edition is either adjacent within its own Volume, or the first of the
	 * following Volume.
	 */
	$join  = " INNER JOIN {$wpdb->term_taxonomy} tt ON ( t.term_id = tt.term_taxonomy_id )"; // Edition taxonomy
	$join .= " INNER JOIN {$wpdb->termmeta} AS tm ON ( t.term_id = tm.term_id )"; // Edition meta
	$join .= " INNER JOIN {$wpdb->term_relationships} AS volumes_tr ON ( t.term_id = volumes_tr.object_id )"; // Volume relationship
	$join .= " INNER JOIN {$wpdb->term_taxonomy} AS volumes_tt ON ( volumes_tr.term_taxonomy_id = volumes_tt.term_taxonomy_id )"; // Volume term
	$join .= " INNER JOIN {$wpdb->terms} AS volumes ON ( volumes_tt.term_taxonomy_id = volumes.term_id )"; // Volume taxonomy
	$where = $wpdb->prepare( "WHERE ( tt.taxonomy = %s ) AND ( volumes_tt.taxonomy = %s ) AND tm.meta_key = %s AND tt.count > 0 AND ( CAST( volumes.slug AS SIGNED ) $op CAST( %d AS SIGNED ) OR ( CAST( volumes.slug AS SIGNED ) = CAST( %d AS SIGNED ) AND CAST( tm.meta_value AS SIGNED ) $op CAST( %s AS SIGNED ) ) )", econozel_get_edition_tax_id(), econozel_get_volume_tax_id(), 'issue', $_volume->slug, $_volume->slug, econozel_get_edition_issue()
	);

	// Get all Volumes ID's, properly ordered
	$volumes = get_terms( econozel_get_volume_tax_id(), array( 'fields' => 'ids', 'hide_empty' => false, 'order' => $order ) );
	$volumes = ! empty( $volumes ) ? implode( ', ', $volumes ) : '0';

	$sort  = "ORDER BY FIELD( volumes_tr.term_taxonomy_id, $volumes ), CAST( tm.meta_value AS SIGNED ) $order LIMIT 1";

	// Construct query, use caching as in `get_adjacent_post()`.
	$query = "SELECT t.term_id FROM {$wpdb->terms} AS t $join $where $sort";
	$query_key = 'econozel_adjacent_term_' . md5( $query );
	$result = wp_cache_get( $query_key, 'counts' );
	if ( false !== $result ) {
		if ( $result ) {
			$edition = econozel_get_edition( $result );
		}
	} else {
		$result = $wpdb->get_var( $query );
		if ( null === $result ) {
			$result = '';
		}

		wp_cache_set( $query_key, $result, 'counts' );

		if ( $result ) {
			$edition = econozel_get_edition( $result );
		}
	}

	return apply_filters( 'econozel_get_adjacent_edition', $edition, $previous );
}

/**
 * Output the current Edition's term ID
 *
 * @since 1.0.0
 */
function econozel_the_edition_id() {
	echo econozel_get_edition_id();
}

	/**
	 * Return the current Edition's term ID
	 *
	 * @since 1.0.0
	 *
	 * @return int|bool Edition ID or False when not found.
	 */
	function econozel_get_edition_id() {
		if ( $edition = econozel_get_edition() ) {
			return $edition->term_id;
		}

		return false;
	}

/**
 * Output the Edition's title
 *
 * @since 1.0.0
 *
 * @param WP_Term|WP_Post|int $edition Optional. Defaults to the current edition.
 */
function econozel_the_edition_title( $edition = 0 ) {
	echo econozel_get_edition_title( $edition );
}

	/**
	 * Return the Edition's title
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term|WP_Post|int $edition Optional. Defaults to the current edition.
	 * @return string Edition title.
	 */
	function econozel_get_edition_title( $edition = 0 ) {

		// Define return value
		$title = '';

		// Get Edition term object
		if ( $edition = econozel_get_edition( $edition ) ) {
			$title = get_term_field( 'name', $edition );
		}

		return apply_filters( 'econozel_get_edition_title', $title, $edition );
	}

/**
 * Output the Edition's issue title
 *
 * @since 1.0.0
 *
 * @param WP_Term|WP_Post|int $edition Optional. Defaults to the current edition.
 */
function econozel_the_edition_issue_title( $edition = 0 ) {
	echo econozel_get_edition_issue_title( $edition );
}

	/**
	 * Return the Edition's issue title
	 *
	 * Only prepends Edition issue when the issue is numeric.
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'econozel_get_edition_issue_title'
	 *
	 * @param WP_Term|WP_Post|int $edition Optional. Defaults to the current edition.
	 * @return string Edition issue title
	 */
	function econozel_get_edition_issue_title( $edition = 0 ) {

		// Define return value
		$title = '';

		if ( $edition = econozel_get_edition( $edition ) ) {

			// Get Edition issue
			$issue = econozel_get_edition_issue( $edition );

			// Construct Edition title
			$title = sprintf( is_numeric( $issue ) ? '%2$s. %1$s' : '%1$s', econozel_get_edition_title( $edition ), $issue );
		}

		return apply_filters( 'econozel_get_edition_issue_title', $title, $edition );
	}

/**
 * Output the Edition's full label
 *
 * @since 1.0.0
 *
 * @param WP_Post|int $edition Optional. Defaults to the current Edition.
 */
function econozel_the_edition_label( $edition = 0 ) {
	echo econozel_get_edition_label( $edition );
}

	/**
	 * Return the Edition's full label
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'econozel_get_edition_label'
	 *
	 * @param WP_Post|int $edition Optional. Defaults to the current Edition.
	 * @return string Edition label
	 */
	function econozel_get_edition_label( $edition = 0 ) {

		// Define return value
		$label = '';

		// Get Edition term object
		if ( $edition = econozel_get_edition( $edition ) ) {

			// Get Edition Volume
			$volume = econozel_get_edition_volume( $edition, true );

			// Get Edition issue
			$issue = $volume ? econozel_get_edition_issue( $edition ) : false;

			// Define the label
			if ( is_numeric( $issue ) ) {
				$label = sprintf( esc_html__( 'Issue %d', 'econozel' ), (int) $issue );
			} else {
				$label = econozel_get_edition_title( $edition );
			}

			// Prepend Volume to the label
			if ( $volume ) {
				$label = sprintf( '%s &ndash; %s', econozel_get_volume_title( $volume ), $label );
			}
		}

		return apply_filters( 'econozel_get_edition_label', $label, $edition );
	}

/**
 * Output the Edition's permalink
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current Edition.
 */
function econozel_the_edition_link( $edition = 0 ) {
	echo econozel_get_edition_link( $edition );
}

	/**
	 * Return the Edition's permalink
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'econozel_get_edition_link'
	 *
	 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current Edition.
	 * @param bool $issue_title Optional. Whether to use the issue title. Defaults to false.
	 * @return string Edition permalink
	 */
	function econozel_get_edition_link( $edition = 0, $issue_title = false ) {

		// Define return value
		$link = '';

		if ( $edition = econozel_get_edition( $edition ) ) {
			$url  = econozel_get_edition_url( $edition );
			$link = sprintf(
				$url ? '<a href="%1$s" title="%2$s" rel="collection">%3$s</a>' : '%3$s',
				esc_url( $url ),
				esc_attr( sprintf( esc_html__( 'View articles in %s', 'econozel' ), econozel_get_edition_label( $edition ) ) ),
				$issue_title ? econozel_get_edition_issue_title( $edition ) : econozel_get_edition_title( $edition )
			);
		}

		return apply_filters( 'econozel_get_edition_link', $link, $edition, $issue_title );
	}

/**
 * Output the Edition's issue permalink
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current Edition.
 */
function econozel_the_edition_issue_link( $edition = 0 ) {
	echo econozel_get_edition_issue_link( $edition );
}

	/**
	 * Return the Edition's issue permalink
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current Edition.
	 * @return string Edition permalink
	 */
	function econozel_get_edition_issue_link( $edition = 0 ) {
		return econozel_get_edition_link( $edition, true );
	}

/**
 * Output the Edition's url
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current Edition.
 */
function econozel_the_edition_url( $edition = 0 ) {
	echo esc_url( econozel_get_edition_url( $edition ) );
}

	/**
	 * Return the Edition's url
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'econozel_get_edition_url'
	 *
	 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current Edition.
	 * @return string Edition url
	 */
	function econozel_get_edition_url( $edition = 0 ) {

		// Define return value
		$url = '';

		// Get Edition identifiers
		if ( $edition = econozel_get_edition( $edition ) ) {
			$url = get_term_link( $edition );
		}

		return apply_filters( 'econozel_get_edition_url', $url, $edition );
	}

/**
 * Return the Edition archive url
 *
 * @since 1.0.0
 *
 * @return string Edition archive url
 */
function econozel_get_edition_archive_url() {
	return home_url( user_trailingslashit( econozel_get_edition_slug() ) );
}

/**
 * Output or return the Edition's article count in a read-friendly format
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_edition_article_count'
 *
 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current Edition.
 * @param bool $echo Optional. Whether to output the return value. Defaults to true.
 * @return string Edition article count in read-friendly format.
 */
function econozel_edition_article_count( $edition = 0, $echo = true ) {

	// Get article count
	$count  = econozel_get_edition_article_count( $edition );

	// Define return value
	$retval = sprintf( esc_html( _n( '%d article', '%d articles', $count, 'econozel' ) ), $count );

	// Enable plugin filtering
	$retval = apply_filters( 'econozel_edition_article_count', $retval, $edition, $count );

	// Output or return
	if ( $echo ) {
		echo $retval;
	} else {
		return $retval;
	}
}

/**
 * Output the Edition's article count
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current Edition.
 */
function econozel_the_edition_article_count( $edition = 0 ) {
	echo econozel_get_edition_article_count( $edition );
}

	/**
	 * Return the Edition's article count
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'econozel_get_edition_article_count'
	 *
	 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current Edition.
	 * @return int Edition article count
	 */
	function econozel_get_edition_article_count( $edition = 0 ) {

		// Define return value
		$count = 0;

		// Get post count in term
		if ( $edition = econozel_get_edition( $edition ) ) {
			$count = $edition->count;
		}

		return apply_filters( 'econozel_get_edition_article_count', $count, $edition );
	}

/**
 * Output the Edition's description
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current post's Edition.
 */
function econozel_the_edition_description( $edition = 0 ) {
	echo econozel_get_edition_description( $edition );
}

	/**
	 * Return the Edition's description
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'econozel_get_edition_description'
	 *
	 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current post's Edition.
	 * @return string Edition description
	 */
	function econozel_get_edition_description( $edition = 0 ) {

		// Define return var
		$description = '';

		if ( $edition = econozel_get_edition( $edition ) ) {
			$description = get_term_field( 'description', $edition );
		}

		return apply_filters( 'econozel_get_edition_description', $description, $edition );
	}

/**
 * Output the Edition's table of contents
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current post's Edition.
 */
function econozel_the_edition_toc( $edition = 0 ) {
	echo econozel_get_edition_toc( $edition );
}

	/**
	 * Return the Edition's table of contents
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'econozel_get_edition_toc'
	 *
	 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current post's Edition.
	 * @return string Edition table of contents
	 */
	function econozel_get_edition_toc( $edition = 0 ) {

		// Define return var
		$toc = '';

		if ( $edition = econozel_get_edition( $edition ) ) {

			// Start output buffer
			ob_start();

			if ( econozel_query_articles( array(
				'econozel_edition' => $edition->term_id
			) ) ) : ?>

			<ol class="table-of-contents">

				<?php while ( econozel_has_articles() ) : econozel_the_article(); ?>

				<li value="<?php econozel_the_article_page_number(); ?>">
					<a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a>
				</li>

				<?php endwhile; ?>

			</ol>

			<?php endif;

			$toc = ob_get_clean();
		}

		return apply_filters( 'econozel_get_edition_toc', $toc, $edition );
	}

/**
 * Output the Edition's content
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current post's Edition.
 */
function econozel_the_edition_content( $edition = 0 ) {
	echo econozel_get_edition_content( $edition );
}

	/**
	 * Return the Edition's content
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'econozel_get_edition_content'
	 *
	 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current post's Edition.
	 * @return string Edition content
	 */
	function econozel_get_edition_content( $edition = 0 ) {

		// Define return var
		$content = '';

		if ( $edition = econozel_get_edition( $edition ) ) {

			// Start output buffer
			ob_start();

			if ( econozel_query_articles( array(
				'econozel_edition' => $edition->term_id
			) ) ) : ?>

			<ul class="edition-articles">

				<?php while ( econozel_has_articles() ) : econozel_the_article(); ?>

				<li <?php post_class(); ?>>
					<h4 class="article-title">
						<a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a>
					</h4>

					<span class="article-author"><?php econozel_the_article_author_link(); ?></span>

					<?php if ( get_comments_number() ) : ?>
						<span class="comment-count"><?php comments_number(); ?></span>
					<?php endif; ?>

				</li>

				<?php endwhile; ?>

			</ul>

			<?php endif;

			$content = ob_get_clean();
		}

		return apply_filters( 'econozel_get_edition_content', $content, $edition );
	}

/**
 * Output the Edition's document ID
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current Edition.
 */
function econozel_the_edition_document( $edition = 0 ) {
	echo econozel_get_edition_document( $edition );
}

	/**
	 * Return the Edition's document ID
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'econozel_get_edition_document'
	 *
	 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current Edition.
	 * @return string Edition document ID
	 */
	function econozel_get_edition_document( $edition = 0 ) {

		// Define return value
		$document = false;

		// Get Edition identifiers
		if ( $edition = econozel_get_edition( $edition ) ) {
			$meta = get_term_meta( $edition->term_id, 'document', true );

			// Check if attachment exists
			if ( $meta && $post = get_post( $meta ) ) {
				$document = (int) $post->ID;
			}
		}

		return apply_filters( 'econozel_get_edition_document', $document, $edition );
	}

/**
 * Return whether the Edition has a document
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current Edition.
 * @return bool Does the Edition have a document?
 */
function econozel_has_edition_document( $edition = 0 ) {
	return (bool) econozel_get_edition_document( $edition );
}

/**
 * Output the Edition's document url
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current Edition.
 */
function econozel_the_edition_document_url( $edition = 0 ) {
	echo esc_url( econozel_get_edition_document_url( $edition ) );
}

	/**
	 * Return the Edition's document url
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'econozel_get_edition_document_url'
	 *
	 * @param WP_Term|int $edition Optional. Edition object or ID. Defaults to the current Edition.
	 * @return string Edition document url
	 */
	function econozel_get_edition_document_url( $edition = 0 ) {

		// Define return value
		$url = '';

		// Get Edition identifiers
		if ( $edition = econozel_get_edition( $edition ) ) {
			$document = econozel_get_edition_document( $edition );

			if ( $document ) {
				$url = wp_get_attachment_url( $document );
			}
		}

		return apply_filters( 'econozel_get_edition_document_url', $url, $edition );
	}
