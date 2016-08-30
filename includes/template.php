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

/** Theme Compat **************************************************************/

/**
 * Return the path to the plugin's theme compat directory
 *
 * @since 1.0.0
 *
 * @return string Path to theme compat directory
 */
function econozel_get_theme_compat_dir() {
	return trailingslashit( econozel()->themes_dir . 'default' );
}

/**
 * Filter the theme's template for theme compatability
 *
 * @since 1.0.0
 *
 * @param string $template Path to template file
 * @return string Path to template file
 */
function econozel_template_include_theme_compat( $template = '' ) {

	// Bail when template is already included
	if ( econozel_is_template_included() )
		return $template;

	// Root Page
	if ( econozel_is_root() ) {

		// Reset post
		econozel_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => econozel_buffer_template_part( 'content', 'root', false ),
			'post_type'      => '',
			'post_title'     => esc_html__( 'Econozel', 'econozel' ),
			'is_single'      => true,
		) );

	// Volume Archive
	} elseif ( econozel_is_volume_archive() ) {

		// Reset post
		econozel_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => econozel_buffer_template_part( 'content', 'archive-volume', false ),
			'post_type'      => '',
			'post_title'     => esc_html__( 'Econozel Volumes', 'econozel' ),
			'is_archive'     => true,
		) );

	// Volume Page
	} elseif ( econozel_is_volume() ) {

		// Reset post
		econozel_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => econozel_buffer_template_part( 'content', 'archive-edition', false ),
			'post_type'      => '',
			'post_title'     => econozel_get_volume_title( get_queried_object_id() ),
			'is_archive'     => true,
			'is_tax'         => true,
		) );
	}

	// So we're using theme compatibility?
	if ( econozel_is_theme_compat_active() ) {

		// Remove filters on 'the_content'
		// econozel_remove_all_filters( 'the_content' );

		// Use a theme compat template
		$template = econozel_get_theme_compat_template();
	}

	return $template;
}

/**
 * Reset WordPress globals with dummy data to prevent templates
 * reporting missing data.
 *
 * @see bbPress's bbp_theme_compat_reset_post()
 *
 * @since 1.0.0
 *
 * @global WP_Query $wp_query
 * @global WP_Post $post
 * @param array $args Reset post arguments
 */
function econozel_theme_compat_reset_post( $args = array() ) {
	global $wp_query, $post;

	// Switch defaults if post is set
	if ( isset( $wp_query->post ) ) {
		$dummy = wp_parse_args( $args, array(
			'ID'                    => $wp_query->post->ID,
			'post_status'           => $wp_query->post->post_status,
			'post_author'           => $wp_query->post->post_author,
			'post_parent'           => $wp_query->post->post_parent,
			'post_type'             => $wp_query->post->post_type,
			'post_date'             => $wp_query->post->post_date,
			'post_date_gmt'         => $wp_query->post->post_date_gmt,
			'post_modified'         => $wp_query->post->post_modified,
			'post_modified_gmt'     => $wp_query->post->post_modified_gmt,
			'post_content'          => $wp_query->post->post_content,
			'post_title'            => $wp_query->post->post_title,
			'post_excerpt'          => $wp_query->post->post_excerpt,
			'post_content_filtered' => $wp_query->post->post_content_filtered,
			'post_mime_type'        => $wp_query->post->post_mime_type,
			'post_password'         => $wp_query->post->post_password,
			'post_name'             => $wp_query->post->post_name,
			'guid'                  => $wp_query->post->guid,
			'menu_order'            => $wp_query->post->menu_order,
			'pinged'                => $wp_query->post->pinged,
			'to_ping'               => $wp_query->post->to_ping,
			'ping_status'           => $wp_query->post->ping_status,
			'comment_status'        => $wp_query->post->comment_status,
			'comment_count'         => $wp_query->post->comment_count,
			'filter'                => $wp_query->post->filter,

			'is_404'                => false,
			'is_page'               => false,
			'is_single'             => false,
			'is_archive'            => false,
			'is_tax'                => false,
		) );
	} else {
		$dummy = wp_parse_args( $args, array(
			'ID'                    => -9999,
			'post_status'           => 'publish',
			'post_author'           => 0,
			'post_parent'           => 0,
			'post_type'             => 'page',
			'post_date'             => 0,
			'post_date_gmt'         => 0,
			'post_modified'         => 0,
			'post_modified_gmt'     => 0,
			'post_content'          => '',
			'post_title'            => '',
			'post_excerpt'          => '',
			'post_content_filtered' => '',
			'post_mime_type'        => '',
			'post_password'         => '',
			'post_name'             => '',
			'guid'                  => '',
			'menu_order'            => 0,
			'pinged'                => '',
			'to_ping'               => '',
			'ping_status'           => '',
			'comment_status'        => 'closed',
			'comment_count'         => 0,
			'filter'                => 'raw',

			'is_404'                => false,
			'is_page'               => false,
			'is_single'             => false,
			'is_archive'            => false,
			'is_tax'                => false,
		) );
	}

	// Bail if dummy post is empty
	if ( empty( $dummy ) ) {
		return;
	}

	// Set the $post global
	$post = new WP_Post( (object) $dummy );

	// Copy the new post global into the main $wp_query
	$wp_query->post       = $post;
	$wp_query->posts      = array( $post );

	// Prevent comments form from appearing
	$wp_query->post_count = 1;
	$wp_query->is_404     = $dummy['is_404'];
	$wp_query->is_page    = $dummy['is_page'];
	$wp_query->is_single  = $dummy['is_single'];
	$wp_query->is_archive = $dummy['is_archive'];
	$wp_query->is_tax     = $dummy['is_tax'];

	// Clean up the dummy post
	unset( $dummy );

	/**
	 * Force the header back to 200 status if not a deliberate 404
	 *
	 * @see http://bbpress.trac.wordpress.org/ticket/1973
	 */
	if ( ! $wp_query->is_404() ) {
		status_header( 200 );
	}

	// If we are resetting a post, we are in theme compat
	econozel_set_theme_compat_active( true );
}

