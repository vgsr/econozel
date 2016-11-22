<?php

/**
 * Econozel Functions
 * 
 * @package Econozel
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** User **********************************************************************/

/**
 * Return whether the current user has basic access
 *
 * @since 1.0.0
 *
 * @param int $user_id User ID. Optional. Defaults to the current user.
 * @return bool The user has access
 */
function econozel_check_access( $user_id = 0 ) {

	// Default to the current user
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	return econozel_is_user_vgsr( $user_id ) || user_can( $user_id, 'econozel_editor' );
}

/**
 * Context-aware wrapper for `is_user_vgsr()`
 *
 * @since 1.0.0
 *
 * @param int $user_id User ID. Optional. Defaults to the current user.
 * @return bool The user is VGSR lid
 */
function econozel_is_user_vgsr( $user_id = 0 ) {
	return ( function_exists( 'vgsr' ) && is_user_vgsr( $user_id ) );
}

/**
 * Context-aware wrapper for `is_user_lid()`
 *
 * @since 1.0.0
 *
 * @param int $user_id User ID. Optional. Defaults to the current user.
 * @return bool The user is VGSR lid
 */
function econozel_is_user_lid( $user_id = 0 ) {
	return ( function_exists( 'vgsr' ) && is_user_lid( $user_id ) );
}

/** Rewrite *******************************************************************/

/**
 * Return the root rewrite slug
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_root_slug'
 * @return string Root rewrite slug
 */
function econozel_get_root_slug() {
	return apply_filters( 'econozel_get_root_slug', get_option( '_econozel_root_slug', 'econozel' ) );
}

/**
 * Return the root url
 *
 * @since 1.0.0
 *
 * @return string Root url
 */
function econozel_get_root_url() {
	return home_url( user_trailingslashit( econozel_get_root_slug() ) );
}

/**
 * Return the Article rewrite slug
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_article_slug'
 * @return string Article rewrite slug
 */
function econozel_get_article_slug() {
	return apply_filters( 'econozel_get_article_slug', trailingslashit( econozel_get_root_slug() ) . get_option( '_econozel_article_slug', 'articles' ) );
}

/**
 * Return the Volume rewrite slug
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_volume_slug'
 * @return string Volume rewrite slug
 */
function econozel_get_volume_slug() {
	return apply_filters( 'econozel_get_volume_slug', trailingslashit( econozel_get_root_slug() ) . get_option( '_econozel_volume_slug', 'volumes' ) );
}

/**
 * Return the paged slug
 *
 * @since 1.0.0
 *
 * @global WP_Rewrite $wp_rewrite
 * @return string Paged slug
 */
function econozel_get_paged_slug() {
	global $wp_rewrite;
	return $wp_rewrite->pagination_base;
}

/**
 * Return the root rewrite ID
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_root_rewrite_id'
 * @return string Root rewrite ID
 */
function econozel_get_root_rewrite_id() {
	return apply_filters( 'econozel_get_root_rewrite_id', econozel_get_article_post_type() . '_root' );
}

/**
 * Return the Volume rewrite ID
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_volume_rewrite_id'
 * @return string Volume rewrite ID
 */
function econozel_get_volume_rewrite_id() {
	return apply_filters( 'econozel_get_volume_rewrite_id', econozel_get_volume_tax_id() );
}

/**
 * Return the Volume archive rewrite ID
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_volume_archive_rewrite_id'
 * @return string Volume archive rewrite ID
 */
function econozel_get_volume_archive_rewrite_id() {
	return apply_filters( 'econozel_get_volume_archive_rewrite_id', econozel_get_volume_tax_id() . '_archive' );
}

/**
 * Return the Edition rewrite ID
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_edition_issue_rewrite_id'
 * @return string Edition rewrite ID
 */
function econozel_get_edition_issue_rewrite_id() {
	return apply_filters( 'econozel_get_edition_issue_rewrite_id', econozel_get_edition_tax_id() . '_issue' );
}

/**
 * Delete a blogs rewrite rules, so that they are automatically rebuilt on
 * the subsequent page load.
 *
 * @since 1.0.0
 */
function econozel_delete_rewrite_rules() {
	delete_option( 'rewrite_rules' );
}

/** Options *******************************************************************/

/**
 * Return the setting for Volumes per page
 *
 * @since 1.0.0
 *
 * @param int $default Default per page value
 * @return int Volumes per page
 */
function econozel_get_volumes_per_page( $default = 5 ) {
	return (int) apply_filters( 'econozel_get_volumes_per_page', get_option( '_econozel_volumes_per_page', $default ) );
}

/**
 * Return the setting for Editions per page
 *
 * @since 1.0.0
 *
 * @param int $default Default per page value
 * @return int Editions per page
 */
function econozel_get_editions_per_page( $default = 0 ) {
	return (int) apply_filters( 'econozel_get_editions_per_page', get_option( '_econozel_editions_per_page', $default ) );
}

/**
 * Return whether to prepend the Volume title with 'Volume'
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_prepend_volume_title'
 * @return bool Prepend Volume title
 */
