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
		'edit_posts'          => 'edit_econozel_articles',
		'edit_others_posts'   => 'edit_others_econozel_articles',
		'publish_posts'       => 'publish_econozel_articles',
		'read_private_posts'  => 'read_private_econozel_articles',
		'read_hidden_posts'   => 'read_hidden_econozel_articles',
		'delete_posts'        => 'delete_econozel_articles',
		'delete_others_posts' => 'delete_others_econozel_articles'
	) );
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

