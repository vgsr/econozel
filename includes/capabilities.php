<?php

/**
 * Econozel Capability Functions
 * 
 * @package Econozel
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Articles ******************************************************************/

/**
 * Return the capability mappings for the Article post type
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_article_post_type_caps'
 * @return array Article post type caps
 */
function econozel_get_article_post_type_caps() {
	return apply_filters( 'econozel_get_article_post_type_caps', array(
		'edit_post'           => 'edit_econozel_article',
		'edit_posts'          => 'edit_econozel_articles',
		'edit_others_posts'   => 'edit_others_econozel_articles',
		'publish_posts'       => 'publish_econozel_articles',
		'read_private_posts'  => 'read_private_econozel_articles',
		'delete_posts'        => 'delete_econozel_articles',
		'delete_others_posts' => 'delete_others_econozel_articles'
	) );
}

/**
 * Map caps for the Article post type
 *
 * @since 1.0.0
 *
 * @param array $caps Mapped caps
 * @param string $cap Required capability name
 * @param int $user_id User ID
 * @param array $args Additional arguments
 * @return array Mapped caps
 */
function econozel_map_article_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	// Check the required capability
	switch ( $cap ) {

		/** Editing ***********************************************************/

		case 'edit_econozel_articles' :

			// VGSR leden can edit one's own Articles
			if ( econozel_is_user_lid( $user_id ) ) {
				$caps = array( 'read' );
			} else {
				$caps = array( 'econozel_editor' );
			}

			break;

		case 'edit_econozel_article' :

			// Get the Article
			$article = econozel_get_article( $args[0] );
			if ( ! empty( $article ) ) {

				// Get caps for post type object
				$post_type = get_post_type_object( econozel_get_article_post_type() );
				$caps      = array();

				// User is author so allow edit
				// @todo Account for multiple authors
				// @todo Consider applying a time window to limit editing
				if ( $user_id === (int) $article->post_author && user_can( $user_id, $post_type->cap->edit_posts ) ) {
					$caps[] = 'read';

				// Defer to edit_others_posts
				} elseif ( user_can( $user_id, $post_type->cap->edit_others_posts ) ) {
					$caps[] = 'read';

				// Unknown, so block access
				} else {
					$caps[] = 'do_not_allow';
				}
			}

			break;

		/** Other meta caps ***************************************************/

		case 'edit_others_econozel_articles' :
		case 'publish_econozel_articles' :
		case 'read_private_econozel_articles' :
		case 'delete_econozel_articles' :
		case 'delete_others_econozel_articles' :

			// Only allow Econozel Editors
			$caps = array( 'econozel_editor' );

			break;

		/** Admin *************************************************************/

		case 'econozel_articles_admin' :

			// VGSR leden can enter the admin
			if ( econozel_is_user_lid( $user_id ) ) {
				$caps = array( 'read' );
			} else {
				$caps = array( 'econozel_editor' );
			}

			break;
	}

	return $caps;
}

/** Editions ******************************************************************/

/**
 * Return the capability mappings for the edition taxonomy
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_edition_tax_caps'
 * @return array Edition taxonomy caps
 */
function econozel_get_edition_tax_caps() {
	return apply_filters( 'econozel_get_edition_tax_caps', array(
		'manage_terms' => 'manage_econozel_editions',
		'edit_terms'   => 'edit_econozel_editions',
		'delete_terms' => 'delete_econozel_editions',
		'assign_terms' => 'assign_econozel_editions'
	) );
}

/**
 * Map caps for the Edition taxonomy
 *
 * @since 1.0.0
 *
 * @param array $caps Mapped caps
 * @param string $cap Required capability name
 * @param int $user_id User ID
 * @param array $args Additional arguments
 * @return array Mapped caps
 */
function econozel_map_edition_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	// Check the required capability
	switch ( $cap ) {

		/** All meta caps *****************************************************/

		case 'manage_econozel_editions' :
		case 'edit_econozel_editions' :
		case 'delete_econozel_editions' :
		case 'assign_econozel_editions' :

			// Only allow Econozel Editors
			$caps = array( 'econozel_editor' );

			break;

		/** Admin *************************************************************/

		case 'econozel_edition_admin' :
			$caps = array( 'econozel_editor' );
			break;
	}

	return $caps;
}

/** Volumes *******************************************************************/

/**
 * Return the capability mappings for the volume taxonomy
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_volume_tax_caps'
 * @return array Volume taxonomy caps
 */
