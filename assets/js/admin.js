jQuery(document).ready(function($) {
    
    // Inicializar pickers de color
    $('.cuadros-color-picker').wpColorPicker();
    
    // Variables globales
    var uploadModal = $('#cuadros-upload-modal');
    var marcoList = $('#cuadros-marco-list');
    var addMarcoBtn = $('#cuadros-add-marco');
    var woocommerceModels = [];
    
    // Cargar modelos de WooCommerce al iniciar
    loadWooCommerceModels();
    
    // Cargar lista de marcos al iniciar
    loadMarcosList();
    
    // Mostrar modal para agregar nuevo marco
    addMarcoBtn.on('click', function() {
        showUploadModal();
    });
    
    // Cargar modelos de WooCommerce
    function loadWooCommerceModels() {
        console.log('[cuadros] Loading WooCommerce models...');
        console.log('[cuadros] AJAX URL:', cuadros_admin.ajax_url);
        console.log('[cuadros] Nonce:', cuadros_admin.nonce);
        
        $.ajax({
            url: cuadros_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'cuadros_get_models',
                nonce: cuadros_admin.nonce
            },
            success: function(response) {
                console.log('[cuadros] Response from server:', response);
                if (response.success) {
                    woocommerceModels = response.data.models;
                    console.log('[cuadros] Loaded ' + woocommerceModels.length + ' models from WooCommerce:', woocommerceModels);
                } else {
                    console.log('[cuadros] Server returned error:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('[cuadros] AJAX Error:', status, error);
                console.log('[cuadros] Response:', xhr.responseText);
            }
        });
    }
    
    // Cargar lista de marcos
    function loadMarcosList() {
        marcoList.addClass('cuadros-loading');
        
        $.ajax({
            url: cuadros_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'cuadros_get_marcos',
                nonce: cuadros_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderMarcosList(response.data.marcos);
                } else {
                    showMessage('error', 'Error al cargar la lista de marcos.');
                }
            },
            error: function() {
                showMessage('error', 'Error de conexión al cargar marcos.');
            },
            complete: function() {
                marcoList.removeClass('cuadros-loading');
            }
        });
    }
    
    // Renderizar lista de marcos
    function renderMarcosList(marcos) {
        if (marcos.length === 0) {
            marcoList.html('<p class="description">No hay imágenes de marcos subidas aún.</p>');
            return;
        }
        
        var html = '';
        
        marcos.forEach(function(marco) {
            html += '<div class="marco-item" data-filename="' + marco.filename + '">';
            html += '<div class="marco-preview" style="background-image: url(\'' + marco.url + '\')"></div>';
            html += '<div class="marco-info">';
            html += '<h4>' + (marco.modelo || marco.color || 'N/A').charAt(0).toUpperCase() + (marco.modelo || marco.color || 'N/A').slice(1) + ' - ' + marco.orientation.charAt(0).toUpperCase() + marco.orientation.slice(1) + '</h4>';
            html += '<p><strong>Archivo:</strong> ' + marco.filename + '</p>';
            html += '<p><strong>Subido:</strong> ' + marco.uploaded + '</p>';
            html += '</div>';
            html += '<div class="marco-actions">';
            html += '<button type="button" class="button button-small view-marco">Ver</button>';
            html += '<button type="button" class="button button-small button-link-delete delete-marco">Eliminar</button>';
            html += '</div>';
            html += '</div>';
        });
        
        marcoList.html(html);
        
        // Agregar eventos a los botones
        $('.view-marco').on('click', function() {
            var url = $(this).closest('.marco-item').find('.marco-preview').css('background-image');
            url = url.replace(/^url\(["']?/, '').replace(/["']?\)$/, '');
            window.open(url, '_blank');
        });
        
        $('.delete-marco').on('click', function() {
            var item = $(this).closest('.marco-item');
            var filename = item.data('filename');
            
            if (confirm('¿Estás seguro de que quieres eliminar esta imagen?')) {
                deleteMarco(filename, item);
            }
        });
    }
    
    // Eliminar un marco
    function deleteMarco(filename, item) {
        item.addClass('cuadros-loading');
        
        $.ajax({
            url: cuadros_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'cuadros_delete_marco',
                nonce: cuadros_admin.nonce,
                filename: filename
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message);
                    // Siempre recargar la lista después de eliminar
                    setTimeout(function() {
                        loadMarcosList();
                    }, 500);
                } else {
                    showMessage('error', response.data.message);
                    item.removeClass('cuadros-loading');
                }
            },
            error: function(xhr, status, error) {
                console.log('[cuadros] Error al eliminar:', xhr.responseText);
                showMessage('error', 'Error de conexión al eliminar marco.');
                item.removeClass('cuadros-loading');
            }
        });
    }
    
    // Mostrar modal de subida
    function showUploadModal() {
        if (uploadModal.length === 0) {
            createUploadModal();
        }
        
        uploadModal.addClass('active');
    }
    
    // Crear modal de subida
    function createUploadModal() {
        var modalHtml = '<div id="cuadros-upload-modal" class="cuadros-upload-modal">';
        modalHtml += '<div class="cuadros-upload-content">';
        modalHtml += '<h3>Subir Nueva Imagen de Marco</h3>';
        
        // Sección de referencia con imagen
        modalHtml += '<div class="cuadros-upload-reference">';
        modalHtml += '<p class="description"><strong>Referencia:</strong> Las imágenes de marcos deben ser PNG transparentes que se superpongan sobre las imágenes de productos.</p>';
        modalHtml += '<div class="reference-preview" id="reference-preview" style="border: 1px solid #ccc; padding: 10px; margin: 10px 0; text-align: center; color: #999; min-height: 200px; display: flex; align-items: center; justify-content: center;">';
        modalHtml += 'Vista previa (selecciona un archivo PNG)';
        modalHtml += '</div>';
        modalHtml += '</div>';
        
        modalHtml += '<form id="cuadros-upload-form" class="cuadros-upload-form" enctype="multipart/form-data">';
        modalHtml += '<div class="form-group">';
        modalHtml += '<label for="marco-modelo">Modelo del Producto</label>';
        modalHtml += '<select id="marco-modelo" name="modelo" required>';
        modalHtml += '<option value="">Seleccionar modelo...</option>';
        
        // Agregar opciones de modelos dinámicamente
        if (woocommerceModels.length > 0) {
            woocommerceModels.forEach(function(model) {
                modalHtml += '<option value="' + model + '">' + model + '</option>';
            });
        }
        
        modalHtml += '</select>';
        modalHtml += '<p class="description">Selecciona el modelo o atributo del producto para el cual es este marco.</p>';
        modalHtml += '</div>';
        
        modalHtml += '<div class="form-group">';
        modalHtml += '<label for="marco-orientation">Orientación</label>';
        modalHtml += '<select id="marco-orientation" name="orientation" required>';
        modalHtml += '<option value="">Seleccionar orientación...</option>';
        modalHtml += '<option value="vertical">Vertical</option>';
        modalHtml += '<option value="horizontal">Horizontal</option>';
        modalHtml += '</select>';
        modalHtml += '</div>';
        
        modalHtml += '<div class="form-group">';
        modalHtml += '<label for="marco-image">Imagen PNG</label>';
        modalHtml += '<input type="file" id="marco-image" name="marco_image" accept=".png" required>';
        modalHtml += '<p class="description">Suba una imagen PNG transparente para el marco.</p>';
        modalHtml += '</div>';
        
        modalHtml += '<div class="cuadros-upload-actions">';
        modalHtml += '<button type="button" class="button cancel-upload">Cancelar</button>';
        modalHtml += '<button type="submit" class="button button-primary">Subir Imagen</button>';
        modalHtml += '</div>';
        modalHtml += '</form>';
        modalHtml += '<div id="cuadros-upload-message" class="cuadros-message"></div>';
        modalHtml += '</div>';
        modalHtml += '</div>';
        
        $('body').append(modalHtml);
        uploadModal = $('#cuadros-upload-modal');
        
        // Eventos del modal
        $('.cancel-upload').on('click', function() {
            uploadModal.removeClass('active');
            resetUploadForm();
        });
        
        $('#cuadros-upload-form').on('submit', function(e) {
            e.preventDefault();
            uploadMarcoImage();
        });
        
        // Mostrar vista previa de la imagen seleccionada
        $('#marco-image').on('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(event) {
                    $('#reference-preview').html('<img src="' + event.target.result + '" style="max-width: 100%; max-height: 300px; object-fit: contain;">');
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Cerrar modal al hacer clic fuera
        uploadModal.on('click', function(e) {
            if (e.target === this) {
                uploadModal.removeClass('active');
                resetUploadForm();
            }
        });
    }
    
    // Subir imagen de marco
    function uploadMarcoImage() {
        var form = $('#cuadros-upload-form')[0];
        var formData = new FormData(form);
        
        // Validar que se haya seleccionado un archivo
        var fileInput = $('#marco-image')[0];
        if (!fileInput.files || fileInput.files.length === 0) {
            showMessage('error', 'Por favor, selecciona un archivo PNG.');
            return;
        }
        
        // Validar tamaño del archivo (máximo 2MB)
        var file = fileInput.files[0];
        if (file.size > 2 * 1024 * 1024) {
            showMessage('error', 'El archivo es demasiado grande. El tamaño máximo es 2MB.');
            return;
        }
        
        formData.append('action', 'cuadros_upload_marco');
        formData.append('nonce', cuadros_admin.nonce);
        
        var submitBtn = $('#cuadros-upload-form .button-primary');
        var originalText = submitBtn.text();
        
        submitBtn.prop('disabled', true).text('Subiendo...');
        hideMessage();
        
        console.log('Enviando solicitud de subida...', {
            color: $('#marco-color').val(),
            orientation: $('#marco-orientation').val(),
            fileName: file.name,
            fileSize: file.size
        });
        
        $.ajax({
            url: cuadros_admin.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                if (response.success) {
                    showMessage('success', response.data.message);
                    resetUploadForm();
                    // If the upload response includes the updated marcos list, use it
                    if (response.data && response.data.marcos) {
                        var marcosPayload = response.data.marcos;
                        if (Array.isArray(marcosPayload)) {
                            renderMarcosList(marcosPayload);
                        } else if (marcosPayload.marco_images && Array.isArray(marcosPayload.marco_images)) {
                            renderMarcosList(marcosPayload.marco_images);
                        } else {
                            loadMarcosList();
                        }
                    } else {
                        loadMarcosList();
                    }
                    
                    // Cerrar modal después de 2 segundos
                    setTimeout(function() {
                        uploadModal.removeClass('active');
                        hideMessage();
                    }, 2000);
                } else {
                    showMessage('error', response.data.message || 'Error desconocido.');
                    submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la solicitud AJAX:', status, error, xhr.responseText);
                showMessage('error', 'Error de conexión al subir imagen. Verifica la consola para más detalles.');
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    }
    
    // Resetear formulario de subida
    function resetUploadForm() {
        $('#cuadros-upload-form')[0].reset();
        $('#cuadros-upload-form .button-primary').prop('disabled', false).text('Subir Imagen');
        hideMessage();
    }
    
    // Mostrar mensaje
    function showMessage(type, text) {
        var messageDiv = $('#cuadros-upload-message');
        messageDiv.removeClass('success error warning').addClass(type).text(text).show();
    }
    
    // Ocultar mensaje
    function hideMessage() {
        $('#cuadros-upload-message').hide().removeClass('success error warning');
    }
    
    // Mostrar mensaje en la página principal
    function showPageMessage(type, text) {
        var messageHtml = '<div class="cuadros-message ' + type + '">' + text + '</div>';
        $('#cuadros-marco-management').prepend(messageHtml);
        
        setTimeout(function() {
            $('.cuadros-message.' + type).fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
});
