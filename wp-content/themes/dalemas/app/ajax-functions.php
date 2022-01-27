<?php
/***
 * @Descripcion: ajax-functions.php
 * Contiene las diferentes funciones de ajax
 *
 * Estas funciones ajax son utilizadoas tanto en el front-end como en el back-end
 *
 *
***/



/*
|-------------------------------------------------------------------------------
| Function Encontrar Productos
|-------------------------------------------------------------------------------
*/

function update_mini_cart()
{
	// Create response object Ajax
	echo wc_get_template('cart/mini-cart.php');
	die();
}

add_filter('wp_ajax_miniCart', 'update_mini_cart');
add_filter('wp_ajax_nopriv_miniCart', 'update_mini_cart');

/*
|-------------------------------------------------------------------------------
| Function Ajax to Peventa base de datos
|-------------------------------------------------------------------------------
*/
function registerNewsletterCallback() {
	// Get $wpdb
	global $wpdb;
	$email		= $_POST['email'];
	$sexo		= $_POST['sexo'];
	$date 	    = date('m/d/Y h:i:s a', time());
	//Check de Seguridad
	$formNonce = esc_attr( $_POST[ 'nonceContactForm' ] );
	// Create response object Ajax
	$objLoad = ( object ) array(
				'validate' 	=> true,
				'action'	=> 'charla'
	);
	if( ! wp_verify_nonce( $formNonce, 'nonceContactForm' ) ){
		$objLoad -> validate = false;
		$objLoad -> msnError = 'Error Seguridad';
		die( json_encode( $objLoad ) );
	}
	if($objLoad->validate){
		//Verificamos si el usuario ya se encuentrea registrado
		$tablename = $wpdb->prefix . "newsletter";
		$results = $wpdb->get_results("SELECT id FROM $tablename WHERE user_email = '$email'");
		//Solo si el usuario no existe insertamos el registro y creamos el cupo
		if(empty($results)){
			$wpdb->insert($tablename, array(
					'user_email'	=>	$email
				,	'user_fecha'	=>	$date
				,	'user_sexo'		=>	$sexo
			),array(
					'%s'
				,	'%s'
				,	'%s'
			));
			//Desarrollo del descuento en el carrito de compra
			$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$nombre_cupon    = substr(str_shuffle($permitted_chars), 0, 15);

			//vamos a crear un cupon y añadirlo al carrito
			date_default_timezone_set('America/Bogota');
			$local_time = time();
			$time_entrega = strtotime ( '+2 day' , $local_time );
			$limit_dia = date("Y-m-d", $time_entrega);

			$discount_type = 'percent'; 
			$coupon = array(
				'post_title'	=> $nombre_cupon,
				'post_content'	=> '',
				'post_status'	=> 'publish',
				'post_author'	=> 1,
				'post_type'		=> 'shop_coupon'
			);
			$new_coupon_id = wp_insert_post( $coupon );
			if ( $new_coupon_id != 0 ) {
				update_post_meta($new_coupon_id, 'customer_email', $email);
				update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
				update_post_meta( $new_coupon_id, 'coupon_amount', 10 );
				update_post_meta( $new_coupon_id, 'individual_use', 'yes' );
				update_post_meta( $new_coupon_id, 'product_ids', '' );
				update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
				update_post_meta( $new_coupon_id, 'usage_limit', '1' );
				update_post_meta( $new_coupon_id, 'usage_limit_per_user', '1' );
				update_post_meta( $new_coupon_id, 'expiry_date', $limit_dia );
				update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
				
				global $woocommerce;
				if (!$woocommerce->cart->add_discount( sanitize_text_field( $nombre_cupon ))){
					$woocommerce->show_messages();
				}
				$objLoad -> validate 	= true;
				$objLoad -> texto 		= '!Gracias por registrarte!';
			}else{
				$objLoad -> validate 	= false;
				$objLoad -> msnError 		= 'Error al crear el cupon';
			}
		}else{
			$objLoad -> validate 	= false;
			$objLoad -> msnError 		= 'El email ya se encuentra registrado';
		}
    }

	echo json_encode( $objLoad );
	die( ); // Siempre hay que terminar con die
}
add_action('wp_ajax_Newsletter', 'registerNewsletterCallback');
add_action('wp_ajax_nopriv_Newsletter', 'registerNewsletterCallback');
/*
|-------------------------------------------------------------------------------
| Function Ajax to register user
|-------------------------------------------------------------------------------
*/
function RegistroUsuariosCallback()
{

	// Get $wpdb
	global $wpdb;

	// Create response object Ajax
	$objLoad = (object) array(
		'validate'       => TRUE,   
		'htmlErrors'     => FALSE,   
		'html'           => '',   
		'type'     		 => 'registro',
		'action'		 => 'usuarios'
	);

	// Validate if pass is equal
	if ($_POST['nombre'] == '' || $_POST['correo'] == '') {
		$objLoad->validate = FALSE;
		$objLoad->htmlErrors .= '*Los campos estan vacios<br/>';
	}

	// Validate if pass is equal
	if ($_POST['pwd'] != $_POST['pwd_2']) {
		$objLoad->validate = FALSE;
		$objLoad->htmlErrors .= '*Las contraseñas no son iguales<br/>';
	}

	// Validate if exist the e-mail
	if (email_exists($_POST['correo'])) {
		$objLoad->validate = FALSE;
		$objLoad->htmlErrors .= '*El "Email" ingresado ya existe o no es valido <br/>';
	}

	if ($objLoad->validate) :

		// Array with data to register new user
		$newUser = array(
			'user_login'        => $_POST['correo'],	
			'user_pass'			=> $_POST['pwd'],	
			'user_email'		=> $_POST['correo'],	
			'first_name'		=> $_POST['nombre'],	
			'user_registered'	=> date('Y-m-d H:i:s'),	
			'role'				=> 'customer'
		);

		// Register a new user
        $newUserId = wp_insert_user($newUser);

	else :

		$objLoad->html = 'Error al registrar usuario';

	endif;


	if (TRUE) {
		$objLoad->newUserId 	= $newUserId;
	}

	echo json_encode($objLoad);

	die(); // Siempre hay que terminar con die

}
add_action('wp_ajax_RegistroUsuarios', 'RegistroUsuariosCallback');
add_action('wp_ajax_nopriv_RegistroUsuarios', 'RegistroUsuariosCallback');

