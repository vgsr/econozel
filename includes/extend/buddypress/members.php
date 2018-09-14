<?php

/**
 * Econozel BuddyPress Members Functions
 *
 * @package Econozel
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Econozel_BP_Members' ) ) :
/**
 * The Econozel BuddyPress Members class
 *
 * @since 1.0.0
 */
class Econozel_BP_Members {

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
		add_filter( 'econozel_get_article_author_url', array( $this, 'get_article_author_url' ), 10, 3 );
		add_filter( 'get_pagenum_link',                array( $this, 'get_pagenum_link'       )        );
	}

	/** Links ***********************************************************/

	/**
	 * Modify the Article author url
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Article author url
	 * @param int $user_id User ID
	 * @param WP_Post|bool $article Article object or False when not found
	 * @return string Article author url
	 */
	public function get_article_author_url( $url, $user_id, $article ) {
		return bp_core_get_user_domain( $user_id );
	}

	/**
	 * Modify the pagenum url used in posts navigation
	 *
	 * @since 1.0.0
	 *
	 * @uses WP_Rewrite $wp_rewrite
	 *
	 * @param string $url Pagenum url
	 * @return string Pagenum url
	 */
	public function get_pagenum_link( $url ) {
		global $wp_rewrite;

		// BuddyPress member Articles page
		if ( econozel_bp_is_member_articles() ) {
			$pagenum = 1;

			// Get pagenum
			if ( preg_match( "/page\/(\d+)/", $url, $matches ) ) {
				$pagenum = $matches[1];
			}

			// Rebuild the pretty permalink
			$url = trailingslashit( bp_core_get_user_domain( bp_displayed_user_id() ) ) . econozel_bp_get_component() . '/' . econozel_bp_get_article_slug();

			// Add pagination
			if ( $pagenum > 1 ) {
				$url .= '/' . user_trailingslashit( $wp_rewrite->pagination_base . '/' . $pagenum, 'paged' );
			} else {
				$url = user_trailingslashit( $url );
			}
		}

		return $url;
	}
}

endif; // class_exists
