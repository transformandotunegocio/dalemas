<?php

/**
 * Template Name: Contacto
 *
 * @package WordPress
 */

get_header();
$nonceContactForm = wp_create_nonce( 'nonceContactForm' ); 
?>
  <main class="woowContentFull main main_contacto">
    <div class="woowContent1400 pure-g">
        <div class="pure-u-1 pure-u-md-1-2 column_contacto">
            <h1 class="title_contacto"><?= __('contáctenos', 'Dale') ?></h1>
            <p class="info_contacto"><?= __('Quieres aportar al crecimiento de la escena kinky en Colombia? Esta es tu oportunidad. Dale más fabrica y comercializa prendas fetichistas y kinky masculinas. Somos una empresa joven que está creciendo rápidamente. Es por eso que Dale Mas busca frecuentemente empleados que estén interesados en esta escena.', 'Dale') ?></p>
            <p class="info_contacto"><strong><?= __('Taller', 'Dale') ?>:</strong> <?= __('Tenemos un taller donde trabajamos diferentes materiales como el cuero, fibras, neopreno y materiales sintéticos. Si tiene experiencia en confesión trabajando con estos materiales, estaremos encantados de recibir su hoja de vida.', 'Dale') ?></p>
            <p class="info_contacto"><strong><?= __('Ventas', 'Dale') ?>:</strong> <?= __('Buscamos para nuestras tiendas en Bogotá y Medellín, personal de tiempo completo con disponibilidad para trabajar los sábados. Si tienes experiencia en ventas en el área de la moda y deseas trabajar con nosotros envíanos tu hoja de vida.', 'Dale') ?> </p>
            <p class="faqs_contacto">
                <a href="<?=home_url('faqs')?>"><?= __('Preguntas Frecuentes', 'Dale') ?></a>
            </p>
        </div>
        <div class="pure-u-1 pure-u-md-1-2 column_contacto">
            <form id="formContacto">
                <h2><?= __('ESCRÍBENOS', 'Dale') ?></h2>
                <input class="contacto_input" name="name" placeholder="<?= __('Nombre', 'Dale') ?>*" type="text" required>
                <input class="contacto_input" name="email" placeholder="Email*" type="email" required>
                <input class="contacto_input" name="telefono" placeholder="<?= __('Teléfono', 'Dale') ?>*" type="number" required>
                <select class="contacto_select"  name="consulta" required>
                    <option value="" disabled selected="selected"><?= __('Tipo de consulta', 'Dale') ?></option>
                    <option value="<?= __('Obtener Información', 'Dale') ?>"><?= __('Obtener Información', 'Dale') ?></option>
                    <option value="<?= __('Trabajar con nosotros', 'Dale') ?>"><?= __('Trabajar con nosotros', 'Dale') ?></option>
                    <option value="<?= __('Ser influencer', 'Dale') ?>"><?= __('Ser influencer', 'Dale') ?></option>
                </select>
                <textarea class="contacto_textarea" placeholder="<?= __('Mensaje', 'Dale') ?>"  name="mensaje" required></textarea>
                <input name="action" type="hidden" value="saveContacto">
                <input type="hidden" name="nonceContactForm" value="<?php echo $nonceContactForm; ?>">
                <button type="button" onClick="SendForm( '#formContacto' )"><?= __('Enviar mensaje', 'Dale') ?></button>
            </form>
        </div>
        <div class="pure-u-1 pure-u-md-1-3 contancto_info_tienda">
            <h4>BOGOTÁ</h4>
            <ul>
                <li> 
                    <a href="https://www.google.com/maps/place/Dale+M%C3%A1s/@4.6492097,-74.0623463,15z/data=!4m5!3m4!1s0x0:0x804c7456c48a8d59!8m2!3d4.6492097!4d-74.0623463">
                        <i class="icon-map"></i>
                        Calle 59 # 9 - 30, Chapinero
                    </a>
                </li>
                <li> 
                    <a href="mailto:dalemas@dalemas.store">
                        <i class="icon-envelop"></i>
                        ventas1dalemas@gmail.com
                    </a>
                </li>
                <li> 
                    <a href="tel:+573143154888">
                        <i class="icon-phone"></i>
                        (+57)-3138046819
                    </a>
                </li>
            </ul>
        </div>
        <div class="pure-u-1 pure-u-md-1-3 contancto_info_tienda">
            <h4>MEDELLÍN</h4>
            <ul>
                <li> 
                    <a href="https://www.google.com/maps/place/Cl.+10+%2343-16,+Medell%C3%ADn,+Antioquia/@6.2105998,-75.5724181,17z/data=!3m1!4b1!4m5!3m4!1s0x8e44282bb97eec27:0xf49ae98e10179733!8m2!3d6.2105998!4d-75.5702294?hl=es">
                        <i class="icon-map"></i>
                        Calle 10 # 43 - 16
                    </a>
                </li>
                <li> 
                    <a href="mailto:dalemas@dalemas.store">
                        <i class="icon-envelop"></i>
                        ventas1dalemas@gmail.com
                    </a>
                </li>
                <li> 
                    <a href="tel:+573143154888">
                        <i class="icon-phone"></i>
                        (+57)-3505152770
                    </a>
                </li>
            </ul>
        </div>

    </div>
    <div class="content_mapa_contacto woowContent1400">

        <div class="content_botones_mapa">
            <a class="boton_mapa_active" id="butonMapaBogota" href="#"><i class="icon-map-marker"></i><?= __('Sede Bogotá', 'Dale') ?></a>
            <a id="butonMapaMedellin" href="#"><i class="icon-map-marker"></i><?= __('Sede Medellín', 'Dale') ?></a>
        </div>

       
        <div id="boxMapaBogota" class="botones_comprar_content">
            <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d7953.424035379!2d-74.061836!3d4.645368!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8e3f9a38695d9b2b%3A0x8eede85118c0416f!2sCl.%2059%20%239-30%2C%20Bogot%C3%A1%2C%20Colombia!5e0!3m2!1ses!2sus!4v1617985340075!5m2!1ses!2sus" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
        <div id="boxMapaMedellin" class="botones_comprar_content">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.4019446983516!2d-75.57241808523092!3d6.2105997955040895!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8e44282bb97eec27%3A0xf49ae98e10179733!2sCl.%2010%20%2343-16%2C%20Medell%C3%ADn%2C%20Antioquia!5e0!3m2!1ses!2sco!4v1623165051159!5m2!1ses!2sco" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </div>
  </main>
<?php
get_footer();
?>