/*
|-------------------------------------------------------------------------------
| Function Ajax to Peventa base de datos
|-------------------------------------------------------------------------------
*/
function saveContacto() {

    // Create response object Ajax
	$objLoad = ( object ) array(
        'validate' 	=> true,
        'action'	=> 'contacto'
    );
	// Get $wpdb
	global $wpdb;
    
	$email		    = $_POST['email'];
	$phone		    = $_POST['telefono'];
	$name		    = $_POST['name'];
	$mensaje		= $_POST['mensaje'];
	$consulta		= $_POST['consulta'];
    $date 	        = date('m/d/Y h:i:s a', time());
    
	//Check de Seguridad
	$formNonce = esc_attr( $_POST[ 'nonceContactForm' ] );
	
	if( ! wp_verify_nonce( $formNonce, 'nonceContactForm' ) ){
		$objLoad -> validate = false;
		$objLoad -> msnError = 'Error Seguridad';
		die( json_encode( $objLoad ) );
	}
	if($objLoad->validate){
		
		$tablename = $wpdb->prefix . "contacto";
		$response = $wpdb->insert($tablename, array(
				'user_email'	    =>	$email
			,	'user_name'	   	 	=>	$name
			,	'user_phone'	    =>	$phone
			,	'user_mensaje'	    =>	$mensaje
			,	'user_consulta'	    =>	$consulta
			,	'user_fecha'	    =>	$date
		),array(
				'%s'
			,	'%s'
			,	'%s'
			,	'%s'
			,	'%s'
			,	'%s'
		));
        $objLoad -> response = $response;

    }
	echo json_encode( $objLoad );
	die( ); // Siempre hay que terminar con die

}

add_action('wp_ajax_saveContacto', 'saveContacto');
add_action('wp_ajax_nopriv_saveContacto', 'saveContacto');

