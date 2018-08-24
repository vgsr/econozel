<?php

/**
 * Root Page
 *
 * @package Econozel
 * @subpackage Theme
 */

// Econozel page description
the_archive_description( '<div class="archive-description">', '</div>' );

// Define widget args
$widget_args = array(
	'before_widget' => '<div class="%s">',
	'after_widget'  => "</div>",
	'before_title'  => '<h3 class="widgettitle">',
	'after_title'   => '</h3>',
);

// Latest Articles
the_widget( 'Econozel_Articles_Widget', array(
	'title'            => esc_html__( 'Latest Articles', 'econozel' ),
	'econozel_archive' => false,
	'show_date'        => true,
), $widget_args );

// Latest Archived
the_widget( 'Econozel_Articles_Widget', array(
	'title'            => esc_html__( 'Latest Archived', 'econozel' ),
	'econozel_archive' => true,
	'show_date'        => true,
), $widget_args );

// Active Articles
the_widget( 'Econozel_Articles_Widget', array(
	'title'            => esc_html__( 'Active Articles', 'econozel' ),
	'none_found'       => esc_html__( 'It seems there are no articles having been commented on lately.', 'econozel' ),
	'comment_activity' => 30,
	'show_date'        => true,
), $widget_args );

// Recent Comments
the_widget( 'Econozel_Comments_Widget', array(
	'title'            => esc_html__( 'Recent Comments', 'econozel' ),
), $widget_args );
