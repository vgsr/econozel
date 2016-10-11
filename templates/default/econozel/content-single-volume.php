<?php

/**
 * Editions Loop
 * 
 * @package Econozel
 * @subpackage Theme
 */

?>

<div id="econozel-editions">

	<?php if ( econozel_has_editions() ) : ?>

		<?php while ( econozel_has_editions() ) : econozel_the_edition(); ?>

		<div id="term-<?php econozel_the_edition_id(); ?>" <?php econozel_term_class( 'widget_recent_entries' ); ?>>

			<h3 class="edition-title"><?php econozel_the_edition_issue_link(); ?></h3>

			<?php if ( econozel_query_articles() ) : ?>

			<ul class="edition-articles">

				<?php while ( econozel_has_articles() ) : econozel_the_article(); ?>

				<li <?php post_class(); ?>>
					<span class="article-title"><a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a></span>
					<span class="article-author"><?php econozel_the_article_author_link(); ?></span>
					<?php if ( get_comments_number() ) : ?>
						<span class="comment-count"><?php comments_number(); ?></span>
					<?php endif; ?>
				</li>

				<?php endwhile; ?>

			</ul>

			<?php endif; ?>

		</div>

		<?php endwhile; ?>

	<?php else : ?>

		<?php econozel_get_template_part( 'feedback', 'no-editions' ); ?>

	<?php endif; ?>

</div>
