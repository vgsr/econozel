<?php

/**
 * Econozel Editions Functions
 * 
 * @package Econozel
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

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
	$query_args = wp_parse_args( $args, array(
		'econozel_volume' => econozel_get_volume_id(),
		'taxonomy'        => econozel_get_edition_tax_id(),
		'number'          => econozel_get_editions_per_page(),
		'paged'           => econozel_get_paged(),
		'fields'          => 'all',
		'hide_empty'      => true
	) );

	// Bail when Volume does not exist
	if ( empty( $query_args['econozel_volume'] ) || ! ( $volume = econozel_get_volume( $query_args['econozel_volume'] ) ) )
		return false;

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
 *
 * @return bool Are we still in the loop?
 */
function econozel_the_edition() {

	// Get query object
	$query = econozel()->edition_query;

	// We're looping
	$query->in_the_loop = true;

	// Increase current term index
	$query->current_term++;

	// Get next term in list
	$query->term = $query->terms[ $query->current_term ];

	return $query->term;
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
 * @param WP_Post|string|int $edition Optional. Defaults to the current Edition.
 * @param string $by Optional. Method to fetch term through `get_term_by()`. Defaults to 'id'.
 * @return WP_Term|bool Edition term object when found, else False.
 */
function econozel_get_edition( $edition = 0, $by = 'id' ) {

	// Default empty parameter to ...
	if ( empty( $edition ) && ! econozel_is_article() ) {

		// ... the Edition in the loop
		if ( econozel_in_the_edition_loop() ) {
			$edition = econozel()->edition_query->term;

		// ... the query var on Edition pages
		} elseif ( get_query_var( 'econozel_edition' ) ) {
			$edition = get_term( (int) get_query_var( 'econozel_edition' ), econozel_get_edition_tax_id() );
		}

	// Get Edition by Article
	} elseif ( ( empty( $edition ) && econozel_is_article() ) || ( $edition instanceof WP_Post ) ) {
		$article = econozel_get_article( $edition );
		$edition = econozel_get_article_edition( $article, true );

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
 * @param int $issue Edition issue.
 * @param WP_Term|int $volume Optional. Defaults to the current Volume.
 * @param bool $object Optional. Whether to return a term object. Defaults to false.
 * @return WP_Term|bool Edition term object when found, else False.
 */
function econozel_get_edition_by_issue( $issue, $volume = 0, $object = false ) {

	// Bail when Volume does not exist
	if ( ! $volume = econozel_get_volume( $volume ) )
		return false;

	// Define return var
	$edition = false;

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

	return $edition;
}

/**
 * Return the Edition's Volume
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $edition Optional. Defaults to the current Edition.
 * @param bool $object Optional. Whether to return term object or ID. Defaults to ID.
 * @return WP_Term|int|bool Volume term object or ID when found, else False.
 */
function econozel_get_edition_volume( $edition = 0, $object = false ) {

	// Bail when term does not exist
	if ( ! $edition = econozel_get_edition( $edition ) )
		return false;

	// Define return var
	$volume = false;

	// Get Volume from query var
	if ( econozel_is_edition() ) {
		$volume = get_query_var( 'econozel_volume' );

		// Get the Volume object
		if ( $volume && $object ) {
			$volume = econozel_get_volume( $volume );
		}

	// Get the Edition's Volume terms
	} else {
		$term_args = array( 'fields' => $object ? 'all' : 'ids' );
		$terms     = wp_get_object_terms( $edition->term_id, econozel_get_volume_tax_id(), $term_args );

		// Assign term ID when found
		if ( ! empty( $terms ) ) {
			$volume = $terms[0];
		}
	}


	return $volume;
}

/**
 * Return the Edition's issue number
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $edition Optional. Defaults to the current Edition.
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

	return $issue;	
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
	$whitelist = econozel_get_edition_issue_whitelist();

	// Invalidate non-whitelisted issue
	if ( empty( $issue ) || ! in_array( $issue, $whitelist ) ) {
		$issue = false;
	}

	return $issue;
}

/**
 * Return whether the Edition's issue is numeric
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $edition Optional. Defaults to the current Edition.
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
 * @param WP_Term|int $edition Optional. Defaults to the current Edition.
 * @param bool $object Optional. Whether to return post objects. Defaults to false.
 * @return array Edition Article post objects or ID's.
 */
function econozel_get_edition_articles( $edition = 0, $object = false ) {

	// Define return var
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

	return $articles;
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

		// Define return var
		$title = '';

		// Get Edition term object
		if ( $edition = econozel_get_edition( $edition ) ) {
			$title = $edition->name;
		}

		return $title;
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
	 * @param WP_Term|WP_Post|int $edition Optional. Defaults to the current edition.
	 * @return string Edition issue title
	 */
	function econozel_get_edition_issue_title( $edition = 0 ) {

		// Define return var
		$title = '';

		if ( $edition = econozel_get_edition( $edition ) ) {

			// Get Edition issue
			$issue = econozel_get_edition_issue( $edition );

			// Construct Edition title
			$title = sprintf( is_numeric( $issue ) ? '%2$s. %1$s' : '%1$s', econozel_get_edition_title( $edition ), $issue );
		}

		return $title;
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
	 * @param WP_Post|int $edition Optional. Defaults to the current Edition.
	 * @return string Edition label
	 */
	function econozel_get_edition_label( $edition = 0 ) {

		// Define return var
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

		return $label;
	}

/**
 * Output the Edition's permalink
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $edition Optional. Defaults to the current Edition.
 */
function econozel_the_edition_link( $edition = 0 ) {
	echo econozel_get_edition_link( $edition );
}

	/**
	 * Return the Edition's permalink
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term|int $edition Optional. Defaults to the current Edition.
	 * @param bool $issue_title Optional. Whether to use the issue title. Defaults to false.
	 * @return string Edition permalink
	 */
	function econozel_get_edition_link( $edition = 0, $issue_title = false ) {

		// Define return var
		$link = '';

		if ( $edition = econozel_get_edition( $edition ) ) {

			// Construct link
			$link = sprintf(
				'<a href="%1$s" title="%2$s" rel="collection">%3$s</a>',
				esc_url( econozel_get_edition_url( $edition ) ),
				esc_attr( sprintf( esc_html__( 'View articles in %s', 'econozel' ), econozel_get_edition_label( $edition ) ) ),
				$issue_title ? econozel_get_edition_issue_title( $edition ) : econozel_get_edition_title( $edition )
			);
		}

		return $link;
	}

/**
 * Output the Edition's issue permalink
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $edition Optional. Defaults to the current Edition.
 */
function econozel_the_edition_issue_link( $edition = 0 ) {
	echo econozel_get_edition_issue_link( $edition );
}

	/**
	 * Return the Edition's issue permalink
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term|int $edition Optional. Defaults to the current Edition.
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
 * @param WP_Term|int $edition Optional. Defaults to the current Edition.
 */
function econozel_the_edition_url( $edition = 0 ) {
	echo esc_url( econozel_get_edition_url( $edition ) );
}

	/**
	 * Return the Edition's url
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term|int $edition Optional. Defaults to the current Edition.
	 * @return string Edition url
	 */
	function econozel_get_edition_url( $edition = 0 ) {

		// Define return var
		$url = '';

		// Get Edition identifiers
		if ( $edition = econozel_get_edition( $edition ) ) {
			$url = get_term_link( $edition );
		}

		return $url;
	}

/**
 * Output or return the Edition's article count in a read-friendly format
 *
 * @since 1.0.0
 *
 * @param WP_Term|int $edition Optional. Defaults to the current Edition.
 * @param bool $echo Optional. Whether to output the return value. Defaults to true.
 * @return string Edition article count in read-friendly format.
 */
function econozel_edition_article_count( $edition = 0, $echo = true ) {

	// Get article count
	$count  = econozel_get_edition_article_count( $edition );

	// Define return var
	$retval = sprintf( esc_html( _n( '%d article', '%d articles', $count, 'econozel' ) ), $count ) ;

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
 * @param WP_Term|int $edition Optional. Defaults to the current Edition.
 */
function econozel_the_edition_article_count( $edition = 0 ) {
	echo econozel_get_edition_article_count( $edition );
}

	/**
	 * Return the Edition's article count
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term|int $edition Optional. Defaults to the current Edition.
	 * @return int Edition article count
	 */
	function econozel_get_edition_article_count( $edition = 0 ) {

		// Define return var
		$count = 0;

		// Get post count in term
		if ( $edition = econozel_get_edition( $edition ) ) {
			$count = $edition->count;
		}

		return $count;
	}
