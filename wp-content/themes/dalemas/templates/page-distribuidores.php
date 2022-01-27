<?php

/**
 * Template Name: Distribuidores
 *
 * @package WordPress
 */

get_header();
$nonceContactForm = wp_create_nonce( 'nonceContactForm' ); 
?>
    <main class="woowContentFull main main_distribuidor">
        <div class="woowContent1400 pure-g">
            <form id="formDistribuidor">
                <h1>APLICACIÓN PARA SER DISTRIBUIDOR AUTORIZADO</h1>
                <p>Completa este formulario para conocer más detalles de precios y ser uno de nuestros distribuidores autorizados.</p>
                <h2>¡PRONTO NOS CONTACTAREMOS CONTIGO!</h2>
                <div class="pure-g">
                    <div class="pure-u-1">
                        <label>
                            Nombres y Apellidos*<br>
                            <input type="text" name="name" required>
                        </label>
                    </div>
                    <div class="pure-u-1">
                        <label>
                            Número de contacto + Codigo de área*<br>
                            <input type="tel" name="phone" required>
                        </label>
                    </div>
                    <div class="pure-u-1">
                        <label>
                            Nombre de la compañía*<br>
                            <input type="text" name="company" required>
                        </label>
                    </div>
                    <div class="pure-u-1">
                        <label>
                            Redes sociales<br>
                            <input type="text" name="redes">
                        </label>
                    </div>
                    <div class="pure-u-1-2">
                        <label>
                            Email*<br>
                            <input type="email" name="email" required>
                        </label>
                    </div>
                    <div class="pure-u-1-2">
                        <label>
                            Página web<br>
                            <input type="text" name="page_web">
                        </label>
                    </div>
                    <div class="pure-u-1-2">
                        <label>
                            País<br>
                            <input type="text" name="country">
                        </label>
                    </div>
                    <div class="pure-u-1-2">
                        <label>
                            Ciudad<br>
                            <input type="text" name="city">
                        </label>
                    </div>
                    <div class="pure-u-1">
                        <label>
                            Cuéntanos<br>
                            <textarea name="mensaje"></textarea>
                        </label>
                    </div>
                </div>
                <div class="pure-u-1">
                    <input name="action" type="hidden" value="saveDistribuidor">
                    <input type="hidden" name="nonceContactForm" value="<?php echo $nonceContactForm; ?>">
                    <button type="button" onClick="SendForm( '#formDistribuidor' )"><?= __('Enviar mensaje', 'Dale') ?></button>
                </div>
            </form>
        </div>
    </main>
    <!--POPUP PRINCIPAL-->
	<div id="pop_distribuidor" class="woow_popup txt">
		<div class="popup_content">
            <div class="contentPopUp">
                <?php
                    if(wp_is_mobile()){
                        echo '<img src="'.IMGURL.'gracias_dis_m.jpg" alt="">';
                    }else{
                        echo '<img src="'.IMGURL.'gracias_dis.jpg" alt="">';
                    }
                ?>
            </div>
		</div>
	</div>
<?php
get_footer();
?>