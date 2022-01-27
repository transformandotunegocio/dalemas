jQuery(function($) {

    $('.car_a').click(function(e){
      e.preventDefault();
      var miniCartTime = $.cookie("miniCartTime");
      var timeRest = new Date();
      var theTime = new Date().getTime();
      var timeRest = timeRest.setSeconds(timeRest.getSeconds() - 30);

      if (
        typeof miniCartTime == "undefined" ||
        miniCartTime < timeRest ||
        $(".cart_mini_fast").is(":empty")
      ) {
        // Run ajax mini cart
        ajaxMiniCart();
        $.cookie("miniCartTime", theTime);
      }


      $('.content_carrito').addClass('open_carrito');
    });
    $('.close_carrito').click(function(){
      $('.content_carrito').removeClass('open_carrito');
    });

    $('#pa_colores + .variable-item').click(function(){
      $('#pa_colores + .variable-item').removeClass('selected');
      $(this).addClass('selected');
    });
    $('#pa_talla + .variable-item').click(function(){
      $('#pa_talla + .variable-item').removeClass('selected');
      $(this).addClass('selected');
    });
    /*	
    ==============================================	
    Scripts NEWSLETTER	
    ==============================================
    */
    if (sessionStorage.getItem('popState') != 'shown') {
      // if( false ){	
      var myPopVar = setInterval(function() {
        timeInt = startTime();
        if (timeInt == 10000) {
          var htmlElement = $('#pop_bancobogota').find('.popup_content').html();
          $('.popup_txt').html(htmlElement).css('display', 'block').css({ 'background-color': '#FFF' });
          $('.popup_txt').append('<span class="popup_close">&times;</span>');
          $('.popup_cover').fadeIn(100);
          var fateheul = $('.popup_txt').find(".autocom_div");
          $('#pop_bancobogota').find('.popup_content').empty();

          $('.popup_close').click(function() {
            $('.popup_cover').fadeOut(100);
            $('.popup_txt').css({ 'display': 'none', 'width': 'initial', 'background-color': '' });
          });
          sessionStorage.setItem('popState', 'shown');
          clearInterval(myPopVar);
        }
      }, 2000);
    }

    function startTime() {
      try {
        if (sessionStorage.getItem('Time')) {
          timeInt = parseFloat(sessionStorage.getItem('Time'));
        } else {
          sessionStorage.setItem('Time', '0');
          var timeInt = 0;
        }

        timeInt = checkTime(timeInt);

        var timeIntSting = timeInt.toString();

        sessionStorage.setItem('Time', timeIntSting);

        return timeInt;

      } catch (error) {

        alert('Para disfrutar mejor nuestra web, habilita la opción Cookies y datos de sitio web en: Preferencias > Privacidad');
        clearInterval(myPopVar);
        return false;

      }

    }

    function checkTime(i) {

      if (i < 20000) { i = i + 2000; }
      return i;

    }



    //open search form
    $('.menu_tools_sea a').click(function(e){
        e.preventDefault();
        $('.tools_search_content').toggleClass('openSearchForm');
    })

    //Verifiicamos si existe para las faqs
    if($('.main_faqs').length){
        //* dropdown */
        var dropdown = document.getElementsByClassName("dropdown-btn");
        var i;
        for (i = 0; i < dropdown.length; i++) {
        dropdown[i].addEventListener("click", function() {
            this.classList.toggle("active");
            var dropdownContent = this.nextElementSibling;
            if (dropdownContent.style.height === "auto") {
            dropdownContent.style.height = "0";
            dropdownContent.style.opacity = "0";
            } else {
            dropdownContent.style.height = "auto";
            dropdownContent.style.opacity = "1";
            }
        });
        }
    }
    $('#butonMapaBogota').click(function(e){
        e.preventDefault();
        $('.content_botones_mapa a').removeClass('boton_mapa_active');
        $(this).addClass('boton_mapa_active');
        $('#boxMapaBogota').show();
        $('#boxMapaMedellin').hide();
    });
    $('#butonMapaMedellin').click(function(e){
        e.preventDefault();
        $('.content_botones_mapa a').removeClass('boton_mapa_active');
        $(this).addClass('boton_mapa_active');
        $('#boxMapaBogota').hide();
        $('#boxMapaMedellin').show();
    });

    //Slider del producto
    if($('.swiper-container').length){
            var swiperSlider = new Swiper(".swiper-container", {
                slidesPerView: 2,
                spaceBetween: 10,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
                navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
                },
            });
    }
  if ($("#jssor_1").length) {
    /*
    ==============================================
    Script Slide 1
    ==============================================
    */
    var jssor_1_options = {
      $AutoPlay: true,
      $SlideDuration: 1500,
      $LazyLoading: 0,
      $SlideEasing: $Jease$.$OutQuint,
      $ArrowNavigatorOptions: {
        $Class: $JssorArrowNavigator$
      },
      $BulletNavigatorOptions: {
        $Class: $JssorBulletNavigator$,
        $ChanceToShow: 5
      }
    };
    var jssor_1_slider = new $JssorSlider$("jssor_1", jssor_1_options);
    /*responsive code begin*/
    /*you can remove responsive code if you don't want the slider scales while window resizing*/
    function ScaleSlider() {
      var refSize = jssor_1_slider.$Elmt.parentNode.clientWidth;
      if (refSize) {
        refSize = Math.min(refSize, 2600);
        jssor_1_slider.$ScaleWidth(refSize);
      } else {
        window.setTimeout(ScaleSlider, 30);
      }
    }
    ScaleSlider();
    $(window).bind("load", ScaleSlider);
    $(window).bind("resize", ScaleSlider);
    $(window).bind("orientationchange", ScaleSlider);
  }
  /*
	==============================================
	Scripts to show PopUp elements
	==============================================
	*/
	
	if( $( '.woow_popup' ).length ){
		window.onload = WoowPopup();
	}

	function WoowPopup(){

		// Pop Up to images
		$( '.woow_popup.img' ).on( 'click', function( e ){
			e.preventDefault();
			var theImgSrc = $( this ).find( 'img' ).attr( 'data-large_image' );
			var theImgWidth = $( this ).find( 'img' ).attr( 'data-large_image_width' );
			if( theImgWidth !== '' ){
				// $( '.popup_img' ).css( 'width', theImgWidth );
			}
			$( '.popup_img' ).attr( 'src', theImgSrc );
			$( '.content_img' ).css( 'display', 'inline-block' );
			$( '.popup_cover' ).fadeIn( 100 );
			$( '.popup_close' ).click( function(){
				WoowClose( '.content_img' )
			} );
		} );
		// Pop Up to texts
		$( '.woow_popup.txt' ).click( function( e ){
			e.preventDefault();
			var htmlElement = $( this ).find( '.popup_content' ).html();
			$( '.popup_txt' ).html( htmlElement ).css( 'display', 'block' );
			$( '.popup_txt' ).append( '<span class="popup_close">&times;</span>' );
			$( '.popup_cover' ).fadeIn( 100 );
			$( '.popup_close' ).click( function(){
				WoowClose( '.popup_txt' )
			} );
		} );
		// Pop Up to video
		$( '.woow_popup.video' ).click( function( e ){
			e.preventDefault();
			var theVideo = $( this ).find( 'img' ).attr( 'data-video' );
			
			$( '.popup_video' ).html( '<span class="popup_close">&times;</span>' );
			$( '.popup_video' ).append( $("<iframe></iframe>", {
				src: theVideo,
				css: { 'width': '100%', 'height': '600' },
				frameborder: '0',
				allowfullscreen: 'allowfullscreen',
				mozallowfullscreen: 'mozallowfullscreen',
				msallowfullscreen: 'msallowfullscreen',
				oallowfullscreen: 'oallowfullscreen',
				webkitallowfullscreen: 'webkitallowfullscreen'
			}));
			$( '.popup_video' ).css( 'display', 'block' );
			$( '.popup_cover' ).fadeIn( 100 );
			$( '.popup_close' ).click( function(){
				WoowClose( '.popup_video' )
			} );
		} );
		/* Close cover popUp */
		function WoowClose( theElement ){
			if ( theElement == '.popup_video' ) {
				$( theElement ).empty();
			}
			$( '.popup_cover' ).fadeOut( 100 );
			$( theElement ).css( { 'display' : 'none', 'width' : 'initial' } );
		}

	}

  /*
  ==============================================
  Ponerle placheholder al login
  ==============================================
  */
  if($('.tabs_login_content').length){
    $('#user_login').attr('placeholder', 'Nombre de usuario / Correo electrónico');
    $('#user_pass').attr('placeholder', 'Contraseña');
  }

  /*
  ==============================================
  MEnu responsive
  ==============================================
  */
  $('#menuResponsive').click(()=>{
    $('.header_menu_ul').toggleClass('OpenMenuResponsive');
  });
  $('.header_menu_li_responsive').click(function (){
    $(this).find('.header_menu_sub_ul').toggleClass('mostrar_submenu');
  });


  /*
  ==============================================
  Scripts format number for Slider price
  ==============================================
  */
  var formatNumber = {
    separador: ".", // separador para los miles
    sepDecimal: ",", // separador para los decimales
    formatear: function(num) {
      num += "";
      var splitStr = num.split(".");
      var splitLeft = splitStr[0];
      var splitRight = splitStr.length > 1 ? this.sepDecimal + splitStr[1] : "";
      var regx = /(\d+)(\d{3})/;
      while (regx.test(splitLeft)) {
        splitLeft = splitLeft.replace(regx, "$1" + this.separador + "$2");
      }
      return this.simbol + splitLeft + splitRight;
    },
    new: function(num, simbol) {
      this.simbol = simbol || "";
      return this.formatear(num);
    }
  };


  /*
  ==============================================
  Scripts Slider price tienda
  ==============================================
  */

  if ($("#priceSlider").length) {
    var html5Slider = document.getElementById("priceSlider"); // $( '#' );
    var minValue = $("#amountmin").val();
    var maxValue = $("#amountmax").val();

    noUiSlider.create(html5Slider, {
      start: [minValue, maxValue],
      connect: true,
      step: 1,
      range: {
        min: parseInt(minValue),
        max: parseInt(maxValue)
      }
    });

    html5Slider.noUiSlider.on("update", function(values, handle) {
      var value = values[handle];

      if (handle) {
        // Span value
        $(".amountmax").text(formatNumber.new(Math.round(value), "$"));
        // Input value
        $("#amountmax").val(Math.round(value));
      } else {
        // Span value
        $(".amountmin").text(formatNumber.new(Math.round(value), "$"));
        // Input value
        $("#amountmin").val(Math.round(value));
      }
    });

    html5Slider.noUiSlider.on("end", function(values, handle) {
      var value = values[handle];

      if (handle) {
        var valueMax = Math.round(value);

        LoaderPrice(valueMax, "max");
      } else {
        var valueMin = Math.round(value);

        LoaderPrice(valueMin, "min");
      }
    });
  }

  // Eliminar filtro
  $(document).on("click", ".btnFiltroDelete", function() {
    var nameTax = $(this).attr("data-tax");
    var idTerm = $(this).val();

    //Refresh Values Price
    if (nameTax == "price") {
      var html5Slider = document.getElementById("priceSlider"); // $( '#' );
      html5Slider.noUiSlider.reset();
    }

    // Actializar JSON del filtro
    ManageJsonFilter("delete", nameTax, idTerm, "");
  });

  /*
  ==============================================
  Scripts filtro tienda
  ==============================================
  */
  $(".btnTaxProducto").on("click", function() {
    var nameTax = $(this).attr("data-tax");
    var idTerm = $(this).val();
    var nameTerm = $(this).attr("data-name");
    // Actializar JSON del filtro        
    ManageJsonFilter("add", nameTax, idTerm, nameTerm);

    $(".selectFilter").val("null");
  });

  /*
  ==============================================
  Scripts filtro tienda
  ==============================================
  */
  function ManageJsonFilter(dAction, nameTax, idTerm, nameTerm, order) {
    var jsonFilter = $(".jsonFilter").val();
    // Comprobamos existencia de JSON
    if (jsonFilter != 0) {
      // Traemos JSON guardado en input
      jsonFilter = JSON.parse(jsonFilter);
    } else {
      // Declaramos JSON
      jsonFilter = {
        product_cat: [],
        marca: [],
        price: []
      };
    }
    // Comprobamos accion a ejecutar [add]
    if (dAction == "add") {

      jsonFilter[nameTax] = {
        ID: idTerm,
        name: nameTerm
      };

      //Eliminar filtro precio
      if (nameTax != "price") {
        jsonFilter["price"] = [];

        //Refresh Values Price
        var html5Slider = document.getElementById("priceSlider"); // $( '#' );
        html5Slider.noUiSlider.reset();
      }

      // Eliminar filtro
    } else if (dAction == "delete") {
      jsonFilter[nameTax] = [];
    } else if (dAction == "remove") {
      jsonFilter = {
        product_cat: [],
        marca: [],
        price: []
      };
    }

    // Actualizar filtros
    $(".widget_filters .widget-content").html("");

    $.each(jsonFilter, function(index, val) {
      if (val != 0) {
        var btnFilter = "";

        $(".widget_filters .widget-content").append(
          $("<button>", {
            value: val.ID,
            text: val.name,
            "data-tax": index,
            class: "btnFiltroDelete"
          })
        );
      }
    });

    // Checkamos si no hay filtros
    if ( typeof jsonFilter["product_cat"].ID == "undefined" && typeof jsonFilter["marca"].ID == "undefined") {
      
      if (dAction == "delete" && nameTax != "price") {
        // Declaramos JSON
        jsonFilter = {
          product_cat: [],
          marca: [],
          price: []
        };

        //Refresh Values Price
        var html5Slider = document.getElementById("priceSlider"); // $( '#' );
        html5Slider.noUiSlider.reset();

        //Vaciar Botones
        $(".widget_filters .widget-content").html("Aun no tienes filtros");

        // Reestablecer consulta al eliminar todos filtros
        $(".products_grid").html("");
        product_chane_items("", "");
      } else {
        //chechear si no hay filtro de precio
        if (typeof jsonFilter["price"].ID == "undefined") {
          $(".widget_filters .widget-content").html("Aun no tienes filtros");

          // Reestablecer consulta al eliminar todos filtros
          $(".products_grid").html("");
          product_chane_items("", "");
        } else {
          // Llamada a ajax
          if (order == undefined) {
            productsHtml = TaxFilterTienda(jsonFilter);
          } else {
            productsHtml = TaxFilterTienda(jsonFilter, order);
          }

          $(".products_grid").html("");
        }
      }
    } else {
      // Llamada a ajax
      if (order == undefined) {
        productsHtml = TaxFilterTienda(jsonFilter);
      } else {
        productsHtml = TaxFilterTienda(jsonFilter, order);
      }
      $(".products_grid").html("");
    }
    // Convertimos JSON a String y guardamos
    jsonFilter = JSON.stringify(jsonFilter);
    $(".jsonFilter").val(jsonFilter);
  }

  /*
  ==============================================
  Scripts Loader Price
  ==============================================
  */

  LoaderPrice = function(price, action) {
    if (action == "max") {
      var priceMax = price;
      var priceMin = $("#amountmin").val();
    } else {
      var priceMax = $("#amountmax").val();
      var priceMin = price;
    }

    var jsonFilter = priceMin + "," + priceMax;
    ManageJsonFilter("add", "price", jsonFilter, "Precio");
    $(".selectFilter").val("null");
  };

  // Publicamos la funcion para que sea visible desde afuera
  this.LoaderPrice;
  /*
  ==============================================
  Scripts paginacion
  ==============================================
  */

  if ($('#pagination').length) {

    if (typeof paginationroduct !== "undefined" && paginationroduct) {
      var $pagination = $("#pagination");

      $pagination.twbsPagination({
        totalPages: paginationroduct["mas_page"],
        visiblePages: 3,
        initiateStartPageClick: false,
        first: "Primera",
        prev: '<i class="icon-arrow-left"></i>',
        next: '<i class="icon-arrow-right"></i>',
        last: "Última",
        activeClass: "activePag",
        onPageClick: function(event, page) {
          PrintPageProducts(
            paginationroduct["type"],
            paginationroduct["find"],
            page
          );
        }
      });
    }

  }



});