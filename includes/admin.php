<?php

/**
 * Econozel Admin Functions
 * 
 * @package Econozel
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Econozel_Admin' ) ) :
/**
 * The Econozel Admin Class
 *
 * @since 1.0.0
 */
class Econozel_Admin {

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 *
	 * @uses Econozel_Admin::setup_actions()
	 */
	public function __construct() {
		$this->setup_globals();
		$this->setup_actions();
	}

	/**
	 * Define class globals
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {

		// Identifiers
		$this->article_post_type = econozel_get_article_post_type();
		$this->volume_tax_id     = econozel_get_volume_tax_id();
		$this->edition_tax_id    = econozel_get_edition_tax_id();
	}

	/**
	 * Setup actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {

		// Define local var
		$post_type = $this->article_post_type;
		$taxonomy  = $this->edition_tax_id;

		// Menu
		add_action( 'admin_menu',  array( $this, 'admin_menu'             ) );
		add_action( 'parent_file', array( $this, 'admin_menu_parent_file' ) );

		// Columns
		add_filter( "manage_{$post_type}_posts_columns",        array( $this, 'article_columns'        )        );
		add_action( "manage_{$post_type}_posts_column_content", array( $this, 'article_column_content' ), 10, 2 );
		add_filter( "manage_edit-{$taxonomy}_columns",          array( $this, 'edition_columns'        ), 20    );
		add_filter( "manage_{$taxonomy}_custom_column",         array( $this, 'edition_column_content' ), 10, 3 );
		add_action( 'quick_edit_custom_box',                    array( $this, 'edition_inline_edit'    ), 10, 3 );

		// Edit
		add_action( "add_meta_boxes_{$post_type}",  array( $this, 'article_meta_boxes'            ), 99    );
		add_action( "save_post_{$post_type}",       array( $this, 'article_edition_save_meta_box' )        );
		add_action( "{$taxonomy}_add_form_fields",  array( $this, 'edition_add_fields'            ),  5    );
		add_action( "{$taxonomy}_edit_form_fields", array( $this, 'edition_edit_fields'           ),  5, 2 );
		add_action( "created_{$taxonomy}",          array( $this, 'edition_save_fields'           )        );
		add_action( "edited_{$taxonomy}",           array( $this, 'edition_save_fields'           )        );

		// Scripts & Styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/** Public methods **************************************************/

	/**
	 * Modify the admin menu
	 *
	 * @since 1.0.0
	 */
	public function admin_menu() {

		// Get Volume taxonomy
		if ( ! $tax = get_taxonomy( $this->volume_tax_id ) )
			return;

		// Add Volume submenu page
		$hook = add_submenu_page( "edit.php?post_type={$this->article_post_type}", '', $tax->labels->menu_name, $tax->cap->manage_terms, "edit-tags.php?taxonomy={$this->volume_tax_id}", null );
	}

	/**
	 * Modify the admin menu parent menu
	 *
	 * @since 1.0.0
	 *
	 * @param string $parent_file Parent menu name
	 * @return string parent menu name
	 */
	public function admin_menu_parent_file( $parent_file ) {

		// Set parent menu for Volume taxonomy page
		if ( $this->volume_tax_id == get_current_screen()->taxonomy ) {
			$parent_file = "edit.php?post_type={$this->article_post_type}";
		}

		return $parent_file;
	}

