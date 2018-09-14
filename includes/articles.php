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
	return econozel()->article_post_type;
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

/**
 * Return the econozel Featured post status id
 *
 * @since 1.0.0
 *
 * @return string Post status id
 */
function econozel_get_featured_status_id() {
	return econozel()->featured_status_id;
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
 * Return a random Article
 *
 * @since 1.0.0
 *
 * @param array $args Optional. Query arguments, see {@see WP_Query}.
 * @return WP_Post|bool Post object when found, else False.
 */
function econozel_get_random_article( $args = array() ) {

	// Define return variable
	$article = false;

	// Run query
	$query = new WP_Query( wp_parse_args( $args, array(
		'post_type'      => econozel_get_article_post_type(),
		'orderby'        => 'rand',
		'posts_per_page' => 1,
	) ) );

	if ( $query->posts ) {
		$article = $query->posts[0];
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

	// Define return variable
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

		// Define return variable
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
 * Output the Article's author(s)
 *
 * @since 1.0.0
 *
 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
 * @param bool|string $concat Optional. Whether to concatenate the links into a single string. When provided a string value,
 *                            it will be used as the item separator. Defaults to true, using {@see wp_sprintf_l()}.
 */
function econozel_the_article_author( $article = 0, $concat = true ) {
	echo econozel_get_article_author( $article, $concat );
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
	 * @param bool|string $concat Optional. Whether to concatenate the links into a single string. When provided a string value,
	 *                            it will be used as the item separator. Defaults to true, using {@see wp_sprintf_l()}.
	 * @return string|array Article author user ID(s), or display names when concatted.
	 */
	function econozel_get_article_author( $article = 0, $concat = false ) {

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

		$author = (array) apply_filters( 'econozel_get_article_author', $author, $article );

		// Stringify authors
		if ( false !== $concat ) {
			$author = array_map( 'econozel_get_user_displayname', $author );
			$author = true === $concat ? wp_sprintf_l( '%l', $author ) : implode( $concat, $author );
		}

		return $author;
	}

/**
 * Return the Article authors count
 *
 * @since 1.0.0
 *
 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
 * @return int Article author count.
 */
function econozel_get_article_author_count( $article = 0 ) {
	$author = econozel_get_article_author( $article );
	return count( $author );
}

/**
 * Return whether the Article has multiple authors
 *
 * @since 1.0.0
 *
 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
 * @return bool Has Article multiple authors?
 */
function econozel_is_article_multi_author( $article = 0 ) {
	$author_count = econozel_get_article_author_count( $article );
	$retval = $author_count > 1;

	return $retval;
}

/**
 * Return whether the user is author of the Article
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_is_user_article_author'
 *
 * @param WP_User|int $user Optional. User object or ID. Defaults to the current user.
 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
 * @return bool Is user Article author?
 */
function econozel_is_user_article_author( $user = 0, $article = 0 ) {

	// Define return variable
	$retval = false;

	// Get user ID
	if ( empty( $user ) ) {
		$user = get_current_user_id();
	} elseif ( is_a( $user, 'WP_User' ) ) {
		$user = $user->ID;
	} else {
		$user = (int) $user;
	}

	// Is user in list of Article authors?
	if ( $user ) {
		$retval = in_array( $user, econozel_get_article_author( $article ) );
	}

	return (bool) apply_filters( 'econozel_is_user_article_author', $retval, $user, $article );
}

/**
 * Output the Article's author link
 *
 * @since 1.0.0
 *
 * @param array $args See {@see econozel_get_article_author_link()}. Defaults 'concat' to True.
 */
function econozel_the_article_author_link( $args = array() ) {

	// Accept single argument as the Article
	if ( ! is_array( $args ) ) {
		$args = array( 'article' => $args );
	}

	echo econozel_get_article_author_link( wp_parse_args( $args, array(
		'concat' => true
	) ) );
}

	/**
	 * Return the Article's author link
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Function arguments, supports these args:
	 *  - article: Article object or ID. Defaults to the current Article.
	 *  - concat: Whether to concatenate the links into a single string. When provided a string value, it will be used as
	 *            the item separator. When provided True, will use just {@see wp_sprintf_l()}. Defaults to False.
	 *  - link_before: Markup to put before the link. Defaults to an empty string.
	 *  - link_after: Markup to put after the link. Defaults to an empty string.
	 *  - link_attrs: Link attributes. Key-value list or a callable function which returns one. Defaults to empty string.
	 * @return string|array Article author link(s)
	 */
	function econozel_get_article_author_link( $args = array() ) {

		// Accept single argument as the Article
		if ( ! is_array( $args ) ) {
			$args = array( 'article' => $args );
		}

		$r = wp_parse_args( $args, array(
			'article'     => 0,
			'concat'      => false,
			'link_before' => '',
			'link_after'  => '',
			'link_attrs'  => '',
		) );

		// Define return value
		$links = array();

		if ( $article = econozel_get_article( $r['article'] ) ) {

			// Loop Article author url(s)
			foreach ( econozel_get_article_author_url( $article ) as $user_id => $url ) {

				// Setup user link
				$link = sprintf( ! empty( $url ) ? '%s<a href="%s">%s</a>%s' : '%1$s%3$s%4$s',
					$r['link_before'],
					esc_url( $url ),
					econozel_get_user_displayname( $user_id ),
					$r['link_after']
				);

				// Inject link attributes
				if ( $url && $r['link_attrs'] ) {

					// Get link attributes from callable or plain list
					$attrs = is_callable( $r['link_attrs'] )
						? (array) call_user_func_array( $r['link_attrs'], array( $user_id, $article ) )
						: (array) $r['link_attrs'];

					foreach ( $attrs as $attr => $value ) {
						$link = str_replace( '<a', sprintf( "<a %s=%s", sanitize_key( $attr ), esc_attr( $value ) ), $link );
					}
				}

				// Enable plugin filtering
				$links[ $user_id ] = apply_filters( 'econozel_get_article_author_link', $link, $user_id, $url, $article, $r );
			}
		}

		// Stringify links
		if ( false !== $r['concat'] ) {
			$links = true === $r['concat'] ? wp_sprintf_l( '%l', $links ) : implode( $r['concat'], $links );
		}

		return $links;
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
 * Output the Article's date
 *
 * @since 1.0.0
 *
 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
 */
function econozel_the_article_date( $article = 0 ) {
	echo econozel_get_article_date( $article );
}

	/**
	 * Return the Article's date
	 *
	 * Note: this returns the original publish date of the Article, not the post's date per se.
	 * For Articles published in an Edition, this means the Edition title is returned instead
	 * of a date string.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
	 * @return string|array Article date
	 */
	function econozel_get_article_date( $article = 0 ) {

		// Define return value
		$date = array();

		// Get the Article
		if ( $article = econozel_get_article( $article ) ) {

			// Get Edition title
			if ( econozel_has_article_edition( $article ) ) {
				$date = econozel_get_edition_title( $article );

			// Get post date
			} else {
				$date = mysql2date( get_option( 'date_format' ), $article->post_date );
			}
		}

		return apply_filters( 'econozel_get_article_date', $date, $article );
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

	// Define return variable
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

		// Define return variable
		$page_number = 0;

		// Get the Article
		if ( $article = econozel_get_article( $article ) ) {
			$page_number = $article->menu_order;
		}

		return apply_filters( 'econozel_get_article_page_number', $page_number, $article );
	}

/**
 * Return the list of featured Articles
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_get_featured_articles'
 *
 * @param array $args Optional. Query arguments, see {@see WP_Query}.
 * @return array Featured Articles
 */
function econozel_get_featured_articles( $args = array() ) {

	// Parse arguments
	$r = wp_parse_args( $args, array(
		'post_type'      => econozel_get_article_post_type(),
		'post_status'    => econozel_get_featured_status_id(),
		'posts_per_page' => -1,
	) );

	// Get the Articles
	$query = new WP_Query( $r );
	$posts = $query->posts;

	return apply_filters( 'econozel_get_featured_articles', $posts, $r );
}

/**
 * Return whether the Article is featured
 *
 * @since 1.0.0
 *
 * @uses apply_filters() Calls 'econozel_is_article_featured'
 *
 * @param WP_Post|int $article Optional. Article object or ID. Defaults to the current Article.
 * @return bool Is Article featured?
 */
function econozel_is_article_featured( $article = 0 ) {

	// Define return variable
	$retval = false;

	// Get the Article
	if ( $article = econozel_get_article( $article ) ) {
		$retval = econozel_get_featured_status_id() === $article->post_status;
	}

	return apply_filters( 'econozel_is_article_featured', $retval, $article );
}

/**
 * Output the Article query pagination count
 *
 * @since 1.0.0
 */
function econozel_the_article_pagination_count() {
	echo econozel_get_article_pagination_count();
}

	/**
	 * Return the Article query pagination count
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'econozel_get_article_pagination_count'
	 * @return string Article pagination count
	 */
	function econozel_get_article_pagination_count() {

		// Get query object
		$query = econozel()->article_query;

		if ( empty( $query ) )
			return false;

		// Set pagination values
		$start_num = intval( ( $query->get( 'paged' ) - 1 ) * $query->get( 'posts_per_page' ) ) + 1;
		$to_num    = ( $start_num + ( $query->get( 'posts_per_page' ) - 1 ) > $query->found_posts ) ? $query->found_posts : $start_num + ( $query->get( 'posts_per_page' ) - 1 );
		$total     = (int) ! empty( $query->found_posts ) ? $query->found_posts : $query->post_count;

		// Several articles in a single page
		if ( empty( $to_num ) ) {
			$retstr = sprintf( _n( 'Viewing %1$s article', 'Viewing %1$s articles', $total, 'econozel' ), $total );

		// Several articles in several pages
		} else {
			$retstr = sprintf( _n( 'Viewing article %2$s (of %4$s total)', 'Viewing %1$s articles - %2$s through %3$s (of %4$s total)', $total, 'econozel' ), $query->post_count, $start_num, $to_num, $total );
		}

		return apply_filters( 'econozel_get_article_pagination_count', esc_html( $retstr ) );
	}
