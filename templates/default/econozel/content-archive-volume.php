<?php

/**
 * Volumes Loop Template Part
 * 
 * @package Econozel
 * @subpackage Theme
 */

?>

<div id="econozel-volumes">

	<?php if ( econozel_has_volumes() ) : ?>

		<?php while ( econozel_has_volumes() ) : econozel_the_volume(); ?>

		<div id="term-<?php econozel_the_volume_id(); ?>" <?php econozel_term_class( 'widget_recent_entries' ); ?>>

			<h2 class="volume-title"><?php econozel_the_volume_link(); ?></h2>

			<?php econozel_the_volume_description(); ?>

			<?php econozel_the_volume_content(); ?>

		</div>

		<?php endwhile; ?>

	<?php else : ?>

		<?php econozel_get_template_part( 'feedback', 'no-volumes' ); ?>

	<?php endif; ?>

</div>
