<?php

/**
 * Econozel Articles Widget
 *
 * @since 1.0.0
 *
 * @package Econozel
 * @subpackage Widgets
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'Econozel_Articles_Widget' ) ) :
/**
 * The Econozel Articles Widget
 *
 * @since 1.0.0
 */
class Econozel_Articles_Widget extends WP_Widget {

	/**
	 * Setup the widget
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct( false, esc_html__( 'Econozel Recent Articles', 'econozel' ), array(
			'description' => esc_html__( 'A list of the most recent articles.', 'econozel' )
		) );
	}

	/**
	 * Register the widget
	 *
	 * @since 1.0.0
	 *
	 * @uses register_widget()
	 */
	public static function register_widget() {
		register_widget( 'Econozel_Articles_Widget' );
	}

	/**
	 * Output the widget content
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Display arguments
	 * @param array $instance Widget instance arguments
	 */
	public function widget( $args, $instance ) {

		// Define widget details
		$instance = wp_parse_args( $instance, array(
			'title'          => str_replace( 'Econozel ', '', $this->name ),
			'description'    => false,
			'none_found'     => esc_html__( 'It seems there have been no articles published yet.', 'econozel' ),

			// Query args
			'econozel_edition' => false,
			'posts_per_page'   => 5,
			'orderby'          => 'date',
			'order'            => 'DESC'
		) );

		// Define widget details
		$title       = $instance['title'];
		$description = $instance['description'];
		$none_found  = $instance['none_found'];

		// Remove query vars
		unset( $instance['title'], $instance['description'], $instance['none_found'] );

		// Open widget 
		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		// Query Articles
		if ( econozel_query_articles( $instance ) ) : ?>

			<?php if ( $description ) : ?>

			<em><?php echo is_string( $description ) ? $description : $this->widget_options['description']; ?></em>

			<?php endif; ?>

			<ul>
				<?php while ( econozel_has_articles() ) : econozel_the_article(); ?>

				<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>

				<?php endwhile; ?>
			</ul>

		<?php else : ?>

			<?php echo $none_found; ?>

		<?php endif;

		// Close widget
		echo $args['after_widget'];
	}
}
endif;
