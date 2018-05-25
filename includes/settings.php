<?php

/**
 * Econozel Settings Functions
 *
 * @package Econozel
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return the plugin's settings sections
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_admin_get_settings_sections'
 * @return array Settings sections
 */
function econozel_admin_get_settings_sections() {
	return (array) apply_filters( 'econozel_admin_get_settings_sections', array(

		// General settings
		'econozel_settings_general' => array(
			'title'    => esc_html__( 'General Settings', 'econozel' ),
			'callback' => 'econozel_admin_setting_callback_general_section',
			'page'     => 'econozel',
		),

		// Per page settings
		'econozel_settings_per_page' => array(
			'title'    => esc_html__( 'Per Page Settings', 'econozel' ),
			'callback' => 'econozel_admin_setting_callback_per_page_section',
			'page'     => 'econozel',
		),

		// Slug settings
		'econozel_settings_slugs' => array(
			'title'    => esc_html__( 'Econozel Slugs', 'econozel' ),
			'callback' => 'econozel_admin_setting_callback_slugs_section',
			'page'     => 'econozel',
		)
	) );
}

/**
 * Return the plugin's settings fields
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_admin_get_settings_fields'
 * @return array Settings fields
 */
function econozel_admin_get_settings_fields() {
	return (array) apply_filters( 'econozel_admin_get_settings_fields', array(

		// General settings
		'econozel_settings_general' => array(

			// Admin access
			'_econozel_toggle_admin_access' => array(
				'title'             => esc_html__( 'Admin Access', 'econozel' ),
				'callback'          => 'econozel_admin_setting_callback_toggle_admin_access',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

			// Edition issue whitelist
			'_econozel_edition_issue_whitelist' => array(
				'title'             => esc_html__( 'Edition Issues', 'econozel' ),
				'callback'          => 'econozel_admin_setting_callback_issue_whitelist',
				'sanitize_callback' => 'econozel_sanitize_issue_whitelist',
				'args'              => array()
			),

			// Volume title setting
			'_econozel_prepend_volume_title' => array(
				'title'             => esc_html__( 'Volume Title', 'econozel' ),
				'callback'          => 'econozel_admin_setting_callback_volume_title',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),
		),

		// Per page settings
		'econozel_settings_per_page' => array(

			// Volumes per page setting
			'_econozel_volumes_per_page' => array(
				'title'             => esc_html__( 'Volumes', 'econozel' ),
				'callback'          => 'econozel_admin_setting_callback_volumes_per_page',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

			// Editions per page setting
			'_econozel_editions_per_page' => array(
				'title'             => esc_html__( 'Editions', 'econozel' ),
				'callback'          => 'econozel_admin_setting_callback_editions_per_page',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),
		),

		// Slug settings
		'econozel_settings_slugs' => array(

			// Root slug setting
			'_econozel_root_slug' => array(
				'title'             => esc_html__( 'Econozel Root', 'econozel' ),
				'callback'          => 'econozel_admin_setting_callback_root_slug',
				'sanitize_callback' => 'econozel_sanitize_slug',
				'args'              => array()
			),

			// Volume slug setting
			'_econozel_volume_slug' => array(
				'title'             => esc_html__( 'Volume', 'econozel' ),
				'callback'          => 'econozel_admin_setting_callback_volume_slug',
				'sanitize_callback' => 'econozel_sanitize_slug',
				'args'              => array()
			),

			// Edition slug setting
			'_econozel_edition_slug' => array(
				'title'             => esc_html__( 'Edition', 'econozel' ),
				'callback'          => 'econozel_admin_setting_callback_edition_slug',
				'sanitize_callback' => 'econozel_sanitize_slug',
				'args'              => array()
			),

			// Article slug setting
			'_econozel_article_slug' => array(
				'title'             => esc_html__( 'Article', 'econozel' ),
				'callback'          => 'econozel_admin_setting_callback_article_slug',
				'sanitize_callback' => 'econozel_sanitize_slug',
				'args'              => array()
			),
		),
	) );
}

/**
 * Get settings fields by section
 *
 * @since 1.0.0
 *
 * @param string $section_id
 * @return array|bool Array of fields or False when section is invalid
 */
function econozel_admin_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty
	if ( empty( $section_id ) )
		return false;

	$fields = econozel_admin_get_settings_fields();
	$retval = isset( $fields[$section_id] ) ? $fields[$section_id] : false;

	return $retval;
}

/** General Section ***********************************************************/

/**
 * Display the description of the General settings section
 *
 * @since 1.0.0
 */
function econozel_admin_setting_callback_general_section() { ?>

	<p><?php esc_html_e( 'Define the available edition issues and other generic settings', 'econozel' ); ?></p>

	<?php
}

/**
 * Display the content of the Toggle Access settings field
 *
 * @since 1.0.0
 */
function econozel_admin_setting_callback_toggle_admin_access() { ?>

	<input name="_econozel_toggle_admin_access" id="_econozel_toggle_admin_access" type="checkbox" value="1" <?php checked( econozel_toggle_admin_access() ); ?>>
	<label for="_econozel_toggle_admin_access"><?php esc_html_e( 'Restrict Econozel admin access to Econozel Editors only', 'econozel' ); ?></label>

	<?php
}

/**
 * Display the content of the Edition Issues settings field
 *
 * @since 1.0.0
 */
function econozel_admin_setting_callback_issue_whitelist() { ?>

	<input name="_econozel_edition_issue_whitelist" id="_econozel_edition_issue_whitelist" type="text" class="regular-text" value="<?php econozel_form_option( '_econozel_edition_issue_whitelist', '1,2,3,4,5,6,7,8,9,10,11,12' ); ?>">
	<label for="_econozel_edition_issue_whitelist"><?php esc_html_e( 'Comma separated list of available issues', 'econozel' ); ?></label>

	<?php
}

/**
 * Display the content of the Prepend Volume Title settings field
 *
 * @since 1.0.0
 */
function econozel_admin_setting_callback_volume_title() { ?>

	<input name="_econozel_prepend_volume_title" id="_econozel_prepend_volume_title" type="checkbox" value="1" <?php checked( econozel_prepend_volume_title() ); ?>>
	<label for="_econozel_prepend_volume_title"><?php esc_html_e( "Prefix all volume titles with the term 'Volume' (Useful when you have only numeric volumes)", 'econozel' ); ?></label>

	<?php
}

/** Per Page Section **********************************************************/

/**
 * Display the description of the Per Page settings section
 *
 * @since 1.0.0
 */
function econozel_admin_setting_callback_per_page_section() { ?>

	<p><?php esc_html_e( 'How many volumes and editions to show per page', 'econozel' ); ?></p>

	<?php
}

/**
 * Display the content of the Volumes Per Page settings field
 *
 * @since 1.0.0
 */
function econozel_admin_setting_callback_volumes_per_page() { ?>

	<input name="_econozel_volumes_per_page" id="_econozel_volumes_per_page" type="number" min="1" step="1" class="small-text" value="<?php econozel_form_option( '_econozel_volumes_per_page', '5' ); ?>">
	<label for="_econozel_volumes_per_page"><?php esc_html_e( 'per page', 'econozel' ); ?></label>

	<?php
}

/**
 * Display the content of the Editions Per Page settings field
 *
 * @since 1.0.0
 */
function econozel_admin_setting_callback_editions_per_page() { ?>

	<input name="_econozel_editions_per_page" id="_econozel_editions_per_page" type="number" min="1" step="1" class="small-text" value="<?php econozel_form_option( '_econozel_editions_per_page', '5' ); ?>">
	<label for="_econozel_editions_per_page"><?php esc_html_e( 'per page', 'econozel' ); ?> (<?php esc_html_e( 'Note: when showing volume editions this setting is ignored', 'econozel' ); ?>)</label>

	<?php
}

/** Slugs Section *************************************************************/

/**
 * Display the description of the Slugs settings section
 *
 * @since 1.0.0
 */
function econozel_admin_setting_callback_slugs_section() {

	// Flush rewrite rules when this section is saved
	if ( isset( $_GET['settings-updated'] ) && isset( $_GET['page'] ) )
		flush_rewrite_rules(); ?>

	<p><?php esc_html_e( 'Customize your econozel slugs', 'econozel' ); ?></p>

	<?php
}

/**
 * Display the content of the Root Slug settings field
 *
 * @since 1.0.0
 */
function econozel_admin_setting_callback_root_slug() { ?>

	<input name="_econozel_root_slug" id="_econozel_root_slug" type="text" class="regular-text code" value="<?php econozel_form_option( '_econozel_root_slug', 'econozel', true ); ?>">

	<?php
}

/**
 * Display the content of the Volume Slug settings field
 *
 * @since 1.0.0
 */
function econozel_admin_setting_callback_volume_slug() { ?>

	<input name="_econozel_volume_slug" id="_econozel_volume_slug" type="text" class="regular-text code" value="<?php econozel_form_option( '_econozel_volume_slug', 'volumes', true ); ?>">

	<?php
}

/**
 * Display the content of the Edition Slug settings field
 *
 * @since 1.0.0
 */
function econozel_admin_setting_callback_edition_slug() { ?>

	<input name="_econozel_edition_slug" id="_econozel_edition_slug" type="text" class="regular-text code" value="<?php econozel_form_option( '_econozel_edition_slug', 'editions', true ); ?>">

	<?php
}

/**
 * Display the content of the Article Slug settings field
 *
 * @since 1.0.0
 */
function econozel_admin_setting_callback_article_slug() { ?>

	<input name="_econozel_article_slug" id="_econozel_article_slug" type="text" class="regular-text code" value="<?php econozel_form_option( '_econozel_article_slug', 'articles', true ); ?>">

	<?php
}

/** Helpers *******************************************************************/

/**
 * Output settings API option
 *
 * @since 1.0.0
 *
 * @param string $option Option name
 * @param string $default Default value
 * @param bool $slug Whether the option is a slug. Defaults to false.
 */
function econozel_form_option( $option, $default = '', $slug = false ) {
	echo econozel_get_form_option( $option, $default, $slug );
}
	/**
	 * Return settings API option
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'econozel_get_form_option'
	 *
	 * @param string $option Option name
	 * @param string $default Default value
	 * @param bool $slug Whether the option is a slug. Defaults to false.
	 * @return mixed Option value
	 */
	function econozel_get_form_option( $option, $default = '', $slug = false ) {

		// Get the option and sanitize it
		$value = get_option( $option, $default );

		// Slug?
		if ( true === $slug ) {
			$value = esc_attr( apply_filters( 'editable_slug', $value ) );

		// Not a slug
		} else {
			$value = esc_attr( $value );
		}

		// Fallback to default
		if ( empty( $value ) )
			$value = $default;

		// Allow plugins to further filter the output
		return apply_filters( 'econozel_get_form_option', $value, $option );
	}

/** Sanitization **************************************************************/

/**
 * Sanitize the Edition issue whitelist setting
 *
 * @since 1.0.0
 *
 * @param string $list List of issues to sanitize
 * @return string Sanitized list
 */
function econozel_sanitize_issue_whitelist( $list = '' ) {

	// Don't allow multiple commas in a row
	$value = preg_replace( '#,+#', ',', str_replace( '#', '', $list ) );

	// Strip out unsafe or unusable chars
	$value = sanitize_text_field( $value );

	// Trim off first and last commas
	$value = ltrim( $value, ',' );
	$value = rtrim( $value, ',' );

	return $value;
}

/**
 * Sanitize a permalihk slug setting
 *
 * @since 1.0.0
 *
 * @param string $slug Slug to sanitize
 * @return string Sanitized slug
 */
function econozel_sanitize_slug( $slug = '' ) {

	// Don't allow multiple slashes in a row
	$value = preg_replace( '#/+#', '/', str_replace( '#', '', $slug ) );

	// Strip out unsafe or unusable chars
	$value = esc_url_raw( $value );

	// esc_url_raw() adds a scheme via esc_url(), so let's remove it
	$value = str_replace( 'http://', '', $value );

	// Trim off first and last slashes
	$value = ltrim( $value, '/' );
	$value = rtrim( $value, '/' );

	return $value;
}
