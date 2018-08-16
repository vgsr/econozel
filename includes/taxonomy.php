<?php

/**
 * Econozel Taxonomy Functions
 * 
 * @package Econozel
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Registration **************************************************************/

/**
 * Modify the taxonomy arguments on taxonomy registration
 *
 * @since 1.0.0
 *
 * @param array $args Taxonomy arguments
 * @param string $taxonomy Taxonomy id
 * @param array $object_types Object types
 * @return array Taxonomy arguments
 */
function econozel_register_taxonomy_args( $args, $taxonomy, $object_types ) {

	// Registering Post Tags - this is fixed since WP 4.7. See WP#35614
	if ( 'post_tag' === $taxonomy && version_compare( $GLOBALS['wp_version'], '4.7-beta1', '<' ) ) {

		/**
		 * Change caps definition when not already changed
		 *
		 * The following code is a hack to uniquely identify caps for the post_tag
		 * taxonomy. Capabilities for the post_tag taxonomy default to the generic
		 * 'manage_categories' and 'edit_posts' primitive caps. Since taxonomy cap
		 * checks are not associated with term objects, we cannot find which taxonomy
		 * is actually checked for. Alternatively, we would rather not extend
		 * Econozel caps to managing categories and editing posts in general.
		 */
		if ( ! isset( $args['capabilities'] ) ) {
			$args['capabilities'] = array(
				'manage_terms' => 'manage_post_tags',
				'edit_terms'   => 'edit_post_tags',
				'delete_terms' => 'delete_post_tags',
				'assign_terms' => 'assign_post_tags',
			);
		}
	}

	// Register media meta flags
	$fields = econozel_get_taxonomy_meta( $taxonomy );
	$fields = wp_list_filter( $fields, array( 'type' => 'media' ) );

	foreach ( array_keys( $fields ) as $meta_key ) {
		$args["term_meta_{$meta_key}"] = true;
	}

	return $args;
}

/** Meta **********************************************************************/

/**
 * Return the meta for plugin taxonomies
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_taxonomy_meta'
 *
 * @param string $taxonomy Taxonomy name
 * @param string $meta_key Optional. Meta key to get single meta. Defaults to false.
 * @return array Taxonomy meta or single meta args.
 */
function econozel_get_taxonomy_meta( $taxonomy, $meta_key = false ) {

	// Get all metas. Structured as `array( string $taxonomy => array( stringi $meta_key => array $args ) )`
	$meta = (array) apply_filters( 'econozel_get_taxonomy_meta', array() );

	// Select a taxonomy's meta
	if ( $taxonomy && isset( $meta[ $taxonomy ] ) ) {
		$meta = $meta[ $taxonomy ];
	} else {
		$meta = array();
	}

	// Select single meta field
	if ( $meta_key && isset( $meta[ $meta_key ] ) ) {
		$meta             = $meta[ $meta_key ];
		$meta['taxonomy'] = $taxonomy;
		$meta['meta_key'] = $meta_key;
	} elseif ( false !== $meta_key ) {
		$meta = false;
	}

	return $meta;
}

/**
 * Register meta fields for plugin taxonomies
 *
 * @since 1.0.0
 *
 * @param string $taxonomy Taxonomy name
 */
function econozel_register_taxonomy_meta( $taxonomy ) {

	// Get non-media meta fields
	$fields = econozel_get_taxonomy_meta( $taxonomy );
	$fields = wp_list_filter( $fields, array( 'type' => 'media' ), 'NOT' );

	// Bail when there are no other meta fields
	if ( ! $fields )
		return;

	// Display meta fields on create and edit
	add_action( "{$taxonomy}_add_form_fields",  'econozel_taxonomy_meta_add_fields'         );
	add_action( "{$taxonomy}_edit_form_fields", 'econozel_taxonomy_meta_edit_fields', 10, 2 );

	// Save meta fields on create and edit
	add_action( "created_{$taxonomy}", 'econozel_taxonomy_meta_save_fields' );
	add_action( "edited_{$taxonomy}",  'econozel_taxonomy_meta_save_fields' );

	// Admin and inline columns
	add_filter( "manage_edit-{$taxonomy}_columns", 'econozel_taxonomy_meta_admin_columns'      );
	add_action( 'quick_edit_custom_box',           'econozel_taxonomy_meta_inline_edit', 10, 3 );

	// Walk fields
	foreach ( $fields as $meta_key => $args ) {

		// Sanitize meta value on updates
		add_filter( "sanitize_term_meta_{$meta_key}", $args['sanitize_cb'] );

		// Admin column
		if ( isset( $args['admin_column_cb'] ) && $args['admin_column_cb'] ) {
			add_filter( "manage_{$taxonomy}_custom_column", is_callable( $args['admin_column_cb'] )
				? $args['admin_column_cb']
				: 'econozel_taxonomy_meta_admin_column_content',
				20, 3
			);
		}
	}
}

