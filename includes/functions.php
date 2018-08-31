<?php

/**
 * Econozel Functions
 * 
 * @package Econozel
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Versions ******************************************************************/

/**
 * Output the plugin version
 *
 * @since 1.0.0
 */
function econozel_version() {
	echo econozel_get_version();
}

	/**
	 * Return the plugin version
	 *
	 * @since 1.0.0
	 *
	 * @return string The plugin version
	 */
	function econozel_get_version() {
		return econozel()->version;
	}

/**
 * Output the plugin database version
 *
 * @since 1.0.0
 */
function econozel_db_version() {
	echo econozel_get_db_version();
}

	/**
	 * Return the plugin database version
	 *
	 * @since 1.0.0
	 *
	 * @return string The plugin version
	 */
	function econozel_get_db_version() {
		return econozel()->db_version;
	}

/**
 * Output the plugin database version directly from the database
 *
 * @since 1.0.0
 */
function econozel_db_version_raw() {
	echo econozel_get_db_version_raw();
}

	/**
	 * Return the plugin database version directly from the database
	 *
	 * @since 1.0.0
	 *
	 * @return string The current plugin version
	 */
	function econozel_get_db_version_raw() {
		return get_option( 'econozel_db_version', '' );
	}

/** User **********************************************************************/

/**
 * Return whether the current user has basic access
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_check_access'
 *
 * @param int $user_id User ID. Optional. Defaults to the current user.
 * @return bool Has the user basic access?
 */
function econozel_check_access( $user_id = 0 ) {

	// Default to the current user
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	// Allow Econozel Editors
	$retval = user_can( $user_id, 'econozel_editor' );

	return (bool) apply_filters( 'econozel_check_access', $retval, $user_id );
}

/**
 * Return whether the current user has admin access
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_check_admin_access'
 *
 * @param int $user_id User ID. Optional. Defaults to the current user.
 * @return bool Has the user admin access?
 */
