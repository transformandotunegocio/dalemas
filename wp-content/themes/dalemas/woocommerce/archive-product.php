<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

get_header('shop');

$current_term = $GLOBALS['wp_query']->get_queried_object();
$printother = false;
$search_type = false;
$catSlug = NULL;
$catName = NULL;

if ( isset( $_GET['post_type'] ) &&  !empty( $_GET['post_type'] ) && isset( $_GET['s'] ) &&  !empty( $_GET['s'] ) ) {
	$findSe = sanitize_text_field($_GET['s']);
	$search_type = true;
} elseif (isset($current_term->taxonomy)) {
	if ($current_term->taxonomy == 'product_cat') {
        $catSlug = $current_term->slug;
        $catName = $current_term->name;
	}
}


$imgbannermarca = "";
//comprobar que sea alguna taxonomia
if ( isset($current_term->taxonomy) ) {

    if ($current_term->taxonomy == 'product_cat') {
        $showName = $current_term->name;
        //banners marca
        switch ( $showName ) {
            case 'BDSM':
                $title_banner       = "BDSM";
                $subtitle_banner    = "SIENTE Y SUPERA LOS LÍMITES";
                if(wp_is_mobile()) {
                    $imgbannermarca = IMGURL . 'tienda/bdsm.png';
                }else {
                    $imgbannermarca = IMGURL . 'tienda/bdsm.png';
                }
            break;
        }
    }
}

//comprobar que sea alguna taxonomia
if ( isset($current_term->taxonomy) ) {

	if ($current_term->taxonomy == 'product_cat') {

		//Variables paraa Filtros
		$showName = $current_term->name;
		$idTerm = $current_term->term_id;


		$button = '<button value="' . $idTerm . '" data-tax="product_cat" class="btnFiltroDelete">' . $showName . '</button>';

		$filter = array(
			'product_cat' 	=> array(
				'ID'		=>	$idTerm,	
				'name'		=>	$showName
			), 	
			'marca'			=> array(), 	
			'price'			=> array(),
			
		);

		$filterjson = json_encode($filter);

		$input = "<input class='jsonFilter' type='hidden' value='" . $filterjson . "'>";

	} elseif ($current_term->taxonomy == 'marca') {

		$showName = $current_term->name;
		$idTerm = $current_term->term_id;

		$button = '<button value="' . $idTerm . '" data-tax="product_cat" class="btnFiltroDelete">' . $showName . '</button>';

		$filter = array(
				'product_cat' 	=> array(), 	
				'marca'			=> array(
					'ID'			=>	$idTerm,	
					'name'			=>	$showName
				),	
				'price' 		=> array(),
		
		);

		$filterjson = json_encode($filter);

		$input = "<input class='jsonFilter' type='hidden' value='" . $filterjson . "'>";

	}

}




