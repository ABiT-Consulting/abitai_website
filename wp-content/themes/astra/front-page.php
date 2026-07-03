<?php
/**
 * Front page template.
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();

if ( function_exists( 'astra_page_layout' ) && astra_page_layout() === 'left-sidebar' ) {
	get_sidebar();
}
?>

<div id="primary" <?php function_exists( 'astra_primary_class' ) ? astra_primary_class() : post_class( 'content-area primary' ); ?>>

	<?php
	if ( function_exists( 'astra_primary_content_top' ) ) {
		astra_primary_content_top();
	}

	if ( function_exists( 'astra_content_page_loop' ) ) {
		astra_content_page_loop();
	} elseif ( have_posts() ) {
		while ( have_posts() ) {
			the_post();

			if ( locate_template( 'template-parts/content-page.php' ) ) {
				get_template_part( 'template-parts/content', 'page' );
			} else {
				the_content();
			}
		}
	}

	if ( function_exists( 'astra_primary_content_bottom' ) ) {
		astra_primary_content_bottom();
	}
	?>

</div><!-- #primary -->

<?php
if ( function_exists( 'astra_page_layout' ) && astra_page_layout() === 'right-sidebar' ) {
	get_sidebar();
}

get_footer();
