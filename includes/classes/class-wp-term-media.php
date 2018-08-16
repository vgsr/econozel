<?php

/**
 * Term Media Class
 *
 * @since 0.1.0
 * @author Laurens Offereins <https://github.com/lmoffereins>
 *
 * @package Plugins/Terms/Metadata/Media
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_Term_Media' ) && class_exists( 'WP_Term_Meta_UI' ) ) :
/**
 * Main WP Term Media class
 *
 * @since 0.1.0
 */
final class WP_Term_Media extends WP_Term_Meta_UI {

	/**
	 * @var string Plugin version
	 */
	public $version = '0.1.1';

	/**
	 * @var string Database version
	 */
	public $db_version = 201808150001;

	/**
	 * @var string Metadata key
	 */
	public $meta_key = 'media';

	/**
	 * @var string|array Attachment mime type
	 */
	public $mime_type = 'image';

	/**
	 * @var string Media size to generate
	 */
	public $image_size = '';

	/**
	 * @var string Media size to generate
	 */
	public $ajax_action = '';

	/**
	 * Hook into queries, admin screens, and more!
	 *
	 * @since 0.1.0
	 */
	public function __construct( $file = '', $args = array() ) {

		// Parse the defaults
		$args = wp_parse_args( $args, array(
			'meta_key'   => 'media',
			'mime_type'  => 'image',
			'labels'     => array(),
			'image_size' => '',
			'element'    => '',
		) );

		// Setup the meta key and labels
		$this->meta_key   = $args['meta_key'];
		$this->mime_type  = $args['mime_type'];
		$this->labels     = wp_parse_args( $args['labels'], array(
			'singular'        => esc_html__( 'Media',  'wp-term-media' ),
			'plural'          => esc_html__( 'Medias', 'wp-term-media' ),
			'description'     => esc_html__( 'Assign an image to uniquely identify each item.', 'wp-term-media' ),

			// Help tab
			'help_title'      => esc_html__( 'Term Media', 'wp-term-media' ),
			'help_content'    => esc_html__( 'Terms can have unique images to visually identify them.', 'wp-term-media' ),

			// JS interface
			'setTermMedia'    => esc_html__( 'Set %s image', 'wp-term-media' ),
			'termMediaTitle'  => esc_html__( '%s image', 'wp-term-media' ),
			'removeTermMedia' => esc_html__( 'Remove %s image', 'wp-term-media' ),
			'error'           => esc_html__( 'Could not set that as the %s image. Try a different attachment.', 'wp-term-media' ),
		) );
		$this->image_size = $args['image_size'];
		$this->element    = $args['element'];

		// Call the parent and pass the file
		parent::__construct( $file );

		// Bail if taxonomy does not include colors
		if ( ! empty( $_REQUEST['taxonomy'] ) && in_array( $_REQUEST['taxonomy'], $this->taxonomies, true ) ) {
			add_action( 'load-edit-tags.php', array( $this, 'setup_globals' ) );
			add_action( 'load-term.php',      array( $this, 'setup_globals' ) );
		}

		// Setup additional actions
		add_filter( 'media_view_settings', array( $this, 'media_settings' ) );
	}

	/**
	 * Return whether we're editing (single) term(s) in the admin
	 *
	 * @since 0.1.0
	 *
	 * @param string $type Optional. Either 'single' or 'multiple'. Defaults to any editing check.
	 * @return bool Are we editing term?
	 */
	public function is_term_edit( $type = '' ) {
		$screen  = get_current_screen();
		$is_edit = false;

		if ( is_admin() && $screen && in_array( $screen->taxonomy, $this->taxonomies ) ) {
			if ( 'single' === $type ) {
				$is_edit = ( 'term' === $screen->base );
			} elseif ( 'multiple' === $type ) {
				$is_edit = ( 'edit-tags' === $screen->base );
			} else {
				$is_edit = in_array( $screen->base, array( 'term', 'edit-tags' ) );
			}
		}

		return $is_edit;
	}

	/**
	 * Administration area hooks
	 *
	 * @since 0.1.0
	 */
	public function setup_globals() {

		// Define default element js selector
		if ( empty( $this->element ) ) {
			$this->element = $this->is_term_edit( 'single' ) ? ".term-{$this->meta_key}-wrap" : "#the-list";
		}

	}

	/**
	 * Return whether the term's attachment has an image to display
	 *
	 * @since 0.1.0
	 *
	 * @param int $term_id Term ID
	 * @return bool Term media has image
	 */
	public function media_has_image( $term_id ) {
		$attachment_id = $this->get_meta( $term_id );
		$is_image = $attachment_id && wp_attachment_is_image( $attachment_id );

		return $is_image;
	}

