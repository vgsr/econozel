<?php

/**
 * Econozel Template Functions
 * 
 * @package Econozel
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Query *********************************************************************/

/**
 * Add checks for plugin conditions to parse_query action
 *
 * @since 1.0.0
 *
 * @param WP_Query $posts_query
 */
function econozel_parse_query( $posts_query ) {

	// Bail when this is not the main loop
	if ( ! $posts_query->is_main_query() )
		return;

	// Bail when filters are suppressed on this query
	if ( true === $posts_query->get( 'suppress_filters' ) )
		return;

	// Bail when in admin
	if ( is_admin() )
		return;

	// Get Econozel
	$eco = econozel();

	// Get query variables
	$is_root           = $posts_query->get( econozel_get_root_rewrite_id()           );
	$is_volume         = $posts_query->get( econozel_get_volume_rewrite_id()         );
	$is_volume_archive = $posts_query->get( econozel_get_volume_archive_rewrite_id() );
	$is_edition        = $posts_query->get( econozel_get_edition_issue_rewrite_id()  );

	// Root Page
	if ( ! empty( $is_root ) ) {

		// Looking at the root page
		$posts_query->econozel_is_root = true;
		$posts_query->is_archive       = true;

		// Make sure 404 is not set
		$posts_query->is_404 = false;

		// Correct is_home variable
		$posts_query->is_home = false;

		// Bypass empty query result
		$posts_query->found_posts   = 0;
		$posts_query->max_num_pages = 0;

	// Volume Archive
	} elseif ( ! empty( $is_volume_archive ) ) {

		// 404 and bail when Volumes are not returned in query
		if ( ! econozel_query_volumes() ) {
			$posts_query->set_404();
			return;
		}

		// Looking at the volume archive
		$posts_query->econozel_is_volume_archive = true;
		$posts_query->is_archive                 = true;

		// Make sure 404 is not set
		$posts_query->is_404 = false;

		// Correct is_home variable
		$posts_query->is_home = false;

		// Define query result
		$posts_query->found_posts   = $eco->volume_query->found_terms;
		$posts_query->max_num_pages = $eco->volume_query->max_num_pages;

	// Single Edition
	} elseif ( ! empty( $is_volume ) && ! empty( $is_edition ) ) {

		// Get Volume and Edition
		$the_volume  = econozel_get_volume( $is_volume, 'slug' );
		$the_edition = econozel_get_edition_by_issue( $is_edition, $the_volume, true );

		// 404 and bail when Volume or Edition does not exist or the Edition has no Articles
		if ( ! $the_volume || ! $the_edition || ! econozel_get_edition_article_count( $the_edition ) ) {
			$posts_query->set_404();
			return;
		}

		// Looking at a single Edition
		$posts_query->econozel_is_edition = true;
		$posts_query->is_tax              = true;
		$posts_query->is_archive          = true;

		// Make sure 404 is not set
		$posts_query->is_404 = false;

		// Correct is_home variable
		$posts_query->is_home = false;

		// Set queried object vars
		$posts_query->queried_object    = $the_edition;
		$posts_query->queried_object_id = $the_edition->term_id;

		// Set term ID's for future reference
		$posts_query->set( 'econozel_edition', $the_edition->term_id );
		$posts_query->set( 'econozel_volume',  $the_volume->term_id  );

	// Single Volume/Edition Archive
	} elseif ( ! empty( $is_volume ) ) {

		// Get Volume term
		$the_volume = econozel_get_volume( $is_volume, 'slug' );

		// 404 and bail when Volume does not exist or Editions are not returned in query
		if ( ! $the_volume || ! econozel_query_editions( array( 'econozel_volume' => $the_volume->term_id ) ) ) {
			$posts_query->set_404();
			return;
		}

		// Set econozel_volume for future reference
		$posts_query->set( 'econozel_volume', $the_volume->term_id );

		// Looking at a single Volume
		$posts_query->econozel_is_volume = true;
		$posts_query->is_tax             = true;
		$posts_query->is_archive         = true;

		// Make sure 404 is not set
		$posts_query->is_404 = false;

		// Correct is_home variable
		$posts_query->is_home = false;

		// Set queried object vars
		$posts_query->queried_object    = $the_volume;
		$posts_query->queried_object_id = $the_volume->term_id;

		// Define query result
		$posts_query->found_posts   = $eco->edition_query->found_terms;
		$posts_query->max_num_pages = $eco->edition_query->max_num_pages;
	}
}