	/**
	 * Enqueue additional admin scripts and styles
	 *
	 * @since 1.0.0
	 */
	public function enqueue_admin_scripts() {

		// Get Econozel
		$eco    = econozel();

		// Get the current screen
		$screen = get_current_screen();

		// Define local var
		$styles = array();

		// Article edit.php
		if ( "edit-{$this->article_post_type}" == $screen->id ) {

			// Define additional styles
			$styles[] = ".fixed .column-taxonomy-{$this->edition_tax_id} { width: 10%; }";
		}

		// Article post.php
		if ( 'post' == $screen->base && $this->article_post_type == $screen->id ) {

			// Define additional styles
			$styles[] = "#econozel-edition select#taxonomy-{$this->edition_tax_id} { width: 100%; max-width: 100%; }";
		}

		// Edition edit-tags.php
		if ( "edit-{$this->edition_tax_id}" == $screen->id ) {

			// Enqueue admin script
			wp_enqueue_script( 'econozel-admin', $eco->includes_url . 'assets/js/admin.js', array( 'jquery' ), $eco->version, true );
			wp_localize_script( 'econozel-admin', 'econozelAdmin', array(
				'settings' => array(
					'editionTaxId' => econozel_get_edition_tax_id(),
					'volumeTaxId'  => econozel_get_volume_tax_id()
				)
			) );

			// Define additional styles
			$styles[] = '.fixed .column-issue, .fixed .column-file { width: 10%; }';
			$styles[] = ".fixed .column-taxonomy-{$this->volume_tax_id} { width: 15%; }";
			$styles[] = ".form-field select#taxonomy-{$this->volume_tax_id}, .form-field select#{$this->edition_tax_id}-issue { width: 95%; max-width: 95%; }";
			$styles[] = ".inline-edit-row fieldset + fieldset { margin-top: -3px; }";
			$styles[] = ".inline-edit-row .input-text-wrap select { width: 100%; vertical-align: top; }";
		}

		// Attach styles to admin's common.css
		if ( ! empty( $styles ) ) {
			wp_add_inline_style( 'common', implode( "\n", $styles ) );
		}
	}

	/** Article ***************************************************************/

	/**
	 * Modify the columns for the Article list table
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns List table columns
	 * @return array List table columns
	 */
	public function article_columns( $columns ) {

		// Insert Edition taxonomy column after 'author' column
		$edition = array( "taxonomy-{$this->edition_tax_id}" => esc_html__( 'Edition', 'econozel' ) );
		$pos     = array_search( 'author', array_keys( $columns ) ) + 1;
		$columns = array_slice( $columns, 0, $pos, true ) + $edition + array_slice( $columns, $pos, count( $columns ) - 1, true );

		return $columns;
	}

	/**
	 * Output the column content for the Article list table
	 *
	 * @since 1.0.0
	 *
	 * @param string $column Column name
	 * @param int $post_id Post ID
	 */
	public function article_column_content( $column, $post_id ) {

		switch ( $column ) {
			case "taxonomy-{$this->edition_tax_id}" :

				// Get Article Edition label
				$edition = econozel_get_article_edition_label( $post_id );

				if ( ! empty( $edition ) ) {
					echo $edition;
				} else {
					echo '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">' . get_taxonomy( $this->edition_tax_id )->labels->no_terms . '</span>';
				}

				break;
		}
	}

	/**
	 * Register meta boxes
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Post object
	 */
	public function article_meta_boxes( $post ) {

		// Bail when this is not an Article
		if ( $this->article_post_type !== $post->post_type )
			return;

		// Remove default page-attributes metabox
		remove_meta_box( 'pageparentdiv', get_current_screen(), 'side' );

		// Article Details metabox
		add_meta_box( 'article_details', esc_html__( 'Article Details', 'econozel' ), array( $this, 'article_details_meta_box' ), null, 'side', 'high' );
	}

	/**
	 * Display the Edition meta box
	 *
	 * @since 1.0.0
	 *
	 * @todo List Editions as #s instead of a list of registered terms > distinct Edition # query helper
	 * @param WP_Post $post Post object
	 */
	public function article_details_meta_box( $post ) { ?>

		<div id="econozel-edition" class="categorydiv">

		<?php

		// Get Edition taxonomy
		$tax        = get_taxonomy( $this->edition_tax_id );
		$terms      = $tax && wp_count_terms( $tax->name ) : false;
		$can_assign = $tax && current_user_can( $tax->cap->assign_terms );

		// When either the user is capable or there is something to show
		if ( $terms && ( $can_assign || econozel_get_article_edition( $post ) ) ) : ?>
			<fieldset>
				<legend><p><strong><?php esc_html_e( 'Volume & Edition', 'econozel' ); ?></strong></p></legend>
				<?php if ( $can_assign ) : ?>

				<p><?php econozel_dropdown_editions( array(
					'name'       => "taxonomy-{$this->edition_tax_id}",
					'hide_empty' => 0,
					'selected'   => econozel_get_article_edition( $post )
				) ); ?></p>

				<?php else : ?>

				<p><?php econozel_the_article_edition_label( $post ); ?></p>

				<?php endif; ?>
			</fieldset>
		<?php endif;

		// When either the user is capable or there is something to show
		if ( $can_assign || $post->menu_order ) : ?>
			<p><strong><?php esc_html_e( 'Page Number', 'econozel' ); ?></strong></p>

			<?php if ( $can_assign ) : ?>

			<p><label class="screen-reader-text" for="menu_order"><?php esc_html_e( 'Page Number', 'econozel' ); ?></label><input name="menu_order" type="text" size="4" id="menu_order" value="<?php echo esc_attr( $post->menu_order ); ?>" /></p>

			<?php else : ?>

			<p><?php echo $post->menu_order; ?></p>

			<?php endif; ?>
		<?php endif; ?>

		</div>

		<?php

		// Metabox nonce
		wp_nonce_field( 'econozel_edition_metabox_save', 'econozel_edition_metabox' );
	}

