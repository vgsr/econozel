<?php

/**
 * Econozel Taxonomy Functions
 * 
 * @package Econozel
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

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

	// Get all meta. Structured as `array( 'taxonomy' => array( 'meta_key' => array( args ) ) )`
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
 * @param string $object_type Object name
 * @param array $args Taxonomy registration arguments
 */
function econozel_register_taxonomy_meta( $taxonomy, $object_type, $args ) {

	// Bail when the taxonomy has no meta fields
	if ( ! $fields = econozel_get_taxonomy_meta( $taxonomy ) )
		return;

	// Display add meta fields
	add_action( "{$taxonomy}_add_form_fields", 'econozel_taxonomy_meta_display_add_fields' );

	// Display edit meta fields
	add_action( "{$taxonomy}_edit_form_fields", 'econozel_taxonomy_meta_display_edit_fields', 10, 2 );

	// Save meta fields on create and edit
	add_action( "created_{$taxonomy}", 'econozel_taxonomy_meta_save_fields' );
	add_action( "edited_{$taxonomy}",  'econozel_taxonomy_meta_save_fields' );

	// Admin columns
	add_filter( "manage_edit-{$taxonomy}_columns", 'econozel_taxonomy_admin_columns' );

	// Inline editing
	add_action( 'quick_edit_custom_box', 'econozel_taxonomy_inline_edit', 10, 3 );

	// Walk fields
	foreach ( $fields as $meta_key => $args ) {

		// Sanitize meta value on updates
		add_filter( "sanitize_term_meta_{$meta_key}", $args['sanitize_cb'] );

		// Admin column
		if ( isset( $args['admin_column_cb'] ) && $args['admin_column_cb'] ) {
			add_action( "manage_{$taxonomy}_custom_column", is_callable( $args['admin_column_cb'] )
				? $args['admin_column_cb']
				: 'econozel_taxonomy_admin_column_content',
				10, 3
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
function econozel_taxonomy_meta_display_add_fields( $taxonomy ) {

	// Bail when the taxonomy has no meta fields
	if ( ! $fields = econozel_get_taxonomy_meta( $taxonomy ) )
		return;

	// Walk fields to output
	foreach ( $fields as $meta_key => $args ) : ?>

	<div class="form-field term-<?php echo $meta_key; ?>">
		<label for="<?php echo "{$taxonomy}_{$meta_key}"; ?>"><?php echo $args['label']; ?></label>
		<?php econozel_taxonomy_meta_display_single_field( wp_parse_args( $args, array(
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
function econozel_taxonomy_meta_display_edit_fields( $term, $taxonomy ) {

	// Bail when the taxonomy has no meta fields
	if ( ! $fields = econozel_get_taxonomy_meta( $taxonomy ) )
		return;

	// Walk fields to output
	foreach ( $fields as $meta_key => $args ) : ?>

	<tr class="form-field term-<?php echo $meta_key; ?>-wrap">
		<th scope="row">
			<label for="<?php echo "{$taxonomy}_{$meta_key}"; ?>"><?php echo $args['label']; ?></label>
		</th>
		<td>
			<?php econozel_taxonomy_meta_display_single_field( wp_parse_args( $args, array(
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
function econozel_taxonomy_meta_display_single_field( $args = array() ) {

	// Get field details
	$taxonomy = $args['taxonomy'];
	$meta_key = $args['meta_key'];
	$term_id  = isset( $args['term'] ) ? $args['term']->term_id : 0;

	// Define field keys
	$field     = '';
	$field_key = "{$taxonomy}_{$meta_key}";
	$nonce_key = $field_key . '_nonce';

	// Define field attributes
	$attrs         = isset( $args['attrs'] ) ? $args['attrs'] : array();
	$attrs['name'] = $field_key;

	switch ( $args['type'] ) {
		case 'number':

			// Define value attribute
			if ( $term_id ) {
				$attrs['value'] = get_term_meta( $term_id, $meta_key, true );
			}

			// Setup field markup
			$field = '<input type="number" %s/>';

			break;
		case 'file':
			// Load upload library
			break;
		default:
			$field = apply_filters( 'econozel_taxonomy_meta_display_single_field', $field, $args, $attrs );
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
		$field_key    = "{$term->taxonomy}_{$meta_key}";
		$nonce_key    = $field_key . '_nonce';

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
function econozel_taxonomy_admin_columns( $columns ) {

	// When on the taxonomy page
	if ( function_exists( 'get_current_screen' ) || ! empty( get_current_screen()->taxonomy ) ) {

		// Walk all fields
		foreach ( econozel_get_taxonomy_meta( get_current_screen()->taxonomy ) as $meta_key => $args ) {

			// Skip when no column
			if ( ! isset( $args['admin_column_cb'] ) )
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
function econozel_taxonomy_admin_column_content( $content, $column, $term_id ) {

	// Get the meta field
	if ( $meta = econozel_get_taxonomy_meta( get_current_screen()->taxonomy, $column ) ) {
		switch ( $meta['type'] ) {
			case 'number' :
			default :
				$content = get_term_meta( $term_id, $column, true );
				break;
		}
	}

	return $content;
}

/**
 * Output the taxonomy meta inline edit field
 *
 * @since 1.0.0
 *
 * @todo Apply js update when selecting row, see wp-admin/js/inline-edit-tax.js
 *
 * @param string $column Column name
 * @param string $screen_type Screen type name
 * @param string $taxonomy Taxonomy name
 */
function econozel_taxonomy_inline_edit( $column, $screen_type, $taxonomy = '' ) {

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
			<span class="input-text-wrap"><?php econozel_taxonomy_meta_display_single_field( $meta ); ?></span>
		</label>
		</div>
	</fieldset>

	<?php
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
						'compare' => 'EXISTS'
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
 * @param array $clauses Query clauses
 * @param array $taxonomies Queried taxonomies
 * @param array $args Query arguments
 * @return array Query clauses
 */
function econozel_query_terms_clauses( $clauses, $taxonomies, $args ) {

	// When querying Editions ...
	if ( array( econozel_get_edition_tax_id() ) == $taxonomies ) {

		// ... by single Volume
		if ( isset( $args['econozel_volume'] ) ) {

			/**
			 * Setup tax query object to query by single Volume
			 */
			$tax_query = new WP_Tax_Query( array(
				array(
					'taxonomy' => econozel_get_volume_tax_id(),
					'terms'    => array( (int) $args['econozel_volume'] ),
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
			global $wpdb;

			/**
			 * Append clauses to join on Volume term relationships
			 *
			 * This is done not through `WP_Tax_Query` because it lacks alternate
			 * table aliases when generating single level tax queries.
			 */
			$clauses['join']  .= " INNER JOIN {$wpdb->term_relationships} tr ON ( t.term_id = tr.object_id )";
			$clauses['join']  .= " INNER JOIN {$wpdb->term_taxonomy} tt2 ON ( tr.term_taxonomy_id = tt2.term_taxonomy_id )";
			$clauses['where'] .= $wpdb->prepare( " AND ( tt2.taxonomy = %s )", econozel_get_volume_tax_id() );

			// Get all Volumes ID's, properly ordered
			$volumes = get_terms( econozel_get_volume_tax_id(), array( 'fields' => 'ids' ) );
			$volumes = ! empty( $volumes ) ? implode( ', ', $volumes ) : '0';

			/**
			 * Change order to list the latest issue in the latest Volume first
			 * Thus: 1. Volume order DESC 2. Edition order DESC
			 */
			$clauses['orderby'] = str_replace( 'ORDER BY ', "ORDER BY FIELD( tr.term_taxonomy_id, $volumes ), ", $clauses['orderby'] );
			$clauses['order']   = 'DESC';
		}

	// When querying Volumes ...
	} elseif ( array( econozel_get_volume_tax_id() ) == $taxonomies ) {

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