/*
|-------------------------------------------------------------------------------
| Function Ajax to Peventa base de datos
|-------------------------------------------------------------------------------
*/
function saveDistribuidor() {
    // Create response object Ajax
	$objLoad = ( object ) array(
        'validate' 	=> true,
        'action'	=> 'Distribuidor'
    );
	// Get $wpdb
	global $wpdb;
	$name		= $_POST['name'];
	$phone		= $_POST['phone'];
	$company	= $_POST['company'];
	$redes		= $_POST['redes'];
	$email		= $_POST['email'];
	$page_web	= $_POST['page_web'];
	$country	= $_POST['country'];
	$city		= $_POST['city'];
	$mensaje	= $_POST['mensaje'];
    $date 	    = date('m/d/Y h:i:s a', time());
	//Check de Seguridad
	$formNonce = esc_attr( $_POST[ 'nonceContactForm' ] );
	if( ! wp_verify_nonce( $formNonce, 'nonceContactForm' ) ){
		$objLoad -> validate = false;
		$objLoad -> msnError = 'Error Seguridad';
		die( json_encode( $objLoad ) );
	}
	if($objLoad->validate){
		$tablename = $wpdb->prefix . "distribuidor";
		$response = $wpdb->insert($tablename, array(
				'name'	    =>	$name
			,	'phone'	   	=>	$phone
			,	'company'	=>	$company
			,	'redes'	    =>	$redes
			,	'email'	    =>	$email
			,	'page_web'	=>	$page_web
			,	'country'	=>	$country
			,	'city'		=>	$city
			,	'mensaje'	=>	$mensaje
			,	'date'		=>	$date
		),array(
				'%s'
			,	'%s'
			,	'%s'
			,	'%s'
			,	'%s'
			,	'%s'
			,	'%s'
			,	'%s'
			,	'%s'
			,	'%s'
		));
        $objLoad -> response = $response;
    }
	//traemos la clase
	$mailZoco = new ZocoMail;
	//Enviamos el mensaje al administrador del sitio
	//definimos el encabezado
	$msnSubject = 'Nuevo distribuidor';
	$subject    = utf8_decode( $msnSubject );
	$headers    = $mailZoco->HeaderMail();
	$body = $mailZoco->HtmlMail( 
        // Fields to SEARCH
        array(
                '{EMAILURL}'
            ,	'{EMAILUSER}'
        )
        // Fields to REPLACE into search
    ,	array(
                EMAILURL
            ,	$email
        )
        // Template HTML
    ,	'qr.html' 
    );
	wp_mail( 'contact@dalemas.store', $subject, $body, $headers );
	
	
	
	//Enviamos el mensaje al cliente
	$msnSubject  = '¡Gracias por registrarse!';
	$subject     = utf8_decode( $msnSubject );
	$headers     = $mailZoco->HeaderMail();
	$body 		 = $mailZoco->HtmlMail( 
        // Fields to SEARCH
        array(
            '{EMAILURL}'
        )
    ,	array(
            EMAILURL
        )
        // Template HTML
    ,	'cliente.html' 
    );
	$attachments = array(
		EMAILPATH . 'Presentancion.pdf',
		EMAILPATH . 'DaleCorporatePresentation.pdf'
	);
	wp_mail( $email, $subject, $body, $headers, $attachments );

	echo json_encode( $objLoad );
	die(); // Siempre hay que terminar con die
}

add_action('wp_ajax_saveDistribuidor', 'saveDistribuidor');
add_action('wp_ajax_nopriv_saveDistribuidor', 'saveDistribuidor');

/*
|-------------------------------------------------------------------------------
| Function Change Product Grilla
|-------------------------------------------------------------------------------
*/

function Change_Products(){

	// Create response object Ajax
	$objLoad = (object) array(
		'validate' 			=> true,	
		'html' 				=> '',	
		'mas_pages'			=> 0,	
		'maxPrice'			=> '',	
		'minPrice'			=> ''
	);

	// Instanciamos clase para generar PDF
	$init = get_option('posts_per_page', true);

	//Definir Pagina
	if (isset($_POST['page'])) {
		$page = $_POST['page'];
	} else {
		$page = 1;
	}

	//Determinar Rango de precios
	$arrayPrecios = get_option('preciosArray');

	if (!empty($arrayPrecios) && is_array($arrayPrecios)) {

		$objLoad->minPrice = $arrayPrecios['total']['min'];
		$objLoad->maxPrice = $arrayPrecios['total']['max'];

		if ($arrayPrecios['total']['min'] == $arrayPrecios['total']['max']) {
			$objLoad->minPrice = 0;
		}
	}

	$customWoo = new Woocommerce_Custom;

	$arrayProduct = $customWoo->getProductStore( $_POST['tax'], $_POST['cat'], 'date', $init, $page );


	if (is_array($arrayProduct) && !empty($arrayProduct)) {

		$objLoad->html = $customWoo->printProductsArr($arrayProduct);

		if (isset($arrayProduct['data']['pagination']) && $arrayProduct['data']['pagination'] > 1) {
			$objLoad->mas_pages = $arrayProduct['data']['pagination'];
		}
	} else {

		$objLoad->html = '<div class="pure-u-1 product_none"><p>NO TENEMOS EXISTENCIA DE ESTA CATEGORIA</p></div>';
	}


	echo json_encode($objLoad);

	die(); // Siempre hay que terminar con die

}