/**
 * Get a template part in an output buffer and return it
 *
 * @since 1.0.0
 *
 * @param string $slug Template slug.
 * @param string $name Optional. Template name.
 * @param bool $echo Optional. Whether to echo the template part. Defaults to false.
 * @return string Template part content
 */
function econozel_buffer_template_part( $slug, $name = '', $echo = false ) {

	// Start buffer
	ob_start();

	// Output template part
	econozel_get_template_part( $slug, $name );

	// Close buffer and get its contents
	$output = ob_get_clean();

	// Echo or return the output buffer contents
	if ( $echo ) {
		echo $output;
	} else {
		return $output;
	}
}

/**
 * Output a template part
 *
 * @since 1.0.0
 *
 * @param string $slug Template slug.
 * @param string $name Optional. Template name.
 */
function econozel_get_template_part( $slug, $name = '' ) {

	// Execute code for this part
	do_action( 'get_template_part_' . $slug, $slug, $name );

	// Setup possible parts
	$templates = array();
	if ( isset( $name ) )
		$templates[] = $slug . '-' . $name . '.php';
	$templates[] = $slug . '.php';

	// Allow template part to be filtered
	$templates = apply_filters( 'econozel_get_template_part', $templates, $slug, $name );

	// Return the part that is found
	return econozel_locate_template( $templates, true, false );
}

/**
 * Retrieve the path of the highest priority template file that exists.
 *
 * @since 1.0.0
 *
 * @param array $template_names Template hierarchy
 * @param bool $load Optional. Whether to load the file when it is found. Default to false.
 * @param bool $require_once Optional. Whether to require_once or require. Default to true.
 * @return string Path of the template file when located.
 */
function econozel_locate_template( $template_names, $load = false, $require_once = true ) {

	// No file found yet
	$located = '';

	// Define template stack
	$stack = apply_filters( 'econozel_get_template_stack', array(
		get_stylesheet_directory(),     // Child theme
		get_template_directory(),       // Parent theme
		econozel_get_theme_compat_dir() // Plugin theme-compat
	) );

	// Define template locations
	$locations = apply_filters( 'econozel_get_template_locations', array(
		'econozel',
		''
	) );

	// Try to find a template file
	foreach ( (array) $template_names as $template_name ) {

		// Skip empty template
		if ( empty( $template_name ) )
			continue;

		// Loop through the template stack
		foreach ( $stack as $template_dir ) {

			// Loop through the template locations
			foreach ( $locations as $location ) {

				// Construct template location
				$template_location = trailingslashit( $template_dir ) . $location;

				// Skip empty locations
				if ( empty( $template_location ) )
					continue;

				// Locate template file
				if ( file_exists( trailingslashit( $template_location ) . $template_name ) ) {
					$located = trailingslashit( $template_location ) . $template_name;
					break 3;
				}
			}
		}
	}

	// Maybe load the template when it was located
	if ( $load && ! empty( $located ) ) {
		load_template( $located, $require_once );
	}

	return $located;
}

/**
 * Return whether the current page is inside theme compatibility
 *
 * @since 1.0.0
 *
 * @return bool Is theme compat active?
 */
function econozel_is_theme_compat_active() {

	// Get Econozel
	$eco = econozel();

	// Compatibility is not set yet
	if ( empty( $eco->theme_compat->active ) )
		return false;

	return (bool) $eco->theme_compat->active;
}

/**
 * Set whether the current page is inside theme compatibility
 *
 * @since 1.0.0
 *
 * @param bool $set Active setting
 * @return bool Is theme compat active?
 */
function econozel_set_theme_compat_active( $set = true ) {
	econozel()->theme_compat->active = (bool) $set;

	return (bool) econozel()->theme_compat->active;
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
