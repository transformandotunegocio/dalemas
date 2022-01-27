<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( post_password_required() ) {
	echo get_the_password_form(); // WPCS: XSS ok.
	return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'woowContent1400', $product ); ?>>
	<?php
	/**
	 * Hook: woocommerce_before_single_product_summary.
	 *
	 * @hooked woocommerce_show_product_sale_flash - 10
	 * @hooked woocommerce_show_product_images - 20
	 */
	do_action( 'woocommerce_before_single_product_summary' );
	?>
	<div class="producto_info">
		<?php
			do_action( 'woocommerce_before_main_content' );
			/**
			 * Hook: woocommerce_single_product_summary.
			 *
			 * @hooked woocommerce_template_single_title - 5
			 * @hooked woocommerce_template_single_rating - 10
			 * @hooked woocommerce_template_single_price - 10
			 * @hooked woocommerce_template_single_excerpt - 20
			 * @hooked woocommerce_template_single_add_to_cart - 30
			 * @hooked woocommerce_template_single_meta - 40
			 * @hooked woocommerce_template_single_sharing - 50
			 * @hooked WC_Structured_Data::generate_product_data() - 60
			 */
			do_action( 'woocommerce_single_product_summary' );
		?>
    </div>	
</div>

<div class="only_product_relact">
	<?php
	/**
	 * Hook: woocommerce_after_single_product_summary.
	 *
	 * @hooked woocommerce_output_product_data_tabs - 10
	 * @hooked woocommerce_upsell_display - 15
	 * @hooked woocommerce_output_related_products - 20
	 */
	do_action( 'woocommerce_after_single_product_summary' );
	?>
	<div class="productos_relacionados">
        <h3 class="title_producto_relacionado"><?= __('PRODUCTOS RELACIONADOS', 'Dale') ?></h3>
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

				$galery = "";
				$attachment_ids = $wc_product->get_gallery_image_ids();
				foreach( $attachment_ids as $attachment_id ) {
					$galery = wp_get_attachment_url( $attachment_id );
					break;
				}

				?>
				<div class="home_products_content_items pure-u-1 pure-u-md-1-4">
					<div class="products_content_items_img">
						<?php
						
						if($wc_product->is_on_sale()){
						?>
						<div class="tienda_precio_oferta">
							<span>¡Oferta!</span>
						</div>
						<?php
						}
						?>
						<img class="tienda_img_1" src="<?= $image_url ?>" alt="">

						<?php
						if($galery != ""){
							?>
							<img class="tienda_img_2" src="<?php echo $galery ?>"/>
							<?php
						}else{
							?>
							<img class="tienda_img_2" src="<?php echo $image_url ?>"/>
							<?php
						}
						?>
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
					<p><a href="<?=$wc_product->get_permalink()?>"><i class="icon-check"></i>Seleccionar opciones</a></p>
				</div>
				<?php
			}
        ?>
        </div>
    </div>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
