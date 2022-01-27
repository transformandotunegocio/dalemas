<?php
/**
 * Mini-cart
 *
 * Contains the markup for the mini-cart, used by the cart widget.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/mini-cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 5.2.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_mini_cart' ); ?>
<?php if ( ! WC()->cart->is_empty() ) : ?>

    <div class="content_carrito_main">
        <?php
        do_action( 'woocommerce_before_mini_cart_contents' );
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $_product     = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
            $product_id   = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
            if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
                $product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
                $thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
                $product_price     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
                $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
                ?>
                <div class="carrito_main_item">
                    <div class="carrito_main_item_img">
                        <!-- img prueba -->
                        <?php if ( ! $_product->is_visible() ) : ?>
                            <?php echo str_replace( array( 'http:', 'https:' ), '', $thumbnail ); ?>
                        <?php else : ?>
                            <a href="<?php echo esc_url( $product_permalink ); ?>">
                                <?php echo str_replace( array( 'http:', 'https:' ), '', $thumbnail ); ?>
                            </a>
                        <?php endif; ?>	
                    </div>
                    <div class="carrito_main_item_info">
                        <h3><?php echo substr($product_name, 0, 20); ?></h3>
                        <h4><?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?></h4>
                    </div>
                    <div class="carrito_main_item_delete">
                        <div class="mini_remove">
                            <?php
                            echo apply_filters( 'woocommerce_cart_item_remove_link', sprintf(
                                '<a href="%s" class="remove remove_from_cart_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s"><i class="icon-close"></i></a>',
                                esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
                                __( 'Remove this item', 'woocommerce' ),
                                esc_attr( $product_id ),
                                esc_attr( $cart_item_key ),
                                esc_attr( $_product->get_sku() )
                            ), $cart_item_key );
                            ?>
                        </div>
                    </div>
                </div>
                
            <?php
            }
        }
        do_action( 'woocommerce_mini_cart_contents' );
        ?>   
	</div>
	



<?php else : ?>
    
	<div class="empty">
        <?php _e( 'Su bolsa de compras está vacía', 'woocommerce' ); ?>
        <p class="button_empty_store"><a href="<?php echo home_url('tienda'); ?>">Comience a Comprar</a></p>
    </div>
<?php endif; ?>
<?php if ( ! WC()->cart->is_empty() ) : ?>



    <div class="content_carrito_bottom">
		<div class="item_carrito_total">
			<p class="item_carrito_total_title">Total Parcial</p>
			<p class="item_carrito_total_price"><?php echo WC()->cart->get_cart_subtotal(); ?></p>
		</div>
		
		<div class="item_carrito_total">
			<p class="item_carrito_total_title">Total</p>
			<p class="item_carrito_total_price">
                <?php 
                    $total =  WC()->cart->get_total(); 
                    echo $total;
                ?>
            </p>
		</div>
		<div class="item_carrito_total_link">
			<a href="<?=home_url('carrito')?>">Abrir carrito de compras</a>
		</div>
	</div>


<?php endif; ?>
<?php do_action( 'woocommerce_after_mini_cart' ); ?>

<!--Funciones para actualizar el carrito -->



