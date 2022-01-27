<?php
/***
 * @Descripcion: helpers-functions.php
 * Contiene las diferentes funciones axiliares a las demas funciones de wordpress
 *
 * Estas funciones auxiliares estan destinadas a ser accesibles por cuarquier funcion
 *
 *
***/


/*
|-------------------------------------------------------------------------------
| Function for the control and test variables
|
|-------------------------------------------------------------------------------
*/
function dump( $test, $metod = 2 ){

	$test = str_replace( '<', '&lt;', $test );
	$test = str_replace( '>', '&gt;', $test );

	echo '<pre class="dump">';

	if( $metod == 1 ){
		var_dump( $test );

	}else{
		print_r( $test );

	}
	echo '</pre>';
}


/*
|-------------------------------------------------------------------------------
| Function for the print paged
|-------------------------------------------------------------------------------
*/
function PrintPagination( $wp_query ){

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


	$postPagination = paginate_links( $arrgs );

	return $postPagination;

}



/*
|-------------------------------------------------------------------------------
| Function to clean and validate POST data
| @param { array } post_data: POST data
|-------------------------------------------------------------------------------
*/
function CleanPostData( $post_data ) {

	// Declaramos array con los datos escapados
	$cleaned_data = array();

	// Recorremos el array del POST para escapar los datos
	foreach( $post_data as $name => $data ) {

		// Determinamos si es un array anidado
		if( is_array( $post_data[ $name ] ) ) {

			// Recorremos y escapamos Array anidado
			foreach ( $post_data[ $name ] as $sub_name => $sub_data ) {
				$cleaned_data[ $name ][ $sub_name ] = esc_sql( $post_data[ $name ][ $sub_name ] );
			}

		} else {
			// Recorremos y escapamos datos
			$cleaned_data[ $name ] = esc_sql( $post_data[ $name ] );
		}
	}

	return $cleaned_data;

}

/*
|-------------------------------------------------------------------------------
| Function currente date
| @param { none }
|-------------------------------------------------------------------------------
*/
function FechaActual() {

	//Fecha Actual
	$local_time = time();
	$cur_year	= date("Y", $local_time);
	$cur_month	= date("m", $local_time);
	$cur_day	= date("j", $local_time);
	$is_current_day = $cur_day.'/'.$cur_month.'/'.$cur_year;

	return $is_current_day;

}


/*
|-------------------------------------------------------------------------------
| Function check if the value is empty
| @param { string } value: value to checking
| @param { string } ifEmpty: respond if value empty
|-------------------------------------------------------------------------------
*/
function issetVal( $value, $ifEmpty ) {

	if( isset( $value ) ){
		$theValue = $value;

	}else{
		$theValue = $ifEmpty;
	}

	return $theValue;

}


/*
|-------------------------------------------------------------------------------
| Function Get specific taxonomy grouped by the father
| @param { string } value: value to checking
| @param { string } ifEmpty: respond if value empty
|-------------------------------------------------------------------------------
*/
function getTaxonomiesGrouped( $term = 'product_cat' ) {
	// Consultamos los taxomonies
	$taxonomies = get_terms( $term, array(
			'order'		=> 'ASC'
		,	'hide_empty' => true
	));

	$arrCatsGruped = array();

	// Verificamos que si tenga elementos
	if ( !empty($taxonomies) ) :
		foreach( $taxonomies as $category ) {
			if( $category->parent == 0 ) {
				// Datos cat padre
				$fatherId = $category->term_id;
				$arrCatsGruped[ $fatherId ][ 'id' ] = $category->term_id;
				$arrCatsGruped[ $fatherId ][ 'name' ] = $category->name;
				$arrCatsGruped[ $fatherId ][ 'slug' ] = $category->slug;
				$arrCatsGruped[ $fatherId ][ 'count' ] = $category->count;

				// Recorremos sub-categorias
				foreach( $taxonomies as $subcategory ) {
					if($subcategory->parent == $category->term_id) {
						// Datos cat hijos
						$childrenId = $subcategory->term_id;
						$arrCatsGruped[ $fatherId ][ 'children' ][ $childrenId ][ 'id' ] = $subcategory->term_id;
						$arrCatsGruped[ $fatherId ][ 'children' ][ $childrenId ][ 'name' ] = $subcategory->name;
						$arrCatsGruped[ $fatherId ][ 'children' ][ $childrenId ][ 'slug' ] = $subcategory->slug;
						$arrCatsGruped[ $fatherId ][ 'children' ][ $childrenId ][ 'count' ] = $subcategory->count;

					}
				}

			}
		}
	endif;

	return $arrCatsGruped;

}

/*
|-------------------------------------------------------------------------------
| Function log write in file
|-------------------------------------------------------------------------------
*/
function writeObjData( $nameTxt, $objectData, $title = '' ) {
	// $nameTxt = BASEPATH . '/' . $nameTxt;

	if (is_array($objectData) || is_object($objectData))
		$objectData = '<pre>' . print_r($objectData, true) . '</pre>';

	// convertimos a json
	// $objectData = json_encode( $objectData );

	// Abrimos archivo
	$file = fopen( $nameTxt, 'a+' );
	fwrite( $file, date_i18n( 'Y-m-d H:i:s' ) . ' <br>' );
	fwrite( $file, $title . ' <br>' );
	fwrite( $file, $objectData );
	fwrite( $file, '<br><br>----<br><br>' );
	fclose( $file );
	// echo 'TRUE';

	return TRUE;

}

// writeObjData( 'log-test.html', 'holass', 'Test ' );

