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
                'z-index': '1'
            });
            
            $paspartuLayer.css({
                'position': 'absolute',
                'pointer-events': 'none',
                'opacity': '0',
                'transition': 'opacity 0.3s ease',
                'z-index': '2'
            });
            
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
                // Primero intentar detectar del selector de tamaño
                var texto = "";
                $('form.variations_form select').each(function() {
                    var opt = $(this).find('option:selected').text();
                    if (opt && opt.match(/\d+\s*[xX]\s*\d+/)) {
                        texto = opt;
                        return false;
                    }
                });
                
                if (texto) {
                    var match = texto.match(/(\d+)\s*[xX]\s*(\d+)/);
                    if (match) {
                        var ancho = parseInt(match[1]);
                        var alto = parseInt(match[2]);
                        if (Math.abs(ancho - alto) < 5) return '1:1';
                        return (ancho < alto) ? 'vertical' : 'horizontal';
                    }
                }
                
                // Fallback: detectar por dimensiones de imagen
                var imgW = $productImage.width();
                var imgH = $productImage.height();
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
                
                // Calcular padding según lo que esté activo
                var paddingMarco = hayMarco ? GROSOR_MARCO : 0;
                var paddingPaspartu = hayPaspartu ? GROSOR_PASPARTU : 0;
                var paddingTotal = paddingMarco + paddingPaspartu;
                
                // Aplicar padding al wrapper de la imagen (esto crea el espacio para marco y paspartú)
                $imagenWrapper.css({
                    'padding': paddingTotal + 'px'
                });
                
                // Configurar capa del marco (cubre todo el wrapper)
                if (hayMarco) {
                    $marcoLayer.css({
                        'background-image': 'url(' + marcoUrl + ')',
                        'opacity': '1'
                    });
                } else {
                    $marcoLayer.css({
                        'background-image': 'none',
                        'opacity': '0'
                    });
                }
                
                // Configurar capa del paspartú (entre el marco y la imagen)
                if (hayPaspartu) {
                    $paspartuLayer.css({
                        'top': paddingMarco + 'px',
                        'left': paddingMarco + 'px',
                        'right': paddingMarco + 'px',
                        'bottom': paddingMarco + 'px',
                        'background-color': paspartuColor,
                        'opacity': '1'
                    });
                } else {
                    $paspartuLayer.css({
                        'opacity': '0'
                    });
                }
            }
            
            // ========== EVENT LISTENERS ==========
            $('form.variations_form').on('change', 'select', function() {
                setTimeout(actualizarVisualizacion, 100);
            });
            
            $(document).on('woocommerce_variation_has_changed', function() {
                setTimeout(actualizarVisualizacion, 100);
            });
            
            $('.reset_variations').on('click', function() {
                $imagenWrapper.css('padding', '0');
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
                    
                    var $img = $('<img>').attr('src', imgData.large).attr('alt', imgData.alt).css({
                        maxWidth: '80vw',
                        maxHeight: '80vh',
                        display: 'block'
                    });
                    
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
