<?php

/**
 * Single Volume Template Part
 * 
 * @package Econozel
 * @subpackage Theme
 */

?>

<?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>

<div id="econozel-editions">

	<?php if ( econozel_has_editions() ) : ?>

		<?php while ( econozel_has_editions() ) : econozel_the_edition(); ?>

		<div id="term-<?php econozel_the_edition_id(); ?>" <?php econozel_term_class( 'widget_recent_entries' ); ?>>

			<h2 class="edition-title"><?php econozel_the_edition_link(); ?></h2>

			<?php econozel_the_edition_description(); ?>

			<?php econozel_the_edition_toc(); ?>

			<?php if ( econozel_has_edition_document() ) : ?>
				<p><a href="<?php echo esc_url( econozel_get_edition_document_url() ); ?>" target="_blank"><?php esc_html_e( "Download the Edition's document file", 'econozel' ); ?></a></p>
			<?php endif; ?>

		</div>

		<?php endwhile; ?>

	<?php else : ?>

		<?php econozel_get_template_part( 'feedback', 'no-editions' ); ?>

	<?php endif; ?>

</div>
