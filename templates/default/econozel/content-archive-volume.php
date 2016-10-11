<?php

/**
 * Volumes Loop
 * 
 * @package Econozel
 * @subpackage Theme
 */

?>

<div id="econozel-volumes">

	<?php if ( econozel_has_volumes() ) : ?>

		<?php while ( econozel_has_volumes() ) : econozel_the_volume(); ?>

		<div id="term-<?php econozel_the_volume_id(); ?>" <?php econozel_term_class( 'widget_recent_entries' ); ?>>

			<h3 class="volume-title"><?php econozel_the_volume_link(); ?></h3>

			<?php if ( econozel_query_editions() ) : ?>

			<ul class="volume-editions">

				<?php while ( econozel_has_editions() ) : econozel_the_edition(); ?>

				<li <?php econozel_term_class(); ?>>
					<span class="edition-title"><?php econozel_the_edition_issue_link(); ?></a></span>
					<span class="article-count"><?php econozel_edition_article_count(); ?></span>
				</li>

				<?php endwhile; ?>

			</ul>

			<?php endif; ?>

		</div>

		<?php endwhile; ?>

	<?php else : ?>

		<?php econozel_get_template_part( 'feedback', 'no-volumes' ); ?>

	<?php endif; ?>

</div>