/**
 * Create XML using string or array
 *
 * @param mixed $data input data
 * @param SimpleXMLElement $xml
 * @param string $child name of first level child
 *
 * @return adding Xml formated data into SimpleXmlElement
*/
function dataToXML( $data, SimpleXMLElement $xml, $child = "items" ){
	// Recorremos array
	foreach($data as $key => $val) {

		if( is_numeric($key) ){
				$key = $child.$key; //dealing with <0/>..<n/> issues
		}
		if( is_array($val) ) {
				$subnode = $xml->addChild($key);
				dataToXML($val, $subnode);
		} else {

			// Buscamos si hay prefijos
			$busqueda = strpos( $key, ':' );
			if( is_numeric( $busqueda ) && $busqueda =! 0 ){
				$prefijo = substr( $key, 0,  $busqueda );
				// Agregamos hijo con prefijo
				$xml->addChild( "$key", htmlspecialchars("$val"), $prefijo );

				/*$attr = $xml->xpath("//$key/@tem");
				var_dump( $attr );*/
				
			}else{
				// Agregamos hijo
				$xml->addChild( "$key", htmlspecialchars("$val") );
			}
			
		}

	}
	return $xml;

}


/*
|-------------------------------------------------------------------------------
| Function Generar Html
| @param { array } nameHtml: name html file
| @param { array } contentHtml: content html file
|-------------------------------------------------------------------------------
*/

function create_html( $nameHtml, $contentHtml ){

	// Especificamos la ruta
	$folderEvento = HTMLPATH;

	// Si no exisite la carpeta la creamos
	if( ! file_exists( $folderEvento ) ){
		mkdir( $folderEvento, 0777, TRUE );
	}

	$nameArchive = HTMLPATH . $nameHtml . '.html';

	file_put_contents( $nameArchive, $contentHtml );
}

/*
|-------------------------------------------------------------------------------
| Function Generar Html
|-------------------------------------------------------------------------------
*/

function generate_html(){

	$customWoo = new Woocommerce_Custom;
	$products = $customWoo -> print_product_order_session();

	// creamos archivos HTML
	create_html( 'DropTienda', $products['DropTienda'] );

	// Actualizamos manejador
	$date = date_i18n( 'd/m/Y', strtotime( '11/15-1976' ) );
	update_option( 'htmlDate', $date );

}

/*
|-------------------------------------------------------------------------------
| Function control cache html
| @param { array } nameHtml: name html file
|-------------------------------------------------------------------------------
*/

function cache_html( $nameHtml ){
	// Determinamos si se generan los archivos HTML
	$dataOpt = get_option( 'htmlDate' );
	$date = date_i18n( 'd/m/Y', strtotime( '11/15-1976' ) );

	if( $dataOpt != $date ){
		//generate_html();
	}
	generate_html();

	return get_html( $nameHtml );

}

/*
|-------------------------------------------------------------------------------
| Function Obtener Html
| @param { array } nameHtml: name html file
|-------------------------------------------------------------------------------
*/

function get_html( $nameHtml ){

	$getContent = file_get_contents( HTMLPATH . $nameHtml . '.html' );
	return $getContent;

}

/*
|-------------------------------------------------------------------------------
| Validate data of key registry user.
| @param { string } userKey: Validate user Key
| @param { string } checker: Check value
|-------------------------------------------------------------------------------
*/
function LoginById( $user_id ) {
	if ( ! is_numeric( $user_id ) ){
		return false;
	}

	$user = get_user_by( 'id', $user_id );
	// Set current user and cookie
	wp_set_current_user( $user_id, $user->data->user_login );
	wp_set_auth_cookie( $user_id, true );
	// Fire 'wp_login' event
	do_action( 'wp_login', $user->data->user_login, $user );
	return $user;
}

/*
|-------------------------------------------------------------------------------
| Encrypt and decrypt private data
| @param { string } action: type of action to run
| @param { string } input: text string to encrypt or decrypt
|-------------------------------------------------------------------------------
*/
function Encrypter( $action, $encryption_key, $simple_string ) {

	$encryption_iv = '1234567891011121';
	$ciphering = "AES-128-CTR";
	$options = 0; 

	if( $action == 'encrypt' ) {
		$iv_length = openssl_cipher_iv_length($ciphering);
		$output = openssl_encrypt($simple_string, $ciphering, $encryption_key, $options, $encryption_iv); 
	}

	if( $action == 'decrypt' ) {
		$output=openssl_decrypt ($simple_string, $ciphering, $encryption_key, $options, $encryption_iv);
	}

	return $output;

}

/*
|-------------------------------------------------------------------------------
| Validate data of key registry user.
| @param { string } userKey: Validate user Key
|-------------------------------------------------------------------------------
*/
function ValidateKeyRegistry( $userKey, $checker ) {
	$arrValData = array();
	$arrValData[ 'validate' ] = TRUE;
	$valEncryp = $userKey;
	if( ! isset( $valEncryp ) || empty( $valEncryp ) ) {
		$arrValData[ 'validate' ] = FALSE;
		$arrValData[ 'msn' ] = 'ERROR VARIABLE';
	}
	$valCompuesto = Encrypter( 'decrypt', $checker, $valEncryp );
	$arrValues = explode( '.', $valCompuesto );
	$arrCount = count( $arrValues );
	if( $arrCount == 2 ) {
		$arrValData[ 'idUser' ] = (int) $arrValues[ 0 ];
		$arrValData[ 'comprobador' ] = (string) $arrValues[ 1 ];
	} else {
		$arrValData[ 'validate' ] = FALSE;
		$arrValData[ 'msn' ] = 'ERROR AL DESENCRIPTAR VARIABLE';
	}
	if( strpos( $arrValData[ 'comprobador' ], $checker ) === FALSE ) {
		$arrValData[ 'validate' ] = FALSE;
		$arrValData[ 'msn' ] = 'ERROR EN EL COMPROBADOR';
	}
	return $arrValData;
}


?>
