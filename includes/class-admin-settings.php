<?php
/**
 * Clase para el panel de administración del plugin Cuadros
 */
class Cuadros_Admin_Settings {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Hook para sanitizar SOLO desde el formulario de admin
        add_filter('pre_update_option_cuadros_settings', array($this, 'sanitize_on_admin_form'), 10, 2);
    }
    
    /**
     * Agregar menú de administración
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Configuración de Cuadros', 'cuadros'),
            __('Cuadros', 'cuadros'),
            'manage_options',
            'cuadros-settings',
            array($this, 'render_settings_page'),
            'dashicons-format-gallery',
            56
        );
        
        add_submenu_page(
            'cuadros-settings',
            __('Configuración General', 'cuadros'),
            __('Configuración', 'cuadros'),
            'manage_options',
            'cuadros-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        // Registrar sin sanitizer para permitir que AJAX maneje marco_images directamente
        // El sanitizer solo se usa en el formulario de admin
        register_setting('cuadros_settings_group', 'cuadros_settings');
        
        // Sección de colores de paspartú
        add_settings_section(
            'cuadros_paspartu_section',
            __('Colores de Paspartú', 'cuadros'),
            array($this, 'render_paspartu_section'),
            'cuadros-settings'
        );
        
        // Sección de imágenes de marcos
        add_settings_section(
            'cuadros_marco_section',
            __('Imágenes de Marcos', 'cuadros'),
            array($this, 'render_marco_section'),
            'cuadros-settings'
        );
        
        // Sección de dimensiones
        add_settings_section(
            'cuadros_dimensions_section',
            __('Dimensiones de Visualización', 'cuadros'),
            array($this, 'render_dimensions_section'),
            'cuadros-settings'
        );
        
        // Campos para colores de paspartú - se cargan dinámicamente
        add_settings_field(
            'paspartu_colors_dynamic',
            __('Colores de Paspartú Configurados', 'cuadros'),
            array($this, 'render_paspartu_colors_field'),
            'cuadros-settings',
            'cuadros_paspartu_section',
            array()
        );
        
        // Campos para dimensiones
        add_settings_field(
            'vertical_dimensions',
            __('Dimensiones Verticales', 'cuadros'),
            array($this, 'render_dimensions_field'),
            'cuadros-settings',
            'cuadros_dimensions_section',
            array(
                'orientation' => 'vertical',
                'default_width' => 70,
                'default_height' => 90
            )
        );
        
        add_settings_field(
            'horizontal_dimensions',
            __('Dimensiones Horizontales', 'cuadros'),
            array($this, 'render_dimensions_field'),
            'cuadros-settings',
            'cuadros_dimensions_section',
            array(
                'orientation' => 'horizontal',
                'default_width' => 90,
                'default_height' => 70
            )
        );
    }
    
    /**
     * Sanitizar solo cuando viene del formulario de admin
     * Si viene de otro lado (AJAX, API), pasar el valor sin cambios
     */
    public function sanitize_on_admin_form($new_value, $old_value) {
        // Si el nuevo valor tiene la estructura de datos de AJAX (solo marco_images), pasarlo sin cambios
        if (is_array($new_value) && isset($new_value['marco_images']) && count($new_value) === 1) {
            error_log('[cuadros] sanitize_on_admin_form: detectado formato AJAX, pasando sin cambios');
            return $new_value;
        }
        
        // Si viene del formulario de admin (tiene colores o dimensiones), sanitizar
        if (is_array($new_value) && (isset($new_value['paspartu_colors']) || isset($new_value['dimensions']))) {
            error_log('[cuadros] sanitize_on_admin_form: detectado formulario admin, sanitizando');
            return $this->sanitize_settings($new_value);
        }
        
        // En cualquier otro caso, preservar marco_images del registro anterior
        $old_settings = get_option('cuadros_settings', array());
        if (isset($old_settings['marco_images']) && is_array($new_value)) {
            $new_value['marco_images'] = $old_settings['marco_images'];
        }
        
        return $new_value;
    }
    
    /**
     * Sanitizar configuraciones (usado solo desde formulario de admin)
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Sanitizar colores de paspartú - ahora viene del campo dinámico
        if (isset($input['paspartu_colors']) && is_array($input['paspartu_colors'])) {
            $sanitized['paspartu_colors'] = array();
            foreach ($input['paspartu_colors'] as $key => $color) {
                $sanitized['paspartu_colors'][$key] = sanitize_hex_color($color);
            }
        }
        
        // Sanitizar dimensiones
        if (isset($input['vertical_width']) && isset($input['vertical_height'])) {
            $sanitized['dimensions']['vertical'] = array(
                'width' => absint($input['vertical_width']),
                'height' => absint($input['vertical_height'])
            );
        }
        
        if (isset($input['horizontal_width']) && isset($input['horizontal_height'])) {
            $sanitized['dimensions']['horizontal'] = array(
                'width' => absint($input['horizontal_width']),
                'height' => absint($input['horizontal_height'])
            );
        }
        
        // Mantener imágenes existentes
        $old_settings = get_option('cuadros_settings', array());
        if (isset($old_settings['marco_images'])) {
            $sanitized['marco_images'] = $old_settings['marco_images'];
        }
        
        return $sanitized;
    }
    
    /**
     * Renderizar sección de colores de paspartú
     */
    public function render_paspartu_section() {
        echo '<p>' . __('Configura los colores disponibles para los paspartús. Los usuarios podrán seleccionar entre estos colores en la página del producto.', 'cuadros') . '</p>';
    }
    
    /**
     * Renderizar sección de imágenes de marcos
     */
    public function render_marco_section() {
        echo '<p>' . __('Gestiona las imágenes de marcos. Para cada color de marco (oro, negro, blanco), sube una imagen para orientación vertical y otra para horizontal.', 'cuadros') . '</p>';
        echo '<div id="cuadros-marco-uploader">';
        echo '<button type="button" class="button" id="cuadros-add-marco">' . __('Agregar Nuevo Marco', 'cuadros') . '</button>';
        echo '</div>';
    }
    
    /**
     * Renderizar sección de dimensiones
     */
    public function render_dimensions_section() {
        echo '<p>' . __('Configura las dimensiones de visualización para los marcos y paspartús. Los valores son porcentajes relativos al contenedor de la imagen.', 'cuadros') . '</p>';
    }
    
    /**
     * Renderizar campo dinámico de colores de paspartú
     */
    public function render_paspartu_colors_field($args) {
        $settings = get_option('cuadros_settings', array());
        $paspartu_colors = isset($settings['paspartu_colors']) ? $settings['paspartu_colors'] : array();
        ?>
        <div id="cuadros-paspartu-colors" style="margin: 10px 0;">
            <p style="color: #666; font-style: italic;">Cargando colores de paspartú disponibles...</p>
        </div>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cuadros_get_paspartu_colors',
                        nonce: '<?php echo wp_create_nonce('cuadros_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data.colors) {
                            var colors = response.data.colors;
                            var savedColors = <?php echo json_encode($paspartu_colors); ?>;
                            var html = '<table style="width: 100%; border-collapse: collapse;">';
                            
                            colors.forEach(function(color) {
                                var colorKey = color.toLowerCase().replace(/\s+/g, '_');
                                var savedValue = savedColors[colorKey] || '#ffffff';
                                
                                html += '<tr style="border-bottom: 1px solid #ddd; padding: 5px 0;">';
                                html += '<td style="padding: 10px; width: 30%;"><strong>' + color + '</strong></td>';
                                html += '<td style="padding: 10px; width: 70%;">';
                                html += '<input type="text" class="cuadros-color-picker" name="cuadros_settings[paspartu_colors][' + colorKey + ']" value="' + savedValue + '" data-color="' + savedValue + '" style="width: 100px; padding: 5px;">';
                                html += '</td>';
                                html += '</tr>';
                            });
                            
                            html += '</table>';
                            $('#cuadros-paspartu-colors').html(html);
                            
                            // Inicializar color pickers
                            $('.cuadros-color-picker').wpColorPicker();
                        } else {
                            $('#cuadros-paspartu-colors').html('<p style="color: #999;">No hay colores de paspartú configurados en WooCommerce.</p>');
                        }
                    },
                    error: function() {
                        $('#cuadros-paspartu-colors').html('<p style="color: #d32f2f;">Error al cargar los colores de paspartú.</p>');
                    }
                });
            });
        </script>
        <?php
    }
    
    /**
     * Renderizar campo de color
     */
    public function render_color_field($args) {
        $settings = get_option('cuadros_settings', array());
        $color_key = $args['color_key'];
        $default = $args['default'];
        
        $value = isset($settings['paspartu_colors'][$color_key]) ? $settings['paspartu_colors'][$color_key] : $default;
        
        echo '<input type="text" 
                     id="' . esc_attr($args['label_for']) . '" 
                     name="cuadros_settings[' . esc_attr($args['label_for']) . ']" 
                     value="' . esc_attr($value) . '" 
                     class="cuadros-color-picker" 
                     data-default-color="' . esc_attr($default) . '" />';
        echo '<p class="description">' . sprintf(__('Color hexadecimal para el paspartú %s.', 'cuadros'), $color_key) . '</p>';
    }
    
    /**
     * Renderizar campo de dimensiones
     */
    public function render_dimensions_field($args) {
        $settings = get_option('cuadros_settings', array());
        $orientation = $args['orientation'];
        
        $width = isset($settings['dimensions'][$orientation]['width']) ? $settings['dimensions'][$orientation]['width'] : $args['default_width'];
        $height = isset($settings['dimensions'][$orientation]['height']) ? $settings['dimensions'][$orientation]['height'] : $args['default_height'];
        
        echo '<div class="cuadros-dimensions-field">';
        echo '<label>' . __('Ancho (%):', 'cuadros') . ' ';
        echo '<input type="number" 
                     min="1" 
                     max="100" 
                     name="cuadros_settings[' . $orientation . '_width]" 
                     value="' . esc_attr($width) . '" /></label>';
        
        echo '<label style="margin-left: 20px;">' . __('Alto (%):', 'cuadros') . ' ';
        echo '<input type="number" 
                     min="1" 
                     max="100" 
                     name="cuadros_settings[' . $orientation . '_height]" 
                     value="' . esc_attr($height) . '" /></label>';
        echo '</div>';
    }
    
    /**
     * Renderizar página de configuración
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('cuadros_settings_group');
                do_settings_sections('cuadros-settings');
                submit_button(__('Guardar Cambios', 'cuadros'));
                ?>
            </form>
            
            <div id="cuadros-marco-management" style="margin-top: 30px;">
                <h2><?php _e('Gestión de Imágenes de Marcos', 'cuadros'); ?></h2>
                <div id="cuadros-marco-list">
                    <!-- Las imágenes de marcos se cargarán aquí via AJAX -->
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue scripts y estilos de administración
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_cuadros-settings') {
            return;
        }
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        wp_enqueue_media();
        
        wp_enqueue_style(
            'cuadros-admin-style',
            CUADROS_ASSETS_URL . 'css/admin.css',
            array(),
            CUADROS_VERSION
        );
        
        wp_enqueue_script(
            'cuadros-admin-script',
            CUADROS_ASSETS_URL . 'js/admin.js',
            array('jquery', 'wp-color-picker', 'media-upload'),
            CUADROS_VERSION,
            true
        );
        
        wp_localize_script('cuadros-admin-script', 'cuadros_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cuadros_admin_nonce'),
            'upload_title' => __('Seleccionar Imagen de Marco', 'cuadros'),
            'upload_button' => __('Usar esta imagen', 'cuadros')
        ));
    }
}