	/** Assets ****************************************************************/

	/**
	 * Return the collection of details of the current post media
	 *
	 * @since 0.1.0
	 *
	 * @return array Post media details
	 */
	public function get_js_data() {

		// Parse the current screen's taxonomy labels
		$taxonomy = get_taxonomy( get_current_screen()->taxonomy );
		$labels   = array_combine(
			array_keys( $this->labels ),
			array_map( 'sprintf', $this->labels, array_fill( 0, count( $this->labels ), $taxonomy->labels->singular_name ) )
		);

		$data = array(
			'name'       => "termMedia_{$this->meta_key}",
			'key'        => "term-media-{$this->meta_key}",
			'metaKey'    => $this->meta_key,
			'mimeType'   => $this->mime_type,
			'l10n'       => $labels,
			'parentEl'   => $this->element,
			'wrapEl'     => $this->is_term_edit( 'single' ) ? 'td' : ".column-{$this->meta_key}",
			'ajaxAction' => "{$this->meta_key}_terms",
		);

		return $data;
	}

	/**
	 * Enqueue inline-edit JS
	 *
	 * @since 0.1.0
	 */
	public function enqueue_scripts() {

		// Enqueue media scripts
		wp_enqueue_media();

		// Enqueue fancy media selecting
		wp_enqueue_script( 'term-media', $this->url . 'assets/js/term-media.js',  array( 'media-editor' ), $this->db_version, true );
		wp_enqueue_style( 'term-media', $this->url . 'assets/css/term-media.css', array(), $this->db_version );

		// Add script to setup the js instance
		wp_add_inline_script( 'term-media', "
/* global wp */
jQuery(document).ready( function( $ ) {
	if ( typeof wp.media.wpTermMedia === 'undefined' )
		return;

	// Setup media selector
	if ( $( '.wp-term-media', '" . $this->element . "' ).length ) {
		wp.media.wpTermMedia( " . json_encode( $this->get_js_data() ) . " );
	}
} );
" );
	}

	/**
	 * Add help tabs for `media` column
	 *
	 * @since 0.1.0
	 */
	public function help_tabs() {
		get_current_screen()->add_help_tab(array(
			'id'      => "wp_term_{$this->meta_key}_help_tab",
			'title'   => $this->labels['help_title'],
			'content' => '<p>' . $this->labels['help_content'] . '</p>',
		) );
	}

	/**
	 * Output the value for the custom column
	 *
	 * @since 0.1.0
	 *
	 * @param string $empty
	 * @param string $custom_column
	 * @param int    $term_id
	 *
	 * @return mixed
	 */
	public function add_column_value( $empty = '', $custom_column = '', $term_id = 0 ) {

		// Bail if no taxonomy passed or not on the `meta_key` column
		if ( empty( $_REQUEST['taxonomy'] ) || ( $this->meta_key !== $custom_column ) || ! empty( $empty ) ) {
			return;
		}

		// Output HTML element
		echo $this->format_output( $term_id );
	}

	/**
	 * Return the formatted output for the colomn row
	 *
	 * @since 0.1.0
	 *
	 * @param int $term_id
	 */
	protected function format_output( $term_id = 0 ) {

		// Define element attributes
		$attr  = ' class="wp-term-media' . ( $this->media_has_image( $term_id ) ? ' has-image' : '' ) . '"';
		$attr .= ' data-term="' . esc_attr( $term_id ) . '"';

		// When adding a single row through AJAX
		if ( isset( $_REQUEST['_wpnonce_add-tag'] ) && wp_verify_nonce( $_REQUEST['_wpnonce_add-tag'], 'add-tag' ) ) {
			$attr .= ' data-nonce="' . wp_create_nonce( "update-term_{$term_id}" ) . '"';
		}

		return '<span' . $attr . '>' . $this->_media_input_html( $term_id ) . '</span>';
	}

	/**
	 * Do not output the form field for this metadata when adding a new term
	 *
	 * @since 0.1.0
	 */
	public function add_form_field() {
		?>

		<div class="form-field term-<?php echo esc_attr( $this->meta_key ); ?>-wrap">
			<label for="term-<?php echo esc_attr( $this->meta_key ); ?>">
				<?php echo esc_html( $this->labels['singular'] ); ?>
			</label>

			<?php if ( ! empty( $this->labels['description'] ) ) : ?>

				<p class="description">
					<?php echo esc_html( $this->labels['description'] ); ?>
					<?php esc_html_e( 'You can select an attachment for the term, once the term has been created and added to the list.', 'wp-term-media' ); ?>
				</p>

			<?php endif; ?>

		</div>

		<?php
	}

	/**
	 * Output the form field
	 *
	 * @since 0.1.0
	 *
	 * @param  $term
	 */
	protected function form_field( $term = '' ) {
		echo $this->format_output( $term->term_id );
	}

	/**
	 * Do not output the quick-edit field
	 *
	 * @since 0.1.0
	 */
	public function quick_edit_meta( $column_name = '', $screen = '', $name = '' ) { /* Nothing to display */ }

	/**
	 * Prevent sorting by media.
	 *
	 * @since 0.1.0
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function sortable_columns( $columns = array() ) {
		return $columns;
	}

	/** Media Handling ********************************************************/

	/**
	 * Modify the page's media settings for the term media
	 *
	 * @since 0.1.0
	 *
	 * @param array $settings Media settings
	 * @return array Media settings
	 */
	public function media_settings( $settings ) {

		// When editing terms
		if ( $this->is_term_edit() ) {

			// Define post medias collection
			if ( ! isset( $settings['termMedias'] ) || ! is_array( $settings['termMedias'] ) ) {
				$settings['termMedias'] = array();
			}

			// Define this post media's collection
			if ( ! isset( $settings['termMedias'][ $this->meta_key ] ) || ! is_array( $settings['termMedias'][ $this->meta_key ] ) ) {
				$settings['termMedias'][ $this->meta_key ] = array();
			}

			foreach ( $this->get_displayed_terms() as $term_id ) {
				$attachment_id = $this->get_meta( $term_id );
				$settings['termMedias'][ $this->meta_key ][ $term_id ] = array(
					'media' => $attachment_id ? $attachment_id : -1,
					'nonce' => wp_create_nonce( "update-term_{$term_id}" )
				);
			}
		}

		return $settings;
	}

	/**
	 * Return the displayed terms that are on the current page
	 *
	 * @since 0.1.0
	 *
	 * @return array Terms
	 */
	public function get_displayed_terms() {
		$terms = array();

		// Single term
		if ( $this->is_term_edit( 'single' ) ) {
			$terms = array( get_term( (int) $_REQUEST['tag_ID'] )->term_id );

		// List table terms
		} elseif ( $this->is_term_edit( 'multiple' ) ) {
			/**
			 * Get the queried terms. The terms list table does not query them
			 * in the `prepare_items()` method, but rather inline when setting
			 * up the list table's rows. Therefore, we mimc the identical query
			 * here.
			 *
			 * @see WP_Terms_List_Table::has_items()
			 * @see WP_Terms_List_Table::display_rows_or_placeholder()
			 */
			global $wp_list_table;

			$taxonomy = $wp_list_table->screen->taxonomy;

			$args = wp_parse_args( $wp_list_table->callback_args, array(
				'page' => 1,
				'number' => 20,
				'search' => '',
				'hide_empty' => 0
			) );

			$page = $args['page'];

			// Set variable because $args['number'] can be subsequently overridden.
			$number = $args['number'];

			$args['offset'] = $offset = ( $page - 1 ) * $number;

			// Convert it to table rows.
			$count = 0;

			if ( is_taxonomy_hierarchical( $taxonomy ) && ! isset( $args['orderby'] ) ) {
				// We'll need the full set of terms then.
				$args['number'] = $args['offset'] = 0;
			}

			$args['fields'] = 'ids'; // Query term ids

			$terms = get_terms( $taxonomy, $args );
		}

		return $terms;
	}

	/**
	 * Return the post media input HTML
	 *
	 * @since 0.1.0
	 *
	 * @param int $term_id Term ID
	 * @return string Input HTML
	 */
	private function _media_input_html( $term_id = 0 ) {

		// Define local variables
		$term            = get_term( $term_id );
		$taxonomy        = get_taxonomy( $term->taxonomy );
		$set_action_text = sprintf( $this->labels['setTermMedia'], $taxonomy->labels->singular_name );
		$set_media_link  = '<span class="hide-if-no-js"><a title="%s" href="#" class="wp-term-media-set">%s</a></span>';

		$content = sprintf( $set_media_link,
			esc_attr( $set_action_text ),
			esc_html( $set_action_text )
		);

		$attachment_id = $this->get_meta( $term_id );

		// This term has an attachment
		if ( $attachment_id ) {
			$att_html = '';

			if ( $this->media_has_image( $term_id ) ) {
				// Get image in predefined width for admin metabox
				$att_html = wp_get_attachment_image( $attachment_id, array( 150, 150 ) );
			} else {
				$att_html = get_the_title( $attachment_id );
			}

			if ( ! empty( $att_html ) ) {
				$remove_action_text = sprintf( $this->labels['removeTermMedia'], $taxonomy->labels->singular_name );
				$remove_media_link  = ' <span class="hide-if-no-js delete"><a href="#" class="wp-term-media-remove aria-button-if-js" aria-label="%s"><span class="screen-reader-text">' . __( 'Delete' ) . '</span></a></span>';

				$content = sprintf( $set_media_link,
					esc_attr( $set_action_text ),
					$att_html
				) . sprintf( $remove_media_link,
					esc_attr( $remove_action_text )
				);
			}
		}

		return $content;
	}

	/**
	 * Save a term media input on AJAX update
	 *
	 * @since 0.1.0
	 *
	 * @see wp_ajax_set_post_thumbnail()
	 */
	public function ajax_update() {
		$json = ! empty( $_REQUEST['json'] ); // New-style request

		$term_ID = intval( $_POST['term_id'] );
		$term = get_term( $term_ID );

		if ( ! $term || is_wp_error( $term ) )
			wp_die( -1 );
		if ( ! current_user_can( 'edit_term', $term_ID ) )
			wp_die( -1 );

		$attachment_id = intval( $_POST['term_media_id'] );

		if ( $json ) {
			check_ajax_referer( "update-term_{$term_ID}" );
		} else {
			check_ajax_referer( "wp-term-media-set_{$this->meta_key}-{$term_ID}" );
		}

		// Delete term media
		if ( $attachment_id == '-1' ) {
			if ( delete_term_meta( $term_ID, $this->meta_key ) ) {
				$return = $this->ajax_get_return_data( $term_ID, false );
				$json ? wp_send_json_success( $return ) : wp_die( $return );
			} else {
				wp_die( 0 );
			}
		}

		// Update term media
		if ( update_term_meta( $term_ID, $this->meta_key, $attachment_id ) ) {

			// Maybe resize the image
			if ( $this->media_has_image( $term_ID ) ) {
				$this->maybe_resize_image( $attachment_id );
			}

			$return = $this->ajax_get_return_data( $term_ID );
			$json ? wp_send_json_success( $return ) : wp_die( $return );
		}

		wp_die( 0 );
	}

	/**
	 * Return the AJAX update return data
	 *
	 * @since 0.1.0
	 *
	 * @param int $term_id Term ID
	 * @param bool $update Optional. Whether the term was updated or deleted
	 * @return array Return data
	 */
	public function ajax_get_return_data( $term_id, $update = true ) {
		return array(
			'html'          => $this->_media_input_html( $term_id ),
			'setImageClass' => $update ? $this->media_has_image( $term_id ) : false,
			'nonce'         => wp_create_nonce( "update-term_{$term_id}" ),
		);
	}

	/**
	 * Check whether to generate a new sized version of the image
	 *
	 * Do this when an appropriately sized version may not exist for previously
	 * uploaded images.
	 *
	 * @since 0.1.0
	 *
	 * @param int $attachment_id Post ID
	 */
	public function maybe_resize_image( $attachment_id ) {

		// Get requested size. Append crop parameter when missing
		$requested_size = $this->image_size;
		if ( is_array( $requested_size ) && 2 == count( $requested_size ) ) {
			$requested_size[] = false;
		}

		// Find the requested size of the image
		$size_found = image_get_intermediate_size( $attachment_id, $requested_size );
		if ( $size_found ) {
			// Get the size values only for comparison
			$size_found = array( $size_found['width'], $size_found['height'], false );
		}

		// Bail when the requested image size already exists
		if ( $size_found && ( is_string( $requested_size ) || $size_found == $requested_size ) )
			return;

		// Get the requested image size dimensions
		if ( is_string( $requested_size ) ) {
			if ( has_image_size( $requested_size ) ) {
				$sizes = wp_get_additional_image_sizes(); // Since WP 4.7
				$new_size = array_values( $sizes[ $requested_size ] ); // array( width, height, crop )
			} else {
				$new_size = false;
			}
		} else {
			$new_size = $requested_size;
		}

		// Bail when no dimensions are found
		if ( ! $new_size )
			return;

		$editor = wp_get_image_editor( get_attached_file( $attachment_id ) );
		if ( is_wp_error( $editor ) )
			return $editor;

		$resized = $editor->resize( $new_size[0], $new_size[1], $new_size[2] );
		if ( is_wp_error( $resized ) )
			return $resized;

		$destination = $editor->generate_filename();
		$saved = $editor->save( $destination );

		if ( is_wp_error( $saved ) )
			return $saved;

		// Save new image size in attachment metadata
		$metadata = wp_get_attachment_metadata( $attachment_id );
		unset( $saved['path'] );
		$metadata['sizes'][ is_string( $requested_size ) ? $requested_size : $this->meta_key ] = $saved;
		wp_update_attachment_metadata( $attachment_id, $metadata );

		return $destination;
	}
}

endif;
