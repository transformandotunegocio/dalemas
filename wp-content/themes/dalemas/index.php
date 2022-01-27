<?php
/**
 * The main template file
 *
 */

get_header(); ?>

	<section id="index" class="woowContentFull">
		<?php 
			if ( have_posts() ) :
				// Start the Loop.
				while ( have_posts() ) : the_post();

					the_content();

				endwhile;

			else :
				get_template_part( 'content', 'none' );
			endif;
		?>
	</section>

<?php get_footer(); ?>