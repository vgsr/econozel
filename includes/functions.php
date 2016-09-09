<?php

/**
 * Econozel Functions
 * 
 * @package Econozel
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Article *******************************************************************/

/**
 * Return the econozel article post type
 *
 * @since 1.0.0
 *
 * @return string Post type name
 */
function econozel_get_article_post_type() {
	return 'econozel';
}

/**
 * Return the labels for the Article post type
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_article_post_type_labels'
 * @return array Article post type labels
 */
function econozel_get_article_post_type_labels() {
	return apply_filters( 'econozel_get_article_post_type_labels', array(
		'name'               => __( 'Econozel Articles',          'econozel' ),
		'menu_name'          => __( 'Econozel',                   'econozel' ),
		'singular_name'      => __( 'Econozel Article',           'econozel' ),
		'all_items'          => __( 'All Articles',               'econozel' ),
		'add_new'            => __( 'New Article',                'econozel' ),
		'add_new_item'       => __( 'Create New Article',         'econozel' ),
		'edit'               => __( 'Edit',                       'econozel' ),
		'edit_item'          => __( 'Edit Article',               'econozel' ),
		'new_item'           => __( 'New Article',                'econozel' ),
		'view'               => __( 'View Article',               'econozel' ),
		'view_item'          => __( 'View Article',               'econozel' ),
		'search_items'       => __( 'Search Articles',            'econozel' ),
		'not_found'          => __( 'No articles found',          'econozel' ),
		'not_found_in_trash' => __( 'No articles found in Trash', 'econozel' ),
	) );
}

/**
 * Return the Article post type rewrite settings
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_article_post_type_rewrite'
 * @return array Article post type support
 */
function econozel_get_article_post_type_rewrite() {
	return apply_filters( 'econozel_get_article_post_type_rewrite', array(
		'slug'       => econozel_get_article_slug(),
		'with_front' => false
	) );
}

/**
 * Return an array of features the Article post type supports
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_article_post_type_supports'
 * @return array Article post type support
 */
function econozel_get_article_post_type_supports() {
	return apply_filters( 'econozel_get_article_post_type_supports', array(
		'title',
		'author',
		'editor',
		'comments'
	) );
}

/** Volume ********************************************************************/

/**
 * Return the Volume taxonomy id
 *
 * @since 1.0.0
 *
 * @return string Taxonomy id
 */
function econozel_get_volume_tax_id() {
	return 'econozel_volume';
}

/**
 * Return the labels for the Volume taxonomy
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_volume_tax_labels'
 * @return array Volume taxonomy labels
 */
function econozel_get_volume_tax_labels() {
	return apply_filters( 'econozel_get_volume_tax_labels', array(
		'name'          => __( 'Econozel Volumes',  'econozel' ),
		'menu_name'     => __( 'Volumes',           'econozel' ),
		'singular_name' => __( 'Econozel Volume',   'econozel' ),
		'search_items'  => __( 'Search Volumes',    'econozel' ),
		'popular_items' => null, // Disable tagcloud
		'all_items'     => __( 'All Volumes',       'econozel' ),
		'no_items'      => __( 'No Volume',         'econozel' ),
		'edit_item'     => __( 'Edit Volume',       'econozel' ),
		'update_item'   => __( 'Update Volume',     'econozel' ),
		'add_new_item'  => __( 'Add New Volume',    'econozel' ),
		'new_item_name' => __( 'New Volume Name',   'econozel' ),
		'view_item'     => __( 'View Volume',       'econozel' )
	) );
}

/** Edition *******************************************************************/

/**
 * Return the Edition taxonomy id
 *
 * @since 1.0.0
 *
 * @return string Taxonomy id
 */
function econozel_get_edition_tax_id() {
	return 'econozel_edition';
}

/**
 * Return the labels for the Edition taxonomy
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_edition_tax_labels'
 * @return array Edition taxonomy labels
 */
function econozel_get_edition_tax_labels() {
	return apply_filters( 'econozel_get_edition_tax_labels', array(
		'name'          => __( 'Econozel Editions',  'econozel' ),
		'menu_name'     => __( 'Editions',           'econozel' ),
		'singular_name' => __( 'Econozel Edition',   'econozel' ),
		'search_items'  => __( 'Search Editions',    'econozel' ),
		'popular_items' => null, // Disable tagcloud
		'all_items'     => __( 'All Editions',       'econozel' ),
		'no_items'      => __( 'No Edition',         'econozel' ),
		'edit_item'     => __( 'Edit Edition',       'econozel' ),
		'update_item'   => __( 'Update Edition',     'econozel' ),
		'add_new_item'  => __( 'Add New Edition',    'econozel' ),
		'new_item_name' => __( 'New Edition Name',   'econozel' ),
		'view_item'     => __( 'View Edition',       'econozel' )
	) );
}

/**
 * Add Edition details for taxonomy meta registration
 *
 * @since 1.0.0
 *
 * @param array $meta Meta fields to register
 * @return array Meta fields
 */
function econozel_add_edition_tax_meta( $meta ) {

	// Append Edition meta
	$meta[ econozel_get_edition_tax_id() ] = array(

		// Issue number
		'issue' => array(
			'label'           => esc_html__( 'Issue', 'econozel' ),
			'description'     => esc_html__( 'The nth number of this Edition within the Volume.', 'econozel' ),
			'type'            => 'number',
			'attrs'           => array(
				'min' => 0
			),
			'sanitize_cb'     => 'intval',
			'admin_column_cb' => true,
			'inline_edit'     => true
		),

		// Main file
		'file' => array(
			'label'           => esc_html__( 'File', 'econozel' ),
			'description'     => esc_html__( 'The published Edition as a PDF file.', 'econozel' ),
			'type'            => 'upload',
			'mime_type'       => 'pdf',
			'sanitize_cb'     => 'intval',
			'admin_column_cb' => true
		),

		// Cover image
		'cover_image' => array(
			'label'           => esc_html__( 'Cover Image', 'econozel' ),
			'description'     => esc_html__( 'The cover image of the published Edition.', 'econozel' ),
			'type'            => 'upload',
			'mime_type'       => 'image',
			'sanitize_cb'     => 'intval'
		)
	);

	return $meta;
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
	return apply_filters( 'econozel_get_root_slug', econozel_get_article_post_type() );
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
	return apply_filters( 'econozel_get_article_slug', trailingslashit( econozel_get_root_slug() ) . _x( 'articles', 'Article rewrite slug', 'econozel' ) );
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
	return apply_filters( 'econozel_get_volume_slug', trailingslashit( econozel_get_root_slug() ) . _x( 'volumes', 'Volume rewrite slug', 'econozel' ) );
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
 * Return the Article rewrite ID
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_article_rewrite_id'
 * @return string Article rewrite ID
 */
function econozel_get_article_rewrite_id() {
	return apply_filters( 'econozel_get_article_rewrite_id', econozel_get_article_post_type() . '_article' );
}

/** Options *******************************************************************/

/**
 * Return whether to prepend the Volume title with 'Volume'
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_prepend_volume_title'
 * @return bool Prepend Volume title
 */
function econozel_prepend_volume_title() {
	return apply_filters( 'econozel_prepend_volume_title', get_option( 'econozel_prepend_volume_title', true ) );
}
