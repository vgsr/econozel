<?php

/**
 * Econozel Article Page Walker
 *
 * @package Econozel
 * @subpackage Walker
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Walker_Econozel_Article' ) ) :
/**
 * The Econozel Walker Article class
 *
 * Extends the page walker to modify the element's contents.
 *
 * @since 1.0.0
 */
class Walker_Econozel_Article extends Walker_Page {

	/**
	 * Outputs the beginning of the current element in the tree.
	 *
	 * @see Walker_Page::start_el()
	 *
	 * @since 1.0.0
	 *
	 * @param string  $output       Used to append additional content. Passed by reference.
	 * @param WP_Post $article      Article data object.
	 * @param int     $depth        Optional. Depth of page. Used for padding. Default 0.
	 * @param array   $args         Optional. Array of arguments. Default empty array.
	 * @param int     $current_page Optional. Post ID. Default 0.
	 */
	public function start_el( &$output, $article, $depth = 0, $args = array(), $current_page = 0 ) {

		// Run parent's logic to start the element
		parent::start_el( $output, $article, $depth, $args, $current_page );

		// Append post date
		if ( $args['show_date'] ) {

			// Define post date. Show Edition when not listing an Edition's Articles.
			$date = empty( $args['econozel_edition'] ) ? econozel_get_edition_title( $article ) : false;
			if ( ! $date ) {
				$date = mysql2date( get_option( 'date_format' ), $article->post_date );
			}

			$output .= sprintf( '<span class="post-date">%s</span>', $date );
		}
	}
}

endif; // class_exists
