<?php

/**
 * Econozel Actions
 * 
 * @package Econozel
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Sub-actions ***************************************************************/

add_action( 'widgets_init',                'econozel_widgets_init'      );
add_action( 'after_setup_theme',           'econozel_after_setup_theme' );

/** Taxonomy ******************************************************************/

add_filter( 'econozel_get_taxonomy_meta',  'econozel_add_edition_tax_meta',      5    );
add_action( 'registered_taxonomy',         'econozel_register_taxonomy_meta',   10, 3 );
add_filter( 'get_terms_defaults',          'econozel_query_terms_default_args', 10, 2 ); // Since WP 4.4
add_filter( 'terms_clauses',               'econozel_query_terms_clauses',      10, 3 );
add_filter( 'list_cats',                   'econozel_list_cats',                10, 2 );
add_filter( 'wp_dropdown_cats',            'econozel_dropdown_cats',            10, 2 );
add_filter( 'term_link',                   'econozel_term_link',                10, 3 );

/** Query *********************************************************************/

add_action( 'parse_query',                 'econozel_parse_query',               2    ); // Early for overrides
add_action( 'parse_query',                 'econozel_parse_query_vars',         10    );
add_action( 'posts_clauses',               'econozel_posts_clauses',            10, 2 );
add_filter( 'posts_request',               'econozel_filter_wp_query',          10, 2 );
add_filter( 'posts_pre_query',             'econozel_bypass_wp_query',          10, 2 ); // Since WP 4.6

/** Template ******************************************************************/

add_action( 'econozel_after_setup_theme',  'econozel_load_theme_functions'        );
add_filter( 'document_title_parts',        'econozel_document_title_parts'        ); // Since WP 4.4
add_filter( 'body_class',                  'econozel_body_class'                  );
add_filter( 'get_the_archive_title',       'econozel_get_the_archive_title'       );
add_filter( 'get_the_archive_description', 'econozel_get_the_archive_description' );
add_filter( 'post_class',                  'econozel_filter_object_class'         );
add_filter( 'econozel_term_class',         'econozel_filter_object_class'         );
add_filter( 'previous_post_link',          'econozel_adjacent_post_link',   10, 5 );
add_filter( 'next_post_link',              'econozel_adjacent_post_link',   10, 5 );

// Theme Compat
add_filter( 'template_include', 'econozel_template_include_theme_supports', 10 );
add_filter( 'template_include', 'econozel_template_include_theme_compat',   12 );

/** Widgets *******************************************************************/

add_action( 'econozel_widgets_init', array( 'Econozel_Articles_Widget', 'register_widget' ) );
add_action( 'econozel_widgets_init', array( 'Econozel_Comments_Widget', 'register_widget' ) );

/** Admin *********************************************************************/

if ( is_admin() ) {
	add_action( 'init', 'econozel_admin' );

	// Activation/Deactivation
	add_action( 'econozel_activation',   'econozel_delete_rewrite_rules' );
	add_action( 'econozel_deactivation', 'econozel_delete_rewrite_rules' );
}

/** Admin *********************************************************************/

/**
 * Run dedicated activation hook for this plugin
 *
 * @since 1.0.0
 *
 * @uses do_action() Calls 'econozel_activation'
 */
function econozel_activation() {
	do_action( 'econozel_activation' );
}

/**
 * Run dedicated deactivation hook for this plugin
 *
 * @since 1.0.0
 *
 * @uses do_action() Calls 'econozel_deactivation'
 */
function econozel_deactivation() {
	do_action( 'econozel_deactivation' );
}

/**
 * Run dedicated widgets hook for this plugin
 *
 * @since 1.0.0
 *
 * @uses do_action() Calls 'econozel_widgets_init'
 */
function econozel_widgets_init() {
	do_action( 'econozel_widgets_init' );
}

/**
 * Run dedicated hook after theme setup for this plugin
 *
 * @since 1.0.0
 *
 * @uses do_action() Calls 'econozel_after_setup_theme'
 */
function econozel_after_setup_theme() {
	do_action( 'econozel_after_setup_theme' );
}