//Para manejar admin-ajax tenemos que añadir estas dos acciones.
//wp_ajax_LoadPost debe coincidir con nuestra Action del formulario
add_action('wp_ajax_ChangeProducts', 'Change_Products');
add_action('wp_ajax_nopriv_ChangeProducts', 'Change_Products');
/*
|-------------------------------------------------------------------------------
| Function Filtros Tiendas
|-------------------------------------------------------------------------------
*/

function FiltersTienda(){

	// Instanciamos clase custom woocommerce
	$customWoo = new Woocommerce_Custom;

	// Create response object Ajax
	$objLoad = (object) array(
		'validate' 			=> true,	
		'html' 				=> '',	
		'mas_pages'			=> 0,	
		'maxPrice'			=> '',	
		'minPrice'			=> ''

	);

	if (empty($_POST['jsonFilter'])) {
		$objLoad->validate = FALSE;
		echo json_encode($objLoad);
		die();
	}

	// Capturamos JSON del ajax
	$price = NULL;
	$jsonFilter =  $_POST['jsonFilter'];
	


	// Si es mas de una consulta, crear arra
	if (is_array($jsonFilter['marca']) && is_array($jsonFilter['product_cat'])) {

		$tax = array('product_cat', 'marca');
		$term = array($jsonFilter['product_cat']['ID'], $jsonFilter['marca']['ID']);
		$price = $jsonFilter['price']['ID'];
		$key = 'product_cat';
		// Sino, crear variable simple
	} else {

		foreach ($jsonFilter as $key => $value) {
			if ($key !== 'price') {
				$tax = $key;
				$term = $jsonFilter[$key]['ID'];
			} else {
				$price = $jsonFilter['price']['ID'];
			}
		}
	}
	//Definir Pagina
	if (isset($_POST['page'])) {
		$page = $_POST['page'];
	} else {
		$page = 1;
	}

	
	//Determinar Rango de precios
	if (empty($jsonFilter['price'])) {

		$arrayPrecios = get_option('preciosArray');

		if (!empty($arrayPrecios) && is_array($arrayPrecios)) {

			$objLoad->minPrice = $arrayPrecios['total']['min'];
			$objLoad->maxPrice = $arrayPrecios['total']['max'];

			if ($arrayPrecios['total']['min'] == $arrayPrecios['total']['max']) {
				$objLoad->minPrice = 0;
			}

			if (is_array($jsonFilter['product_cat'])) {

				$termIdPrice = $jsonFilter[$key]['ID'];

				$objLoad->key = $key;

				if (isset($arrayPrecios[$termIdPrice])) {

					if ($arrayPrecios[$termIdPrice]['min'] > 0 && $arrayPrecios[$termIdPrice]['max'] > 0) {

						$objLoad->minPrice = $arrayPrecios[$termIdPrice]['min'];
						$objLoad->maxPrice = $arrayPrecios[$termIdPrice]['max'];

						if ($arrayPrecios[$termIdPrice]['min'] == $arrayPrecios[$termIdPrice]['max']) {
							$objLoad->minPrice = 0;
						}
					}
				}
			}
		}
	}

	// Determinamos si es infinite loader
	$init = get_option('posts_per_page', true);

	$arrProduct = $customWoo->getProductStore( $tax, $term, 'date', $init, $page, $price);
	

	$objLoad->array = $arrProduct;
	
	

	if (is_array($arrProduct)) {

		$objLoad->html = $customWoo->printProductsArr($arrProduct);

		if (isset($arrProduct['data']['pagination']) && $arrProduct['data']['pagination'] > 1) {
			$objLoad->mas_pages = $arrProduct['data']['pagination'];
		}

	} else {

		$objLoad->html = '<div class="pure-u-1 product_none"><p>"NO TENEMOS EXISTENCIA DE ESTA CATEGORIA</p></div>';
	}

	echo json_encode($objLoad);

	die(); // Siempre hay que terminar con die

}


//Para manejar admin-ajax tenemos que añadir estas dos acciones.
//wp_ajax_LoadPost debe coincidir con nuestra Action del formulario
add_action('wp_ajax_FiltersTienda', 'FiltersTienda');
add_action('wp_ajax_nopriv_FiltersTienda', 'FiltersTienda');