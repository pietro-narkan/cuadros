<?php
/**
 * Clase para la lógica frontend del plugin Cuadros
 */
class Cuadros_Frontend {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_shortcode('cuadros_visualizador', array($this, 'render_visualizador_shortcode'));
        
        // Funcionalidad automática para productos variables
        add_action('woocommerce_before_single_product', array($this, 'maybe_add_overlay_layers'));
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
     * Agregar capas de overlay automáticamente si es necesario
     */
    public function maybe_add_overlay_layers() {
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
        
        // Agregar divs para las capas
        echo '<div id="layer-marco" class="custom-overlay-layer"></div>';
        echo '<div id="layer-paspartu" class="custom-overlay-layer"></div>';
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
            'vertical' => array('width' => 70, 'height' => 90),
            'horizontal' => array('width' => 90, 'height' => 70)
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
            
            // Procesar urlsMarcos para facilitar el acceso
            var urlsMarcos = {};
            for (var key in urlsMarcosRaw) {
                urlsMarcos[key] = {};
                for (var orient in urlsMarcosRaw[key]) {
                    urlsMarcos[key][orient] = urlsMarcosRaw[key][orient];
                }
            }
            
            console.log('[cuadros] Marcos disponibles:', urlsMarcos);
            console.log('[cuadros] Paspartús disponibles:', coloresPaspartu);
            console.log('[cuadros] Dimensiones:', dimensiones);
            
            // 2. PREPARACIÓN DOM - Buscar la imagen principal del producto de forma robusta
            var selectors = [
                '.woocommerce-product-gallery__image img',
                '.elementor-widget-woocommerce-product-images .woocommerce-product-gallery__image img',
                '.woocommerce-product-gallery__wrapper img',
                '.woocommerce-product-gallery img',
                '.product img'
            ];

            var $mainImage = $();
            for (var i = 0; i < selectors.length; i++) {
                var $found = $(selectors[i] + ':first');
                if ($found.length) {
                    $mainImage = $found;
                    break;
                }
            }

            // Verificar si estamos usando shortcode
            var usingShortcode = $('#cuadros-visualizador-container').length > 0;
            var $container = $();

            if (usingShortcode) {
                $container = $('#cuadros-visualizador-container');
                if ($container.find('img').length === 0 && $mainImage.length) {
                    $container = $mainImage.closest('.woocommerce-product-gallery__image, .woocommerce-product-gallery__wrapper, figure, .product');
                    if (!$container.length) {
                        $container = $mainImage.parent();
                    }
                }
            } else if ($mainImage.length) {
                // Preferir contenedores semánticos cercanos a la imagen
                $container = $mainImage.closest('.woocommerce-product-gallery__image, .woocommerce-product-gallery__wrapper, figure, .product');
                if (!$container.length) {
                    $container = $mainImage.parent();
                }
            }

            // Asegurar que las capas estén en el lugar correcto
            if ($container.length > 0) {
                // Mover las capas al contenedor correcto de forma segura
                $('#layer-marco').detach().appendTo($container);
                $('#layer-paspartu').detach().appendTo($container);

                // Asegurar que el contenedor tenga posicionamiento relativo solo si es estático
                var currentPos = $container.css('position');
                if (!currentPos || currentPos === 'static') {
                    $container.css('position', 'relative');
                }

                // Posicionar las capas exactamente sobre la imagen
                var $img = $container.find('img').first();
                if ($img.length > 0) {
                    // Obtener tamaño de la imagen
                    var imgWidth = $img.width();
                    var imgHeight = $img.height();

                    // Posicionar las capas sobre la imagen (dentro del contenedor)
                    // Inicialmente ocultas y sin dimensiones fijas
                    $('#layer-marco, #layer-paspartu').css({
                        'position': 'absolute',
                        'top': '0',
                        'left': '0',
                        'width': 'auto',
                        'height': 'auto',
                        'transform': 'none',
                        'background-size': 'contain',
                        'background-position': 'center',
                        'background-repeat': 'no-repeat',
                        'max-width': '100%',
                        'max-height': '100%'
                    });
                }
            } else {
                console.log('[cuadros] No se encontró contenedor para las capas');
            }
            
            // 3. LÓGICA DE DETECCIÓN INTELIGENTE
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
                    return (ancho < alto) ? 'vertical' : 'horizontal';
                }
                return null;
            }
            
            // 4. FUNCIÓN PRINCIPAL
            function actualizarCapas() {
                var marcoVal = $('#pa_marco').val();
                var paspartuVal = $('#pa_paspartu').val();
                var tamanoTexto = encontrarTextoTamano();
                var orientacion = obtenerOrientacion(tamanoTexto);
                var estilo = orientacion || 'vertical';
                
                console.log('[cuadros] actualizarCapas:', {marco: marcoVal, paspartu: paspartuVal, orientation: estilo});
                
                var $divMarco = $('#layer-marco');
                var $divPaspartu = $('#layer-paspartu');
                var $wrapper = $('.woocommerce-product-gallery__wrapper');
                
                // A. CAMBIO DE DIMENSIONES - Calcular dimensiones basadas en la imagen real
                var $mainImage = $('.woocommerce-product-gallery__image:first img');
                if ($mainImage.length === 0) {
                    $mainImage = $('.elementor-widget-woocommerce-product-images .woocommerce-product-gallery__image:first img');
                }
                if ($mainImage.length === 0) {
                    $mainImage = $('.woocommerce-product-gallery__wrapper img:first');
                }
                
                if ($mainImage.length > 0) {
                    var imgWidth = $mainImage.width();
                    var imgHeight = $mainImage.height();
                    
                    if (imgWidth > 0 && imgHeight > 0) {
                        // Calcular porcentajes según orientación
                        var marcoWidthPercent, marcoHeightPercent;
                        
                        if (dimensiones[estilo]) {
                            marcoWidthPercent = dimensiones[estilo].width;
                            marcoHeightPercent = dimensiones[estilo].height;
                        } else {
                            // Valores por defecto
                            if (estilo === 'vertical') {
                                marcoWidthPercent = 70;
                                marcoHeightPercent = 90;
                            } else {
                                marcoWidthPercent = 90;
                                marcoHeightPercent = 70;
                            }
                        }
                        
                        var paspartuWidthPercent = marcoWidthPercent - 3;
                        var paspartuHeightPercent = marcoHeightPercent - 3;
                        
                        // Calcular dimensiones en píxeles basadas en la imagen
                        var marcoWidth = (imgWidth * marcoWidthPercent) / 100;
                        var marcoHeight = (imgHeight * marcoHeightPercent) / 100;
                        var paspartuWidth = (imgWidth * paspartuWidthPercent) / 100;
                        var paspartuHeight = (imgHeight * paspartuHeightPercent) / 100;
                        
                        // Calcular posiciones centradas
                        var marcoLeft = (imgWidth - marcoWidth) / 2;
                        var marcoTop = (imgHeight - marcoHeight) / 2;
                        var paspartuLeft = (imgWidth - paspartuWidth) / 2;
                        var paspartuTop = (imgHeight - paspartuHeight) / 2;
                        
                        // Aplicar dimensiones y posicionamiento en píxeles
                        $divMarco.css({
                            'width': marcoWidth + 'px',
                            'height': marcoHeight + 'px',
                            'left': marcoLeft + 'px',
                            'top': marcoTop + 'px',
                            'background-size': '100% 100%',
                            'background-position': 'center',
                            'background-repeat': 'no-repeat'
                        });
                        
                        $divPaspartu.css({
                            'width': paspartuWidth + 'px',
                            'height': paspartuHeight + 'px',
                            'left': paspartuLeft + 'px',
                            'top': paspartuTop + 'px',
                            'background-size': '100% 100%',
                            'background-position': 'center',
                            'background-repeat': 'no-repeat'
                        });
                        
                        console.log('[cuadros] Dimensiones calculadas:', {
                            imgWidth: imgWidth,
                            imgHeight: imgHeight,
                            marcoWidth: marcoWidth,
                            marcoHeight: marcoHeight,
                            paspartuWidth: paspartuWidth,
                            paspartuHeight: paspartuHeight
                        });
                    }
                }
                
                // B. RENDERIZADO MARCO - búsqueda insensible a mayúsculas/minúsculas
                var marcoEncontrado = null;
                if (marcoVal) {
                    // Normalizar el valor seleccionado a minúsculas
                    var marcoValNormalized = marcoVal.toLowerCase();
                    
                    // Buscar en urlsMarcos (las claves ya están en minúsculas)
                    if (urlsMarcos[marcoValNormalized] && urlsMarcos[marcoValNormalized][estilo]) {
                        marcoEncontrado = urlsMarcos[marcoValNormalized][estilo];
                        console.log('[cuadros] Marco encontrado (normalizado):', marcoValNormalized);
                    } else {
                        // Si no se encuentra exacto, buscar cualquier coincidencia parcial
                        for (var key in urlsMarcos) {
                            if (key.includes(marcoValNormalized) || marcoValNormalized.includes(key)) {
                                if (urlsMarcos[key][estilo]) {
                                    marcoEncontrado = urlsMarcos[key][estilo];
                                    console.log('[cuadros] Marco encontrado (coincidencia parcial):', key);
                                    break;
                                }
                            }
                        }
                    }
                }
                
                if (marcoEncontrado) {
                    console.log('[cuadros] Mostrando marco:', marcoEncontrado);
                    $divMarco.css('background-image', 'url(' + marcoEncontrado + ')');
                    $divMarco.addClass('visible');
                } else {
                    console.log('[cuadros] Marco no encontrado:', marcoVal, 'Normalizado:', marcoValNormalized, 'Disponibles:', Object.keys(urlsMarcos));
                    $divMarco.removeClass('visible');
                }
                
                // C. RENDERIZADO PASPARTU - búsqueda flexible
                var paspartuEncontrado = null;
                if (paspartuVal) {
                    // Intentar coincidencia exacta
                    if (coloresPaspartu[paspartuVal]) {
                        paspartuEncontrado = coloresPaspartu[paspartuVal];
                        console.log('[cuadros] Paspartú encontrado (exacto):', paspartuVal);
                    } else {
                        // Intentar búsqueda case-insensitive
                        for (var key in coloresPaspartu) {
                            if (key.toLowerCase() === paspartuVal.toLowerCase()) {
                                paspartuEncontrado = coloresPaspartu[key];
                                console.log('[cuadros] Paspartú encontrado (case-insensitive):', key);
                                break;
                            }
                        }
                    }
                }
                
                if (paspartuEncontrado) {
                    console.log('[cuadros] Mostrando paspartú:', paspartuVal, paspartuEncontrado);
                    $divPaspartu.css('background-color', paspartuEncontrado);
                    $divPaspartu.addClass('visible');
                    $wrapper.css('padding', '15%');
                } else {
                    console.log('[cuadros] Paspartú no encontrado:', paspartuVal, 'Disponibles:', Object.keys(coloresPaspartu));
                    $divPaspartu.removeClass('visible');
                    $divPaspartu.css('background-color', 'transparent');
                    if (marcoEncontrado) {
                        $wrapper.css('padding', '3%');
                    } else {
                        $wrapper.css('padding', '0');
                    }
                }
            }
            
            // Listeners para cambios en variaciones
            function inicializarListeners() {
                $('form.variations_form').off('change.cuadros').on('change.cuadros', 'select', function() {
                    setTimeout(actualizarCapas, 100);
                });
                
                $(document).off('woocommerce_variation_has_changed.cuadros').on('woocommerce_variation_has_changed.cuadros', function() {
                    setTimeout(actualizarCapas, 100);
                });
                
                $('.reset_variations').off('click.cuadros').on('click.cuadros', function() {
                    $('.custom-overlay-layer').removeClass('visible');
                    $('.woocommerce-product-gallery__wrapper').css('padding', '0');
                });
                
                // Inicializar
                setTimeout(actualizarCapas, 500);
            }
            
            // Inicializar listeners inmediatamente si el DOM está listo
            if ($('form.variations_form').length > 0) {
                inicializarListeners();
            } else {
                // Si no está listo, esperar un momento
                setTimeout(function() {
                    if ($('form.variations_form').length > 0) {
                        inicializarListeners();
                    } else {
                        console.log('[cuadros] No se encontró formulario de variaciones');
                    }
                }, 1000);
            }
            
            // Observer para cambios dinámicos (por si hay AJAX)
            if (typeof MutationObserver !== 'undefined') {
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.addedNodes.length) {
                            setTimeout(actualizarCapas, 300);
                        }
                    });
                });
                
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }
            
            // Recalcular dimensiones cuando cambie el tamaño de la ventana
            $(window).on('resize', function() {
                setTimeout(actualizarCapas, 100);
            });
            
            // También recalcular cuando se cargue completamente la imagen
            $('img').on('load', function() {
                if ($(this).closest('.woocommerce-product-gallery').length) {
                    setTimeout(actualizarCapas, 100);
                }
            });
        });
        </script>
        <?php
    }
}
