<?php

/**
 * Single Edition
 * 
 * @package Econozel
 * @subpackage Theme
 */

?>

<div id="econozel-articles">

	<?php if ( econozel_has_articles() ) : ?>

		<?php while ( econozel_has_articles() ) : econozel_the_article(); ?>

		<div id="post-<?php econozel_the_article_id(); ?>" <?php post_class(); ?>>

			<h3 class="article-title">
				<a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a>
			</h3>

			<p class="article-excerpt"><?php econozel_the_article_description(); ?></p>

			<p class="article-meta">
				<span class="article-author"><?php econozel_the_article_author_link(); ?></span>
				<?php if ( econozel_get_article_page_number() ) : ?>
					<span class="article-page-number"><?php econozel_article_page_number(); ?></span>
				<?php endif; ?>
				<?php if ( get_comments_number() ) : ?>
					<span class="comment-count"><?php comments_number(); ?></span>
				<?php endif; ?>
			</p>
		</div>

		<?php endwhile; ?>

	<?php else : ?>

		<?php econozel_get_template_part( 'feedback', 'no-articles' ); ?>

	<?php endif; ?>

</div>
