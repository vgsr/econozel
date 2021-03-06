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
	 * Minimum capability to access plugin settings
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $minimum_capability = 'econozel_editor';

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

		// Settings
		add_action( 'admin_init',             array( $this, 'register_settings'      )        );
		add_filter( 'econozel_map_meta_caps', array( $this, 'map_settings_meta_caps' ), 10, 4 );

		// Article
		add_filter( "manage_{$post_type}_posts_columns",       array( $this, 'article_columns'            )        );
		add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'article_column_content'     ), 10, 2 );
		add_action( 'quick_edit_custom_box',                   array( $this, 'article_inline_edit'        ), 10, 2 );
		add_action( 'bulk_edit_custom_box',                    array( $this, 'article_inline_edit'        ), 10, 2 );
		add_action( "add_meta_boxes_{$post_type}",             array( $this, 'article_meta_boxes'         ), 99    );
		add_action( 'wp_insert_post_data',                     array( $this, 'wp_insert_post_data'        ), 10, 2 );
		add_action( 'econozel_save_article',                   array( $this, 'article_save_meta_box'      )        );
		add_action( 'econozel_save_article',                   array( $this, 'article_save_author'        )        );
		add_action( 'econozel_save_article',                   array( $this, 'article_save_bulk_edit'     )        );
		add_filter( 'wp_dropdown_users_args',                  array( $this, 'dropdown_users_args'        ), 10, 2 ); // Since WP 4.4
		add_filter( 'post_updated_messages',                   array( $this, 'post_updated_messages'      )        );
		add_filter( 'bulk_post_updated_messages',              array( $this, 'bulk_post_updated_messages' ), 10, 2 );
		add_filter( 'display_post_states',                     array( $this, 'display_post_states'        ), 10, 2 );
		add_action( 'admin_action_econozel-toggle-featured',   array( $this, 'article_toggle_featured'    )        );
		add_filter( 'query',                                   array( $this, 'query_user_posts_count'     )        );

		// Edition
		add_filter( "manage_edit-{$taxonomy}_columns",  array( $this, 'edition_columns'        ), 20    );
		add_filter( "manage_{$taxonomy}_custom_column", array( $this, 'edition_column_content' ), 20, 3 );
		add_action( 'quick_edit_custom_box',            array( $this, 'edition_inline_edit'    ), 10, 3 );
		add_action( "{$taxonomy}_add_form_fields",      array( $this, 'edition_add_fields'     ),  5    );
		add_action( "{$taxonomy}_edit_form_fields",     array( $this, 'edition_edit_fields'    ),  5, 2 );
		add_action( "created_{$taxonomy}",              array( $this, 'edition_save_fields'    )        );
		add_action( "edited_{$taxonomy}",               array( $this, 'edition_save_fields'    )        );

		// User
		add_action( 'edit_user_profile',        array( $this, 'user_profile_settings'      ) );
		add_action( 'edit_user_profile_update', array( $this, 'user_save_profile_settings' ) );

		// Menus
		add_filter( "nav_menu_items_{$post_type}", 'econozel_nav_menu_items_metabox', 10, 3 );

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
		if ( $tax = get_taxonomy( $this->volume_tax_id ) ) {

			// Add Volume submenu page
			add_submenu_page( "edit.php?post_type={$this->article_post_type}", '', $tax->labels->menu_name, $tax->cap->manage_terms, "edit-tags.php?taxonomy={$this->volume_tax_id}", null );
		}

		// Add settings page
		add_submenu_page( "edit.php?post_type={$this->article_post_type}", esc_html__( 'Econozel Settings', 'econozel' ), esc_html__( 'Settings', 'econozel' ), 'econozel_editor', 'econozel_settings', array( $this, 'admin_settings_page' ) );
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
	 * Output the contents of the admin settings page
	 *
	 * @since 1.0.0
	 */
	public function admin_settings_page() { ?>

		<div class="wrap">
			<h1><?php esc_html_e( 'Econozel Settings', 'econozel' ); ?></h1>

			<form action="options.php" method="post">
				<?php settings_fields( 'econozel' ); ?>
				<?php do_settings_sections( 'econozel' ); ?>

				<?php submit_button(); ?>
			</form>
		</div>

		<?php
	}

	/**
	 * Enqueue additional admin scripts and styles
	 *
	 * @since 1.0.0
	 */
	public function enqueue_admin_scripts() {

		// Define local variable(s)
		$screen      = get_current_screen();
		$styles      = array();
		$load_script = false;
		$load_style  = false;

		// Article edit.php
		if ( "edit-{$this->article_post_type}" == $screen->id ) {
			$load_script = true;
			$load_style  = true;

			// Define additional styles
			$styles[] = ".fixed .column-econozel-author, .fixed .column-taxonomy-{$this->edition_tax_id} { width: 10%; }";
			$styles[] = ".fixed .column-econozel-featured { width: 1.8em; }";

		// Article post.php
		} elseif ( 'post' == $screen->base && $this->article_post_type == $screen->id ) {
			$load_script = true;
			$load_style  = true;

			// Define additional styles
			$styles[] = "#article-details .article-edition label, #article-details .article-page-number label { display: inline-block; margin: .5em 0px; vertical-align: bottom; font-weight: 600; }";
			$styles[] = "#article-details select#taxonomy-{$this->edition_tax_id} { width: 100%; max-width: 100%; }";

			// Hide 'Save Draft' button when featured
			if ( econozel_is_article_featured() ) {
				$styles[] = "#save-post { display: none; }";
			}

		// Edition edit-tags.php
		} elseif ( "edit-{$this->edition_tax_id}" == $screen->id ) {
			$load_script = true;

			// Define additional styles
			$styles[] = '.fixed .column-issue, .fixed .column-file { width: 10%; }';
			$styles[] = ".fixed .column-taxonomy-{$this->volume_tax_id} { width: 15%; }";
			$styles[] = ".form-field select#taxonomy-{$this->volume_tax_id}, .form-field select#{$this->edition_tax_id}-issue { width: 95%; max-width: 95%; }";
			$styles[] = ".inline-edit-row fieldset + fieldset { margin-top: -3px; }";
			$styles[] = ".inline-edit-row .input-text-wrap select { width: 100%; vertical-align: top; }";
		}

		// Enqueue admin script
		if ( $load_script ) {

			// Get Edition taxonomy
			$tax_edition = get_taxonomy( $this->edition_tax_id );

			// Enqueue and localize admin script
			wp_enqueue_script( 'econozel-admin', econozel()->assets_url . 'js/admin.js', array( 'jquery' ), econozel_get_version(), true );
			wp_localize_script( 'econozel-admin', 'econozelAdmin', array(
				'l10n' => array(
					'articleMenuOrderLabel' => esc_html__( 'Page Number', 'econozel' ),
					'noChangeLabel'         => __( '&mdash; No Change &mdash;' ), // As WP does
				),
				'settings' => array(

					// Identifiers
					'articlePostType'       => $this->article_post_type,
					'editionTaxId'          => $this->edition_tax_id,
					'volumeTaxId'           => $this->volume_tax_id,

					// Capabilities
					'userCanAssignEditions' => current_user_can( $tax_edition->cap->assign_terms ),

					// Featured post status
					'featuredStatusId' => econozel_get_featured_status_id(),
					'featuredLabel'    => esc_html__( 'Featured', 'econozel' ),
					'publishStatusId'  => 'publish',
					'publishLabel'     => esc_html__( 'Published' ),
					'isFeatured'       => econozel_is_article_featured(),
				)
			) );
		}

		// Enqueue admin styles
		if ( $load_style ) {
			wp_enqueue_style( 'econozel-admin', econozel()->assets_url . 'css/admin.css', array( 'common' ), econozel_get_version() );
		}

		// Attach styles to admin's common.css
		if ( ! empty( $styles ) ) {
			wp_add_inline_style( 'common', implode( "\n", $styles ) );
		}
	}

	/** Settings **************************************************************/

	/**
	 * Register plugin settings
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {

		// Bail if no sections available
		$sections = econozel_admin_get_settings_sections();
		if ( empty( $sections ) )
			return false;

		// Loop through sections
		foreach ( (array) $sections as $section_id => $section ) {

			// Only proceed if current user can see this section
			if ( ! current_user_can( $section_id ) )
				continue;

			// Only add section and fields if section has fields
			$fields = econozel_admin_get_settings_fields_for_section( $section_id );
			if ( empty( $fields ) )
				continue;

			// Define section page
			if ( ! empty( $section['page'] ) ) {
				$page = $section['page'];
			} else {
				$page = 'econozel';
			}

			// Add the section
			add_settings_section( $section_id, $section['title'], $section['callback'], $page );

			// Loop through fields for this section
			foreach ( (array) $fields as $field_id => $field ) {

				// Add the field
				if ( ! empty( $field['callback'] ) && ! empty( $field['title'] ) ) {
					add_settings_field( $field_id, $field['title'], $field['callback'], $page, $section_id, $field['args'] );
				}

				// Register the setting
				register_setting( $page, $field_id, $field['sanitize_callback'] );
			}
		}
	}

	/**
	 * Map caps for the plugin settings
	 *
	 * @since 1.0.0
	 *
	 * @param array $caps Mapped caps
	 * @param string $cap Required capability name
	 * @param int $user_id User ID
	 * @param array $args Additional arguments
	 * @return array Mapped caps
	 */
	public function map_settings_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

		// Check the required capability
		switch ( $cap ) {

			// Econozel Settings
			case 'econozel_settings_general' :
			case 'econozel_settings_per_page' :
			case 'econozel_settings_slugs' :
				$caps = array( $this->minimum_capability );
				break;
		}

		return $caps;
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

		// Insert Featured column before 'title' column
		if ( current_user_can( 'feature_econozel_articles' ) ) {
			$edition = array( 'econozel-featured' => '<i class="dashicons-before dashicons-star-filled" title="' . esc_attr__( 'Featured', 'econozel' ) . '"></i><span class="screen-reader-text">' . esc_html__( 'Featured', 'econozel' ) . '</span>' );
			$pos     = array_search( 'title', array_keys( $columns ) );
			$columns = array_slice( $columns, 0, $pos, true ) + $edition + array_slice( $columns, $pos, count( $columns ) - 1, true );
		}

		// Reuse author column with custom content
		$column_keys = array_keys( $columns );
		$column_keys[ array_search( 'author', $column_keys ) ] = 'econozel-author';
		$columns     = array_combine( $column_keys, $columns );

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

			// Post author
			case 'econozel-author' :

				// Provide admin posts url
				add_filter( 'econozel_get_article_author_url', array( $this, 'admin_posts_author_url' ), 99, 3 );

				// Display author link(s)
				econozel_the_article_author_link( array(
					'article' => $post_id,
					'concat'  => ', '
				) );

				// Unhook admin posts url
				remove_filter( 'econozel_get_article_author_url', array( $this, 'admin_posts_author_url' ), 99, 3 );

				break;

			// Edition
			case "taxonomy-{$this->edition_tax_id}" :

				// Get Article Edition label
				$edition = econozel_get_article_edition_label( $post_id );

				if ( ! empty( $edition ) ) {
					echo $edition;
				} else {
					echo '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">' . get_taxonomy( $this->edition_tax_id )->labels->no_terms . '</span>';
				}

				break;

			// Featured
			case 'econozel-featured' :

				// Get featured status
				$featured   = econozel_is_article_featured( $post_id );

				// Define whether the user can change the status
				$actionable = current_user_can( 'feature_econozel_articles' ) && in_array( get_post_status( $post_id ), array( 'publish', econozel_get_featured_status_id() ) );

				if ( $actionable ) {
					printf( '<a class="dashicons-before %1$s" href="%2$s" title="%3$s"><span class="screen-reader-text">%3$s</span></a>',
						$featured ? 'dashicons-star-filled' : 'dashicons-star-empty',
						esc_url( wp_nonce_url( admin_url( add_query_arg( array( 'post_type' => $this->article_post_type, 'post' => $post_id, 'action' => 'econozel-toggle-featured' ), 'admin.php' ) ), 'econozel_toggle_featured_article' ) ),
						$featured ? esc_attr__( 'Make this article not featured', 'econozel' ) : esc_attr__( 'Make this article featured', 'econozel' )
					);
				}

				break;
		}
	}

	/**
	 * Return the url that points to the author's posts
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Post author url
	 * @param int $user_id User ID
	 * @param WP_Post $post Post object
	 * @return string Post author url
	 */
	public function admin_posts_author_url( $url, $user_id, $post ) {
		return add_query_arg( array( 'post_type' => econozel_get_article_post_type(), 'author' => $user_id ), 'edit.php' );
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
		remove_meta_box( 'pageparentdiv', null, 'side' );

		// Article Details metabox
		add_meta_box( 'article-details', esc_html__( 'Article Details', 'econozel' ), array( $this, 'article_details_meta_box' ), null, 'side', 'high' );

		// Multi-author
		remove_meta_box( 'authordiv', null, 'normal' );
		add_meta_box( 'econozel-author', __( 'Author' ), array( $this, 'article_author_meta_box' ), null, 'side', 'high' );
	}

	/**
	 * Display the Edition meta box
	 *
	 * @since 1.0.0
	 *
	 * @todo List Editions as #s instead of a list of registered terms > distinct Edition # query helper
	 * @param WP_Post $post Post object
	 */
	public function article_details_meta_box( $post ) {

		// Get Edition taxonomy
		$tax        = get_taxonomy( $this->edition_tax_id );
		$terms      = $tax && wp_count_terms( $tax->name );
		$can_assign = $terms && current_user_can( $tax->cap->assign_terms );

		// Metabox nonce
		wp_nonce_field( 'econozel_edition_metabox_save', 'econozel_edition_metabox' );

		// When the user can assign Editions
		if ( $can_assign ) : ?>

			<fieldset class="article-edition">
				<legend><label for="taxonomy-<?php echo $this->edition_tax_id; ?>"><?php esc_html_e( 'Volume & Edition', 'econozel' ); ?></label></legend>
				<?php econozel_dropdown_editions( array(
					'name'       => "taxonomy-{$this->edition_tax_id}",
					'hide_empty' => 0,
					'selected'   => econozel_get_article_edition( $post )
				) ); ?>
			</fieldset>

			<p class="article-page-number">
				<label for="menu_order"><?php esc_html_e( 'Page Number', 'econozel' ); ?></label>
				<input name="menu_order" type="number" size="4" id="menu_order" class="small-text" value="<?php echo esc_attr( $post->menu_order ); ?>" />
			</p>

		<?php else : ?>

			<?php if ( econozel_get_article_edition( $post ) ) : ?>

				<p><?php econozel_the_article_edition_label( $post ); ?></p>

				<?php if ( $post->menu_order ) : ?>

				<p><?php prinft( esc_html__( 'Page Number: %d', 'econozel' ), $post->menu_order ); ?></p>

				<?php endif; ?>

			<?php else : ?>

				<p><?php esc_html_e( '&mdash; Not published in an Edition &mdash;', 'econozel' ); ?></p>

			<?php endif; ?>

		<?php endif;
	}

	/**
	 * Display the custom Author meta box
	 *
	 * @see post_author_meta_box()
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Post object
	 */
	public function article_author_meta_box( $post ) { ?>

		<label class="screen-reader-text" for="post_author_override"><?php _e( 'Author' ); ?></label>
		<?php

		// Collect post author(s)
		$user_ids = econozel_get_article_author( $post );
		if ( empty( $user_ids ) ) {
			$user_ids = array( $GLOBALS['user_ID'] );
		}

		// Define remove button
		$remove_button = ' <button type="button" class="button-link remove-extra-author dashicons-before dashicons-no-alt"><span class="screen-reader-text">' . esc_html__( 'Remove author', 'econozel' ) . '</span></button>';

		foreach ( $user_ids as $k => $user_id ) {
			$first    = 0 === $k;
			$args     =  array(
				'econozel'         => true,
				'who'              => 'authors',
				'id'               => 'post_author_override',
				'name'             => $first ? 'post_author_override' : 'econozel-author[]',
				'selected'         => $user_id,
				'include_selected' => true,
				'show'             => 'display_name_with_login',
				'echo'             => false
			);
			$dropdown = wp_dropdown_users( $args );

			// Bail when the dropdown has no users
			if ( empty( $dropdown ) ) {
				echo '<p><em>' . esc_html__( 'There are no users available to be selected as author.', 'econozel' ) . '</em></p>';
				return;
			}

			echo '<div class="post-author">' . $dropdown . ( ! $first ? $remove_button : '' ) . '</div>';
		}

		// Makes ure the input name is for extra authors, remove selection
		$dropdown = str_replace( "name='post_author_override'", "name='econozel-author[]'", $dropdown );
		$dropdown = str_replace( "id='", "disabled id='", str_replace( " selected='selected'", '', $dropdown ) );

		?>

		<div class="post-author hidden"><?php echo $dropdown . $remove_button ?></div>
		<p class="author-actions">
			<button type="button" class="button add-extra-author"><?php esc_html_e( 'Add author', 'econozel' ); ?></button>
		</p>

		<?php
	}

	/**
	 * Add fields to the Article bulk/inline-edit form
	 *
	 * @since 1.0.0
	 *
	 * @param string $column Column name
	 * @param string $post_type Post type name
	 */
	public function article_inline_edit( $column, $post_type ) {

		// Bail when we're not editing Articles
		if ( $this->article_post_type !== $post_type )
			return;

		// Bulk or Quick edit?
		$bulk = doing_action( 'bulk_edit_custom_box' );

		// Check the column
		switch ( $column ) {

			// Edition
			case "taxonomy-{$this->edition_tax_id}" :

				// Get Edition taxonomy
				$tax = get_taxonomy( $this->edition_tax_id );

				// Bail when the user is not capable
				if ( ! $tax || ! current_user_can( $tax->cap->assign_terms ) )
					return; ?>

		<div class="<?php echo $bulk ? 'inline-edit-group' : 'inline-edit-col'; ?> article-edition">
			<label>
				<span class="title"><?php esc_html_e( 'Edition', 'econozel' ); ?></span>
				<span class="input-text-wrap"><?php econozel_dropdown_editions( array(
					'name'              => $bulk ? 'article-edition' : "tax_input[{$this->edition_tax_id}]", // Doing bulk through tax_input prohibits removing the current term(s), so a custom input name/saving is used
					'class'             => "tax_input_{$this->edition_tax_id}",
					'hide_empty'        => false,
					'value_field'       => $bulk ? 'term_id' : 'name', // Saving through WP's non-hierarchical tax_input uses term names
					'option_none_value' => '', // NOTE: non-empty values are used to create new terms on the fly
				) ); ?></span>
			</label>
		</div>

			<?php
				break;
		}
	}

	/**
	 * Modify the data of a post to be saved
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Post data
	 * @param array $postarr Initial post data
	 * @return array Post data
	 */
	public function wp_insert_post_data( $data, $postarr ) {

		// Get featured status
		$featured = econozel_get_featured_status_id();

		/**
		 * Article was featured and should remain so. This is however interpreted by
		 * WP in a different way. See {@see _wp_translate_postdata()}, where the post
		 * status is reset to 'publish' when the 'Publish' button was used, even when
		 * a different/custom post status was provided.
		 */
		if ( isset( $_REQUEST['publish'] ) && $featured === $postarr['original_post_status'] && $featured === $_REQUEST['post_status'] ) {
			$data['post_status'] = $featured;
		}

		return $data;
	}

	/**
	 * Save the input from the Edition meta box
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 */
	public function article_save_meta_box( $post_id ) {

		// Bail when nonce does not verify
		if ( empty( $_POST['econozel_edition_metabox'] ) || ! wp_verify_nonce( $_POST['econozel_edition_metabox'], 'econozel_edition_metabox_save' ) )
			return;

		// Get Edition taxonomy
		$tax = get_taxonomy( $this->edition_tax_id );

		// Continue when the user is capable
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

	/**
	 * Save the input from the Author meta box
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 */
	public function article_save_author( $post_id ) {

		// Author
		if ( isset( $_REQUEST['econozel-author'] ) ) {
			// var_dump( $_REQUEST ); exit;

			// Remove previous meta instances
			delete_post_meta( $post_id, 'post_author' );

			// Get user ids
			$user_ids = array_map( 'absint', (array) $_REQUEST['econozel-author'] );
			$user_ids = array_unique( array_filter( $user_ids ) );

			// When the post is multi-author, store canonical author in meta as well
			if ( $user_ids ) {
				$post_author = get_post( $post_id )->post_author;

				if ( ! in_array( $post_author, $user_ids ) ) {
					$user_ids[] = $post_author;
				}
			}

			// Update post meta
			foreach ( $user_ids as $user_id ) {
				add_post_meta( $post_id, 'post_author', $user_id );
			}
		}
	}

	/**
	 * Save the input from the post bulk edit action
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 */
	public function article_save_bulk_edit( $post_id ) {

		// Bail when not bulk editing or nonce does not verify
		if ( ! isset( $_REQUEST['bulk_edit'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-posts' ) )
			return;

		// Edition
		if ( isset( $_REQUEST['article-edition'] ) ) {

			// Get Edition taxonomy
			$tax = get_taxonomy( $this->edition_tax_id );

			// Continue when the user is capable
			if ( $tax && current_user_can( $tax->cap->assign_terms ) ) {
				$edition = $_REQUEST['article-edition'];

				// Remove Article Edition
				if ( empty( $edition ) && $edition = econozel_get_article_edition( $post_id ) ) {
					wp_remove_object_terms( $post_id, array( $edition ), $this->edition_tax_id );

				// Set Article Edition. -1 means to do nothing
				} elseif ( '-1' !== $edition ) {
					wp_set_object_terms( $post_id, array( intval( $edition ) ), $this->edition_tax_id, false );
				}
			}
		}
	}

	/**
	 * Modify the query args for the users dropdown
	 *
	 * @since 1.0.0
	 *
	 * @param array $query_args Query args for `WP_User_Query`
	 * @param array $args Dropdown args
	 * @return array Query args
	 */
	public function dropdown_users_args( $query_args, $args ) {

		// When querying Econozel users
		if ( isset( $args['econozel'] ) && $args['econozel'] ) {
			$query_args['econozel'] = true;

			// Remove the 'authors' limitation
			unset( $query_args['who'] );

			// Query by Editor role
			$query_args['role'] = econozel_get_editor_role();
		}

		return $query_args;
	}

	/**
	 * Add post-type specific messages for post updates
	 *
	 * @since 1.0.0
	 *
	 * @param array $messages Messages
	 * @return array Messages
	 */
	public function post_updated_messages( $messages ) {

		// Define view link
		$view_article_link = sprintf( ' <a href="%s">%s</a>',
			esc_url( get_permalink() ),
			esc_html__( 'View Article', 'econozel' )
		);

		$messages[ $this->article_post_type ] = array(
			 1 => __( 'Article updated.',   'econozel' ) . $view_article_link,
			 4 => __( 'Article updated.',   'econozel' ),
			 6 => __( 'Article created.',   'econozel' ) . $view_article_link,
			 7 => __( 'Article saved.',     'econozel' ),
			 8 => __( 'Article submitted.', 'econozel' ) . $view_article_link,
		);

		return $messages;
	}

	/**
	 * Add post-type specific messages for post bulk updates
	 *
	 * @since 1.0.0
	 *
	 * @param array $messages Messages
	 * @param array $bulk_counts Changed post counts
	 * @return array Messages
	 */
	public function bulk_post_updated_messages( $messages, $bulk_counts ) {

		$messages[ $this->article_post_type ] = array(
			'updated'   => _n( '%s article updated.', '%s articles updated.', $bulk_counts['updated'], 'econozel' ),
			'locked'    => ( 1 == $bulk_counts['locked'] ) ? __( '1 article not updated, somebody is editing it.', 'econozel' ) :
							_n( '%s article not updated, somebody is editing it.', '%s articles not updated, somebody is editing them.', $bulk_counts['locked'], 'econozel' ),
			'deleted'   => _n( '%s article permanently deleted.', '%s articles permanently deleted.', $bulk_counts['deleted'], 'econozel' ),
			'trashed'   => _n( '%s article moved to the Trash.', '%s articles moved to the Trash.', $bulk_counts['trashed'], 'econozel' ),
			'untrashed' => _n( '%s article restored from the Trash.', '%s articles restored from the Trash.', $bulk_counts['untrashed'], 'econozel' ),
		);

		return $messages;
	}

	/**
	 * Modify the post states
	 *
	 * @since 1.0.0
	 *
	 * @param array $states Post states
	 * @param WP_Post $post Post object
	 * @return array Post states
	 */
	public function display_post_states( $states, $post ) {

		// Get Article
		if ( $article = econozel_get_article( $post ) ) {

			// Article is featured, only when not showing the featured list
			if ( ! ( isset( $_GET['post_status'] ) && econozel_get_featured_status_id() === $_GET['post_status'] ) && econozel_get_featured_status_id() === $article->post_status ) {
				$states['econozel-featured'] = '<span class="econozel-featured-status">' . esc_html__( 'Featured', 'econozel' ) . '</span>';
			}
		}

		return $states;
	}

	/**
	 * Toggle the 'featured' post status of an Article
	 *
	 * @since 1.0.0
	 */
	public function article_toggle_featured() {

		// Check user's intent
		check_admin_referer( 'econozel_toggle_featured_article' );

		if ( ! $location = wp_get_referer() ) {
			$location = admin_url( add_query_arg( array( 'post_type' => $this->article_post_type ), 'edit.php' ) );
		}

		// When able
		if ( current_user_can( 'feature_econozel_articles' ) ) {

			// Update article
			if ( isset( $_REQUEST['post'] ) && $article = econozel_get_article( $_REQUEST['post'] ) ) {
				$article->post_status = econozel_is_article_featured( $article ) ? 'publish' : econozel_get_featured_status_id();
				
				if ( wp_update_post( $article ) ) {
					$location = add_query_arg( 'updated', 1, $location );
				}
			}
		}

		// Send back
		wp_safe_redirect( $location );
		exit;
	}

	/**
	 * Modfiy the query when querying for the user posts count
	 *
	 * @since 1.0.0
	 *
	 * @uses WPDB $wpdb
	 *
	 * @param string $query WPDB query
	 * @return string WPDB query
	 */
	public function query_user_posts_count( $query ) {
		global $wpdb;

		// Bail when not in the admin or the user is fully capable
		if ( ! is_admin() ) {
			remove_action( 'query', array( $this, 'query_user_posts_count' ) );
			return $query;
		}

		// When we're able to determine where we are
		if ( function_exists( 'get_current_screen' ) && get_current_screen() && null !== get_current_screen()->id ) {

			// Bail when not on the Articles list table page
			if ( 'edit-' . econozel_get_article_post_type() !== get_current_screen()->id ) {
				remove_action( 'query', array( $this, 'query_user_posts_count' ) );
				return $query;
			}

			// Define modifier flag
			static $modified = null;

			if ( null === $modified ) {

				// Define query to match once
				static $query_to_match = null;

				/**
				 * Reconstruct the query we're trying to match. See {@see WP_Posts_List_Table::__construct()}.
				 * NOTE: Keep the layout like this, because the whitespace between lines matters when comparing
				 * strings!
				 */
				if ( null === $query_to_match ) {
					$exclude_states         = get_post_stati(
						array(
							'show_in_admin_all_list' => false,
						)
					);
					$query_to_match = $wpdb->prepare( "
			SELECT COUNT( 1 )
			FROM $wpdb->posts
			WHERE post_type = %s
			AND post_status NOT IN ( '" . implode( "','", $exclude_states ) . "' )
			AND post_author = %d
					", get_current_screen()->post_type, get_current_user_id()
					);
				}

				// This is the query we're looking for
				if ( trim( $query ) === trim( $query_to_match ) ) {

					// Construct replacement query to find the user's posts
					$query = new WP_Query( array(
						'post_type'      => econozel_get_article_post_type(),
						'author'         => get_current_user_id(),
						'posts_per_page' => -1,
						'fields'         => 'ids'
					) );

					// Query just the wanted value
					$query = "SELECT {$query->post_count}";

					// Signal that we're done here
					$modified = true;
				}
			}

			// Remove action when run
			if ( null !== $modified ) {
				remove_action( 'query', array( $this, 'query_user_posts_count' ) );
			}
		}

		return $query;
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
		$volume  = array( "taxonomy-{$this->volume_tax_id}" => esc_html__( 'Volume', 'econozel' ) );
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

		// Bail when we're not editing Editions
		if ( 'edit-tags' !== $screen_type || $this->edition_tax_id !== $taxonomy )
			return;

		switch ( $column ) {

			// Volume
			case "taxonomy-{$this->volume_tax_id}" : ?>

		<fieldset>
			<div class="inline-edit-col">
			<label>
				<span class="title"><?php esc_html_e( 'Volume', 'econozel' ); ?></span>
				<span class="input-text-wrap"><?php econozel_dropdown_volumes( array(
					'name'       => "taxonomy-{$this->volume_tax_id}",
					'hide_empty' => false,
				) ); ?></span>
			</label>
			<?php wp_nonce_field( 'edition_volume_field_save', 'edition_volume_field' ); ?>
			</div>
		</fieldset>

			<?php
				break;
		}

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

	/** User ******************************************************************/

	/**
	 * Output the plugin settings fields for the user profile
	 *
	 * @since 1.0.0
	 *
	 * @param WP_User $profileuser User data
	 */
	function user_profile_settings( $profileuser ) {

		// Bail when user is not capable
		if ( ! current_user_can( 'edit_user', $profileuser->ID ) )
			return;

		// Get user's current role
		$eco_role = econozel_get_user_role( $profileuser->ID ); ?>

		<h2><?php esc_html_e( 'Econozel', 'econozel' ); ?></h2>

		<table class="form-table">
			<tbody>
				<tr>
					<th><label for="econozel-role"><?php esc_html_e( 'Econozel Role', 'econozel' ); ?></label></th>
					<td>
						<select id="econozel-role" name="econozel-role">
							<option value=""><?php esc_html_e( '&mdash; No Econozel role &mdash;', 'econozel' ); ?></option>

							<?php foreach ( econozel_get_dynamic_roles() as $role => $args ) : ?>

							<option <?php selected( $role, $eco_role ); ?> value="<?php echo esc_attr( $role ); ?>"><?php echo $args['name']; ?></option>

							<?php endforeach; ?>
						</select>
					</td>
			</tbody>
		</table>

		<?php
	}

	/**
	 * Save user profile settings
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID
	 */
	function user_save_profile_settings( $user_id ) {

		// Bail when user is not capable
		if ( ! current_user_can( 'edit_user', $user_id ) )
			return;

		// Get the saved data
		$role = isset( $_POST['econozel-role'] ) ? $_POST['econozel-role'] : null;

		// Assign role when provided
		if ( null !== $role ) {
			econozel_set_user_role( $user_id, $role );
		}
	}
}

/**
 * Setup the admin class
 *
 * @since 1.0.0
 *
 * @uses Econozel_Admin
 */
function econozel_admin() {
	econozel()->admin = new Econozel_Admin;
}

endif; // class_exists
