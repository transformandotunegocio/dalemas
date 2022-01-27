<?php
/**
 * The template for displaying product search form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/product-searchform.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<form class="woocommerce-product-search header_top_form" role="search" method="get" action="<?php echo esc_url( home_url( 'tienda/' ) ); ?>">
    <input type="search" id="woocommerce-product-search-field-<?php echo isset( $index ) ? absint( $index ) : 0; ?>" class="search-field" placeholder="Buscar" value="<?php echo get_search_query(); ?>" name="s" />
    <button class="header_send_form" type="submit" value="<?php echo esc_attr_x( 'Search', 'submit button', 'woocommerce' ); ?>">
        <i class="icon-search"></i>
    </button>
    <input type="hidden" name="post_type" value="product" />
</form>