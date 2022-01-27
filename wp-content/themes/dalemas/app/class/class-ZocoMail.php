<?php
 /*
 * @Archivo: class-ZocoMail.php
 * @Descripcion: Clase encargada de construir la estructura de los emails
 *
 */
/**
* MacMail content class
*/
class ZocoMail {
	/**
	* Constructor
	*/
	public function __construct() {
	}
	/**
	* Function to construct header emails
	*/
	function HeaderMail (){
		// Para enviar un correo HTML, debe establecerse la cabecera Content-type
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
		return $headers;
	}
	/*
	|-------------------------------------------------------------------------------
	| Function Convert HTML to text to send email.
	| * @param { string } search: Fields to search
	| * @param { string } replace: Fields to replace
	| * @param { string } html_file: email template
	|-------------------------------------------------------------------------------
	*/
	function HtmlMail ( $search, $replace, $html_file ) {
		// Get email template
		$body = file_get_contents( EMAILURL . $html_file );
		// Replace Values
		$html_body = str_replace( $search, $replace, $body );
		// Codificamos a caracteres espanioles
		$html_body = htmlspecialchars_decode( htmlentities( $html_body, ENT_NOQUOTES, 'UTF-8', false ), ENT_NOQUOTES );
		// Return
		return $html_body;
	}
}
?>