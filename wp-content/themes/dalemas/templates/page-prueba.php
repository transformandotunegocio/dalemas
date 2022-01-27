
<?php

/**
 * Template Name: Prueba
 *
 * @package WordPress
 */


get_header();


$attachments = array(
                        EMAILPATH . 'Presentancion.pdf',
                        EMAILPATH . 'DaleCorporatePresentation.pdf'
                    );


$sent = wp_mail('andymoreno.ing@gmail.com', 'Testing Attachment' , 'This is subscription','This is for header', $attachments);

var_dump($sent);

get_footer();