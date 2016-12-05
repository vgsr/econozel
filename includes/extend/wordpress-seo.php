<?php

/**
 * Econozel WP SEO Functions
 *
 * @package Econozel
 * @subpackage WP SEO
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Econozel_WPSEO' ) ) :
/**
 * The Econozel WP SEO class
 *
 * @since 1.0.0
 */
class Econozel_WPSEO {

	/**
	 * Setup this class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Bail when WP SEO is not active. Checking the constant,
		// because the plugin has no init sub-action of its own.
		if ( ! defined( 'WPSEO_VERSION' ) )
			return;

		$this->setup_actions();
	}

	/**
	 * Define default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {

		// Plugin objects
		$volume  = econozel_get_volume_tax_id();
		$edition = econozel_get_edition_tax_id();
		$article = econozel_get_article_post_type();

		// Admin
		add_filter( "manage_{$article}_posts_columns", array( $this, 'admin_remove_columns' ), 99 );
		add_filter( "manage_edit-{$edition}_columns",  array( $this, 'admin_remove_columns' ), 99 );
		add_filter( "manage_edit-{$volume}_columns",   array( $this, 'admin_remove_columns' ), 99 );
	}

	/** Public methods **************************************************/

	/**
	 * Modify the admin list table columns
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns Admin columns
	 * @return array Admin columns
	 */
	public function admin_remove_columns( $columns ) {

		// Walk registered columns
		foreach ( $columns as $column => $label ) {

			// Remove WP SEO column
			if ( false !== strpos( $column, 'wpseo' ) ) {
				unset( $columns[ $column ] );
			}
		}

		return $columns;
	}
}

/**
 * Setup the extension logic for BuddyPress
 *
 * @since 1.0.0
 *
 * @uses Econozel_WPSEO
 */
function econozel_wpseo() {
	econozel()->extend->wpseo = new Econozel_WPSEO;
}

endif; // class_exists
