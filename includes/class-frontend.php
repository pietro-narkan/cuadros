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
            'horizontal' => array('width' => 80, 'height' => 60)
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
                
                // Obtener dimensiones actuales de la imagen
                var imgWidth = $productImage.width();
                var imgHeight = $productImage.height();
                
                console.log('[cuadros] Dimensiones imagen:', imgWidth, 'x', imgHeight);
                
                if (imgWidth <= 0 || imgHeight <= 0) {
                    console.log('[cuadros] Imagen sin dimensiones válidas');
                    return;
                }
                
                // Calcular dimensiones del marco según orientación
                var marcoWidthPercent = dimensiones[estilo] ? dimensiones[estilo].width : (estilo === 'vertical' ? 60 : 80);
                var marcoHeightPercent = dimensiones[estilo] ? dimensiones[estilo].height : (estilo === 'vertical' ? 80 : 60);
                
                // El paspartú debe ser ligeramente más pequeño que el marco para quedar dentro
                var paspartuWidthPercent = marcoWidthPercent - 3;
                var paspartuHeightPercent = marcoHeightPercent - 3;
                
                // Calcular dimensiones en píxeles
                var marcoWidth = (imgWidth * marcoWidthPercent) / 100;
                var marcoHeight = (imgHeight * marcoHeightPercent) / 100;
                var paspartuWidth = (imgWidth * paspartuWidthPercent) / 100;
                var paspartuHeight = (imgHeight * paspartuHeightPercent) / 100;
                
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
                    
                    // Buscar coincidencia exacta o parcial
                    for (var key in urlsMarcos) {
                        var keyNorm = key.toLowerCase().replace(/-/g, ' ');
                        if (keyNorm === marcoValNorm || keyNorm.includes(marcoValNorm) || marcoValNorm.includes(keyNorm)) {
                            if (urlsMarcos[key][estilo]) {
                                marcoEncontrado = urlsMarcos[key][estilo];
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
        });
        </script>
        <?php
    }
}
