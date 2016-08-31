<?php

/**
 * Econozel Actions
 * 
 * @package Econozel
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Taxonomy ******************************************************************/

add_filter( 'econozel_get_taxonomy_meta', 'econozel_add_edition_tax_meta',      5    );
add_action( 'registered_taxonomy',        'econozel_register_taxonomy_meta',   10, 3 );
add_filter( 'get_terms_defaults',         'econozel_query_terms_default_args', 10, 2 );
add_filter( 'terms_clauses',              'econozel_query_terms_clauses',      10, 3 );
add_filter( 'list_cats',                  'econozel_list_cats',                10, 2 );

/** Template ******************************************************************/

add_action( 'parse_query',                 'econozel_parse_query',                 2 ); // Early for overrides
add_filter( 'document_title_parts',        'econozel_document_title_parts'           );
add_filter( 'body_class',                  'econozel_body_class'                     );
add_filter( 'get_the_archive_title',       'econozel_get_the_archive_title'          );
add_filter( 'get_the_archive_description', 'econozel_get_the_archive_description'    );

// Theme Compat
add_filter( 'template_include', 'econozel_template_include_theme_supports', 10 );
add_filter( 'template_include', 'econozel_template_include_theme_compat',   12 );

/** Admin *********************************************************************/

if ( is_admin() ) {
	add_action( 'init', 'econozel_admin' );
}