/**
 * Handle custom query vars at parse_query action
 *
 * @since 1.0.0
 *
 * @param WP_Query $posts_query
 */
function econozel_parse_query_vars( $posts_query ) {

	// Bail when filters are suppressed on this query
	if ( true === $posts_query->get( 'suppress_filters' ) )
		return;

	// Query by Edition
	if ( $edition = $posts_query->get( 'econozel_edition' ) ) {

		// Post type
		$posts_query->set( 'post_type', econozel_get_article_post_type() );

		// Edition taxonomy
		$tax_query   = $posts_query->get( 'tax_query', array() );
		$tax_query[] = array(
			'taxonomy'         => econozel_get_edition_tax_id(),
			'terms'            => array( (int) $edition ),
			'field'            => 'term_id',
			'include_children' => false
		);
		$posts_query->set( 'tax_query', $tax_query );

		// Default to ordering by page number in menu_order
		if ( ! $posts_query->get( 'orderby' ) ) {
			$posts_query->set( 'orderby', 'menu_order' );
			$posts_query->set( 'order',   'ASC'        );
		}
	}
}

/**
 * Add checks for plugin conditions to posts_clauses action
 *
 * @since 1.0.0
 *
 * @global WPDB $wpdb
 *
 * @param array $clauses SQL clauses
 * @param WP_Query $query Post query object
 * @return array SQL clauses
 */
function econozel_posts_clauses( $clauses, $query ) {
	global $wpdb;

	// Bail when filters are suppressed on this query
	if ( true === $query->get( 'suppress_filters' ) )
		return $clauses;

	// Bail when in admin
	if ( is_admin() )
		return $clauses;

	// Filter by comment activity
	if ( $comment_activity = $query->get( 'comment_activity', false ) ) {

		// Define local variable(s)
		$days    = is_numeric( $comment_activity ) ? (int) $comment_activity : 10;
		$since   = date( 'Y-m-d 00:00:00', strtotime( "{$days} days ago" ) );

		/**
		 * Query posts that have commenst in the last X days, order by comment count
		 *
		 * To be able to order by comment count for the given period (not all-time),
		 * that count needs to be included as a column in the 'fields' clause. This
		 * requires a JOIN with the comments table, filtered for the last X days.
		 */
		$clauses['fields'] .= ', c.comment_count';
		$clauses['join']   .= $wpdb->prepare( " INNER JOIN ( SELECT comment_post_ID, COUNT( * ) AS comment_count FROM {$wpdb->comments} WHERE comment_approved = %s AND comment_date > %s GROUP BY comment_post_ID ) AS c ON c.comment_post_ID = {$wpdb->posts}.ID", 1, $since );
		$clauses['where']  .= " AND c.comment_count > 0";

		$orderby = 'c.comment_count DESC';
		if ( ! empty( $clauses['orderby'] ) )
			$orderby .= ', ';

		$clauses['orderby'] = $orderby . $clauses['orderby'];
	}

	return $clauses;
}

/**
 * Overwrite the main WordPress query
 *
 * @since 1.0.0
 *
 * @param string $request SQL query
 * @param WP_Query $query Query object
 * @return string SQL query
 */
function econozel_filter_wp_query( $request, $query ) {
	global $wpdb;

	// Bail when this is not the main query
	if ( ! $query->is_main_query() )
		return $request;

	// Bail when not displaying root or term archives
	if ( ! econozel_is_root() && ! econozel_have_archive() )
		return $request;

	// Query for nothing and your chicks for free
	$request = "SELECT 1 FROM {$wpdb->posts} WHERE 0=1";

	return $request;
}

/**
 * Stop WordPress performing a DB query for its main loop
 *
 * @since 1.0.0
 *
 * @param null $retval Current return value
 * @param WP_Query $query Query object
 * @return null|array
 */