function econozel_prepend_volume_title() {
	return (bool) apply_filters( 'econozel_prepend_volume_title', get_option( '_econozel_prepend_volume_title', true ) );
}

/**
 * Return the whitelist of Edition issues
 *
 * @since 1.0.0
 *
 * @return array Whitelist of Edition issues
 */
function econozel_get_edition_issue_whitelist( $flat = true ) {

	// Get the available issues
	$issues = (array) apply_filters( 'econozel_get_edition_issue_whitelist', explode( ',', get_option( '_econozel_edition_issue_whitelist', '1,2,3,4,5,6,7,8,9,10,11,12' ) ) );

	// Setup array with sanitized keys
	if ( ! $flat ) {
		$issues = array_combine( array_map( 'sanitize_title', $issues ), $issues );
	}

	return $issues;
}

/** Menus *********************************************************************/

/**
 * Return the available custom Econozel nav menu items
 *
 * @since 1.0.0
 *
 * @return array Custom nav menu items
 */
function econozel_nav_menu_get_items() {

	// Try to return items from cache
	if ( ! empty( econozel()->wp_nav_menu_items ) ) {
		return econozel()->wp_nav_menu_items;
	} else {
		econozel()->wp_nav_menu_items = new stdClass;
	}

	// Setup nav menu items
	$items = (array) apply_filters( 'econozel_nav_menu_get_items', array(

		// Econozel root
		'root' => array(
			'title'       => esc_html__( 'Econozel', 'econozel' ),
			'url'         => econozel_get_root_url(),
			'is_current'  => econozel_is_root(),
			'is_parent'   => econozel_is_article_archive() || econozel_is_volume_archive(),
			'is_ancestor' => ! econozel_is_root() && is_econozel(),
		),

		// Volume archives
		'volume-archive' => array(
			'title'       => get_taxonomy( econozel_get_volume_tax_id() )->labels->all_items,
			'url'         => econozel_get_volume_archive_url(),
			'is_current'  => econozel_is_volume_archive(),
			'is_parent'   => econozel_is_volume(),
			'is_ancestor' => econozel_is_edition(),
		),
	) );

	// Set default arguments
	foreach ( $items as $item_id => &$item ) {
		$item = wp_parse_args( $item, array(
			'id'          => $item_id,
			'title'       => '',
			'type'        => econozel_get_article_post_type(),
			'type_label'  => esc_html_x( 'Econozel Page', 'customizer menu type label', 'econozel' ),
			'url'         => '',
			'is_current'  => false,
			'is_parent'   => false,
			'is_ancestor' => false,
		) );
	}

	// Assign items to global
	econozel()->wp_nav_menu_items = $items;

	return $items;
}

/**
 * Add custom Econozel pages to the availabel nav menu items
 *
 * @since 1.0.0
 *
 * @param array $items The nav menu items for the current post type.
 * @param array $args An array of WP_Query arguments.
 * @param WP_Post_Type $post_type The current post type object for this menu item meta box.
 * @return array $items Nav menu items
 */
function econozel_nav_menu_items( $items, $args, $post_type ) {
	global $_wp_nav_menu_placeholder;

	// Econozel items
	if ( econozel_get_article_post_type() === $post_type->name ) {
		$_items = econozel_nav_menu_get_items();

		// Prepend all custom items
		foreach ( array_reverse( $_items ) as $item_id => $item ) {
			$_wp_nav_menu_placeholder = ( 0 > $_wp_nav_menu_placeholder ) ? intval( $_wp_nav_menu_placeholder ) -1 : -1;

			// Prepend item
			array_unshift( $items, (object) array(
				'ID'           => $post_type->name . '-' . $item_id,
				'object_id'    => $_wp_nav_menu_placeholder,
				'object'       => $item_id,
				'post_content' => '',
				'post_excerpt' => '',
				'post_title'   => $item['title'],
				'post_type'    => 'nav_menu_item',
				'type'         => $item['type'],
				'type_label'   => $item['type_label'],
				'url'          => $item['url'],
			) );
		}
	}

	return $items;
}

/**
 * Add custom Econozel pages to the available menu items in the Customizer
 *
 * @since 1.0.0
 *
 * @param array $items The array of menu items.
 * @param string $type The object type.
 * @param string $object The object name.
 * @param int $page The current page number.
 * @return array Menu items
 */
function econozel_customize_nav_menu_available_items( $items, $type, $object, $page ) {

	// First page of Econozel Articles list
	if ( econozel_get_article_post_type() === $object && 0 === $page ) {
		$_items = econozel_nav_menu_get_items();

		// Prepend all custom items
		foreach ( array_reverse( $_items ) as $item_id => $item ) {

			// Redefine item details
			$item['id']     = $post_type . '-' . $item_id;
			$item['object'] = $item_id;

			// Prepend item
			array_unshift( $items, $item );
		}
	}

	return $items;
}

