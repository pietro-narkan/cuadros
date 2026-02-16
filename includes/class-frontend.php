<?php
/**
 * Clase para la lógica frontend del plugin Cuadros
 */
class Cuadros_Frontend {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_footer', array($this, 'output_frontend_script_auto'));
    }
    
    public function enqueue_frontend_scripts() {
        if (!is_product()) return;
        wp_enqueue_style('cuadros-frontend-style', CUADROS_ASSETS_URL . 'css/frontend.css', array(), CUADROS_VERSION);
        wp_enqueue_script('jquery');
    }
    
    public function output_frontend_script_auto() {
        if (!is_product()) return;
        global $product;
        if (!$product || !$product->is_type('variable')) return;
        $this->output_frontend_script();
    }
    
    public function output_frontend_script() {
        $settings = get_option('cuadros_settings', array());
        $marco_colors = isset($settings['marco_colors']) ? $settings['marco_colors'] : array();
        $paspartu_colors = isset($settings['paspartu_colors']) ? $settings['paspartu_colors'] : array();
        $doble_marco_enabled = isset($settings['doble_marco_enabled']) ? $settings['doble_marco_enabled'] : array();
        $doble_marco_colors = isset($settings['doble_marco_colors']) ? $settings['doble_marco_colors'] : array();
        $doble_marco_grosores = isset($settings['doble_marco_grosores']) ? $settings['doble_marco_grosores'] : array();
        
        // Grosores por orientación
        $grosor_marco_vertical = isset($settings['grosor_marco_vertical']) ? intval($settings['grosor_marco_vertical']) : 8;
        $grosor_marco_cuadrado = isset($settings['grosor_marco_cuadrado']) ? intval($settings['grosor_marco_cuadrado']) : 10;
        $grosor_marco_horizontal = isset($settings['grosor_marco_horizontal']) ? intval($settings['grosor_marco_horizontal']) : 6;
        
        $grosor_paspartu_vertical = isset($settings['grosor_paspartu_vertical']) ? intval($settings['grosor_paspartu_vertical']) : 30;
        $grosor_paspartu_cuadrado = isset($settings['grosor_paspartu_cuadrado']) ? intval($settings['grosor_paspartu_cuadrado']) : 25;
        $grosor_paspartu_horizontal = isset($settings['grosor_paspartu_horizontal']) ? intval($settings['grosor_paspartu_horizontal']) : 20;
        
        $grosor_doble_marco_vertical = isset($settings['grosor_doble_marco_vertical']) ? intval($settings['grosor_doble_marco_vertical']) : 3;
        $grosor_doble_marco_cuadrado = isset($settings['grosor_doble_marco_cuadrado']) ? intval($settings['grosor_doble_marco_cuadrado']) : 4;
        $grosor_doble_marco_horizontal = isset($settings['grosor_doble_marco_horizontal']) ? intval($settings['grosor_doble_marco_horizontal']) : 2;

        $flujo_paso_1 = isset($settings['flujo_paso_1']) ? sanitize_text_field($settings['flujo_paso_1']) : 'Selecciona primero el tamaño.';
        $flujo_paso_2 = isset($settings['flujo_paso_2']) ? sanitize_text_field($settings['flujo_paso_2']) : 'Selecciona el paspartú para desbloquear el marco.';
        $flujo_paso_3 = isset($settings['flujo_paso_3']) ? sanitize_text_field($settings['flujo_paso_3']) : 'Ahora puedes elegir el marco.';

        if ('' === trim($flujo_paso_1)) {
            $flujo_paso_1 = 'Selecciona primero el tamaño.';
        }

        if ('' === trim($flujo_paso_2)) {
            $flujo_paso_2 = 'Selecciona el paspartú para desbloquear el marco.';
        }

        if ('' === trim($flujo_paso_3)) {
            $flujo_paso_3 = 'Ahora puedes elegir el marco.';
        }

        $textos_pasos = array(
            'paso1' => $flujo_paso_1,
            'paso2' => $flujo_paso_2,
            'paso3' => $flujo_paso_3,
        );
        
        if (defined('CUADROS_SCRIPT_LOADED')) return;
        define('CUADROS_SCRIPT_LOADED', true);
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            
            var coloresMarco = <?php echo json_encode($marco_colors); ?>;
            var coloresPaspartu = <?php echo json_encode($paspartu_colors); ?>;
            var dobleMarcoEnabled = <?php echo json_encode($doble_marco_enabled); ?>;
            var dobleMarcoColors = <?php echo json_encode($doble_marco_colors); ?>;
            var dobleMarcoGrosores = <?php echo json_encode($doble_marco_grosores); ?>;
            
            console.log('[cuadros] Colores Marco cargados:', coloresMarco);
            console.log('[cuadros] Colores Paspartú cargados:', coloresPaspartu);
            console.log('[cuadros] Doble Marco Enabled:', dobleMarcoEnabled);
            console.log('[cuadros] Doble Marco Colors:', dobleMarcoColors);
            
            // Grosores por orientación
            var GROSORES_MARCO = {
                'vertical': <?php echo $grosor_marco_vertical; ?>,
                'cuadrado': <?php echo $grosor_marco_cuadrado; ?>,
                'horizontal': <?php echo $grosor_marco_horizontal; ?>
            };
            
            var GROSORES_PASPARTU = {
                'vertical': <?php echo $grosor_paspartu_vertical; ?>,
                'cuadrado': <?php echo $grosor_paspartu_cuadrado; ?>,
                'horizontal': <?php echo $grosor_paspartu_horizontal; ?>
            };
            
            var GROSORES_DOBLE_MARCO = {
                'vertical': <?php echo $grosor_doble_marco_vertical; ?>,
                'cuadrado': <?php echo $grosor_doble_marco_cuadrado; ?>,
                'horizontal': <?php echo $grosor_doble_marco_horizontal; ?>
            };

            var textosPasos = <?php echo wp_json_encode($textos_pasos); ?>;
            
            var FACTOR_ESCALA = 0.85;

            function obtenerSelectTamano() {
                var $tamano = $('#pa_tamano');
                if ($tamano.length) return $tamano.first();

                $tamano = $('select[name="attribute_pa_tamano"]');
                if ($tamano.length) return $tamano.first();

                $tamano = $('#tamano');
                if ($tamano.length) return $tamano.first();

                $tamano = $('select[name="attribute_tamano"]');
                if ($tamano.length) return $tamano.first();

                return $();
            }
            
            // Esperar un momento para que WooCommerce inicialice la galería
            setTimeout(inicializarCuadros, 500);
            
            function inicializarCuadros() {
                // Verificar si el producto tiene los atributos de cuadros
                var $tamanoSelect = obtenerSelectTamano();
                var $marcoSelect = $('#pa_marco');
                var $paspartuSelect = $('#pa_paspartu');
                var $variationsForm = $('form.variations_form').first();
                var flujoSecuencialActivo = $tamanoSelect.length > 0 && $paspartuSelect.length > 0 && $marcoSelect.length > 0;
                var $avisoOrden = $();
                var aplicandoOrden = false;
                var ultimoTamanoSeleccionado = $tamanoSelect.length ? String($tamanoSelect.val() || '').trim() : '';
                
                // Si no existe ninguno de los dos atributos, no activar el plugin
                if ($marcoSelect.length === 0 && $paspartuSelect.length === 0) {
                    console.log('[cuadros] Producto sin atributos de cuadros - plugin no activado');
                    return;
                }
                
                var $galleryImage = $('.woocommerce-product-gallery__image').first();
                var $productImage = $galleryImage.find('img').first();
                
                if ($productImage.length === 0) {
                    console.log('[cuadros] No se encontró imagen');
                    return;
                }
                
                // Obtener el tamaño del CONTENEDOR de la galería, no de la imagen
                var $galleryContainer = $('.flex-viewport').first();
                if ($galleryContainer.length === 0) {
                    $galleryContainer = $('.woocommerce-product-gallery__wrapper').first();
                }
                if ($galleryContainer.length === 0) {
                    $galleryContainer = $galleryImage;
                }
                
                var originalWidth = $galleryContainer.width();
                var originalHeight = $galleryContainer.height();
                
                // Fallback a la imagen si el contenedor no tiene dimensiones
                if (originalWidth < 50) originalWidth = $productImage.width() || $productImage[0].naturalWidth || 400;
                if (originalHeight < 50) originalHeight = $productImage.height() || $productImage[0].naturalHeight || 400;
                
                console.log('[cuadros] Dimensiones del contenedor:', originalWidth, 'x', originalHeight);
                
                // Crear los elementos
                var $fondoWrapper = $('<div id="cuadros-fondo"></div>');
                var $wrapper = $('<div id="cuadros-wrapper"></div>');
                var $marcoLayer = $('<div id="layer-marco"></div>');
                var $dobleMarcoLayer = $('<div id="layer-doble-marco"></div>');
                var $paspartuLayer = $('<div id="layer-paspartu"></div>');
                var $imagenLayer = $('<div id="layer-imagen"></div>');
                
                // Insertar antes de la imagen
                $productImage.before($fondoWrapper);
                
                // Construir estructura
                $fondoWrapper.append($wrapper);
                $wrapper.append($marcoLayer);
                $wrapper.append($paspartuLayer);
                $wrapper.append($dobleMarcoLayer);
                $wrapper.append($imagenLayer);
                
                // Mover la imagen dentro de imagenLayer
                $imagenLayer.append($productImage);
                
                // Estilos del fondo - tamaño fijo
                $fondoWrapper.css({
                    'width': originalWidth + 'px',
                    'height': originalHeight + 'px',
                    'min-width': originalWidth + 'px',
                    'min-height': originalHeight + 'px',
                    'max-width': originalWidth + 'px',
                    'max-height': originalHeight + 'px'
                });

                function escaparHtml(valor) {
                    return $('<div>').text(String(valor || '')).html();
                }

                function obtenerTextoPaso(clave, fallback) {
                    var texto = textosPasos && textosPasos[clave] ? String(textosPasos[clave]).trim() : '';
                    return texto || fallback;
                }

                function construirAvisoPasos() {
                    var paso1 = escaparHtml(obtenerTextoPaso('paso1', 'Selecciona primero el tamaño.'));

                    return '' +
                        '<div class="cuadros-steps-inline" role="list">' +
                            '<div class="cuadros-step-item is-active" data-step="1" role="listitem"><span class="cuadros-step-text">' + paso1 + '</span></div>' +
                        '</div>' +
                        '<div class="cuadros-step-status" aria-live="polite"></div>';
                }

                function inicializarAvisoOrden() {
                    if (!flujoSecuencialActivo || !$variationsForm.length) {
                        return;
                    }

                    $avisoOrden = $('#cuadros-selection-order-notice');
                    if ($avisoOrden.length === 0) {
                        $avisoOrden = $('<div id="cuadros-selection-order-notice" class="cuadros-selection-order-notice"></div>');
                        var $target = $variationsForm.find('table.variations').first();
                        if ($target.length) {
                            $target.before($avisoOrden);
                        } else {
                            $variationsForm.prepend($avisoOrden);
                        }
                    }

                    if (!$avisoOrden.find('.cuadros-steps-inline').length) {
                        $avisoOrden.html(construirAvisoPasos());
                    }
                }

                function setAvisoOrden(tamanoSeleccionado, paspartuSeleccionado, marcoSeleccionado, mensajeEstado, estadoTipo) {
                    if (!$avisoOrden.length) {
                        return;
                    }

                    var pasoActivo = 1;
                    var textoPaso = obtenerTextoPaso('paso1', 'Selecciona primero el tamaño.');

                    if (!tamanoSeleccionado) {
                        pasoActivo = 1;
                    } else if (!paspartuSeleccionado) {
                        pasoActivo = 2;
                        textoPaso = obtenerTextoPaso('paso2', 'Selecciona el paspartú para desbloquear el marco.');
                    } else if (!marcoSeleccionado) {
                        pasoActivo = 3;
                        textoPaso = obtenerTextoPaso('paso3', 'Ahora puedes elegir el marco.');
                    } else {
                        pasoActivo = 3;
                        textoPaso = obtenerTextoPaso('paso3', 'Ahora puedes elegir el marco.');
                    }

                    var $paso = $avisoOrden.find('.cuadros-step-item').first();
                    if ($paso.length) {
                        $paso
                            .attr('data-step', pasoActivo)
                            .removeClass('is-active is-completed')
                            .toggleClass('is-completed', tamanoSeleccionado && paspartuSeleccionado && marcoSeleccionado)
                            .toggleClass('is-active', !(tamanoSeleccionado && paspartuSeleccionado && marcoSeleccionado));

                        $paso.find('.cuadros-step-text').text(textoPaso);
                    }

                    var textoEstado = String(mensajeEstado || '').trim();
                    var $estado = $avisoOrden.find('.cuadros-step-status');

                    if (!$estado.length) {
                        return;
                    }

                    $estado
                        .removeClass('is-warning is-success')
                        .toggleClass('is-warning', estadoTipo === 'warning')
                        .toggleClass('is-success', estadoTipo === 'success')
                        .text(textoEstado);

                    if (textoEstado) {
                        $estado.show();
                    } else {
                        $estado.hide();
                    }
                }

                function tieneSeleccion($select) {
                    if (!$select.length) return false;
                    var val = $select.val();
                    return !!(val && String(val).trim() !== '');
                }

                function bloquearSelect($select, bloquear, tooltip) {
                    if (!$select.length) return;

                    $select.prop('disabled', bloquear);
                    $select.toggleClass('cuadros-select-bloqueado', bloquear);

                    if (bloquear && tooltip) {
                        $select.attr('title', tooltip);
                    } else {
                        $select.removeAttr('title');
                    }
                }

                function limpiarSelect($select) {
                    if (!$select.length || !tieneSeleccion($select)) {
                        return false;
                    }

                    $select.val('');
                    return true;
                }

                function normalizarSlug(valor) {
                    return String(valor || '').toLowerCase().trim().replace(/_/g, '-');
                }

                function esValorSinPaspartu(valor) {
                    var slug = normalizarSlug(valor);
                    if (!slug) return false;

                    return slug === 'sin-paspartu' ||
                        slug.indexOf('sin-paspartu') === 0 ||
                        slug === 'sin-paspartu-color' ||
                        slug === 'ninguno' ||
                        slug === 'none' ||
                        slug === 'no';
                }

                function obtenerValorSinPaspartu($select) {
                    if (!$select.length) return '';

                    var valor = '';
                    $select.find('option').each(function() {
                        var candidato = $(this).val();
                        if (esValorSinPaspartu(candidato)) {
                            valor = candidato;
                            return false;
                        }
                    });

                    return valor;
                }

                function aplicarFlujoSeleccion(saltarRecalculo) {
                    if (!flujoSecuencialActivo || aplicandoOrden) {
                        return;
                    }

                    aplicandoOrden = true;

                    var tamanoSeleccionado = tieneSeleccion($tamanoSelect);
                    var paspartuSeleccionado = tieneSeleccion($paspartuSelect);
                    var marcoSeleccionado = tieneSeleccion($marcoSelect);
                    var tamanoActual = $tamanoSelect.length ? String($tamanoSelect.val() || '').trim() : '';
                    var tamanoCambio = tamanoActual !== ultimoTamanoSeleccionado;
                    var tamanoCambioReal = tamanoCambio && tamanoSeleccionado && String(ultimoTamanoSeleccionado || '').trim() !== '';
                    var paspartuAutoSin = false;
                    var $selectRecalculo = null;
                    var mensajeEstado = '';
                    var estadoTipo = '';

                    if (tamanoCambioReal) {
                        var valorSinPaspartu = obtenerValorSinPaspartu($paspartuSelect);

                        if (valorSinPaspartu) {
                            if (String($paspartuSelect.val() || '') !== String(valorSinPaspartu)) {
                                $paspartuSelect.val(valorSinPaspartu);
                                $selectRecalculo = $paspartuSelect;
                            }
                            paspartuSeleccionado = true;
                            paspartuAutoSin = true;
                        } else {
                            if (limpiarSelect($paspartuSelect)) {
                                $selectRecalculo = $paspartuSelect;
                            }
                            paspartuSeleccionado = false;
                        }
                    }

                    if (!tamanoSeleccionado) {
                        if (limpiarSelect($paspartuSelect)) {
                            $selectRecalculo = $paspartuSelect;
                            paspartuSeleccionado = false;
                        }

                        if (limpiarSelect($marcoSelect)) {
                            marcoSeleccionado = false;
                            if (!$selectRecalculo) {
                                $selectRecalculo = $marcoSelect;
                            }
                        }

                        bloquearSelect($paspartuSelect, true, 'Selecciona primero un tamaño');
                        bloquearSelect($marcoSelect, true, 'Selecciona primero tamaño y paspartú');
                    } else if (!paspartuSeleccionado) {
                        bloquearSelect($paspartuSelect, false);

                        bloquearSelect($marcoSelect, true, 'Selecciona primero un paspartú');
                        if (tamanoCambioReal) {
                            mensajeEstado = 'Tamaño actualizado: no se encontró "Sin paspartú". Elige un paspartú para continuar.';
                            estadoTipo = 'warning';
                        }
                    } else {
                        bloquearSelect($paspartuSelect, false);
                        bloquearSelect($marcoSelect, false);

                        if (marcoSeleccionado) {
                            mensajeEstado = 'cofiguracion completa';
                            estadoTipo = 'success';
                        } else if (tamanoCambioReal && paspartuAutoSin) {
                            mensajeEstado = 'Tamaño actualizado: paspartú en "Sin paspartú". Ahora puedes elegir el marco.';
                            estadoTipo = 'success';
                        }
                    }

                    setAvisoOrden(tamanoSeleccionado, paspartuSeleccionado, marcoSeleccionado, mensajeEstado, estadoTipo);

                    ultimoTamanoSeleccionado = tamanoActual;

                    aplicandoOrden = false;

                    if ($selectRecalculo && !saltarRecalculo) {
                        setTimeout(function() {
                            $selectRecalculo.trigger('change');
                        }, 0);
                    }
                }

                inicializarAvisoOrden();

                if (flujoSecuencialActivo) {
                    console.log('[cuadros] Flujo secuencial activo: tamaño -> paspartú -> marco');
                } else {
                    console.log('[cuadros] Flujo secuencial inactivo: faltan atributos requeridos (tamano/paspartu/marco)');
                }
                
                function esSlugSinMarco(val) {
                    if (!val) return false;
                    var slugNormalizado = String(val).toLowerCase().trim().replace(/_/g, '-');
                    return slugNormalizado === 'sin-marco';
                }

                function buscarColorMarco(val) {
                    if (!val) return null;
                    
                    // El valor del select ya es el slug
                    var slug = val.toLowerCase();

                    // Caso especial: si el slug es "sin-marco", no se debe dibujar borde
                    if (esSlugSinMarco(slug)) {
                        return null;
                    }
                    
                    // Buscar coincidencia directa por slug
                    if (coloresMarco[slug]) {
                        return coloresMarco[slug];
                    }
                    
                    // Buscar coincidencia parcial
                    for (var k in coloresMarco) {
                        if (k.includes(slug) || slug.includes(k)) {
                            return coloresMarco[k];
                        }
                    }
                    
                    return null;
                }
                
                function buscarDobleMarco(val) {
                    if (!val) return { enabled: false, color: null, grosor: null };
                    
                    var slug = val.toLowerCase();

                    if (esSlugSinMarco(slug)) {
                        return { enabled: false, color: null, grosor: null };
                    }
                    
                    // Solo buscar coincidencia EXACTA por slug (sin búsqueda parcial)
                    var enabled = false;
                    var color = null;
                    var grosor = null;
                    
                    if (dobleMarcoEnabled[slug]) {
                        enabled = true;
                        color = dobleMarcoColors[slug] || '#8B4513';
                        grosor = dobleMarcoGrosores[slug] || null; // null significa usar grosor por orientación
                    }
                    
                    return { enabled: enabled, color: color, grosor: grosor };
                }
                
                function buscarColorPaspartu(val) {
                    if (!val) return null;
                    
                    // Detectar "sin paspartú" o variantes
                    var slug = val.toLowerCase();
                    if (slug.includes('sin') || slug === 'ninguno' || slug === 'none' || slug === 'no') {
                        return null;
                    }
                    
                    // Buscar coincidencia directa por slug
                    if (coloresPaspartu[slug]) {
                        return coloresPaspartu[slug];
                    }
                    
                    // Buscar coincidencia parcial
                    for (var k in coloresPaspartu) {
                        if (k.includes(slug) || slug.includes(k)) {
                            return coloresPaspartu[k];
                        }
                    }
                    
                    return null;
                }
                
                function detectarOrientacion(imgW, imgH) {
                    var ratio = imgW / imgH;
                    if (Math.abs(ratio - 1) < 0.1) {
                        return 'cuadrado'; // Aproximadamente 1:1
                    } else if (ratio > 1) {
                        return 'horizontal'; // Más ancho que alto
                    } else {
                        return 'vertical'; // Más alto que ancho
                    }
                }
                
                function actualizar() {
                    var marcoColor = buscarColorMarco($('#pa_marco').val());
                    var paspartuColor = buscarColorPaspartu($('#pa_paspartu').val());
                    var dobleMarcoInfo = buscarDobleMarco($('#pa_marco').val());
                    
                    console.log('[cuadros] DEBUG - Marco seleccionado:', $('#pa_marco').val());
                    console.log('[cuadros] DEBUG - Paspartú seleccionado:', $('#pa_paspartu').val());
                    console.log('[cuadros] DEBUG - Color marco encontrado:', marcoColor);
                    console.log('[cuadros] DEBUG - Color paspartú encontrado:', paspartuColor);
                    console.log('[cuadros] DEBUG - Doble marco info:', dobleMarcoInfo);
                    
                    var hayMarco = !!marcoColor;
                    var hayPaspartu = !!paspartuColor;
                    var hayDobleMarco = dobleMarcoInfo.enabled && hayMarco;
                    
                    var imgNaturalW = $productImage[0].naturalWidth || originalWidth;
                    var imgNaturalH = $productImage[0].naturalHeight || originalHeight;
                    
                    // Detectar orientación de la imagen
                    var orientacion = detectarOrientacion(imgNaturalW, imgNaturalH);
                    
                    // Obtener grosores según orientación
                    var bordeMarco = hayMarco ? GROSORES_MARCO[orientacion] : 0;
                    var bordePaspartu = hayPaspartu ? GROSORES_PASPARTU[orientacion] : 0;
                    
                    // Usar grosor personalizado si está configurado, sino usar por orientación
                    var bordeDobleMarco = 0;
                    if (hayDobleMarco) {
                        bordeDobleMarco = dobleMarcoInfo.grosor || GROSORES_DOBLE_MARCO[orientacion];
                    }
                    
                    var bordeTotal = bordeMarco + bordePaspartu;
                    
                    console.log('[cuadros] Orientación:', orientacion, 'Marco:', bordeMarco + 'px', 'Paspartú:', bordePaspartu + 'px', 'Doble marco:', bordeDobleMarco + 'px', 'personalizado:', !!dobleMarcoInfo.grosor);
                    console.log('[cuadros] Colores - Marco:', marcoColor, 'Paspartú:', paspartuColor, 'Doble marco:', dobleMarcoInfo.color);
                    
                    var imgRatio = imgNaturalW / imgNaturalH;
                    
                    // El wrapper máximo es el 85% del fondo
                    var maxWrapperW = originalWidth * FACTOR_ESCALA;
                    var maxWrapperH = originalHeight * FACTOR_ESCALA;
                    
                    // Espacio para la imagen = wrapper máximo menos bordes
                    var espacioW = Math.max(50, maxWrapperW - (bordeTotal * 2));
                    var espacioH = Math.max(50, maxWrapperH - (bordeTotal * 2));
                    var espacioRatio = espacioW / espacioH;
                    
                    // Calcular tamaño de imagen manteniendo proporción
                    var imgW, imgH;
                    if (imgRatio >= espacioRatio) {
                        imgW = espacioW;
                        imgH = imgW / imgRatio;
                    } else {
                        imgH = espacioH;
                        imgW = imgH * imgRatio;
                    }
                    
                    // El wrapper es la imagen + bordes
                    var wrapperW = imgW + (bordeTotal * 2);
                    var wrapperH = imgH + (bordeTotal * 2);
                    
                    // Asegurar que el wrapper no exceda el fondo
                    if (wrapperW > originalWidth) {
                        var scale = originalWidth / wrapperW;
                        wrapperW = originalWidth;
                        wrapperH = wrapperH * scale;
                        imgW = imgW * scale;
                        imgH = imgH * scale;
                    }
                    if (wrapperH > originalHeight) {
                        var scale = originalHeight / wrapperH;
                        wrapperH = originalHeight;
                        wrapperW = wrapperW * scale;
                        imgW = imgW * scale;
                        imgH = imgH * scale;
                    }
                    
                    // Redondear usando floor para el wrapper y ceil para la imagen (evita gaps)
                    wrapperW = Math.floor(wrapperW);
                    wrapperH = Math.floor(wrapperH);
                    imgW = Math.ceil(imgW);
                    imgH = Math.ceil(imgH);
                    
                    // Calcular posición centrada
                    var posX = Math.floor((wrapperW - imgW) / 2);
                    var posY = Math.floor((wrapperH - imgH) / 2);
                    
                    // Determinar el color de fondo del wrapper (para evitar líneas blancas)
                    var wrapperBg = 'transparent';
                    if (hayPaspartu) {
                        wrapperBg = paspartuColor;
                    } else if (hayMarco) {
                        wrapperBg = marcoColor;
                    }
                    
                    $wrapper.css({ 
                        'width': wrapperW + 'px', 
                        'height': wrapperH + 'px',
                        'background-color': wrapperBg
                    });
                    
                    // Aplicar marco principal
                    $marcoLayer.css({ 'border': hayMarco ? bordeMarco + 'px solid ' + marcoColor : 'none' });
                    
                    // Aplicar paspartú (capa de fondo completa)
                    if (hayPaspartu) {
                        $paspartuLayer.css({
                            'position': 'absolute',
                            'top': '0',
                            'left': '0',
                            'right': '0',
                            'bottom': '0',
                            'background-color': paspartuColor,
                            'z-index': 1
                        });
                    } else {
                        $paspartuLayer.css({ 'background-color': 'transparent' });
                    }
                    
                    // Aplicar doble marco (ENTRE paspartú y marco principal)
                    // Va en el borde INTERIOR del marco principal
                    if (hayDobleMarco) {
                        var dobleMarcoPos = bordeMarco - bordeDobleMarco;
                        $dobleMarcoLayer.css({
                            'position': 'absolute',
                            'top': dobleMarcoPos + 'px',
                            'left': dobleMarcoPos + 'px',
                            'right': dobleMarcoPos + 'px',
                            'bottom': dobleMarcoPos + 'px',
                            'border': bordeDobleMarco + 'px solid ' + dobleMarcoInfo.color,
                            'box-sizing': 'border-box',
                            'pointer-events': 'none',
                            'z-index': 4
                        });
                        
                        console.log('[cuadros] Doble marco - Pos:', dobleMarcoPos, 'Grosor:', bordeDobleMarco, 'Color:', dobleMarcoInfo.color);
                    } else {
                        $dobleMarcoLayer.css({ 'border': 'none' });
                    }
                    
                    $imagenLayer.css({ 
                        'top': posY + 'px', 
                        'left': posX + 'px', 
                        'width': imgW + 'px', 
                        'height': imgH + 'px' 
                    });
                }

                function actualizarTodo() {
                    aplicarFlujoSeleccion();
                    actualizar();
                    actualizarLightboxState();
                }
                
                // Eventos
                if ($variationsForm.length) {
                    $variationsForm.on('change', 'select', function() { setTimeout(actualizarTodo, 120); });
                }
                $(document).on('woocommerce_variation_has_changed', function() { setTimeout(actualizarTodo, 120); });
                $('.reset_variations').on('click', function() { setTimeout(actualizarTodo, 120); });
                
                // Ejecutar
                aplicarFlujoSeleccion(true);
                actualizar();
                
                // ===== LIGHTBOX =====
                var currentMarcoColor = null;
                var currentPaspartuColor = null;
                var currentDobleMarcoInfo = { enabled: false, color: null };
                var galleryImages = [];
                
                function actualizarLightboxState() {
                    currentMarcoColor = buscarColorMarco($('#pa_marco').val());
                    currentPaspartuColor = buscarColorPaspartu($('#pa_paspartu').val());
                    currentDobleMarcoInfo = buscarDobleMarco($('#pa_marco').val());
                    galleryImages = [];
                    $('.woocommerce-product-gallery__image').each(function() {
                        var $img = $(this).find('img');
                        var $link = $(this).find('a');
                        galleryImages.push({ src: $link.attr('href') || $img.attr('data-large_image') || $img.attr('src') });
                    });
                }
                
                function mostrarLightbox() {
                    var idx = 0;
                    var $overlay = $('<div id="cuadros-lightbox"></div>').css({
                        position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
                        background: 'rgba(0,0,0,0.9)', zIndex: 999999,
                        display: 'flex', alignItems: 'center', justifyContent: 'center'
                    });
                    var $container = $('<div></div>').css({ position: 'relative' });
                    var $closeBtn = $('<div>&times;</div>').css({ position: 'absolute', top: 20, right: 30, color: '#fff', fontSize: 40, cursor: 'pointer', zIndex: 10 });
                    var $prevBtn = $('<div>&#10094;</div>').css({ 
                        position: 'absolute', left: 20, top: '50%', transform: 'translateY(-50%)',
                        color: '#fff', fontSize: 50, cursor: 'pointer', zIndex: 10,
                        display: galleryImages.length > 1 ? 'block' : 'none',
                        padding: '10px',
                        userSelect: 'none'
                    });
                    var $nextBtn = $('<div>&#10095;</div>').css({ 
                        position: 'absolute', right: 20, top: '50%', transform: 'translateY(-50%)',
                        color: '#fff', fontSize: 50, cursor: 'pointer', zIndex: 10,
                        display: galleryImages.length > 1 ? 'block' : 'none',
                        padding: '10px',
                        userSelect: 'none'
                    });
                    var $counter = $('<div></div>').css({
                        position: 'absolute', bottom: 20, left: '50%', transform: 'translateX(-50%)',
                        color: '#fff', fontSize: 16, zIndex: 10
                    });
                    
                    $overlay.append($container, $closeBtn, $prevBtn, $nextBtn, $counter);
                    $('body').append($overlay);
                    
                    function mostrar(i) {
                        if (i < 0) i = galleryImages.length - 1;
                        if (i >= galleryImages.length) i = 0;
                        idx = i;
                        $container.empty();
                        $counter.text((i + 1) + ' / ' + galleryImages.length);
                        
                        var src = galleryImages[i] ? galleryImages[i].src : '';
                        
                        // Solo mostrar marco/paspartú/doble marco en la PRIMERA imagen del lightbox
                        var hayM = (i === 0) && currentMarcoColor;
                        var hayP = (i === 0) && currentPaspartuColor;
                        var hayDM = (i === 0) && currentDobleMarcoInfo.enabled && hayM;
                        
                        // Tamaño máximo - 80% para mobile
                        var maxW = window.innerWidth * 0.8;
                        var maxH = window.innerHeight * 0.75;
                        
                        // Limpiar estilos del container para imágenes sin marco
                        $container.css({
                            width: 'auto',
                            height: 'auto',
                            background: 'none',
                            border: 'none',
                            boxShadow: 'none'
                        });
                        
                        if (hayM || hayP) {
                            var tmp = new Image();
                            tmp.onload = function() {
                                // Detectar orientación para el lightbox
                                var orientacionLb = detectarOrientacion(tmp.width, tmp.height);
                                var bM = hayM ? GROSORES_MARCO[orientacionLb] : 0;
                                var bP = hayP ? GROSORES_PASPARTU[orientacionLb] : 0;
                                
                                // Usar grosor personalizado si está configurado
                                var bDM = 0;
                                if (hayDM) {
                                    bDM = currentDobleMarcoInfo.grosor || GROSORES_DOBLE_MARCO[orientacionLb];
                                }
                                
                                var bT = bM + bP;
                                
                                var r = tmp.width / tmp.height;
                                
                                // Calcular espacio disponible para la imagen (restando bordes)
                                var espacioW = maxW - (bT * 2);
                                var espacioH = maxH - (bT * 2);
                                var espacioRatio = espacioW / espacioH;
                                
                                // Calcular tamaño de imagen manteniendo proporción (igual que producto)
                                var imgW, imgH;
                                if (r >= espacioRatio) {
                                    imgW = espacioW;
                                    imgH = imgW / r;
                                } else {
                                    imgH = espacioH;
                                    imgW = imgH * r;
                                }
                                
                                // El contenedor es la imagen + bordes totales
                                var containerW = imgW + (bT * 2);
                                var containerH = imgH + (bT * 2);
                                
                                // Redondear (igual que producto)
                                containerW = Math.floor(containerW);
                                containerH = Math.floor(containerH);
                                imgW = Math.ceil(imgW);
                                imgH = Math.ceil(imgH);
                                
                                // Calcular posición centrada de la imagen (igual que en vista producto)
                                var posX = Math.floor((containerW - imgW) / 2);
                                var posY = Math.floor((containerH - imgH) / 2);
                                
                                // Contenedor sin border (el marco será una capa separada)
                                $container.css({ 
                                    width: containerW + 'px', 
                                    height: containerH + 'px', 
                                    background: 'transparent',
                                    boxShadow: '-4px 4px 12px rgba(0,0,0,0.5)',
                                    position: 'relative'
                                });
                                
                                // Aplicar marco principal como capa separada (igual que producto)
                                if (hayM) {
                                    $('<div></div>').css({
                                        position: 'absolute',
                                        top: '0',
                                        left: '0',
                                        right: '0',
                                        bottom: '0',
                                        border: bM + 'px solid ' + currentMarcoColor,
                                        boxSizing: 'border-box',
                                        pointerEvents: 'none',
                                        zIndex: 3
                                    }).appendTo($container);
                                }
                                
                                // Aplicar paspartú como capa de fondo en lightbox
                                if (hayP) {
                                    $('<div></div>').css({
                                        position: 'absolute',
                                        top: '0',
                                        left: '0',
                                        right: '0',
                                        bottom: '0',
                                        backgroundColor: currentPaspartuColor,
                                        zIndex: 1
                                    }).appendTo($container);
                                }
                                
                                // Aplicar doble marco en lightbox (ENTRE paspartú y marco)
                                if (hayDM) {
                                    var dobleMarcoPos = bM - bDM;
                                    $('<div></div>').css({
                                        position: 'absolute',
                                        top: dobleMarcoPos + 'px',
                                        left: dobleMarcoPos + 'px',
                                        right: dobleMarcoPos + 'px',
                                        bottom: dobleMarcoPos + 'px',
                                        border: bDM + 'px solid ' + currentDobleMarcoInfo.color,
                                        boxSizing: 'border-box',
                                        pointerEvents: 'none',
                                        zIndex: 4
                                    }).appendTo($container);
                                }
                                
                                // Imagen centrada usando posX y posY (igual que vista producto)
                                $('<div></div>').css({ 
                                    position: 'absolute', 
                                    top: posY + 'px', 
                                    left: posX + 'px', 
                                    width: imgW + 'px', 
                                    height: imgH + 'px', 
                                    overflow: 'hidden',
                                    zIndex: 2
                                }).append(
                                    $('<img>').attr('src', src).css({ width: '100%', height: '100%', objectFit: 'fill' })
                                ).appendTo($container);
                            };
                            tmp.src = src;
                        } else {
                            // Sin marco ni paspartú - imagen limpia
                            $('<img>').attr('src', src).css({ 
                                maxWidth: maxW + 'px', 
                                maxHeight: maxH + 'px', 
                                display: 'block' 
                            }).appendTo($container);
                        }
                    }
                    
                    mostrar(0);
                    $closeBtn.on('click', function() { $overlay.remove(); $(document).off('keydown.cuadrosLb'); });
                    $prevBtn.on('click', function(e) { e.stopPropagation(); mostrar(idx - 1); });
                    $nextBtn.on('click', function(e) { e.stopPropagation(); mostrar(idx + 1); });
                    $overlay.on('click', function(e) { if (e.target === $overlay[0]) { $overlay.remove(); $(document).off('keydown.cuadrosLb'); } });
                    
                    // Soporte para teclado
                    $(document).on('keydown.cuadrosLb', function(e) {
                        if (e.keyCode === 27) { $overlay.remove(); $(document).off('keydown.cuadrosLb'); }
                        else if (e.keyCode === 37) mostrar(idx - 1);
                        else if (e.keyCode === 39) mostrar(idx + 1);
                    });
                }
                
                $fondoWrapper.on('click', function(e) {
                    // Solo activar lightbox si hay marco o paspartú seleccionado
                    if (currentMarcoColor || currentPaspartuColor) {
                        e.preventDefault();
                        e.stopPropagation();
                        mostrarLightbox();
                    }
                });
                
                actualizarLightboxState();
            }
        });
        </script>
        <?php
    }
}
