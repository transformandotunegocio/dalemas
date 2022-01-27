(function( $ ){

    DownloadNews = function () {
        serialiceData = "action=generarExcelNewsletter";
        $.ajax({
                type: "POST"
            ,   url: MyAjax.url
            ,   dataType: "json"
            ,   data: serialiceData
            ,   beforeSend: function(){
                }
            ,   success: function( msn ){
                    if (msn.validate) {
                        var link = document.createElement( 'a' );
                        link.href = msn.link;
                        link.download = "Newsletter.xlsx";
                        link.click();
                    }else{
                        alert( 'No hay Registos' );
                    }
                }
            ,   error: function( msn ){
                    console.log( msn );
                }
            , complete: function(){
                }
        });
    };
    this.DownloadNews;

    DownloadRC = function () {

        serialiceData = "action=generarExcelRegistros";

        $.ajax({
                type: "POST"
            ,   url: MyAjax.url
            ,   dataType: "json"
            ,   data: serialiceData
            ,   beforeSend: function(){
                }
            ,   success: function( msn ){
                    if (msn.validate) {
                        var link = document.createElement( 'a' );
                        link.href = msn.link;
                        link.download = "Registros contacto.xlsx";
                        link.click();
                    }else{
                        alert( 'No hay Registos' );
                    }

                }

            ,   error: function( msn ){
                    console.log( msn );
                }

            , complete: function(){
                }
        });

    };


    this.DownloadRC;


    DownloadRC2 = function () {

        serialiceData = "action=generarExcelNews";

        $.ajax({
                type: "POST"
            ,   url: MyAjax.url
            ,   dataType: "json"
            ,   data: serialiceData
            ,   beforeSend: function(){
                }
            ,   success: function( msn ){
                    if (msn.validate) {
                        var link = document.createElement( 'a' );
                        link.href = msn.link;
                        link.download = "Registros Newsletter.xlsx";
                        link.click();
                    }else{
                        alert( 'No hay Registos' );
                    }

                }

            ,   error: function( msn ){
                    console.log( msn );
                }

            , complete: function(){
                }
        });

    };


    this.DownloadRC2;



    DownloadDIS = function () {

        serialiceData = "action=generarExcelDis";

        $.ajax({
                type: "POST"
            ,   url: MyAjax.url
            ,   dataType: "json"
            ,   data: serialiceData
            ,   beforeSend: function(){
                }
            ,   success: function( msn ){
                    if (msn.validate) {
                        var link = document.createElement( 'a' );
                        link.href = msn.link;
                        link.download = "distribuidores.xlsx";
                        link.click();
                    }else{
                        alert( 'No hay Registos' );
                    }

                }

            ,   error: function( msn ){
                    console.log( msn );
                }

            , complete: function(){
                }
        });

    };


    this.DownloadDIS;

    


    
})( jQuery );