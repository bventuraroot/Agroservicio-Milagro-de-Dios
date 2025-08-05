/**
 *  Form Wizard
 */

"use strict";
$( document ).ready(function() {
    // Obtener todos los parámetros
    const urlParams = new URLSearchParams(window.location.search);
    const typedocument = urlParams.get('typedocument');
    const draftId = $('#draft_id').val(); // Para preventas

    // Si hay un draft_id (preventas), cargar directamente
    if (draftId && draftId !== '') {
        setTimeout(function() {
            loadDraftPreventa(draftId);
        }, 800);
        return;
    }

    // LÓGICA ORIGINAL: Solo crear correlativo para nuevas ventas
    createcorrsale(typedocument);

    var operation = $('#operation').val();
    var valdraft = $('#valdraft').val();
    var valcorr = $('#valcorr').val();
    if (operation == 'delete') {
        var stepper = new Stepper(document.querySelector('.wizard-icons-example'))
        stepper.to(3);
    }else{
        if(valdraft && $.isNumeric(valcorr)){
            var stepper = new Stepper(document.querySelector('.wizard-icons-example'))
            stepper.to(2);
        }
    }
});

// CÓDIGO ORIGINAL PARA CARGAR DRAFT (FUERA DEL DOCUMENT.READY) - SOLO ESTE
var valcorrdoc = $("#valcorr").val();
var valdraftdoc = $("#valdraft").val();
if (valcorrdoc != "" && valdraftdoc == "true") {
    var draft = draftdocument(valcorrdoc, valdraftdoc);
}

$(function () {
    const select2 = $(".select2"),
        selectPicker = $(".selectpicker");

    // Bootstrap select
    if (selectPicker.length) {
        selectPicker.selectpicker();
    }

    // select2
    if (select2.length) {
        select2.each(function () {
            var $this = $(this);
            $this.wrap('<div class="position-relative"></div>');
            $this.select2({
                placeholder: "Select value",
                dropdownParent: $this.parent(),
            });
        });
    }
    //Get companies avaibles
    var iduser = $("#iduser").val();
    $.ajax({
        url: "/company/getCompanybyuser/" + iduser,
        method: "GET",
        success: function (response) {
            $("#company").append('<option value="0">Seleccione</option>');
            $.each(response, function (index, value) {
                $("#company").append(
                    '<option value="' +
                        value.id +
                        '">' +
                        value.name.toUpperCase() +
                        "</option>"
                );
            });
        },
    });

    //Get products avaibles
    $.ajax({
        url: "/product/getproductall",
        method: "GET",
        success: function (response) {
            $("#psearch").append('<option value="0">Seleccione</option>');
            $.each(response, function (index, value) {
                $("#psearch").append(
                    '<option value="' +
                        value.id +
                        '" title="'+ value.image +'">' +
                        value.name.toUpperCase() + "| Descripción: " + value.description + "| Proveedor: " + value.nameprovider +
                        "</option>"
                );
            });
        },
    });

    var selectdcompany = $(".select2company");

    if (selectdcompany.length) {
        var $this = selectdcompany;
        $this.wrap('<div class="position-relative"></div>').select2({
            placeholder: "Seleccionar empresa",
            dropdownParent: $this.parent(),
        });
    }

    var selectddestino = $(".select2destino");

    if (selectddestino.length) {
        var $this = selectddestino;
        $this.wrap('<div class="position-relative"></div>').select2({
            placeholder: "Seleccionar destino",
            dropdownParent: $this.parent(),
        });
    }

    var selectdcanal = $(".select2canal");

    if (selectdcanal.length) {
        var $this = selectdcanal;
        $this.wrap('<div class="position-relative"></div>').select2({
            placeholder: "Seleccionar destino",
            dropdownParent: $this.parent(),
        });
    }

    var selectdlinea = $(".select2linea");

    if (selectdlinea.length) {
        var $this = selectdlinea;
        $this.wrap('<div class="position-relative"></div>').select2({
            placeholder: "Seleccionar linea aerea",
            dropdownParent: $this.parent(),
        });
    }

    var selectdclient = $(".select2client");

    if (selectdclient.length) {
        var $this = selectdclient;
        $this.wrap('<div class="position-relative"></div>').select2({
            placeholder: "Seleccionar cliente",
            dropdownParent: $this.parent(),
        });
    }

    function formatState(state) {
        //console.log(state);
        if (state.id==0) {
          return state.text;
        }
        // Verificar que state.title existe y no es undefined
        var imageSrc = state.title && state.title !== 'undefined' ? state.title : 'default.png';
        var $state = $(
          '<span><img src="../assets/img/products/'+ imageSrc +'" class="imagen-producto-select2" /> ' + state.text + '</span>'
        );
        return $state;
      };
    var selectdpsearch = $(".select2psearch");

    if (selectdpsearch.length) {
        var $this = selectdpsearch;
        $this.wrap('<div class="position-relative"></div>').select2({
            placeholder: "Seleccionar Producto",
            dropdownParent: $this.parent(),
            templateResult: formatState
        });
    }
        //Get destinos
        $.ajax({
            url: "/sale/destinos",
            method: "GET",
            success: function (response) {
                $("#destino").append('<option value="0">Seleccione</option>');
                $.each(response, function (index, value) {
                    //console.log(value.iata);
                    $("#destino").append(
                        '<option value="' +
                            value.id_aeropuerto +'">'+ value.iata + '-' +value.ciudad + '-' + value.pais + '-' + value.continente +
                            "</option>"
                    );
                });
            },
        });

        //Get linea aerea
        $.ajax({
            url: "/sale/linea",
            method: "GET",
            success: function (response) {
                $("#linea").append('<option value="0">Seleccione</option>');
                $.each(response, function (index, value) {
                    //console.log(value.iata);
                    $("#linea").append(
                        '<option value="' +
                            value.id_aerolinea +'">'+ value.iata + '-' + value.nombre +
                            "</option>"
                    );

                });
            },
        });
});