/**
 * Add custom Econozel pages to the searched menu items in the Customizer
 *
 * @since 1.0.0
 *
 * @param array $items The array of menu items.
 * @param array $args Includes 'pagenum' and 's' (search) arguments.
 * @return array Menu items
 */
function econozel_customize_nav_menu_searched_items( $items, $args ) {

	// Search query matches a part of the term 'econozel'
	if ( false !== strpos( 'econozel', strtolower( $args['s'] ) ) ) {
		$post_type = econozel_get_article_post_type();

		// Append all custom items
		foreach ( econozel_nav_menu_get_items() as $item_id => $item ) {

			// Redefine item details
			$item['id']     = $post_type . '-' . $item_id;
			$item['object'] = $item_id;

			// Append item
			$items[] = $item;
		}

		// Also Article archives
		$items[] = array(
			'id'         => $post_type . '-archive',
			'title'      => get_post_type_object( $post_type )->labels->all_items,
			'type'       => 'post_type',
			'type_label' => __( 'Post Type Archive' ),
			'object'     => $post_type,
			'url'        => get_post_type_archive_link( $post_type ),
		);
	}

	return $items;
}

/**
 * Setup details of nav menu item for Econozel pages
 *
 * @since 1.0.0
 *
 * @param WP_Post $menu_item Nav menu item object
 * @return WP_Post Nav menu item object
 */
function econozel_setup_nav_menu_item( $menu_item ) {

	// Econozel page
	if ( econozel_get_article_post_type() === $menu_item->type ) {

		// This is a registered custom menu item
		if ( $item = wp_list_filter( econozel_nav_menu_get_items(), array( 'id' => $menu_item->object ) ) ) {
			$item = array_values( $item );
			$item = (object) $item[0];

			// Set item details
			$menu_item->type_label = $item->type_label;
			$menu_item->url        = $item->url;

			// Set item classes
			if ( ! is_array( $menu_item->classes ) ) {
				$menu_item->classes = array();
			}

			// This is the current page
			if ( $item->is_current ) {
				$menu_item->classes[] = 'current_page_item';
				$menu_item->classes[] = 'current-menu-item';

			// This is the parent page
			} elseif ( $item->is_parent ) {
				$menu_item->classes[] = 'current_page_parent';
				$menu_item->classes[] = 'current-menu-parent';

			// This is an ancestor page
			} elseif ( $item->is_ancestor ) {
				$menu_item->classes[] = 'current_page_ancestor';
				$menu_item->classes[] = 'current-menu-ancestor';
			}
		}

		// Enable plugin filtering
		$menu_item = apply_filters( 'econozel_setup_nav_menu_item', $menu_item );

		// Prevent rendering when the user has no access
		if ( ! econozel_check_access() || empty( $menu_item->url ) ) {
			$menu_item->_invalid = true;
		}

	// Econozel post type (archive) or taxonomy
	} elseif (
		   ( in_array( $menu_item->type, array( 'post_type', 'post_type_archive' ) ) && econozel_get_article_post_type() == $menu_item->object )
		|| ( 'taxonomy' == $menu_item->type && in_array( $menu_item->object, array( econozel_get_edition_tax_id(), econozel_get_volume_tax_id() ) ) )
	) {

		// Prevent rendering when the user has no access
		if ( ! econozel_check_access() ) {
			$menu_item->_invalid = true;
		}
	}

	return $menu_item;
}

/** Utility *******************************************************************/

/**
 * Return the current plugin's version
 *
 * @since 1.0.0
 *
 * @return string Plugin version
 */
function econozel_get_version() {
	return econozel()->version;
}

/**
 * Determine if this plugin is being deactivated
 *
 * @since 1.0.0
 *
 * @param string $basename Optional. Plugin basename to check for.
 * @return bool True if deactivating the plugin, false if not
 */
function econozel_is_deactivation( $basename = '' ) {
	global $pagenow;

	$eco    = econozel();
	$action = false;

	// Bail if not in admin/plugins
	if ( ! ( is_admin() && ( 'plugins.php' === $pagenow ) ) ) {
		return false;
	}

	if ( ! empty( $_REQUEST['action'] ) && ( '-1' !== $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];
	} elseif ( ! empty( $_REQUEST['action2'] ) && ( '-1' !== $_REQUEST['action2'] ) ) {
		$action = $_REQUEST['action2'];
	}

	// Bail if not deactivating
	if ( empty( $action ) || ! in_array( $action, array( 'deactivate', 'deactivate-selected' ) ) ) {
		return false;
	}

	// The plugin(s) being deactivated
	if ( $action === 'deactivate' ) {
		$plugins = isset( $_GET['plugin'] ) ? array( $_GET['plugin'] ) : array();
	} else {
		$plugins = isset( $_POST['checked'] ) ? (array) $_POST['checked'] : array();
	}

	// Set basename if empty
	if ( empty( $basename ) && ! empty( $eco->basename ) ) {
		$basename = $eco->basename;
	}

	// Bail if no basename
	if ( empty( $basename ) ) {
		return false;
	}

	// Is bbPress being deactivated?
	return in_array( $basename, $plugins );
}
