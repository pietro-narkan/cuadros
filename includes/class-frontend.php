<?php
/**
 * Clase para la lógica frontend del plugin Cuadros
 */
class Cuadros_Frontend {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_shortcode('cuadros_visualizador', array($this, 'render_visualizador_shortcode'));
        
        // Script en el footer para manejar todo
        add_action('wp_footer', array($this, 'output_frontend_script_auto'));
    }
    
    /**
     * Enqueue scripts y estilos frontend
     */
    public function enqueue_frontend_scripts() {
        if (!is_product()) {
            return;
        }
        
        wp_enqueue_style(
            'cuadros-frontend-style',
            CUADROS_ASSETS_URL . 'css/frontend.css',
            array(),
            CUADROS_VERSION
        );
        
        wp_enqueue_script('jquery');
    }
    
    /**
     * Renderizar visualizador via shortcode
     */
    public function render_visualizador_shortcode($atts = array()) {
        // Verificar si estamos en una página de producto
        if (!is_product()) {
            return '<p class="cuadros-notice">' . __('El visualizador de cuadros solo funciona en páginas de producto.', 'cuadros') . '</p>';
        }
        
        global $product;
        
        if (!$product || !$product->is_type('variable')) {
            return '<p class="cuadros-notice">' . __('El visualizador de cuadros solo funciona con productos variables.', 'cuadros') . '</p>';
        }
        
        // Verificar si el producto tiene atributos de marco y paspartú
        $attributes = $product->get_attributes();
        $has_marco = isset($attributes['pa_marco']);
        $has_paspartu = isset($attributes['pa_paspartu']);
        
        if (!$has_marco && !$has_paspartu) {
            return '<p class="cuadros-notice">' . __('Este producto no tiene atributos de marco o paspartú configurados.', 'cuadros') . '</p>';
        }
        
        // Enqueue scripts necesarios
        $this->enqueue_frontend_scripts();
        
        // Agregar divs para las capas
        $output = '<div id="cuadros-visualizador-container">';
        $output .= '<div id="layer-marco" class="custom-overlay-layer"></div>';
        $output .= '<div id="layer-paspartu" class="custom-overlay-layer"></div>';
        $output .= '</div>';
        
        // Solo incluir el script si no está ya cargado automáticamente
        // Verificar si ya hay capas en la página (modo automático)
        if (!did_action('woocommerce_before_single_product')) {
            // Output del script
            ob_start();
            $this->output_frontend_script();
            $output .= ob_get_clean();
        } else {
            // Modo automático ya está activo, solo agregar mensaje
            $output .= '<script type="text/javascript">
                console.log("[Cuadros] Shortcode detectado en modo automático.");
            </script>';
        }
        
        // Agregar mensaje de depuración en la consola
        $output .= '<script type="text/javascript">
            console.log("[Cuadros] Shortcode cargado correctamente.");
            console.log("[Cuadros] Contenedor ID: #cuadros-visualizador-container");
            console.log("[Cuadros] Posición en DOM:", document.getElementById("cuadros-visualizador-container") ? "ENCONTRADO" : "NO ENCONTRADO");
            if (document.getElementById("cuadros-visualizador-container")) {
                var rect = document.getElementById("cuadros-visualizador-container").getBoundingClientRect();
                console.log("[Cuadros] Posición (x, y):", rect.left + "px, " + rect.top + "px");
                console.log("[Cuadros] Dimensión (ancho x alto):", rect.width + "px x " + rect.height + "px");
                console.log("[Cuadros] Estilos aplicados:", window.getComputedStyle(document.getElementById("cuadros-visualizador-container")));
            }
            // Verificar si hay capas
            console.log("[Cuadros] Capa marco:", document.getElementById("layer-marco") ? "ENCONTRADA" : "NO ENCONTRADA");
            console.log("[Cuadros] Capa paspartú:", document.getElementById("layer-paspartu") ? "ENCONTRADA" : "NO ENCONTRADA");
        </script>';
        
        return $output;
    }
    
    /**
     * Output del script frontend automático (sin shortcode)
     */
    public function output_frontend_script_auto() {
        if (!is_product()) {
            return;
        }
        
        global $product;
        
        if (!$product || !$product->is_type('variable')) {
            return;
        }
        
        // Verificar si el producto tiene atributos de marco y paspartú
        $attributes = $product->get_attributes();
        $has_marco = isset($attributes['pa_marco']);
        $has_paspartu = isset($attributes['pa_paspartu']);
        
        if (!$has_marco && !$has_paspartu) {
            return;
        }
        
        // Output del script
        $this->output_frontend_script();
    }
    
    /**
     * Output del script frontend con la lógica de visualización
     */
    public function output_frontend_script() {
        // Este método ahora se llama tanto automáticamente como desde el shortcode
        
        $settings = get_option('cuadros_settings', array());
        
        // Preparar datos para JavaScript
        $marco_images = isset($settings['marco_images']) ? $settings['marco_images'] : array();
        $paspartu_colors = isset($settings['paspartu_colors']) ? $settings['paspartu_colors'] : array();
        $dimensions = isset($settings['dimensions']) ? $settings['dimensions'] : array(
            'vertical' => array('width' => 60, 'height' => 80),
            'horizontal' => array('width' => 80, 'height' => 60),
            '1:1' => array('width' => 80, 'height' => 80)
        );
        
        // Estructurar imágenes de marcos para JS (claves en minúsculas para búsqueda insensible)
        $urls_marcos = array();
        foreach ($marco_images as $marco) {
            // Soportar tanto 'modelo' (nuevo) como 'color' (antiguo)
            $key = isset($marco['modelo']) ? $marco['modelo'] : (isset($marco['color']) ? $marco['color'] : null);
            
            if ($key && isset($marco['orientation']) && isset($marco['url'])) {
                // Usar minúsculas para la clave
                $normalized_key = strtolower($key);
                if (!isset($urls_marcos[$normalized_key])) {
                    $urls_marcos[$normalized_key] = array();
                }
                // Guardar la URL directamente
                $urls_marcos[$normalized_key][$marco['orientation']] = $marco['url'];
            }
        }
        
        // Evitar cargar el script dos veces
        if (defined('CUADROS_SCRIPT_LOADED')) {
            return;
        }
        define('CUADROS_SCRIPT_LOADED', true);
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            
            // 1. CONFIGURACIÓN DE DATOS
            var urlsMarcosRaw = <?php echo json_encode($urls_marcos); ?>;
            var coloresPaspartu = <?php echo json_encode($paspartu_colors); ?>;
            var dimensiones = <?php echo json_encode($dimensions); ?>;
            
            var urlsMarcos = {};
            for (var key in urlsMarcosRaw) {
                urlsMarcos[key] = {};
                for (var orient in urlsMarcosRaw[key]) {
                    urlsMarcos[key][orient] = urlsMarcosRaw[key][orient];
                }
            }
            
            console.log('[cuadros] Marcos disponibles:', urlsMarcos);
            console.log('[cuadros] Paspartús disponibles:', coloresPaspartu);
            
            // 2. CREAR LAS CAPAS DINÁMICAMENTE
            // Buscar la imagen del producto
            var $productImage = $('.woocommerce-product-gallery__image').first().find('img').first();
            
            if ($productImage.length === 0) {
                console.log('[cuadros] No se encontró imagen del producto');
                return;
            }
            
            // Buscar el contenedor padre de la imagen (el <a> o el figure)
            var $imageLink = $productImage.closest('a');
            var $container = $imageLink.length ? $imageLink : $productImage.parent();
            
            console.log('[cuadros] Contenedor encontrado:', $container[0].tagName);
            
            // Configurar el contenedor
            $container.css({
                'position': 'relative',
                'display': 'block'
            });
            
            // Crear las capas si no existen
            if ($('#layer-marco').length === 0) {
                $container.prepend('<div id="layer-marco" class="custom-overlay-layer"></div>');
            }
            if ($('#layer-paspartu').length === 0) {
                $container.prepend('<div id="layer-paspartu" class="custom-overlay-layer"></div>');
            }
            
            var $divMarco = $('#layer-marco');
            var $divPaspartu = $('#layer-paspartu');
            
            // Asegurar que las capas estén en el contenedor correcto
            if (!$divMarco.parent().is($container)) {
                $divMarco.detach().prependTo($container);
            }
            if (!$divPaspartu.parent().is($container)) {
                $divPaspartu.detach().prependTo($container);
            }
            
            // Estilos base para las capas - DETRÁS de la imagen
            $divPaspartu.css({
                'position': 'absolute',
                'pointer-events': 'none',
                'z-index': '1',
                'opacity': '0',
                'transition': 'opacity 0.3s'
            });
            
            $divMarco.css({
                'position': 'absolute',
                'pointer-events': 'none',
                'z-index': '2',
                'opacity': '0',
                'transition': 'opacity 0.3s',
                'background-size': '100% 100%',
                'background-repeat': 'no-repeat'
            });
            
            // La imagen debe estar ENCIMA
            $productImage.css({
                'position': 'relative',
                'z-index': '10'
            });
            
            console.log('[cuadros] Capas creadas y configuradas');
            
            // 3. FUNCIONES AUXILIARES
            function encontrarTextoTamano() {
                var texto = "";
                $('form.variations_form select').each(function() {
                    var opt = $(this).find('option:selected').text();
                    if (opt && opt.match(/\d+\s*[xX]\s*\d+/)) {
                        texto = opt;
                        return false;
                    }
                });
                return texto;
            }
            
            function obtenerOrientacion(texto) {
                if (!texto) return null;
                var match = texto.match(/(\d+)\s*[xX]\s*(\d+)/);
                if (match) {
                    var ancho = parseInt(match[1]);
                    var alto = parseInt(match[2]);
                    console.log('[cuadros] Detectando orientación - ancho:', ancho, 'alto:', alto);
                    if (ancho === alto) {
                        console.log('[cuadros] Orientación detectada: 1:1 (cuadrado)');
                        return '1:1';
                    }
                    var orient = (ancho < alto) ? 'vertical' : 'horizontal';
                    console.log('[cuadros] Orientación detectada:', orient);
                    return orient;
                }
                return null;
            }
            
            // 4. FUNCIÓN PRINCIPAL
            function actualizarCapas() {
                var marcoVal = $('#pa_marco').val();
                var paspartuVal = $('#pa_paspartu').val();
                var tamanoTexto = encontrarTextoTamano();
                var orientacion = obtenerOrientacion(tamanoTexto);
                
                // Obtener dimensiones actuales de la imagen
                var imgWidth = $productImage.width();
                var imgHeight = $productImage.height();
                
                // Si no se detectó orientación del texto, detectar por dimensiones de imagen
                if (!orientacion && imgWidth > 0 && imgHeight > 0) {
                    var ratio = imgWidth / imgHeight;
                    if (ratio > 0.95 && ratio < 1.05) {
                        orientacion = '1:1';
                        console.log('[cuadros] Orientación detectada por imagen: 1:1 (cuadrado)');
                    } else if (ratio < 1) {
                        orientacion = 'vertical';
                    } else {
                        orientacion = 'horizontal';
                    }
                }
                
                var estilo = orientacion || 'vertical';
                
                console.log('[cuadros] actualizarCapas:', {marco: marcoVal, paspartu: paspartuVal, orientation: estilo, tamanoTexto: tamanoTexto});
                
                console.log('[cuadros] Dimensiones imagen:', imgWidth, 'x', imgHeight);
                
                if (imgWidth <= 0 || imgHeight <= 0) {
                    console.log('[cuadros] Imagen sin dimensiones válidas');
                    return;
                }
                
                // Calcular dimensiones del marco según orientación
                var marcoWidthPercent, marcoHeightPercent;
                if (dimensiones[estilo]) {
                    marcoWidthPercent = dimensiones[estilo].width;
                    marcoHeightPercent = dimensiones[estilo].height;
                } else if (estilo === '1:1') {
                    marcoWidthPercent = 80;
                    marcoHeightPercent = 80;
                } else if (estilo === 'vertical') {
                    marcoWidthPercent = 60;
                    marcoHeightPercent = 80;
                } else {
                    marcoWidthPercent = 80;
                    marcoHeightPercent = 60;
                }
                
                // El paspartú debe ser ligeramente más pequeño que el marco para quedar dentro
                var paspartuWidthPercent = marcoWidthPercent - 2.5;
                var paspartuHeightPercent = marcoHeightPercent - 2.5;
                
                // Calcular dimensiones en píxeles
                var marcoWidth, marcoHeight, paspartuWidth, paspartuHeight;
                
                if (estilo === '1:1') {
                    // Para formato cuadrado, usar el lado menor como base para mantener proporción 1:1
                    var minSide = Math.min(imgWidth, imgHeight);
                    marcoWidth = (minSide * marcoWidthPercent) / 100;
                    marcoHeight = marcoWidth; // Forzar cuadrado
                    paspartuWidth = (minSide * paspartuWidthPercent) / 100;
                    paspartuHeight = paspartuWidth; // Forzar cuadrado
                } else {
                    marcoWidth = (imgWidth * marcoWidthPercent) / 100;
                    marcoHeight = (imgHeight * marcoHeightPercent) / 100;
                    paspartuWidth = (imgWidth * paspartuWidthPercent) / 100;
                    paspartuHeight = (imgHeight * paspartuHeightPercent) / 100;
                }
                
                // Calcular posiciones centradas (relativas al contenedor, que es el mismo que la imagen)
                var marcoLeft = (imgWidth - marcoWidth) / 2;
                var marcoTop = (imgHeight - marcoHeight) / 2;
                var paspartuLeft = (imgWidth - paspartuWidth) / 2;
                var paspartuTop = (imgHeight - paspartuHeight) / 2;
                
                console.log('[cuadros] Posiciones:', {
                    marcoLeft: marcoLeft,
                    marcoTop: marcoTop,
                    marcoWidth: marcoWidth,
                    marcoHeight: marcoHeight
                });
                
                // Aplicar posiciones
                $divMarco.css({
                    'width': marcoWidth + 'px',
                    'height': marcoHeight + 'px',
                    'left': marcoLeft + 'px',
                    'top': marcoTop + 'px'
                });
                
                $divPaspartu.css({
                    'width': paspartuWidth + 'px',
                    'height': paspartuHeight + 'px',
                    'left': paspartuLeft + 'px',
                    'top': paspartuTop + 'px'
                });
                
                // Buscar y mostrar marco
                var marcoEncontrado = null;
                if (marcoVal) {
                    var marcoValNorm = marcoVal.toLowerCase().replace(/-/g, ' ');
                    
                    console.log('[cuadros] Buscando marco:', marcoValNorm, 'con orientación:', estilo);
                    
                    // Buscar coincidencia exacta o parcial
                    for (var key in urlsMarcos) {
                        var keyNorm = key.toLowerCase().replace(/-/g, ' ');
                        if (keyNorm === marcoValNorm || keyNorm.includes(marcoValNorm) || marcoValNorm.includes(keyNorm)) {
                            console.log('[cuadros] Marco encontrado:', key, 'orientaciones disponibles:', Object.keys(urlsMarcos[key]));
                            if (urlsMarcos[key][estilo]) {
                                marcoEncontrado = urlsMarcos[key][estilo];
                                console.log('[cuadros] Usando orientación:', estilo);
                                break;
                            }
                        }
                    }
                }
                
                if (marcoEncontrado) {
                    console.log('[cuadros] Mostrando marco:', marcoEncontrado);
                    $divMarco.css({
                        'background-image': 'url(' + marcoEncontrado + ')',
                        'opacity': '1'
                    });
                } else {
                    $divMarco.css('opacity', '0');
                }
                
                // Buscar y mostrar paspartú
                var paspartuEncontrado = null;
                if (paspartuVal) {
                    var paspartuValNorm = paspartuVal.toLowerCase();
                    for (var key in coloresPaspartu) {
                        if (key.toLowerCase() === paspartuValNorm) {
                            paspartuEncontrado = coloresPaspartu[key];
                            break;
                        }
                    }
                }
                
                if (paspartuEncontrado) {
                    console.log('[cuadros] Mostrando paspartú:', paspartuEncontrado);
                    $divPaspartu.css({
                        'background-color': paspartuEncontrado,
                        'opacity': '1'
                    });
                } else {
                    $divPaspartu.css('opacity', '0');
                }
            }
            
            // 5. LISTENERS
            $('form.variations_form').on('change', 'select', function() {
                setTimeout(actualizarCapas, 100);
            });
            
            $(document).on('woocommerce_variation_has_changed', function() {
                setTimeout(actualizarCapas, 100);
            });
            
            $('.reset_variations').on('click', function() {
                $divMarco.css('opacity', '0');
                $divPaspartu.css('opacity', '0');
            });
            
            // Recalcular al cambiar tamaño de ventana
            $(window).on('resize', function() {
                setTimeout(actualizarCapas, 100);
            });
            
            // Ejecutar cuando la imagen cargue
            $productImage.on('load', function() {
                actualizarCapas();
            });
            
            // Ejecutar inicialmente
            setTimeout(actualizarCapas, 500);
            
            // 6. LIGHTBOX CON MARCOS Y PASPARTÚS
            // Variables para almacenar el estado actual
            var currentMarcoUrl = null;
            var currentPaspartuColor = null;
            var currentEstilo = 'vertical';
            
            // Función para generar imagen compuesta con Canvas
            function generarImagenCompuesta(callback) {
                var canvas = document.createElement('canvas');
                var ctx = canvas.getContext('2d');
                
                // Obtener la imagen original en alta resolución
                var imgSrc = $productImage.attr('data-large_image') || $productImage.attr('data-src') || $productImage.attr('src');
                
                var img = new Image();
                img.crossOrigin = 'anonymous';
                
                img.onload = function() {
                    var imgWidth = img.width;
                    var imgHeight = img.height;
                    
                    // Detectar orientación
                    var ratio = imgWidth / imgHeight;
                    var estilo;
                    if (ratio > 0.95 && ratio < 1.05) {
                        estilo = '1:1';
                    } else if (ratio < 1) {
                        estilo = 'vertical';
                    } else {
                        estilo = 'horizontal';
                    }
                    
                    // Obtener dimensiones del marco
                    var marcoWidthPercent, marcoHeightPercent;
                    if (dimensiones[estilo]) {
                        marcoWidthPercent = dimensiones[estilo].width;
                        marcoHeightPercent = dimensiones[estilo].height;
                    } else if (estilo === '1:1') {
                        marcoWidthPercent = 80;
                        marcoHeightPercent = 80;
                    } else if (estilo === 'vertical') {
                        marcoWidthPercent = 60;
                        marcoHeightPercent = 80;
                    } else {
                        marcoWidthPercent = 80;
                        marcoHeightPercent = 60;
                    }
                    
                    var paspartuWidthPercent = marcoWidthPercent - 2.5;
                    var paspartuHeightPercent = marcoHeightPercent - 2.5;
                    
                    // Calcular dimensiones en píxeles
                    var marcoWidth, marcoHeight, paspartuWidth, paspartuHeight;
                    
                    if (estilo === '1:1') {
                        var minSide = Math.min(imgWidth, imgHeight);
                        marcoWidth = (minSide * marcoWidthPercent) / 100;
                        marcoHeight = marcoWidth;
                        paspartuWidth = (minSide * paspartuWidthPercent) / 100;
                        paspartuHeight = paspartuWidth;
                    } else {
                        marcoWidth = (imgWidth * marcoWidthPercent) / 100;
                        marcoHeight = (imgHeight * marcoHeightPercent) / 100;
                        paspartuWidth = (imgWidth * paspartuWidthPercent) / 100;
                        paspartuHeight = (imgHeight * paspartuHeightPercent) / 100;
                    }
                    
                    // Posiciones centradas
                    var marcoLeft = (imgWidth - marcoWidth) / 2;
                    var marcoTop = (imgHeight - marcoHeight) / 2;
                    var paspartuLeft = (imgWidth - paspartuWidth) / 2;
                    var paspartuTop = (imgHeight - paspartuHeight) / 2;
                    
                    // Configurar canvas
                    canvas.width = imgWidth;
                    canvas.height = imgHeight;
                    
                    // 1. Dibujar paspartú (si existe)
                    if (currentPaspartuColor) {
                        ctx.fillStyle = currentPaspartuColor;
                        ctx.fillRect(paspartuLeft, paspartuTop, paspartuWidth, paspartuHeight);
                    }
                    
                    // 2. Dibujar imagen del producto
                    ctx.drawImage(img, 0, 0, imgWidth, imgHeight);
                    
                    // 3. Dibujar marco (si existe)
                    if (currentMarcoUrl) {
                        var marcoImg = new Image();
                        marcoImg.crossOrigin = 'anonymous';
                        
                        marcoImg.onload = function() {
                            ctx.drawImage(marcoImg, marcoLeft, marcoTop, marcoWidth, marcoHeight);
                            callback(canvas.toDataURL('image/png'));
                        };
                        
                        marcoImg.onerror = function() {
                            console.log('[cuadros] Error cargando marco para lightbox');
                            callback(canvas.toDataURL('image/png'));
                        };
                        
                        marcoImg.src = currentMarcoUrl;
                    } else {
                        callback(canvas.toDataURL('image/png'));
                    }
                };
                
                img.onerror = function() {
                    console.log('[cuadros] Error cargando imagen para lightbox');
                    callback(null);
                };
                
                img.src = imgSrc;
            }
            
            // Actualizar variables de estado cuando cambian los valores
            function actualizarEstadoLightbox() {
                var marcoVal = $('#pa_marco').val();
                var paspartuVal = $('#pa_paspartu').val();
                
                // Obtener orientación actual
                var imgWidth = $productImage.width();
                var imgHeight = $productImage.height();
                var ratio = imgWidth / imgHeight;
                
                if (ratio > 0.95 && ratio < 1.05) {
                    currentEstilo = '1:1';
                } else if (ratio < 1) {
                    currentEstilo = 'vertical';
                } else {
                    currentEstilo = 'horizontal';
                }
                
                // Buscar URL del marco
                currentMarcoUrl = null;
                if (marcoVal) {
                    var marcoValNorm = marcoVal.toLowerCase().replace(/-/g, ' ');
                    for (var key in urlsMarcos) {
                        var keyNorm = key.toLowerCase().replace(/-/g, ' ');
                        if (keyNorm === marcoValNorm || keyNorm.includes(marcoValNorm) || marcoValNorm.includes(keyNorm)) {
                            if (urlsMarcos[key][currentEstilo]) {
                                currentMarcoUrl = urlsMarcos[key][currentEstilo];
                                break;
                            }
                        }
                    }
                }
                
                // Buscar color del paspartú
                currentPaspartuColor = null;
                if (paspartuVal) {
                    var paspartuValNorm = paspartuVal.toLowerCase();
                    for (var key in coloresPaspartu) {
                        if (key.toLowerCase() === paspartuValNorm) {
                            currentPaspartuColor = coloresPaspartu[key];
                            break;
                        }
                    }
                }
            }
            
            // Interceptar clic en la imagen para el lightbox
            $container.on('click', 'a', function(e) {
                // Solo interceptar si hay marco o paspartú seleccionado
                if (!currentMarcoUrl && !currentPaspartuColor) {
                    return; // Dejar comportamiento normal
                }
                
                e.preventDefault();
                e.stopPropagation();
                
                console.log('[cuadros] Generando imagen compuesta para lightbox...');
                
                // Mostrar indicador de carga
                var $loading = $('<div id="cuadros-lightbox-loading" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:99999;display:flex;align-items:center;justify-content:center;"><div style="color:white;font-size:18px;">Generando vista previa...</div></div>');
                $('body').append($loading);
                
                generarImagenCompuesta(function(dataUrl) {
                    $loading.remove();
                    
                    if (!dataUrl) {
                        console.log('[cuadros] Error generando imagen, usando lightbox normal');
                        window.location.href = $productImage.attr('data-large_image') || $productImage.attr('src');
                        return;
                    }
                    
                    // Crear lightbox personalizado
                    var $lightbox = $('<div id="cuadros-lightbox" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:99999;display:flex;align-items:center;justify-content:center;cursor:pointer;">' +
                        '<img src="' + dataUrl + '" style="max-width:90%;max-height:90%;object-fit:contain;box-shadow:0 0 30px rgba(0,0,0,0.5);">' +
                        '<div style="position:absolute;top:20px;right:30px;color:white;font-size:40px;cursor:pointer;line-height:1;">&times;</div>' +
                        '</div>');
                    
                    $('body').append($lightbox);
                    
                    // Cerrar al hacer clic
                    $lightbox.on('click', function() {
                        $lightbox.remove();
                    });
                    
                    // Cerrar con ESC
                    $(document).on('keydown.cuadrosLightbox', function(e) {
                        if (e.keyCode === 27) {
                            $lightbox.remove();
                            $(document).off('keydown.cuadrosLightbox');
                        }
                    });
                    
                    console.log('[cuadros] Lightbox mostrado con imagen compuesta');
                });
            });
            
            // Actualizar estado cuando cambian las selecciones
            $('form.variations_form').on('change', 'select', function() {
                setTimeout(actualizarEstadoLightbox, 150);
            });
            
            $(document).on('woocommerce_variation_has_changed', function() {
                setTimeout(actualizarEstadoLightbox, 150);
            });
            
            // Inicializar estado
            setTimeout(actualizarEstadoLightbox, 600);
        });
        </script>
        <?php
    }
}