function econozel_bypass_wp_query( $retval, $query ) {

	// Bail when this is not the main query
	if ( ! $query->is_main_query() )
		return $retval;

	// Bail when not displaying root or term archives
	if ( ! econozel_is_root() && ! econozel_have_archive() )
		return $retval;

	// Return something other than a null value to bypass WP_Query
	return array();
}

/**
 * Check if current page is the root page
 *
 * @since 1.0.0
 *
 * @global WP_Query $wp_query To check if WP_Query::econozel_is_root is true
 * @return bool Is it the root page?
 */
function econozel_is_root() {
	global $wp_query;

	// Assume false
	$retval = false;

	// Check query
	if ( ! empty( $wp_query->econozel_is_root ) && ( true === $wp_query->econozel_is_root ) ) {
		$retval = true;
	}

	return (bool) $retval;
}

/**
 * Check if current page is the Volume archive
 *
 * @since 1.0.0
 *
 * @global WP_Query $wp_query To check if WP_Query::econozel_is_volume_archive is true
 * @return bool Is it the Volume archive?
 */
function econozel_is_volume_archive() {
	global $wp_query;

	// Assume false
	$retval = false;

	// Check query
	if ( ! empty( $wp_query->econozel_is_volume_archive ) && ( true === $wp_query->econozel_is_volume_archive ) ) {
		$retval = true;
	}

	return (bool) $retval;
}

/**
 * Check if current page is a Volume page
 *
 * @since 1.0.0
 *
 * @global WP_Query $wp_query To check if WP_Query::econozel_is_volume is true
 * @return bool Is it a Volume page?
 */
function econozel_is_volume() {
	global $wp_query;

	// Assume false
	$retval = false;

	// Check query
	if ( ! empty( $wp_query->econozel_is_volume ) && ( true === $wp_query->econozel_is_volume ) ) {
		$retval = true;
	}

	return (bool) $retval;
}

/**
 * Check if current page is an Edition archive
 *
 * @since 1.0.0
 *
 * @return bool Is it an Edition archive?
 */
function econozel_is_edition_archive() {

	// Assume false
	$retval = false;

	// Volume page
	if ( econozel_is_volume() ) {
		$retval = true;
	}

	return (bool) $retval;
}

/**
 * Check if current page is an Edition page
 *
 * @since 1.0.0
 *
 * @global WP_Query $wp_query To check if WP_Query::econozel_is_edition is true
 * @return bool Is it an Edition page?
 */
function econozel_is_edition() {
	global $wp_query;

	// Assume false
	$retval = false;

	// Check query
	if ( ! empty( $wp_query->econozel_is_edition ) && ( true === $wp_query->econozel_is_edition ) ) {
		$retval = true;
	}

	return (bool) $retval;
}

/**
 * Check if current page is a taxononmy archive
 *
 * @since 1.0.0
 *
 * @return bool Is it a taxonomy archive?
 */
function econozel_is_tax_archive() {

	// Assume false
	$retval = false;

	// Check Volume archive or Edition archive
	if ( econozel_is_volume_archive() || econozel_is_edition_archive() ) {
		$retval = true;
	}

	return (bool) $retval;
}

/**
 * Check if current page is the Article archive
 *
 * @since 1.0.0
 *
 * @return bool Is it the Article archive?
 */
function econozel_is_article_archive() {

	// Assume false
	$retval = false;

	// Article post type archive
	if ( is_post_type_archive( econozel_get_article_post_type() ) ) {
		$retval = true;
	}

	return (bool) $retval;
}

/**
 * Check if current page is an Article page
 *
 * @since 1.0.0
 *
 * @return bool Is it an Article page?
 */
function econozel_is_article() {

	// Assume false
	$retval = false;

	// Single article
	if ( is_singular( econozel_get_article_post_type() ) ) {
		$retval = true;
	}

	return (bool) $retval;
}

/**
 * Modify the page's body class
 *
 * @since 1.0.0
 *
 * @param array $wp_classes Body classes
 * @param array $custom_classes Additional classes
 * @return array Body classes
 */
