<?php

/**
 * Econozel Theme-Compat Functions
 *
 * Override this logic with your own econozel-functions.php inside your theme.
 *
 * @package Econozel
 * @subpackage Theme
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Econozel_Default' ) ) :
/**
 * Loads default Econozel theme compatibility functionality
 *
 * @since 1.0.0
 */
class Econozel_Default {

	/**
	 * Setup the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 1.0.0
	 */
	public function setup_actions() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' )    );
		add_action( 'the_content',        array( $this, 'the_content'    ), 2 );
	}

	/**
	 * Load the theme styles
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		econozel_enqueue_style( 'econozel', 'css/econozel.css', array(), econozel_get_version(), 'screen' );
	}

	/**
	 * Filter the post content
	 *
	 * @since 1.0.0
	 *
	 * @global bool $more Whether to show all of the post content.
	 *
	 * @param string $content Post content
	 * @return string Post content
	 */
	public function the_content( $content ) {
		global $more;

		// For Articles, display just the description when teasing
		if ( econozel_get_article() && ! $more ) {
			$content = econozel_get_article_description();
		}

		return $content;
	}
}

// Load it up
new Econozel_Default();

endif; // class_exists
