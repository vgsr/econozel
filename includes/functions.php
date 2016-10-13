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
 * @return bool Current user has access
 */
function econozel_check_access( $user_id = 0 ) {
	return econozel_is_user_vgsr( $user_id ) || user_can( $user_id, 'econozel_editor' );
}

/**
 * Context-aware wrapper for `is_user_vgsr()`
 *
 * @since 1.0.0
 *
 * @param int $user_id User ID. Optional. Defaults to the current user.
 * @return bool Current user is VGSR lid
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
 * @return bool Current user is VGSR lid
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
 * Return the Article rewrite slug
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_article_slug'
 * @return string Article rewrite slug
 */
function econozel_get_article_slug() {
	return apply_filters( 'econozel_get_article_slug', trailingslashit( econozel_get_root_slug() ) . get_option( '_econozel_article_slug', _x( 'articles', 'Article rewrite slug', 'econozel' ) ) );
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
	return apply_filters( 'econozel_get_volume_slug', trailingslashit( econozel_get_root_slug() ) . get_option( '_econozel_volume_slug', _x( 'volumes', 'Volume rewrite slug', 'econozel' ) ) );
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
