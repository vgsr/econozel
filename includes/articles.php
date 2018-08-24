<?php

/**
 * Econozel Article Functions
 * 
 * @package Econozel
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Post Type *****************************************************************/

/**
 * Return the econozel Article post type
 *
 * @since 1.0.0
 *
 * @return string Post type name
 */
function econozel_get_article_post_type() {
	return 'econozel';
}

/**
 * Return the labels for the Article post type
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_article_post_type_labels'
 * @return array Article post type labels
 */
function econozel_get_article_post_type_labels() {
	return apply_filters( 'econozel_get_article_post_type_labels', array(
		'name'                  => __( 'Econozel Articles',          'econozel' ),
		'menu_name'             => __( 'Econozel',                   'econozel' ),
		'singular_name'         => __( 'Econozel Article',           'econozel' ),
		'all_items'             => __( 'All Articles',               'econozel' ),
		'add_new'               => __( 'New Article',                'econozel' ),
		'add_new_item'          => __( 'Create New Article',         'econozel' ),
		'edit'                  => __( 'Edit',                       'econozel' ),
		'edit_item'             => __( 'Edit Article',               'econozel' ),
		'new_item'              => __( 'New Article',                'econozel' ),
		'view'                  => __( 'View Article',               'econozel' ),
		'view_item'             => __( 'View Article',               'econozel' ),
		'view_items'            => __( 'View Articles',              'econozel' ), // Since WP 4.7
		'search_items'          => __( 'Search Articles',            'econozel' ),
		'not_found'             => __( 'No articles found',          'econozel' ),
		'not_found_in_trash'    => __( 'No articles found in Trash', 'econozel' ),
		'insert_into_item'      => __( 'Insert into article',        'econozel' ),
		'uploaded_to_this_item' => __( 'Uploaded to this article',   'econozel' ),
		'filter_items_list'     => __( 'Filter articles list',       'econozel' ),
		'items_list_navigation' => __( 'Articles list navigation',   'econozel' ),
		'items_list'            => __( 'Articles list',              'econozel' ),
	) );
}

/**
 * Return the Article post type rewrite settings
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_article_post_type_rewrite'
 * @return array Article post type support
 */
function econozel_get_article_post_type_rewrite() {
	return apply_filters( 'econozel_get_article_post_type_rewrite', array(
		'slug'       => econozel_get_article_slug(),
		'with_front' => false
	) );
}

/**
 * Return an array of features the Article post type supports
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_article_post_type_supports'
 * @return array Article post type support
 */
function econozel_get_article_post_type_supports() {
	return apply_filters( 'econozel_get_article_post_type_supports', array(
		'title',
		'author',
		'editor',
		'excerpt',
		'comments',
		'thumbnail',
		'page-attributes' // For custom menu_order
	) );
}

/** Query *********************************************************************/

/**
 * Setup and run the Articles query
 *
 * @since 1.0.0
 *
 * @param array $args Query arguments.
 * @return bool Has the query returned any results?
 */
function econozel_query_articles( $args = array() ) {

	// Get query object
	$query = econozel()->article_query;

	// Reset query defaults
	$query->in_the_loop  = false;
	$query->current_post = -1;
	$query->post_count   = 0;
	$query->post         = null;
	$query->posts        = array();

	// Define query args
	$query_args = wp_parse_args( $args, array(
		'econozel_edition' => econozel_get_edition_id(),
		'post_type'        => econozel_get_article_post_type(),
		'posts_per_page'   => -1,
		'paged'            => econozel_get_paged(),
		'fields'           => 'all'
	) );

	// Run query to get the posts
	$query->query( $query_args );

	// Return whether the query has returned results
	return $query->have_posts();
}

/**
 * Return whether the query has Articles to loop over
 *
 * @since 1.0.0
 *
 * @return bool Query has Articles
 */
