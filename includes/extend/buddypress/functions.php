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
 * @return bool Are BP summaries enabled?
 */
function econozel_bp_enable_summary() {
	return (bool) apply_filters( 'econozel_bp_enable_summary', false );
}
