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

			<h3><?php econozel_the_volume_title(); ?></h3>

			<p><?php var_dump( econozel_get_volume_editions() ); ?></p>

		<?php endwhile; ?>

	<?php else : ?>

		<?php econozel_get_template_part( 'feedback', 'no-volumes' ); ?>

	<?php endif; ?>

</div>