function econozel_has_articles() {

	// Has query a next post?
	$has_next = econozel()->article_query->have_posts();

	// Clean up after ourselves
	if ( ! $has_next ) {
		wp_reset_postdata();
	}

	return $has_next;
}

/**
 * Setup next Article in the current loop
 *
 * @since 1.0.0
 */
function econozel_the_article() {
	econozel()->article_query->the_post();
}

/**
 * Return whether we're in the Article loop
 *
 * @since 1.0.0
 *
 * @return bool Are we in the Article loop?
 */
function econozel_in_the_article_loop() {
	return econozel()->article_query->in_the_loop;
}

/** Template ******************************************************************/

/**
 * Return the Article's post
 *
 * @since 1.0.0
 *
 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current post.
 * @return WP_Post|bool Post object when it is an Article, else False.
 */
function econozel_get_article( $article = 0 ) {

	// Get the post
	$article = get_post( $article );

	// Verify Aritcle post type
	if ( ! $article || econozel_get_article_post_type() != $article->post_type ) {
		$article = false;
	}

	return $article;
}

/**
 * Return the Article's Volume
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_article_volume'
 *
 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current post.
 * @param bool $object Optional. Whether to return term object or ID. Defaults to ID.
 * @return WP_Term|int|bool Volume term object or ID when found, else False.
 */
function econozel_get_article_volume( $article = 0, $object = false ) {

	// Bail when Edition does not exist
	if ( ! $edition = econozel_get_article_edition( $article ) )
		return false;

	// Get the Edition's Volume
	$volume = econozel_get_edition_volume( $edition, $object );

	return apply_filters( 'econozel_get_article_volume', $volume, $article, $object );
}

/**
 * Return the Article's Edition
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_article_edition'
 *
 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current post.
 * @param bool $object Optional. Whether to return term object or ID. Defaults to ID.
 * @return WP_Term|int|bool Edition term object or ID when found, else False.
 */
function econozel_get_article_edition( $article = 0, $object = false ) {

	// Bail when post does not exist
	if ( ! $article = econozel_get_article( $article ) )
		return false;

	// Define return var
	$edition = false;

	// Get the Article's Edition terms
	$term_args = array( 'fields' => ( false === $object ) ? 'ids' : 'all' );
	$terms     = wp_get_object_terms( $article->ID, econozel_get_edition_tax_id(), $term_args );

	// Assign term ID when found
	if ( ! empty( $terms ) ) {
		$edition = $terms[0];
	}

	return apply_filters( 'econozel_get_article_edition', $edition, $article, $object );
}

/**
 * Return whether the Article has a Edition
 *
 * @since 1.0.0
 *
 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current post.
 * @return bool Article has a Edition
 */
function econozel_has_article_edition( $article = 0 ) {
	return ! empty( econozel_get_article_edition( $article ) );
}

/**
 * Output the Article Edition label
 *
 * @since 1.0.0
 *
 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current post.
 */
function econozel_the_article_edition_label( $article = 0 ) {
	echo econozel_get_article_edition_label( $article );
}

	/**
	 * Return the Article Edition label
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'econozel_get_article_edition_label'
	 *
	 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current post.
	 * @return string Article Edition label
	 */
	function econozel_get_article_edition_label( $article = 0 ) {

		// Get Article's Edition
		$edition = econozel_get_article_edition( $article );

		// Get Edition label
		$label = econozel_get_edition_label( $edition );

		return apply_filters( 'econozel_get_article_edition_label', $label, $article, $edition );
	}

/**
 * Output the current Article's ID
 *
 * @since 1.0.0
 */
function econozel_the_article_id() {
	echo econozel_get_article_id();
}

	/**
	 * Return the current Article's ID
	 *
	 * @since 1.0.0
	 *
	 * @return int|bool Article ID or False when not found.
	 */
	function econozel_get_article_id() {
		if ( $article = econozel_get_article() ) {
			return $article->ID;
		}

		return false;
	}