function econozel_body_class( $wp_classes, $custom_classes = false ) {

	// Define local var
	$econozel_classes = array();

	/** Root ******************************************************************/

	if ( econozel_is_root() ) {
		$econozel_classes[] = 'econozel-root';

	/** Pages *****************************************************************/

	} elseif ( econozel_is_volume_archive() ) {
		$econozel_classes[] = 'econozel-volume-archive';

	} elseif ( econozel_is_volume() ) {
		$econozel_classes[] = 'econozel-volume';

	} elseif ( econozel_is_edition() ) {
		$econozel_classes[] = 'econozel-edition';

	} elseif ( econozel_is_article() ) {
		$econozel_classes[] = 'econozel-article';
	}

	/** Type ******************************************************************/

	if ( econozel_is_tax_archive() ) {
		$econozel_classes[] = 'econozel-terms-list';
	}

	/** Clean up **************************************************************/

	// Add Econozel class when on an Econozel page
	if ( ! empty( $econozel_classes ) ) {
		$econozel_classes[] = 'econozel';
	}

	// Merge WP classes with plugin classes and remove duplicates
	$classes = array_unique( array_merge( (array) $wp_classes, $econozel_classes ) );

	return $classes;
}

/**
 * Use the is_() functions to return if on any Econozel page
 *
 * @since 1.0.0
 *
 * @return bool On an Econozel page
 */
function is_econozel() {

	// Default to false
	$retval = false;

	/** Root ******************************************************************/

	if ( econozel_is_root() ) {
		$retval = true;

	/** Pages *****************************************************************/

	} elseif ( econozel_is_volume_archive() ) {
		$retval = true;

	} elseif ( econozel_is_volume() ) {
		$retval = true;

	} elseif ( econozel_is_edition() ) {
		$retval = true;

	} elseif ( econozel_is_article_archive() ) {
		$retval = true;

	} elseif ( econozel_is_article() ) {
		$retval = true;
	}

	return $retval;
}

/** Theme *********************************************************************/

/**
 * Filter the theme's template for supporting themes
 *
 * @since 1.0.0
 *
 * @param string $template Path to template file
 * @return string Path to template file
 */
function econozel_template_include_theme_supports( $template = '' ) {

	// Define local var
	$_template = '';

	// Root Page
	if     ( econozel_is_root()            && ( $_template = econozel_get_root_template()            ) ) :

	// Volume Archive
	elseif ( econozel_is_volume_archive()  && ( $_template = econozel_get_volume_archive_template()  ) ) :

	// Edition Archive
	elseif ( econozel_is_edition_archive() && ( $_template = econozel_get_edition_archive_template() ) ) :

	// Single Edition
	elseif ( econozel_is_edition()         && ( $_template = econozel_get_edition_template()         ) ) :
	endif;

	// Set included template file
	if ( ! empty( $_template ) ) {
		$template = econozel_set_template_included( $_template );

		// Reset post, but theme compat is not active
		econozel_theme_compat_reset_post();
		econozel_set_theme_compat_active( false );
	}

	return $template;
}

/**
 * Set the included template
 *
 * @since 1.0.0
 *
 * @param string|bool $template Path to template file. Defaults to false.
 * @return string|bool Path to template file. False if empty.
 */
function econozel_set_template_included( $template = false ) {
	econozel()->theme_compat->econozel_template = $template;

	return econozel()->theme_compat->econozel_template;
}

/**
 * Return whether a template is included
 *
 * @since 1.0.0
 *
 * @return bool Template is included.
 */
function econozel_is_template_included() {
	return ! empty( econozel()->theme_compat->econozel_template );
}

/**
 * Retreive path to a template
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_{$type}_template'
 *
 * @param string $type Filename without extension.
 * @param array $templates Optional. Template candidates.
 * @return string Path to template file
 */
function econozel_get_query_template( $type, $templates = array() ) {
	$type = preg_replace( '|[^a-z0-9-]+|', '', $type );

	// Fallback file
	if ( empty( $templates ) ) {
		$templates = array( "{$type}.php" );
	}

	// Locate template file
	$template = econozel_locate_template( $templates );

	return apply_filters( "econozel_{$type}_template", $template );
}

