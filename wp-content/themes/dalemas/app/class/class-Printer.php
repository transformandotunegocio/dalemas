<?php
/**
*
* Printer helper
*/


/**
* Printer content class
*/
class Printer {


	/**
	* Constructor
	*/
	public function __construct() {

	}


	/**
	* Function to print pages in Home sections
	*
	* @param { string } name_page: nombre de la pagina a imprimir
	* @param { string } name_meta: nombre del valor Meta a imprimir (si tiene)
	*/
	function PageSection ( $name_page, $name_meta = NULL ){

		$print_page_arr = array();

		global $wp_query;

		query_posts('pagename=' . $name_page);

		if( have_posts() ) :
			while ( have_posts() ) : the_post();

				// Traemos el contenido a traves de "get_the_content" y le damos formato
				$get_content = apply_filters( 'the_content', get_the_content() );
				$get_content = str_replace( ']]>', ']]&gt;', $get_content );

				// Traemos la url de la imagen
				$get_image = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_id() ), 'full' );
				$get_image = $get_image[0];

				// Asignamos valores
				$print_page_arr['title'] = get_the_title();
				$print_page_arr['content'] = $get_content;
				$print_page_arr['excerpt'] = get_the_excerpt();
				$print_page_arr['link'] = get_permalink();
				$print_page_arr['img'] = $get_image;
				$print_page_arr['id'] = get_the_id();

				if( $name_meta ){
					$print_page_arr['metaOptions'] = get_post_meta( get_the_id(), $name_meta );
				}

			endwhile;
		endif;

		wp_reset_query();

		return $print_page_arr;

	}

	/**
	* Function to print the Custom post type
	*
	* @param { string } post_type: tipo de post a imprimir
	* @param { string } cat_id: Nombre de la categoria a imprimir
	*/
	function TotaTypeSection( $post_type, $cat_id = NULL, $tax = NULL, $taxTerm = NULL ){

		global $post;
		global $wp_query, $wp_the_query;

		$post_count = 1;

		$args = array(
				'post_type'             => $post_type
			,   'post_status'           => 'publish'
			,   'orderby'               => 'date'
			,   'nopaging'				=> true
		);

		if( !empty( $cat_id ) ){
			$args[ 'cat' ] = $cat_id;
		}

		// Filtrar por taxonomy
		if( !empty( $tax ) ){

			$argTax = array(
					'taxonomy' => $tax // Nombre de la taxonomía.
				,	'field'    => 'slug'
				,	'terms'    => $taxTerm // El SLUG de la categoría a filtrar.
			);

			$args[ 'tax_query' ] = array( $argTax );
		}

		$wp_query = new WP_Query( $args );

		$TotalProduct = $wp_query->post_count;

		wp_reset_postdata();
		wp_reset_query();

		return $TotalProduct;

	}

	/**
	* Function to print the Custom post type
	*
	* @param { string } post_type: tipo de post a imprimir
	* @param { string } cat_id: Nombre de la categoria a imprimir
	*/
	function TotaTypeSectionPendiente( $post_type, $cat_id = NULL, $tax = NULL, $taxTerm = NULL ){

		global $post;
		global $wp_query, $wp_the_query;

		$post_count = 1;

		$args = array(
				'post_type'             => $post_type
			,   'post_status'           => 'pending'
			,   'orderby'               => 'date'
			,   'nopaging'				=> true
		);

		if( !empty( $cat_id ) ){
			$args[ 'cat' ] = $cat_id;
		}

		// Filtrar por taxonomy
		if( !empty( $tax ) ){

			$argTax = array(
					'taxonomy' => $tax // Nombre de la taxonomía.
				,	'field'    => 'slug'
				,	'terms'    => $taxTerm // El SLUG de la categoría a filtrar.
			);

			$args[ 'tax_query' ] = array( $argTax );
		}

		$wp_query = new WP_Query( $args );

		$TotalProduct = $wp_query->post_count;

		wp_reset_postdata();
		wp_reset_query();

		return $TotalProduct;

	}

	/**
	* Function to print the Custom post type
	*
	* @param { string } post_type: tipo de post a imprimir
	* @param { mixed } num_post: numero de post a imprimir
	* @param { string } img_size: Tamanio de imagen a imprimir
	* @param { string } name_meta: nombre metadato del post
	* @param { string } paged: Pagina actual
	* @param { string } posts_per_page: post por pagina
	* @param { string } cat_id: Nombre de la categoria a imprimir
	*/
	function PostTypeSection (
		$post_type, $num_post, $img_size = NULL, $name_meta = NULL,
		$paged = NULL, $posts_per_page = NULL, $cat_id = NULL,
		$tax = NULL, $taxTerm = NULL, $orderby = NULL, $tag_id = NULL, $idEx = NULL ){

		global $post;
		global $wp_query, $wp_the_query;

		$post_count = 1;

		$post_type_arr = array();

		$args = array(
				'post_type'             => $post_type
			,   'post_status'           => 'publish'
			,   'orderby'               => 'date'
			,	'order'   				=> (!empty( $orderby )) ? 'ASC' : 'DESC'
			,   'showposts'             => $num_post
			,   'posts_per_page'        => $posts_per_page
			,   'paged'                 => $paged
		);

		
		if( $idEx != NULL ){
			$args[ 'post__not_in' ] = $cat_id;
		}

		if( !empty( $cat_id ) ){
			$args[ 'cat' ] = $cat_id;
		}

		if( !empty( $tag_id ) ){
			$args[ 'tag_id' ] = $tag_id;
		}

		// Filtrar por taxonomy
		if( !empty( $tax ) ){

			$argTax = array(
					'taxonomy' => $tax // Nombre de la taxonomía.
				,	'field'    => 'slug'
				,	'terms'    => $taxTerm // El SLUG de la categoría a filtrar.
			);

			$args[ 'tax_query' ] = array( $argTax );
		}

		// Hablitar post destacados solo en el home
		if( ! is_home() ){
			$args[ 'ignore_sticky_posts' ] = 1;
		}

		$wp_query = new WP_Query( $args );

		while( $wp_query->have_posts() ) : $wp_query->the_post();

			$post_id = get_the_id();

			// Ignoramos post sobrantes, en caso de existencia de post destacados
			if( $post_count <= $num_post ){
				$post_count++;
			}else{
				break;
			}

			// Save the Date
			$post_type_arr[ $post_id ]['id'] = $post_id;

			// Get the Date
			$post_type_arr[ $post_id ]['date'] = get_the_date( 'd F, Y' );

			// Get attribs from image
			$imgAttr = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), $img_size );
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

			// Traemos Meta del post solicitado
			if( ! empty( $name_meta ) ){
				// Get attribs for Name Meta
				$listMeta = get_post_meta( $post->ID, $name_meta );
				$post_type_arr[ $post_id ][$name_meta] = $listMeta;

			}


		endwhile;


		wp_reset_postdata();
		wp_reset_query();

		return $post_type_arr;

	}


	/*
	* Function for the print paged
	*
	*/
	function Pagination( $wp_query ){

		$big = 999999999;

		$arrgs = array(
			'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format' => '/page/%#%',
			'current' => max( 1, get_query_var('paged') ),
			'total' => $wp_query->max_num_pages,
			'end_size' => 1,
			'mid_size' => 2,
			'prev_text' => '<i class="fa fa-chevron-left"></i>',
			'next_text' => '<i class="fa fa-chevron-right"></i>'
		);

		$post_pagination = paginate_links( $arrgs );

		return $post_pagination;

	}

	/**
	* Function to print the Custom post type
	*
	* @param { string } post_type: tipo de post a imprimir
	* @param { mixed } num_post: numero de post a imprimir
	* @param { string } img_size: Tamanio de imagen a imprimir
	* @param { string } name_meta: nombre metadato del post
	* @param { string } paged: Pagina actual
	* @param { string } posts_per_page: post por pagina
	* @param { string } cat_id: Nombre de la categoria a imprimir
	*/
	function PostTypeSectionPendiente (
		$post_type, $num_post, $img_size = NULL, $name_meta = NULL,
		$paged = NULL, $posts_per_page = NULL, $cat_id = NULL,
		$tax = NULL, $taxTerm = NULL ){

		global $post;
		global $wp_query, $wp_the_query;

		$post_count = 1;

		$post_type_arr = array();

		$args = array(
				'post_type'             => $post_type
			,   'post_status'           => 'pending'
			,   'orderby'               => 'date'
			// ,    'ignore_sticky_posts'   => 1
			,   'showposts'             => $num_post
			,   'posts_per_page'        => $posts_per_page
			,   'paged'                 => $paged
		);

		if( !empty( $cat_id ) ){
			$args[ 'cat' ] = $cat_id;
		}

		// Filtrar por taxonomy
		if( !empty( $tax ) ){

			$argTax = array(
					'taxonomy' => $tax // Nombre de la taxonomía.
				,	'field'    => 'slug'
				,	'terms'    => $taxTerm // El SLUG de la categoría a filtrar.
			);

			$args[ 'tax_query' ] = array( $argTax );
		}

		// Hablitar post destacados solo en el home
		if( ! is_home() ){
			$args[ 'ignore_sticky_posts' ] = 1;
		}

		$wp_query = new WP_Query( $args );

		while( $wp_query->have_posts() ) : $wp_query->the_post();

			$post_id = get_the_id();

			// Ignoramos post sobrantes, en caso de existencia de post destacados
			if( $post_count <= $num_post ){
				$post_count++;
			}else{
				break;
			}

			// Save the Date
			$post_type_arr[ $post_id ]['id'] = $post_id;

			// Get the Date
			$date = get_the_date( 'F d \d\e Y' );
			$post_type_arr[ $post_id ]['date'] = $date;
			$post_type_arr[ $post_id ]['mes'] = get_the_date( 'M' );

			// Get attribs from image
			$imgAttr = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), $img_size );
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

			// Traemos Meta del post solicitado
			if( ! empty( $name_meta ) ){
				// Get attribs for Name Meta
				$listMeta = get_post_meta( $post->ID, $name_meta );
				$post_type_arr[ $post_id ][$name_meta] = $listMeta;

			}


		endwhile;

		wp_reset_postdata();
		wp_reset_query();

		return $post_type_arr;

	}


	/*
	* Function to print the category post type
	*
	* @param { string } category: type of catagory to print
	* @param { string } order_by: argument to order categories
	* @param { string } parent: parent of categories
	*/
	
	function CategoryList( $category, $order_by = NULL, $parent = NULL ){

		$category_arr = array();
		$categoryTienda = get_terms( $category, array(
					'orderby'   => $order_by
				,   'parent'    => $parent
			) );

		foreach ( $categoryTienda as $key => $category ) {
			$category_arr[ $key ][ 'name' ] = $category->name;
			$category_arr[ $key ][ 'link' ] = esc_attr(get_term_link($category, $category));
			$category_arr[ $key ][ 'title' ] = sprintf( ( "Ver todos los productos de %s" ), $category->name );

		}

		return $category_arr;

	}


	/*
	* Function to print the similar products
	*
	* PAGE: Single Tienda Disgraf
	* @param { int } ID: ID del articulo actual
	*/
	function SimilarPosts( $ID, $post, $tax, $img_size ){

		//Return the IDs of the tags '$tax'
		$tagsOfPost = wp_get_post_terms( $ID, $tax, array('fields' => 'ids'));

		$argTaxonomy = array(
					'taxonomy' => $tax // Nombre de la taxonomía.
				,   'field'    => 'term_id'
				,   'terms'    => $tagsOfPost // El ID de la categoría a filtrar.
				,   'operator' => 'IN'
			);

		$args = array(
					'post_type'=> $post
				,   'showposts'=> '3'
				,   'post__not_in' => array( $ID )
				,   'tax_query' => array( $argTaxonomy )
			);

		$arrSimilarResour = array();
		$i = 0;

		$querySimilarResour = new WP_Query( $args );


		while( $querySimilarResour -> have_posts()  ): $querySimilarResour->the_post();

			// Get attribs from image
			$img_attr = wp_get_attachment_image_src( get_post_thumbnail_id(), $img_size );

			$arrSimilarResour[$i]['title'] = get_the_title();
			$arrSimilarResour[$i]['excerpt'] = get_the_excerpt();
			$arrSimilarResour[$i]['url'] = get_the_permalink();
			$arrSimilarResour[$i]['img'] = $img_attr[0];
			// $arrSimilarResour[$i]['costo'] = get_post_custom_values( 'costo', $post->ID );

			$i++;

		endwhile;

		wp_reset_query();

		return $arrSimilarResour;
		//dump( $tagsOfPost );
		//dump( wp_get_post_terms( $ID, 'type' ) );
		//print_r( $querySimilarResour );
	}

}

?>
