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
        wp_enqueue_style('cuadros-frontend-style', CUADROS_ASSETS_URL . 'css/frontend.css', array(), CUADROS_VERSION . '.' . time());
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
        (function($) {
            $(window).on('load', function() {
                var coloresMarco = <?php echo json_encode($marco_colors); ?>;
                var coloresPaspartu = <?php echo json_encode($paspartu_colors); ?>;
                var GROSOR_MARCO = <?php echo $grosor_marco; ?>;
                var GROSOR_PASPARTU = <?php echo $grosor_paspartu; ?>;
                var FACTOR_ESCALA = 0.85;
                
                var $galleryImage = $('.woocommerce-product-gallery__image').first();
                var $productImage = $galleryImage.find('img').first();
                
                if ($productImage.length === 0) {
                    console.log('[cuadros] No se encontró imagen');
                    return;
                }
                
                // Esperar a que la imagen tenga dimensiones
                var originalWidth = $productImage.width();
                var originalHeight = $productImage.height();
                
                if (originalWidth < 50 || originalHeight < 50) {
                    originalWidth = $productImage[0].naturalWidth || 400;
                    originalHeight = $productImage[0].naturalHeight || 400;
                }
                
                console.log('[cuadros] Iniciando con dimensiones:', originalWidth, 'x', originalHeight);
                
                // Crear estructura envolviendo la imagen
                var $fondoWrapper = $('<div id="cuadros-fondo"></div>');
                var $wrapper = $('<div id="cuadros-wrapper"></div>');
                var $marcoLayer = $('<div id="layer-marco"></div>');
                var $paspartuLayer = $('<div id="layer-paspartu"></div>');
                var $imagenLayer = $('<div id="layer-imagen"></div>');
                
                // Insertar estructura
                $productImage.wrap($imagenLayer);
                $imagenLayer = $productImage.parent();
                $imagenLayer.wrap($wrapper);
                $wrapper = $imagenLayer.parent();
                $wrapper.prepend($paspartuLayer).prepend($marcoLayer);
                $wrapper.wrap($fondoWrapper);
                $fondoWrapper = $wrapper.parent();
                
                // Aplicar estilos al fondo
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
                    
                    // Calcular espacio disponible (85% del contenedor menos bordes)
                    var espacioW = Math.max(50, (originalWidth * FACTOR_ESCALA) - (bordeTotal * 2));
                    var espacioH = Math.max(50, (originalHeight * FACTOR_ESCALA) - (bordeTotal * 2));
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
                    
                    var wrapperW = imgW + (bordeTotal * 2);
                    var wrapperH = imgH + (bordeTotal * 2);
                    
                    // Aplicar estilos
                    $wrapper.css({ 'width': wrapperW + 'px', 'height': wrapperH + 'px' });
                    
                    if (hayMarco) {
                        $marcoLayer.css({ 'border': bordeMarco + 'px solid ' + marcoColor });
                    } else {
                        $marcoLayer.css({ 'border': 'none' });
                    }
                    
                    if (hayPaspartu) {
                        $paspartuLayer.css({ 'background-color': paspartuColor });
                    } else {
                        $paspartuLayer.css({ 'background-color': 'transparent' });
                    }
                    
                    $imagenLayer.css({
                        'top': bordeTotal + 'px',
                        'left': bordeTotal + 'px',
                        'width': imgW + 'px',
                        'height': imgH + 'px'
                    });
                }
                
                // Event listeners
                $('form.variations_form').on('change', 'select', function() {
                    setTimeout(actualizar, 100);
                });
                
                $(document).on('woocommerce_variation_has_changed', function() {
                    setTimeout(actualizar, 100);
                });
                
                $('.reset_variations').on('click', function() {
                    setTimeout(actualizar, 100);
                });
                
                // Ejecutar al inicio
                actualizar();
                
                // ========== LIGHTBOX ==========
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
                        galleryImages.push({
                            src: $link.attr('href') || $img.attr('data-large_image') || $img.attr('src')
                        });
                    });
                }
                
                function mostrarLightbox(startIndex) {
                    var currentSlideIndex = startIndex || 0;
                    
                    var $overlay = $('<div id="cuadros-lightbox"></div>').css({
                        position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
                        background: 'rgba(0,0,0,0.9)', zIndex: 999999,
                        display: 'flex', alignItems: 'center', justifyContent: 'center'
                    });
                    
                    var $container = $('<div></div>').css({ position: 'relative' });
                    var $closeBtn = $('<div>&times;</div>').css({
                        position: 'absolute', top: '20px', right: '30px',
                        color: '#fff', fontSize: '40px', cursor: 'pointer', zIndex: 10
                    });
                    var $prevBtn = $('<div>&#10094;</div>').css({
                        position: 'absolute', left: '20px', top: '50%', transform: 'translateY(-50%)',
                        color: '#fff', fontSize: '50px', cursor: 'pointer', zIndex: 10,
                        display: galleryImages.length > 1 ? 'block' : 'none'
                    });
                    var $nextBtn = $('<div>&#10095;</div>').css({
                        position: 'absolute', right: '20px', top: '50%', transform: 'translateY(-50%)',
                        color: '#fff', fontSize: '50px', cursor: 'pointer', zIndex: 10,
                        display: galleryImages.length > 1 ? 'block' : 'none'
                    });
                    
                    $overlay.append($container, $closeBtn, $prevBtn, $nextBtn);
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
                                var ratio = tempImg.width / tempImg.height;
                                var imgW, imgH;
                                if (ratio >= 1) {
                                    imgW = maxSize - (bordeTotal * 2);
                                    imgH = imgW / ratio;
                                } else {
                                    imgH = maxSize - (bordeTotal * 2);
                                    imgW = imgH * ratio;
                                }
                                
                                $container.css({
                                    width: (imgW + bordeTotal * 2) + 'px',
                                    height: (imgH + bordeTotal * 2) + 'px',
                                    background: hayPaspartu ? currentPaspartuColor : '#fff',
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
                                    $('<img>').attr('src', imgSrc).css({ width: '100%', height: '100%', objectFit: 'fill' })
                                ).appendTo($container);
                            };
                            tempImg.src = imgSrc;
                        } else {
                            $container.css({ width: 'auto', height: 'auto', background: 'none', border: 'none' });
                            $('<img>').attr('src', imgSrc).css({ maxWidth: '80vw', maxHeight: '80vh', display: 'block' }).appendTo($container);
                        }
                    }
                    
                    mostrarImagen(currentSlideIndex);
                    
                    $closeBtn.on('click', function() { $overlay.remove(); });
                    $prevBtn.on('click', function(e) { e.stopPropagation(); mostrarImagen(currentSlideIndex - 1); });
                    $nextBtn.on('click', function(e) { e.stopPropagation(); mostrarImagen(currentSlideIndex + 1); });
                    $overlay.on('click', function(e) { if (e.target === $overlay[0]) $overlay.remove(); });
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
                
                actualizarLightboxState();
            });
        })(jQuery);
        </script>
        <?php
    }
}
