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
            
            var $galleryImage = $('.woocommerce-product-gallery__image').first();
            var $productImage = $galleryImage.find('img').first();
            
            if ($productImage.length === 0) return;
            
            var originalWidth = $productImage.width() || $productImage[0].naturalWidth || 400;
            var originalHeight = $productImage.height() || $productImage[0].naturalHeight || 400;
            
            // Crear estructura
            var $fondoWrapper = $('<div id="cuadros-fondo"></div>');
            var $wrapper = $('<div id="cuadros-wrapper"></div>');
            var $marcoLayer = $('<div id="layer-marco"></div>');
            var $paspartuLayer = $('<div id="layer-paspartu"></div>');
            var $imagenLayer = $('<div id="layer-imagen"></div>');
            
            $productImage.before($fondoWrapper);
            $fondoWrapper.append($wrapper);
            $wrapper.append($marcoLayer).append($paspartuLayer).append($imagenLayer);
            $imagenLayer.append($productImage);
            
            // Estilos iniciales
            $fondoWrapper.css({
                'background-color': '#f0f0f0',
                'width': originalWidth + 'px',
                'height': originalHeight + 'px',
                'display': 'flex',
                'align-items': 'center',
                'justify-content': 'center',
                'position': 'relative',
                'margin': '0 auto'
            });
            
            $wrapper.css({ 'position': 'relative', 'box-sizing': 'border-box' });
            $marcoLayer.css({ 'position': 'absolute', 'top': 0, 'left': 0, 'width': '100%', 'height': '100%', 'box-sizing': 'border-box', 'pointer-events': 'none' });
            $paspartuLayer.css({ 'position': 'absolute', 'top': 0, 'left': 0, 'width': '100%', 'height': '100%', 'pointer-events': 'none' });
            $imagenLayer.css({ 'position': 'absolute', 'overflow': 'hidden' });
            $productImage.css({ 'display': 'block', 'width': '100%', 'height': '100%', 'object-fit': 'fill' });
            
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
                
                var espacioW = Math.max(50, (originalWidth * FACTOR_ESCALA) - (bordeTotal * 2));
                var espacioH = Math.max(50, (originalHeight * FACTOR_ESCALA) - (bordeTotal * 2));
                var espacioRatio = espacioW / espacioH;
                
                var imgW, imgH;
                if (imgRatio >= espacioRatio) {
                    imgW = espacioW;
                    imgH = imgW / imgRatio;
                } else {
                    imgH = espacioH;
                    imgW = imgH * imgRatio;
                }
                
                var wrapperW = imgW + (bordeTotal * 2);
                var wrapperH = imgH + (bordeTotal * 2);
                
                $wrapper.css({ 'width': wrapperW + 'px', 'height': wrapperH + 'px' });
                $marcoLayer.css({ 'border': hayMarco ? bordeMarco + 'px solid ' + marcoColor : 'none' });
                $paspartuLayer.css({ 'background-color': hayPaspartu ? paspartuColor : 'transparent' });
                $imagenLayer.css({ 'top': bordeTotal + 'px', 'left': bordeTotal + 'px', 'width': imgW + 'px', 'height': imgH + 'px' });
            }
            
            function initDimensions() {
                var w = $productImage.width();
                var h = $productImage.height();
                if (w < 10 || h < 10) {
                    w = $productImage[0].naturalWidth || 400;
                    h = $productImage[0].naturalHeight || 400;
                }
                originalWidth = w;
                originalHeight = h;
                $fondoWrapper.css({ 'width': w + 'px', 'height': h + 'px' });
            }
            
            // Event listeners
            $('form.variations_form').on('change', 'select', function() { setTimeout(actualizar, 100); });
            $(document).on('woocommerce_variation_has_changed', function() { setTimeout(actualizar, 100); });
            $('.reset_variations').on('click', function() { setTimeout(actualizar, 100); });
            $(window).on('resize', function() { setTimeout(actualizar, 100); });
            
            // Inicializar
            if ($productImage[0].complete && $productImage[0].naturalWidth > 0) {
                initDimensions();
                actualizar();
            } else {
                $productImage.on('load', function() { initDimensions(); actualizar(); });
            }
            setTimeout(function() { initDimensions(); actualizar(); }, 500);
            
            // ========== LIGHTBOX ==========
            var currentMarcoColor = null, currentPaspartuColor = null, galleryImages = [], currentSlideIndex = 0;
            
            function actualizarLightboxState() {
                currentMarcoColor = buscarColorMarco($('#pa_marco').val());
                currentPaspartuColor = buscarColorPaspartu($('#pa_paspartu').val());
                galleryImages = [];
                $('.woocommerce-product-gallery__image').each(function() {
                    var $img = $(this).find('img'), $link = $(this).find('a');
                    galleryImages.push({ src: $link.attr('href') || $img.attr('data-large_image') || $img.attr('src') });
                });
            }
            
            function mostrarLightbox(startIndex) {
                currentSlideIndex = startIndex || 0;
                var $overlay = $('<div id="cuadros-lightbox"></div>').css({
                    position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
                    background: 'rgba(0,0,0,0.9)', zIndex: 999999,
                    display: 'flex', alignItems: 'center', justifyContent: 'center'
                });
                var $container = $('<div id="cuadros-lb-container"></div>').css({ position: 'relative' });
                var $closeBtn = $('<div class="cuadros-lb-close">&times;</div>').css({ position: 'absolute', top: '20px', right: '30px', color: '#fff', fontSize: '40px', cursor: 'pointer', zIndex: 10 });
                var $prevBtn = $('<div class="cuadros-lb-prev">&#10094;</div>').css({ position: 'absolute', left: '20px', top: '50%', transform: 'translateY(-50%)', color: '#fff', fontSize: '50px', cursor: 'pointer', zIndex: 10, display: galleryImages.length > 1 ? 'block' : 'none' });
                var $nextBtn = $('<div class="cuadros-lb-next">&#10095;</div>').css({ position: 'absolute', right: '20px', top: '50%', transform: 'translateY(-50%)', color: '#fff', fontSize: '50px', cursor: 'pointer', zIndex: 10, display: galleryImages.length > 1 ? 'block' : 'none' });
                
                $overlay.append($container).append($closeBtn).append($prevBtn).append($nextBtn);
                $('body').append($overlay);
                
                function mostrarImagen(index) {
                    if (index < 0) index = galleryImages.length - 1;
                    if (index >= galleryImages.length) index = 0;
                    currentSlideIndex = index;
                    $container.empty();
                    
                    var imgSrc = galleryImages[index].src;
                    var hayMarco = (index === 0) && currentMarcoColor;
                    var hayPaspartu = (index === 0) && currentPaspartuColor;
                    
                    if (hayMarco || hayPaspartu) {
                        var bordeMarco = hayMarco ? GROSOR_MARCO : 0;
                        var bordePaspartu = hayPaspartu ? GROSOR_PASPARTU : 0;
                        var bordeTotal = bordeMarco + bordePaspartu;
                        var maxSize = Math.min(window.innerWidth * 0.75, window.innerHeight * 0.75);
                        
                        var tempImg = new Image();
                        tempImg.onload = function() {
                            var imgRatio = tempImg.width / tempImg.height;
                            var imgW = imgRatio >= 1 ? maxSize - (bordeTotal * 2) : (maxSize - (bordeTotal * 2)) * imgRatio;
                            var imgH = imgRatio >= 1 ? imgW / imgRatio : maxSize - (bordeTotal * 2);
                            
                            $container.css({ width: (imgW + bordeTotal * 2) + 'px', height: (imgH + bordeTotal * 2) + 'px', background: hayPaspartu ? currentPaspartuColor : '#fff', border: hayMarco ? bordeMarco + 'px solid ' + currentMarcoColor : 'none', boxSizing: 'border-box' });
                            $('<div></div>').css({ position: 'absolute', top: bordePaspartu + 'px', left: bordePaspartu + 'px', width: imgW + 'px', height: imgH + 'px', overflow: 'hidden' }).append($('<img>').attr('src', imgSrc).css({ width: '100%', height: '100%', objectFit: 'fill' })).appendTo($container);
                        };
                        tempImg.src = imgSrc;
                    } else {
                        $container.css({ width: 'auto', height: 'auto', background: 'none', border: 'none' });
                        $('<img>').attr('src', imgSrc).css({ maxWidth: '80vw', maxHeight: '80vh', display: 'block' }).appendTo($container);
                    }
                }
                
                mostrarImagen(currentSlideIndex);
                $closeBtn.on('click', function() { $overlay.remove(); $(document).off('keydown.cuadrosLb'); });
                $prevBtn.on('click', function(e) { e.stopPropagation(); mostrarImagen(currentSlideIndex - 1); });
                $nextBtn.on('click', function(e) { e.stopPropagation(); mostrarImagen(currentSlideIndex + 1); });
                $overlay.on('click', function(e) { if (e.target === $overlay[0]) { $overlay.remove(); $(document).off('keydown.cuadrosLb'); } });
                $(document).on('keydown.cuadrosLb', function(e) {
                    if (e.keyCode === 27) { $overlay.remove(); $(document).off('keydown.cuadrosLb'); }
                    else if (e.keyCode === 37) mostrarImagen(currentSlideIndex - 1);
                    else if (e.keyCode === 39) mostrarImagen(currentSlideIndex + 1);
                });
            }
            
            $fondoWrapper.on('click', function(e) { if (currentMarcoColor || currentPaspartuColor) { e.preventDefault(); e.stopPropagation(); mostrarLightbox(0); } });
            $('form.variations_form').on('change', 'select', function() { setTimeout(actualizarLightboxState, 150); });
            setTimeout(actualizarLightboxState, 600);
        });
        </script>
        <?php
    }
}