/**
 * Output taxonomy meta fields in the Add Term screen
 *
 * @since 1.0.0
 *
 * @param string $taxonomy Taxonomy name
 */
function econozel_taxonomy_meta_add_fields( $taxonomy ) {

	// Get non-media meta fields
	$fields = econozel_get_taxonomy_meta( $taxonomy );
	$fields = wp_list_filter( $fields, array( 'type' => 'media' ), 'NOT' );

	// Bail when the taxonomy has no meta fields
	if ( ! $fields )
		return;

	// Walk fields to output
	foreach ( $fields as $meta_key => $args ) : ?>

	<div class="form-field term-<?php echo $meta_key; ?>">
		<label for="<?php echo "{$taxonomy}_{$meta_key}"; ?>"><?php echo $args['label']; ?></label>
		<?php econozel_taxonomy_meta_field_input( wp_parse_args( $args, array(
			'taxonomy' => $taxonomy,
			'meta_key' => $meta_key
		) ) ); ?>

		<?php if ( ! empty( $args['description'] ) ) : ?>

		<p><?php echo $args['description']; ?></p>

		<?php endif; ?>
	</div>

	<?php endforeach;
}

/**
 * Output taxonomy meta fields in the Edit Term screen
 *
 * @since 1.0.0
 *
 * @param WP_Term $term Term object
 * @param string $taxonomy Taxonomy name
 */
function econozel_taxonomy_meta_edit_fields( $term, $taxonomy ) {

	// Get non-media meta fields
	$fields = econozel_get_taxonomy_meta( $taxonomy );
	$fields = wp_list_filter( $fields, array( 'type' => 'media' ), 'NOT' );

	// Bail when the taxonomy has no meta fields
	if ( ! $fields )
		return;

	// Walk fields to output
	foreach ( $fields as $meta_key => $args ) : ?>

	<tr class="form-field term-<?php echo $meta_key; ?>-wrap">
		<th scope="row">
			<label for="<?php echo "{$taxonomy}_{$meta_key}"; ?>"><?php echo $args['label']; ?></label>
		</th>
		<td>
			<?php econozel_taxonomy_meta_field_input( wp_parse_args( $args, array(
				'taxonomy' => $taxonomy,
				'meta_key' => $meta_key,
				'term'     => $term
			) ) ); ?>

			<?php if ( ! empty( $args['description'] ) ) : ?>

			<p class="description"><?php echo $args['description']; ?></p>

			<?php endif; ?>
		</td>
	</tr>

	<?php endforeach;
}

/**
 * Display a single taxonomy meta input field
 *
 * @since 1.0.0
 *
 * @param array $args Meta field arguments
 */