function agregarp() {

    // Verificar que hay correlativo antes de continuar
    var corrid = $("#corr").val();
    if (!corrid || corrid == "" || corrid == "null") {
        Swal.fire("Error", "No hay correlativo disponible. Por favor recarga la página.", "error");
        return;
    }

    // Verificar que hay cliente seleccionado
    var clientid = $("#client").val();
    if (!clientid || clientid == "0" || clientid == "null") {
        Swal.fire("Error", "Debe seleccionar un cliente antes de agregar productos.", "error");
        return;
    }

    // Verificar que hay producto seleccionado
    var productid = $("#productid").val();
    if (!productid || productid == "" || productid == "null") {
        Swal.fire("Error", "Debe seleccionar un producto.", "error");
        return;
    }
    //alert(productid);
    var reserva = $('#reserva').val();
    var ruta = $('#ruta').val();
    var destino = $('#destino').val();
    var linea = $('#linea').val();
    var canal = $('#Canal').val();
    var fee = parseFloat($("#fee").val()) || 0.00;
    var fee2 = parseFloat($("#fee2").val()) || 0.00;

    // Validar si el producto es 9 y los campos son obligatorios
    if (productid == 9) {
        if (!reserva || !ruta || !destino || !linea || !canal) {
            swal.fire("Favor complete la información del producto");
            return;
        }
    } else {
        // Si el producto no es 9, enviar valores vacíos
        reserva = "null";
        ruta = "null";
        destino = "0";
        linea = "0";
        canal = "null";
    }
    var typedoc = $('#typedocument').val();
    var clientid = $("#client").val();
    var corrid = $("#corr").val();
    var acuenta = ($("#acuenta").val()==""?'SIN VALOR DEFINIDO':$("#acuenta").val());
    var fpago = $("#fpago").val();
    var productname = $("#productname").val();
    var marca = $("#marca").val();
    var price = parseFloat($("#precio").val());
    var ivarete13 = parseFloat($("#ivarete13").val());
    var rentarete = parseFloat($("#rentarete").val())||0.00;
    var ivarete = parseFloat($("#ivarete").val());
    var type = $("#typesale").val();
    var cantidad = parseFloat($("#cantidad").val());
    var productdescription = $("#productdescription").val();
    var pricegravada = 0;
    var priceexenta = 0;
    var pricenosujeta = 0;
    var sumas = parseFloat($("#sumas").val());
    var iva13 = parseFloat($("#13iva").val());
    var rentarete10 = parseFloat($("#rentaretenido").val());
    var ivaretenido = parseFloat($("#ivaretenido").val());
    var ventasnosujetas = parseFloat($("#ventasnosujetas").val());
    var ventasexentas = parseFloat($("#ventasexentas").val());
    var ventatotal = parseFloat($("#ventatotal").val());
    var descriptionbyproduct;
    //ventatotal = parseFloat(ventatotal/1.13).toFixed(2);
    var sumasl = 0;
    var ivaretenidol = 0;
    var iva13l = 0;
    var renta10l = 0;
    var ventasnosujetasl = 0;
    var ventasexentasl = 0;
    var ventatotall = 0;
    var iva13temp = 0;
    var renta10temp = 0;
    var totaltempgravado = 0;
    var priceunitariofee = 0;
    if (type == "gravada") {
        pricegravada = parseFloat((price * cantidad)+fee);
        totaltempgravado = parseFloat(pricegravada);
        if(typedoc==6 || typedoc==8){
            iva13temp = 0.00;
        }else if(typedoc==3){
            iva13temp = parseFloat(pricegravada * 0.13).toFixed(2);
        }

        //iva13temp = parseFloat(ivarete13 * cantidad).toFixed(2);
    } else if (type == "exenta") {
        priceexenta = parseFloat(price * cantidad);
        iva13temp = 0;
    } else if (type == "nosujeta") {
        pricenosujeta = parseFloat(price * cantidad);
        iva13temp = 0;
    }
    if(typedoc=='8'){
        iva13temp = 0.00;
    }
    if(!$.isNumeric(ivarete)){
        ivarete = 0.00;
    }
    renta10temp = parseFloat(rentarete*cantidad).toFixed(2);
    var totaltemp = parseFloat(parseFloat(pricegravada) + parseFloat(priceexenta) + parseFloat(pricenosujeta));
    var ventatotaltotal =  parseFloat(ventatotal); //+ parseFloat(iva13) + parseFloat(ivaretenido);
    priceunitariofee = price + (fee/cantidad);
    var totaltemptotal = parseFloat(
    ($.isNumeric(pricegravada)? pricegravada: 0) +
    ($.isNumeric(priceexenta)? priceexenta: 0) +
    ($.isNumeric(pricenosujeta)? pricenosujeta: 0) +
    ($.isNumeric(iva13temp)? parseFloat(iva13temp): 0) -
    ($.isNumeric(renta10temp)? parseFloat(renta10temp): 0) -
    ($.isNumeric(ivarete)? ivarete: 0));

    //descripcion factura
    //if(productid==10){
    descriptionbyproduct = productname + " " + marca;
    //}else {savefactemp
        //descriptionbyproduct =  productname + " " + reserva + " " + ruta;
    //}

    //enviar a temp factura
    //console.log("Enviando datos a savefactemp:");
    //console.log("corrid:", corrid);
    //console.log("clientid:", clientid);
    //console.log("productid:", productid);

    $.ajax({
        url:
            "savefactemp/" +
            corrid +
            "/" +
            clientid +
            "/" +
            productid +
            "/" +
            cantidad +
            "/" +
            price +
            "/" +
            pricenosujeta +
            "/" +
            priceexenta +
            "/" +
            pricegravada +
            "/" +
            ivarete13 +
            "/" +
            rentarete +
            "/" +
            ivarete +
            "/" +
            acuenta +
            "/" +
            fpago +
            "/" +
            fee +
            "/" +
            reserva +
            "/" +
            ruta +
            "/" +
            destino +
            "/" +
            linea +
            "/" +
            canal,
        method: "GET",
        success: function (response) {
            //console.log("Respuesta de savefactemp:", response);
            if (response.res == 1) {
                var row =
                    '<tr id="pro' +
                    response.idsaledetail +
                    '"><td>' +
                    cantidad +
                    "</td><td>" +
                    descriptionbyproduct +
                    "</td><td>" +
                    priceunitariofee.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    "</td><td>" +
                    pricenosujeta.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    "</td><td>" +
                    priceexenta.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    "</td><td>" +
                    pricegravada.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    '</td><td class="text-center">' +
                    totaltemp.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    '</td><td class="quitar_documents"><button class="btn rounded-pill btn-icon btn-danger" type="button" onclick="eliminarpro(' +
                    response.idsaledetail +
                    ')"><span class="ti ti-trash"></span></button></td></tr>';
                $("#tblproduct tbody").append(row);
                sumasl = sumas + totaltemp;
                $("#sumasl").html(
                    sumasl.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#sumas").val(sumasl);
                if(typedoc==6 || typedoc==8){
                    iva13l=0.00;
                }else if(typedoc==3){
                    //calculo de iva 13%
                    iva13l = parseFloat(parseFloat(iva13) + parseFloat(iva13temp));
                }
                $("#13ival").html(
                    iva13l.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#13iva").val(iva13l);

                if($("#typedocument").val() == '8'){
                    //calculo de retenido 10%
                renta10l = parseFloat(parseFloat(renta10temp) + parseFloat(rentarete10));
                $("#10rental").html(
                    renta10l.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#rentaretenido").val(renta10l);
                }
                //calculo del retenido 1%
                ivaretenidol = ivaretenido + ivarete;
                $("#ivaretenidol").html(
                    ivaretenidol.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#ivaretenido").val(ivaretenidol);
                ventasnosujetasl = ventasnosujetas + pricenosujeta;
                $("#ventasnosujetasl").html(
                    ventasnosujetasl.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#ventasnosujetas").val(ventasnosujetasl);
                ventasexentasl = ventasexentas + priceexenta;
                $("#ventasexentasl").html(
                    ventasexentasl.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#ventasexentas").val(ventasexentasl);

                ventatotall = parseFloat(ventatotaltotal)  + parseFloat(totaltemptotal);
                $("#ventatotall").html(
                    parseFloat(ventatotall).toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $('#ventatotallhidden').val(ventatotall);
                $("#ventatotal").val(ventatotall);
            } else if (response == 0) {
                //console.error("Error en savefactemp: response = 0");
                Swal.fire("Error", "No se pudo agregar el producto", "error");
            }
        },
        error: function(xhr, status, error) {
            //console.error("Error en savefactemp:", error);
            //console.error("Status:", status);
            //console.error("Response:", xhr.responseText);

            try {
                var errorResponse = JSON.parse(xhr.responseText);
                Swal.fire("Error", errorResponse.message || "Error al agregar producto", "error");
            } catch(e) {
                Swal.fire("Error", "Error al agregar producto: " + error, "error");
            }
        }
    });
    $('#precio').val(0.00);
    $('#fee').val(0.00);
    $('#ivarete13').val(0.00);
    $('#ivarete').val(0.00);
    $('#rentarete').val(0.00);
    $('#reserva').val();
    $('#ruta').val();
    $('#destino').val(null).trigger('change');
    $('#linea').val(null).trigger('change');
    $('#canal').val(null).trigger('change');
    $("#psearch").val("0").trigger("change.select2");
}

function totalamount() {
    var typecontricompany = $("#typecontribuyente").val();
    var typecontriclient = $("#typecontribuyenteclient").val();
    var typedoc = $('#typedocument').val();

    // Convertir valores a números asegurando que no sean NaN
    var cantidad = parseFloat($("#cantidad").val()) || 0.00;
    var fee = parseFloat($("#fee").val()) || 0.00;
    //var fee2 = parseFloat($("#fee2").val()) || 0.00;
    var iva = parseFloat($("#iva").val()) || 0.00;
    var valor = parseFloat($('#precio').val()) || 0.00;

    var ivarete13 = 0.00;
    var retencionamount = 0.00;
    var renta = 0.00;
    var totalamount = 0.00;
    var totaamountsinivi = 0.00;
    var totalvalor = 0.00;
    var totalfee = 0.00;
    let retencion = 0.00;

    // Evaluar la retención de IVA según el tipo de contribuyente
    if (typecontricompany === "GRA") { // Empresa grande
        if (typecontriclient === "GRA") {
            retencion = 0.01; // 1% de retención cuando ambas son grandes
        } else if (["MED", "PEQ", "OTR"].includes(typecontriclient)) {
            retencion = 0.01; // 1% cuando empresa grande paga a mediana, pequeña u otro
        }
    } else if (["MED", "PEQ", "OTR"].includes(typecontricompany)) { // Empresa no es grande
        retencion = 0.00; // No retiene IVA
    }

    totalamount = parseFloat(valor * cantidad);
    totaamountsinivi = parseFloat(totalamount/1.13);

    // Cálculo de IVA retenido
    if (typedoc === '6' || typedoc === '8') {
        $("#ivarete13").val(0);
        ivarete13 = 0.00;
    } else {
        totalvalor = parseFloat(valor * iva);
        totalfee = parseFloat(fee * iva);
        ivarete13 = parseFloat(totalvalor + totalfee);
        $("#ivarete13").val(ivarete13.toFixed(2));
    }

    // Cálculo de retención
    retencionamount = parseFloat(valor * retencion);
    $("#ivarete").val(retencionamount.toFixed(2));

    // Cálculo de renta retenida
    if (typedoc === '8') {
        renta = parseFloat(valor * 0.10);
        $("#rentarete").val(renta.toFixed(2));
    } else {
        renta = 0.00;
    }

    // Depuración: Verificar tipos de datos
    //console.log("fee:", typeof fee, fee);
    //console.log("fee2:", typeof fee2, fee2);
    //console.log("iva:", typeof iva, iva);
    //console.log("ivarete13:", typeof ivarete13, ivarete13);
    //console.log("retencionamount:", typeof retencionamount, retencionamount);
    //console.log("renta:", typeof renta, renta);

    // Cálculo del total asegurando que todo es número


    var totalFinal = totalamount + fee + ivarete13 + retencionamount + renta;

    $("#total").val(totalFinal.toFixed(2)); // Aplicar `.toFixed(2)` solo después de la suma final

    // Actualizar resumen si estamos en la sección de revisión
    if ($("#review-submit").hasClass("active")) {
        setTimeout(updateResumenData, 100); // Pequeño delay para asegurar que se actualicen los totales primero
    }
}


function searchproduct(idpro) {
    if(idpro==9){
        $("#add-information-tickets").css("display", "");
    }else{
        $("#add-information-tickets").css("display", "none");
    }
    //Get products by id avaibles
    var typedoc = $('#typedocument').val();
    var typecontricompany = $("#typecontribuyente").val();
    var typecontriclient = $("#typecontribuyenteclient").val();
    var iva = parseFloat($("#iva").val());
    var iva_entre = parseFloat($("#iva_entre").val()) || 1.13; // Valor por defecto si no está definido
    //var typecontriclient = $("#typecontribuyenteclient").val();
    var retencion=0.00;
    var pricevalue;
    $.ajax({
        url: "/product/getproductid/" + btoa(idpro),
        method: "GET",
        success: function (response) {
            $.each(response, function (index, value) {
                console.log('Producto recibido:', value);
                console.log('Tipo de documento:', typedoc);
                console.log('IVA entre:', iva_entre);
                console.log('Precio original:', value.price);

                if(typedoc=='6' || typedoc=='8'){
                    pricevalue = parseFloat(value.price);
                }else{
                    pricevalue = parseFloat(value.price/iva_entre);
                }

                console.log('Precio calculado:', pricevalue);
                $("#precio").val(pricevalue.toFixed(2));
                $("#productname").val(value.productname);
                $("#marca").val(value.marcaname);
                $("#productid").val(value.id);
                $("#productdescription").val(value.description);
                $("#productunitario").val(value.id);
                //validar si es gran contribuyente el cliente vs la empresa

                if (typecontricompany == "GRA") {
                    if (typecontriclient == "GRA") {
                        retencion = 0.01;
                    } else if (
                        typecontriclient == "MED" ||
                        typecontriclient == "PEQ" ||
                        typecontriclient == "OTR"
                    ) {
                        retencion = 0.00;
                    }
                }
                if(typecontriclient==""){
                    retencion = 0.0;
                }
                if(typedoc=='6' || typedoc=='8'){
                    $("#ivarete13").val(0);
                }else{
                    $("#ivarete13").val(parseFloat(pricevalue.toFixed(2) * iva).toFixed(2));
                }
                $("#ivarete").val(
                    parseFloat(pricevalue.toFixed(2) * retencion).toFixed(2)
                );
                if(typedoc=='8'){
                    $("#rentarete").val(
                        parseFloat(pricevalue.toFixed(2) * 0.10).toFixed(2)
                    );
                }
            });
            var updateamounts = totalamount();
        },
    });
}

function searchproductcode(codeproduct) {
    //Get products by id avaibles
    var typedoc = $('#typedocument').val();
    var typecontricompany = $("#typecontribuyente").val();
    var typecontriclient = $("#typecontribuyenteclient").val();
    var iva = parseFloat($("#iva").val());
    var iva_entre = parseFloat($("#iva_entre").val()) || 1.13; // Valor por defecto si no está definido
    //var typecontriclient = $("#typecontribuyenteclient").val();
    var retencion=0.00;
    var pricevalue;
    $.ajax({
        url: "/product/getproductcode/" + btoa(codeproduct),
        method: "GET",
        success: function (response) {
            $.each(response, function (index, value) {
                console.log('Producto por código recibido:', value);
                console.log('Tipo de documento:', typedoc);
                console.log('IVA entre:', iva_entre);
                console.log('Precio original:', value.price);

                if(typedoc=='6' || typedoc=='8'){
                    pricevalue = parseFloat(value.price);
                }else{
                    pricevalue = parseFloat(value.price/iva_entre);
                }

                console.log('Precio calculado por código:', pricevalue);
                $("#psearch").val(value.id).trigger("change.select2");
                $("#codesearch").val(value.code);
                $("#precio").val(pricevalue.toFixed(2));
                $("#productname").val(value.productname);
                $("#marca").val(value.marcaname);
                $("#productid").val(value.id);
                $("#productdescription").val(value.description);
                $("#productunitario").val(value.id);
                $("#add-information-products").css("display", "");
                $("#product-image").attr("src", '../assets/img/products/' + value.image);
                $("#product-name").html(value.productname);
                $("#product-marca").html(value.marcaname);
                $("#product-provider").html(value.provider);
                $("#product-price").html(pricevalue.toFixed(2));
                //validar si es gran contribuyente el cliente vs la empresa

                if (typecontricompany == "GRA") {
                    if (typecontriclient == "GRA") {
                        retencion = 0.01;
                    } else if (
                        typecontriclient == "MED" ||
                        typecontriclient == "PEQ" ||
                        typecontriclient == "OTR"
                    ) {
                        retencion = 0.00;
                    }
                }
                if(typecontriclient==""){
                    retencion = 0.0;
                }
                if(typedoc=='6' || typedoc=='8'){
                    $("#ivarete13").val(0);
                }else{
                    $("#ivarete13").val(parseFloat(pricevalue.toFixed(2) * iva).toFixed(2));
                }
                $("#ivarete").val(
                    parseFloat(pricevalue.toFixed(2) * retencion).toFixed(2)
                );
                if(typedoc=='8'){
                    $("#rentarete").val(
                        parseFloat(pricevalue.toFixed(2) * 0.10).toFixed(2)
                    );
                }
            });
            var updateamounts = totalamount();
        },
    });
}

function changetypesale(type){
    var price = $("#precio").val();
    var typedoc = $('#typedocument').val();
    var iva = parseFloat($("#iva").val());
switch(type){
    case 'gravada':
        if(typedoc=='6' || typedoc=='8'){
            $('#ivarete13').val(parseFloat(0));
        }else{
            $('#ivarete13').val(parseFloat(price*iva).toFixed(2));
        }

        if(typedoc=='8'){
            $('#rentarete').val(parseFloat(price*0.10).toFixed(2));
        }

        break;
    case 'exenta':
        $('#ivarete13').val(0.00);
        $('#ivarete').val(0.00);
        $('#rentarete').val(0.00);
        break;
    case 'nosujeta':
        $('#ivarete13').val(0.00);
        break;
}
}

function eliminarpro(id) {
    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
            confirmButton: "btn btn-success",
            cancelButton: "btn btn-danger",
        },
        buttonsStyling: false,
    });

    swalWithBootstrapButtons
        .fire({
            title: "¿Eliminar?",
            text: "Esta accion no tiene retorno",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Si, Eliminarlo!",
            cancelButtonText: "No, Cancelar!",
            reverseButtons: true,
        })
        .then((result) => {
            if (result.isConfirmed) {
                var corr = $('#valcorr').val();
                var document = $('#typedocument').val();
                $.ajax({
                    url: "destroysaledetail/" + btoa(id),
                    method: "GET",
                    async: false,
                    success: function (response) {
                        if (response.res == 1) {
                            Swal.fire({
                                title: "Eliminado",
                                icon: "success",
                                confirmButtonText: "Ok",
                            }).then((result) => {
                                /* Read more about isConfirmed, isDenied below */
                                if (result.isConfirmed) {
                                    //$("#pro" + id).remove();
                                    //$('#resultados').load(location.href + " #resultados");
                                    //var details = agregarfacdetails($('#valcorr').val());
                                    //location.reload(true);
                                    window.location.href =
                                    "create?corr=" + corr + "&draft=true&typedocument=" + document +"&operation=delete";
                                }
                            });
                        } else if (response.res == 0) {
                            swalWithBootstrapButtons.fire(
                                "Problemas!",
                                "Algo sucedio y no pudo eliminar el cliente, favor comunicarse con el administrador.",
                                "success"
                            );
                        }
                    },
                });
            } else if (
                /* Read more about handling dismissals below */
                result.dismiss === Swal.DismissReason.cancel
            ) {
                swalWithBootstrapButtons.fire(
                    "Cancelado",
                    "No hemos hecho ninguna accion :)",
                    "error"
                );
            }
        });
}

function aviablenext(idcompany) {
    $("#step1").prop("disabled", false);
}

function getclientbycompanyurl(idcompany) {
    console.log("Cargando clientes para empresa:", idcompany);
    $.ajax({
        url: "/client/getclientbycompany/" + btoa(idcompany),
        method: "GET",
        success: function (response) {
            console.log("Respuesta de clientes:", response);

            // Limpiar opciones existentes antes de agregar nuevas
            $("#client").empty();
            $("#client").append('<option value="0">Seleccione</option>');

            $.each(response, function (index, value) {
                console.log("Cliente encontrado:", value);
                if(value.tpersona=='J'){
                    $("#client").append(
                        '<option value="' +
                            value.id +
                            '">' +
                            (value.name_contribuyente ? value.name_contribuyente.toUpperCase() : 'SIN NOMBRE') +
                            "</option>"
                    );
                }else if (value.tpersona=='N'){
                    $("#client").append(
                        '<option value="' +
                            value.id +
                            '">' +
                            (value.firstname ? value.firstname.toUpperCase() : 'SIN NOMBRE') +
                            " " +
                            (value.firstlastname ? value.firstlastname.toUpperCase() : '') +
                            "</option>"
                    );
                }
            });
        },
        error: function(xhr, status, error) {
            console.error("Error cargando clientes:", error);
            console.error("Response:", xhr.responseText);
            $("#client").empty();
            $("#client").append('<option value="0">Error cargando clientes</option>');
        }
    });

    //traer el tipo de contribuyente
    $.ajax({
        url: "/company/gettypecontri/" + btoa(idcompany),
        method: "GET",
        success: function (response) {
            $("#typecontribuyente").val(response.tipoContribuyente);
        },
    });
}

function valtrypecontri(idcliente) {
    //traer el tipo de contribuyente
    $.ajax({
        url: "/client/gettypecontri/" + btoa(idcliente),
        method: "GET",
        success: function (response) {
            $("#typecontribuyenteclient").val(response.tipoContribuyente);
        },
    });

    // Guardar el cliente en la venta inmediatamente
    var corrValue = $("#corr").val();
    if (corrValue && corrValue !== '' && idcliente && idcliente !== '0') {
        $.ajax({
            url: "/sale/updateclient/" + corrValue + "/" + idcliente,
            method: "GET",
            success: function (response) {
                if (response.success) {
                    console.log("Cliente actualizado correctamente en la venta");
                } else {
                    console.error("Error al actualizar cliente:", response.error);
                }
            },
            error: function (error) {
                console.error("Error al comunicarse con el servidor:", error);
            }
        });
    }
}
function createcorrsale(typedocument="") {
    //crear correlativo temp de factura
    let salida = false;
    var valicorr = $("#valcorr").val();
    if (valicorr == "") {
        // Solo crear nuevo correlativo si NO hay valcorr (nueva venta)
        $.ajax({
            url: "newcorrsale/" +  typedocument,
            method: "GET",
            async: false,
            success: function (response) {
                if ($.isNumeric(response.sale_id)) {
                    //recargar la pagina para retomar si una factura quedo en modo borrador
                    window.location.href =
                        "create?corr=" + response.sale_id + "&draft=true&typedocument=" + typedocument;
                } else {
                    Swal.fire("Hay un problema, favor verificar"+response);
                }
            },
        });
    } else {
        // Ya hay un valcorr (retomar venta), no crear nuevo correlativo
        salida = true;
    }

    return salida;
}

function valfpago(fpago) {
    //alert(fpago);
}

function draftdocument(corr, draft) {
    //console.log("🔄 draftdocument llamada - corr:", corr, "draft:", draft);
    if (draft) {
        $.ajax({
            url: "getdatadocbycorr/" + btoa(corr),
            method: "GET",
            async: false,
            success: function (response) {
                //console.log("✅ Draft data obtenido del servidor:", response);
                $.each(response, function (index, value) {
                    //campo de company
                    $('#company').empty();
                    $("#company").append(
                        '<option value="' +
                            value.id +
                            '">' +
                            value.name.toUpperCase() +
                            "</option>"
                    );
                    $("#step1").prop("disabled", false);
                    $('#company').prop('disabled', true);
                    $('#corr').prop('disabled', true);
                    $("#typedocument").val(value.typedocument_id);
                    $("#typecontribuyente").val(value.tipoContribuyente);
                    $("#iva").val(value.iva);
                    $("#iva_entre").val(value.iva_entre);
                    $("#typecontribuyenteclient").val(value.client_contribuyente);
                    $('#date').prop('disabled', true);
                    //console.log(corr);
                    $("#corr").val(corr);
                    // Formatear la fecha correctamente para el input type="date"
                    var dateValue = value.date;
                    if (dateValue && dateValue !== null && dateValue !== '') {
                        // Si la fecha viene con hora, extraer solo la parte de la fecha
                        var formattedDate = dateValue.split(' ')[0];
                        // Verificar que esté en formato Y-m-d
                        if (formattedDate.match(/^\d{4}-\d{2}-\d{2}$/)) {
                            $("#date").val(formattedDate);
                        } else {
                            // Si no está en el formato correcto, usar fecha actual
                            $("#date").val(new Date().toISOString().split('T')[0]);
                        }
                    } else {
                        // Si la fecha es null o vacía, usar fecha actual
                        $("#date").val(new Date().toISOString().split('T')[0]);
                    }
                    //campo cliente
                    $("#client").empty(); // Limpiar clientes existentes
                    if(value.client_id != null && value.client_firstname!='N/A'){
                        $("#client").append(
                            '<option value="' +
                                value.client_id +
                                '">' +
                                value.client_firstname +' '+ value.client_secondname +
                                "</option>"
                        );
                        $('#client').prop('disabled', true);
                        //console.log("✅ Cliente cargado:", value.client_firstname);
                    }else if(value.client_firstname=='N/A') {
                        $("#client").append(
                            '<option value="' +
                                value.client_id +
                                '">' +
                                value.comercial_name +
                                "</option>"
                        );
                        $('#client').prop('disabled', true);
                        //console.log("✅ Cliente comercial cargado:", value.comercial_name);
                    }else{
                        //console.log("🔄 Cargando lista de clientes para la empresa");
                        var getsclient =  getclientbycompanyurl(value.id);
                    }
                    if(value.waytopay != null){
                        $("#fpago option[value="+ value.waytopay +"]").attr("selected",true);
                    }
                    $("#acuenta").val(value.acuenta);
                    // CARGAR LOS PRODUCTOS - ESTO ES LO CLAVE QUE FALTABA
                    //console.log("🛒 Cargando productos con agregarfacdetails...");
                    var details = agregarfacdetails(corr);
                });
            },
            failure: function (response) {
                Swal.fire("Hay un problema: " + response.responseText);
            },
            error: function (response) {
                Swal.fire("Hay un problema: " + response.responseText);
            },
        });
    }
}

function loadDraftPreventa(draftId) {
    $.ajax({
        url: "get-draft-preventa/" + draftId,
        method: "GET",
        async: false,
        success: function (response) {
            //console.log('Draft preventa cargado:', response);
            $.each(response, function (index, value) {
                // Configurar campos de empresa
                $('#company').empty();
                $("#company").append(
                    '<option value="' +
                        value.id +
                        '">' +
                        value.name.toUpperCase() +
                        "</option>"
                );
                $("#step1").prop("disabled", false);
                $('#company').prop('disabled', true);

                // Configurar tipos de documento e IVA
                $("#typedocument").val(value.typedocument_id);
                $("#typecontribuyente").val(value.tipoContribuyente);
                $("#iva").val(value.iva);
                $("#iva_entre").val(value.iva_entre);
                $("#typecontribuyenteclient").val(value.client_contribuyente);

                // Auto-seleccionar el tipo de documento en la interfaz
                const documentCard = document.querySelector(`[data-type="${value.typedocument_id}"]`);
                if (documentCard) {
                    document.querySelectorAll('.document-type-card').forEach(card => {
                        card.classList.remove('selected');
                    });
                    documentCard.classList.add('selected');
                    document.getElementById('step-document-type').disabled = false;
                }

                // Configurar fecha y correlativo (usar el ID del draft como correlativo)
                $('#date').prop('disabled', true);
                $("#corr").val(value.id); // Usar el ID del draft como correlativo
                // Formatear la fecha correctamente para el input type="date"
                var dateValue = value.date;
                if (dateValue && dateValue !== null && dateValue !== '') {
                    // Si la fecha viene con hora, extraer solo la parte de la fecha
                    var formattedDate = dateValue.split(' ')[0];
                    // Verificar que esté en formato Y-m-d
                    if (formattedDate.match(/^\d{4}-\d{2}-\d{2}$/)) {
                        $("#date").val(formattedDate);
                    } else {
                        // Si no está en el formato correcto, usar fecha actual
                        $("#date").val(new Date().toISOString().split('T')[0]);
                    }
                } else {
                    // Si la fecha es null o vacía, usar fecha actual
                    $("#date").val(new Date().toISOString().split('T')[0]);
                }

                // Configurar cliente
                if(value.client_id != null && value.client_firstname != null && value.client_firstname != 'N/A'){
                    $("#client").empty();
                    $("#client").append(
                        '<option value="' +
                            value.client_id +
                            '">' +
                            value.client_firstname + ' ' + (value.client_secondname || '') +
                            "</option>"
                    );
                    $('#client').prop('disabled', true);
                } else if(value.client_id != null && value.comercial_name != null) {
                    $("#client").empty();
                    $("#client").append(
                        '<option value="' +
                            value.client_id +
                            '">' +
                            value.comercial_name +
                            "</option>"
                    );
                    $('#client').prop('disabled', true);
                } else {
                    // Cargar clientes disponibles para la empresa
                    getclientbycompanyurl(value.id);
                }

                // Configurar forma de pago
                if(value.waytopay != null){
                    $("#fpago option[value="+ value.waytopay +"]").attr("selected", true);
                }

                // Configurar campo a cuenta
                $("#acuenta").val(value.acuenta);

                // Cargar detalles de productos del draft
                agregarfacdetails(value.id);

                // Auto-avanzar al paso de información de factura cuando el stepper esté disponible
                var checkStepper = setInterval(function() {
                    if (window.stepper) {
                        clearInterval(checkStepper);
                        // Si hay empresa auto-seleccionada, ir al paso 2, sino al paso 3
                        if (window.hasAutoSelectedCompany) {
                            window.stepper.to(2); // Saltar tipo doc y empresa
                        } else {
                            window.stepper.to(3); // Saltar tipo doc y ir al paso empresa
                        }
                    }
                }, 100);
            });
        },
        failure: function (response) {
            Swal.fire("Error", "Hay un problema cargando el draft de preventa: " + response.responseText, "error");
        },
        error: function (response) {
            console.error("Error cargando draft preventa:", response);
            Swal.fire("Error", "No se pudo cargar el draft de preventa", "error");
        },
    });
}

function CheckNullUndefined(value) {
    return typeof value == 'string' && !value.trim() || typeof value == 'undefined' || value === null;
  }

function getinfodoc(){
    var corr = $('#valcorr').val();
    let salida = false;
    $.ajax({
        url: "getdatadocbycorr2/" + btoa(corr),
        method: "GET",
        async: false,
        success: function (response) {
            salida = true;
            //console.log(response);
            $('#logodocfinal').attr('src', '../assets/img/logo/' + response[0].logo);
            $('#addressdcfinal').empty();
            $('#addressdcfinal').html('' + response[0].country_name.toUpperCase() + ', ' + response[0].department_name + ', ' + response[0].municipality_name + '</br>' + response[0].address);
            $('#phonedocfinal').empty();
            $('#phonedocfinal').html('' + ((CheckNullUndefined(response[0].phone_fijo)==true) ? '' : 'PBX: +503 ' + response[0].phone_fijo) + ' ' + ((CheckNullUndefined(response[0].phone)==true) ? '' : 'Móvil: +503 ' + response[0].phone));
            $('#emaildocfinal').empty();
            $('#emaildocfinal').html(response[0].email);
            $('#name_client').empty();
            if(response[0].tpersona == 'J'){
                $('#name_client').html(response[0].name_contribuyente);
            }else if (response[0].tpersona == 'N'){
                $('#name_client').html(response[0].client_firstname + ' ' + response[0].client_secondname);
            }
            $('#date_doc').empty();
            var dateformat = response[0].date.split('-');
            $('#date_doc').html(dateformat[2] + '/' + dateformat[1] + '/' + dateformat[0]);
            $('#address_doc').empty();
            $('#address_doc').html(response[0].address);
            $('#duinit').empty();
            $('#duinit').html(response[0].nit);
            $('#municipio_name').empty();
            $('#municipio_name').html(response[0].municipality_name);
            $('#giro_name').empty();
            $('#giro_name').html(response[0].giro);
            $('#name_type_documents_details').empty();
            $('#name_type_documents_details').html(response[0].document_name);
            $('#corr_details').empty();
            $('#corr_details').html('USD' + response[0].corr + '00000');
            $('#NCR_details').empty();
            $('#NCR_details').html('NCR: ' + response[0].NCR);
            $('#NIT_details').empty();
            $('#NIT_details').html('NIT: ' + response[0].NIT);
            $('#departamento_name').empty();
            $('#departamento_name').html(response[0].department_name);
            $('#forma_pago_name').empty();
            var forma_name;
            switch(response[0].waytopay){
                case "1":
                    forma_name='CONTADO';
                    break;
                case "2":
                    forma_name='CREDITO';
                    break;
                case "3":
                    forma_name='OTRO';
                    break;
            }
            $('#forma_pago_name').html(forma_name);
            $('#acuenta_de').empty();
            $('#acuenta_de').html(response[0].acuenta);
            var div_copy = $('#tblproduct').clone();
                div_copy.removeClass();
                div_copy.addClass('table_details');
                div_copy.find('.fadeIn').removeClass();
                div_copy.children().val("");
                div_copy.find('.quitar_documents').remove();
                div_copy.find('.bg-secondary').removeClass();
                div_copy.find('.text-white').removeClass();
                div_copy.find('thead').addClass('head_details');
                div_copy.find('tfoot').addClass('tfoot_details');
                div_copy.find('th').addClass('th_details');
                div_copy.find('td').addClass('td_details');
                $('#details_products_documents').empty();
                $('#details_products_documents').append(div_copy);
                //$(".quitar_documents").empty();
                //$("#quitar_documents").remove();
        },
    });
    return salida;
}

function creardocuments() {
    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
            confirmButton: "btn btn-success",
            cancelButton: "btn btn-danger",
        },
        buttonsStyling: false,
    });

    swalWithBootstrapButtons
        .fire({
            title: "Crear Documento?",
            text: "Es seguro de guardar la información",
            icon: "info",
            showCancelButton: true,
            confirmButtonText: "Si, Crear!",
            cancelButtonText: "No, espera!",
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return new Promise((resolve) => {
                    var corr = $('#valcorr').val();
                    var totalamount = $('#ventatotallhidden').val();
                    totalamount = 0 + totalamount;

                    $.ajax({
                        url: "createdocument/" + btoa(corr) + '/' + totalamount,
                        method: "GET",
                        success: function (response) {
                            //console.log(response);
                            if (response.res == 1) {
                                // Si viene información del ticket automático, guardarla
                                if (response.ticket_auto && response.ticket_url) {
                                    response.ticket_data = {
                                        auto: true,
                                        url: response.ticket_url,
                                        print_url: response.ticket_print_url,  // Nueva URL de impresión directa
                                        sale_id: response.sale_id
                                    };
                                }
                                resolve(response);
                            } else if (response.res == 0) {
                                reject("Algo salió mal");
                            }
                        },
                        error: function(xhr, status, error) {
                            reject("Error al crear el documento: " + error);
                        }
                    });
                });
            },
        })
        .then((result) => {
            if (result.value) {
                // Limpiar localStorage al finalizar exitosamente
                localStorage.removeItem('corr_sale_id');

                                // Abrir ticket automáticamente si está configurado Y habilitado por el usuario
                const ticketAutoEnabled = localStorage.getItem('ticket_auto_enabled') !== 'false';
                if (result.value.ticket_data && result.value.ticket_data.auto && ticketAutoEnabled) {
                    console.log('🎫 Imprimiendo ticket automáticamente para venta #' + result.value.ticket_data.sale_id);

                    // Intentar impresión directa del servidor primero
                    if (result.value.ticket_data.print_url) {
                        fetch(result.value.ticket_data.print_url)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    console.log('✅ Ticket impreso automáticamente en:', data.printer);
                                } else {
                                    console.log('⚠️ Impresión directa falló, usando navegador');
                                    // Fallback: abrir en ventana
                                    const ticketWindow = window.open(
                                        result.value.ticket_data.url,
                                        'ticket_auto_' + result.value.ticket_data.sale_id,
                                        'width=400,height=500,scrollbars=no,resizable=no,menubar=no,toolbar=no,location=no,status=no'
                                    );
                                }
                            })
                            .catch(error => {
                                console.error('❌ Error en impresión automática:', error);
                                // Fallback: abrir en ventana
                                const ticketWindow = window.open(
                                    result.value.ticket_data.url,
                                    'ticket_auto_' + result.value.ticket_data.sale_id,
                                    'width=400,height=500,scrollbars=no,resizable=no,menubar=no,toolbar=no,location=no,status=no'
                                );
                            });
                    } else {
                        // Solo navegador disponible
                        const ticketWindow = window.open(
                            result.value.ticket_data.url,
                            'ticket_auto_' + result.value.ticket_data.sale_id,
                            'width=400,height=500,scrollbars=no,resizable=no,menubar=no,toolbar=no,location=no,status=no'
                        );

                        if (!ticketWindow) {
                            console.log('⚠️ No se pudo abrir ventana del ticket (bloqueador de pop-ups)');
                        }
                    }
                }

                Swal.fire({
                    title: "¡DTE Creado correctamente!",
                    text: result.value.ticket_data && result.value.ticket_data.auto ? "Generando ticket automáticamente..." : "",
                    icon: "success",
                    confirmButtonText: "Ok",
                    timer: result.value.ticket_data && result.value.ticket_data.auto ? 3000 : undefined,
                    showConfirmButton: true
                }).then(() => {
                    window.location.href = "index";
                });
            }
        })
        .catch((error) => {
            Swal.fire({
                title: "Error",
                text: error,
                icon: "error",
                confirmButtonText: "Ok",
            });
        });
}


