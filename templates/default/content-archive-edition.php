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

			<h3><?php printf( '%d &ndash; %s', econozel_get_edition_issue(), econozel_get_edition_title() ); ?></h3>

			<p><?php var_dump( econozel_get_edition_articles() ); ?></p>

		<?php endwhile; ?>

	<?php else : ?>

		<?php econozel_get_template_part( 'feedback', 'no-editions' ); ?>

	<?php endif; ?>

</div>
