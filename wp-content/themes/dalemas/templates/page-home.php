<?php

/**
 * Template Name: Home
 *
 * @package WordPress
 */

get_header();

?>
  <main class="woowContentFull main main_home">
    <div class="home_slider">
      <?php
        //baner solo para colombia
        if($isColombia){
          if (wp_is_mobile())
            include(get_template_directory() . '/template-parts/slider-movil.php');
          else
            include(get_template_directory() . '/template-parts/slider-desktop.php');
        }
        if($isMexico){
          if (wp_is_mobile())
            include(get_template_directory() . '/template-parts/slider-movil-mx.php');
          else
            include(get_template_directory() . '/template-parts/slider-desktop-mx.php');
        }
      ?>
    </div>
    <div class="woowContent1400">
      <div class="home_banner">
        <?php
        //baner solo para colombia
        if($isColombia){
          if (wp_is_mobile()){
            echo '<a href="https://dalemas.store/terminos-y-condiciones/"><img src="'.IMGURL.'ban_home_m.jpeg" alt=""></a>';
          }else{
            echo '<a href="https://dalemas.store/terminos-y-condiciones/"><img src="'.IMGURL.'colombia-baner.jpeg" alt=""></a>';
          }
        }
        if($isMexico){
          if (wp_is_mobile()){
            echo '<img src="'.IMGURL.'slider/mexico/mex_1_m.jpg" alt="">';
          }else{
            echo '<img src="'.IMGURL.'slider/mexico/mex_1.jpg" alt="">';
          }
        }
        ?>
      </div>
      <div class="home_products">
        <h2><?= __('LO', 'Dale') ?> <span>+</span> <?= __('VENDIDO', 'Dale') ?></h2>
        <div class="home_products_content pure-g">
        <?php
          $css_woocommerce = new Woocommerce_Custom;
          $productos = $css_woocommerce->printProductosHome();
          foreach ($productos->products as $key => $wc_product) {
            //Otro Proceso
            $image        = $wc_product->get_image_id();
            $image_url    = wp_get_attachment_image_url( $image, '400x600' );
            $tipo_product = $wc_product->get_type();
            //obtenemos array de price
            $aPrecios     = getPriceProduct($wc_product);
            ?>
            <div class="home_products_content_items pure-u-1 pure-u-md-1-4">
              <div class="products_content_items_img">
                <?php
                
                if($wc_product->is_on_sale()){
                  ?>
                  <div class="tienda_precio_oferta">
                    <span><?= __('¡Oferta!', 'Dale') ?></span>
                  </div>
                  <?php
                }
                ?>
                <img src="<?= $image_url ?>" alt="">
              </div>
              <h4><a href="<?=$wc_product->get_permalink()?>"><?= $wc_product->get_name() ?></a></h4>
              <?php
                if(is_array($aPrecios)){
                  ?>
                  <h3><span class="home_product_price_old">$<?=number_format( $aPrecios['price'], 0, ',', '.' )?></span><span class="home_product_price_new">$<?=number_format( $aPrecios['price_sale'], 0, ',', '.' )?></span></h3>
                  <?php
                }else{
                  ?>
                  <h3>$<?=number_format( $aPrecios, 0, ',', '.' )?></h3>
                  <?php
                }
              ?>
              <p><a href="<?=$wc_product->get_permalink()?>"><i class="icon-check"></i><?= __('Seleccionar opciones', 'Dale') ?></a></p>
            </div>
            <?php
          }
        ?>
        </div>
      </div>
    </div>
  </main>
<?php
get_footer();
?>