function agregarfacdetails(corr) {
    var typedoc = $('#typedocument').val()
    $.ajax({
        url: "getdetailsdoc/" + btoa(corr),
        method: "GET",
        async: false,
        success: function (response) {

            let totaltemptotal = 0;
            let totalsumas = 0;
            let ivarete13total = 0;
            let rentatotal = 0;
            let ivaretetotal = 0;
            let nosujetatotal = 0;
            let exempttotal = 0;
            let pricesaletotal = 0;
            let preciounitario = 0;
            let preciogravadas = 0;
            $.each(response, function (index, value) {

                if(typedoc=='6' || typedoc=='8'){
                    ivarete13total += parseFloat(0.00);
                    preciounitario = parseFloat(parseFloat(value.priceunit)+(value.detained13/value.amountp));
                    preciogravadas = parseFloat(parseFloat(value.pricesale)+parseFloat(value.detained13));
                }else{
                    ivarete13total += parseFloat(value.detained13);
                    preciounitario = parseFloat(value.priceunit);
                    preciogravadas = parseFloat(value.pricesale);
                }
                var totaltemp = (parseFloat(value.nosujeta) + parseFloat(value.exempt) + parseFloat(preciogravadas));
                totalsumas += totaltemp;
                rentatotal += parseFloat(value.renta);
                ivaretetotal += parseFloat(value.detained);
                nosujetatotal += parseFloat(value.nosujeta);
                exempttotal += parseFloat(value.exempt);
                pricesaletotal += parseFloat(value.pricesale);
                totaltemptotal += (parseFloat(value.nosujeta) + parseFloat(value.exempt) + parseFloat(value.pricesale))
                + (parseFloat(value.detained13) - (parseFloat(value.renta) + (parseFloat(value.detained))));
                var sumasl = 0;
                var iva13l = 0;
                var renta10l = 0;
                var ivaretenidol = 0;
                var ventasnosujetasl = 0;
                var ventasexentasl = 0;
                var ventatotall = 0;
                var row =
                    '<tr id="pro' +
                    value.id +
                    '"><td>' +
                    value.amountp +
                    "</td><td>" +
                    value.product_name +
                    "</td><td>" +
                    preciounitario.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    "</td><td>" +
                    parseFloat(value.nosujeta).toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    "</td><td>" +
                    parseFloat(value.exempt).toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    "</td><td>" +
                    preciogravadas.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    '</td><td class="text-center">' +
                    totaltemp.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    '</td><td class="quitar_documents"><button class="btn rounded-pill btn-icon btn-danger" type="button" onclick="eliminarpro(' +
                    value.id +
                    ')"><span class="ti ti-trash"></span></button></td></tr>';
                $("#tblproduct tbody").append(row);
                sumasl = totalsumas;
                $("#sumasl").html(
                    sumasl.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#sumas").val(sumasl);
                iva13l = ivarete13total;
                $("#13ival").html(
                    iva13l.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#13iva").val(iva13l);
                renta10l = rentatotal;
                $("#10rental").html(
                    renta10l.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#rentaretenido").val(renta10l);
                ivaretenidol =  ivaretetotal;
                $("#ivaretenidol").html(
                    ivaretenidol.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#ivaretenido").val(ivaretenidol);
                ventasnosujetasl =  nosujetatotal;
                $("#ventasnosujetasl").html(
                    ventasnosujetasl.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#ventasnosujetas").val(ventasnosujetasl);
                ventasexentasl = exempttotal;
                $("#ventasexentasl").html(
                    ventasexentasl.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#ventasexentas").val(ventasexentasl);
                ventatotall = totaltemptotal;
                $("#ventatotall").html(
                    ventatotall.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#ventatotallhidden").val(ventatotall);
                $("#ventatotal").val(ventatotall);
            });
        },
        failure: function (response) {
            Swal.fire("Hay un problema: " + response.responseText);
        },
        error: function (response) {
            Swal.fire("Hay un problema: " + response.responseText);
        },
    });
}

(function () {
    // Icons Wizard
    // --------------------------------------------------------------------
    const wizardIcons = document.querySelector(".wizard-icons-example");

    if (typeof wizardIcons !== undefined && wizardIcons !== null) {
        const wizardIconsBtnNextList = [].slice.call(
                wizardIcons.querySelectorAll(".btn-next")
            ),
            wizardIconsBtnPrevList = [].slice.call(
                wizardIcons.querySelectorAll(".btn-prev")
            ),
            wizardIconsBtnSubmit = wizardIcons.querySelector(".btn-submit");

        const iconsStepper = new Stepper(wizardIcons, {
            linear: false,
        });

        // Asignar stepper globalmente para poder usarlo desde otras funciones
        window.stepper = iconsStepper;

        // Agregar listener para detectar cuando se navega a la sección de revisión
        wizardIcons.addEventListener('shown.bs-stepper', function (event) {
            // Si se navega al paso 4 (review-submit), actualizar resumen
            if (event.detail.indexStep === 3) { // index 3 = paso 4 (review-submit)
                updateResumenData();
            }
        });

        if (wizardIconsBtnNextList) {
            wizardIconsBtnNextList.forEach((wizardIconsBtnNext) => {
                wizardIconsBtnNext.addEventListener("click", (event) => {
                    var id = $(wizardIconsBtnNext).attr("id");
                    switch (id) {
                        case "step-document-type":
                            // Validar que se haya seleccionado un tipo de documento
                            var typedocument = $("#typedocument").val();
                            if (typedocument && typedocument !== '') {
                                // Preparar datos para el siguiente paso
                                if (window.hasAutoSelectedCompany && window.autoSelectedCompanyId) {
                                    // Cargar clientes de la empresa auto-seleccionada
                                    getclientbycompanyurl(window.autoSelectedCompanyId);
                                    // Simular aviablenext para empresa auto-seleccionada
                                    aviablenext(window.autoSelectedCompanyId);
                                    // Crear correlativo
                                    createcorrsale(typedocument);
                                    // Saltar el paso de empresa si está auto-seleccionada
                                    iconsStepper.to(2); // Ir directamente a información de factura
                                } else {
                                    // Ir al paso normal de selección de empresa
                                    iconsStepper.next();
                                }
                            } else {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Tipo de documento requerido',
                                    text: 'Debes seleccionar un tipo de documento.'
                                });
                            }
                            break;
                        case "step1":
                            var typedocument = $("#typedocument").val();
                            var create = createcorrsale(typedocument);
                            if (create) {
                                iconsStepper.next();
                            }
                            break;
                        case "step2":
                            iconsStepper.to(3);
                            break;
                        case "step3":
                            var createdoc = getinfodoc();
                            if(createdoc){
                                iconsStepper.to(4);
                                // Actualizar datos del resumen cuando se navegue a la revisión
                                updateResumenData();
                            }
                            break;

                    }
                });
            });
        }
        if (wizardIconsBtnPrevList) {
            wizardIconsBtnPrevList.forEach((wizardIconsBtnPrev) => {
                wizardIconsBtnPrev.addEventListener("click", (event) => {
                    iconsStepper.previous();
                });
            });
        }
        if (wizardIconsBtnSubmit) {
            wizardIconsBtnSubmit.addEventListener("click", (event) => {
                validarYConfirmarGuardado();
            });
        }
    }
})();

// Función para actualizar los datos del resumen en la sección de Revisión & Creación
function updateResumenData() {
    // 1. Actualizar información de la empresa
    var empresaTexto = $("#company option:selected").text();
    $("#resumen-empresa").text(empresaTexto || "-");

    // 2. Actualizar información del cliente
    var clienteTexto = $("#client option:selected").text();
    $("#resumen-cliente").text(clienteTexto || "-");

    // 3. Actualizar forma de pago
    var pagoTexto = $("#fpago option:selected").text();
    $("#resumen-pago").text(pagoTexto || "-");

    // 4. Actualizar total de venta
    var totalVenta = $("#ventatotall").text() || "$ 0.00";
    // También intentar obtener del campo hidden como fallback
    if (totalVenta === "$ 0.00" || totalVenta === "$0.00") {
        var totalHidden = $("#ventatotallhidden").val();
        if (totalHidden && parseFloat(totalHidden) > 0) {
            totalVenta = "$ " + parseFloat(totalHidden).toFixed(2);
        }
    }
    console.log('Total para resumen:', totalVenta);
    $("#resumen-total").text(totalVenta);

    // 5. Actualizar lista de productos
    updateResumenProductos();
}

// Función para actualizar la lista de productos en el resumen
function updateResumenProductos() {
    var productosHtml = "";
    var productRows = $("#tblproduct tbody tr");

    if (productRows.length === 0) {
        productosHtml = "<p>No hay productos agregados</p>";
    } else {
        productosHtml = "<div class='table-responsive'>";
        productosHtml += "<table class='table table-sm'>";
        productosHtml += "<thead><tr><th>Producto</th><th>Cant.</th><th>Precio</th><th>Total</th></tr></thead>";
        productosHtml += "<tbody>";

        productRows.each(function() {
            var $row = $(this);
            var producto = $row.find('td:eq(0)').text(); // Descripción del producto
            var cantidad = $row.find('td:eq(1)').text(); // Cantidad
            var precio = $row.find('td:eq(2)').text(); // Precio unitario
            var total = $row.find('td:eq(3)').text(); // Total

            productosHtml += "<tr>";
            productosHtml += "<td>" + producto + "</td>";
            productosHtml += "<td>" + cantidad + "</td>";
            productosHtml += "<td>" + precio + "</td>";
            productosHtml += "<td>" + total + "</td>";
            productosHtml += "</tr>";
        });

        productosHtml += "</tbody></table></div>";

        // Agregar totales resumidos
        productosHtml += "<div class='mt-2'>";
        productosHtml += "<small><strong>Ventas Gravadas:</strong> " + ($("#sumasl").text() || "$0.00") + "</small><br>";
        productosHtml += "<small><strong>Ventas No Sujetas:</strong> " + ($("#ventasnosujetasl").text() || "$0.00") + "</small><br>";
        productosHtml += "<small><strong>Ventas Exentas:</strong> " + ($("#ventasexentasl").text() || "$0.00") + "</small><br>";
        productosHtml += "<small><strong>IVA 13%:</strong> " + ($("#13ival").text() || "$0.00") + "</small>";
        productosHtml += "</div>";
    }

    $("#resumen-productos").html(productosHtml);
}

// Función requerida para el botón submit
function validarYConfirmarGuardado() {
    // Validar empresa
    if (!$('#company').val() || $('#company').val() == '0') {
        Swal.fire({icon:'warning',title:'Empresa requerida',text:'Debes seleccionar una empresa.'});
        return false;
    }
    // Validar cliente
    if (!$('#client').val() || $('#client').val() == '0') {
        Swal.fire({icon:'warning',title:'Cliente requerido',text:'Debes seleccionar un cliente.'});
        return false;
    }
    // Validar forma de pago
    if (!$('#fpago').val() || $('#fpago').val() == '0') {
        Swal.fire({icon:'warning',title:'Forma de pago requerida',text:'Debes seleccionar la forma de pago.'});
        return false;
    }
    // Validar que haya al menos un producto
    if ($('#tblproduct tbody tr').length === 0) {
        Swal.fire({icon:'warning',title:'Productos requeridos',text:'Debes agregar al menos un producto a la factura.'});
        return false;
    }
    // Confirmación antes de guardar
    Swal.fire({
        title: '¿Guardar factura?',
        text: '¿Estás seguro de que deseas guardar esta factura?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'No, cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Aquí va la lógica de guardado real (llama a tu función de guardar)
            creardocuments();
        }
    });
}