?>
<main class="woowContentFull main main_tienda">

	<?php
        if($imgbannermarca != ""){
            ?>
            <div class="home_banner">
                <a href=""><img src="<?= $imgbannermarca ?>" alt=""></a>
                <div class="info_banner">
                    <h1><?= $title_banner ?></h1>
                    <span></span>
                    <p><?= $subtitle_banner ?></p>
                </div>
            </div>
            <?php
        }
    ?>
    <div class="woowContent1400">

        <div class="tienda_producto_content">
            <div class="tienda_producto_content_filtro">
                <div id="titFiltrar">
					
					<div class="filtros">
						
						<div class="filtro">
							<div class="filtroHeader">
								<h4><?= __('Filtros activos', 'Dale') ?></h4>
							</div>
							<aside class="widget woocommerce widget_filters">
								<div class="widget-content">
									<?php
									if (isset($button)) {
										echo $button;
									} else {
										echo __('Aun no tienes filtros', 'Dale');
									}
									?>
								</div>
								<?php
								if (isset($input)) {
									echo $input;
								} else {
									echo '<input class="jsonFilter" type="hidden" value=""></input>';
								}
								?>
							</aside>
						</div>
						<div class="filtro">
							<h4><?= __('Categorias', 'Dale') ?></h4>
							<aside class="widget woocommerce">
								<div class="widget-content widget_tax">
									<ul class="ul_cat_tienda product-categories">
										<?php echo cache_html('DropTienda') ?>
									</ul>
								</div>
							</aside>
						</div>
						<div class="filtro">
							<h4><?= __('Precio', 'Dale') ?></h4>
							<aside class="widget woocommerce">
								<div class="price_widget_tax">
									<?php
									$arrayPrecios = get_option('preciosArray');
									if (!empty($arrayPrecios) && is_array($arrayPrecios)) {
										$min_view_price = $arrayPrecios['total']['min'];
										$max_view_price = $arrayPrecios['total']['max'];
										if ($arrayPrecios['total']['min'] == $arrayPrecios['total']['max']) {
											$min_view_price = 0;
										}
										//Check term
										if ( isset($current_term->taxonomy) ) {
											if ($current_term->taxonomy == 'product_cat') {
												if (isset($arrayPrecios[$idTerm])) {
													if ($arrayPrecios[$idTerm]['min'] > 0 && $arrayPrecios[$idTerm]['max'] > 0) {
														$min_view_price = $arrayPrecios[$idTerm]['min'];
														$max_view_price = $arrayPrecios[$idTerm]['max'];
														if ($arrayPrecios[$idTerm]['min'] == $arrayPrecios[$idTerm]['max']) {
															$min_view_price = 1000;
														}
													}
												}
											}
											
										}
									} else {
										$min_view_price = 1000;
										$max_view_price = 10000000;
									}
									?>
									<!-- Slider price -->
									<div id="priceSlider"></div>
									<!-- Data slider price -->
									<p class="priceSliderValues">
										<span class="value amountmin">00</span>
										<span class="separador"> - </span>
										<span class="value amountmax">00</span>
										<input type="hidden" id="amountmin" value="<?php echo ($min_view_price == 0) ? 0 : $min_view_price ?>" readonly></input>
										<input type="hidden" id="amountmax" value="<?php echo $max_view_price; ?>" readonly></input>
									</p>
								</div>
							</aside>
						</div>
					</div>
				</div>
            </div>
            <div class="home_products_content pure-g">

                <?php
					
					$init = get_option('posts_per_page', true);
					$customWoo = new Woocommerce_Custom;
					$paged = (get_query_var('paged')) ? absint(get_query_var('paged')) : 1;


					if ( $search_type ) {
						$arrayProduct = $customWoo->getProductStore( 'product_cat', NULL, 'date', $init, $paged, NULL, NULL, $findSe);
					}else{
						//realizar busqueda
						if (isset($current_term->taxonomy)) {
							$arrayProduct = $customWoo->getProductStore('product_cat', $idTerm, 'date', $init, $paged);
						} else {
							$arrayProduct = $customWoo->getProductStore('product_cat', NULL, 'date', $init, $paged);
						}
							
					}
					//imprimir pprdoctos
					if (isset($current_term->taxonomy)) {
						if (is_array($arrayProduct) && !empty($arrayProduct)) {
							$objHtml = $customWoo->printProductsArr($arrayProduct);
							echo $objHtml;
							if (isset($arrayProduct['data']['pagination']) && $arrayProduct['data']['pagination'] > 1) {
								$arrayPage = array(
										'type' => $current_term->taxonomy
									,	'find' => $idTerm
									,	'mas_page' => $arrayProduct['data']['pagination']
								);
								?>
								<script type="text/javascript">
									var paginationroduct = <?php echo wp_json_encode($arrayPage); ?>;
								</script>
								<?php
							}
						} else {
							?>
							<div class="pure-u-1 product_none">
								<p>NO EXISTEN PRODUCTOS EN ESTA CATEGORIA</p>
							</div>
							<?php
						}
					} else {
						echo '<input type="hidden" id="taxonomi" value="">';
						echo '<input type="hidden" id="category" value="">';
						if (is_array($arrayProduct) && !empty($arrayProduct)) {
							$objHtml = $customWoo->printProductsArr($arrayProduct);
							echo $objHtml;
							if (isset($arrayProduct['data']['pagination']) && $arrayProduct['data']['pagination'] > 1) {
								$arrayPage = array(
										'type' 		=> ''
									,	'find' 		=> ''
									,	'mas_page' 	=> $arrayProduct['data']['pagination']
								);
								?>
								<script type="text/javascript">
									var paginationroduct = <?php echo wp_json_encode($arrayPage); ?>;
								</script>
								<?php
							}
						} else {
							?>
							<div class="pure-u-1 product_none">
								<p>NO EXISTEN PRODUCTOS EN ESTA CATEGORIA</p>
							</div>
							<?php
						}
					}
              	?>
            </div>
            <div class="tienda_products_pagination">
				<div class="sec_pagination">
					<div id="pagination" class="products_pagination"></div>
				</div>
            </div>
        </div>
    </div>
</main>
<?php


get_footer( 'shop' );
