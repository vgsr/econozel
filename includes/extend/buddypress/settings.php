<?php

/**
 * Econozel BuddyPress Settings Functions
 *
 * @package Econozel
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Register additional admin settings fields
 *
 * @since 1.0.0
 *
 * @param array $fields Settings fields
 * @return array Settings fields
 */
function econozel_bp_admin_add_settings_fields( $fields ) {

	// Activity component
	if ( bp_is_active( 'activity' ) ) {

		// Enable BP summaries
		$fields['econozel_settings_general']['_econozel_bp_enable_summary'] = array(
			'title'             => esc_html__( 'Article summaries', 'econozel' ),
			'callback'          => 'econozel_bp_admin_setting_callback_enable_summary',
			'sanitize_callback' => 'intval',
			'args'              => array()
		);
	}

	return $fields;
}

/**
 * Display the content of the Article summaries settings field
 *
 * @since 1.0.0
 */
function econozel_bp_admin_setting_callback_enable_summary() { ?>

	<input name="_econozel_bp_enable_summary" id="_econozel_bp_enable_summary" type="checkbox" value="1" <?php checked( econozel_bp_enable_summary() ); ?>>
	<label for="_econozel_bp_enable_summary"><?php esc_html_e( 'Display more fancy article summaries when they contain media', 'econozel' ); ?></label>

	<?php
}
