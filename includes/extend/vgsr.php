<?php

/**
 * Econozel Extension for VGSR
 *
 * @package Econozel
 * @subpackage VGSR
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Econozel_VGSR' ) ) :
/**
 * The Econozel VGSR class
 *
 * @since 1.0.0
 */
class Econozel_VGSR {

	/**
	 * Setup this class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Define default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {

		// Access
		add_filter( 'econozel_check_access',       array( $this, 'check_access'       ), 10, 2 );
		add_filter( 'econozel_check_admin_access', array( $this, 'check_admin_access' ), 10, 2 );

		// Authors
		add_filter( 'wp_dropdown_users_args', array( $this, 'dropdown_users_args' ), 20, 2 ); // Since WP 4.4
		add_filter( 'pre_user_query',         array( $this, 'pre_user_query'      ), 10    );
	}

	/** Access ********************************************************************/

	/**
	 * Modify whether the current user has access to Econozel pages
	 *
	 * @since 1.0.0
	 *
	 * @param bool $access The user has access
	 * @param int $user_id User ID
	 * @return bool The user has access
	 */
	public function check_access( $access, $user_id ) {

		// Allow access for vgsr users
		if ( ! $access ) {
			$access = is_user_vgsr( $user_id );
		}

		return $access;
	}

	/**
	 * Modify whether the current user has access to Econozel admin
	 *
	 * @since 1.0.0
	 *
	 * @param bool $access The user has access
	 * @param int $user_id User ID
	 * @return bool The user has access
	 */
	public function check_admin_access( $access, $user_id ) {

		// Allow admin access for leden
		if ( ! $access ) {
			$access = is_user_lid( $user_id );
		}

		return $access;
	}

	/** Authors *******************************************************************/

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

		// Econozel user dropdown
		if ( isset( $args['econozel'] ) && $args['econozel'] ) {

			// Enable vgsr users to be authors
			$query_args['vgsr'] = true;
		}

		return $query_args;
	}

	/**
	 * Modify the user query
	 *
	 * @since 1.0.0
	 *
	 * @uses WPDB $wpdb
	 *
	 * @param WP_User_Query $users_query
	 */
	public function pre_user_query( $users_query ) {
		global $wpdb;

		// Querying for Econozel authors and vgsr users
		if ( $users_query->get( 'vgsr' ) && $users_query->get( 'econozel' ) && econozel_get_editor_role() === $users_query->get( 'role' ) ) {

			/**
			 * When querying Econozel authors, we'd like to get both vgsr users AND
			 * Econozel Editors. To do this, both query requirements need to be combined
			 * with an OR statement in the WHERE clause.
			 *
			 * The original role statement from `WP_User_Query` is replaced by itself and 
			 * extended by the statement for vgsr users. This is done by removing the original
			 * statement for vgsr users and inserting it after the role statement, wrapping
			 * both in their own OR statement.
			 */

			/**
			 * Refetch vgsr user query clauses. Move its WHERE clause to cooperate
			 * with the role statement in an OR statement.
			 *
			 * @see vgsr_pre_user_query()
			 */
			$sql_clauses = apply_filters( 'vgsr_pre_user_query', array( 'join' => '', 'where' => '' ), $users_query );

			// Append WHERE statement
			if ( ! empty( $sql_clauses['where'] ) ) {
				$where = preg_replace( '/^\s*AND\s*/', '', $sql_clauses['where'] );

				// Remove original vgsr statement in the WHERE clause
				$users_query->query_where = str_replace( " AND $where", '', $users_query->query_where );

				// Get the blog to query for
				$blog_id = $users_query->get( 'blog_id' ) ? (int) $users_query->get( 'blog_id' ) : 0;

				/**
				 * Refetch role meta query as is done in WP_User_Query.
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
				$role_clauses = $role_query->get_sql( 'user', $wpdb->users, 'ID', $users_query );
				$role_where   = trim( trim( preg_replace( '/^\s*AND\s*/', '', $role_clauses['where'] ), '()' ) );

				// Minimize results for usermeta role join for vgsr user matches. Capability meta always occurs once.
				$where = $wpdb->prepare( "( {$where} AND {$wpdb->usermeta}.meta_key = %s )", $wpdb->get_blog_prefix( $blog_id ) . 'capabilities' );

				// Replace role statement in the WHERE clause
				$users_query->query_where = str_replace( $role_where, "( {$role_where} OR {$where} )", $users_query->query_where );
			}
		}
	}
}

/**
 * Setup the extension logic for VGSR
 *
 * @since 1.0.0
 *
 * @uses Econozel_VGSR
 */
function econozel_vgsr() {
	econozel()->extend->vgsr = new Econozel_VGSR;
}

endif; // class_exists
