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
		parent::__construct( false, esc_html__( 'Econozel Articles', 'econozel' ), array(
			'description' => esc_html__( 'A list of (recent) articles.', 'econozel' ),
			'classname'   => 'widget_recent_entries econozel-articles',
			'customize_selective_refresh' => true
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

		// Bail when current user has no access
		if ( ! econozel_check_access() )
			return;

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

		// Define widget arguments
		$r = wp_parse_args( $instance, array(

			// Widget details
			'title'             => econozel_post_type_title( econozel_get_article_post_type() ),
			'description'       => false,
			'none_found'        => esc_html__( 'There were no articles found.', 'econozel' ),
			'walker'            => new Econozel_Walker_Article,
			'item_spacing'      => 'preserve',

			// Display
			'show_author'       => false,
			'show_date'         => false,

			// Query args
			'econozel_edition'  => null,
			'econozel_archive'  => null,
			'econozel_featured' => false,
			'posts_per_page'    => 5,
		) );

		// Detect whether to query by an Edition
		if ( $r['econozel_edition'] ) {
			$edition = econozel_get_dropdown_edition( $r['econozel_edition'] );
			$r['econozel_edition'] = is_a( $edition, 'WP_Term' ) ? $edition->term_id : $edition;

			// When querying by Edition, query all its Articles and override the title
			if ( $r['econozel_edition'] ) {
				$r['posts_per_page'] = -1;
				$title = econozel_get_edition_title( $r['econozel_edition'] );

				if ( $title ) {
					$r['title'] = sprintf( esc_html__( 'Articles in %s', 'econozel' ), $title );
				}
			}
		}

		// Query featured articles
		if ( $r['econozel_featured'] ) {
			$r['post_status'] = econozel_get_featured_status_id();

			// Do not limit to Editions or archived or recent articles
			unset( $r['econozel_edition'], $r['econozel_archive'] );
		}

		// Open widget 
		echo $args['before_widget'];
		if ( $r['title'] ) {
			echo $args['before_title'] . $r['title'] . $args['after_title'];
		}

		// Remove unwanted query vars
		unset( $r['title'], $r['econozel_featured'] );

		// Query Articles
		if ( econozel_query_articles( $r ) ) :

			// List description
			if ( $r['description'] ) {
				echo '<p>' . is_string( $r['description'] ) ? $r['description'] : $this->widget_options['description'] . '</p>';
			}

			// Output page list
			printf( '<ul>%s</ul>', walk_page_tree( econozel()->article_query->posts, 0, econozel_is_article( true ) ? get_queried_object_id() : 0, $r ) );

		// Nothing found
		else :

			echo '<p>' . $r['none_found'] . '</p>';

		endif;

		// Close widget
		echo $args['after_widget'];
	}

	/**
	 * Handles updating the settings for the current Recent Articles widget instance.
	 *
	 * @since 1.0.0
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array Sanitized instance data
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['posts_per_page'] = (int) $new_instance['posts_per_page'];
		$instance['show_author'] = (bool) $new_instance['show_author'];
		$instance['show_date'] = (bool) $new_instance['show_date'];

		/**
		 * Accept a term ID for the given Edition, 'related' for the related Edition, 'latest'
		 * for the latest Edition, or either 'false' or a boolean False for no Edition.
		 */
		$instance['econozel_edition'] = is_numeric( $new_instance['econozel_edition'] )
			? (int) $new_instance['econozel_edition']
			: ( in_array( $new_instance['econozel_edition'], array( 'related', 'latest' ) )
				? $new_instance['econozel_edition']
				: wp_validate_boolean( $new_instance['econozel_edition'] ) );

		$instance['econozel_archive'] = -1 != $new_instance['econozel_archive'] ? wp_validate_boolean( $new_instance['econozel_archive'] ) : null;
		$instance['econozel_featured'] = (bool) $new_instance['econozel_featured'];

		return $instance;
	}

	/**
	 * Output widget form elements
	 *
	 * @since 1.0.0
	 *
	 * @param array $instance Current settings
	 */
	public function form( $instance ) {

		// Define local variable(s)
		$title             = isset( $instance['title'] ) ? $instance['title'] : '';
		$posts_per_page    = isset( $instance['posts_per_page'] ) ? $instance['posts_per_page'] : 5;
		$econozel_edition  = isset( $instance['econozel_edition'] ) ? $instance['econozel_edition'] : -1;
		$econozel_archive  = isset( $instance['econozel_archive'] ) ? (bool) $instance['econozel_archive'] : -1;
		$show_author       = isset( $instance['show_author'] ) ? (bool) $instance['show_author'] : false;
		$show_date         = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false;
		$econozel_featured = isset( $instance['econozel_featured'] ) ? (bool) $instance['econozel_featured'] : false;

		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'econozel_edition' ); ?>"><?php esc_html_e( 'Filter articles by edition:', 'econozel' ); ?></label>
			<?php econozel_dropdown_editions( array(
				'id'                  => $this->get_field_id( 'econozel_edition' ),
				'name'                => $this->get_field_name( 'econozel_edition' ),
				'selected'            => $econozel_edition,
				'show_option_none'    => esc_html__( '&mdash; No Selection &mdash;', 'econozel' ),
				'show_option_without' => true,
				'show_option_related' => true,
				'show_option_latest'  => true
			) ); ?>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'econozel_archive' ); ?>"><?php esc_html_e( 'Limit articles to show:', 'econozel' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'econozel_archive' ); ?>" name="<?php echo $this->get_field_name( 'econozel_archive' ); ?>">
				<option value="-1"><?php esc_html_e( '&mdash; All articles &mdash;', 'econozel' ); ?></option>
				<option value="false"<?php selected( false, $econozel_archive ); ?>><?php esc_html_e( 'Only recent articles', 'econozel' ); ?></option>
				<option value="true"<?php selected( true,  $econozel_archive ); ?>><?php esc_html_e( 'Only older articles',  'econozel' ); ?></option>
			</select>
			<span class="description"><?php esc_html_e( 'Only effects unfiltered article lists.', 'econozel' ); ?></span>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:' ); ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>"/>
			<span class="description"><?php esc_html_e( 'For an edition, the title will be overridden.', 'econozel' ); ?></span>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'posts_per_page' ); ?>"><?php esc_html_e( 'Number of articles to show:', 'econozel' ); ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'posts_per_page' ); ?>" name="<?php echo $this->get_field_name( 'posts_per_page' ); ?>" value="<?php echo esc_attr( $posts_per_page ); ?>"/>
			<span class="description"><?php esc_html_e( 'For an edition, all articles are shown.', 'econozel' ); ?></span>
		</p>
		<p>
			<input type="checkbox" class="checkbox"<?php checked( $show_author ); ?> id="<?php echo $this->get_field_id( 'show_author' ); ?>" name="<?php echo $this->get_field_name( 'show_author' ); ?>"/>
			<label for="<?php echo $this->get_field_id( 'show_author' ); ?>"><?php esc_html_e( 'Display article author(s)?', 'econozel' ); ?></label>
		</p>
		<p>
			<input type="checkbox" class="checkbox"<?php checked( $show_date ); ?> id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>"/>
			<label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php esc_html_e( 'Display edition or post date?', 'econozel' ); ?></label>
		</p>
		<p>
			<input type="checkbox" class="checkbox"<?php checked( $econozel_featured ); ?> id="<?php echo $this->get_field_id( 'econozel_featured' ); ?>" name="<?php echo $this->get_field_name( 'econozel_featured' ); ?>"/>
			<label for="<?php echo $this->get_field_id( 'econozel_featured' ); ?>"><?php esc_html_e( 'Display featured articles?', 'econozel' ); ?></label>
		</p>

		<?php
	}
}
endif;