/**
 * Locate and return the Econozel root page template
 *
 * @since 1.0.0
 *
 * @return string Path to template file
 */
function econozel_get_root_template() {
	$templates = array(
		'econozel-root.php', // Econozel root
		'econozel.php',      // Econozel
	);

	return econozel_get_query_template( 'econozel', $templates );
}

/**
 * Locate and return the Volume archive page template
 *
 * @since 1.0.0
 *
 * @return string Path to template file
 */
function econozel_get_volume_archive_template() {
	$templates = array(
		'archive-econozel-volume.php', // Volumes archive
		'archive-econozel.php',        // Econozel archive
	);

	return econozel_get_query_template( 'econozel-volumes', $templates );
}

/**
 * Locate and return the Edition archive page template
 *
 * @since 1.0.0
 *
 * @return string Path to template file
 */
function econozel_get_edition_archive_template() {
	$tax_id    = econozel_get_volume_tax_id();
	$term_id   = econozel_get_volume_id();
	$templates = array(
		'taxonomy-' . $tax_id . '-' . $term_id . '.php', // Single Volume ID
		'taxonomy-' . $tax_id . '.php',                  // Generic Volume Taxonomy
		'econozel-volume.php',                           // Single Volume
		'archive-econozel-edition.php',                  // Editions archive
		'archive-econozel.php',                          // Econozel archive
	);

	return econozel_get_query_template( 'econozel-editions', $templates );
}

/**
 * Locate and return the single Edition page template
 *
 * @since 1.0.0
 *
 * @return string Path to template file
 */
function econozel_get_edition_template() {
	$tax_id     = econozel_get_edition_tax_id();
	$term_id    = econozel_get_edition_id();
	$templates  = array(
		'taxonomy-' . $tax_id . '-' . $term_id . '.php', // Single Edition ID
		'taxonomy-' . $tax_id . '.php',                  // Generic Edition Taxonomy
		'single-econozel-edition.php',                   // Single Edition
		'econozel-edition.php',                          // Single Edition
	);

	return econozel_get_query_template( 'econozel-edition', $templates );
}

/**
 * Locate and return the generic plugin page template
 *
 * @since 1.0.0
 *
 * @return string Path to template file
 */
function econozel_get_theme_compat_template() {
	$templates = array(
		'econozel-compat.php',
		'econozel.php'
	);

	// Use archive.php for Taxonomy archives
	if ( econozel_is_volume_archive() ) {
		$templates[] = 'archive.php';
	}

	// Append generic templates
	$templates = array_merge( $templates, array(
		'generic.php',
		'page.php',
		'single.php',
		'index.php'
	) );

	return econozel_get_query_template( 'econozel-compat', $templates );
}

/** Archives ******************************************************************/

/**
 * Return whether the current archive has query results
 *
 * @since 1.0.0
 *
 * @return bool Whether the current archive page has query results
 */
function econozel_have_archive() {

	// Define return value
	$retval = false;

	// Volume Archive
	if ( econozel_is_volume_archive() && econozel_has_volumes() ) {
		$retval = true;

	// Edition Archive
	} elseif ( econozel_is_edition_archive() && econozel_has_editions() ) {
		$retval = true;
	}

	return $retval;
}

/**
 * Return the currently queried page number
 *
 * @since 1.0.0
 *
 * @return int Queried page number
 */
function econozel_get_paged() {
	global $wp_query;

	// Check the query var
	if ( get_query_var( 'paged' ) ) {
		$paged = get_query_var( 'paged' );

	// Check query paged
	} elseif ( ! empty( $wp_query->query['paged'] ) ) {
		$paged = $wp_query->query['paged'];
	}

	// Paged found
	if ( ! empty( $paged ) )
		return (int) $paged;

	// Default to first page
	return 1;
}

/**
 * Modify the document title parts for plugin pages
 *
 * @since 1.0.0
 *
 * @param array $title Title parts
 * @return array Title parts
 */
