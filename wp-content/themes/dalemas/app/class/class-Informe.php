<?php

/*
 * @Archivo: class-ReservaSalas.php
 * @Descripcion: Clase para el Informe
 *
 */

// Cargamos wordpress
require_once( explode("wp-content", __FILE__)[0] . "wp-load.php" );

// Hook para usuarios no logueados, ejecuta funcion para el informe semana
add_action('wp_ajax_generarExcelRegistros', array(Informes::getInstance(), 'functionExcelRegistros'));
// Hook para usuarios logueados, ejecuta funcion para el proceso de productos
add_action('wp_ajax_nopriv_generarExcelRegistros', array(Informes::getInstance(), 'functionExcelRegistros'));


// Hook para usuarios no logueados, ejecuta funcion para el informe semana
add_action('wp_ajax_generarExcelDis', array(Informes::getInstance(), 'generarExcelDis'));
// Hook para usuarios logueados, ejecuta funcion para el proceso de productos
add_action('wp_ajax_nopriv_generarExcelDis', array(Informes::getInstance(), 'generarExcelDis'));


// Hook para usuarios no logueados, ejecuta funcion para el informe semana
add_action('wp_ajax_generarExcelNewsletter', array(Informes::getInstance(), 'functionNewsletter'));
// Hook para usuarios logueados, ejecuta funcion para el proceso de productos
add_action('wp_ajax_nopriv_generarExcelNewsletter', array(Informes::getInstance(), 'functionNewsletter'));




class Informes {

    /**
     * Plugin main instance.
     *
     * @type object
     */
    protected static $instance = NULL;
    private $timeZone = 'America/Bogota';

    /**
     * Depurar en un log el proceso
     *
     * @type object
     */
    private $debug = true;

    /**
     * Ruta donde se almacenaran los archivos que genere el proceso
     *
     * @type String
     */
    private $rutaArchivos;
    private $urlArchivos;

    private $Urlarchivo;
    private $archivo;

    public function __construct() {
        
    }

    /**
     * Acceder a la instancia del complemento. Puede crear mas instancias llamando
     * Patron Singleton
     *
     * @wp-hook wp_loaded
     * @return  object procesarOrden
     */
    public static function getInstance() {
        if (NULL === self::$instance)
            self::$instance = new self;
        return self::$instance;
    }

    /*
     |-------------------------------------------------------------------------------
     | Function Generar Informe Semanal
     |-------------------------------------------------------------------------------
    */

    private function obtenerArchivo($resultadoInformes, $index = '') {

    
        
        set_include_path(get_include_path() . PATH_SEPARATOR . "..");
        include_once(CLASSPATH . "class-XlsxWriter.php");


       

        if ( count($resultadoInformes) > 0 ) {

            $writer = new XLSXWriter();

            $sheet_name = 'Registros';
            $header = array( "string","string","string","string","string" );
            $headerExel = array(
                                'Usuario'
                            ,   'Email'
                            ,   'Telefono'
                            ,   'Consulta'
                            ,   'Mensaje'
                            ,   'Fecha'
                        );

            $writer->writeSheetHeader($sheet_name, $header, $suppress_header_row = true);
            $writer->writeSheetRow($sheet_name, $headerExel);
            
            foreach ($resultadoInformes as $key => $value) {

      

                $insertRow = array(
                                    $value->user_name
                                ,   $value->user_email
                                ,   $value->user_phone
                                ,   $value->user_consulta
                                ,   $value->user_mensaje
                                ,   $value->user_fecha
                            );

                $writer->writeSheetRow($sheet_name, $insertRow);
            }
            
         

            $this->archivo = $this->rutaArchivos . '/ReporteRegistros_' . $index . '_'
                    . date("Y-m-d") . '.xlsx';

            $this->Urlarchivo = $this->urlArchivos . '/ReporteRegistros_' . $index . '_'
                    . date("Y-m-d") . '.xlsx';

            $writer->writeToFile($this->archivo);

        
           

        } else {

            $this->writeLog('No se encontraron datos para generar el archivo', $this->procesoLog);

        }

    }

/*
     |-------------------------------------------------------------------------------
     | Function Generar Informe Semanal
     |-------------------------------------------------------------------------------
    */