function econozel_taxonomy_meta_field_input( $args = array() ) {

	// Get field details
	$taxonomy = $args['taxonomy'];
	$meta_key = $args['meta_key'];
	$term_id  = isset( $args['term'] ) ? $args['term']->term_id : 0;

	// Define field keys
	$field     = '';
	$field_key = "{$taxonomy}-{$meta_key}";
	$nonce_key = $field_key . '_nonce';

	// Define field attributes
	$attrs         = isset( $args['attrs'] ) ? $args['attrs'] : array();
	$attrs['id']   = isset( $attrs['id'] ) ? $attrs['id'] : $field_key;
	$attrs['name'] = $field_key;

	switch ( $args['type'] ) {

		// Number field
		case 'number':

			// Define value attribute
			if ( $term_id ) {
				$attrs['value'] = get_term_meta( $term_id, $meta_key, true );
			}

			// Setup field markup
			$field = '<input type="number" %s/>';

			break;

		// Dropdown or multiselect field
		case 'select':

			// Select multiple?
			$multiple = isset( $args['multiple'] ) && $args['multiple'];

			// Get meta value
			$value = get_term_meta( $term_id, $meta_key, ! $multiple );

			// Setup field markup
			$field = '<select %s>';

			// Define no-option
			if ( isset( $args['show_option_none'] ) && $args['show_option_none'] ) {
				$field .= sprintf( '<option value="0">%s</option>',
					is_string( $args['show_option_none'] ) ? esc_html( $args['show_option_none'] ) : esc_html__( '&mdash; Select &mdash;' )
				);
			}

			// Get input options from callable or array
			$options = is_callable( $args['options'] ) ? call_user_func( $args['options'] ) : $args['options'];

			// Walk the options
			foreach ( (array) $options as $val => $label ) {

				// Determine whether this value is selected
				$selected = $multiple ? in_array( $val, $value ) : $val == $value;

				// Define option
				$field .= sprintf( '<option value="%s"%s>%s</option>', esc_attr( $val ), selected( $selected, true, false ), esc_html( $label ) );
			}

			$field .= '</select>';

			break;

		// Upload field
		case 'file':

			// Load upload library
			break;

		default:
			$field = apply_filters( 'econozel_taxonomy_meta_field_input', $field, $args, $attrs );
			break;
	}

	// Field is defined
	if ( ! empty( $field ) ) {

		// Output field, parse attributes
		printf( $field, implode( ' ', array_map(
			function( $v, $k ) { return sprintf( '%s="%s"', $k, $v ); },
			$attrs,
			array_keys( $attrs )
		) ) );

		// Output field specific nonce
		wp_nonce_field( $nonce_key, $nonce_key );
	}
}

/**
 * Save meta value fields for the given term
 *
 * @since 1.0.0
 *
 * @param WP_Term $term Term object
 * @param string $meta_key Meta key
 * @param mixed $value Meta value to save
 * @return bool Update success
 */
function econozel_taxonomy_meta_save_fields( $term_id ) {

	// Get the term and taxonomy
	$term = get_term( $term_id );
	$tax  = get_taxonomy( $term->taxonomy );

	// Bail when user is not capable
	if ( ! current_user_can( $tax->cap->edit_terms ) )
		return;

	// Get taxonomy meta fields
	$fields = econozel_get_taxonomy_meta( $term->taxonomy );

	// Walk fields
	foreach ( $fields as $meta_key => $args ) {

		// Define field keys
		$field_key = "{$term->taxonomy}-{$meta_key}";
		$nonce_key = $field_key . '_nonce';

		// Skip when nonce does not verify
		if ( ! isset( $_POST[ $nonce_key ] ) || ! wp_verify_nonce( $_POST[ $nonce_key ], $nonce_key ) )
			continue;

		// Get posted value
		if ( isset( $_POST[ $field_key ] ) ) {
			$value = $_POST[ $field_key ];

		// Set value for unchecked checkboxes
		} elseif ( 'checkbox' === $args['type'] ) {
			$value = 0;
		}

		// Update Edition meta
		update_term_meta( $term->term_id, $meta_key, $value );
	}
}

/**
 * Modify the taxonomy admin list table columns
 *
 * @since 1.0.0
 *
 * @param array $columns List table columns
 * @return array List table columns
 */
function econozel_taxonomy_meta_admin_columns( $columns ) {

	// When on the taxonomy page
	if ( function_exists( 'get_current_screen' ) || ! empty( get_current_screen()->taxonomy ) ) {

		// Walk all fields
		foreach ( econozel_get_taxonomy_meta( get_current_screen()->taxonomy ) as $meta_key => $args ) {

			// Skip when no column
			if ( ! isset( $args['admin_column_cb'] ) || empty( $args['admin_column_cb'] ) )
				continue;

			// Add column
			$columns[ $meta_key ] = $args['label'];
		}
	}

	return $columns;
}

/**
 * Modify the taxonomy admin list table column content
 *
 * @since 1.0.0
 *
 * @param string $content Column content
 * @param string $column Column name
 * @param int $term_id Term ID
 * @return stringn Column content
 */