function econozel_document_title_parts( $title = array() ) {

	// Define local var
	$_title = '';

	// Econozel page, not the root page
	if ( is_econozel() && ! econozel_is_root() ) {

		// Define parent title part
		$parent = array( 'parent' => esc_html__( 'Econozel', 'econozel' ) );

		// Insert 'Econozel' part after title part, creates 'Title - Econozel - Site'
		$title = array_slice( $title, 0, 1, true ) + $parent + array_slice( $title, 1, count( $title ) - 1, true );
	}

	// Root page
	if ( econozel_is_root() ) {
		$_title = esc_html__( 'Econozel', 'econozel' );

	// Volume Archive
	} elseif ( econozel_is_volume_archive() ) {
		$_title = esc_html__( 'Volume Archives', 'econozel' );

	// Volume page
	} elseif ( econozel_is_volume() ) {
		$_title = econozel_get_volume_title();

	// Edition page
	} elseif ( econozel_is_edition() ) {
		$_title = econozel_get_edition_title();
	}

	// Overwrite document title
	if ( ! empty( $_title ) ) {
		$title['title'] = $_title;
	}

	return $title;
}

/**
 * Return the Econozel archive title
 *
 * @since 1.0.0
 *
 * @param string $title Archive title
 * @return string Archive title
 */
function econozel_get_the_archive_title( $title = '' ) {

	// Root page
	if ( econozel_is_root() ) {
		$title = esc_html__( 'Econozel', 'econozel' );

	// Volume Archive
	} elseif ( econozel_is_volume_archive() ) {
		$title = esc_html__( 'Econozel Volumes', 'econozel' );

	// Volume page
	} elseif ( econozel_is_volume() ) {
		$title = sprintf( _x( 'Econozel %s', 'Single volume title', 'econozel' ), econozel_get_volume_title() );

	// Edition page
	} elseif ( econozel_is_edition() ) {
		$title = sprintf( _x( 'Econozel %s', 'Single edition title', 'econozel' ), econozel_get_edition_title() );
	}

	return $title;
}

/**
 * Return the Econozel archive description
 *
 * @since 1.0.0
 *
 * @param string $description Archive description
 * @return string Archive description
 */
function econozel_get_the_archive_description( $description = '' ) {

	// Root page
	if ( econozel_is_root() ) {
		$description = sprintf( __( 'This page lists recent Econozel activity on this site. You can browse the <a href="%1$s">Volume archives</a> or <a href="%2$s">Article archives</a> to find all articles that have been archived or published on this site.', 'econozel' ), esc_url( econozel_get_volume_archive_url() ), esc_url( get_post_type_archive_link( econozel_get_article_post_type() ) ) );

	// Volume archive
	} elseif ( econozel_is_volume_archive() ) {
		$description = esc_html__( 'This page lists all Econozel Volumes with their respective Editions. You can browse here to find articles that have been archived or published on this site.', 'econozel' );

	// Article archive
	} elseif ( econozel_is_article_archive() ) {
		$description = esc_html__( 'This page lists all Econozel articles archived on this site. You can browse them here or through the registered Volumes and Editions in which they have been published.', 'econozel' );
	}

	return $description;
}

/**
 * Modify the term classes
 *
 * @since 1.0.0
 *
 * @param array $classes Term classes
 * @return array Term classes
 */
function econozel_filter_term_class( $classes ) {

	// When in Theme Compat mode
	if ( econozel_is_theme_compat_active() ) {

		// Remove 'hentry' term class, because when doing theme-compat
		// it messes with the basic logic of theme styling
		if ( false !== ( $key = array_search( 'hentry', $classes ) ) ) {
			unset( $classes[ $key ] );
		}
	}

	return $classes;
}

/** Template Tags *************************************************************/

/**
 * Output the classes for the term div.
 *
 * @see post_class()
 *
 * @since 1.0.0
 *
 * @param string $class Optional. One or more classes to add to the class list.
 * @param int|WP_Term $term_id Optional. Term ID or object. Defaults to the current object.
 */
function econozel_term_class( $class = '', $term_id = null ) {
	echo 'class="' . join( ' ', econozel_get_term_class( $class, $term_id ) ) . '"';
}

/**
 * Return the classes for the term div.
 *
 * @since 1.0.0
 *
 * @param string $class Optional. One or more classes to add to the class list.
 * @param int|WP_Term $term_id Optional. Term ID or object. Defaults to the current object.
 * @return array Classes
 */