/**
 * Output the Article's description
 *
 * @since 1.0.0
 *
 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
 */
function econozel_the_article_description( $article = 0 ) {
	echo econozel_get_article_description( $article = 0 );
}

	/**
	 * Return the Article's description
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'econozel_get_article_description'
	 *
	 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
	 * @return string Article description
	 */
	function econozel_get_article_description( $article = 0 ) {

		// Define return value
		$description = '';

		// Get the Article
		if ( $article = econozel_get_article( $article ) ) {

			// Use the Article's excerpt
			if ( ! empty( $article->post_excerpt ) ) {
				$description = get_the_excerpt( $article );
			}

			// Default to a trimmed content
			if ( empty( $description ) ) {
				$description = wp_trim_words( $article->post_content, 55, apply_filters( 'excerpt_more', ' [&hellip;]' ) );
			}
		}

		return apply_filters( 'econozel_get_article_description', $description, $article );
	}

/**
 * Output the Article's content for the Edition's Article loop
 *
 * @since 1.0.0
 *
 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
 */
function econozel_the_article_content( $article = 0 ) {
	echo econozel_get_article_content( $article );
}

	/**
	 * Return the Article's content for the Edition's Article loop
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'econozel_get_article_content'
	 *
	 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
	 * @return string Article content
	 */
	function econozel_get_article_content( $article = 0 ) {

		// Define return var
		$content = '';

		if ( $article = econozel_get_article( $article ) ) {

			// Start output buffer
			ob_start(); ?>

			<?php econozel_the_article_description( $article ); ?>

			<p class="article-meta">
				<span class="article-author"><?php econozel_the_article_author_link( $article ); ?></span>

				<?php if ( econozel_get_article_page_number( $article ) ) : ?>
					<span class="article-page-number"><?php econozel_article_page_number( $article ); ?></span>
				<?php endif; ?>

				<?php if ( get_comments_number( $article ) ) : ?>
					<span class="comment-count"><?php comments_number(); ?></span>
				<?php endif; ?>
			</p>

			<?php

			$content = ob_get_clean();
		}

		return apply_filters( 'econozel_get_article_content', $content, $article );
	}

/**
 * Return the Article's author(s)
 *
 * Considers to return multiple authors by way of an array.
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_article_author'
 *
 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
 * @return array Article author user ID(s)
 */
function econozel_get_article_author( $article = 0 ) {

	// Define return value
	$author = array();

	// Get author from post object
	if ( $article = econozel_get_article( $article ) ) {
		$author[] = $article->post_author;

		// Get multi-author data from post meta
		foreach ( get_post_meta( $article->ID, 'post_author', false ) as $post_author ) {
			$author[] = (int) $post_author;
		}

		// Only unique values
		$author = array_unique( $author );
	}

	return (array) apply_filters( 'econozel_get_article_author', $author, $article );
}

/**
 * Output the Article's author link
 *
 * @since 1.0.0
 *
 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
 * @param bool|string $concat Optional. Whether to concatenate the links into a single string. When provided a string value,
 *                            it will be used as the item separator. Defaults to true, using {@see wp_sprintf_l()}.
 */
function econozel_the_article_author_link( $article = 0, $concat = true ) {
	echo econozel_get_article_author_link( $article, $concat );
}

	/**
	 * Return the Article's author link
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
	 * @param bool|string $concat Optional. Whether to concatenate the links into a single string. When provided a string value,
	 *                            it will be used as the item separator. Defaults to false.
	 * @return string|array Article author link(s)
	 */
	function econozel_get_article_author_link( $article = 0, $concat = false ) {

		// Define return value
		$link = array();

		// Get the Article author url
		$url = econozel_get_article_author_url( $article );

		// Loop Article author url(s)
		foreach ( $url as $user_id => $user_url ) {

			// Setup user link
			$_link = sprintf( ! empty( $user_url ) ? '<a href="%1$s">%2$s</a>' : '%2$s', esc_url( $user_url ), econozel_get_user_displayname( $user_id ) );

			// Enable plugin filtering
			$link[ $user_id ] = apply_filters( 'econozel_get_article_author_link', $_link, $user_id, $user_url, $article );
		}

		// Stringify links
		if ( false !== $concat ) {
			$link = true === $concat ? wp_sprintf_l( '%l', $link ) : implode( $concat, $link );
		}

		return $link;
	}

