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
        $grosor_marco = isset($settings['grosor_marco']) ? intval($settings['grosor_marco']) : 8;
        $grosor_paspartu = isset($settings['grosor_paspartu']) ? intval($settings['grosor_paspartu']) : 25;
        
        if (defined('CUADROS_SCRIPT_LOADED')) return;
        define('CUADROS_SCRIPT_LOADED', true);
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            
            var coloresMarco = <?php echo json_encode($marco_colors); ?>;
            var coloresPaspartu = <?php echo json_encode($paspartu_colors); ?>;
            var GROSOR_MARCO = <?php echo $grosor_marco; ?>;
            var GROSOR_PASPARTU = <?php echo $grosor_paspartu; ?>;
            var FACTOR_ESCALA = 0.85;
            
            // Esperar un momento para que WooCommerce inicialice la galería
            setTimeout(inicializarCuadros, 500);
            
            function inicializarCuadros() {
                var $galleryImage = $('.woocommerce-product-gallery__image').first();
                var $productImage = $galleryImage.find('img').first();
                
                if ($productImage.length === 0) {
                    console.log('[cuadros] No se encontró imagen');
                    return;
                }
                
                var originalWidth = $productImage.width();
                var originalHeight = $productImage.height();
                
                // Si no hay dimensiones, usar las naturales
                if (originalWidth < 50) originalWidth = $productImage[0].naturalWidth || 400;
                if (originalHeight < 50) originalHeight = $productImage[0].naturalHeight || 400;
                
                console.log('[cuadros] Dimensiones:', originalWidth, 'x', originalHeight);
                
                // Crear los elementos
                var $fondoWrapper = $('<div id="cuadros-fondo"></div>');
                var $wrapper = $('<div id="cuadros-wrapper"></div>');
                var $marcoLayer = $('<div id="layer-marco"></div>');
                var $paspartuLayer = $('<div id="layer-paspartu"></div>');
                var $imagenLayer = $('<div id="layer-imagen"></div>');
                
                // Insertar antes de la imagen
                $productImage.before($fondoWrapper);
                
                // Construir estructura
                $fondoWrapper.append($wrapper);
                $wrapper.append($marcoLayer);
                $wrapper.append($paspartuLayer);
                $wrapper.append($imagenLayer);
                
                // Mover la imagen dentro de imagenLayer
                $imagenLayer.append($productImage);
                
                // Estilos del fondo
                $fondoWrapper.css({
                    'width': originalWidth + 'px',
                    'height': originalHeight + 'px'
                });
                
                function buscarColorMarco(val) {
                    if (!val) return null;
                    var v = val.toLowerCase().replace(/[\s-]/g, '_');
                    for (var k in coloresMarco) {
                        var kn = k.toLowerCase().replace(/[\s-]/g, '_');
                        if (kn === v || kn.includes(v) || v.includes(kn)) return coloresMarco[k];
                    }
                    return null;
                }
                
                function buscarColorPaspartu(val) {
                    if (!val) return null;
                    var valLower = val.toLowerCase();
                    if (valLower.includes('sin ') || valLower === 'ninguno' || valLower === 'none' || valLower === 'no') return null;
                    var v = val.toLowerCase().replace(/[\s-]/g, '_');
                    for (var k in coloresPaspartu) {
                        var kn = k.toLowerCase().replace(/[\s-]/g, '_');
                        if (kn === v || kn.includes(v) || v.includes(kn)) return coloresPaspartu[k];
                    }
                    return null;
                }
                
                function actualizar() {
                    var marcoColor = buscarColorMarco($('#pa_marco').val());
                    var paspartuColor = buscarColorPaspartu($('#pa_paspartu').val());
                    var hayMarco = !!marcoColor;
                    var hayPaspartu = !!paspartuColor;
                    
                    var bordeMarco = hayMarco ? GROSOR_MARCO : 0;
                    var bordePaspartu = hayPaspartu ? GROSOR_PASPARTU : 0;
                    var bordeTotal = bordeMarco + bordePaspartu;
                    
                    var imgNaturalW = $productImage[0].naturalWidth || originalWidth;
                    var imgNaturalH = $productImage[0].naturalHeight || originalHeight;
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
                    
                    $wrapper.css({ 'width': wrapperW + 'px', 'height': wrapperH + 'px' });
                    $marcoLayer.css({ 'border': hayMarco ? bordeMarco + 'px solid ' + marcoColor : 'none' });
                    $paspartuLayer.css({ 'background-color': hayPaspartu ? paspartuColor : 'transparent' });
                    $imagenLayer.css({ 'top': bordeTotal + 'px', 'left': bordeTotal + 'px', 'width': imgW + 'px', 'height': imgH + 'px' });
                }
                
                // Eventos
                $('form.variations_form').on('change', 'select', function() { setTimeout(actualizar, 100); });
                $(document).on('woocommerce_variation_has_changed', function() { setTimeout(actualizar, 100); });
                $('.reset_variations').on('click', function() { setTimeout(actualizar, 100); });
                
                // Ejecutar
                actualizar();
                
                // ===== LIGHTBOX =====
                var currentMarcoColor = null;
                var currentPaspartuColor = null;
                var galleryImages = [];
                
                function actualizarLightboxState() {
                    currentMarcoColor = buscarColorMarco($('#pa_marco').val());
                    currentPaspartuColor = buscarColorPaspartu($('#pa_paspartu').val());
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
                    
                    $overlay.append($container, $closeBtn);
                    $('body').append($overlay);
                    
                    function mostrar(i) {
                        idx = i;
                        $container.empty();
                        var src = galleryImages[i] ? galleryImages[i].src : '';
                        var hayM = (i === 0) && currentMarcoColor;
                        var hayP = (i === 0) && currentPaspartuColor;
                        
                        if (hayM || hayP) {
                            var bM = hayM ? GROSOR_MARCO : 0;
                            var bP = hayP ? GROSOR_PASPARTU : 0;
                            var bT = bM + bP;
                            var max = Math.min(window.innerWidth * 0.75, window.innerHeight * 0.75);
                            var tmp = new Image();
                            tmp.onload = function() {
                                var r = tmp.width / tmp.height;
                                var w = r >= 1 ? max - bT*2 : (max - bT*2) * r;
                                var h = r >= 1 ? w / r : max - bT*2;
                                $container.css({ width: w + bT*2, height: h + bT*2, background: hayP ? currentPaspartuColor : '#fff', border: hayM ? bM + 'px solid ' + currentMarcoColor : 'none', boxSizing: 'border-box' });
                                $('<div></div>').css({ position: 'absolute', top: bP, left: bP, width: w, height: h, overflow: 'hidden' }).append($('<img>').attr('src', src).css({ width: '100%', height: '100%', objectFit: 'fill' })).appendTo($container);
                            };
                            tmp.src = src;
                        } else {
                            $('<img>').attr('src', src).css({ maxWidth: '80vw', maxHeight: '80vh' }).appendTo($container);
                        }
                    }
                    
                    mostrar(0);
                    $closeBtn.on('click', function() { $overlay.remove(); });
                    $overlay.on('click', function(e) { if (e.target === $overlay[0]) $overlay.remove(); });
                }
                
                $fondoWrapper.on('click', function(e) {
                    if (currentMarcoColor || currentPaspartuColor) {
                        e.preventDefault();
                        e.stopPropagation();
                        mostrarLightbox();
                    }
                });
                
                $('form.variations_form').on('change', 'select', function() { setTimeout(actualizarLightboxState, 150); });
                actualizarLightboxState();
            }
        });
        </script>
        <?php
    }
}