function econozel_get_volume_tax_caps() {
	return apply_filters( 'econozel_get_volume_tax_caps', array(
		'manage_terms' => 'manage_econozel_volumes',
		'edit_terms'   => 'edit_econozel_volumes',
		'delete_terms' => 'delete_econozel_volumes',
		'assign_terms' => 'assign_econozel_volumes'
	) );
}

/**
 * Map caps for the Volume taxonomy
 *
 * @since 1.0.0
 *
 * @param array $caps Mapped caps
 * @param string $cap Required capability name
 * @param int $user_id User ID
 * @param array $args Additional arguments
 * @return array Mapped caps
 */
function econozel_map_volume_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	// Check the required capability
	switch ( $cap ) {

		/** All meta caps *****************************************************/

		case 'manage_econozel_volumes' :
		case 'edit_econozel_volumes' :
		case 'delete_econozel_volumes' :
		case 'assign_econozel_volumes' :

			// Only allow Econozel Editors
			$caps = array( 'econozel_editor' );

			break;

		/** Admin *************************************************************/

		case 'econozel_volume_admin' :
			$caps = array( 'econozel_editor' );
			break;
	}

	return $caps;
}

/** Post Tags *****************************************************************/

/**
 * Map caps for the Post Tags taxonomy
 *
 * @since 1.0.0
 *
 * @param array $caps Mapped caps
 * @param string $cap Required capability name
 * @param int $user_id User ID
 * @param array $args Additional arguments
 * @return array Mapped caps
 */
function econozel_map_post_tag_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	// Check the required capability
	switch ( $cap ) {

		/** Management ********************************************************/

		case 'manage_post_tags' :
		case 'edit_post_tags' :
		case 'delete_post_tags' :

			// Allow Econozel Editors
			if ( user_can( $user_id, 'econozel_editor' ) ) {
				$caps = array( 'econozel_editor' );

			// Or default to original manage_categories
			} else {
				$caps = array( 'manage_categories' );
			}

			break;

		/** Assigning *********************************************************/

		case 'assign_post_tags' :

			// VGSR leden can assign tags
			if ( econozel_is_user_lid( $user_id ) ) {
				$caps = array( 'read' );

			// Allow Econozel Editors
			} elseif ( user_can( $user_id, 'econozel_editor' ) ) {
				$caps = array( 'econozel_editor' );

			// Or default to original edit_posts
			} else {
				$caps = array( 'edit_posts' );
			}

			break;
	}

	return $caps;
}

/** Roles *********************************************************************/

/**
 * The following functionality is mainly copied over from the
 * implementation of dynamic user roles in bbPress. The dynamic
 * roles are added to the global roles collection, but are not
 * added to the database. In addition, dynamic role management
 * is separated from the default blog roles.
 */

/**
 * Helper function to add a filter to the user roles option
 *
 * @since 1.0.0
 *
 * @global WPDB $wpdb
 */
function econozel_filter_user_roles_option() {
	global $wpdb;

	$role_key = $wpdb->prefix . 'user_roles';

	add_filter( "option_{$role_key}", '_econozel_init_dynamic_roles' );
}

/**
 * Add dynamic plugin roles to the roles option
 *
 * @since 1.0.0
 *
 * @param array $roles Registered roles
 * @return array Roles
 */
function _econozel_init_dynamic_roles( $roles = array() ) {

	// Loop all plugin roles 
	foreach ( econozel_get_dynamic_roles() as $role => $args ) {
		$roles[ $role ] = $args;
	}

	return $roles;
}

/**
 * Return the capabilities for a given role
 *
 * @since 1.0.0
 *
 * @param string $role Role ID
 * @return array Primitive capabilities
 */
function econozel_get_caps_for_role( $role = '' ) {

	// Check role ID
	switch ( $role ) {

		// Econozel Editor
		case econozel_get_editor_role() :
			$caps = array(

				// Primary cap
				'econozel_editor'                 => true,

				// Article
				'edit_econozel_article'           => true,
				'edit_econozel_articles'          => true,
				'edit_others_econozel_articles'   => true,
				'publish_econozel_articles'       => true,
				'read_private_econozel_articles'  => true,
				'delete_econozel_articles'        => true,
				'delete_others_econozel_articles' => true,

				// Edition
				'manage_econozel_editions'        => true,
				'edit_econozel_editions'          => true,
				'delete_econozel_editions'        => true,
				'assign_econozel_editions'        => true,

				// Volume
				'manage_econozel_volumes'         => true,
				'edit_econozel_volumes'           => true,
				'delete_econozel_volumes'         => true,
				'assign_econozel_volumes'         => true,

				// Post tags
				'manage_post_tags'                => true,
				'edit_post_tags'                  => true,
				'delete_post_tags'                => true,
				'assign_post_tags'                => true,
			);

			break;

		// Default
		default :
			$caps = array( 'econozel_editor' => false );
			break;
	}

	return $caps;
}

