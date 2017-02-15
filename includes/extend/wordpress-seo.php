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
		add_filter( "manage_edit-{$volume}_columns",   array( $this, 'admin_remove_columns'   ), 99    );
		add_filter( "manage_edit-{$edition}_columns",  array( $this, 'admin_remove_columns'   ), 99    );
		add_filter( "manage_{$article}_posts_columns", array( $this, 'admin_remove_columns'   ), 99    );
		add_filter( 'manage_edit-post_tag_columns',    array( $this, 'admin_remove_columns'   ), 99    );
		add_action( 'option_wpseo_titles',             array( $this, 'admin_remove_metaboxes' ), 10, 2 );
		add_action( 'site_option_wpseo_titles',        array( $this, 'admin_remove_metaboxes' ), 10, 2 );

		// Breadcrumbs
		add_filter( 'wpseo_breadcrumb_links', array( $this, 'breadcrumb_links' ) );
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

		// Get the current screen
		$screen = get_current_screen();

		// Bail when these are not the post tags in Econozel context
		if ( 'post_tag' === $screen->taxonomy && econozel_get_article_post_type() !== $screen->post_type )
			return $columns;

		// Walk registered columns
		foreach ( $columns as $column => $label ) {

			// Remove WP SEO column
			if ( false !== strpos( $column, 'wpseo' ) ) {
				unset( $columns[ $column ] );
			}
		}

		return $columns;
	}

	/**
	 * Modify the wpseo_titles option value
	 *
	 * @since 1.0.0
	 *
	 * @param array $value Option value
	 * @param string $option Option name
	 * @return array Option value
	 */
	public function admin_remove_metaboxes( $value, $option ) {

		// Plugin objects
		$volume  = econozel_get_volume_tax_id();
		$edition = econozel_get_edition_tax_id();
		$article = econozel_get_article_post_type();

		// Override metabox setting
		$value["hideeditbox-tax-{$volume}"]  = true;
		$value["hideeditbox-tax-{$edition}"] = true;
		$value["hideeditbox-{$article}"]     = true;

		return $value;
	}

	/**
	 * Modify the collection of page crumb links
	 *
	 * @since 1.0.0
	 *
	 * @param array $crumbs Breadcrumb links
	 * @return array Breadcrumb links
	 */
	public function breadcrumb_links( $crumbs ) {

		// Econozel page. Fully overwrite crumb paths
		if ( is_econozel() ) {

			// Define plugin crumb presets
			$_crumbs = array(
				'root' => array(
					'text'       => esc_html_x( 'Econozel', 'root page title', 'econozel' ),
					'url'        => econozel_get_root_url(),
					'allow_html' => false,
				),
				'volumes' => array(
					'text'       => esc_html_x( 'Volumes', 'volume archives breadcrumb title', 'econozel' ),
					'url'        => econozel_get_volume_archive_url(),
					'allow_html' => false,
				),
				'articles' => array(
					'text'       => esc_html_x( 'Articles', 'article archives breadcrumb title', 'econozel' ),
					'url'        => get_post_type_archive_link( econozel_get_article_post_type() ),
					'allow_html' => false,
				)
			);

			// Always append Econozel root just after home
			array_splice( $crumbs, 1, 0, array( $_crumbs['root'] ) );

			// Define local variable(s)
			$last = count( $crumbs ) - 1;

			// Volume archives
			if ( econozel_is_volume_archive() ) {

				// Append Volume crumb, 'cause there is none
				$crumbs[] = $_crumbs['volumes'];

			// Single Volume
			} elseif ( econozel_is_volume() ) {

				// Prepend Volume archives
				array_splice( $crumbs, $last, 0, array( $_crumbs['volumes'] ) );

				// Set Volume crumb. Overwrites default crumb on queried term.
				$crumbs[ $last + 1 ] = array(
					'text'       => econozel_get_volume_title(),
					'allow_html' => false
				);

			// Single Edition
			} elseif ( econozel_is_edition() && $volume = econozel_get_edition_volume() ) {

				// Prepend Volumes archives > {Volume}
				array_splice( $crumbs, $last, 0, array( $_crumbs['volumes'], array(
					'text'       => econozel_get_volume_title( $volume, false ),
					'url'        => econozel_get_volume_url( $volume ),
					'allow_html' => false
				) ) );

				// Set Edition crumb. Overwrites default crumb on queried term.
				$crumbs[ $last + 2 ] = array(
					'text'       => econozel_get_edition_title(),
					'allow_html' => false
				);

			// Article archives
			} elseif ( econozel_is_article_archive() ) {

				// Set Article archives crumb. Overwrited default archive crumb.
				$crumbs[ $last ] = $_crumbs['articles'];

			// Single Article
			} elseif ( econozel_is_article() ) {

				// Published in Edition
				if ( $edition = econozel_get_article_edition() ) {

					// Get more ancestry details
					$volume  = econozel_get_article_volume();
					$numeric = econozel_is_edition_issue_numeric( $edition );

					// Prepend Volume archives
					array_splice( $crumbs, $last, 0, array( $_crumbs['volumes'],

						// Volume
						array(
							'text'       => econozel_get_volume_title( $volume, false ),
							'url'        => econozel_get_volume_url( $volume ),
							'allow_html' => false
						),

						// Edition
						array(
							'text'       => $numeric ? econozel_get_edition_issue( $edition ) : econozel_get_edition_title( $edition ),
							'url'        => econozel_get_edition_url( $edition ),
							'allow_html' => false
						),
					) );

					// Remove Article archives
					unset( $crumbs[ $last - 1 ] );

				// Openly published
				} else {

					// Set Volume archives crumb. Overwrites default archive crumb.
					$crumbs[ $last - 1 ] = $_crumbs['articles'];
				}
			}

			$crumbs = array_values( $crumbs );
		}

		return $crumbs;
	}
}

/**
 * Setup the extension logic for WP SEO
 *
 * @since 1.0.0
 *
 * @uses Econozel_WPSEO
 */
function econozel_wpseo() {
	econozel()->extend->wpseo = new Econozel_WPSEO;
}

endif; // class_exists
