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
			if ( is_user_lid( $user_id ) ) {
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
				if ( $user_id === (int) $article->post_author && user_can( $user_id, $post_type->cap->edit_posts ) ) {
					$caps[] = 'read';

				// Unknown, so defer to edit_others_posts
				} else {
					$caps[] = $post_type->cap->edit_others_posts;
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
			if ( is_user_lid( $user_id ) ) {
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
			if ( is_user_lid( $user_id ) ) {
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