    private function obtenerArchivoDis($resultadoInformes, $index = '') {
        set_include_path(get_include_path() . PATH_SEPARATOR . "..");
        include_once(CLASSPATH . "class-XlsxWriter.php");

        if ( count($resultadoInformes) > 0 ) {

            $writer = new XLSXWriter();

            $sheet_name = 'Registros';
            $header = array( "string","string","string","string","string" );
            $headerExel = array(
                                'name'	    
                            ,	'phone'	   	
                            ,	'company'	
                            ,	'redes'	    
                            ,	'email'	    
                            ,	'page_web'	
                            ,	'country'	
                            ,	'city'		
                            ,	'mensaje'	
                            ,	'date'		
                        );

            $writer->writeSheetHeader($sheet_name, $header, $suppress_header_row = true);
            $writer->writeSheetRow($sheet_name, $headerExel);
            
            foreach ($resultadoInformes as $key => $value) {
                $insertRow = array(
                                    $value->name
                                ,   $value->phone
                                ,   $value->company
                                ,   $value->redes
                                ,   $value->email
                                ,   $value->page_web
                                ,   $value->country
                                ,   $value->city
                                ,   $value->mensaje
                                ,   $value->date
                            );

                $writer->writeSheetRow($sheet_name, $insertRow);
            }
            
            $this->archivo = $this->rutaArchivos . '/ReporteRegistros_' . $index . '_'
                    . date("Y-m-d") . '.xlsx';

            $this->Urlarchivo = $this->urlArchivos . '/ReporteRegistros_' . $index . '_'
                    . date("Y-m-d") . '.xlsx';

            $writer->writeToFile($this->archivo);

        
           

        } else {

            $this->writeLog('No se encontraron datos para generar el archivo', $this->procesoLog);

        }

    }
    /*
     |-------------------------------------------------------------------------------
     | Function Generar Informe Semanal
     |-------------------------------------------------------------------------------
    */
    private function obtenerArchivoNewsletter($resultadoInformes, $index = '') {
 
        set_include_path(get_include_path() . PATH_SEPARATOR . "..");
        include_once(CLASSPATH . "class-XlsxWriter.php");

        if ( count($resultadoInformes) > 0 ) {

            $writer = new XLSXWriter();

            $sheet_name = 'Registros';
            $header = array( "string","string" );
            $headerExel = array(
                                'Email'
                            ,   'Fecha'
                            ,   'Sexo'
                        );

            $writer->writeSheetHeader($sheet_name, $header, $suppress_header_row = true);
            $writer->writeSheetRow($sheet_name, $headerExel);
            
            foreach ($resultadoInformes as $key => $value) {

      

                $insertRow = array(
                                    $value->user_email
                                ,   $value->user_fecha
                                ,   $value->user_sexo
                            );

                $writer->writeSheetRow($sheet_name, $insertRow);
            }
            
         

            $this->archivo = $this->rutaArchivos . '/ReportePreventa_' . $index . '_'
                    . date("Y-m-d") . '.xlsx';

            $this->Urlarchivo = $this->urlArchivos . '/ReportePreventa_' . $index . '_'
                    . date("Y-m-d") . '.xlsx';

            $writer->writeToFile($this->archivo);

    
        } else {

            $this->writeLog('No se encontraron datos para generar el archivo', $this->procesoLog);

        }

    }



    public function functionExcelRegistros() {

        global $wpdb;

        // Create response object Ajax
        $objLoad = ( object ) array(
                'validate'  => false
        );

        date_default_timezone_set($this->timeZone);
        $this->verificarRutaArchivos();

        $this->procesoLog = 'Generar Excel FHR';
        $this->writeLog('Inicio del proceso', $this->procesoLog);

        $table_name = $wpdb->prefix . "contacto"; 
		
        $resultInformes = $wpdb->get_results( "SELECT * FROM $table_name");
        

        if( ! empty( $resultInformes ) && is_array( $resultInformes ) ){

            // Obtener archivo informes
            
            $this->obtenerArchivo($resultInformes, '1');

            if ( file_exists( $this->archivo ) ) {

                $this->writeLog('Archivo generado correctamente', $this->procesoLog);
                $objLoad -> validate = true;
                $objLoad -> link = $this->Urlarchivo;

            }

        }else{

           $this->writeLog('No se encontraron fechas', $this->procesoLog);
           $objLoad -> validate = false;

        }


        $this->writeLog('Fin del proceso', $this->procesoLog);

        $this->writeLog('Fin del proceso', $this->procesoLog);
        echo json_encode($objLoad);
        die(); // Siempre hay que terminar con die

    }


