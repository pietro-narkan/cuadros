<?php
/**
 * Clase para la lógica frontend del plugin Cuadros
 * 
 * ENFOQUE: 3 capas
 * - Capa 1 (fondo): Paspartú - color sólido
 * - Capa 2 (medio): Imagen - centrada y redimensionada
 * - Capa 3 (frente): Marco PNG - con transparencia central
 */
class Cuadros_Frontend {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_shortcode('cuadros_visualizador', array($this, 'render_visualizador_shortcode'));
        add_action('wp_footer', array($this, 'output_frontend_script_auto'));
    }
    
    public function enqueue_frontend_scripts() {
        if (!is_product()) return;
        wp_enqueue_style('cuadros-frontend-style', CUADROS_ASSETS_URL . 'css/frontend.css', array(), CUADROS_VERSION);
        wp_enqueue_script('jquery');
    }
    
    public function render_visualizador_shortcode($atts = array()) {
        if (!is_product()) return '';
        global $product;
        if (!$product || !$product->is_type('variable')) return '';
        $this->enqueue_frontend_scripts();
        return '<div id="cuadros-visualizador-container"></div>';
    }
    
    public function output_frontend_script_auto() {
        if (!is_product()) return;
        global $product;
        if (!$product || !$product->is_type('variable')) return;
        $attributes = $product->get_attributes();
        if (!isset($attributes['pa_marco']) && !isset($attributes['pa_paspartu'])) return;
        $this->output_frontend_script();
    }
    
    public function output_frontend_script() {
        $settings = get_option('cuadros_settings', array());
        $marco_images = isset($settings['marco_images']) ? $settings['marco_images'] : array();
        $paspartu_colors = isset($settings['paspartu_colors']) ? $settings['paspartu_colors'] : array();
        
        $urls_marcos = array();
        foreach ($marco_images as $marco) {
            $key = isset($marco['modelo']) ? $marco['modelo'] : (isset($marco['color']) ? $marco['color'] : null);
            if ($key && isset($marco['orientation']) && isset($marco['url'])) {
                $normalized_key = strtolower($key);
                if (!isset($urls_marcos[$normalized_key])) {
                    $urls_marcos[$normalized_key] = array();
                }
                $urls_marcos[$normalized_key][$marco['orientation']] = $marco['url'];
            }
        }
        
        if (defined('CUADROS_SCRIPT_LOADED')) return;
        define('CUADROS_SCRIPT_LOADED', true);
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            
            var urlsMarcos = <?php echo json_encode($urls_marcos); ?>;
            var coloresPaspartu = <?php echo json_encode($paspartu_colors); ?>;
            
            // Porcentaje del área que ocupa la imagen dentro del marco
            // El marco PNG tiene un borde visual, la imagen debe caber en el centro
            var IMAGEN_PORCENTAJE = 75; // La imagen ocupa el 75% del área total
            var PASPARTU_PORCENTAJE = 85; // El paspartú ocupa el 85% (entre marco e imagen)
            
            console.log('[cuadros] Iniciando - Marcos:', Object.keys(urlsMarcos), 'Paspartús:', Object.keys(coloresPaspartu));
            
            // Buscar elementos
            var $galleryImage = $('.woocommerce-product-gallery__image').first();
            var $productImage = $galleryImage.find('img').first();
            var $imageLink = $productImage.closest('a');
            var $originalContainer = $imageLink.length ? $imageLink : $productImage.parent();
            
            if ($productImage.length === 0) {
                console.log('[cuadros] No se encontró imagen');
                return;
            }
            
            // Guardar dimensiones originales
            var originalWidth = $productImage.width();
            var originalHeight = $productImage.height();
            
            // Crear estructura de 3 capas
            var $wrapper = $('<div id="cuadros-wrapper"></div>');
            var $paspartuLayer = $('<div id="layer-paspartu"></div>');  // Capa 1: fondo
            var $imagenLayer = $('<div id="layer-imagen"></div>');      // Capa 2: imagen
            var $marcoLayer = $('<div id="layer-marco"></div>');        // Capa 3: frente
            
            // Insertar estructura
            $productImage.before($wrapper);
            $wrapper.append($paspartuLayer);
            $wrapper.append($imagenLayer);
            $wrapper.append($marcoLayer);
            $imagenLayer.append($productImage);
            
            // Estilos del wrapper
            $wrapper.css({
                'position': 'relative',
                'display': 'inline-block',
                'width': originalWidth + 'px',
                'height': originalHeight + 'px'
            });
            
            // Capa 1: Paspartú (fondo) - z-index 1
            $paspartuLayer.css({
                'position': 'absolute',
                'top': '0', 'left': '0', 'right': '0', 'bottom': '0',
                'z-index': '1',
                'opacity': '0',
                'transition': 'opacity 0.3s'
            });
            
            // Capa 2: Imagen (medio) - z-index 2
            $imagenLayer.css({
                'position': 'absolute',
                'z-index': '2',
                'display': 'flex',
                'align-items': 'center',
                'justify-content': 'center',
                'overflow': 'hidden'
            });
            
            $productImage.css({
                'display': 'block',
                'width': '100%',
                'height': '100%',
                'object-fit': 'cover'
            });
            
            // Capa 3: Marco (frente) - z-index 3
            $marcoLayer.css({
                'position': 'absolute',
                'top': '0', 'left': '0', 'right': '0', 'bottom': '0',
                'z-index': '3',
                'pointer-events': 'none',
                'background-size': '100% 100%',
                'background-repeat': 'no-repeat',
                'opacity': '0',
                'transition': 'opacity 0.3s'
            });
            
            // Funciones auxiliares
            function detectarOrientacion() {
                var orientacion = null;
                
                $('form.variations_form select').each(function() {
                    var id = ($(this).attr('id') || '').toLowerCase();
                    var val = ($(this).val() || '').toLowerCase();
                    var txt = ($(this).find('option:selected').text() || '').toLowerCase();
                    var todo = id + ' ' + val + ' ' + txt;
                    
                    // Detectar 1:1
                    if (todo.match(/1\s*[:\-x]\s*1/) || todo.includes('cuadrado')) {
                        orientacion = '1:1';
                        return false;
                    }
                    // Detectar horizontal
                    if (todo.includes('horizontal')) {
                        orientacion = 'horizontal';
                        return false;
                    }
                    // Detectar vertical
                    if (todo.includes('vertical')) {
                        orientacion = 'vertical';
                        return false;
                    }
                    // Detectar por dimensiones NxM
                    var m = todo.match(/(\d+)\s*[xX]\s*(\d+)/);
                    if (m) {
                        var w = parseInt(m[1]), h = parseInt(m[2]);
                        orientacion = (Math.abs(w-h) <= 5) ? '1:1' : (w > h ? 'horizontal' : 'vertical');
                        return false;
                    }
                });
                
                console.log('[cuadros] Orientación detectada:', orientacion || 'default vertical');
                return orientacion || 'vertical';
            }
            
            function buscarMarco(val, orient) {
                if (!val) return null;
                var v = val.toLowerCase().replace(/-/g, ' ');
                for (var k in urlsMarcos) {
                    var kn = k.toLowerCase().replace(/-/g, ' ');
                    if (kn === v || kn.includes(v) || v.includes(kn)) {
                        return urlsMarcos[k][orient] || urlsMarcos[k]['vertical'] || urlsMarcos[k]['horizontal'] || null;
                    }
                }
                return null;
            }
            
            function buscarPaspartu(val) {
                if (!val) return null;
                var v = val.toLowerCase();
                for (var k in coloresPaspartu) {
                    if (k.toLowerCase() === v) return coloresPaspartu[k];
                }
                return null;
            }
            
            // Función principal
            function actualizar() {
                var marcoVal = $('#pa_marco').val();
                var paspartuVal = $('#pa_paspartu').val();
                var orientacion = detectarOrientacion();
                
                var marcoUrl = buscarMarco(marcoVal, orientacion);
                var paspartuColor = buscarPaspartu(paspartuVal);
                
                var hayMarco = !!marcoUrl;
                var hayPaspartu = !!paspartuColor;
                
                console.log('[cuadros] Actualizar:', {orientacion, hayMarco, hayPaspartu});
                
                // Si no hay nada, restaurar
                if (!hayMarco && !hayPaspartu) {
                    $wrapper.css({ 'width': originalWidth + 'px', 'height': originalHeight + 'px' });
                    $imagenLayer.css({ 'top': '0', 'left': '0', 'width': '100%', 'height': '100%' });
                    $productImage.css({ 'object-fit': 'contain' });
                    $marcoLayer.css('opacity', '0');
                    $paspartuLayer.css('opacity', '0');
                    return;
                }
                
                // Calcular dimensiones según orientación
                var wrapperW, wrapperH;
                
                if (orientacion === '1:1') {
                    // Cuadrado: usar el menor lado
                    var side = Math.min(originalWidth, originalHeight);
                    wrapperW = wrapperH = side;
                } else if (orientacion === 'horizontal') {
                    wrapperW = originalWidth;
                    wrapperH = originalWidth * 0.75; // Ratio 4:3
                } else {
                    // Vertical: mantener proporciones originales
                    wrapperW = originalWidth;
                    wrapperH = originalHeight;
                }
                
                $wrapper.css({ 'width': wrapperW + 'px', 'height': wrapperH + 'px' });
                
                // Calcular posición y tamaño de la imagen (centro del marco)
                var imgW, imgH, imgTop, imgLeft;
                
                if (hayPaspartu) {
                    // Con paspartú: imagen más pequeña
                    imgW = wrapperW * (IMAGEN_PORCENTAJE / 100);
                    imgH = wrapperH * (IMAGEN_PORCENTAJE / 100);
                } else {
                    // Sin paspartú: imagen un poco más grande
                    imgW = wrapperW * ((IMAGEN_PORCENTAJE + 5) / 100);
                    imgH = wrapperH * ((IMAGEN_PORCENTAJE + 5) / 100);
                }
                
                // Centrar imagen
                imgLeft = (wrapperW - imgW) / 2;
                imgTop = (wrapperH - imgH) / 2;
                
                $imagenLayer.css({
                    'top': imgTop + 'px',
                    'left': imgLeft + 'px',
                    'width': imgW + 'px',
                    'height': imgH + 'px'
                });
                
                $productImage.css({ 'object-fit': 'cover' });
                
                // Marco
                if (hayMarco) {
                    $marcoLayer.css({
                        'background-image': 'url(' + marcoUrl + ')',
                        'opacity': '1'
                    });
                } else {
                    $marcoLayer.css('opacity', '0');
                }
                
                // Paspartú (fondo visible a través del marco transparente)
                if (hayPaspartu) {
                    $paspartuLayer.css({
                        'background-color': paspartuColor,
                        'opacity': '1'
                    });
                } else {
                    $paspartuLayer.css('opacity', '0');
                }
                
                console.log('[cuadros] Dimensiones - Wrapper:', wrapperW, 'x', wrapperH, '- Imagen:', imgW, 'x', imgH);
            }
            
            // Event listeners
            $('form.variations_form').on('change', 'select', function() {
                setTimeout(actualizar, 100);
            });
            
            $(document).on('woocommerce_variation_has_changed', function() {
                setTimeout(actualizar, 100);
            });
            
            $('.reset_variations').on('click', function() {
                $wrapper.css({ 'width': originalWidth + 'px', 'height': originalHeight + 'px' });
                $imagenLayer.css({ 'top': '0', 'left': '0', 'width': '100%', 'height': '100%' });
                $marcoLayer.css('opacity', '0');
                $paspartuLayer.css('opacity', '0');
            });
            
            $(window).on('resize', function() { setTimeout(actualizar, 100); });
            
            // Inicializar
            if ($productImage[0].complete) {
                originalWidth = $productImage.width();
                originalHeight = $productImage.height();
                setTimeout(actualizar, 300);
            } else {
                $productImage.on('load', function() {
                    originalWidth = $productImage.width();
                    originalHeight = $productImage.height();
                    $wrapper.css({ 'width': originalWidth + 'px', 'height': originalHeight + 'px' });
                    setTimeout(actualizar, 100);
                });
            }
            
            setTimeout(actualizar, 500);

            // ========== LIGHTBOX ==========
            var currentMarcoUrl = null;
            var currentPaspartuColor = null;
            var currentOrientacion = 'vertical';
            
            function actualizarLightboxState() {
                var marcoVal = $('#pa_marco').val();
                var paspartuVal = $('#pa_paspartu').val();
                currentOrientacion = detectarOrientacion();
                currentMarcoUrl = buscarMarco(marcoVal, currentOrientacion);
                currentPaspartuColor = buscarPaspartu(paspartuVal);
            }
            
            function mostrarLightbox() {
                var imgSrc = $productImage.attr('data-large_image') || $productImage.attr('src');
                
                var $overlay = $('<div id="cuadros-lightbox"></div>').css({
                    position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
                    background: 'rgba(0,0,0,0.95)', zIndex: 999999,
                    display: 'flex', alignItems: 'center', justifyContent: 'center'
                });
                
                var maxSize = Math.min(window.innerWidth * 0.8, window.innerHeight * 0.8);
                var lbW, lbH;
                
                if (currentOrientacion === '1:1') {
                    lbW = lbH = maxSize;
                } else if (currentOrientacion === 'horizontal') {
                    lbW = maxSize;
                    lbH = maxSize * 0.75;
                } else {
                    lbH = maxSize;
                    lbW = maxSize * 0.75;
                }
                
                var $container = $('<div></div>').css({
                    position: 'relative', width: lbW + 'px', height: lbH + 'px'
                });
                
                // Paspartú
                if (currentPaspartuColor) {
                    $('<div></div>').css({
                        position: 'absolute', top: 0, left: 0, right: 0, bottom: 0,
                        background: currentPaspartuColor, zIndex: 1
                    }).appendTo($container);
                }
                
                // Imagen
                var imgSize = currentPaspartuColor ? IMAGEN_PORCENTAJE : (IMAGEN_PORCENTAJE + 5);
                var imgW = lbW * (imgSize / 100);
                var imgH = lbH * (imgSize / 100);
                
                $('<div></div>').css({
                    position: 'absolute',
                    top: (lbH - imgH) / 2 + 'px',
                    left: (lbW - imgW) / 2 + 'px',
                    width: imgW + 'px', height: imgH + 'px',
                    zIndex: 2, overflow: 'hidden'
                }).append(
                    $('<img>').attr('src', imgSrc).css({
                        width: '100%', height: '100%', objectFit: 'cover'
                    })
                ).appendTo($container);
                
                // Marco
                if (currentMarcoUrl) {
                    $('<div></div>').css({
                        position: 'absolute', top: 0, left: 0, right: 0, bottom: 0,
                        backgroundImage: 'url(' + currentMarcoUrl + ')',
                        backgroundSize: '100% 100%',
                        zIndex: 3, pointerEvents: 'none'
                    }).appendTo($container);
                }
                
                // Botón cerrar
                $('<div>&times;</div>').css({
                    position: 'absolute', top: '20px', right: '30px',
                    color: '#fff', fontSize: '40px', cursor: 'pointer', zIndex: 10
                }).on('click', function() { $overlay.remove(); }).appendTo($overlay);
                
                $overlay.append($container);
                $overlay.on('click', function(e) { if (e.target === $overlay[0]) $overlay.remove(); });
                $('body').append($overlay);
                
                $(document).on('keydown.cuadrosLb', function(e) {
                    if (e.keyCode === 27) { $overlay.remove(); $(document).off('keydown.cuadrosLb'); }
                });
            }
            
            // Click en imagen
            $wrapper.on('click', function(e) {
                if (currentMarcoUrl || currentPaspartuColor) {
                    e.preventDefault();
                    e.stopPropagation();
                    mostrarLightbox();
                }
            });
            
            $('form.variations_form').on('change', 'select', function() {
                setTimeout(actualizarLightboxState, 150);
            });
            
            setTimeout(actualizarLightboxState, 600);
        });
        </script>
        <?php
    }
}
