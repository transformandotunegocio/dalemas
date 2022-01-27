<?php

/**
 * Template Name: Promociones
 *
 * @package WordPress
 */


get_header('shop');



?>
<main class="woowContentFull main main_tienda">
    <div class="home_banner">
        <img src="<?=IMGURL?>promo.jpeg" alt="">
        <div class="info_banner">
            <h1><?= __('VIVE LA EXPERIENCIA DÁLE MÁS', 'Dale') ?></h1>
            <span></span>
            <p><?= __('DESCUENTOS', 'Dale') ?></p>
        </div>
    </div>
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
								</div>
								<input class="jsonFilter" type="hidden" value=""></input>
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
					//realizar busqueda
					
                    $arrayProduct = $customWoo->getProductStoreSale('product_cat', NULL, 'date', $init, $paged);

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
