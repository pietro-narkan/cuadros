<?php
/**
 * Clase para la lógica frontend del plugin Cuadros
 * 
 * ENFOQUE: Marco y paspartú con CSS (bordes de color)
 * - Marco: borde exterior con color sólido
 * - Paspartú: espacio de color entre marco e imagen
 * - Imagen: centrada
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
        
        // Colores de marco y paspartú
        $marco_colors = isset($settings['marco_colors']) ? $settings['marco_colors'] : array();
        $paspartu_colors = isset($settings['paspartu_colors']) ? $settings['paspartu_colors'] : array();
        
        // Grosores configurables
        $grosor_marco = isset($settings['grosor_marco']) ? intval($settings['grosor_marco']) : 8;
        $grosor_paspartu = isset($settings['grosor_paspartu']) ? intval($settings['grosor_paspartu']) : 25;
        
        if (defined('CUADROS_SCRIPT_LOADED')) return;
        define('CUADROS_SCRIPT_LOADED', true);
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            
            // Configuración desde admin
            var coloresMarco = <?php echo json_encode($marco_colors); ?>;
            var coloresPaspartu = <?php echo json_encode($paspartu_colors); ?>;
            var GROSOR_MARCO = <?php echo $grosor_marco; ?>;
            var GROSOR_PASPARTU = <?php echo $grosor_paspartu; ?>;
            
            console.log('[cuadros] Iniciando - Grosor marco:', GROSOR_MARCO, 'px - Grosor paspartú:', GROSOR_PASPARTU, 'px');
            
            // Buscar elementos
            var $galleryImage = $('.woocommerce-product-gallery__image').first();
            var $productImage = $galleryImage.find('img').first();
            
            if ($productImage.length === 0) {
                console.log('[cuadros] No se encontró imagen');
                return;
            }
            
            // Guardar dimensiones originales
            var originalWidth = $productImage.width();
            var originalHeight = $productImage.height();
            
            // Crear estructura
            var $fondoWrapper = $('<div id="cuadros-fondo"></div>');
            var $wrapper = $('<div id="cuadros-wrapper"></div>');
            var $marcoLayer = $('<div id="layer-marco"></div>');
            var $paspartuLayer = $('<div id="layer-paspartu"></div>');
            var $imagenLayer = $('<div id="layer-imagen"></div>');
            
            $productImage.before($fondoWrapper);
            $fondoWrapper.append($wrapper);
            $wrapper.append($marcoLayer);
            $wrapper.append($paspartuLayer);
            $wrapper.append($imagenLayer);
            $imagenLayer.append($productImage);
            
            // Fondo gris detrás del cuadro
            $fondoWrapper.css({
                'background-color': '#f0f0f0',
                'display': 'flex',
                'align-items': 'center',
                'justify-content': 'center',
                'width': originalWidth + 'px',
                'height': originalHeight + 'px',
                'box-sizing': 'border-box'
            });
            
            // Estilos base
            $wrapper.css({
                'position': 'relative',
                'display': 'inline-block',
                'width': originalWidth + 'px',
                'height': originalHeight + 'px',
                'box-sizing': 'border-box'
            });
            
            // Marco: borde exterior
            $marcoLayer.css({
                'position': 'absolute',
                'top': '0', 'left': '0', 'right': '0', 'bottom': '0',
                'box-sizing': 'border-box',
                'pointer-events': 'none',
                'opacity': '0',
                'transition': 'opacity 0.3s, border-color 0.3s'
            });
            
            // Paspartú: fondo de color
            $paspartuLayer.css({
                'position': 'absolute',
                'box-sizing': 'border-box',
                'pointer-events': 'none',
                'opacity': '0',
                'transition': 'opacity 0.3s, background-color 0.3s'
            });
            
            // Imagen
            $imagenLayer.css({
                'position': 'absolute',
                'overflow': 'hidden'
            });
            
            $productImage.css({
                'display': 'block',
                'width': '100%',
                'height': '100%',
                'object-fit': 'fill'
            });
            
            // Funciones auxiliares
            function buscarColorMarco(val) {
                if (!val) return null;
                var v = val.toLowerCase().replace(/[\s-]/g, '_');
                for (var k in coloresMarco) {
                    var kn = k.toLowerCase().replace(/[\s-]/g, '_');
                    if (kn === v || kn.includes(v) || v.includes(kn)) {
                        return coloresMarco[k];
                    }
                }
                return null;
            }
            
            function buscarColorPaspartu(val) {
                if (!val) return null;
                
                // Detectar "sin paspartú" o variantes
                var valLower = val.toLowerCase();
                if (valLower.includes('sin ') || valLower === 'ninguno' || valLower === 'none' || valLower === 'no') {
                    return null; // Sin paspartú
                }
                
                var v = val.toLowerCase().replace(/[\s-]/g, '_');
                for (var k in coloresPaspartu) {
                    var kn = k.toLowerCase().replace(/[\s-]/g, '_');
                    if (kn === v || kn.includes(v) || v.includes(kn)) {
                        return coloresPaspartu[k];
                    }
                }
                return null;
            }
            
            // Función principal
            function actualizar() {
                var marcoVal = $('#pa_marco').val();
                var paspartuVal = $('#pa_paspartu').val();
                
                var marcoColor = buscarColorMarco(marcoVal);
                var paspartuColor = buscarColorPaspartu(paspartuVal);
                
                var hayMarco = !!marcoColor;
                var hayPaspartu = !!paspartuColor;
                
                console.log('[cuadros] Actualizar:', {marco: marcoVal, paspartu: paspartuVal, hayMarco, hayPaspartu});
                
                // Si no hay nada, restaurar
                if (!hayMarco && !hayPaspartu) {
                    $wrapper.css({ 'width': originalWidth + 'px', 'height': originalHeight + 'px' });
                    $imagenLayer.css({ 'top': '0', 'left': '0', 'width': '100%', 'height': '100%' });
                    $marcoLayer.css('opacity', '0');
                    $paspartuLayer.css('opacity', '0');
                    return;
                }
                
                // Calcular grosores totales
                var bordeMarco = hayMarco ? GROSOR_MARCO : 0;
                var bordePaspartu = hayPaspartu ? GROSOR_PASPARTU : 0;
                var bordeTotal = bordeMarco + bordePaspartu;
                
                // Proporciones de la imagen
                var imgNaturalW = $productImage[0].naturalWidth || originalWidth;
                var imgNaturalH = $productImage[0].naturalHeight || originalHeight;
                var imgRatio = imgNaturalW / imgNaturalH;
                
                // Calcular tamaño de imagen que cabe en el espacio disponible
                var espacioW = originalWidth - (bordeTotal * 2);
                var espacioH = originalHeight - (bordeTotal * 2);
                
                var imgW, imgH;
                if (imgRatio >= (espacioW / espacioH)) {
                    imgW = espacioW;
                    imgH = imgW / imgRatio;
                } else {
                    imgH = espacioH;
                    imgW = imgH * imgRatio;
                }
                
                // El wrapper se ajusta a imagen + bordes
                var wrapperW = imgW + (bordeTotal * 2);
                var wrapperH = imgH + (bordeTotal * 2);
                
                $wrapper.css({ 
                    'width': wrapperW + 'px', 
                    'height': wrapperH + 'px' 
                });
                
                // Marco: borde exterior
                if (hayMarco) {
                    $marcoLayer.css({
                        'border': bordeMarco + 'px solid ' + marcoColor,
                        'opacity': '1'
                    });
                } else {
                    $marcoLayer.css('opacity', '0');
                }
                
                // Paspartú: fondo entre marco e imagen
                if (hayPaspartu) {
                    $paspartuLayer.css({
                        'top': bordeMarco + 'px',
                        'left': bordeMarco + 'px',
                        'right': bordeMarco + 'px',
                        'bottom': bordeMarco + 'px',
                        'background-color': paspartuColor,
                        'opacity': '1'
                    });
                } else {
                    $paspartuLayer.css('opacity', '0');
                }
                
                // Imagen: posicionada dentro del paspartú
                $imagenLayer.css({
                    'top': bordeTotal + 'px',
                    'left': bordeTotal + 'px',
                    'width': imgW + 'px',
                    'height': imgH + 'px'
                });
                
                console.log('[cuadros] Wrapper:', wrapperW.toFixed(0), 'x', wrapperH.toFixed(0), '- Borde total:', bordeTotal, 'px');
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
            var currentMarcoColor = null;
            var currentPaspartuColor = null;
            var galleryImages = [];
            var currentSlideIndex = 0;
            
            function obtenerImagenesGaleria() {
                galleryImages = [];
                $('.woocommerce-product-gallery__image').each(function(index) {
                    var $img = $(this).find('img');
                    var $link = $(this).find('a');
                    galleryImages.push({
                        src: $link.attr('href') || $img.attr('data-large_image') || $img.attr('src'),
                        alt: $img.attr('alt') || ''
                    });
                });
            }
            
            function actualizarLightboxState() {
                var marcoVal = $('#pa_marco').val();
                var paspartuVal = $('#pa_paspartu').val();
                currentMarcoColor = buscarColorMarco(marcoVal);
                currentPaspartuColor = buscarColorPaspartu(paspartuVal);
                obtenerImagenesGaleria();
            }
            
            function mostrarLightbox(startIndex) {
                currentSlideIndex = startIndex || 0;
                
                var $overlay = $('<div id="cuadros-lightbox"></div>').css({
                    position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
                    background: 'rgba(0,0,0,0.9)', zIndex: 999999,
                    display: 'flex', alignItems: 'center', justifyContent: 'center'
                });
                
                var $container = $('<div id="cuadros-lb-container"></div>').css({ position: 'relative' });
                
                var $closeBtn = $('<div class="cuadros-lb-close">&times;</div>').css({
                    position: 'absolute', top: '20px', right: '30px',
                    color: '#fff', fontSize: '40px', cursor: 'pointer', zIndex: 10
                });
                
                var $prevBtn = $('<div class="cuadros-lb-prev">&#10094;</div>').css({
                    position: 'absolute', left: '20px', top: '50%',
                    transform: 'translateY(-50%)',
                    color: '#fff', fontSize: '50px', cursor: 'pointer', zIndex: 10,
                    display: galleryImages.length > 1 ? 'block' : 'none'
                });
                
                var $nextBtn = $('<div class="cuadros-lb-next">&#10095;</div>').css({
                    position: 'absolute', right: '20px', top: '50%',
                    transform: 'translateY(-50%)',
                    color: '#fff', fontSize: '50px', cursor: 'pointer', zIndex: 10,
                    display: galleryImages.length > 1 ? 'block' : 'none'
                });
                
                var $counter = $('<div class="cuadros-lb-counter"></div>').css({
                    position: 'absolute', top: '20px', left: '30px',
                    color: '#fff', fontSize: '16px', zIndex: 10
                });
                
                $overlay.append($container).append($closeBtn).append($prevBtn).append($nextBtn).append($counter);
                $('body').append($overlay);
                
                function mostrarImagen(index) {
                    if (index < 0) index = galleryImages.length - 1;
                    if (index >= galleryImages.length) index = 0;
                    currentSlideIndex = index;
                    
                    $container.empty();
                    $counter.text((index + 1) + ' / ' + galleryImages.length);
                    
                    var imgSrc = galleryImages[index].src;
                    var esPrimeraImagen = (index === 0);
                    var hayMarco = esPrimeraImagen && currentMarcoColor;
                    var hayPaspartu = esPrimeraImagen && currentPaspartuColor;
                    
                    if (hayMarco || hayPaspartu) {
                        var bordeMarco = hayMarco ? GROSOR_MARCO : 0;
                        var bordePaspartu = hayPaspartu ? GROSOR_PASPARTU : 0;
                        var bordeTotal = bordeMarco + bordePaspartu;
                        var fondoColor = hayPaspartu ? currentPaspartuColor : '#ffffff';
                        
                        var maxSize = Math.min(window.innerWidth * 0.75, window.innerHeight * 0.75);
                        
                        var tempImg = new Image();
                        tempImg.onload = function() {
                            var imgRatio = tempImg.width / tempImg.height;
                            var imgW, imgH;
                            
                            if (imgRatio >= 1) {
                                imgW = maxSize - (bordeTotal * 2);
                                imgH = imgW / imgRatio;
                            } else {
                                imgH = maxSize - (bordeTotal * 2);
                                imgW = imgH * imgRatio;
                            }
                            
                            var lbW = imgW + (bordeTotal * 2);
                            var lbH = imgH + (bordeTotal * 2);
                            
                            $container.css({ 
                                width: lbW + 'px', 
                                height: lbH + 'px',
                                background: fondoColor,
                                border: hayMarco ? bordeMarco + 'px solid ' + currentMarcoColor : 'none',
                                boxSizing: 'border-box'
                            });
                            
                            $('<div></div>').css({
                                position: 'absolute',
                                top: bordePaspartu + 'px',
                                left: bordePaspartu + 'px',
                                width: imgW + 'px',
                                height: imgH + 'px',
                                overflow: 'hidden'
                            }).append(
                                $('<img>').attr('src', imgSrc).css({
                                    width: '100%',
                                    height: '100%',
                                    objectFit: 'fill'
                                })
                            ).appendTo($container);
                        };
                        tempImg.src = imgSrc;
                    } else {
                        $container.css({ width: 'auto', height: 'auto', background: 'none', border: 'none' });
                        $('<img>').attr('src', imgSrc).css({
                            maxWidth: '80vw', maxHeight: '80vh', display: 'block'
                        }).appendTo($container);
                    }
                }
                
                mostrarImagen(currentSlideIndex);
                
                $closeBtn.on('click', function() { 
                    $overlay.remove(); 
                    $(document).off('keydown.cuadrosLb');
                });
                
                $prevBtn.on('click', function(e) {
                    e.stopPropagation();
                    mostrarImagen(currentSlideIndex - 1);
                });
                
                $nextBtn.on('click', function(e) {
                    e.stopPropagation();
                    mostrarImagen(currentSlideIndex + 1);
                });
                
                $overlay.on('click', function(e) {
                    if (e.target === $overlay[0]) {
                        $overlay.remove();
                        $(document).off('keydown.cuadrosLb');
                    }
                });
                
                $(document).on('keydown.cuadrosLb', function(e) {
                    if (e.keyCode === 27) {
                        $overlay.remove();
                        $(document).off('keydown.cuadrosLb');
                    } else if (e.keyCode === 37) {
                        mostrarImagen(currentSlideIndex - 1);
                    } else if (e.keyCode === 39) {
                        mostrarImagen(currentSlideIndex + 1);
                    }
                });
            }
            
            $fondoWrapper.on('click', function(e) {
                if (currentMarcoColor || currentPaspartuColor) {
                    e.preventDefault();
                    e.stopPropagation();
                    mostrarLightbox(0);
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
