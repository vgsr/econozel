<?php

/**
 * Single Edition Template Part
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

			<?php econozel_the_article_content(); ?>

		</div>

		<?php endwhile; ?>

	<?php else : ?>

		<?php econozel_get_template_part( 'feedback', 'no-articles' ); ?>

	<?php endif; ?>

</div>
