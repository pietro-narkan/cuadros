<?php
/**
 * Clase para la lógica frontend del plugin Cuadros
 */
class Cuadros_Frontend {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('woocommerce_before_single_product', array($this, 'maybe_add_overlay_layers'));
        add_action('wp_footer', array($this, 'output_frontend_script'));
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
     * Agregar capas de overlay si es necesario
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
     * Output del script frontend con la lógica de visualización
     */
    public function output_frontend_script() {
        if (!is_product()) {
            return;
        }
        
        $settings = get_option('cuadros_settings', array());
        
        // Preparar datos para JavaScript
        $marco_images = isset($settings['marco_images']) ? $settings['marco_images'] : array();
        $paspartu_colors = isset($settings['paspartu_colors']) ? $settings['paspartu_colors'] : array();
        $dimensions = isset($settings['dimensions']) ? $settings['dimensions'] : array(
            'vertical' => array('width' => 70, 'height' => 90),
            'horizontal' => array('width' => 90, 'height' => 70)
        );
        
        // Estructurar imágenes de marcos para JS
        $urls_marcos = array();
        foreach ($marco_images as $marco) {
            if (isset($marco['color']) && isset($marco['orientation']) && isset($marco['url'])) {
                if (!isset($urls_marcos[$marco['color']])) {
                    $urls_marcos[$marco['color']] = array();
                }
                $urls_marcos[$marco['color']][$marco['orientation']] = $marco['url'];
            }
        }
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            
            // 1. CONFIGURACIÓN DE DATOS
            var urlsMarcos = <?php echo json_encode($urls_marcos); ?>;
            var coloresPaspartu = <?php echo json_encode($paspartu_colors); ?>;
            var dimensiones = <?php echo json_encode($dimensions); ?>;
            
            // 2. PREPARACIÓN DOM
            var $gallery = $('.woocommerce-product-gallery');
            if ($gallery.length === 0) {
                $gallery = $('.elementor-widget-woocommerce-product-images .woocommerce-product-gallery');
            }
            
            // Asegurar que las capas existan
            if ($gallery.length > 0 && $('#layer-marco').length === 0) {
                $gallery.prepend('<div id="layer-marco" class="custom-overlay-layer"></div>');
                $gallery.prepend('<div id="layer-paspartu" class="custom-overlay-layer"></div>');
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
                
                var $divMarco = $('#layer-marco');
                var $divPaspartu = $('#layer-paspartu');
                var $wrapper = $('.woocommerce-product-gallery__wrapper');
                
                // A. CAMBIO DE DIMENSIONES (CSS DINÁMICO)
                if (dimensiones[estilo]) {
                    var widthMarco = dimensiones[estilo].width + '%';
                    var heightMarco = dimensiones[estilo].height + '%';
                    var widthPaspartu = (dimensiones[estilo].width - 3) + '%';
                    var heightPaspartu = (dimensiones[estilo].height - 3) + '%';
                    
                    $divMarco.css({
                        'width': widthMarco,
                        'height': heightMarco
                    });
                    $divPaspartu.css({
                        'width': widthPaspartu,
                        'height': heightPaspartu
                    });
                } else {
                    // Fallback a valores por defecto
                    if (estilo === 'vertical') {
                        $divMarco.css({ 'width': '70%', 'height': '90%' });
                        $divPaspartu.css({ 'width': '67%', 'height': '87%' });
                    } else {
                        $divMarco.css({ 'width': '90%', 'height': '70%' });
                        $divPaspartu.css({ 'width': '87%', 'height': '67%' });
                    }
                }
                
                // B. RENDERIZADO MARCO
                if (marcoVal && urlsMarcos[marcoVal] && urlsMarcos[marcoVal][estilo]) {
                    $divMarco.css('background-image', 'url(' + urlsMarcos[marcoVal][estilo] + ')');
                    $divMarco.addClass('visible');
                } else {
                    $divMarco.removeClass('visible');
                }
                
                // C. RENDERIZADO PASPARTU
                if (paspartuVal && coloresPaspartu[paspartuVal]) {
                    $divPaspartu.css('background-color', coloresPaspartu[paspartuVal]);
                    $divPaspartu.addClass('visible');
                    $wrapper.css('padding', '15%');
                } else {
                    $divPaspartu.removeClass('visible');
                    $divPaspartu.css('background-color', 'transparent');
                    if (marcoVal && urlsMarcos[marcoVal]) {
                        $wrapper.css('padding', '3%');
                    } else {
                        $wrapper.css('padding', '0');
                    }
                }
            }
            
            // Listeners
            $('form.variations_form').on('change', 'select', function() {
                setTimeout(actualizarCapas, 100);
            });
            
            $(document).on('woocommerce_variation_has_changed', function() {
                setTimeout(actualizarCapas, 100);
            });
            
            $('.reset_variations').on('click', function() {
                $('.custom-overlay-layer').removeClass('visible');
                $('.woocommerce-product-gallery__wrapper').css('padding', '0');
            });
            
            // Inicializar
            setTimeout(actualizarCapas, 500);
            
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
        });
        </script>
        <?php
    }
}