    public function generarExcelDis() {

        global $wpdb;

        // Create response object Ajax
        $objLoad = ( object ) array(
                'validate'  => false
        );

        date_default_timezone_set($this->timeZone);
        $this->verificarRutaArchivos();

        $this->procesoLog = 'Generar Excel FHR';
        $this->writeLog('Inicio del proceso', $this->procesoLog);

        $table_name = $wpdb->prefix . "distribuidor"; 
		
        $resultInformes = $wpdb->get_results( "SELECT * FROM $table_name");
        

        if( ! empty( $resultInformes ) && is_array( $resultInformes ) ){

            // Obtener archivo informes
            
            $this->obtenerArchivoDis($resultInformes, '1');

            if ( file_exists( $this->archivo ) ) {

                $this->writeLog('Archivo generado correctamente', $this->procesoLog);
                $objLoad -> validate = true;
                $objLoad -> link = $this->Urlarchivo;

            }

        }else{

           $this->writeLog('No se encontraron fechas', $this->procesoLog);
           $objLoad -> validate = false;

        }


        $this->writeLog('Fin del proceso', $this->procesoLog);

        $this->writeLog('Fin del proceso', $this->procesoLog);
        echo json_encode($objLoad);
        die(); // Siempre hay que terminar con die

    }



    
      /*
     |-------------------------------------------------------------------------------
     | Function Generar Informe Semanal
     |-------------------------------------------------------------------------------
    */
    public function functionNewsletter() {

        global $wpdb;

        // Create response object Ajax
        $objLoad = ( object ) array(
                'validate'  => false
        );

        date_default_timezone_set($this->timeZone);
        $this->verificarRutaArchivos();

        $this->procesoLog = 'Generar Excel FHR';
        $this->writeLog('Inicio del proceso', $this->procesoLog);

        $table_name = $wpdb->prefix . "newsletter"; 
		
        $resultInformes = $wpdb->get_results( "SELECT * FROM $table_name");


        if( ! empty( $resultInformes ) && is_array( $resultInformes ) ){

            // Obtener archivo informes
            
            $this->obtenerArchivoNewsletter($resultInformes, '1');

            $objLoad ->array = $this->obtenerArchivoNewsletter($resultInformes, '1');

            if ( file_exists( $this->archivo ) ) {

                $this->writeLog('Archivo generado correctamente', $this->procesoLog);
                $objLoad -> validate = true;
                $objLoad -> link = $this->Urlarchivo;

            }

        }else{

           $this->writeLog('No se encontraron fechas', $this->procesoLog);
           $objLoad -> validate = false;

        }


        $this->writeLog('Fin del proceso', $this->procesoLog);

        $this->writeLog('Fin del proceso', $this->procesoLog);
        echo json_encode($objLoad);
        die(); // Siempre hay que terminar con die

    }


    private function verificarRutaArchivos() {

        $dirLog = get_stylesheet_directory() . "/logs2";
        $urlLog = get_bloginfo( 'template_url' ) . "/logs2";

        if (file_exists($dirLog)) {
            $this->rutaArchivos = $dirLog;
            $this->urlArchivos = $urlLog;
        } else {
            $dir = mkdir($dirLog, 0755);
            if ($dir) {
                $this->rutaArchivos = $dirLog;
                $this->urlArchivos = $urlLog;
            }
        }
    }

    private function writeLog($texto, $proceso) {
        if ($this->debug && file_exists($this->rutaArchivos)) {
            $arch = fopen($this->rutaArchivos . "/zD_log_" . date("Y-m-d") . ".txt", "a+");
            if ($arch) {
                fwrite($arch, "[" . date("Y-m-d H:i:s") . " " . $this->getIp() . " - " . "$proceso ] " . $texto . "\n");
                fclose($arch);
            }
        }
    }

    private function getIp() {
        if (preg_match("/^([d]{1,3}).([d]{1,3}).([d]{1,3}).([d]{1,3})$/", getenv('HTTP_X_FORWARDED_FOR'))) {
            return getenv('HTTP_X_FORWARDED_FOR');
        }
        return getenv('REMOTE_ADDR');
    }

}

?>
