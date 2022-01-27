<?php
 /*
 * @Archivo: class-Cities.php
 * @Descripcion: Clase para generar select de ciudades y departamentos
 *
 */

 // Cargamos wordpress
require_once( explode( "wp-content" , __FILE__ )[0] . "wp-load.php" );




	class Cities_Clas
	{
		private $reponse;


		public function __construct(){

		}

		/*
		|-------------------------------------------------------------------------------
		| Function to Cities Pay U
		|-------------------------------------------------------------------------------
		*/
		public function Find_Tipo_Document(){

			global $wpdb;

			//Define tabla para busqueda
			$tbtypes = $wpdb->prefix . "types_document";

			//Resuktados Busqueda
		    $selectTypes= $wpdb->get_results( "SELECT * FROM $tbtypes ORDER BY $tbtypes.des_tip ASC" );


		    $typessarray =	array(
								'html' => array( '' => __('Selecciona Tipo de Documento', 'Dale') )
						);

	    	//Print Option Select
			foreach ( $selectTypes as $key => $value ) {

				$des_tip = $value->des_tip;
				$typessarray['html'][$des_tip] = $des_tip;

			}

		    return $typessarray;

		    $wpdb->flush();

		}

		/*
		|-------------------------------------------------------------------------------
		| Function to Cities Pay U
		|-------------------------------------------------------------------------------
		*/
		public function Find_City_PayU( $data ){

			global $wpdb;


			//Define tabla para busqueda
			$tbcities = $wpdb->prefix . "cities";
			$tbstates = $wpdb->prefix . "states";

			//Resuktados Busqueda
		    $cities = $wpdb->get_results( "
		    							SELECT city.city AS city
		    							FROM $tbstates state
		    							INNER JOIN $tbcities city ON state.id_state=city.state_id
		    							AND state.state = '$data'
		    							ORDER BY city.city ASC
										" );

		    $option = '<option value="">Ciudad</option>';

		    foreach ( $cities as $key => $value ) {
		    	$option .= '<option value="'.$value->city.'">'.$value->city.'</option>';
		    }

		    return $option;

		    $wpdb->flush();

		}

		/*
		|-------------------------------------------------------------------------------
		| Function to City Woocommerce
		|-------------------------------------------------------------------------------
		*/

		public function Find_CC_Woo( $data ){

			$cc_user =	array(
									'validate' => false
								, 	'html' => ''
							);

			switch ( $data['shift'] ) {

				case 'billing':

					$FindLogin = 'billing_cedula';

				break;

				case 'shipping':

					$FindLogin = 'shipping_cedula';
					
				break;

			}

			if ( is_user_logged_in() ) {

				$usuario = wp_get_current_user();

				$cedula = get_user_meta( $usuario->ID, $FindLogin, true );

				$cc_user['validate'] = true;
				
				$cc_user['html'] = $cedula;

			}

			return $cc_user;

		}

		/*
		|-------------------------------------------------------------------------------
		| Function to City Woocommerce
		|-------------------------------------------------------------------------------
		*/

		public function Find_City_Woo( $data, $f_state = NULL, $option = false ){

			global $wpdb;

			if ( $option ) {

				$state = $f_state;

			} else {

				$state = $data['state'];

				switch ( $data['shift'] ) {

					case 'billing':

						$FindLogin = 'billing_city';

					break;

					case 'shipping':

						$FindLogin = 'shipping_city';
						
					break;

				}

				$city_user = '';

				if ( $data['change'] == 'false' ) {

					if ( is_user_logged_in() ) {

						$usuario = wp_get_current_user();

						$city_user = get_user_meta( $usuario->ID, $FindLogin, true );

					}

				}

			}

			//Define tabla para busqueda
			$tbcities = $wpdb->prefix . "cities";
			$tbstates = $wpdb->prefix . "states";

			//Resuktados Busqueda
		    $cities = $wpdb->get_results( "
		    							SELECT city.city AS city
		    							FROM $tbstates state
		    							INNER JOIN $tbcities city ON state.id_state=city.state_id
		    							AND state.state = '$state'
		    							ORDER BY city.city ASC
										" );

		    if ( $option ) {

				$optionHtml =	array( '' => 'Selecciona tu Ciudad' );

		    	//Print Option Select
				foreach ( $cities as $key => $value ) {

					$city = $value->city;
					$optionHtml[$city] = $city;

				}


		    } else {

			    $optionHtml = $this -> Option_City_Woo( $cities, $city_user );

		    }

		    return $optionHtml;

		    $wpdb->flush();

		}
		/*
		|-------------------------------------------------------------------------------
		| Function to State Woocommerce
		|-------------------------------------------------------------------------------
		*/

		public function Find_State_Billing( $data, $option = false ){

			global $wpdb;

			if ( $option ) {

				$type =  $data;

			} else {

				$type =  $data['shift'];

			}
			

			switch ( $type ) {

				case 'billing':

					$FindLogin = 'billing_states';

				break;

				case 'shipping':

					$FindLogin = 'shipping_states';
					
				break;

			}

			$state_user = '';

			if ( is_user_logged_in() ) {

				$usuario = wp_get_current_user();

				$state_user = get_user_meta( $usuario->ID, $FindLogin, true );

			}

			//Define tabla para busqueda
			$tbstates = $wpdb->prefix . "states";

			//Resuktados Busqueda
		    $selectState= $wpdb->get_results( "SELECT * FROM $tbstates ORDER BY $tbstates.state ASC" );

		    if ( $option ) {

				$statesarray =	array(
									'html' => array( '' => 'Selecciona tu Departamento' )
							);

				if ( $state_user != '' ) {

			    	$statesarray['state'] = $state_user;

			    }


		    	//Print Option Select
				foreach ( $selectState as $key => $value ) {

					$state = $value->state;
					$statesarray['html'][$state] = $state;

				}


		    } else {

		    	$statesarray =	array(
										'validate' => false
									, 	'html' => ''
									,	'state' => ''
								);

		    	if ( $state_user != '' ) {

			    	$statesarray['validate'] = true;
			    	$statesarray['state'] = $state_user;

			    }

			    $statesarray['html'] = $this -> Option_State_Woo( $selectState, $state_user );

		    }

		    return $statesarray;

		    $wpdb->flush();

		}

		/*
		|-------------------------------------------------------------------------------
		| Function to Registro User
		|-------------------------------------------------------------------------------
		*/
		public function Find_City($data){

			global $wpdb;

			//Define tabla para busqueda
			$tbcities = $wpdb->prefix . "cities";

			//Resuktados Busqueda
		    $cities = $wpdb->get_results( "SELECT * FROM $tbcities WHERE state_id = '$data' ORDER BY $tbcities.city ASC" );

		    $option = $this -> Option_City( $cities );

		    return $option;

		    $wpdb->flush();

		}

		/*
		|-------------------------------------------------------------------------------
		| Function to Registro User
		|-------------------------------------------------------------------------------
		*/
		public function Find_City_Chip($data){

			global $wpdb;

			//Define tabla para busqueda
			$tbcities = $wpdb->prefix . "cities";

			//Resuktados Busqueda
		    $cities = $wpdb->get_results( "SELECT * FROM $tbcities WHERE state_id = '$data' ORDER BY $tbcities.city ASC" );

		    $option = $this -> Option_City_Chip( $cities );

		    return $option;

		    $wpdb->flush();

		}

		/*
		|-------------------------------------------------------------------------------
		| Function to Registro User
		|-------------------------------------------------------------------------------
		*/
		public function NameState($state){

			global $wpdb;

			//Define tabla para busqueda
			$tbstate = $wpdb->prefix . "states";

			//Resuktados Busqueda
		    $state = $wpdb->get_results( "SELECT * FROM $tbstate WHERE id_state = '$state' LIMIT 1" );

		    $nameState = $state[0]->state;

		    return $nameState;

		    $wpdb->flush();

		}

		/*
		|-------------------------------------------------------------------------------
		| Function Array Option
		|-------------------------------------------------------------------------------
		*/

		public function Option_City( $cities ){

			$classFormulario = new Print_Form();

		    $form = $classFormulario->default_template();

			//Print select city
			$printForm .= '<select class="formValidate" id="strcity" name="strcity" required="">';

			//Print Option Select Default
			$printForm .= str_replace('{namedefault}', 'Selecciona tu Ciudad' , $form['option_select_default']);


			//Print Option Select
			foreach ( $cities as $key => $value ) {

				//Print option city
				$printForm .= preg_replace( 
												array(
															0 => '/valueoption/'
														,	1 => '/nameoption/'
													)
											, 	array( 
															1 => $value->city
														,	0 => $value->city
													)
											,	$form['option_select']
										);

			}

			//Close select city
			$printForm .= $form['select_close'];

			return $printForm;

		}

		/*
		|-------------------------------------------------------------------------------
		| Function Array Option
		|-------------------------------------------------------------------------------
		*/

		public function Option_City_Chip( $cities ){

			$classFormulario = new Print_Form();

		    $form = $classFormulario->default_template();

			//Print select city
			$printForm .= preg_replace( 
											array(
														0 => '/idselect/'
													,	1 => '/nameselect/'
													,	2 => '/boolrequi/'
												)
										, 	array( 
														2 => 'CITY'
													,	1 => 'CITY'
													,	0 => 'required'
												)
										,	$form['select_open']
									);

			//Print Option Select Default
			$printForm .= str_replace('{namedefault}', 'Selecciona tu Ciudad' , $form['option_select_default']);


			//Print Option Select
			foreach ( $cities as $key => $value ) {

				//Print option city
				$printForm .= preg_replace( 
												array(
															0 => '/valueoption/'
														,	1 => '/nameoption/'
													)
											, 	array( 
															1 => $value->city
														,	0 => $value->city
													)
											,	$form['option_select']
										);

			}

			//Close select city
			$printForm .= $form['select_close'];

			return $printForm;

		}

		/*
		|-------------------------------------------------------------------------------
		| Function Array Option
		|-------------------------------------------------------------------------------
		*/

		public function Option_State_Woo( $states, $stateuser ){

			$classFormulario = new Print_Form();

		    $form = $classFormulario->default_template();

			//Print Option Select Default
			if ( $stateuser == '' ) {

				$printForm .= str_replace('{namedefault}', 'Selecciona tu Departamento' , $form['option_select_default']);

			}

			//Print Option Select
			foreach ( $states as $key => $value ) {

				if ( $stateuser != '' && $value->state == $stateuser ) {

					//Print option State Select
					$printForm .= preg_replace( 
													array(
																0 => '/valueoption/'
															,	1 => '/nameoption/'
														)
												, 	array( 
																1 => $value->state
															,	0 => $value->state
														)
												,	$form['option_select_check']
											);

				}else{

					//Print option State Select
					$printForm .= preg_replace( 
													array(
																0 => '/valueoption/'
															,	1 => '/nameoption/'
														)
												, 	array( 
																1 => $value->state
															,	0 => $value->state
														)
												,	$form['option_select']
											);

				}

			}

			return $printForm;

		}

		/*
		|-------------------------------------------------------------------------------
		| Function Array Option
		|-------------------------------------------------------------------------------
		*/

		public function Option_City_Woo( $cities, $cityuser ){

			$classFormulario = new Print_Form();

		    $form = $classFormulario->default_template();

			//Print Option Select Default
			if ( $cityuser == '' ) {

				$printForm .= str_replace('{namedefault}', 'Selecciona tu Ciudad' , $form['option_select_default']);

			}

			//Print Option Select
			foreach ( $cities as $key => $value ) {

				if ( $cityuser != '' && $value->city == $cityuser ) {

					//Print option State Select
					$printForm .= preg_replace( 
													array(
																0 => '/valueoption/'
															,	1 => '/nameoption/'
														)
												, 	array( 
																1 => $value->city
															,	0 => $value->city
														)
												,	$form['option_select_check']
											);

				}else{

					//Print option State Select
					$printForm .= preg_replace( 
													array(
																0 => '/valueoption/'
															,	1 => '/nameoption/'
														)
												, 	array( 
																1 => $value->city
															,	0 => $value->city
														)
												,	$form['option_select']
											);

				}

			}

			return $printForm;

		}

	}


?>
