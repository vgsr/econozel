<?php

/**
 * Econozel User Functions
 *
 * @package Econozel
 * @subpackage Users
 */

// Exit if accesssed directly
defined( 'ABSPATH' ) || exit;

/** Authors *******************************************************************/

/**
 * Modify the vgsr user query to include Econozel authors
 *
 * @since 1.0.0
 *
 * @param array $sql_clauses SQL clauses
 * @param WP_User_Query $query
 * @return array SQL clauses
 */
function econozel_vgsr_pre_user_query( $sql_clauses, $query ) {

	// When querying Econozel authors
	if ( $query->get( 'econozel' ) ) {
		global $wpdb;

		// Get the blog to query for
		$blog_id = $query->get( 'blog_id' ) ? (int) $query->get( 'blog_id' ) : get_current_blog_id();

		/**
		 * Setup role meta query.
		 *
		 * @see WP_User_Query which uses meta queries for roles.
		 */
		$role_query = new WP_Meta_Query( array(
			array(
				'key'     => $wpdb->get_blog_prefix( $blog_id ) . 'capabilities',
				'value'   => '"' . econozel_get_editor_role() . '"',
				'compare' => 'LIKE'
			)
		) );
		$role_clauses = $role_query->get_sql( 'user', $wpdb->users, 'ID', $query );

		// Append JOIN statement
		$sql_clauses['join'] .= $role_clauses['join'];

		// Rewrite WHERE statement to query either vgsr users or Econozel Editors
		$where_vgsr = preg_replace( '/^\s*AND\s*/', '', $sql_clauses['where'] );
		$where_role = preg_replace( '/^\s*AND\s*/', '', $role_clauses['where'] );
		$sql_clauses['where'] = "( {$where_vgsr} OR {$where_role} )";
	}

	return $sql_clauses;
}
