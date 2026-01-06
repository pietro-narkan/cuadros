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
                    urlsMarcos[key][orient] = urlsMarcosRaw[key][orient].url;
                }
            }
            
            console.log('[cuadros] Marcos disponibles:', urlsMarcos);
            console.log('[cuadros] Paspartús disponibles:', coloresPaspartu);
            console.log('[cuadros] Dimensiones:', dimensiones);
            
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
                
                console.log('[cuadros] actualizarCapas:', {marco: marcoVal, paspartu: paspartuVal, orientation: estilo});
                
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