/**
 * Output the Article's author url
 *
 * @since 1.0.0
 *
 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
 * @param bool|string $concat Optional. Whether to concatenate the urls into a single string. When provided a string value,
 *                            it will be used as the item separator. Defaults to true, using {@see wp_sprintf_l()}.
 */
function econozel_the_article_author_url( $article = 0, $concat = true ) {
	echo econozel_get_article_author_url( $article, $concat );
}

	/**
	 * Return the Article's author url
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
	 * @param bool|string $concat Optional. Whether to concatenate the urls into a single string. When provided a string value,
	 *                            it will be used as the item separator. Defaults to false.
	 * @return string|array Article author url(s)
	 */
	function econozel_get_article_author_url( $article = 0, $concat = false ) {

		// Define return value
		$url = array();

		// Get the Article author
		$author = econozel_get_article_author( $article );

		// Loop Article author(s)
		foreach ( $author as $user_id ) {

			// Get the user
			if ( ! $user = get_userdata( $user_id ) )
				continue;

			// Enable plugin filtering
			$url[ $user->ID ] = apply_filters( 'econozel_get_article_author_url', '', $user->ID, $article );
		}

		// Stringify urls
		if ( false !== $concat ) {
			$url = true === $concat ? wp_sprintf_l( '%l', $url ) : implode( $concat, $url );
		}

		return $url;
	}

/**
 * Output the user's display name
 *
 * @since 1.0.0
 *
 * @param int $user_id User ID
 */
function econozel_the_user_displayname( $user_id ) {
	echo econozel_get_user_displayname( $user_id );
}

	/**
	 * Return the user's display name
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID
	 * @return string User display name
	 */
	function econozel_get_user_displayname( $user_id ) {

		// Define return value
		$name = '';

		// Get the user
		if ( $user = get_userdata( $user_id ) ) {
			$name = $user->display_name;
		}

		return apply_filters( 'econozel_get_user_displayname', $name, $user_id );
	}

/**
 * Output the current Article's page number in a read-friendly format
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_article_page_number'
 *
 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
 * @param bool $echo Optional. Whether to output the return value. Defaults to true.
 * @return string Article page number in read-friendly format.
 */
function econozel_article_page_number( $article = 0, $echo = true ) {

	// Get page number
	$page_number = econozel_get_article_page_number( $article );

	// Define return var
	$retval = '';

	// Pages are only valid beyond 0
	if ( $page_number > 0 ) {
		$retval = sprintf( esc_html__( 'Page %d', 'econozel' ), $page_number );
	}

	// Enable plugin filtering
	$retval = apply_filters( 'econozel_article_page_number', $retval, $article, $page_number );

	if ( $echo ) {
		echo $retval;
	} else {
		return $retval;
	}
}

/**
 * Output the Article's page number
 *
 * @since 1.0.0
 *
 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
 */
function econozel_the_article_page_number( $article = 0 ) {
	echo econozel_get_article_page_number( $article );
}

	/**
	 * Return the Article's page number
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'econozel_get_article_page_number'
	 *
	 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
	 * @return int Article page number
	 */
	function econozel_get_article_page_number( $article = 0 ) {

		// Define return var
		$page_number = 0;

		// Get the Article
		if ( $article = econozel_get_article( $article ) ) {
			$page_number = $article->menu_order;
		}

		return apply_filters( 'econozel_get_article_page_number', $page_number, $article );
	}
