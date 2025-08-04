/**
 * Form Picker
 */

'use strict';
$(document).ready(function (){

    $("#name").on("keyup", function () {
        var valor = $(this).val();
        $(this).val(valor.toUpperCase());
    });

    $("#name-edit").on("keyup", function () {
        var valor = $(this).val();
        $(this).val(valor.toUpperCase());
    });

    //Get providers avaibles
    var iduser = $('#iduser').val();
    $.ajax({
        url: "/provider/getproviders",
        method: "GET",
        success: function(response){
            //console.log(response);
            $('#provider').append('<option value="0">Seleccione</option>');
            $.each(response, function(index, value) {
                $('#provider').append('<option value="'+value.id+'">'+value.razonsocial.toUpperCase()+'</option>');
                $('#provideredit').append('<option value="'+value.id+'">'+value.razonsocial.toUpperCase()+'</option>');
              });
        }
    });
    //Get marcas avaibles
    $.ajax({
        url: "/marcas/getmarcas",
        method: "GET",
        success: function(response){
            //console.log(response);
            $('#marca').append('<option value="0">Seleccione</option>');
            $.each(response, function(index, value) {
                $('#marca').append('<option value="'+value.id+'">'+value.name.toUpperCase()+'</option>');
                $('#marcaredit').append('<option value="'+value.id+'">'+value.name.toUpperCase()+'</option>');
              });
        }
    });

    // Inicializar componente cuando se muestre el modal de crear
    $('#addProductModal').on('shown.bs.modal', function () {
        if(typeof initializeImageUpload === 'function') {
            initializeImageUpload('image');
        }
    });

    // Limpiar modal de crear cuando se cierre
    $('#addProductModal').on('hidden.bs.modal', function () {
        if(typeof clearImageUpload === 'function') {
            clearImageUpload('image');
        }
    });

    // Limpiar modal de editar cuando se cierre
    $('#updateProductModal').on('hidden.bs.modal', function () {
        if(typeof clearImageUpload === 'function') {
            clearImageUpload('imageedit');
        }
        // Eliminar el campo oculto de imagen original
        $('#editproductForm #imageeditoriginal').remove();
    });
});

   function editproduct(id){
    // Limpiar el componente de imagen antes de cargar datos
    if(typeof clearImageUpload === 'function') {
        clearImageUpload('imageedit');
    }

    //Get data edit Products
    $.ajax({
        url: "getproductid/"+btoa(id),
        method: "GET",
        success: function(response){
            $.each(response[0], function(index, value) {
                    // Excluir campos de archivo y campos especiales
                    if(index !== 'image' && index !== 'provider_id' && index !== 'cfiscal' && index !== 'type' && index !== 'category') {
                        $('#'+index+'edit').val(value);
                    }

                    if(index=='image'){
                        // Agregar o actualizar el campo oculto para la imagen original
                        let hiddenField = $('#imageeditoriginal');
                        if(hiddenField.length === 0) {
                            // Si no existe, crear el campo oculto
                            $('<input>').attr({
                                type: 'hidden',
                                id: 'imageeditoriginal',
                                name: 'imageeditoriginal',
                                value: value
                            }).appendTo('#editproductForm');
                        } else {
                            // Si existe, actualizar su valor
                            hiddenField.val(value);
                        }
                        // Limpiar el preview anterior
                        const currentImageContainer = document.getElementById('current-image-imageedit');
                        if(currentImageContainer) {
                            currentImageContainer.innerHTML = '';
                        }
                        // Mostrar la imagen actual si existe
                        if(value && value !== 'null' && value !== '') {
                            if(currentImageContainer) {
                                currentImageContainer.innerHTML = `
                                    <img src="/assets/img/products/${value}" alt="Imagen actual" class="img-thumbnail" style="max-height: 100px;">
                                    <div class="mt-1">
                                        <small class="text-muted">${value}</small>
                                    </div>
                                `;
                                currentImageContainer.style.display = 'block';
                            }
                        } else {
                            if(currentImageContainer) {
                                currentImageContainer.style.display = 'none';
                            }
                        }
                        // Limpiar el campo de imagen sin establecer valor
                        if($('#imageedit').length) {
                            $('#imageedit').val('');
                        }
                    }
                    if(index=='marca_id'){
                        //$("#marcaredit option[value='"+ value  +"']").attr("selected", true);
                        $("#marcaredit").val(value).trigger('change');
                    }
                    if(index=='provider_id'){
                        $("#provideredit option[value='"+ value  +"']").attr("selected", true);
                    }
                    if(index=='cfiscal'){
                        $("#cfiscaledit option[value='"+ value  +"']").attr("selected", true);
                    }
                    if(index=='type'){
                        $("#typeedit option[value='"+ value  +"']").attr("selected", true);
                    }
                    if(index=='category'){
                        //$("#categoryedit option[value='"+ value  +"']").attr("selected", true);
                        $("#categoryedit").val(value).trigger('change');
                    }

              });
              $("#updateProductModal").modal("show");

              // Inicializar el componente de imagen después de mostrar el modal
              setTimeout(() => {
                  if(typeof initializeImageUpload === 'function') {
                      initializeImageUpload('imageedit');
                  }
              }, 300);
        }
    });
   }

   function toggleState(id, newState){
    var stateText = newState == 1 ? 'activar' : 'desactivar';
    var swalWithBootstrapButtons = Swal.mixin({
        customClass: {
          confirmButton: 'btn btn-success',
          cancelButton: 'btn btn-danger'
        },
        buttonsStyling: false
      })

      swalWithBootstrapButtons.fire({
        title: '¿Cambiar estado?',
        text: '¿Está seguro que desea ' + stateText + ' este producto?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Si, ' + stateText + '!',
        cancelButtonText: 'No, Cancelar!',
        reverseButtons: true
      }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: "toggleState/"+btoa(id),
                method: "POST",
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    state: newState
                },
                success: function(response){
                        if(response.res==1){
                            Swal.fire({
                                title: 'Estado actualizado',
                                text: 'Producto ' + stateText + 'do correctamente',
                                icon: 'success',
                                confirmButtonText: 'Ok'
                              }).then(function(result) {
                                /* Read more about isConfirmed, isDenied below */
                                if (result.isConfirmed) {
                                  location.reload();
                                }
                              })

                        }else if(response.res==0){
                            swalWithBootstrapButtons.fire(
                                'Problemas!',
                                'Algo sucedió y no pudo cambiar el estado del producto, favor comunicarse con el administrador.',
                                'error'
                              )
                        }
            }
            });
        } else if (
          /* Read more about handling dismissals below */
          result.dismiss === Swal.DismissReason.cancel
        ) {
          swalWithBootstrapButtons.fire(
            'Cancelado',
            'No hemos hecho ninguna acción :)',
            'info'
          )
        }
      })
   }

   function deleteproduct(id){
    var swalWithBootstrapButtons = Swal.mixin({
        customClass: {
          confirmButton: 'btn btn-success',
          cancelButton: 'btn btn-danger'
        },
        buttonsStyling: false
      })

      swalWithBootstrapButtons.fire({
        title: '¿Eliminar?',
        text: "Esta accion no tiene retorno",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, Eliminarlo!',
        cancelButtonText: 'No, Cancelar!',
        reverseButtons: true
      }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: "destroy/"+btoa(id),
                method: "GET",
                success: function(response){
                        if(response.res==1){
                            Swal.fire({
                                title: 'Eliminado',
                                icon: 'success',
                                confirmButtonText: 'Ok'
                              }).then(function(result) {
                                /* Read more about isConfirmed, isDenied below */
                                if (result.isConfirmed) {
                                  location.reload();
                                }
                              })

                        }else if(response.res==0){
                            swalWithBootstrapButtons.fire(
                                'Problemas!',
                                'Algo sucedió y no pudo eliminar el cliente, favor comunicarse con el administrador.',
                                'success'
                              )
                        }
            }
            });
        } else if (
          /* Read more about handling dismissals below */
          result.dismiss === Swal.DismissReason.cancel
        ) {
          swalWithBootstrapButtons.fire(
            'Cancelado',
            'No hemos hecho ninguna acción :)',
            'error'
          )
        }
      })
   }