function econozel_check_admin_access( $user_id = 0 ) {

	// Default to the current user
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	// Allow Econozel Editors
	$retval = user_can( $user_id, 'econozel_editor' );

	// When not restricted, allow filtering
	if ( ! econozel_toggle_admin_access() ) {
		$retval = (bool) apply_filters( 'econozel_check_admin_access', $retval, $user_id );
	}

	return $retval;
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
 * Return the Edition rewrite slug
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_edition_slug'
 * @return string Edition rewrite slug
 */
function econozel_get_edition_slug() {
	return apply_filters( 'econozel_get_edition_slug', trailingslashit( econozel_get_root_slug() ) . get_option( '_econozel_edition_slug', 'editions' ) );
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
 * Return the Edition archive rewrite ID
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_edition_archive_rewrite_id'
 * @return string Edition archive rewrite ID
 */
function econozel_get_edition_archive_rewrite_id() {
	return apply_filters( 'econozel_get_edition_archive_rewrite_id', econozel_get_edition_tax_id() . '_archive' );
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

/**
 * Update WP's htaccess file with plugin specific rules
 *
 * @since 1.0.0
 *
 * @uses WPDB $wpdb
 * @uses apply_filters() Calls 'econozel_update_htaccess'
 */
function econozel_update_htaccess() {
	global $wpdb;

	$lines = array();

	/** Editions **************************************************************/

	/**
	 * Prevent crawlers from indexing Edition documents.
	 */

	// Query Edition documents at once
	$query     = $wpdb->prepare( "SELECT meta_value FROM {$wpdb->termmeta} WHERE meta_key = %s", 'document' );
	$documents = array_unique( $wpdb->get_col( $query ) );

	// Get file paths from documents
	foreach ( $documents as $k => $attachment_id ) {
		if ( $path = get_attached_file( $attachment_id, true ) ) {
			$documents[ $k ] = basename( $path );
		} else {
			unset( $documents[ $k ] );
		}
	}

	if ( $documents ) {
		$lines[] = '';
		$lines[] = '# block edition documents from being indexed';
		$lines[] = '<FilesMatch "^(' . implode( '|', $documents ) . ')$">';
		$lines[] = 'Header Set X-Robots-Tag "noindex, noarchive, nosnippet"';
		$lines[] = '</FilesMatch>';
	}

	// Update plugin section in htaccess file
	insert_with_markers( get_home_path() . '.htaccess', 'Econozel', (array) apply_filters( 'econozel_update_htaccess', $lines ) );
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
 * Return whether to restrict admin access to Econozel Editors only
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_toggle_admin_access'
 * @return bool Restricted admin access
 */
function econozel_toggle_admin_access() {
	return (bool) apply_filters( 'econozel_toggle_admin_access', get_option( '_econozel_toggle_admin_access', false ) );
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
 * @uses apply_filters() Calls 'econozel_get_edition_issue_whitelist'
 * @return array Whitelist of Edition issues
 */
function econozel_get_edition_issue_whitelist() {

	// Get the available issues
	$issues = (array) apply_filters( 'econozel_get_edition_issue_whitelist', explode( ',', get_option( '_econozel_edition_issue_whitelist', '1,2,3,4,5,6,7,8,9,10,11,12' ) ) );

	// Setup array with sanitized keys
	$issues = array_combine( array_map( 'sanitize_title', $issues ), $issues );

	return $issues;
}

/** Post Type *****************************************************************/

/**
 * Return the post type title
 *
 * @since 1.0.0
 *
 * @param string $post_type Post type name
 * @return string Post type title
 */
function econozel_post_type_title( $post_type = '' ) {
	$title = '';

	if ( post_type_exists( $post_type ) ) {
		$title = get_post_type_object( $post_type )->labels->name;
	}

	return $title;
}

/**
 * Return whether the post query is (exclusively) for Econozel Articles
 *
 * @since 1.0.0
 *
 * @param WP_Query $posts_query Query object
 * @param bool $exclusive Optional. Whether the query should be exclusively for Articles.
 * @return bool Is this an Econozel Article query?
 */
function econozel_is_article_query( $posts_query, $exclusive = false ) {

	// Require a `WP_Query` object
	$retval = is_a( $posts_query, 'WP_Query' );

	if ( $retval ) {
		$query_var = array_values( (array) $posts_query->get( 'post_type' ) );
		$retval    = $exclusive
			? array( econozel_get_article_post_type() ) === $query_var
			: in_array( econozel_get_article_post_type(), $query_var, true );
	}

	return $retval;
}

/**
 * Modify the post's thumbnail ID
 *
 * @since 1.0.0
 *
 * @param mixed $value Meta value
 * @param int $object_id Post ID
 * @param string $meta_key Meta key
 * @param bool $single Whether to return a single value
 * @return mixed Meta value
 */
function econozel_filter_post_thumbnail( $value, $object_id, $meta_key, $single ) {

	// Getting thumbnail ids, unfiltered, for Articles
	if ( '_thumbnail_id' === $meta_key && null === $value && $article = econozel_get_article( $object_id ) ) {

		// Check cache first for any existing value
		$meta_cache = wp_cache_get( $object_id, 'post_meta' );
		$meta_value = isset( $meta_cache[ $meta_key ] )
			? ( $single ? $meta_cache[ $meta_key ][0] : $meta_cache[ $meta_key ] )
			: false;

		// Article has no featured image of its own and is published in an Edition
		if ( ! $meta_value && $edition = econozel_get_article_edition( $article ) ) {

			// Use the Edition's cover photo
			if ( econozel_has_edition_cover_photo( $edition ) ) {
				$value = econozel_get_edition_cover_photo( $edition );
			}
		}
	}

	return $value;
}

/** Menus *********************************************************************/

/**
 * Return the available custom Econozel nav menu items
 *
 * @since 1.0.0
 *
 * @return array Custom nav menu items
 */
function econozel_get_nav_menu_items() {

	// Setup items in cache
	if ( empty( econozel()->wp_nav_menu_items ) ) {

		// Setup nav menu items
		$items = (array) apply_filters( 'econozel_get_nav_menu_items', array(

			// Econozel root
			'root' => array(
				'title'       => esc_html_x( 'Econozel Home', 'root page title', 'econozel' ),
				'url'         => econozel_get_root_url(),
				'is_current'  => econozel_is_root(),
				'is_parent'   => econozel_is_article_archive() || econozel_is_volume_archive() || econozel_is_edition_archive(),
				'is_ancestor' => ! econozel_is_root() && is_econozel(),
			),

			// Volume archives
			'volume-archive' => array(
				'title'       => get_taxonomy( econozel_get_volume_tax_id() )->labels->all_items,
				'url'         => econozel_get_volume_archive_url(),
				'is_current'  => econozel_is_volume_archive(),
				'is_parent'   => econozel_is_volume(),
			),

			// Edition archives
			'edition-archive' => array(
				'title'       => get_taxonomy( econozel_get_edition_tax_id() )->labels->all_items,
				'url'         => econozel_get_edition_archive_url(),
				'is_current'  => econozel_is_edition_archive(),
				'is_parent'   => econozel_is_edition(),
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

		// Assign items to cache
		econozel()->wp_nav_menu_items = $items;
	}

	return econozel()->wp_nav_menu_items;
}

/**
 * Add custom Econozel pages to the available nav menu items metabox
 *
 * @since 1.0.0
 *
 * @param array $items The nav menu items for the current post type.
 * @param array $args An array of WP_Query arguments.
 * @param WP_Post_Type $post_type The current post type object for this menu item meta box.
 * @return array $items Nav menu items
 */
function econozel_nav_menu_items_metabox( $items, $args, $post_type ) {
	global $_wp_nav_menu_placeholder;

	// Econozel items
	if ( econozel_get_article_post_type() === $post_type->name ) {
		$_items = econozel_get_nav_menu_items();

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
		$_items = econozel_get_nav_menu_items();

		// Prepend all custom items
		foreach ( array_reverse( $_items ) as $item_id => $item ) {

			// Redefine item details
			$item['id']     = $object . '-' . $item_id;
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
		foreach ( econozel_get_nav_menu_items() as $item_id => $item ) {

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
		if ( $item = wp_list_filter( econozel_get_nav_menu_items(), array( 'id' => $menu_item->object ) ) ) {
			$item = (object) reset( $item );

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

	// Edition page
	} elseif ( 'taxonomy' === $menu_item->type && econozel_get_edition_tax_id() === $menu_item->object ) {

		// Set item classes
		if ( ! is_array( $menu_item->classes ) ) {
			$menu_item->classes = array();
		}

		// This is the current page
		if ( econozel_is_edition() && econozel_get_edition_id() == $menu_item->object_id ) {
			$menu_item->classes[] = 'current_page_item';
			$menu_item->classes[] = 'current-menu-item';

		// This is the parent page
		} elseif ( econozel_is_article() && econozel_get_article_edition() == $menu_item->object_id ) {
			$menu_item->classes[] = 'current_page_parent';
			$menu_item->classes[] = 'current-menu-parent';
		}
	}

	// Econozel post type (archive) or taxonomy
	if ( (
	   in_array( $menu_item->type, array( 'post_type', 'post_type_archive' ) )
	   && econozel_get_article_post_type() == $menu_item->object
	) || (
	   'taxonomy' === $menu_item->type
	   && in_array( $menu_item->object, array( econozel_get_edition_tax_id(), econozel_get_volume_tax_id() ) )
	) ) {

		// Prevent rendering when the user has no access
		if ( ! econozel_check_access() ) {
			$menu_item->_invalid = true;
		}
	}

	return $menu_item;
}

/**
 * Modify the sorted list of menu items
 *
 * @since 1.0.0
 *
 * @param  array $items Menu items
 * @param  array $args Arguments for `wp_nav_menu()`
 * @return array Menu items
 */
function econozel_nav_menu_objects( $items, $args ) {

	// When Econozeling
	if ( is_econozel() ) {
		$posts_page = (int) get_option( 'page_for_posts' );

		foreach ( $items as $k => $item ) {

			// Remove the posts page's parent status/class. By default WordPress
			// appoints the posts page as parent for non-page pages. Please not.
			if ( $item->object_id == $posts_page && 'post_type' == $item->type && in_array( 'current_page_parent', $item->classes ) ) {
				unset( $items[ $k ]->classes[ array_search( 'current_page_parent', $item->classes ) ] );
			}
		}
	}

	return $items;
}
