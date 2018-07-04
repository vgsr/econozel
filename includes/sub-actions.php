<?php

/**
 * Econozel Sub-Actions
 *
 * @package Econozel
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

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
 * Run dedicated init hook for this plugin
 *
 * @since 1.0.0
 *
 * @uses do_action() Calls 'econozel_init'
 */
function econozel_init() {
	do_action( 'econozel_init' );
}

/**
 * Run dedicated admin init hook for this plugin
 *
 * @since 1.0.0
 *
 * @uses do_action() Calls 'econozel_admin_init'
 */
function econozel_admin_init() {
	do_action( 'econozel_admin_init' );
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

/**
 * Run dedicated template include filter for this plugin
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_template_include'
 *
 * @param string $template Template name
 * @param string Template name
 */
function econozel_template_include( $template = '' ) {
	return apply_filters( 'econozel_template_include', $template );
}

/**
 * Run dedicated map meta caps filter for this plugin
 *
 * @since 1.0.0
 *
 * @uses do_action() Calls 'econozel_map_meta_caps'
 *
 * @param array $caps Mapped caps
 * @param string $cap Required capability name
 * @param int $user_id User ID
 * @param array $args Additional arguments
 * @return array Mapped caps
 */
function econozel_map_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {
	return apply_filters( 'econozel_map_meta_caps', $caps, $cap, $user_id, $args );
}
