<?php

/**
 * Single Edition Template Part
 * 
 * @package Econozel
 * @subpackage Theme
 */

?>

<?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>

<div id="econozel-articles">

	<?php if ( econozel_has_articles() ) : ?>

		<?php while ( econozel_has_articles() ) : econozel_the_article(); ?>

		<div id="post-<?php econozel_the_article_id(); ?>" <?php post_class(); ?>>

			<h2 class="article-title"><?php the_title( sprintf( '<a href="%s">', esc_url( get_permalink() ) ), '</a>' ); ?></h2>

			<p class="article-meta">
				<span class="article-author"><?php printf( esc_html__( 'Written by %s', 'econozel' ), econozel_get_article_author_link( 0, true ) ); ?></span>
			</p>

			<?php econozel_the_article_description(); ?>

		</div>

		<?php endwhile; ?>

	<?php else : ?>

		<?php econozel_get_template_part( 'feedback', 'no-articles' ); ?>

	<?php endif; ?>

</div>
