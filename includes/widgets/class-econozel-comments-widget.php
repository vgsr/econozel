<?php

/**
 * Econozel Comments Widget
 *
 * @since 1.0.0
 *
 * @package Econozel
 * @subpackage Widgets
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'Econozel_Comments_Widget' ) ) :

// Load WP_Widget_Recent_Comments widget class
require_once( ABSPATH . WPINC . '/widgets/class-wp-widget-recent-comments.php' );

/**
 * The Econozel Comments Widget
 *
 * @since 1.0.0
 */
class Econozel_Comments_Widget extends WP_Widget_Recent_Comments {

	/**
	 * Setup the widget
	 *
	 * @see WP_Widget_Recent_Comments::__construct()
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		WP_Widget::__construct( false, esc_html__( 'Econozel Recent Comments', 'econozel' ), array(
			'classname' => 'widget_recent_comments',
			'description' => esc_html__( 'A list of the most recent article comments.', 'econozel' ),
			'customize_selective_refresh' => true,
		) );

		if ( is_active_widget( false, false, $this->id_base ) || is_customize_preview() ) {
			add_action( 'wp_head', array( $this, 'recent_comments_style' ) );
		}
	}

	/**
	 * Register the widget
	 *
	 * @since 1.0.0
	 *
	 * @uses register_widget()
	 */
	public static function register_widget() {

		// Bail when current user has no access
		if ( ! econozel_check_access() )
			return;

		register_widget( 'Econozel_Comments_Widget' );
	}

	/**
	 * Output the widget content
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Display arguments
	 * @param array $instance Widget instance arguments
	 */
	public function widget( $args, $instance ) {

		// Filter comment query args
		add_filter( 'widget_comments_args', array( $this, 'widget_comments_args' ) );

		// Pre-fetch the comments with widget query
		$comments = get_comments( apply_filters( 'widget_comments_args', array(
			'number'      => $number,
			'status'      => 'approve',
			'post_status' => 'publish'
		) ) );

		// Only output parent widget content with comments
		if ( $comments ) {
			parent::widget( $args, $instance );
		}

		// Remove comment query filter
		remove_filter( 'widget_comments_args', array( $this, 'widget_comments_args' ) );
	}

	/**
	 * Modify the widget's comment query arguments
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Query arguments
	 * @return array Query arguments
	 */
	public function widget_comments_args( $args ) {

		// Define default query args
		$args = wp_parse_args( $args, array(
			'post_type' => econozel_get_article_post_type()
		) );

		return $args;
	}
}
endif;