/**
 * Return a filtered list of available plugin roles
 *
 * @see bbp_get_dynamic_roles()
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_dynamic_roles'
 * @return array Roles
 */
function econozel_get_dynamic_roles() {
	return (array) apply_filters( 'econozel_get_dynamic_roles', array(

		// Econozel Editor
		econozel_get_editor_role() => array(
			'name'         => esc_html__( 'Econozel Editor', 'econozel' ),
			'capabilities' => econozel_get_caps_for_role( econozel_get_editor_role() )
		)
	) );
}

/**
 * Return the name for the given role
 *
 * @since 1.0.0
 *
 * @param string $role Role ID
 * @return string Role name
 */
function econozel_get_dynamic_role_name( $role = '' ) {
	$roles = econozel_get_dynamic_roles();
	$name  = isset( $roles[ $role ] ) ? $roles[ $role ]['name'] : '';

	return $name;
}

/**
 * Filter the blog editable roles collection
 *
 * This removes the plugin roles from the collection of editable
 * blog roles, preventing plugin role assignment by any user having
 * the 'edit_users' capability. This also prevents overwriting blog
 * roles with lesser meaningful plugin roles.
 *
 * @since 1.0.0
 *
 * @param array $roles Registered roles
 * @return array Roles
 */
function econozel_filter_editable_roles( $roles = array() ) {

	// Loop all plugin roles
	foreach ( array_keys( econozel_get_dynamic_roles() ) as $eco_role ) {

		// Loop through registered roles
		foreach ( array_keys( $roles ) as $wp_role ) {

			// If keys match, remove the plugin role
			if ( $wp_role === $eco_role ) {
				unset( $roles[ $eco_role ] );
			}
		}
	}

	return $roles;
}

/**
 * Act when a new role is set for the user
 *
 * This maintains the assigned plugin role(s) for the given user,
 * when their blog role is changed.
 *
 * @since 1.0.0
 *
 * @param int $user_id User ID
 * @param string $role Role ID that is set
 * @param array $old_roles Previous roles
 */
function econozel_handle_set_user_role( $user_id, $role, $old_roles ) {

	// Get all plugin roles
	$eco_roles = array_keys( econozel_get_dynamic_roles() );

	// Bail when a plugin role was set
	if ( in_array( $role, $eco_roles ) )
		return;

	// Get the edited user
	$user = get_userdata( $user_id );

	// Loop the plugin roles
	foreach ( $eco_roles as $eco_role ) {

		// Re-add the plugin role if the user had it
		if ( in_array( $eco_role, $old_roles ) ) {
			$user->add_role( $eco_role );
		}
	}
}

/**
 * Get the plugin role of the given user
 *
 * @since 1.0.0
 *
 * @param int $user_id User ID. Optional. Defaults to the current user.
 * @return string|bool Role ID or False when not found
 */
function econozel_get_user_role( $user_id = 0 ) {

	// Default to the current user
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	// Define local variable(s)
	$user = get_userdata( $user_id );
	$role = false;

	if ( $user ) {

		// Find plugin roles in the user
		$roles = array_intersect(
			array_values( $user->roles ),
			array_keys( econozel_get_dynamic_roles() )
		);

		// Get first role found - assuming unique plugin role assignment
		if ( ! empty( $roles ) ) {
			$role = array_shift( $roles );
		}
	}

	return $role;
}

/**
 * Set the plugin role of the given user
 *
 * @since 1.0.0
 *
 * @param int $user_id User ID. Optional. Defaults to the current user.
 * @param string $role Role ID
 */
function econozel_set_user_role( $user_id = 0, $role = '' ) {

	// Default to the current user
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	// Get available plugin roles
	$user     = get_userdata( $user_id );
	$roles    = array_keys( econozel_get_dynamic_roles() );
	$old_role = econozel_get_user_role( $user_id );

	// Bail when the role is already assigned
	if ( ! $user || $role === $old_role )
		return;

	// Remove the current role
	$user->remove_role( $old_role );

	// Set the new role
	if ( ! empty( $role ) && in_array( $role, $roles ) ) {
		$user->add_role( $role );
	}
}

/**
 * Return the ID for the Econozel Editor role
 *
 * @since 1.0.0
 *
 * @return string Role ID
 */
function econozel_get_editor_role() {
	return 'econozel_editor';
}