function econozel_taxonomy_meta_admin_column_content( $content, $column, $term_id ) {

	// Get the meta field
	$meta = econozel_get_taxonomy_meta( get_current_screen()->taxonomy, $column );
	if ( $meta && 'media' !== $meta['type'] ) {

		// Get meta value
		$value = get_term_meta( $term_id, $column, true );

		// Check meta type
		switch ( $meta['type'] ) {
			case 'select' :
				if ( isset( $meta['options'][ $value ] ) ) {
					$content = $meta['options'][ $value ];
					break;
				}

			// Display value by default
			case 'number' :
			default :
				$content = $value;
				break;
		}

		// Append content for inline editing
		if ( isset( $meta['inline_edit'] ) && $meta['inline_edit'] ) {
			$content .= sprintf( '<span id="%s" class="hidden">%s</span>', "inline_{$term_id}-{$column}", get_term_meta( $term_id, $column, true ) );
		}
	}

	return $content;
}

/**
 * Output the taxonomy meta inline edit field
 *
 * @since 1.0.0
 *
 * @param string $column Column name
 * @param string $screen_type Screen type name
 * @param string $taxonomy Taxonomy name
 */
function econozel_taxonomy_meta_inline_edit( $column, $screen_type, $taxonomy = '' ) {

	// Bail when we're not editing terms
	if ( 'edit-tags' != $screen_type )
		return;

	// Bail when the taxonomy's meta field does not exist
	if ( ! $meta = econozel_get_taxonomy_meta( $taxonomy, $column ) )
		return;

	// Bail when column does not apply
	if ( empty( $meta['admin_column_cb'] ) || empty( $meta['inline_edit'] ) )
		return;

	?>

	<fieldset>
		<div class="inline-edit-col">
		<label>
			<span class="title"><?php echo $meta['label']; ?></span>
			<span class="input-text-wrap"><?php econozel_taxonomy_meta_field_input( $meta ); ?></span>
		</label>
		</div>
	</fieldset>

	<?php
}

/**
 * Register media term meta fields
 *
 * @since 1.0.0
 *
 * @param array|string $fields_or_taxonomy Taxonomy meta fields or taxonomy name
 * @return array Remaining non-media meta fields
 */
function econozel_register_taxonomy_media_meta( $fields_or_taxonomy ) {
	global $wp_taxonomies;

	// Define local variables
	$plugin   = econozel();
	$taxonomy = false;
	$fields   = array();

	// Set term media collection
	if ( ! isset( $plugin->term_media ) ) {
		$plugin->term_media = new stdClass;
	}

	// Default to the taxonomy's meta fields
	if ( is_array( $fields_or_taxonomy ) ) {
		$fields = $fields_or_taxonomy;
	} elseif ( is_string( $fields_or_taxonomy ) && taxonomy_exists( $fields_or_taxonomy ) ) {
		$taxonomhy = $fields_or_taxonomy;
		$fields    = econozel_get_taxonomy_meta( $fields_or_taxonomy );
	}

	// Walk media metas
	foreach ( wp_list_filter( $fields, array( 'type' => 'media' ) ) as $meta_key => $args ) {

		// Bail when the meta key is invalid or already registered
		if ( ! $meta_key || isset( $plugin->term_media->{$meta_key} ) )
			return false;

		// Require term meta classes
		require_once( $plugin->includes_dir . 'classes/class-wp-term-meta-ui.php' );
		require_once( $plugin->includes_dir . 'classes/class-wp-term-media.php'   );

		// Define term meta key
		$args['meta_key'] = $meta_key;

		// Register new `WP_Term_Media`
		$plugin->term_media->{$meta_key} = new WP_Term_Media( $plugin->file, $args );
	}
}

/** Query *********************************************************************/

/**
 * Modify the default term query arguments
 *
 * Filter available since WP 4.4.
 *
 * @since 1.0.0
 *
 * @param array $args Default query args
 * @param array $taxonomies Queried taxonomies
 * @return array Default query args
 */
function econozel_query_terms_default_args( $args, $taxonomies ) {

	// When querying a single taxonomy
	if ( count( $taxonomies ) == 1 ) {
		switch ( $taxonomies[0] ) {

			// When querying Editions
			case econozel_get_edition_tax_id() :

				// Order by issue meta value, list latest issue first
				$args['orderby'] = 'meta_issue';
				$args['order']   = 'DESC';
				$args['meta_query'] = array(
					'meta_issue' => array(
						'key'     => 'issue',
						'compare' => 'EXISTS',
						'type'    => 'NUMERIC'
					)
				);

				break;

			// When querying Volumes
			case econozel_get_volume_tax_id() :

				// Order by slug descending, list latest Volume first
				$args['orderby'] = 'slug';
				$args['order']   = 'DESC';

				break;
		}
	}

	return $args;
}

