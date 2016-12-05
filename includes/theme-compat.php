<?php

/**
 * Econozel Theme Compatibility Functions
 * 
 * @package Econozel
 * @subpackage Theme
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

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
 * Return the stack of template path locations
 *
 * @since 1.0.0
 *
 * @return array Template locations
 */
function econozel_get_template_stack() {
	return apply_filters( 'econozel_get_template_stack', array(
		get_stylesheet_directory(),     // Child theme
		get_template_directory(),       // Parent theme
		econozel_get_theme_compat_dir() // Plugin theme-compat
	) );
}

/**
 * Return the template folder locations to look for files
 *
 * @since 1.0.0
 *
 * @return array Template folders
 */
function econozel_get_template_locations() {
	return apply_filters( 'econozel_get_template_locations', array(
		'econozel', // econozel folder
		''          // root folder
	) );
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
			'ID'          => 0,
			'post_author' => 0,
			'post_date'   => 0,
			'post_type'   => '',
			'post_title'  => esc_html_x( 'Econozel', 'root page title', 'econozel' ),
			'is_single'   => true,
			'template'    => array( 'content', 'econozel' ),
		) );

	// Volume Archive
	} elseif ( econozel_is_volume_archive() ) {

		// Reset post
		econozel_theme_compat_reset_post( array(
			'ID'          => 0,
			'post_author' => 0,
			'post_date'   => 0,
			'post_type'   => '',
			'post_title'  => esc_html__( 'Econozel Volumes', 'econozel' ),
			'is_archive'  => true,
			'template'    => array( 'content', 'archive-volume' ),
		) );

	// Single Volume
	} elseif ( econozel_is_volume() ) {

		// Reset post
		econozel_theme_compat_reset_post( array(
			'ID'          => 0,
			'post_author' => 0,
			'post_date'   => 0,
			'post_type'   => '',
			'post_title'  => econozel_get_volume_title( get_queried_object_id() ),
			'is_single'   => true,
			'is_tax'      => true,
			'template'    => array( 'content', 'single-volume' ),
		) );

	// Single Edition
	} elseif ( econozel_is_edition() ) {

		// Reset post
		econozel_theme_compat_reset_post( array(
			'ID'          => 0,
			'post_author' => 0,
			'post_date'   => 0,
			'post_type'   => '',
			'post_title'  => econozel_get_edition_title( get_queried_object_id() ),
			'is_single'   => true,
			'is_tax'      => true,
			'template'    => array( 'content', 'single-edition' ),
		) );
	}

	// So we're using theme compatibility?
	if ( econozel_is_theme_compat_active() ) {

		// Remove filters on 'the_content'
		// TODO: When to restore the filters?
		remove_all_filters( 'the_content' );

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

	// If we are resetting a post, we are in theme compat
	econozel_set_theme_compat_active( true );

	// Render post content from template
	if ( isset( $dummy['template'] ) ) {
		$dummy['post_content'] = econozel_buffer_template_part( $dummy['template'][0], $dummy['template'][1], false );
		unset( $dummy['template'] );
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

	// Get template stack and locations
	$stack     = econozel_get_template_stack();
	$locations = econozel_get_template_locations();

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
 * Enqueue a script from the highest priority location in the template stack.
 *
 * Registers the style if file provided (does NOT overwrite) and enqueues.
 *
 * @since 1.0.0
 *
 * @param string      $handle Name of the stylesheet.
 * @param string|bool $file   Relative path to stylesheet. Example: '/css/mystyle.css'.
 * @param array       $deps   An array of registered style handles this stylesheet depends on. Default empty array.
 * @param string|bool $ver    String specifying the stylesheet version number, if it has one. This parameter is used
 *                            to ensure that the correct version is sent to the client regardless of caching, and so
 *                            should be included if a version number is available and makes sense for the stylesheet.
 * @param string      $media  Optional. The media for which this stylesheet has been defined.
 *                            Default 'all'. Accepts 'all', 'aural', 'braille', 'handheld', 'projection', 'print',
 *                            'screen', 'tty', or 'tv'.
 *
 * @return string The style filename if one is located.
 */
function econozel_enqueue_style( $handle = '', $file = '', $dependencies = array(), $version, $media = 'all' ) {

	// No file found yet
	$located = false;

	// Trim off any slashes from the template name
	$file = ltrim( $file, '/' );

	// Make sure there is always a version
	if ( empty( $version ) ) {
		$version = econozel_get_version();
	}

	// Loop through template stack
	foreach ( (array) econozel_get_template_stack() as $template_location ) {

		// Continue if $template_location is empty
		if ( empty( $template_location ) ) {
			continue;
		}

		// Check child theme first
		if ( file_exists( trailingslashit( $template_location ) . $file ) ) {
			$located = trailingslashit( $template_location ) . $file;
			break;
		}
	}

	// Enqueue if located
	if ( !empty( $located ) ) {

		$content_dir = constant( 'WP_CONTENT_DIR' );

		// IIS (Windows) here
		// Replace back slashes with forward slash
		if ( strpos( $located, '\\' ) !== false ) {
			$located     = str_replace( '\\', '/', $located     );
			$content_dir = str_replace( '\\', '/', $content_dir );
		}

		// Make path to file relative to site URL
		$located = str_replace( $content_dir, content_url(), $located );

		// Enqueue the style
		wp_enqueue_style( $handle, $located, $dependencies, $version, $media );
	}

	return $located;
}

/**
 * Enqueue a script from the highest priority location in the template stack.
 *
 * Registers the style if file provided (does NOT overwrite) and enqueues.
 *
 * @since 1.0.0
 *
 * @param string      $handle    Name of the script.
 * @param string|bool $file      Relative path to the script. Example: '/js/myscript.js'.
 * @param array       $deps      An array of registered handles this script depends on. Default empty array.
 * @param string|bool $ver       Optional. String specifying the script version number, if it has one. This parameter
 *                               is used to ensure that the correct version is sent to the client regardless of caching,
 *                               and so should be included if a version number is available and makes sense for the script.
 * @param bool        $in_footer Optional. Whether to enqueue the script before </head> or before </body>.
 *                               Default 'false'. Accepts 'false' or 'true'.
 *
 * @return string The script filename if one is located.
 */
function econozel_enqueue_script( $handle = '', $file = '', $dependencies = array(), $version = false, $in_footer = 'all' ) {

	// No file found yet
	$located = false;

	// Trim off any slashes from the template name
	$file = ltrim( $file, '/' );

	// Make sure there is always a version
	if ( empty( $version ) ) {
		$version = econozel_get_version();
	}

	// Loop through template stack
	foreach ( (array) econozel_get_template_stack() as $template_location ) {

		// Continue if $template_location is empty
		if ( empty( $template_location ) ) {
			continue;
		}

		// Check child theme first
		if ( file_exists( trailingslashit( $template_location ) . $file ) ) {
			$located = trailingslashit( $template_location ) . $file;
			break;
		}
	}

	// Enqueue if located
	if ( !empty( $located ) ) {

		$content_dir = constant( 'WP_CONTENT_DIR' );

		// IIS (Windows) here
		// Replace back slashes with forward slash
		if ( strpos( $located, '\\' ) !== false ) {
			$located     = str_replace( '\\', '/', $located     );
			$content_dir = str_replace( '\\', '/', $content_dir );
		}

		// Make path to file relative to site URL
		$located = str_replace( $content_dir, content_url(), $located );

		// Enqueue the style
		wp_enqueue_script( $handle, $located, $dependencies, $version, $in_footer );
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

/**
 * Load a custom Econozel functions file, similar to each theme's functions.php file.
 *
 * @since 1.0.0
 *
 * @global string $pagenow
 */
function econozel_load_theme_functions() {
	global $pagenow;

	// When Econozel is being deactivated, do not load any more files
	if ( econozel_is_deactivation() )
		return;

	// Load file when not installing
	if ( ! defined( 'WP_INSTALLING' ) || ( ! empty( $pagenow ) && ( 'wp-activate.php' !== $pagenow ) ) ) {
		econozel_locate_template( 'econozel-functions.php', true );
	}
}
