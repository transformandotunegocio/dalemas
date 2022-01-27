<?php

/**
 * The template for displaying the footer.
 *
 *
 * @package WordPress
 */
//Verificacion de link dependiendo del idioma
$url 	 	= $_SERVER["REQUEST_URI"];
$isMexico 	= false;
$isColombia = true;
//verificaxion brasil
$mx  	 	= stripos($url, 'mx/');
if($mx > 0){
	$isMexico 	= true;
	$isColombia = false;
}
?>
	<footer class="woowContentFull footer">
		<div class="woowContent1400 pure-g">
			<?php
				if(!$isMexico){
					?>
					<div class="pure-u-1 pure-u-md-1-5 footer_section">
						<a href=""><img src="<?= IMGURL ?>logo.png" alt=""></a>
					</div>
					<div class="pure-u-1 pure-u-md-1-5 footer_section">
						<h4>BOGOTÁ +</h4>
						<p><?= __('Calle', 'Dale') ?> 59 # 9 – 30 Chapinero</p>
						<br><br>
						<h4><?= __('HORARIO', 'Dale') ?></h4>
						<p><?= __('Lunes a Jueves', 'Dale') ?></p>
						<p>11:00 am - 7:30 pm.</p>
						<br><br>
						<p><?= __('Viernes a Sábado', 'Dale') ?></p>
						<p>11:00 am a 8:00 pm.</p>
						<br><br>
						<h4><?= __('TELÉFONO', 'Dale') ?></h4>
						<p>(+57)350 5152770</p>
					</div>
					<div class="pure-u-1 pure-u-md-1-5 footer_section">
						<h4>BOGOTÁ Theatron+</h4>
						<p><?= __('Calle', 'Dale') ?> 58 #10-32</p>
						<br><br>
						<h4><?= __('HORARIO', 'Dale') ?></h4>
						<p><?= __('Lunes a Jueves', 'Dale') ?></p>
						<p>9:00 p.m a 2:00 a.m</p>
						<br><br>
						<h4><?= __('TELÉFONO', 'Dale') ?></h4>
						<p>(+57)350 5152770</p>
					</div>
					<div class="pure-u-1 pure-u-md-1-5 footer_section">
						<h4>MEDELLÍN +</h4>
						<p><?= __('Calle', 'Dale') ?> 10 # 43 - 16</p>
						<br><br>
						<h4><?= __('HORARIO', 'Dale') ?></h4>
						<p><?= __('Lunes a Sábado', 'Dale') ?></p>
						<p>11:00 am - 8:00 pm.</p>
						<br><br>
						<h4><?= __('TELÉFONO', 'Dale') ?></h4>
						<p>(+57)350 5152770</p>
					</div>
					<?php
			  	}else{
					echo '<style>.centra_footer_mexico{margin:auto}</style>';
				}
			?>
			
			<div class="pure-u-1 pure-u-md-1-5 footer_section centra_footer_mexico">
				<ul class="ul_redes_sociales">
					<li><a href="https://www.instagram.com/dalemas_store/" target="_blank"><i class="icon-instagram"></i></a></li>
					<li><a href="https://www.facebook.com/dale.mas.3557" target="_blank"><i class="icon-facebook"></i></a></li>
					<li><a href="https://twitter.com/DaleMasColombia" target="_blank"><i class="icon-twitter"></i></a></li>
				</ul>
				<ul class="ul_terminos">
					<li><a href="<?= home_url('politicas') ?>"><?= __('Políticas de Privacidad', 'Dale') ?>.</a></li>
					<li><a href="<?= home_url('terminos-y-condiciones') ?>"><?= __('Términos y condiciones', 'Dale') ?>.</a></li>
					<li><a href="<?= home_url('faqs') ?>"><?= __('Preguntas Frecuentes', 'Dale') ?>.</a></li>
				</ul>
			</div>
			<div class="pure-u-1 footer_section_copy">
				<p>COPYRIGHT ©  <?= __('TODOS LOS DERECHOS RESERVADOS', 'Dale') ?>.</p>
			</div>
		</div>
	</footer>
	<!--POPUP PRINCIPAL-->
	<div id="pop_bancobogota" class="woow_popup txt">
		<div class="popup_content">
			<div class="info_bancobogota">
				<div class="pure-g">
					<div class="pure-u-1 contentPopUp">
						<form class="pure-form pure-form-aligned forms_pages" id="kinky" method="POST">
							<img src="<?=IMGURL?>fondo_modal.jpg" alt="">
							<fieldset>
								<div class="pure-g">
									<div class="pure-u-1">
										<div class="section_title_modal">
											<h2><?= __('SÉ PARTE DE NUESTRA', 'Dale') ?></h2>
											<h1><?= __('COMUNIDAD KINKY', 'Dale') ?></h1>
											<h2><?= __('Y RECIBE UN', 'Dale') ?> <span>10%</span></h2>
											<h2><span><?= __('DE DESCUENTO', 'Dale') ?></span></h2>
										</div>
										<div class="section_input_email">
											<input name="email" type="email" required placeholder="Email">
										</div>
										<div class="title_modal_sexo">
											<p><?= __('Sexo', 'Dale') ?>*</p>
										</div>
										<div class="section_input_sexo">
											<div class="section_input_sexo_container">
												<div class="sexo_input">
													<input type="radio" name="sexo" value="Mujer" id="Uno" required/>
													<label for="Uno">
														<svg class="check" viewbox="0 0 40 40">
															<defs>
																<linearGradient id="gradient" x1="0" y1="0" x2="0" y2="100%">
																	<stop offset="0%" stop-color="#FB0D1B"></stop>
																	<stop offset="100%" stop-color="#FB0D1B"></stop>
																</linearGradient>
															</defs>
															<circle id="border" r="18px" cx="20px" cy="20px"></circle>
															<circle id="dot" r="8px" cx="20px" cy="20px"></circle>
														</svg>
														<?= __('Mujer', 'Dale') ?>
													</label>
												</div>
												<div class="sexo_input">
													<input type="radio" name="sexo" value="Hombre" id="Dos" required/>
													<label for="Dos">
														<svg class="check" viewbox="0 0 40 40">
															<defs>
																<linearGradient id="gradient" x1="0" y1="0" x2="0" y2="100%">
																	<stop offset="0%" stop-color="#FB0D1B"></stop>
																	<stop offset="100%" stop-color="#FB0D1B"></stop>
																</linearGradient>
															</defs>
															<circle id="border" r="18px" cx="20px" cy="20px"></circle>
															<circle id="dot" r="8px" cx="20px" cy="20px"></circle>
														</svg>
														<?= __('Hombre', 'Dale') ?>
													</label>
												</div>
												<div class="sexo_input">
													<input type="radio" name="sexo" value="Prefiero no decirlo" id="Tres" required/>
													<label for="Tres">
														<svg class="check" viewbox="0 0 40 40">
															<defs>
																<linearGradient id="gradient" x1="0" y1="0" x2="0" y2="100%">
																	<stop offset="0%" stop-color="#FB0D1B"></stop>
																	<stop offset="100%" stop-color="#FB0D1B"></stop>
																</linearGradient>
															</defs>
															<circle id="border" r="18px" cx="20px" cy="20px"></circle>
															<circle id="dot" r="8px" cx="20px" cy="20px"></circle>
														</svg>
														<?= __('Prefiero no decirlo', 'Dale') ?>
													</label>
												</div>
											</div>											
										</div>
										<div class="section_input_terms">
											<div class="section_input_sexo_container">
												<div class="sexo_input">
													<input type="checkbox" name="terms" id="Cuatro" required/>
													<label for="Cuatro" style="align-items: flex-start;">
														<svg class="check" style="width: 80px;" viewbox="0 0 40 40">
															<defs>
																<linearGradient id="gradient" x1="0" y1="0" x2="0" y2="100%">
																	<stop offset="0%" stop-color="#FB0D1B"></stop>
																	<stop offset="100%" stop-color="#FB0D1B"></stop>
																</linearGradient>
															</defs>
															<circle id="border" r="18px" cx="20px" cy="20px"></circle>
															<circle id="dot" r="8px" cx="20px" cy="20px"></circle>
														</svg>
														<?= __('Me gustaría recibir noticias sobre productos y servicios de Dale Más SAS. Consiento recibir mensajes de markenting personalizados por correo electrónico de Dale Más SAS.', 'Dale') ?>
														
													</label>
												</div>
									
											</div>
								
										</div>
										<?php
											$nonceContactForm = wp_create_nonce( 'nonceContactForm' );
										?>
										<div class="pure-control-group inputSubmit">
											<input name="action" type="hidden" value="Newsletter">
											<input type="hidden" name="nonceContactForm" value="<?php echo $nonceContactForm; ?>">
											<button type="button" class="input_submit" name="cargar" onClick="SendForm( '#kinky' )"><?= __('SUSCRIBIRSE', 'Dale') ?></button>
											<p class="texto_modal_terns"><?= __('Al hacer click en Suscribirse, confirmas que has leido', 'Dale') ?> <br><?= __('y aceptas el aviso de privacidad de Dale Más', 'Dale') ?>.</p>
										</div>
									</div>
								</div>
								<div class="pure-control-group printErrors" id="div-errors" style="display: none;"></div>
							</fieldset>
							<div class="alerts_forms">
								<div id="alertFailValidation" class="alertFail">
									<span> <?= __('Por favor, ingrese todos los datos requeridos', 'Dale') ?> </span>
								</div>
							</div>
						</form> 
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- LOADER SPECIAL-->		
	<section id="loader_special">
		<div class="cont_loader">
			<div class="cart_loader">
				<img src="<?php echo IMGURL; ?>elements/oval.svg" />
				<p class="expecial_txt_loader"><?= __('Cargando', 'Dale') ?>...</p>
			</div>
		</div>
		<div class="cont_cart_pre_loader" style="display: none;">
			<div class="cart_loader">
				<img src="<?php echo IMGURL; ?>elements/ovalb.svg" />
				<p class="expecial_cart_loader"><?= __('Cargando', 'Dale') ?>...</p>
			</div>
		</div>
	</section>

	<section id="registro" class="woowContentFull padigSpecialMac">
		<div class="alertOk alertRegistro" >
			<span>
				<?= __('!Gracias por registrarte!', 'Dale') ?>
			</span>
		</div>
		<div class="alertFail" >
			<span></span>
		</div>
	</section>

	<div class="content_widget_wp">
        <a href="https://wa.me/573505152770" target="_blank"><img src="<?=IMGURL?>wp.png" alt=""></a>     
	</div>


	<section class="popup_cover">
		<!-- Pop Up to images -->
		<div class="content_img">
			<span class="popup_close">&times;</span>
			<img class="popup_img popup_animate"></img>
		</div>

		<!-- Pop Up to texts -->
		<div class="popup_txt popup_animate">
			<span class="popup_close">&times;</span>
			aaaa
		</div>
		
	<!-- Pop Up to Video -->
		<div class="popup_video popup_animate">
			<span class="popup_close">&times;</span>
		</div>
	</section>

 
	<?php wp_footer(); ?>
	<!-- Slider Scripts -->
	<!-- General Scripts -->
	<script type="text/JavaScript" src="<?php echo JSURL ?>html5.js"></script>
	<!-- Scripts Pagination -->
	<script type='text/javascript' src='<?php echo JSURL ?>jquery.cookie.js'></script>
	<script type="text/javascript" src="<?php echo JSURL ?>jquery.twbsPagination.min.js"></script>
	<script type="text/javascript" src="<?php echo JSURL ?>jssor.slider-22.2.10.mini.js"></script>
	<script type='text/javascript' src='<?php echo JSURL ?>jquery.form.min.js?ver=<?php echo VCACHE ?>'></script>
	<script type='text/javascript' src='<?php echo JSURL ?>swiper.min.js?ver=<?php echo VCACHE ?>'></script>
	<!-- Slider price Script -->
	<script src="<?php echo JSURL ?>nouislider.min.js"></script>
	<!-- Woow Custom Scripts -->
	<script type='text/javascript' src='<?php echo JSURL ?>app.js?ver=<?php echo VCACHE ?>'></script>
</body>
</html>