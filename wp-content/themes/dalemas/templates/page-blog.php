<?php

/**
 * Template Name: Blog
 *
 * @package WordPress
 */

get_header();

// Class print content
$classPrint     = new Printer;
$getPost     = $classPrint->PostTypeSection( 'post', 100, '', NULL, 1, 100 );

?>
    <main class="woowContentFull main main_blog">
        <div class="woowContent1400">
            <div class="main_blog_content pure-g">
                <?php
                    foreach ($getPost as $key => $post) {
                        ?>
                        <div class="pure-u-1 pure-u-md-1-3">
                            <article class="item_blog">
                                <div class="item_blog_img">
                                    <a href="<?= $post['link']?>">
                                        <img src="<?= $post['urlImg']?>" alt="">
                                        <div class="info_modal_blog">
                                            <p><?= $post['title']?></p>
                                        </div>
                                    </a>
                                </div>
                                <div class="item_blog_text">
                                    <h1><a href="<?= $post['link']?>"><?= $post['title']?></a></h1>
                                    <p class="item_blog_description"><?= $post['excerpt']?></p>
                                    <p class="item_blog_link"><a href="<?= $post['link']?>"><?= __('Leer más', 'Dale') ?></a></p>
                                </div>
                            </article>
                        </div>
                        <?php
                    }
                ?>
            </div>
        </div>
    </main>
<?php
get_footer();
?>