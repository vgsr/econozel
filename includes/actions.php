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

add_action( 'init',                        'econozel_init'                     );
add_action( 'admin_init',                  'econozel_admin_init'               );
add_action( 'widgets_init',                'econozel_widgets_init'             );
add_action( 'after_setup_theme',           'econozel_after_setup_theme'        );
add_filter( 'template_include',            'econozel_template_include'         );
add_filter( 'map_meta_cap',                'econozel_map_meta_caps',     10, 4 );
add_action( 'save_post',                   'econozel_save_article',      10, 1 );

/** Utility *******************************************************************/

add_action( 'econozel_activation',         'econozel_delete_rewrite_rules' );
add_action( 'econozel_deactivation',       'econozel_delete_rewrite_rules' );

/** Taxonomy ******************************************************************/

add_filter( 'econozel_get_taxonomy_meta',  'econozel_add_edition_tax_meta',          5    );
add_action( 'registered_taxonomy',         'econozel_register_taxonomy_meta',       10, 3 );
add_action( 'registered_taxonomy',         'econozel_register_taxonomy_media_meta', 10, 1 );
add_filter( 'register_taxonomy_args',      'econozel_register_taxonomy_args',       10, 3 );
add_filter( 'get_terms_defaults',          'econozel_query_terms_default_args',     10, 2 ); // Since WP 4.4
add_filter( 'terms_clauses',               'econozel_query_terms_clauses',          10, 3 );
add_filter( 'list_cats',                   'econozel_list_cats',                    10, 2 );
add_filter( 'wp_dropdown_cats',            'econozel_dropdown_cats',                10, 2 );
add_filter( 'term_link',                   'econozel_term_link',                    10, 3 );
add_filter( 'updated_term_meta',           'econozel_updated_term_meta',            10, 4 );

/** Query *********************************************************************/

add_action( 'parse_query',                 'econozel_parse_query',               2    ); // Early for overrides
add_action( 'parse_query',                 'econozel_parse_query_vars',         10    );
add_filter( 'posts_clauses',               'econozel_posts_clauses',            10, 2 );
add_filter( 'posts_request',               'econozel_filter_wp_query',          10, 2 );
add_filter( 'posts_pre_query',             'econozel_bypass_wp_query',          10, 2 ); // Since WP 4.6
add_filter( 'wp_count_posts',              'econozel_filter_count_posts',       10, 3 );

/** Post **********************************************************************/

add_filter( 'get_post_metadata',           'econozel_filter_post_thumbnail',    99, 4 );

/** Template ******************************************************************/

add_action( 'econozel_after_setup_theme',  'econozel_load_theme_functions'        );
add_filter( 'document_title_parts',        'econozel_document_title_parts'        ); // Since WP 4.4
add_filter( 'body_class',                  'econozel_body_class'                  );
add_filter( 'get_the_archive_title',       'econozel_get_the_archive_title'       );
add_filter( 'get_the_archive_description', 'econozel_get_the_archive_description' );
add_filter( 'post_class',                  'econozel_filter_item_class'           );
add_filter( 'econozel_term_class',         'econozel_filter_item_class'           );
add_filter( 'previous_post_link',          'econozel_adjacent_post_link',   10, 5 );
add_filter( 'next_post_link',              'econozel_adjacent_post_link',   10, 5 );

// Content filters
add_filter( 'econozel_get_article_description', 'strip_shortcodes', 1 );
add_filter( 'econozel_get_article_description', 'wpautop'             );

// Theme Compat
add_filter( 'econozel_template_include',   'econozel_template_include_theme_supports', 10 );
add_filter( 'econozel_template_include',   'econozel_template_include_theme_compat',   12 );

/** Menus *********************************************************************/

add_filter( 'customize_nav_menu_available_items', 'econozel_customize_nav_menu_available_items', 10, 4 );
add_filter( 'customize_nav_menu_searched_items',  'econozel_customize_nav_menu_searched_items',  10, 2 );
add_filter( 'wp_setup_nav_menu_item',             'econozel_setup_nav_menu_item'                       );
add_filter( 'wp_nav_menu_objects',                'econozel_nav_menu_objects',                   10, 2 );

/** Widgets *******************************************************************/

add_action( 'econozel_widgets_init', array( 'Econozel_Articles_Widget', 'register_widget' ) );
add_action( 'econozel_widgets_init', array( 'Econozel_Comments_Widget', 'register_widget' ) );

/** User **********************************************************************/

add_filter( 'econozel_map_meta_caps', 'econozel_map_article_meta_caps',  10, 4 );
add_filter( 'econozel_map_meta_caps', 'econozel_map_edition_meta_caps',  10, 4 );
add_filter( 'econozel_map_meta_caps', 'econozel_map_volume_meta_caps',   10, 4 );
add_filter( 'econozel_map_meta_caps', 'econozel_map_post_tag_meta_caps', 10, 4 );
add_action( 'delete_user',            'econozel_delete_user',            10, 2 );
add_action( 'deleted_user',           'econozel_deleted_user',           10, 2 );

// Dynamic roles
add_filter( 'plugins_loaded',         'econozel_filter_user_roles_option'      );
add_filter( 'editable_roles',         'econozel_filter_editable_roles'         );
add_action( 'set_user_role',          'econozel_handle_set_user_role',   10, 3 );

/** Admin *********************************************************************/

if ( is_admin() ) {
	add_action( 'econozel_init',       'econozel_admin'              );
	add_action( 'econozel_admin_init', 'econozel_setup_updater', 999 );
}

/** Extend ********************************************************************/

add_action( 'bp_loaded',     'econozel_buddypress'  );
add_action( 'vgsr_loaded',   'econozel_vgsr'        );
add_action( 'econozel_init', 'econozel_woosidebars' );
add_action( 'econozel_init', 'econozel_wpseo'       );
