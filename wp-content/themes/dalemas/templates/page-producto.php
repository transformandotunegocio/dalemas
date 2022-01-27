<?php

/**
 * Template Name: Producto
 *
 * @package WordPress
 */

get_header();

?>
    <main class="woowContentFull main main_producto">
        <div class="woowContent1400">
           <div class="main_producto_content">
                <div class="producto_galeria_imagen">
                    <div class="swiper-container slider_mini_ban">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <img src="<?= IMGURL  ?>producto/producto_1.jpeg" alt="">
                            </div>
                            <div class="swiper-slide">
                                <img src="<?= IMGURL  ?>producto/producto_2.jpeg" alt="">
                            </div>
                            <div class="swiper-slide">
                                <img src="<?= IMGURL  ?>producto/producto_3.jpeg" alt="">
                            </div>
                        </div>
                        <!-- Add Arrows -->
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                </div>
                <div class="producto_info">
                    <p class="migas_producto">
                       <a href="#">INICIO / CUERO / BRAZALETE / HORNY DUDE / BRAZALETE HORNY DUDE</a>
                    </p>
                    <h1 class="name_producto">BRAZALETE HORNY DUDE</h1>
                    <p class="sku_producto"><strong>SKU</strong> 2-0809042</p>
                    <p class="categoria_producto">
                        <strong>Categories: </strong>
                        <a href="">Brazalete, Cuero, Horny Dude, VIVE LA EXPERIENCIA DÁLE MÁS</a>
                    </p>
                    <p class="mensaje_venta">*El precio es por unidad.</p>
                    <p class="description_producto">Elaborado en cuero napa blando de la mejor calidad, disponible en fondo entero o con vivos contrastados, este brazalete resalta los brazos y atrapa miradas. Infaltable en un guardarropa fetichista.</p>
                    <p class="mensaje_venta">**Seleccionar talla y color para ver el producto</p>
                    <div class="precios_productos">
                        <p class="price_normal">$59,000.00</p>
                        <p class="price_oferta">$41,300.00</p>
                    </div>
                    <div class="variacion_talla">
                        <p>Talla:</p>
                        <ul>
                            <li class="variacion_li" data-variation="36"><button class="variation_talla_ejecut">36</button></li>
                            <li class="variacion_li" data-variation="37"><button class="variation_talla_ejecut">37</button></li>
                            <li class="variacion_li" data-variation="38"><button class="variation_talla_ejecut">38</button></li>
                            <li class="variacion_li" data-variation="39"><button class="variation_talla_ejecut">39</button></li>
                            <li class="variacion_li" data-variation="40"><button class="variation_talla_ejecut">40</button></li>
                        </ul>
                    </div>
                    <div class="variacion_color">
                        <p>Colores:</p>
                        <ul>
                            <li class="variacion_li" data-variation="Negro"><button class="variation_color_ejecut" style="background: #000"></button></li>
                            <li class="variacion_li" data-variation="Rojo"><button class="variation_color_ejecut" style="background: red"></button></li>
                        </ul>
                    </div>
                    <div class="cantidad_producto">
                        <input type="number" value="1">
                        <button>AÑADIR CARRITO</button>
                    </div>
                </div>
                <div class="producto_descripcion">
                    <div class="content_botones_mapa">
                        <a class="boton_mapa_active" id="butonMapaBogota" href="#">Descripción</a>
                        <a id="butonMapaMedellin" href="#">Información adicional</a>
                    </div>
                    <div id="boxMapaBogota" class="botones_comprar_content">
                        <div class="content_description_product">
                            <p>Botas militares de caña media para darle actitud al outfit haciendo realidad fantasías donde la rudeza y el poder son protagonistas. Elaboradas en cuero y lona con suela de caucho resistente.</p>
                        </div>
                    </div>
                    <div id="boxMapaMedellin" class="botones_comprar_content">
                        <div class="content_description_product">
                            <ul>
                                <li>Limpiar el exterior con un paño húmedo.</li>
                                <li>Usar limpiadores especiales para cuero (adquiéralos con nosotros).</li>
                                <li>Secar en la sombra.</li>
                                <li>Limpieza profesional en tintorería.</li>
                            </ul>
                            <h3>TABLA DE MEDIDAS</h3>
                            <img src="<?= IMGURL  ?>producto/producto_4.jpeg" alt="">
                        </div>
                    </div>
                </div>
                <div class="productos_relacionados">
                    <h3 class="title_producto_relacionado">PRODUCTOS RELACIONADOS</h3>
                    <div class="home_products_content pure-g">
                        <div class="home_products_content_items pure-u-1 pure-u-md-1-4">
                            <div class="products_content_items_img">
                                <div class="tienda_precio_oferta">
                                    <span>¡Oferta!</span>
                                </div>
                                <img class="tienda_img_1" src="<?= IMGURL ?>tienda/producto_1.jpeg" alt="">
                                <img class="tienda_img_2" src="<?= IMGURL ?>tienda/producto_2.jpeg" alt="">
                            </div>
                            <h4><a href="">SHORT GYM FREAK</a></h4>
                            <div class="tienda_product_stars">
                                <i class="icon-star-o"></i>
                                <i class="icon-star-o"></i>
                                <i class="icon-star-o"></i>
                                <i class="icon-star-o"></i>
                                <i class="icon-star-o"></i>
                            </div>
                            <h3>$55,000.00</h3>
                        </div>
                        <div class="home_products_content_items pure-u-1 pure-u-md-1-4">
                            <div class="products_content_items_img">
                                <img class="tienda_img_1" src="<?= IMGURL ?>tienda/producto_1.jpeg" alt="">
                                <img class="tienda_img_2" src="<?= IMGURL ?>tienda/producto_2.jpeg" alt="">
                            </div>
                            <h4><a href="">SHORT GYM FREAK</a></h4>
                            <div class="tienda_product_stars">
                                <i class="icon-star-o"></i>
                                <i class="icon-star-o"></i>
                                <i class="icon-star-o"></i>
                                <i class="icon-star-o"></i>
                                <i class="icon-star-o"></i>
                            </div>
                            <h3>$55,000.00</h3>
                        </div>
                        <div class="home_products_content_items pure-u-1 pure-u-md-1-4">
                            <div class="products_content_items_img">
                                <img class="tienda_img_1" src="<?= IMGURL ?>tienda/producto_1.jpeg" alt="">
                                <img class="tienda_img_2" src="<?= IMGURL ?>tienda/producto_2.jpeg" alt="">
                            </div>
                            <h4><a href="">SHORT GYM FREAK</a></h4>
                            <div class="tienda_product_stars">
                                <i class="icon-star-o"></i>
                                <i class="icon-star-o"></i>
                                <i class="icon-star-o"></i>
                                <i class="icon-star-o"></i>
                                <i class="icon-star-o"></i>
                            </div>
                            <h3>$55,000.00</h3>
                        </div>
                        <div class="home_products_content_items pure-u-1 pure-u-md-1-4">
                            <div class="products_content_items_img">
                                <img class="tienda_img_1" src="<?= IMGURL ?>tienda/producto_1.jpeg" alt="">
                                <img class="tienda_img_2" src="<?= IMGURL ?>tienda/producto_2.jpeg" alt="">
                            </div>
                            <h4><a href="">SHORT GYM FREAK</a></h4>
                            <div class="tienda_product_stars">
                                <i class="icon-star-o"></i>
                                <i class="icon-star-o"></i>
                                <i class="icon-star-o"></i>
                                <i class="icon-star-o"></i>
                                <i class="icon-star-o"></i>
                            </div>
                            <h3>$55,000.00</h3>
                        </div>
                    </div>
                
                </div>
           </div>
        </div>
    </main>
<?php
get_footer();
?>