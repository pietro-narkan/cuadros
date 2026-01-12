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
            
            // Asegurar que los contenedores padres permitan overflow
            $wrapper.parents().each(function() {
                var $parent = $(this);
                if ($parent.css('overflow') === 'hidden') {
                    $parent.css('overflow', 'visible');
                }
            });
            
            // También el contenedor de la galería
            $('.woocommerce-product-gallery, .woocommerce-product-gallery__wrapper, .woocommerce-product-gallery__image').css('overflow', 'visible');
            
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
                // La orientación se determina SOLO por las dimensiones de la imagen
                var imgW = $productImage[0].naturalWidth || $productImage.width();
                var imgH = $productImage[0].naturalHeight || $productImage.height();
                
                if (imgW <= 0 || imgH <= 0) return 'vertical';
                
                var ratio = imgW / imgH;
                
                if (ratio > 0.95 && ratio < 1.05) {
                    console.log('[cuadros] Orientación por imagen: 1:1 (cuadrada)');
                    return '1:1';
                } else if (ratio < 1) {
                    console.log('[cuadros] Orientación por imagen: vertical');
                    return 'vertical';
                } else {
                    console.log('[cuadros] Orientación por imagen: horizontal');
                    return 'horizontal';
                }
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
                
                // Calcular dimensiones del wrapper según orientación
                // Usamos el ancho original como base y calculamos el alto según la proporción
                var wrapperW = originalWidth;
                var wrapperH;
                
                if (orientacion === '1:1') {
                    // Cuadrado: usar el ancho como base
                    wrapperH = wrapperW;
                } else if (orientacion === 'horizontal') {
                    // Horizontal: ratio 4:3 (ancho > alto)
                    wrapperH = wrapperW * 0.75;
                } else {
                    // Vertical: ratio 3:4 (alto > ancho)
                    wrapperH = wrapperW * 1.33;
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
            var galleryImages = [];
            var currentSlideIndex = 0;
            
            function obtenerImagenesGaleria() {
                galleryImages = [];
                $('.woocommerce-product-gallery__image').each(function(index) {
                    var $img = $(this).find('img');
                    var $link = $(this).find('a');
                    galleryImages.push({
                        index: index,
                        src: $link.attr('href') || $img.attr('data-large_image') || $img.attr('src'),
                        alt: $img.attr('alt') || ''
                    });
                });
                console.log('[cuadros] Galería:', galleryImages.length, 'imágenes');
            }
            
            function actualizarLightboxState() {
                var marcoVal = $('#pa_marco').val();
                var paspartuVal = $('#pa_paspartu').val();
                currentOrientacion = detectarOrientacion();
                currentMarcoUrl = buscarMarco(marcoVal, currentOrientacion);
                currentPaspartuColor = buscarPaspartu(paspartuVal);
                obtenerImagenesGaleria();
            }
            
            function mostrarLightbox(startIndex) {
                currentSlideIndex = startIndex || 0;
                
                var $overlay = $('<div id="cuadros-lightbox"></div>').css({
                    position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
                    background: 'rgba(0,0,0,0.9)', zIndex: 999999,
                    display: 'flex', alignItems: 'center', justifyContent: 'center'
                });
                
                var $container = $('<div id="cuadros-lb-container"></div>').css({
                    position: 'relative'
                });
                
                // Botón cerrar
                var $closeBtn = $('<div class="cuadros-lb-close">&times;</div>').css({
                    position: 'absolute', top: '20px', right: '30px',
                    color: '#fff', fontSize: '40px', cursor: 'pointer', zIndex: 10
                });
                
                // Flechas de navegación
                var $prevBtn = $('<div class="cuadros-lb-prev">&#10094;</div>').css({
                    position: 'absolute', left: '20px', top: '50%',
                    transform: 'translateY(-50%)',
                    color: '#fff', fontSize: '50px', cursor: 'pointer', zIndex: 10,
                    padding: '10px', userSelect: 'none',
                    display: galleryImages.length > 1 ? 'block' : 'none'
                });
                
                var $nextBtn = $('<div class="cuadros-lb-next">&#10095;</div>').css({
                    position: 'absolute', right: '20px', top: '50%',
                    transform: 'translateY(-50%)',
                    color: '#fff', fontSize: '50px', cursor: 'pointer', zIndex: 10,
                    padding: '10px', userSelect: 'none',
                    display: galleryImages.length > 1 ? 'block' : 'none'
                });
                
                // Contador
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
                    
                    var maxSize = Math.min(window.innerWidth * 0.8, window.innerHeight * 0.8);
                    var lbW, lbH;
                    
                    // Solo aplicar marco/paspartú en la primera imagen
                    if (esPrimeraImagen && (currentMarcoUrl || currentPaspartuColor)) {
                        if (currentOrientacion === '1:1') {
                            lbW = lbH = maxSize;
                        } else if (currentOrientacion === 'horizontal') {
                            lbW = maxSize;
                            lbH = maxSize * 0.75;
                        } else {
                            lbH = maxSize;
                            lbW = maxSize * 0.75;
                        }
                        
                        $container.css({ width: lbW + 'px', height: lbH + 'px' });
                        
                        // Fondo: paspartú o blanco
                        var fondoColor = currentPaspartuColor || '#ffffff';
                        $('<div></div>').css({
                            position: 'absolute', top: 0, left: 0, right: 0, bottom: 0,
                            background: fondoColor, zIndex: 1
                        }).appendTo($container);
                        
                        // Imagen centrada
                        var imgSize = currentPaspartuColor ? IMAGEN_PORCENTAJE : (IMAGEN_PORCENTAJE + 5);
                        var imgW = lbW * (imgSize / 100);
                        var imgH = lbH * (imgSize / 100);
                        
                        $('<div></div>').css({
                            position: 'absolute',
                            top: (lbH - imgH) / 2 + 'px',
                            left: (lbW - imgW) / 2 + 'px',
                            width: imgW + 'px', height: imgH + 'px',
                            zIndex: 2, overflow: 'hidden',
                            display: 'flex', alignItems: 'center', justifyContent: 'center'
                        }).append(
                            $('<img>').attr('src', imgSrc).css({
                                maxWidth: '100%', maxHeight: '100%',
                                width: 'auto', height: 'auto',
                                objectFit: 'contain'
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
                    } else {
                        // Otras imágenes: mostrar sin marco/paspartú
                        $container.css({ width: 'auto', height: 'auto' });
                        $('<img>').attr('src', imgSrc).css({
                            maxWidth: '80vw', maxHeight: '80vh',
                            display: 'block'
                        }).appendTo($container);
                    }
                }
                
                mostrarImagen(currentSlideIndex);
                
                // Eventos
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
                    if (e.keyCode === 27) { // ESC
                        $overlay.remove();
                        $(document).off('keydown.cuadrosLb');
                    } else if (e.keyCode === 37) { // Flecha izquierda
                        mostrarImagen(currentSlideIndex - 1);
                    } else if (e.keyCode === 39) { // Flecha derecha
                        mostrarImagen(currentSlideIndex + 1);
                    }
                });
            }
            
            // Click en imagen
            $wrapper.on('click', function(e) {
                if (currentMarcoUrl || currentPaspartuColor) {
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
