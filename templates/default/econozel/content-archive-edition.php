<?php

/**
 * Editions Loop Template Part
 * 
 * @package Econozel
 * @subpackage Theme
 */

?>

<div id="econozel-editions">

	<?php if ( econozel_has_editions() ) : ?>

		<?php while ( econozel_has_editions() ) : econozel_the_edition(); ?>

		<div id="term-<?php econozel_the_edition_id(); ?>" <?php econozel_term_class( 'widget_recent_entries' ); ?>>

			<h3 class="edition-title"><?php econozel_the_edition_link(); ?></h3>

			<?php econozel_the_edition_content(); ?>

		</div>

		<?php endwhile; ?>

	<?php else : ?>

		<?php econozel_get_template_part( 'feedback', 'no-editions' ); ?>

	<?php endif; ?>

</div>