	/**
	 * Save the input from the Edition meta box
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 */
	public function article_edition_save_meta_box( $post_id ) {

		// Bail when doing an autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// Bail when not a post request
		if ( 'POST' != strtoupper( $_SERVER['REQUEST_METHOD'] ) )
			return;

		// Bail when nonce does not verify
		if ( empty( $_POST['econozel_edition_metabox'] ) || ! wp_verify_nonce( $_POST['econozel_edition_metabox'], 'econozel_edition_metabox_save' ) )
			return;

		// Only save for period post-types
		if ( ! $article = econozel_get_article( $post_id ) )
			return;

		// Get post type object
		$post_type_object = get_post_type_object( $article->post_type );

		// Bail when current user is not capable
		if ( ! current_user_can( $post_type_object->cap->edit_post, $post_id ) )
			return;

		// Get Edition taxonomy. Assign when the user is capable
		$tax = get_taxonomy( $this->edition_tax_id );
		if ( $tax && current_user_can( $tax->cap->assign_terms ) ) {

			// Set Article Edition
			if ( isset( $_POST["taxonomy-{$this->edition_tax_id}"] ) ) {
				wp_set_object_terms( $post_id, (int) $_POST["taxonomy-{$this->edition_tax_id}"], $this->edition_tax_id, false );

			// Remove Article Edition
			} elseif ( $edition = econozel_get_article_edition( $post_id ) ) {
				wp_remove_object_terms( $post_id, array( $edition ), $this->edition_tax_id );
			}
		}
	}

	/** Edition ***************************************************************/

	/**
	 * Modify the columns for the Edition list table
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns List table columns
	 * @return array List table columns
	 */
	public function edition_columns( $columns ) {

		// Insert Volume taxonomy column before 'issue' column
		$volume  = array( "taxonomy-{$this->volume_tax_id}" => esc_html__( 'Volume', 'econoel' ) );
		$pos     = array_search( 'issue', array_keys( $columns ) );
		$columns = array_slice( $columns, 0, $pos, true ) + $volume + array_slice( $columns, $pos, count( $columns ) - 1, true );

		// Remove 'slug' column
		unset( $columns['slug'] );

		// Move 'posts' column to the end
		$posts = $columns['posts'];
		unset( $columns['posts'] );
		$columns['posts'] = $posts;

		return $columns;
	}

	/**
	 * Modify the column content for the Edition list table
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Column content
	 * @param string $column Column name
	 * @param int $term_id Term ID
	 * @return string Column content
	 */
	public function edition_column_content( $content, $column, $term_id ) {

		// Check the column name
		switch ( $column ) {

			// Volume
			case "taxonomy-{$this->volume_tax_id}" :
				if ( $volume = econozel_get_edition_volume( $term_id ) ) {
					$content = econozel_get_volume_title( $volume );
					$content .= sprintf( '<span id="%s" class="hidden">%s</span>', "inline_{$term_id}-{$column}", $volume );
				} else {
					$content = '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">' . get_taxonomy( $this->volume_tax_id )->labels->no_terms . '</span>';
				}

				break;
		}

		return $content;
	}