function econozel_get_term_class( $class = '', $term_id = null ) {

	// Get term object
	if ( empty( $term_id ) ) {

		// Looping Editions
		if ( econozel_in_the_edition_loop() ) {
			$term = econozel_get_edition();

		// Looping Volumes
		} elseif ( econozel_in_the_volume_loop() ) {
			$term = econozel_get_volume();

		// No idea
		} else {
			$term = false;
		}
	} else {
		$term = get_term( $term_id );
	}

	// Define return var
	$classes = array();

	if ( $class ) {
		if ( ! is_array( $class ) ) {
			$class = preg_split( '#\s+#', $class );
		}
		$classes = array_map( 'esc_attr', $class );
	} else {
		// Ensure that we always coerce class to being an array.
		$class = array();
	}

	if ( ! $term ) {
		return $classes;
	}

	$classes[] = 'term-' . $term->term_id;
	if ( ! is_admin() )
		$classes[] = $term->taxonomy;
	$classes[] = 'tax-' . $term->taxonomy;

	// hentry for hAtom compliance
	$classes[] = 'hentry';

	// All public taxonomies
	$taxonomies = get_taxonomies( array( 'public' => true ) );
	foreach ( (array) $taxonomies as $taxonomy ) {
		if ( is_object_in_taxonomy( $term->taxonomy, $taxonomy ) ) {
			foreach ( (array) get_the_terms( $term->term_id, $taxonomy ) as $_term ) {
				if ( empty( $_term->slug ) ) {
					continue;
				}

				$_term_class = sanitize_html_class( $_term->slug, $_term->term_id );
				if ( is_numeric( $_term_class ) || ! trim( $_term_class, '-' ) ) {
					$_term_class = $_term->term_id;
				}

				$classes[] = sanitize_html_class( $taxonomy . '-' . $_term_class, $taxonomy . '-' . $term->term_id );
			}
		}
	}

	$classes = array_map( 'esc_attr', $classes );

	/**
	 * Filter the list of CSS classes for the current term.
	 *
	 * @since 1.0.0
	 *
	 * @param array $classes An array of term classes.
	 * @param array $class   An array of additional classes added to the term.
	 * @param int   $term_id The term ID.
	 */
	$classes = apply_filters( 'econozel_term_class', $classes, $class, $term->term_id );

	return array_unique( $classes );
}

/**
 * Output navigation markup to next/previous plugin pages
 *
 * @see the_posts_navigation()
 *
 * @since 1.0.0
 *
 * @param array $args Arguments for {@see get_the_posts_navigation()}
 */
function econozel_the_posts_navigation( $args = array() ) {
	echo econozel_get_the_posts_navigation( $args );
}

	/**
	 * Return navigation markup to next/previous plugin pages
	 *
	 * @see get_the_posts_navigation()
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Arguments for {@see get_the_posts_navigation()}
	 * @return string Navigation markup
	 */
	function econozel_get_the_posts_navigation( $args = array() ) {

		// Volume Archive
		if ( econozel_is_volume_archive() ) {
			$args = array(
				'prev_text'          => esc_html__( 'Older volumes',      'econozel' ),
				'next_text'          => esc_html__( 'Newer volumes',      'econozel' ),
				'screen_reader_text' => esc_html__( 'Volumes navigation', 'econozel' )
			);

		// Edition Archive
		} elseif ( econozel_is_edition_archive() ) {
			$args = array(
				'prev_text'          => esc_html__( 'Older editions',      'econozel' ),
				'next_text'          => esc_html__( 'Newer editions',      'econozel' ),
				'screen_reader_text' => esc_html__( 'Editions navigation', 'econozel' )
			);

		// Article Archive
		} elseif ( econozel_is_article_archive() ) {
			$args = array(
				'prev_text'          => esc_html__( 'Older articles',      'econozel' ),
				'next_text'          => esc_html__( 'Newer articles',      'econozel' ),
				'screen_reader_text' => esc_html__( 'Articles navigation', 'econozel' )
			);
		}

		return get_the_posts_navigation( $args );
	}
