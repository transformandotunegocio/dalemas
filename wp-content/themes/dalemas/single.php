<?php
/**
* Template Name: Single Blog
* The template for displaying Home page.
*
* @package WordPress
*/
get_header();
?>
<main class="woowContentFull main main_single">
     <div class="woowContent1400">
         <div class="main_single_content">
             <div class="single_img">
				<?php the_post_thumbnail( 'full' ) ?>
             </div> 
             <div class="single_info">
                 <h1><?= the_title() ?></h1>
                 <p class="single_breadcumps"><a href="">Deja un comentario / Línea BDSM / Por Admin Dale+</a></p>
                 <div class="single_info_description">
					<?= str_replace("&nbsp;","<br><br>",get_the_content());  ?>
				</div>
             </div>     
         </div>
     </div>
 </main>
<?php
get_footer();
?>