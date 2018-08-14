<?php

/**
 * Econozel BuddyPress Functions
 *
 * @package Econozel
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Options ******************************************************************/

/**
 * Return whether to use BuddyPress to create Article summaries
 *
 * @since 1.0.0
 *
 * @param bool $default Optional. Defaults to False.
 * @return bool Are BP summaries enabled?
 */
function econozel_bp_enable_summary( $default = false ) {
	return (bool) apply_filters( 'econozel_bp_enable_summary', get_option( '_econozel_bp_enable_summary', $default ) );
}
