<?php
/**
 * Single Product Price
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/price.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $product;
global $post;

$short_description = apply_filters( 'woocommerce_short_description', $post->post_excerpt );

if ( ! $short_description ) {
	return;
}

?>

<p class="sku_producto"><strong>SKU</strong> <?php echo ( $sku = $product->get_sku() ) ? $sku : esc_html__( 'N/A', 'woocommerce' ); ?></p>

<p class="categoria_producto">
	<?php echo wc_get_product_category_list( $product->get_id(), ', ', '<span class="posted_in">' . _n( 'Category:', 'Categories:', count( $product->get_category_ids() ), 'woocommerce' ) . ' ', '</span>' ); ?>

	<?php echo wc_get_product_tag_list( $product->get_id(), ', ', '<span class="tagged_as">' . _n( 'Tag:', 'Tags:', count( $product->get_tag_ids() ), 'woocommerce' ) . ' ', '</span>' ); ?>

	<?php do_action( 'woocommerce_product_meta_end' ); ?>
</p>

<p class="mensaje_venta">*<?= __('El precio es por unidad', 'Dale') ?>.</p>
<div class="description_producto"><?php echo $short_description; // WPCS: XSS ok. ?></div>

<?php
	$tipo_product = $product->get_type();

	if ( $tipo_product == 'variable' ) {
		echo '<p class="mensaje_venta">**'.__('Seleccionar talla o color para ver el producto', 'Dale').'</p>';
	}else{
		echo '<br>';
	}
?>

<div class="precios_productos">
	<?php echo $product->get_price_html(); ?>
</div>