/**
 * Modify the terms query clauses
 *
 * @since 1.0.0
 *
 * @global WPDB $wpdb
 *
 * @param array $clauses Query clauses
 * @param array $taxonomies Queried taxonomies
 * @param array $args Query arguments
 * @return array Query clauses
 */
function econozel_query_terms_clauses( $clauses, $taxonomies, $args ) {
	global $wpdb;

	// When querying Editions ...
	if ( array( econozel_get_edition_tax_id() ) == $taxonomies ) {

		// ... by single Volume
		if ( isset( $args['econozel_volume'] ) ) {

			// Get Volume
			$volume = econozel_get_volume( $args['econozel_volume'] );

			/**
			 * Setup tax query object to query by single Volume
			 */
			$tax_query = new WP_Tax_Query( array(
				array(
					'taxonomy' => econozel_get_volume_tax_id(),
					'terms'    => array( $volume ? $volume->term_id : 0 ),
					'field'    => 'term_id'
				)
			) );

			// Get tax query SQL. 't' is the query's terms table alias
			$tax_clauses = $tax_query->get_sql( 't', 'term_id' );

			// Append tax clauses
			$clauses['join']  .= $tax_clauses['join'];
			$clauses['where'] .= $tax_clauses['where'];

		// ... by all Volumes, ordering by issue
		} elseif ( 'meta_issue' === $args['orderby'] ) {

			/**
			 * Append clauses to join on Volume term relationships
			 *
			 * This is done not through `WP_Tax_Query` because it lacks alternate
			 * table aliases when generating single level tax queries.
			 */
			$clauses['join']  .= " INNER JOIN {$wpdb->term_relationships} volumes_tr ON ( t.term_id = volumes_tr.object_id )";
			$clauses['join']  .= " INNER JOIN {$wpdb->term_taxonomy} volumes ON ( volumes_tr.term_taxonomy_id = volumes.term_taxonomy_id )";
			$clauses['where'] .= $wpdb->prepare( " AND ( volumes.taxonomy = %s )", econozel_get_volume_tax_id() );

			// Get all Volumes ID's, properly ordered
			$volumes = get_terms( econozel_get_volume_tax_id(), array( 'fields' => 'ids', 'hide_empty' => false ) );
			$volumes = ! empty( $volumes ) ? implode( ', ', $volumes ) : '0';

			/**
			 * Change order to list the latest issue in the latest Volume first
			 * Thus: 1. Volume order DESC 2. Edition order DESC
			 */
			$clauses['orderby'] = str_replace( 'ORDER BY ', "ORDER BY FIELD( volumes_tr.term_taxonomy_id, $volumes ), ", $clauses['orderby'] );
			$clauses['order']   = 'DESC';
		}

	// When querying Volumes ...
	} elseif ( array( econozel_get_volume_tax_id() ) == $taxonomies ) {

		// ... base Volume emptiness on its Editions
		if ( $args['hide_empty'] ) {

			// Require Volume to have at least one Edition with Articles
			$clauses['where'] .= $wpdb->prepare( " AND EXISTS (
				SELECT 1
				FROM {$wpdb->term_taxonomy} editions
				INNER JOIN {$wpdb->term_relationships} editions_tr ON ( editions.term_taxonomy_id = editions_tr.object_id )
				WHERE ( editions.taxonomy = %s ) AND ( editions_tr.term_taxonomy_id = t.term_id ) AND editions.count > 0 )",
				econozel_get_edition_tax_id()
			);
		}

		// ... order by slug numerically
		if ( 'slug' === $args['orderby'] ) {
			$clauses['orderby'] = str_replace( 't.slug', 'CAST(t.slug AS SIGNED)', $clauses['orderby'] );
		}
	}

	return $clauses;
}

/**
 * Return the total found rows for the term query arguments
 *
 * @since 1.0.0
 *
 * @param array $query_args Original term query arguments.
 * @return int Total found rows
 */
function econozel_query_terms_found_rows( $query_args ) {

	// Remove paging arguments
	unset( $query_args['offset'], $query_args['paged'] );

	// Define count query args
	$query_args['fields'] = 'count';
	$query_args['number'] = -1;

	// Run count query
	$count = get_terms( $query_args['taxonomy'], $query_args );

	return (int) $count;
}

/** Misc **********************************************************************/

