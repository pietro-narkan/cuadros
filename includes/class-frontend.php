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
            
            // 6. LIGHTBOX PERSONALIZADO CON MARCOS Y PASPARTÚS
            var currentMarcoUrl = null;
            var currentPaspartuColor = null;
            var currentEstilo = 'vertical';
            var lightboxActivo = false;
            var currentSlideIndex = 0;
            var galleryImages = [];
            
            // Obtener todas las imágenes de la galería
            function obtenerImagenesGaleria() {
                galleryImages = [];
                $('.woocommerce-product-gallery__image').each(function(index) {
                    var $img = $(this).find('img');
                    var $link = $(this).find('a');
                    galleryImages.push({
                        index: index,
                        thumb: $img.attr('src'),
                        large: $link.attr('href') || $img.attr('data-large_image') || $img.attr('src'),
                        alt: $img.attr('alt') || ''
                    });
                });
                console.log('[cuadros] Imágenes de galería:', galleryImages.length);
            }
            
            // Actualizar variables de estado
            function actualizarEstadoLightbox() {
                var marcoVal = $('#pa_marco').val();
                var paspartuVal = $('#pa_paspartu').val();
                
                // Usar la misma lógica de detección de orientación que actualizarCapas
                var tamanoTexto = encontrarTextoTamano();
                var orientacion = obtenerOrientacion(tamanoTexto);
                
                // Si no se detectó del texto, usar dimensiones de imagen
                if (!orientacion) {
                    var imgWidth = $productImage.width();
                    var imgHeight = $productImage.height();
                    if (imgWidth > 0 && imgHeight > 0) {
                        var ratio = imgWidth / imgHeight;
                        if (ratio > 0.95 && ratio < 1.05) {
                            orientacion = '1:1';
                        } else if (ratio < 1) {
                            orientacion = 'vertical';
                        } else {
                            orientacion = 'horizontal';
                        }
                    }
                }
                
                currentEstilo = orientacion || 'vertical';
                console.log('[cuadros] Lightbox - Orientación detectada:', currentEstilo, 'desde texto:', tamanoTexto);
                
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
                
                // Actualizar lista de imágenes
                obtenerImagenesGaleria();
            }
            
            // Función para mostrar lightbox personalizado con navegación
            function mostrarLightboxCuadros(startIndex) {
                lightboxActivo = true;
                currentSlideIndex = startIndex || 0;
                
                // Crear overlay
                var $overlay = $('<div id="cuadros-lightbox-overlay"></div>').css({
                    position: 'fixed',
                    top: 0,
                    left: 0,
                    width: '100%',
                    height: '100%',
                    backgroundColor: 'rgba(0,0,0,0.95)',
                    zIndex: 9999999,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                });
                
                // Contenedor de la imagen
                var $imgContainer = $('<div id="cuadros-lightbox-container"></div>').css({
                    position: 'relative',
                    maxWidth: '85vw',
                    maxHeight: '85vh',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                });
                
                // Botón cerrar
                var $closeBtn = $('<div class="cuadros-lb-close">&times;</div>').css({
                    position: 'absolute',
                    top: '15px',
                    right: '20px',
                    color: 'white',
                    fontSize: '45px',
                    cursor: 'pointer',
                    zIndex: 10000001,
                    lineHeight: 1,
                    padding: '5px 15px'
                });
                
                // Flechas de navegación
                var $prevBtn = $('<div class="cuadros-lb-prev">&#10094;</div>').css({
                    position: 'absolute',
                    left: '15px',
                    top: '50%',
                    transform: 'translateY(-50%)',
                    color: 'white',
                    fontSize: '50px',
                    cursor: 'pointer',
                    zIndex: 10000001,
                    padding: '15px',
                    userSelect: 'none',
                    opacity: galleryImages.length > 1 ? 1 : 0
                });
                
                var $nextBtn = $('<div class="cuadros-lb-next">&#10095;</div>').css({
                    position: 'absolute',
                    right: '15px',
                    top: '50%',
                    transform: 'translateY(-50%)',
                    color: 'white',
                    fontSize: '50px',
                    cursor: 'pointer',
                    zIndex: 10000001,
                    padding: '15px',
                    userSelect: 'none',
                    opacity: galleryImages.length > 1 ? 1 : 0
                });
                
                // Contador
                var $counter = $('<div class="cuadros-lb-counter"></div>').css({
                    position: 'absolute',
                    top: '20px',
                    left: '20px',
                    color: 'white',
                    fontSize: '16px',
                    zIndex: 10000001
                });
                
                $overlay.append($imgContainer).append($closeBtn).append($prevBtn).append($nextBtn).append($counter);
                $('body').append($overlay);
                
                // Función para mostrar imagen específica
                function mostrarImagen(index) {
                    if (index < 0) index = galleryImages.length - 1;
                    if (index >= galleryImages.length) index = 0;
                    currentSlideIndex = index;
                    
                    var imgData = galleryImages[index];
                    $imgContainer.empty();
                    
                    // Actualizar contador
                    $counter.text((index + 1) + ' / ' + galleryImages.length);
                    
                    // Crear imagen
                    var $img = $('<img>').attr('src', imgData.large).attr('alt', imgData.alt).css({
                        maxWidth: '85vw',
                        maxHeight: '85vh',
                        display: 'block',
                        position: 'relative',
                        zIndex: 10
                    });
                    
                    $imgContainer.append($img);
                    
                    // Solo agregar marco/paspartú en la primera imagen (index 0)
                    if (index === 0 && (currentMarcoUrl || currentPaspartuColor)) {
                        $img.on('load', function() {
                            var imgW = $img.width();
                            var imgH = $img.height();
                            
                            var marcoWidthPercent, marcoHeightPercent;
                            if (dimensiones[currentEstilo]) {
                                marcoWidthPercent = dimensiones[currentEstilo].width;
                                marcoHeightPercent = dimensiones[currentEstilo].height;
                            } else if (currentEstilo === '1:1') {
                                marcoWidthPercent = 80;
                                marcoHeightPercent = 80;
                            } else if (currentEstilo === 'vertical') {
                                marcoWidthPercent = 60;
                                marcoHeightPercent = 80;
                            } else {
                                marcoWidthPercent = 80;
                                marcoHeightPercent = 60;
                            }
                            
                            var paspartuWidthPercent = marcoWidthPercent - 2.5;
                            var paspartuHeightPercent = marcoHeightPercent - 2.5;
                            
                            var marcoW, marcoH, paspartuW, paspartuH;
                            if (currentEstilo === '1:1') {
                                var minSide = Math.min(imgW, imgH);
                                marcoW = (minSide * marcoWidthPercent) / 100;
                                marcoH = marcoW;
                                paspartuW = (minSide * paspartuWidthPercent) / 100;
                                paspartuH = paspartuW;
                            } else {
                                marcoW = (imgW * marcoWidthPercent) / 100;
                                marcoH = (imgH * marcoHeightPercent) / 100;
                                paspartuW = (imgW * paspartuWidthPercent) / 100;
                                paspartuH = (imgH * paspartuHeightPercent) / 100;
                            }
                            
                            var marcoL = (imgW - marcoW) / 2;
                            var marcoT = (imgH - marcoH) / 2;
                            var paspartuL = (imgW - paspartuW) / 2;
                            var paspartuT = (imgH - paspartuH) / 2;
                            
                            // Agregar paspartú
                            if (currentPaspartuColor) {
                                var $paspartu = $('<div class="cuadros-lb-paspartu"></div>').css({
                                    position: 'absolute',
                                    width: paspartuW + 'px',
                                    height: paspartuH + 'px',
                                    left: paspartuL + 'px',
                                    top: paspartuT + 'px',
                                    backgroundColor: currentPaspartuColor,
                                    zIndex: 1
                                });
                                $imgContainer.append($paspartu);
                            }
                            
                            // Agregar marco
                            if (currentMarcoUrl) {
                                var $marco = $('<div class="cuadros-lb-marco"></div>').css({
                                    position: 'absolute',
                                    width: marcoW + 'px',
                                    height: marcoH + 'px',
                                    left: marcoL + 'px',
                                    top: marcoT + 'px',
                                    backgroundImage: 'url(' + currentMarcoUrl + ')',
                                    backgroundSize: '100% 100%',
                                    backgroundRepeat: 'no-repeat',
                                    zIndex: 2,
                                    pointerEvents: 'none'
                                });
                                $imgContainer.append($marco);
                            }
                        });
                    }
                }
                
                // Mostrar imagen inicial
                mostrarImagen(currentSlideIndex);
                
                // Función para cerrar
                function cerrarLightbox() {
                    $overlay.remove();
                    lightboxActivo = false;
                    $(document).off('keydown.cuadrosLightbox');
                }
                
                // Eventos de navegación
                $prevBtn.on('click', function(e) {
                    e.stopPropagation();
                    mostrarImagen(currentSlideIndex - 1);
                });
                
                $nextBtn.on('click', function(e) {
                    e.stopPropagation();
                    mostrarImagen(currentSlideIndex + 1);
                });
                
                // Cerrar al hacer clic en overlay o botón cerrar
                $overlay.on('click', function(e) {
                    if (e.target === $overlay[0]) {
                        cerrarLightbox();
                    }
                });
                
                $closeBtn.on('click', function() {
                    cerrarLightbox();
                });
                
                // Teclado: ESC para cerrar, flechas para navegar
                $(document).on('keydown.cuadrosLightbox', function(e) {
                    if (e.keyCode === 27) { // ESC
                        cerrarLightbox();
                    } else if (e.keyCode === 37) { // Flecha izquierda
                        mostrarImagen(currentSlideIndex - 1);
                    } else if (e.keyCode === 39) { // Flecha derecha
                        mostrarImagen(currentSlideIndex + 1);
                    }
                });
            }
            
            // Interceptar clic SOLO en la primera imagen de la galería
            document.addEventListener('click', function(e) {
                var $target = $(e.target);
                
                // Verificar si es la primera imagen de la galería
                var $galleryImage = $target.closest('.woocommerce-product-gallery__image');
                if ($galleryImage.length === 0) return;
                
                // Obtener índice de la imagen
                var imageIndex = $galleryImage.index();
                
                // Solo interceptar si es la primera imagen (index 0) Y hay marco/paspartú
                if (imageIndex === 0 && (currentMarcoUrl || currentPaspartuColor)) {
                    var isClickable = $target.closest('a, .woocommerce-product-gallery__trigger').length > 0 || $target.is('img');
                    
                    if (isClickable) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        mostrarLightboxCuadros(0);
                        return false;
                    }
                }
            }, true);
            
            // Actualizar estado
            $('form.variations_form').on('change', 'select', function() {
                setTimeout(actualizarEstadoLightbox, 150);
            });
            
            $(document).on('woocommerce_variation_has_changed', function() {
                setTimeout(actualizarEstadoLightbox, 150);
            });
            
            setTimeout(actualizarEstadoLightbox, 600);
        });
        </script>
        <?php
    }
}
