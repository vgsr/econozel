<?php

/**
 * Root Page
 *
 * @package Econozel
 * @subpackage Theme
 */

// Econozel page description
the_archive_description();

// Define widget args
$widget_args = array(
	'before_widget' => '<div class="%s">',
	'after_widget'  => "</div>",
	'before_title'  => '<h3 class="widgettitle">',
	'after_title'   => '</h3>',
);

// Recent Articles
the_widget( 'Econozel_Articles_Widget', array(
	'title'            => esc_html__( 'Recent Articles', 'econozel' )
), $widget_args );

// Active Articles
the_widget( 'Econozel_Articles_Widget', array(
	'title'            => esc_html__( 'Active Articles', 'econozel' ),
	'none_found'       => esc_html__( 'It seems there have been no articles commented on lately.', 'econozel' ),
	'comment_activity' => 30
), $widget_args );

// Recent Comments
the_widget( 'Econozel_Comments_Widget', array(
	'title'            => esc_html__( 'Recent Comments', 'econozel' )
), $widget_args );