/**
 * Modify the displayed label of the taxonomy dropdown list item
 *
 * @since 1.0.0
 *
 * @param string $label List item label
 * @param WP_Term|null $term Term object or nothing.
 * @return string List item label
 */
function econozel_list_cats( $label, $term = null ) {

	// When this is a term's list item
	if ( $term instanceof WP_Term ) {
		switch ( $term->taxonomy ) {

			// Edition
			case econozel_get_edition_tax_id() :
				$label = econozel_get_edition_label( $term );
				break;

			// Volume
			case econozel_get_volume_tax_id() :
				$label = econozel_get_volume_title( $term );
				break;
		}
	}

	return $label;
}

/**
 * Modify the taxonomy dropdown HTML
 *
 * @since 1.0.0
 *
 * @param string $dd Dropdown HTML
 * @param array $args Dropdown arguments
 * @return string Dropdown HTML
 */
function econozel_dropdown_cats( $dd, $args ) {

	/// Edition: Add option for the current Edition
	if ( econozel_get_edition_tax_id() == $args['taxonomy'] && $args['show_option_current'] ) {

		// Define option for the current Edition
		$selected = selected( 'current', $r['selected'], false );
		$option   = "\t<option value='current'$selected>" . esc_html__( 'Current Edition', 'econozel' ) . "</option>\n";

		// Insert new option before the first term item
		$dd  = substr_replace( $dd, $option, strpos( $dd, "\t<option class=\"level-" ), 0 );
	}

	return $dd;
}

/**
 * Modify the term link for the plugin's taxonomies
 *
 * @since 1.0.0
 *
 * @param string $link Term link
 * @param WP_Term $term Term object
 * @param string $taxonomy Taxonomy name
 * @return string Term link
 */
function econozel_term_link( $link, $term, $taxonomy ) {

	switch ( $taxonomy ) {

		// Edition
		case econozel_get_edition_tax_id() :

			// Get Edition's Volume and issue
			$volume = econozel_get_edition_volume( $term );
			$issue  = econozel_get_edition_issue( $term );

			if ( $volume && $issue ) {
				$link = user_trailingslashit( trailingslashit( econozel_get_volume_url( $volume ) ) . $issue );
			}
			break;

		// Volume
		case econozel_get_volume_tax_id() :
			$link = home_url( user_trailingslashit( trailingslashit( econozel_get_volume_slug() ) . $term->slug ) );
			break;
	}

	return $link;
}

/** Template Tags *************************************************************/

/**
 * Return the taxonomy title
 *
 * @since 1.0.0
 *
 * @param string $taxonomy Taxonomy name
 * @return string Taxonomy title
 */
function econozel_taxonomy_title( $taxonomy = '' ) {
	$title = '';

	if ( taxonomy_exists( $taxonomy ) ) {
		$title = get_taxonomy( $taxonomy )->labels->name;
	}

	return $title;
}

/**
 * Display or retrieve the HTML dropdown list of Volumes
 *
 * @see wp_dropdown_categories()
 *
 * @since 1.0.0
 *
 * @param array $args Arguments for {@see wp_dropdown_categories()}
 * @return string Volumes HTML dropdown element
 */
function econozel_dropdown_volumes( $args = array() ) {
	return wp_dropdown_categories( wp_parse_args( $args, array(
		'taxonomy' => econozel_get_volume_tax_id(),
		/**
		 * Ordering arguments, see {@see econozel_query_terms_default_args()}.
		 */
		'orderby'  => 'slug',
		'order'    => 'DESC'
	) ) );
}

/**
 * Display or retrieve the HTML dropdown list of Editions
 *
 * @see wp_dropdown_categories()
 *
 * @since 1.0.0
 *
 * @param array $args Arguments for {@see wp_dropdown_categories()}
 * @return string Editions HTML dropdown element
 */
function econozel_dropdown_editions( $args = array() ) {
	return wp_dropdown_categories( wp_parse_args( $args, array(
		'taxonomy'            => econozel_get_edition_tax_id(),
		'show_option_none'    => esc_html__( '&mdash; No Edition &mdash;', 'econozel' ),
		'show_option_current' => false,
		/**
		 * Ordering arguments, see {@see econozel_query_terms_default_args()}.
		 */
		'orderby'    => 'meta_issue',
		'order'      => 'DESC',
		'meta_query' => array(
			'meta_issue' => array(
				'key'     => 'issue',
				'compare' => 'EXISTS'
			)
		)
	) ) );
}
