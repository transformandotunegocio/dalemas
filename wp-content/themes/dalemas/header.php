<?php

/**
 * The Header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="main">
 *
 * @package WordPress
 * @subpackage Nampa Basico
 * @since Nampa Basico 1.0
 */
$nonce = wp_create_nonce( 'nonceContactForm' ); 
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> >
	<head>
		<meta charset="<?php bloginfo('charset'); ?>" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="<?php echo get_bloginfo('description', 'display'); ?>">
		<meta name="title" content="<?php echo wp_title('|', true, 'left'); ?>">
		<meta name="language" content="Español">
		<meta name="googlebot" content="INDEX, FOLLOW">
		<meta name="facebook-domain-verification" content="jcy1hxckl2at4ms1etgev61jnlp9ra"/>
		<!-- Iccon Site -->
		<link rel="stylesheet" href="<?php echo CSSURL ?>fuentes.css?ver=<?php echo VCACHE ?>">
		<!-- Icomoon -->
		<link rel="stylesheet" href="<?php echo CSSURL ?>icomoon/style.css">
		<!-- General Css Styles -->
		<link rel="stylesheet" href="<?php echo CSSURL ?>pure-min.css">
		<link rel="stylesheet" href="<?php echo CSSURL ?>grids-responsive-min.css">
		<link rel="stylesheet" href="<?php echo CSSURL ?>swiper.min.css">
		<link rel="stylesheet" href="<?php echo CSSURL ?>nouislider.min.css">
		<!-- Style Site -->
		<link rel="stylesheet" href="<?php echo CSSURL ?>style.css?ver=<?php echo VCACHE ?>">
		<!-- Responsive Style Site -->
		<link rel="stylesheet" href="<?php echo CSSURL ?>style-responsive.css?ver=<?php echo VCACHE ?>">
		<?php wp_head(); ?>
		<style>html{margin: 0 !important;}</style>
		<title><?php wp_title('|', true, 'left'); ?></title>
	</head>
	<body <?php body_class(); ?>>
		<header class="woowContentFull header">
			<div class="woowContent1400 header_content">
				<div class="header_logo">
					<a href="<?= home_url() ?>"><img src="<?= IMGURL ?>logo.png" alt=""></a>
				</div>
				<nav class="header_menu">
					<?php
					if (wp_is_mobile()){
						$liMenu = 'header_menu_li_responsive';
						?>
						<div id="menuResponsive" class="menu_responsive">
							<div class="icon_menu_responsive">
								<p><i class="icon-menu"></i></p>								
							</div>
						</div>
						<div class="menu_tools_car_responsive">
							<div class="menu_tools_car ">
								<a class="car_a" href="<?=home_url('carrito')?>"><span><?= sprintf(_n('%d', '%d', WC()->cart->get_cart_contents_count()), WC()->cart->get_cart_contents_count()); ?></span></a>
								<div class="content_carrito wrapperHeader">
									<div class="content_carrito_header">
										<h3>BOLSA DE COMPRAS</h3>
										<div class="close_carrito">
											<i class="icon-cheveron-down"></i>
										</div>
									</div>
									<div class="cart_mini_fast"></div>
								</div>
							</div>
						</div>
						<?php
					}else{
						$liMenu = '';
					}
					?>
					<ul class="header_menu_ul">
						<li class="header_menu_li"><a href="<?= home_url() ?>"><?= __('inicio', 'Dale') ?></a></li>
						<li class="header_menu_li <?=$liMenu?>">
							<a href="#"><?= __('productos', 'Dale') ?><i class="icon-cheveron-down"></i></a>
							<ul class="header_menu_sub_ul">
								<li class="header_menu_sub_li"><a href="<?= home_url('tienda/bdsm') ?>">BDSM</a></li>
								<li class="header_menu_sub_li"><a href="<?= home_url('tienda/'.__('cuero', 'Dale')) ?>"><?= __('cuero', 'Dale') ?></a></li>
								<li class="header_menu_sub_li"><a href="<?= home_url('tienda/'.__('urbano', 'Dale')) ?>"><?= __('urbano', 'Dale') ?></a></li>
								<li class="header_menu_sub_li"><a href="<?= home_url('tienda/'.__('sintetico', 'Dale')) ?>"><?= __('sintetico', 'Dale') ?></a></li>
								<li class="header_menu_sub_li"><a href="<?= home_url('tienda/'.__('neopreno', 'Dale')) ?>"><?= __('neopreno', 'Dale') ?></a></li>
							</ul>
						</li>
						<li class="header_menu_li"><a href="<?= home_url('distribuidores') ?>">Distribuidores</a></li>
						<li class="header_menu_li"><a href="<?= home_url('marca') ?>"><?= __('marca', 'Dale') ?></a></li>
						<li class="header_menu_li"><a href="<?= home_url('blog') ?>">Blog</a></li>
						<li class="header_menu_li"><a href="<?= home_url('contactenos') ?>"><?= __('contáctenos', 'Dale') ?></a></li>
						<li class="header_menu_li <?=$liMenu?> menu_tools_use">
							<a href="#">
								<i class="icon-user"></i>
								<i class="icon-cheveron-down"></i>
							</a>
							<ul class="header_menu_sub_ul">
								<li class="header_menu_sub_li woow_popup txt">
									<a><?= __('Iniciar sesión', 'Dale') ?></a>
									<div class="popup_content" style="display:none">
										<div class="content_modal_login">
											<div class="modal_login_imagen">
												<img src="<?=IMGURL?>pop_registtro.jpg" alt="">
											</div>
											<div class="modal_login_formulario">
												<div id="boxTabsLogin" class="tabs_login_content">
														<?php
															// Login form arguments.
															$args = array(
																'echo'           => true,
																'redirect'       => home_url(), 
																'form_id'        => 'loginform',

																
																'label_password' => '',
																'label_remember' => __( 'Remember Me' ),
																'label_log_in'   => __( 'Log In' ),
																'id_username'    => 'user_login',
																'id_password'    => 'user_pass',
																'id_remember'    => 'rememberme',
																'id_submit'      => 'wp-submit',
																'remember'       => true,
																'value_username' => NULL,
																'value_remember' => true
															); 
															
															// Calling the login form.
															wp_login_form( $args );
														
														?>
												</div>
											</div>
										</div>
									</div>
								</li>
								<li class="header_menu_sub_li woow_popup txt">
									<a><?= __('Registrarse', 'Dale') ?></a>
									<div class="popup_content" style="display:none">
										<div class="content_modal_login">
											<div class="modal_login_imagen">
												<img src="<?=IMGURL?>pop_registtro.jpg" alt="">
											</div>
											<div class="modal_login_formulario">
												<div id="boxTabsRegister" class="tabs_login_content">
													<form class="pure-g pure-form-stacked woowFormRegiter" method="post" name="FormRegistro">
														<div class="pure-u-1">
															<input type="text" name="nombre" required placeholder="<?= __('Nombre', 'Dale') ?>*" />
														</div>
														<div class="pure-u-1">
															<input type="email" name="correo" required placeholder="<?= __('Correo electrónico', 'Dale') ?>*" /> 
														</div>
														<div class="pure-u-1">
															<input type="password" name="pwd"  required placeholder="<?= __('Contraseña', 'Dale') ?>" />
														</div>
														<div class="pure-u-1">
															<input type="password" name="pwd_2" required placeholder="<?= __('Repetir Contraseña', 'Dale') ?>" />
														</div>
														<div class="pure-u-1 content_register_terminos">
															<p class="termscondition parentValidate">
																<input name="TermsConditions" type="checkbox" required><span><?= __('Acepto', 'Dale') ?> <a target="_black" href="<?=home_url(__('terminos-y-condiciones', 'Dale'))?>"><?= __('los términos y condiciones', 'Dale') ?></a></span>
															</p>
														</div>
														<input type="hidden" id="action" name="action" value="RegistroUsuarios">
														<input type="hidden" id="nonceRegisterForm" name="nonceRegisterForm" value="<?php echo $nonce;?>">
												
														<div class="pure-u-1 regitser_button_send">
															<button type="button" class="btnEnviar" onClick="SendForm( '.popup_txt .woowFormRegiter' )"><?= __('Registrarme', 'Dale') ?></button>
														</div>
        											</form>
												</div>
											</div>
										</div>
									</div>
									
								</li>
	
							</ul>
						</li>
						<li class="header_menu_li menu_tools_sea">
							<a href="#"><i class="icon-search"></i></a>
							<div class="tools_search_content">
								<?php get_product_search_form();?>
							</div>
						</li>
						<li class="header_menu_li">
							<a class="car_a" href="<?=home_url(__('carrito', 'Dale'))?>"><div class="menu_tools_car">
								<span><?= sprintf(_n('%d', '%d', WC()->cart->get_cart_contents_count()), WC()->cart->get_cart_contents_count()); ?></span>
							</div></a>
							<div class="content_carrito wrapperHeader">
								<div class="content_carrito_header">
									<h3>BOLSA DE COMPRAS</h3>
									<div class="close_carrito">
										<i class="icon-cheveron-down"></i>
									</div>
								</div>
								<div class="cart_mini_fast"></div>
							</div>
						</li>
					</ul>
				</nav>
			</div>
		</header>