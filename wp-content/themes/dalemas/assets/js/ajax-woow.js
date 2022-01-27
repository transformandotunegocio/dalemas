(function( $ ){
	/*
	==============================================
	Scripts Mini Cart
	==============================================
	*/
	ajaxMiniCart = function(addcart) {
		var loader = $(".cont_cart_pre_loader").html();
	
		var data = { action: "miniCart" };

    if (addcart) {
      $('.content_carrito').addClass('open_carrito');
    }
	
		$.ajax({
		  type: "POST",
		  url: MyAjax.url,
		  data: data,
		  beforeSend: function() {
			
			$('.wrapperHeader').addClass("opened");
			$('.wrapperHeader').fadeIn(200);
			$(".wrapperHeader").find(".cart_mini_fast").html(loader);
		  },
		  success: function(msn) {
			  $(".wrapperHeader").find(".cart_mini_fast").html(msn);
		  },
	
		  error: function(msn) {
			console.log(msn);
		  },
	
		  complete: function() {
		  }
		});
	};
	
	// Publicamos la funcion paraque sea visible desde afuera
	this.ajaxMiniCart;
	/*
	==============================================
	Scripts Ajax to send mail "Contact"
	==============================================
	*/

	SendForm = function ( idFom ){

		copyHtmlOk = $( '#alertSuccess span' ).html();

		$( ".input_submit" ).attr( 'disabled', 'disabled' );

		if ( $('.printErrors').is(':visible') ) {

			$('.printErrors').fadeOut(0);

		}
		
		var options = {
				type: "POST"
			,	url: MyAjax.url
			,	dataType: "json"
			,	resetForm: true
			,	beforeSubmit: validate
			,	beforeSend: function(){
				$( '#loader_special' ).fadeIn( 200 );
				$( '#loader_special .expecial_txt_loader' ).html( 'Enviando solicitud...' );
                    
				}
			,	success: function( msn ){

				if (msn.validate == true) {

					$(".alertOk").fadeIn(20);

					if(msn.action == 'Distribuidor'){
						var htmlElement = $('#pop_distribuidor').find('.popup_content').html();
						$('.popup_txt').html(htmlElement).css('display', 'block').css({ 'background-color': '#FFF' });
						$('.popup_txt').append('<span class="popup_close">&times;</span>');
						$('.popup_cover').fadeIn(100);
						$('#pop_distribuidor').find('.popup_content').empty();
						$('.popup_close').click(function() {
							$('.popup_cover').fadeOut(100);
							$('.popup_txt').css({ 'display': 'none', 'width': 'initial', 'background-color': '' });
						});
					}
				} else {
					if (msn.msnError) {
					  var divPrint = ".alertFail";
		  
					  // Print errors of validation
					  $(divPrint)
						.fadeIn()
						.html('<span>'+msn.msnError+'</span>');
					}
		  
					$(".alertFail").fadeIn(200);
					$("#loader_special").fadeOut(200);
				}
			}
			, 	error: function( msn ){

					console.log( msn );

				}
			, complete: function( msn ){
				//ocultar loader
				$( '#loader_special' ).fadeOut( 200 );
				$( ".input_submit" ).attr( 'disabled', false );
			}

		}

		$( idFom ).ajaxSubmit( options );

		setTimeout(function(){  $('.alertFail').fadeOut(500); }, 3500);

	}
	// Publicamos la funcion paraque sea visible desde afuera
	this.SendForm;
    /*
	==============================================
	Scripts Ajax retister facebook
	==============================================
	*/
	TaxFilterTienda = function ( jsonFilter, order ){
		var divload = '.home_products_content';
		var $pagination = $('#pagination');
		$pagination.twbsPagination('destroy');
		
		if(order == undefined){
			var orden = '';
		}else{
			var orden = order;
		}


		$.ajax({
				type: "POST"
			,	url: MyAjax.url
			,	dataType: "json"
			,	data: { jsonFilter: jsonFilter, action : 'FiltersTienda', orden :  orden}
			,	beforeSend: function(){
					$( '#loader_special' ).fadeIn( 200 );
					$( '#loader_special .expecial_txt_loader' ).html( 'Cargando Productos...' );
				}
			,	success: function( msn ){
					if ( msn.validate ) {
						$( divload ).fadeIn(400).html( msn.html );
						if ( msn.mas_pages > 1 ) {

							var totalPages = msn.mas_pages;
							
							$pagination.twbsPagination({
								startPage: 1,
								totalPages: totalPages,
								visiblePages: 3,
								initiateStartPageClick: false,
								first: 'Primera',
								prev: '<i class="icon-arrow-left"></i>',
								next: '<i class="icon-arrow-right"></i>',
								last: 'Última',
								activeClass: 'activePag',
								onPageClick: function (event, page) {
									PrintPageProducts( 'input', false, page, orden);
								}
							});
							

						}
						if ( msn.maxPrice != '' && msn.minPrice != '' ) {

							var html5Slider = document.getElementById('priceSlider'); // $( '#' );
								html5Slider.noUiSlider.updateOptions({
								    start: [ msn.minPrice, msn.maxPrice ],
									connect: true,
									step: 1,
									range: {
										'min': parseInt( msn.minPrice ),
										'max': parseInt( msn.maxPrice )
									}
								});

						}
						
					}

				}

			, 	error: function( msn ){

					//console.log( msn );

				}

			, complete: function( msn ){

					//ocultar loader
					$( '#loader_special' ).fadeOut( 200 );

					//ocultar filtros si es mobile
					if($('.actionFilter').length){
						if($('.filtros').hasClass('mostrarFilter')){
							$('.filtros').fadeOut(100);
						}
					}

				}
		});

	}
	// Publicamos la funcion paraque sea visible desde afuera
	this.TaxFilterTienda;

    /*
	==============================================
	Scripts Search Product Promociones
	==============================================
	*/
	product_chane_items = function ( cat, taxonomy ){

		var datos = 'cat='+cat+'&tax='+taxonomy+'&action=ChangeProducts';

		var divload = '.home_products_content';
		var $pagination = $('#pagination');
		$pagination.twbsPagination('destroy');

		$.ajax({
				type: "POST"
			,	url: MyAjax.url
			,	dataType: "json"
			,	data: datos
			,	beforeSend: function(){
					$( '#loader_special' ).fadeIn( 200 );
					$( '#loader_special .expecial_txt_loader' ).html( 'Cargando Productos...' );
				}
			,	success: function( msn ){

					if ( msn.validate ) {
						
						$( divload ).fadeIn(400).html( msn.html );

						if ( msn.mas_pages > 1 ) {

							var totalPages = msn.mas_pages;
							
							$pagination.twbsPagination({
								startPage: 1,
								totalPages: totalPages,
								visiblePages: 3,
								initiateStartPageClick: false,
								first: 'Primera',
								prev: '<i class="icon-arrow-left"></i>',
								next: '<i class="icon-arrow-right"></i>',
								last: 'Última',
								activeClass: 'activePag',
								onPageClick: function (event, page) {
									PrintPageProducts( 'input', false, page );
								}
							});
							

						}
						
						if ( msn.maxPrice != '' && msn.minPrice != '' ) {

							var html5Slider = document.getElementById('priceSlider'); // $( '#' );
								html5Slider.noUiSlider.updateOptions({
								    start: [ msn.minPrice, msn.maxPrice ],
									connect: true,
									step: 1,
									range: {
										'min': parseInt( msn.minPrice ),
										'max': parseInt( msn.maxPrice )
									}
								});

						}
						
					}

				}

			, 	error: function( msn ){

					//console.log( msn );

				}

			, complete: function(){

					//ocultar loader
					$( '#loader_special' ).fadeOut( 200 );

					//ocultar filtros si es mobile
					if($('.actionFilter').length){
						if($('.filtros').hasClass('mostrarFilter')){
							$('.filtros').fadeOut(100);
						}
					}

				}
		});

	}

	// Publicamos la funcion para que sea visible desde afuera
	this.product_chane_items;

	/*
	==============================================
	Scripts Page Products
	==============================================
	*/	

	PrintPageProducts = function ( type, find, page , order ){

		$('html, body').animate({scrollTop: '0px'}, 1000);

		if ( type == 'input' ) {

			var jsonFilter = $( '.jsonFilter' ).val();
				jsonFilter = JSON.parse( jsonFilter );

			if( typeof jsonFilter[ 'product_cat' ].ID == "undefined" && typeof jsonFilter[ 'marca' ].ID == "undefined" && typeof jsonFilter[ 'price' ].ID == "undefined" ){
				
				var datos = 'page='+page+'&action=ChangeProducts';
			
			}else{


				if(order == undefined){
					var orden = '';
				}else{
					var orden = order;
				}


				var datos = {
						jsonFilter: jsonFilter
					,	action: 'FiltersTienda'
					,	page: page
					,	orden :  orden
				}

			}

		} else if ( type == 'search' ) {

			var datos = 'page='+page+'&find='+find+'&action=SearchProdcuts';

		}else{

			var datos = 'page='+page+'&cat='+find+'&tax='+type+'&action=ChangeProducts';

		}

		var divload = '.home_products_content';

		$.ajax({
				type: "POST"
			,	url: MyAjax.url
			,	dataType: "json"
			,	data: datos
			,	beforeSend: function(){
					$( '#loader_special' ).fadeIn( 200 );
					$( '#loader_special .expecial_txt_loader' ).html( 'Cargando Productos...' );
				}
			,	success: function( msn ){


					if ( msn.validate ) {
						
						$( divload ).fadeIn(400).html( msn.html );

					}
					

				}

			, 	error: function( msn ){

					//console.log( msn );

				}

			, complete: function( msn ){

					$( '#loader_special' ).fadeOut( 200 );

				}
		});
		
	}

	// Publicamos la funcion para que sea visible desde afuera
	this.PrintPageProducts;

	/*
	==============================================
	Scripts to validate input requires in form
	==============================================
	*/
	function validate(formData, jqForm, options) {
		
		var inputValidate = true;
		$(jqForm.selector + " .woowRequireFail").removeClass("woowRequireFail");
		// Validate inputs type [text]
		for (var i = 0; i < formData.length; i++) {
		  var inputName = formData[i].name;
		  // Validate inputs type [text]
		  if (formData[i].required == true && !formData[i].value) {
			inputValidate = false;
			$(jqForm.selector + ' [name="' + inputName + '"]').addClass(
			  "woowRequireFail"
			);
		  }
	
		  // Validate inputs type [email]
		  if (formData[i].type == "email" && formData[i].value) {
			inputValidEmail = validateEmail(formData[i].value);
			if (!inputValidEmail) {
			  inputValidate = false;
			  $(jqForm.selector + ' input[name="' + inputName + '"]').addClass(
				"woowRequireFail"
			  );
			}
		  }
		}
		// Validate inputs type [radio]
		$(jqForm.selector)
		  .find(":radio, :checkbox")
		  .each(function() {
			// get name of input
			name = $(this).attr("name");
			// get attribute required of input
			requiredVal = $(this).attr("required");
			// get attribute checked of input
			checkedVal = $('[name="' + name + '"]:checked').length;
	
			// validate attributes of input
			if (requiredVal && checkedVal == 0) {
			  inputValidate = false;
			  $(this)
				.closest(".parentValidate")
				.addClass("woowRequireFail");
			}
		});
		if (!inputValidate) {
			// Print errors of validation
			$('.alertFail').html('<span>Campos vacios!!</span>');

		 	$(".alertFail").fadeIn(200);

			 setTimeout(function(){  $('.alertFail').fadeOut(500); }, 3500);

		  	return false;
		}
	}
	/*
	==============================================
	Scripts to validate input Email
	==============================================
	*/
	function validateEmail(email) {
		var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
	
		if (!emailReg.test(email)) {
		  return false; //No es E-mail
		} else {
		  return true; //Si es E-mail
		}
	}
	/*
	==============================================
	Scripts to Validate numbers
	==============================================
	*/
	function ValidNumber(e) {
		tecla = document.all ? e.keyCode : e.which;
		//Tecla de retroceso para borrar, siempre la permite
		if (tecla == 8 || tecla == 0) {
		  return true;
		}
		// Patron de entrada, en este caso solo acepta numeros
		patron = /[0-9]/;
		tecla_final = String.fromCharCode(tecla);
		return patron.test(tecla_final);
	}

})( jQuery );