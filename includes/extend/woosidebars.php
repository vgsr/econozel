<?php

/**
 * Econozel Extension for Woosidebars
 *
 * @package Econozel
 * @subpackage Woosidebars
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Econozel_Woosidebars' ) ) :
/**
 * The Econozel Woosidebars class
 *
 * @since 1.0.0
 */
class Econozel_Woosidebars {

	/**
	 * Setup this class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Define default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {
		add_filter( 'woo_conditions_headings',  array( $this, 'conditions_headings'  ) );
		add_filter( 'woo_conditions_reference', array( $this, 'conditions_reference' ) );
		add_filter( 'woo_conditions',           array( $this, 'conditions'           ) );
	}

	/** Public methods **************************************************/

	/**
	 * Modify the headings of the list of available conditions
	 *
	 * @since 1.0.0
	 *
	 * @param array $headings Condition headings
	 * @return array Condition headings
	 */
	public function conditions_headings( $headings ) {

		// Plugin heading
		$headings['econozel'] = esc_html_x( 'Econozel', 'Woosidebars conditions heading', 'econozel' );

		return $headings;
	}

	/**
	 * Modify the list of available conditions to select from
	 *
	 * @since 1.0.0
	 *
	 * @param array $conditions Available conditions
	 * @return array Available conditions
	 */
	public function conditions_reference( $conditions ) {

		// Remove default assets
		unset(

			// Volumes
			$conditions['taxonomies']['archive-' . econozel_get_volume_tax_id() ],
			$conditions['taxonomy-' . econozel_get_volume_tax_id() ],

			// Editions
			$conditions['taxonomies']['archive-' . econozel_get_edition_tax_id() ],
			$conditions['taxonomy-' . econozel_get_edition_tax_id() ],

			// Articles
			$conditions['post_types']['post-type-archive-' . econozel_get_article_post_type() ],
			$conditions['post_types']['post-type-' . econozel_get_article_post_type() ]
		);

		// Define plugin conditions
		$conditions['econozel'] = (array) apply_filters( 'econozel_woosidebars_conditions', array(

			// Any Econozel page
			'econozel' => array(
				'label'       => esc_html__( 'Any Econozel page', 'econozel' ),
				'description' => esc_html__( 'Any page that belongs to the Econozel domain', 'econozel' )
			),

			// Econozel Home
			'econozel-root' => array(
				'label'       => esc_html_x( 'Econozel Home', 'root page title', 'econozel' ),
				'description' => esc_html__( 'The Econozel home page', 'econozel' )
			),

			// Volume archives
			'econozel-volume-archive' => array(
				'label'       => econozel_taxonomy_title( econozel_get_volume_tax_id() ),
				'description' => esc_html__( 'The archive pages of Econozel Volumes', 'econozel' )
			), 

			// Single Volume
			'econozel-single-volume' => array(
				'label'       => esc_html__( 'Each Individual Volume', 'econozel' ),
				'description' => esc_html__( 'Entries in the Econozel Volume taxonomy', 'econozel' )
			), 

			// Edition archives
			'econozel-edition-archive' => array(
				'label'       => econozel_taxonomy_title( econozel_get_edition_tax_id() ),
				'description' => esc_html__( 'The archive pages of Econozel Editions', 'econozel' )
			), 

			// Single Edition
			'econozel-single-edition' => array(
				'label'       => esc_html__( 'Each Individual Edition', 'econozel' ),
				'description' => esc_html__( 'Entries in the Econozel Edition taxonomy', 'econozel' )
			), 

			// Article archives
			'econozel-article-archive' => array(
				'label'       => econozel_post_type_title( econozel_get_article_post_type() ),
				'description' => esc_html__( 'The archive pages of Econozel Articles', 'econozel' )
			), 

			// Single Article
			'econozel-single-article' => array(
				'label'       => esc_html__( 'Each Individual Article', 'econozel' ),
				'description' => esc_html__( 'Entries in the Econozel Article post type', 'econozel' )
			), 
		) );

		return $conditions;
	}

	/**
	 * Modify the conditions that apply for the current page
	 *
	 * @since 1.0.0
	 *
	 * @param array $conditions Conditions that apply
	 * @return array Conditions that apply
	 */
	public function conditions( $conditions ) {

		// Plugin pages
		if ( is_econozel() ) {
			$conditions[] = 'econozel';

			if ( econozel_is_root() ) {
				$conditions[] = 'econozel-root';

			} elseif ( econozel_is_volume_archive() ) {
				$conditions[] = 'econozel-volume-archive';

			} elseif ( econozel_is_volume() ) {
				$conditions[] = 'econozel-single-volume';

			} elseif ( econozel_is_edition_archive() ) {
				$conditions[] = 'econozel-edition-archive';

			} elseif ( econozel_is_edition() ) {
				$conditions[] = 'econozel-single-edition';

			} elseif ( econozel_is_article_archive() ) {
				$conditions[] = 'econozel-article-archive';

			} elseif ( econozel_is_article() ) {
				$conditions[] = 'econozel-single-article';
			}
		}

		return $conditions;
	}
}

/**
 * Setup the extension logic for Woosidebars
 *
 * @since 1.0.0
 *
 * @uses Econozel_Woosidebars
 */
function econozel_woosidebars() {
	econozel()->extend->woosidebars = new Econozel_Woosidebars;
}

endif; // class_exists