	/**
	 * Add fields to the Edition add-term form
	 *
	 * @since 1.0.0
	 *
	 * @param string $taxonomy Taxonomy name
	 */
	public function edition_add_fields( $taxonomy ) { ?>

		<div class="form-field term-<?php echo "taxonomy-{$this->volume_tax_id}"; ?>">
			<label for="<?php echo "taxonomy-{$this->volume_tax_id}"; ?>"><?php esc_html_e( 'Volume', 'econozel' ); ?></label>
			<?php econozel_dropdown_volumes( array(
				'name'       => "taxonomy-{$this->volume_tax_id}",
				'hide_empty' => false
			) ); ?>
			<?php wp_nonce_field( 'edition_volume_field_save', 'edition_volume_field' ); ?>

			<p><?php esc_html_e( 'The Volume is the periodic collection an Edition belongs to.', 'econozel' ); ?></p>
		</div>

		<?php
	}

	/**
	 * Add fields to the Edition edit-term form
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term $term Term object
	 * @param string $taxonomy Taxonomy name
	 */
	public function edition_edit_fields( $term, $taxonomy ) { ?>

		<tr class="form-field term-<?php echo "taxonomy-{$this->volume_tax_id}"; ?>-wrap">
			<th scope="row">
				<label for="<?php echo "taxonomy-{$this->volume_tax_id}"; ?>"><?php esc_html_e( 'Volume', 'econozel' ); ?></label>
			</th>
			<td>
				<?php econozel_dropdown_volumes( array(
					'name'       => "taxonomy-{$this->volume_tax_id}",
					'hide_empty' => false,
					'selected'   => econozel_get_edition_volume( $term )
				) ); ?>
				<?php wp_nonce_field( 'edition_volume_field_save', 'edition_volume_field' ); ?>

				<p class="description"><?php esc_html_e( 'The Volume is the periodic collection an Edition belongs to.', 'econozel' ); ?></p>
			</td>
		</tr>

		<?php
	}

	/**
	 * Add fields to the Edition inline-edit form
	 *
	 * @since 1.0.0
	 *
	 * @param string $column Column name
	 * @param string $screen_type Screen name
	 * @param string $taxonomy Taxonomy name
	 */
	public function edition_inline_edit( $column, $screen_type, $taxonomy = '' ) {

		// Bail when we're not editing terms
		if ( 'edit-tags' != $screen_type )
			return;

		// Bail when we're not editing Edition Volumes
		if ( $taxonomy != $this->edition_tax_id || "taxonomy-{$this->volume_tax_id}" != $column )
			return;

		?>

		<fieldset>
			<div class="inline-edit-col">
			<label>
				<span class="title"><?php esc_html_e( 'Volume', 'econozel' ); ?></span>
				<span class="input-text-wrap"><?php econozel_dropdown_volumes( array(
					'name'       => "taxonomy-{$this->volume_tax_id}",
					'hide_empty' => false,
				) ); ?></span>
			</label>
			</div>
		</fieldset>

		<?php
	}

	/**
	 * Save the input for the given Edition term
	 *
	 * @since 1.0.0
	 *
	 * @param int $term_id Term ID
	 */
	public function edition_save_fields( $term_id ) {

		// Get the term and taxonomy
		$tax = get_taxonomy( $this->edition_tax_id );

		// Bail when user is not capable
		if ( ! current_user_can( $tax->cap->edit_terms ) )
			return;

		// Bail when nonce does not verify
		if ( ! isset( $_POST['edition_volume_field'] ) || ! wp_verify_nonce( $_POST['edition_volume_field'], 'edition_volume_field_save' ) )
			return;

		// Set Edition Volume
		if ( isset( $_POST["taxonomy-{$this->volume_tax_id}"] ) ) {
			wp_set_object_terms( $term_id, (int) $_POST["taxonomy-{$this->volume_tax_id}"], $this->volume_tax_id, false );

		// Remove Edition Volume
		} elseif ( $volume = econozel_get_edition_volume( $term_id ) ) {
			wp_remove_object_terms( $term_id, array( $volume ), $this->volume_tax_id );
		}
	}
}

/**
 * Setup the form search class
 *
 * @since 1.0.0
 *
 * @uses Econozel_Admin
 */
function econozel_admin() {
	econozel()->admin = new Econozel_Admin;
}

endif; // class_exists
