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

		// Looking at the volume archive
		$posts_query->econozel_is_volume_archive = true;
		$posts_query->is_archive                 = true;

		// Make sure 404 is not set
		$posts_query->is_404 = false;

		// Correct is_home variable
		$posts_query->is_home = false;

		// Setup query object
		if ( econozel_query_volumes( array(
			'paged' => $posts_query->get( 'paged' )
		) ) ) {

			// Define query result
			$posts_query->found_posts   = $eco->volume_query->found_terms;
			$posts_query->max_num_pages = $eco->volume_query->max_num_pages;
		}

	// Edition Page
	} elseif ( ! empty( $is_volume ) && ! empty( $is_edition ) ) {

		// Get Volume and Edition
		$the_volume  = econozel_get_volume( $is_volume, 'slug' );
		$the_edition = econozel_get_edition_by_issue( $is_edition, $the_volume, true );

		// 404 and bail when Volume or Edition does not exist
		if ( ! $the_volume || ! $the_edition ) {
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

		// Set main query vars
		$posts_query->set( 'post_type', econozel_get_article_post_type() );
		$posts_query->set( 'tax_query', array(
			array(
				'taxonomy'         => econozel_get_edition_tax_id(),
				'terms'            => array( $the_edition->term_id ),
				'field'            => 'term_id',
				'include_children' => false
			)
		) );

	// Volume Page
	} elseif ( ! empty( $is_volume ) ) {

		// Get Volume term
		$the_volume = econozel_get_volume( $is_volume, 'slug' );

		// 404 and bail when Volume does not exist
		if ( ! $the_volume ) {
			$posts_query->set_404();
			return;
		}

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

		// Set econozel_volume for future reference
		$posts_query->set( 'econozel_volume', $the_volume->term_id );

		// Setup query object
		if ( econozel_query_editions( array(
			'econozel_volume' => $the_volume->term_id,
			'paged'           => $posts_query->get( 'paged' )
		) ) ) {

			// Define query result
			$posts_query->found_posts   = $eco->edition_query->found_terms;
			$posts_query->max_num_pages = $eco->edition_query->max_num_pages;
		}
	}
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
function econozel_filter_wp_query( $retval, $query ) {

	// Bail when this is not the main query
	if ( ! $query->is_main_query() )
		return $retval;

	// Bail when not displaying root or taxonomy terms
	if ( ! econozel_is_root() && ! econozel_is_tax_archive() )
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
 * Check if current page is a taxononmy archive
 *
 * @since 1.0.0
 *
 * @return bool Is it a taxonomy archive?
 */
function econozel_is_tax_archive() {

	// Assume false
	$retval = false;

	// Check Volume archive or single Volume page
	if ( econozel_is_volume_archive() || econozel_is_volume() ) {
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
 * Check if current page is an Article page
 *
 * @since 1.0.0
 *
 * @return bool Is it an Article page?
 */
function econozel_is_article() {

	// Assume false
	$retval = false;

	// Check query
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
		$econozel_classes[] = 'econozel-tax-terms-list';
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
	if     ( econozel_is_root()           && ( $_template = econozel_get_root_template()            ) ) :

	// Volume Archive
	elseif ( econozel_is_volume_archive() && ( $_template = econozel_get_volume_archive_template()  ) ) :

	// Volume Page
	elseif ( econozel_is_volume()         && ( $_template = econozel_get_edition_archive_template() ) ) :
	endif;

	// Set included template file
	if ( ! empty( $_template ) ) {
		$template = econozel_set_template_included( $_template );
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
	$volume_id = econozel_get_volume_id();
	$templates = array(
		'taxonomy-' . $tax_id . '-' . $volume_id . '.php', // Single Volume ID
		'taxonomy-' . $tax_id . '.php',                    // Generic Volume Taxonomy
		'archive-econozel-edition.php',                    // Editions archive
		'archive-econozel.php',                            // Econozel archive
	);

	return econozel_get_query_template( 'econozel-editions', $templates );
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
		'generic.php',
		'page.php',
		'single.php',
		'index.php',
	);

	return econozel_get_query_template( 'archive', $templates );
}

/** Archives ******************************************************************/

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
		$title = sprintf( _x( 'Econozel %s', 'Volume archive title', 'econozel' ), econozel_get_volume_title() );

	// Edition page
	} elseif ( econozel_is_edition() ) {
		$title = sprintf( _x( 'Econozel %s', 'Edition archive title', 'econozel' ), econozel_get_edition_title() );
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

	// Volume archive
	if ( econozel_is_volume_archive() ) {
		$description = esc_html__( 'This page lists all Econozel Volumes with their respective Editions. You can browse here to find articles that have been archived or published on this site.', 'econozel' );

	// Article archive
	} elseif ( is_post_type_archive( econozel_get_article_post_type() ) ) {
		$description = esc_html__( 'This page shows all Econozel articles archived on this site. You can browse them here or through the registered Volumes and Editions in which they have been published.', 'econozel' );
	}

	return $description;
}
