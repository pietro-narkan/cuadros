<?php
/**
 * Clase para la lógica frontend del plugin Cuadros
 * 
 * NUEVO ENFOQUE: El marco y paspartú se posicionan ALREDEDOR de la imagen,
 * no detrás. La imagen mantiene su tamaño y proporción original.
 */
class Cuadros_Frontend {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_shortcode('cuadros_visualizador', array($this, 'render_visualizador_shortcode'));
        add_action('wp_footer', array($this, 'output_frontend_script_auto'));
    }
    
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
    
    public function render_visualizador_shortcode($atts = array()) {
        if (!is_product()) {
            return '<p class="cuadros-notice">' . __('El visualizador solo funciona en páginas de producto.', 'cuadros') . '</p>';
        }
        
        global $product;
        
        if (!$product || !$product->is_type('variable')) {
            return '<p class="cuadros-notice">' . __('El visualizador solo funciona con productos variables.', 'cuadros') . '</p>';
        }
        
        $attributes = $product->get_attributes();
        $has_marco = isset($attributes['pa_marco']);
        $has_paspartu = isset($attributes['pa_paspartu']);
        
        if (!$has_marco && !$has_paspartu) {
            return '<p class="cuadros-notice">' . __('Este producto no tiene atributos de marco o paspartú.', 'cuadros') . '</p>';
        }
        
        $this->enqueue_frontend_scripts();
        return '<div id="cuadros-visualizador-container"></div>';
    }
    
    public function output_frontend_script_auto() {
        if (!is_product()) {
            return;
        }
        
        global $product;
        
        if (!$product || !$product->is_type('variable')) {
            return;
        }
        
        $attributes = $product->get_attributes();
        if (!isset($attributes['pa_marco']) && !isset($attributes['pa_paspartu'])) {
            return;
        }
        
        $this->output_frontend_script();
    }
    
    public function output_frontend_script() {
        $settings = get_option('cuadros_settings', array());
        
        $marco_images = isset($settings['marco_images']) ? $settings['marco_images'] : array();
        $paspartu_colors = isset($settings['paspartu_colors']) ? $settings['paspartu_colors'] : array();
        
        // Estructurar marcos para JS
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
        
        if (defined('CUADROS_SCRIPT_LOADED')) {
            return;
        }
        define('CUADROS_SCRIPT_LOADED', true);
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            
            // ========== CONFIGURACIÓN ==========
            var urlsMarcos = <?php echo json_encode($urls_marcos); ?>;
            var coloresPaspartu = <?php echo json_encode($paspartu_colors); ?>;
            
            // Grosor del marco y paspartú en píxeles (ajustable)
            var GROSOR_MARCO = 15;      // Grosor del borde del marco
            var GROSOR_PASPARTU = 25;   // Grosor del paspartú (espacio entre marco e imagen)
            
            console.log('[cuadros] Marcos:', Object.keys(urlsMarcos));
            console.log('[cuadros] Paspartús:', Object.keys(coloresPaspartu));
            
            // ========== BUSCAR ELEMENTOS ==========
            var $galleryImage = $('.woocommerce-product-gallery__image').first();
            var $productImage = $galleryImage.find('img').first();
            var $imageLink = $productImage.closest('a');
            var $container = $imageLink.length ? $imageLink : $productImage.parent();
            
            if ($productImage.length === 0) {
                console.log('[cuadros] No se encontró imagen del producto');
                return;
            }
            
            // ========== CREAR ESTRUCTURA ==========
            // Crear wrapper que contendrá todo (marco + paspartú + imagen)
            var $wrapper = $('<div id="cuadros-wrapper"></div>');
            var $marcoLayer = $('<div id="layer-marco"></div>');
            var $paspartuLayer = $('<div id="layer-paspartu"></div>');
            var $imagenWrapper = $('<div id="cuadros-imagen-wrapper"></div>');
            
            // Insertar wrapper antes de la imagen y mover la imagen dentro
            $productImage.before($wrapper);
            $wrapper.append($marcoLayer);
            $wrapper.append($paspartuLayer);
            $wrapper.append($imagenWrapper);
            $imagenWrapper.append($productImage);
            
            // ========== ESTILOS BASE ==========
            $wrapper.css({
                'position': 'relative',
                'display': 'inline-block',
                'max-width': '100%',
                'transition': 'all 0.3s ease'
            });
            
            // PASPARTÚ: z-index 1 (más atrás)
            $paspartuLayer.css({
                'position': 'absolute',
                'pointer-events': 'none',
                'opacity': '0',
                'transition': 'opacity 0.3s ease',
                'z-index': '1'
            });
            
            // MARCO: z-index 2 (encima del paspartú)
            $marcoLayer.css({
                'position': 'absolute',
                'top': '0',
                'left': '0',
                'right': '0',
                'bottom': '0',
                'pointer-events': 'none',
                'background-size': '100% 100%',
                'background-repeat': 'no-repeat',
                'opacity': '0',
                'transition': 'opacity 0.3s ease',
                'z-index': '2'
            });
            
            // IMAGEN: z-index 3 (encima de todo)
            $imagenWrapper.css({
                'position': 'relative',
                'z-index': '3',
                'line-height': '0'
            });
            
            $productImage.css({
                'display': 'block',
                'max-width': '100%',
                'height': 'auto'
            });
            
            // ========== FUNCIONES AUXILIARES ==========
            function detectarOrientacion() {
                // Buscar en TODOS los selectores de variaciones
                var orientacionDetectada = null;
                
                $('form.variations_form select').each(function() {
                    var selectId = $(this).attr('id') || '';
                    var val = $(this).val() || '';
                    var opt = $(this).find('option:selected').text() || '';
                    var textoCompleto = (selectId + ' ' + val + ' ' + opt).toLowerCase();
                    
                    console.log('[cuadros] Analizando selector ID:', selectId, '- Valor:', val, '- Texto:', opt);
                    
                    // Detectar "1:1" o "1x1" o "cuadrado" - PRIORIDAD ALTA
                    if (textoCompleto.match(/1\s*[:\-x]\s*1/) || textoCompleto.includes('cuadrado') || textoCompleto.includes('square')) {
                        orientacionDetectada = '1:1';
                        console.log('[cuadros] >>> Detectado 1:1');
                        return false; // break
                    }
                });
                
                // Si encontramos 1:1, retornar inmediatamente
                if (orientacionDetectada === '1:1') {
                    return '1:1';
                }
                
                // Segunda pasada: buscar horizontal/vertical o dimensiones
                $('form.variations_form select').each(function() {
                    var val = $(this).val() || '';
                    var opt = $(this).find('option:selected').text() || '';
                    var textoCompleto = (val + ' ' + opt).toLowerCase();
                    
                    // Detectar "horizontal" o "landscape"
                    if (textoCompleto.includes('horizontal') || textoCompleto.includes('landscape') || textoCompleto.includes('apaisado')) {
                        orientacionDetectada = 'horizontal';
                        return false;
                    }
                    
                    // Detectar "vertical" o "portrait"
                    if (textoCompleto.includes('vertical') || textoCompleto.includes('portrait') || textoCompleto.includes('retrato')) {
                        orientacionDetectada = 'vertical';
                        return false;
                    }
                    
                    // Detectar dimensiones tipo "60x40" - SOLO si no hay otra detección
                    if (!orientacionDetectada) {
                        var match = textoCompleto.match(/(\d+)\s*[xX]\s*(\d+)/);
                        if (match) {
                            var ancho = parseInt(match[1]);
                            var alto = parseInt(match[2]);
                            if (Math.abs(ancho - alto) <= 5) {
                                orientacionDetectada = '1:1';
                            } else {
                                orientacionDetectada = (ancho < alto) ? 'vertical' : 'horizontal';
                            }
                        }
                    }
                });
                
                if (orientacionDetectada) {
                    console.log('[cuadros] Orientación final:', orientacionDetectada);
                    return orientacionDetectada;
                }
                
                // Fallback: detectar por dimensiones de imagen
                var imgW = $productImage[0].naturalWidth || $productImage.width();
                var imgH = $productImage[0].naturalHeight || $productImage.height();
                if (imgW > 0 && imgH > 0) {
                    var ratio = imgW / imgH;
                    if (ratio > 0.95 && ratio < 1.05) return '1:1';
                    return (ratio < 1) ? 'vertical' : 'horizontal';
                }
                
                return 'vertical';
            }
            
            function buscarMarco(marcoVal, orientacion) {
                if (!marcoVal) return null;
                
                var marcoValNorm = marcoVal.toLowerCase().replace(/-/g, ' ');
                
                for (var key in urlsMarcos) {
                    var keyNorm = key.toLowerCase().replace(/-/g, ' ');
                    if (keyNorm === marcoValNorm || keyNorm.includes(marcoValNorm) || marcoValNorm.includes(keyNorm)) {
                        // Buscar orientación exacta o fallback
                        if (urlsMarcos[key][orientacion]) {
                            return urlsMarcos[key][orientacion];
                        }
                        // Fallback: usar cualquier orientación disponible
                        for (var orient in urlsMarcos[key]) {
                            return urlsMarcos[key][orient];
                        }
                    }
                }
                return null;
            }
            
            function buscarPaspartu(paspartuVal) {
                if (!paspartuVal) return null;
                
                var paspartuValNorm = paspartuVal.toLowerCase();
                for (var key in coloresPaspartu) {
                    if (key.toLowerCase() === paspartuValNorm) {
                        return coloresPaspartu[key];
                    }
                }
                return null;
            }
            
            // ========== FUNCIÓN PRINCIPAL ==========
            function actualizarVisualizacion() {
                var marcoVal = $('#pa_marco').val();
                var paspartuVal = $('#pa_paspartu').val();
                var orientacion = detectarOrientacion();
                
                var marcoUrl = buscarMarco(marcoVal, orientacion);
                var paspartuColor = buscarPaspartu(paspartuVal);
                
                var hayMarco = marcoUrl !== null;
                var hayPaspartu = paspartuColor !== null;
                
                console.log('[cuadros] Actualizar:', {
                    marco: marcoVal,
                    paspartu: paspartuVal,
                    orientacion: orientacion,
                    hayMarco: hayMarco,
                    hayPaspartu: hayPaspartu
                });
                
                // Si no hay marco ni paspartú, restaurar estado original
                if (!hayMarco && !hayPaspartu) {
                    $wrapper.css({ 'width': '', 'height': '' });
                    $imagenWrapper.css({ 'padding': '0', 'width': '', 'height': '' });
                    $productImage.css({ 'width': '', 'height': '', 'object-fit': '', 'max-width': '100%' });
                    $marcoLayer.css('opacity', '0');
                    $paspartuLayer.css('opacity', '0');
                    return;
                }
                
                // Calcular padding según lo que esté activo
                var paddingMarco = hayMarco ? GROSOR_MARCO : 0;
                var paddingPaspartu = hayPaspartu ? GROSOR_PASPARTU : 0;
                var paddingTotal = paddingMarco + paddingPaspartu;
                
                // Obtener dimensiones originales de la imagen
                var imgNaturalW = $productImage[0].naturalWidth;
                var imgNaturalH = $productImage[0].naturalHeight;
                
                // Obtener ancho máximo disponible
                var maxContainerWidth = $container.parent().width() || 400;
                
                // Calcular tamaño de la imagen según orientación
                var imgWidth, imgHeight;
                
                if (orientacion === '1:1') {
                    // Para 1:1: hacer cuadrado usando el lado menor de la imagen natural
                    var baseSize = Math.min(imgNaturalW, imgNaturalH);
                    // Limitar al contenedor disponible
                    var maxSize = maxContainerWidth - (paddingTotal * 2);
                    imgWidth = imgHeight = Math.min(baseSize, maxSize);
                    
                    console.log('[cuadros] 1:1 - Cuadrado de', imgWidth, 'px');
                } else if (orientacion === 'horizontal') {
                    // Horizontal: ancho > alto
                    imgWidth = maxContainerWidth - (paddingTotal * 2);
                    imgHeight = imgWidth * 0.75; // Ratio 4:3 aproximado
                } else {
                    // Vertical: alto > ancho (mantener proporción original pero limitado)
                    var maxImgWidth = maxContainerWidth - (paddingTotal * 2);
                    var ratio = imgNaturalH / imgNaturalW;
                    imgWidth = maxImgWidth;
                    imgHeight = maxImgWidth * ratio;
                }
                
                // Aplicar estilos a la imagen
                $productImage.css({
                    'width': imgWidth + 'px',
                    'height': imgHeight + 'px',
                    'max-width': 'none',
                    'object-fit': 'cover',
                    'display': 'block'
                });
                
                // Aplicar padding al wrapper de la imagen
                $imagenWrapper.css({
                    'padding': paddingTotal + 'px'
                });
                
                // Configurar capa del marco (cubre todo el wrapper)
                if (hayMarco) {
                    $marcoLayer.css({
                        'background-image': 'url(' + marcoUrl + ')',
                        'opacity': '1',
                        'top': '0',
                        'left': '0',
                        'right': '0',
                        'bottom': '0'
                    });
                } else {
                    $marcoLayer.css({
                        'background-image': 'none',
                        'opacity': '0'
                    });
                }
                
                // El paspartú se aplica como BORDE de la imagen, no como capa separada
                // Esto crea el efecto de color entre el marco y la imagen
                if (hayPaspartu) {
                    $imagenWrapper.css({
                        'padding': paddingTotal + 'px',
                        'background-color': paspartuColor
                    });
                    $paspartuLayer.css('opacity', '0'); // No usamos la capa separada
                } else {
                    $imagenWrapper.css({
                        'padding': paddingTotal + 'px',
                        'background-color': 'transparent'
                    });
                    $paspartuLayer.css('opacity', '0');
                }
                
                console.log('[cuadros] Dimensiones finales - Imagen:', imgWidth, 'x', imgHeight, '- Padding total:', paddingTotal);
            }
            
            // ========== EVENT LISTENERS ==========
            $('form.variations_form').on('change', 'select', function() {
                setTimeout(actualizarVisualizacion, 100);
            });
            
            $(document).on('woocommerce_variation_has_changed', function() {
                setTimeout(actualizarVisualizacion, 100);
            });
            
            $('.reset_variations').on('click', function() {
                $imagenWrapper.css({ 'padding': '0', 'width': '', 'height': '' });
                $productImage.css({ 'width': '', 'height': '', 'object-fit': '', 'max-width': '100%' });
                $marcoLayer.css('opacity', '0');
                $paspartuLayer.css('opacity', '0');
            });
            
            $(window).on('resize', function() {
                setTimeout(actualizarVisualizacion, 100);
            });
            
            // Ejecutar cuando la imagen cargue
            if ($productImage[0].complete) {
                setTimeout(actualizarVisualizacion, 300);
            } else {
                $productImage.on('load', function() {
                    setTimeout(actualizarVisualizacion, 100);
                });
            }
            
            // Ejecutar inicialmente
            setTimeout(actualizarVisualizacion, 500);
            

            // ========== LIGHTBOX PERSONALIZADO ==========
            var currentMarcoUrl = null;
            var currentPaspartuColor = null;
            var lightboxActivo = false;
            var currentSlideIndex = 0;
            var galleryImages = [];
            
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
            }
            
            function actualizarEstadoLightbox() {
                var marcoVal = $('#pa_marco').val();
                var paspartuVal = $('#pa_paspartu').val();
                var orientacion = detectarOrientacion();
                
                currentMarcoUrl = buscarMarco(marcoVal, orientacion);
                currentPaspartuColor = buscarPaspartu(paspartuVal);
                obtenerImagenesGaleria();
            }
            
            function mostrarLightboxCuadros(startIndex) {
                lightboxActivo = true;
                currentSlideIndex = startIndex || 0;
                
                var $overlay = $('<div id="cuadros-lightbox-overlay"></div>').css({
                    position: 'fixed',
                    top: 0, left: 0,
                    width: '100%', height: '100%',
                    backgroundColor: 'rgba(0,0,0,0.95)',
                    zIndex: 9999999,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                });
                
                var $imgContainer = $('<div id="cuadros-lightbox-container"></div>').css({
                    position: 'relative',
                    display: 'inline-block'
                });
                
                var $closeBtn = $('<div class="cuadros-lb-close">&times;</div>').css({
                    position: 'absolute',
                    top: '15px', right: '20px',
                    color: 'white',
                    fontSize: '45px',
                    cursor: 'pointer',
                    zIndex: 10000001
                });
                
                var $prevBtn = $('<div class="cuadros-lb-prev">&#10094;</div>').css({
                    position: 'absolute',
                    left: '15px', top: '50%',
                    transform: 'translateY(-50%)',
                    color: 'white',
                    fontSize: '50px',
                    cursor: 'pointer',
                    zIndex: 10000001,
                    opacity: galleryImages.length > 1 ? 1 : 0
                });
                
                var $nextBtn = $('<div class="cuadros-lb-next">&#10095;</div>').css({
                    position: 'absolute',
                    right: '15px', top: '50%',
                    transform: 'translateY(-50%)',
                    color: 'white',
                    fontSize: '50px',
                    cursor: 'pointer',
                    zIndex: 10000001,
                    opacity: galleryImages.length > 1 ? 1 : 0
                });
                
                var $counter = $('<div class="cuadros-lb-counter"></div>').css({
                    position: 'absolute',
                    top: '20px', left: '20px',
                    color: 'white',
                    fontSize: '16px',
                    zIndex: 10000001
                });
                
                $overlay.append($imgContainer).append($closeBtn).append($prevBtn).append($nextBtn).append($counter);
                $('body').append($overlay);
                
                function mostrarImagen(index) {
                    if (index < 0) index = galleryImages.length - 1;
                    if (index >= galleryImages.length) index = 0;
                    currentSlideIndex = index;
                    
                    var imgData = galleryImages[index];
                    $imgContainer.empty();
                    $counter.text((index + 1) + ' / ' + galleryImages.length);
                    
                    // Crear estructura: marco > paspartú > imagen
                    var hayMarco = index === 0 && currentMarcoUrl;
                    var hayPaspartu = index === 0 && currentPaspartuColor;
                    var orientacion = detectarOrientacion();
                    
                    var paddingMarco = hayMarco ? GROSOR_MARCO : 0;
                    var paddingPaspartu = hayPaspartu ? GROSOR_PASPARTU : 0;
                    var paddingTotal = paddingMarco + paddingPaspartu;
                    
                    var $lbWrapper = $('<div class="cuadros-lb-wrapper"></div>').css({
                        position: 'relative',
                        display: 'inline-block'
                    });
                    
                    var $lbMarco = $('<div class="cuadros-lb-marco"></div>').css({
                        position: 'absolute',
                        top: 0, left: 0, right: 0, bottom: 0,
                        backgroundSize: '100% 100%',
                        backgroundRepeat: 'no-repeat',
                        zIndex: 1,
                        opacity: hayMarco ? 1 : 0,
                        backgroundImage: hayMarco ? 'url(' + currentMarcoUrl + ')' : 'none'
                    });
                    
                    var $lbPaspartu = $('<div class="cuadros-lb-paspartu"></div>').css({
                        position: 'absolute',
                        top: paddingMarco + 'px',
                        left: paddingMarco + 'px',
                        right: paddingMarco + 'px',
                        bottom: paddingMarco + 'px',
                        backgroundColor: hayPaspartu ? currentPaspartuColor : 'transparent',
                        zIndex: 2,
                        opacity: hayPaspartu ? 1 : 0
                    });
                    
                    var $lbImgWrapper = $('<div class="cuadros-lb-img-wrapper"></div>').css({
                        position: 'relative',
                        zIndex: 3,
                        padding: paddingTotal + 'px',
                        lineHeight: 0
                    });
                    
                    var $img = $('<img>').attr('src', imgData.large).attr('alt', imgData.alt);
                    
                    // Para 1:1, forzar cuadrado
                    if (orientacion === '1:1' && (hayMarco || hayPaspartu)) {
                        var maxSize = Math.min(window.innerWidth * 0.7, window.innerHeight * 0.7);
                        $img.css({
                            'width': maxSize + 'px',
                            'height': maxSize + 'px',
                            'object-fit': 'cover',
                            'display': 'block'
                        });
                    } else {
                        $img.css({
                            'maxWidth': '80vw',
                            'maxHeight': '80vh',
                            'display': 'block'
                        });
                    }
                    
                    $lbImgWrapper.append($img);
                    $lbWrapper.append($lbMarco).append($lbPaspartu).append($lbImgWrapper);
                    $imgContainer.append($lbWrapper);
                }
                
                mostrarImagen(currentSlideIndex);
                
                function cerrarLightbox() {
                    $overlay.remove();
                    lightboxActivo = false;
                    $(document).off('keydown.cuadrosLightbox');
                }
                
                $prevBtn.on('click', function(e) {
                    e.stopPropagation();
                    mostrarImagen(currentSlideIndex - 1);
                });
                
                $nextBtn.on('click', function(e) {
                    e.stopPropagation();
                    mostrarImagen(currentSlideIndex + 1);
                });
                
                $overlay.on('click', function(e) {
                    if (e.target === $overlay[0]) cerrarLightbox();
                });
                
                $closeBtn.on('click', cerrarLightbox);
                
                $(document).on('keydown.cuadrosLightbox', function(e) {
                    if (e.keyCode === 27) cerrarLightbox();
                    else if (e.keyCode === 37) mostrarImagen(currentSlideIndex - 1);
                    else if (e.keyCode === 39) mostrarImagen(currentSlideIndex + 1);
                });
            }
            
            // Interceptar clic en la primera imagen
            document.addEventListener('click', function(e) {
                var $target = $(e.target);
                var $galleryImg = $target.closest('.woocommerce-product-gallery__image');
                if ($galleryImg.length === 0) return;
                
                var imageIndex = $galleryImg.index();
                
                if (imageIndex === 0 && (currentMarcoUrl || currentPaspartuColor)) {
                    var isClickable = $target.closest('a, .woocommerce-product-gallery__trigger, #cuadros-wrapper').length > 0 || $target.is('img');
                    if (isClickable) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        mostrarLightboxCuadros(0);
                        return false;
                    }
                }
            }, true);
            
            // Actualizar estado del lightbox
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
