<?php
/*
 * @Archivo: class-CustomWoo.php
 * @Descripcion: Clase para funciones Personalizadas de woocommerce
 *
 */

// Cargamos wordpress
require_once(explode("wp-content", __FILE__)[0] . "wp-load.php");

class Woocommerce_Custom{
	/*
	|-------------------------------------------------------------------------------
	| Function Productos Home
	|-------------------------------------------------------------------------------
	*/

	public function printProductosHome( $slug_category = NULL, $featured = NULL ){

		$params = array(
				'orderby' => 'date'
			,	'order' => 'ASC'
			,	'limit' => '4'
			,	'page'  => 0
			,	'paginate' => true
			,	'visibility' => 'visible'
		);


		if($slug_category != NULL){
			$params['category'] = array( $slug_category );
		}

		//DESTACADOS
		if (!empty($featured) &&  $featured) {

			$params['tax_query'] = array(
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'featured',
					'operator' => 'IN',
				)
			);
		}
		
		$queryProduct = new WC_Product_Query( $params );
		$products = $queryProduct->get_products();

		return $products;

	}
	/*
	|-------------------------------------------------------------------------------
	| Function Productos Home
	|-------------------------------------------------------------------------------
	*/

	public function printProductosHomeDate(){


		$params = array(
				'orderby' => 'date'
			,	'order' => 'DESC'
			,	'limit' => '30'
			,	'page'  => 0
			,	'paginate' => true
			,	'stock_status' => 'instock'
			,	'visibility' => 'visible'
		);
	

		$queryProduct = new WC_Product_Query( $params );
		$products = $queryProduct->get_products();

		return $products;
		
	}

	/*
	|-------------------------------------------------------------------------------
	| Function get products according filter
	|-------------------------------------------------------------------------------
	*/
	public function getProductStore( $taxonomy, $category = NULL, $order = NULL, $posts_per_page = NULL, $paged = NULL, $price = NULL, $featured = NULL, $search = NULL ){
		global $wp_query;
		
		$printProduct = array();
		// Args en general
		$args = array(
				'post_type'			=> 'product'
			,	'post_status'		=> 'publish'
			,	'meta_query'		=>  array(
											'relation' => 'AND', array(
												'key' => '_stock_status',
												'value' => 'instock'
											)
										)
		);

		if ( $search != NULL ) {
			$args['s'] = $search;
		}
		// Args orden si aplica
		if ( $order == 'date' ) {
			$args['orderby'] = 'date';
			$args['order'] = 'DESC';
		}elseif ( $order == 'priceasc' ) {
			$args['orderby']   = 'meta_value_num';
			$args['meta_key']  = '_price';
			$args['order'] = 'ASC';
		}else{
			$args['orderby']   = 'meta_value_num';
			$args['meta_key']  = '_price';
			$args['order'] = 'DESC';
		}

		// Args por featured
		if( !empty( $featured ) &&  $featured ){
			$args[ 'tax_query' ] = array(
										array(
											'taxonomy' => 'product_visibility',
											'field'    => 'name',
											'terms'    => 'featured',
											'operator' => 'IN',
										)
									);
			
		}
		//Rango de precios
		if (!empty($price)) {
			$a_price = explode(',', $price);
			if (isset($a_price[0]) && isset($a_price[1])) {
				$min_price = (float) $a_price[0];
				$max_price = (float) $a_price[1];
				$args['meta_query'] = array(
					'relation' => 'AND',	array(
						array(
							'key'     => '_regular_price',
							'value'   => array($min_price, $max_price),
							'type'    => 'numeric',
							'compare' => 'BETWEEN',
						)
					),	array(
						'key' => '_stock_status',
						'value' => 'instock'
					)
				);
			}
		}
		//Condicionales Segun categoria y Marca
		if( !empty( $taxonomy ) && ! is_array( $taxonomy ) ){
			// Catgoria descuento
			if ( $category == 'Sale' ) {
				// Ordenar por descuento
				if( empty( $order ) ){
					$args[ 'meta_key' ] = 'order_sale';
				}
				
				$args['meta_query'] = array(
					'relation' => 'AND',	array(
						'relation' => 'OR',	array(
							'key'           => '_sale_price',
							'value'         => 0,
							'compare'       => '>',
							'type'          => 'numeric'
						),	array(
							'key'           => 'sale',
							'value'         => 0,
							'compare'       => '>',
							'type'          => 'numeric'
						)
					),	array(
						'key' => '_stock_status',
						'value' => 'instock'
					)
				);
			// Si no es Catgoria descuento
			}else{
				// Si existe category
				if( ! empty( $category ) ){
					$args[ 'tax_query' ] = array(
												array(
													'taxonomy'  => $taxonomy,
													'field'     => 'term_id',
													'operator' => 'IN',
													'terms'     => $category
													)
												);
				}
				//DESTACADOS
				if( !empty( $featured ) &&  $featured ){
					$args[ 'tax_query' ] = array(
												'relation' => 'AND'
											,	array(
													'taxonomy'  => $taxonomy,
													'field'     => 'term_id',
													'terms'     => array( $category ),
												)
											,	array(
													'taxonomy' => 'product_visibility',
													'field'    => 'name',
													'terms'    => 'featured',
													'operator' => 'IN',
												)
											) ;
					
				}
			}
		
		} elseif (is_array($taxonomy) || is_array($category)) {
			$args['tax_query'] = array(
				'relation' => 'AND',	array(
					'taxonomy'  => $taxonomy[0],
					'field'     => 'term_id',
					'terms'     => array($category[0]),
				),	array(
					'taxonomy'  => $taxonomy[1],
					'field'     => 'term_id',
					'terms'     => array($category[1]),
				)
			);
			//DESTACADOS
			if (!empty($featured) &&  $featured) {
				$featuredArray = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'featured',
					'operator' => 'IN',
				);
				array_push($args['tax_query'], $featuredArray);
			}
		}
		//paginas por Hoja
		if (!empty($posts_per_page)) {
			$args['posts_per_page'] = $posts_per_page;
		}
		//numero de pagina
		if (!empty($paged)) {
			$args['paged'] = $paged;
			$args['nopaging'] = false;
		}
		//Declarar Array
		$post_type_arr = array();
		$wp_query = new WP_Query( $args );
		while( $wp_query->have_posts() ) : $wp_query->the_post();
			//resetear marca
			$post_id = get_the_id();
			// Save the Date
			$post_type_arr[ $post_id ]['id'] = $post_id;
			// Get attribs from image
			$imgAttr = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), '400x600' );
			$post_type_arr[ $post_id ]['urlImg'] = $imgAttr[ 0 ];
			// Traemos el contenido a traves de "get_the_content" y le damos formato
			$get_content = apply_filters( 'the_content', get_the_content() );
			$get_content = str_replace( ']]>', ']]&gt;', $get_content );
			$get_content = preg_replace( '/<img[^>]+./', '', $get_content );
			$post_type_arr[ $post_id ]['title'] = get_the_title();
			$post_type_arr[ $post_id ]['link'] = get_permalink();
			$post_type_arr[ $post_id ]['content'] = $get_content;
			$post_type_arr[ $post_id ]['excerpt'] = get_the_excerpt();
			$post_type_arr[ $post_id ]['listCategory'] = get_the_category_list();
			$post_type_arr[ $post_id ]['date'] = strtotime ( get_the_date( 'Y-m-d' ) );
			$post_type_arr[ $post_id ]['isDestacado'] = $this->haveTerm( $post_id, 'product_tag', 'destacado' );

			$product = wc_get_product( $post_id );
			$attachment_ids = $product->get_gallery_image_ids();
			foreach( $attachment_ids as $attachment_id ) {
				$post_type_arr[ $post_id ]['galery'] = wp_get_attachment_url( $attachment_id );
				break;
			}
			// Datos Woocommerce
			$arrPrices = $this->GetPricesWoocommerce( $post_id );
			$post_type_arr[ $post_id ]['prices'] = $arrPrices;
			
		endwhile;
		if (empty($post_type_arr)) {
			$post_type_arr = '';
		} else {
			if (!empty($paged)) {
				$post_type_arr['data']['pagination'] = $wp_query->max_num_pages;
			}
		}

	
		wp_reset_postdata();
		wp_reset_query();
		return $post_type_arr;
	}

	/*
	|-------------------------------------------------------------------------------
	| Function get products according filter
	|-------------------------------------------------------------------------------
	*/
	public function getProductStoreSale( $taxonomy, $category = NULL, $order = NULL, $posts_per_page = NULL, $paged = NULL, $price = NULL, $featured = NULL, $orden = NULL ){
		global $wp_query;
		// Args en general
		$args = array(
				'post_type'			=> 'product'
			,	'post_status'		=> 'publish'
			,   'post__in' => array_merge(array(0), wc_get_product_ids_on_sale())
			,	'meta_query'		=>  array(
											array(
												'key' => '_stock_status',
												'value' => 'instock'
											)
										)
		);
		// Args orden si aplica
		if ( $order == 'date' ) {
			$args['orderby'] = 'date';
			$args['order'] = 'DESC';
		}elseif ( $order == 'priceasc' ) {
			$args['orderby']   = 'meta_value_num';
			$args['meta_key']  = '_price';
			$args['order'] = 'ASC';
		}else{
			$args['orderby']   = 'meta_value_num';
			$args['meta_key']  = '_price';
			$args['order'] = 'DESC';
		}

		if (!empty($orden)) {

			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = '_price'; 

			if($orden == 'menor'){
				$args['order']    = 'ASC';
			}
			if($orden == 'mayor'){
				$args['order']    = 'DESC';
			}
			
		} 
		// Args por featured
		if( !empty( $featured ) &&  $featured ){
			$args[ 'tax_query' ] = array(
										array(
											'taxonomy' => 'product_visibility',
											'field'    => 'name',
											'terms'    => 'featured',
											'operator' => 'IN',
										)
									);
			
		}
		//Rango de precios
		if (!empty($price)) {
			$a_price = explode(',', $price);
			if (isset($a_price[0]) && isset($a_price[1])) {
				$min_price = (float) $a_price[0];
				$max_price = (float) $a_price[1];
				$args['meta_query'] = array(
					'relation' => 'AND',	array(
						array(
							'key'     => '_regular_price',
							'value'   => array($min_price, $max_price),
							'type'    => 'numeric',
							'compare' => 'BETWEEN',
						)
					),	array(
						'key' => '_stock_status',
						'value' => 'instock'
					)
				);
			}
		}
		//Condicionales Segun categoria y Marca
		if( !empty( $taxonomy ) && ! is_array( $taxonomy ) ){
			// Catgoria descuento
			if ( $category == 'Sale' ) {
				// Ordenar por descuento
				if( empty( $order ) ){
					$args[ 'meta_key' ] = 'order_sale';
				}
				
				$args['meta_query'] = array(
					'relation' => 'AND',	array(
						'relation' => 'OR',	array(
							'key'           => '_sale_price',
							'value'         => 0,
							'compare'       => '>',
							'type'          => 'numeric'
						),	array(
							'key'           => 'sale',
							'value'         => 0,
							'compare'       => '>',
							'type'          => 'numeric'
						)
					),	array(
						'key' => '_stock_status',
						'value' => 'instock'
					)
				);
			// Si no es Catgoria descuento
			}else{
				// Si existe category
				if( ! empty( $category ) ){
					$args[ 'tax_query' ] = array(
												array(
													'taxonomy'  => $taxonomy,
													'field'     => 'term_id',
													'operator' => 'IN',
													'terms'     => $category
													)
												);
				}
				//DESTACADOS
				if( !empty( $featured ) &&  $featured ){
					$args[ 'tax_query' ] = array(
												'relation' => 'AND'
											,	array(
													'taxonomy'  => $taxonomy,
													'field'     => 'term_id',
													'terms'     => array( $category ),
												)
											,	array(
													'taxonomy' => 'product_visibility',
													'field'    => 'name',
													'terms'    => 'featured',
													'operator' => 'IN',
												)
											) ;
					
				}
			}
		
		} elseif (is_array($taxonomy) || is_array($category)) {
			$args['tax_query'] = array(
				'relation' => 'AND',	array(
					'taxonomy'  => $taxonomy[0],
					'field'     => 'term_id',
					'terms'     => array($category[0]),
				),	array(
					'taxonomy'  => $taxonomy[1],
					'field'     => 'term_id',
					'terms'     => array($category[1]),
				)
			);
			//DESTACADOS
			if (!empty($featured) &&  $featured) {
				$featuredArray = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'featured',
					'operator' => 'IN',
				);
				array_push($args['tax_query'], $featuredArray);
			}
		}
		//paginas por Hoja
		if (!empty($posts_per_page)) {
			$args['posts_per_page'] = $posts_per_page;
		}
		//numero de pagina
		if (!empty($paged)) {
			$args['paged'] = $paged;
			$args['nopaging'] = false;
		}
		//Declarar Array
		$post_type_arr = array();
		$wp_query = new WP_Query( $args );
		while( $wp_query->have_posts() ) : $wp_query->the_post();
			//resetear marca
			$post_id = get_the_id();
			// Save the Date
			$post_type_arr[ $post_id ]['id'] = $post_id;
			// Get attribs from image
			$imgAttr = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), '400x600' );
			$post_type_arr[ $post_id ]['urlImg'] = $imgAttr[ 0 ];
			// Traemos el contenido a traves de "get_the_content" y le damos formato
			$get_content = apply_filters( 'the_content', get_the_content() );
			$get_content = str_replace( ']]>', ']]&gt;', $get_content );
			$get_content = preg_replace( '/<img[^>]+./', '', $get_content );
			$post_type_arr[ $post_id ]['title'] = get_the_title();
			$post_type_arr[ $post_id ]['link'] = get_permalink();
			$post_type_arr[ $post_id ]['content'] = $get_content;
			$post_type_arr[ $post_id ]['excerpt'] = get_the_excerpt();
			$post_type_arr[ $post_id ]['listCategory'] = get_the_category_list();
			$post_type_arr[ $post_id ]['date'] = strtotime ( get_the_date( 'Y-m-d' ) );
			$post_type_arr[ $post_id ]['isDestacado'] = $this->haveTerm( $post_id, 'product_tag', 'destacado' );

			$product = wc_get_product( $post_id );
			$attachment_ids = $product->get_gallery_image_ids();
			foreach( $attachment_ids as $attachment_id ) {
				$post_type_arr[ $post_id ]['galery'] = wp_get_attachment_url( $attachment_id );
				break;
			}
			
			// Datos Woocommerce
			$arrPrices = $this->GetPricesWoocommerce( $post_id );
			$post_type_arr[ $post_id ]['prices'] = $arrPrices;
			
		endwhile;
		if (empty($post_type_arr)) {
			$post_type_arr = '';
		} else {
			if (!empty($paged)) {
				$post_type_arr['data']['pagination'] = $wp_query->max_num_pages;
			}
		}

	
		wp_reset_postdata();
		wp_reset_query();
		return $post_type_arr;
	}

	/*
	|-------------------------------------------------------------------------------
	| Function Print Array products Shop
	|-------------------------------------------------------------------------------
	*/
	public function printProductsArr( $arrayProduct ){
		// Almacenamos html en buffers
		ob_start();
		// Mostramos productos
		foreach ( $arrayProduct as $key => $value ):
			if ( $key === 'data' ) {
				continue;
			}
			?>
			<div class="home_products_content_items pure-u-1 pure-u-md-1-4 product-<?php echo $value['id'] ?>">
                <div class="products_content_items_img">
					<?php
					if( $value['prices']['enOferta']){
						?>
						<div class="tienda_precio_oferta">
                        	<span>¡Oferta!</span>
                    	</div>
						<?php
					}
					?>
                    <a href="<?= $value['link'] ?>">
						<img class="tienda_img_1" src="<?php echo $value['urlImg'] ?>" alt="<?php echo $value['title'] ?>" title="<?php echo $value['title'] ?>">
						<?php
						
						if(isset($value['galery'])){
							?>
							<img class="tienda_img_2" src="<?php echo $value['galery'] ?>" alt="<?php echo $value['title'] ?>" title="<?php echo $value['title'] ?>">
							<?php
						}else{
							?>
							<img class="tienda_img_2" src="<?php echo $value['urlImg'] ?>" alt="<?php echo $value['title'] ?>" title="<?php echo $value['title'] ?>">
							<?php
						}
						?>
					</a>
                </div>
                <h4><a href="<?= $value['link'] ?>"><?= $value['title'] ?></a></h4>
                <div class="tienda_product_stars">
                    <i class="icon-star-o"></i>
                    <i class="icon-star-o"></i>
                    <i class="icon-star-o"></i>
                    <i class="icon-star-o"></i>
                    <i class="icon-star-o"></i>
                </div>
				<!-- Producto en Oferta + Simple -->
				<?php if( $value['prices']['enOferta'] && $value['prices']['tipoProducto'] == 'simple' ): ?>
					<h3><span class="home_product_price_old">$<?= $value['prices']['precioRegular'] ?></span><span class="home_product_price_new">$<?=$value['prices']['precio']?></span></h3>
				<!-- Producto SIN Oferta + Simple -->
				<?php elseif( $value['prices']['enOferta'] == FALSE && $value['prices']['tipoProducto'] == 'simple' ): ?>
					<h3>$
						<?php
							echo $value['prices']['precio']; 
						?>
					</h3>
				<?php elseif( $value['prices']['tipoProducto'] == 'variable' ): ?>
					<h3>$<?php echo $value['prices']['precioMin'] ?> - $<?php echo $value['prices']['precioMax'] ?></h3>
				<?php endif; ?>
            </div>
			<?php
		endforeach;
		// volcamos buffer a variable
		$html = ob_get_clean();
		// Retornamos
		return $html;
	}



	/*
	|-------------------------------------------------------------------------------
	| Function check if have the specific term
	|-------------------------------------------------------------------------------
	*/
	public function haveTerm( $post_id, $theTerm, $nameTag ){
		// get the terms
		$listTags = get_the_terms( $post_id, $theTerm );
		$arrTags = array();
		if( ! empty( $listTags ) && is_array( $listTags ) ){
			// Creamos nuevo array con slug de term
			foreach ( $listTags as $term ) {
				$arrTags[] = $term->slug;
			}
		}
		// Verificamos si tiene el tag a buscar
		$checkTag = in_array( $nameTag, $arrTags );
		// var_dump( $checkTag );
		return $checkTag;
	}
	/*
	|-------------------------------------------------------------------------------
	| Function get all prices product
	|-------------------------------------------------------------------------------
	*/
	public function GetPricesWoocommerce( $idProduct ){
		// Declaramos array a devolver
		$arrPrices = array();
		// Traemos producto woocommerce
		$product = wc_get_product( $idProduct );
		//No llamar la funcion cada vez que se quiere verificar si el produ es simple o variable
		$tipo_product = $product->get_type();
		// Esta en oferta?
		$producto_oferta = $product->is_on_sale();
		/*Verificar si el producto esta en oferta*/
		if( $producto_oferta ){
			// Producto Simple?
			if ( $tipo_product == 'simple' ) {
				$ventaP =  wc_get_price_including_tax($product);
				$regularP = wc_get_price_including_tax($product,array('price'=>$product->get_regular_price()));
				// Respuesta Array
				$arrPrices[ 'enOferta' ] = TRUE;
				$arrPrices[ 'tipoProducto' ] = 'simple';
				$arrPrices[ 'precio' ] = number_format( $ventaP, 0, ',', '.' );
				$arrPrices[ 'precioRegular' ] = number_format( $regularP, 0, ',', '.' );
			// Producto Variable?
			}elseif ( $tipo_product == 'variable' ) {
				$prices = $product->get_variation_prices( true );
				$min_price     = current( $prices['price'] );
				$max_price     = end( $prices['price'] );
				// Respuesta Array
				$arrPrices[ 'enOferta' ] = TRUE;
				$arrPrices[ 'tipoProducto' ] = 'variable';
				$arrPrices[ 'precioMin' ] = number_format( $min_price, 0, ',', '.' );
				$arrPrices[ 'precioMax' ] = number_format( $max_price, 0, ',', '.' );
			}
			//$percentage = round( ( ( $regularP - $ventaP ) / $regularP ) * 100 );
		//No esta en oferta
		}else{
			if ( $tipo_product == 'simple' ) {
				// Respuesta Array
				$arrPrices[ 'enOferta' ] = FALSE;
				$arrPrices[ 'tipoProducto' ] = 'simple';
				$arrPrices[ 'precio' ] = number_format( wc_get_price_including_tax($product), 0, ',', '.' );
			}elseif ( $tipo_product == 'variable' ) {
				$prices = $product->get_variation_prices( true );
				$min_price     = current( $prices['price'] );
				$max_price     = end( $prices['price'] );
				// Respuesta Array
				$arrPrices[ 'enOferta' ] = FALSE;
				$arrPrices[ 'tipoProducto' ] = 'variable';
				$arrPrices[ 'precioMin' ] = number_format( $min_price, 0, ',', '.' );
				$arrPrices[ 'precioMax' ] = number_format( $max_price, 0, ',', '.' );
			}
		}
		// Devolvemos Array
		return $arrPrices;
	}
	/*
	|-------------------------------------------------------------------------------
	| Function Print Shortcode Order Special
	|-------------------------------------------------------------------------------
	*/
	public function print_product_order_session(){
		$catExepcion = array(
				'Regalo'
			,	'Sin categorizar'
		);
		$categoryTienda = get_terms('product_cat', array(
			'orderby'	=> 'ID',	
			'order'		=> 'DESC',	
			'parent'	=> 0
		));
		global $wp_query;
		//Array Drop Tienda
		$post_type_arr = array();
		//Arrary Range Producto
		$range_prices = array();
		foreach ($categoryTienda as $key => $parentTax) {
			$nameCategori = $parentTax->name;
			if (in_array($nameCategori, $catExepcion)) {
				continue;
			}
			$args = array(
				'post_type'			=> 'product',
				'post_status'		=> 'publish',
				'orderby'			=> 'date',	
				'order'				=> 'DESC',	
				'nopaging'			=> true,	
				'tax_query'			=> array(
					array(
						'taxonomy'  => 'product_cat',
						'field'     => 'term_id',
						'terms'     => array($parentTax->term_id),
					)
				),	'meta_query'		=> array(
					array(
						'key' => '_stock_status',
						'value' => 'instock'
					)
				)
			);
			$wp_query = new WP_Query($args);
			while ($wp_query->have_posts()) : $wp_query->the_post();
				$post_id = get_the_id();
				$ArrayCategory = wp_get_post_terms(
					$post_id,
					'product_cat',
					array(
						'fields' => 'all',	'parent' => $parentTax->term_id
					)
				);
				$termsObject 	= current($ArrayCategory);
				$categori 	= '';
				$slug 		= '';	
				$term_id 	= '';	
				$drop 		= '';
				if(!empty($ArrayCategory)){
					$categori 		= $termsObject->name;
					$slug 			= $termsObject->slug;
					$term_id 		= $termsObject->term_id;
					$drop 			= 'drop' . $termsObject->slug;
				}
			
				$fatherTerm 	= $parentTax->slug;
				$fatherTermID 	= $parentTax->term_id;
				$slugdinamic 	= $slug;
				//Rango de Precios
				$range_prices = $this->rangePreciosArray($range_prices, $fatherTermID, $term_id, $post_id);
				if (isset($post_type_arr['DropTienda'])) {
					if (isset($$fatherTerm)) {
						if (!isset($$drop)) {
							$$drop = true;
							$remplace = $listchild . '<li class="cat-item">
											<button type="button" class="btnTaxProducto" data-tax="product_cat" data-name="' . $categori . '" value="' . $term_id . '">' . $categori . '</button>
										</li>';
							$post_type_arr['DropTienda'] = str_replace($listchild, $remplace, $post_type_arr['DropTienda']);
							$listchild = $remplace;
						}
					} else {
						$$drop = true;
						$listchild = '<li class="cat-item">
											<button type="button" class="btnTaxProducto" data-tax="product_cat" data-name="' . $categori . '" value="' . $term_id . '">' . $categori . '</button>
										</li>';
						$$fatherTerm = '<li class="cat-item">
												<button type="button" class="btnTaxProducto" data-tax="product_cat" data-name="' . $parentTax->name . '" value="' . $parentTax->term_id . '">' . $parentTax->name . '</button>
												<ul style="display: none;">' . $listchild . '</ul>
											</li>';
						$post_type_arr['DropTienda'] .= $$fatherTerm;
					}
				} else {
					$$drop = true;
					$listchild = '<li class="cat-item">
										<button type="button" class="btnTaxProducto" data-tax="product_cat" data-name="' . $categori . '" value="' . $term_id . '">' . $categori . '</button>
									</li>';
					$$fatherTerm = '<li class="cat-item">
											<button type="button" class="btnTaxProducto" data-tax="product_cat" data-name="' . $parentTax->name . '" value="' . $parentTax->term_id . '">' . $parentTax->name . '</button>
											<ul style="display: none;">' . $listchild . '</ul>
										</li>';
					$post_type_arr['DropTienda'] = $$fatherTerm;
				
				}
			endwhile;
		}
		wp_reset_postdata();
		wp_reset_query();
		//Guardar array precios
		update_option('preciosArray', $range_prices);
		//Retornar Array Drop Tiendas
		return $post_type_arr;
	}
	/*
	|-------------------------------------------------------------------------------
	| Function Array Rango Precios
	|-------------------------------------------------------------------------------
	*/
	public function rangePreciosArray( $range_prices, $fatherTermID, $term_id, $idProduct ){
		//inicio range precios
		$productiNIT = wc_get_product( $idProduct );
		if ( $productiNIT->get_type() == 'simple' ) {
			if( $productiNIT->is_on_sale() ){
				$saleview = $productiNIT -> get_sale_price();
				$priceView = $productiNIT -> get_regular_price();
			}else{
				$priceView = $productiNIT -> get_regular_price();
			}
		}else{
			if( $productiNIT->is_on_sale() ){
				$saleview = $productiNIT -> get_sale_price();
				$priceView = $productiNIT -> get_regular_price();
			}else{
				$priceView = $productiNIT -> get_regular_price();
			}
		}
		//Asignacion Valores
		$max_temporal = round( $priceView );
		$min_temporal = round( $priceView );
		if ( isset( $saleview ) ) {
			$min_temporal = round( $saleview );
		}elseif ( isset($priceViewVar) ) {
			$max_temporal = round( $priceViewVar );
		}
		//Array Precios Padre
		$range_prices = $this -> asignarvalores( $range_prices, $fatherTermID, $max_temporal, $min_temporal);
		//Array Precios hijo
		$range_prices = $this -> asignarvalores( $range_prices, $term_id, $max_temporal, $min_temporal);
		//Array total
		$range_prices = $this -> asignarvalores( $range_prices, 'total', $max_temporal, $min_temporal);
		return $range_prices;
		
	}

	/*
	|-------------------------------------------------------------------------------
	| Function Array Rango Precios
	|-------------------------------------------------------------------------------
	*/
	public function asignarvalores( $range_prices, $key, $max_temporal, $min_temporal){
		//Array total
		if ( isset( $range_prices[$key] ) ) {
			if ( $max_temporal > $range_prices[$key]['max'] ) {
				$range_prices[$key]['max'] = $max_temporal;
			}elseif ( $max_temporal < $range_prices[$key]['min'] ) {
				$range_prices[$key]['min'] = $max_temporal;
				
			}
			if ( $min_temporal > $range_prices[$key]['max'] ) {
				$range_prices[$key]['max'] = $min_temporal;
			}elseif ( $min_temporal < $range_prices[$key]['min'] ) {
				$range_prices[$key]['min'] = $min_temporal;
				
			}
			
		}else{
			if ( $min_temporal > $max_temporal ) {
				$range_prices[$key]['max'] = $min_temporal;
				$range_prices[$key]['min'] = $max_temporal;
			}else{
				$range_prices[$key]['max'] = $max_temporal;
				$range_prices[$key]['min'] = $min_temporal;
				
			}
		}
		return $range_prices;
	}

}

?>