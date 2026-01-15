<?php
/**
 * Clase para el panel de administración del plugin Cuadros
 */
class Cuadros_Admin_Settings {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_filter('pre_update_option_cuadros_settings', array($this, 'sanitize_on_admin_form'), 10, 2);
    }
    
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
    }
    
    public function register_settings() {
        register_setting('cuadros_settings_group', 'cuadros_settings');
        
        // Sección de grosores
        add_settings_section(
            'cuadros_dimensions_section',
            __('Grosores', 'cuadros'),
            array($this, 'render_dimensions_section'),
            'cuadros-settings'
        );
        
        // Campos grosor del marco por orientación
        add_settings_field(
            'grosor_marco_vertical',
            __('Grosor del Marco - Vertical (px)', 'cuadros'),
            array($this, 'render_grosor_marco_vertical_field'),
            'cuadros-settings',
            'cuadros_dimensions_section'
        );
        
        add_settings_field(
            'grosor_marco_cuadrado',
            __('Grosor del Marco - Cuadrado 1:1 (px)', 'cuadros'),
            array($this, 'render_grosor_marco_cuadrado_field'),
            'cuadros-settings',
            'cuadros_dimensions_section'
        );
        
        add_settings_field(
            'grosor_marco_horizontal',
            __('Grosor del Marco - Horizontal (px)', 'cuadros'),
            array($this, 'render_grosor_marco_horizontal_field'),
            'cuadros-settings',
            'cuadros_dimensions_section'
        );
        
        // Campos grosor del paspartú por orientación
        add_settings_field(
            'grosor_paspartu_vertical',
            __('Grosor del Paspartú - Vertical (px)', 'cuadros'),
            array($this, 'render_grosor_paspartu_vertical_field'),
            'cuadros-settings',
            'cuadros_dimensions_section'
        );
        
        add_settings_field(
            'grosor_paspartu_cuadrado',
            __('Grosor del Paspartú - Cuadrado 1:1 (px)', 'cuadros'),
            array($this, 'render_grosor_paspartu_cuadrado_field'),
            'cuadros-settings',
            'cuadros_dimensions_section'
        );
        
        add_settings_field(
            'grosor_paspartu_horizontal',
            __('Grosor del Paspartú - Horizontal (px)', 'cuadros'),
            array($this, 'render_grosor_paspartu_horizontal_field'),
            'cuadros-settings',
            'cuadros_dimensions_section'
        );
        
        // Sección de colores de marco
        add_settings_section(
            'cuadros_marco_colors_section',
            __('Colores de Marco', 'cuadros'),
            array($this, 'render_marco_colors_section'),
            'cuadros-settings'
        );
        
        add_settings_field(
            'marco_colors_dynamic',
            __('Colores de Marco Configurados', 'cuadros'),
            array($this, 'render_marco_colors_field'),
            'cuadros-settings',
            'cuadros_marco_colors_section'
        );
        
        // Sección de colores de paspartú
        add_settings_section(
            'cuadros_paspartu_section',
            __('Colores de Paspartú', 'cuadros'),
            array($this, 'render_paspartu_section'),
            'cuadros-settings'
        );
        
        add_settings_field(
            'paspartu_colors_dynamic',
            __('Colores de Paspartú Configurados', 'cuadros'),
            array($this, 'render_paspartu_colors_field'),
            'cuadros-settings',
            'cuadros_paspartu_section'
        );
    }
    
    public function sanitize_on_admin_form($new_value, $old_value) {
        if ($new_value === $old_value) {
            return $new_value;
        }
        
        wp_cache_delete('cuadros_settings', 'options');
        
        if (is_array($new_value)) {
            $sanitized = array();
            
            // Sanitizar grosores por orientación
            $sanitized['grosor_marco_vertical'] = isset($new_value['grosor_marco_vertical']) ? absint($new_value['grosor_marco_vertical']) : 8;
            $sanitized['grosor_marco_cuadrado'] = isset($new_value['grosor_marco_cuadrado']) ? absint($new_value['grosor_marco_cuadrado']) : 10;
            $sanitized['grosor_marco_horizontal'] = isset($new_value['grosor_marco_horizontal']) ? absint($new_value['grosor_marco_horizontal']) : 6;
            
            $sanitized['grosor_paspartu_vertical'] = isset($new_value['grosor_paspartu_vertical']) ? absint($new_value['grosor_paspartu_vertical']) : 30;
            $sanitized['grosor_paspartu_cuadrado'] = isset($new_value['grosor_paspartu_cuadrado']) ? absint($new_value['grosor_paspartu_cuadrado']) : 25;
            $sanitized['grosor_paspartu_horizontal'] = isset($new_value['grosor_paspartu_horizontal']) ? absint($new_value['grosor_paspartu_horizontal']) : 20;
            
            // Sanitizar colores de marco - preservar colores existentes si no se envían nuevos
            $old_marco_colors = isset($old_value['marco_colors']) ? $old_value['marco_colors'] : array();
            if (isset($new_value['marco_colors']) && is_array($new_value['marco_colors'])) {
                $sanitized['marco_colors'] = array();
                foreach ($new_value['marco_colors'] as $key => $color) {
                    // Sanitizar la key para caracteres especiales
                    $clean_key = sanitize_title($key);
                    $sanitized_color = sanitize_hex_color($color);
                    // Si el color es válido, guardarlo; si no, mantener el anterior o usar negro por defecto
                    if ($sanitized_color) {
                        $sanitized['marco_colors'][$clean_key] = $sanitized_color;
                    } elseif (isset($old_marco_colors[$clean_key])) {
                        $sanitized['marco_colors'][$clean_key] = $old_marco_colors[$clean_key];
                    }
                }
            } else {
                $sanitized['marco_colors'] = $old_marco_colors;
            }
            
            // Sanitizar colores de paspartú - preservar colores existentes si no se envían nuevos
            $old_paspartu_colors = isset($old_value['paspartu_colors']) ? $old_value['paspartu_colors'] : array();
            if (isset($new_value['paspartu_colors']) && is_array($new_value['paspartu_colors'])) {
                $sanitized['paspartu_colors'] = array();
                foreach ($new_value['paspartu_colors'] as $key => $color) {
                    // Sanitizar la key para caracteres especiales
                    $clean_key = sanitize_title($key);
                    $sanitized_color = sanitize_hex_color($color);
                    // Si el color es válido, guardarlo; si no, mantener el anterior o usar blanco por defecto
                    if ($sanitized_color) {
                        $sanitized['paspartu_colors'][$clean_key] = $sanitized_color;
                    } elseif (isset($old_paspartu_colors[$clean_key])) {
                        $sanitized['paspartu_colors'][$clean_key] = $old_paspartu_colors[$clean_key];
                    }
                }
            } else {
                $sanitized['paspartu_colors'] = $old_paspartu_colors;
            }
            
            return $sanitized;
        }
        
        return $new_value;
    }
    
    public function render_dimensions_section() {
        echo '<p>' . __('Configura los grosores del marco y paspartú en píxeles para cada orientación de imagen.', 'cuadros') . '</p>';
        echo '<p class="description">' . __('Los grosores se aplicarán automáticamente según la orientación detectada de la imagen del producto.', 'cuadros') . '</p>';
    }
    
    public function render_grosor_marco_vertical_field() {
        $settings = get_option('cuadros_settings', array());
        $value = isset($settings['grosor_marco_vertical']) ? $settings['grosor_marco_vertical'] : 8;
        ?>
        <input type="number" 
               name="cuadros_settings[grosor_marco_vertical]" 
               value="<?php echo esc_attr($value); ?>" 
               min="1" 
               max="50" 
               style="width: 80px;"> px
        <p class="description"><?php _e('Grosor del borde del marco para imágenes verticales. Recomendado: 6-12px', 'cuadros'); ?></p>
        <?php
    }
    
    public function render_grosor_marco_cuadrado_field() {
        $settings = get_option('cuadros_settings', array());
        $value = isset($settings['grosor_marco_cuadrado']) ? $settings['grosor_marco_cuadrado'] : 10;
        ?>
        <input type="number" 
               name="cuadros_settings[grosor_marco_cuadrado]" 
               value="<?php echo esc_attr($value); ?>" 
               min="1" 
               max="50" 
               style="width: 80px;"> px
        <p class="description"><?php _e('Grosor del borde del marco para imágenes cuadradas (1:1). Recomendado: 8-15px', 'cuadros'); ?></p>
        <?php
    }
    
    public function render_grosor_marco_horizontal_field() {
        $settings = get_option('cuadros_settings', array());
        $value = isset($settings['grosor_marco_horizontal']) ? $settings['grosor_marco_horizontal'] : 6;
        ?>
        <input type="number" 
               name="cuadros_settings[grosor_marco_horizontal]" 
               value="<?php echo esc_attr($value); ?>" 
               min="1" 
               max="50" 
               style="width: 80px;"> px
        <p class="description"><?php _e('Grosor del borde del marco para imágenes horizontales. Recomendado: 4-10px', 'cuadros'); ?></p>
        <?php
    }
    
    public function render_grosor_paspartu_vertical_field() {
        $settings = get_option('cuadros_settings', array());
        $value = isset($settings['grosor_paspartu_vertical']) ? $settings['grosor_paspartu_vertical'] : 30;
        ?>
        <input type="number" 
               name="cuadros_settings[grosor_paspartu_vertical]" 
               value="<?php echo esc_attr($value); ?>" 
               min="1" 
               max="100" 
               style="width: 80px;"> px
        <p class="description"><?php _e('Grosor del paspartú para imágenes verticales. Recomendado: 25-40px', 'cuadros'); ?></p>
        <?php
    }
    
    public function render_grosor_paspartu_cuadrado_field() {
        $settings = get_option('cuadros_settings', array());
        $value = isset($settings['grosor_paspartu_cuadrado']) ? $settings['grosor_paspartu_cuadrado'] : 25;
        ?>
        <input type="number" 
               name="cuadros_settings[grosor_paspartu_cuadrado]" 
               value="<?php echo esc_attr($value); ?>" 
               min="1" 
               max="100" 
               style="width: 80px;"> px
        <p class="description"><?php _e('Grosor del paspartú para imágenes cuadradas (1:1). Recomendado: 20-35px', 'cuadros'); ?></p>
        <?php
    }
    
    public function render_grosor_paspartu_horizontal_field() {
        $settings = get_option('cuadros_settings', array());
        $value = isset($settings['grosor_paspartu_horizontal']) ? $settings['grosor_paspartu_horizontal'] : 20;
        ?>
        <input type="number" 
               name="cuadros_settings[grosor_paspartu_horizontal]" 
               value="<?php echo esc_attr($value); ?>" 
               min="1" 
               max="100" 
               style="width: 80px;"> px
        <p class="description"><?php _e('Grosor del paspartú para imágenes horizontales. Recomendado: 15-30px', 'cuadros'); ?></p>
        <?php
    }
    
    public function render_marco_colors_section() {
        echo '<p>' . __('Configura los colores disponibles para los marcos. Los colores se asignan a los términos del atributo "Marco" de WooCommerce.', 'cuadros') . '</p>';
    }
    
    public function render_marco_colors_field() {
        $settings = get_option('cuadros_settings', array());
        $marco_colors = isset($settings['marco_colors']) ? $settings['marco_colors'] : array();
        ?>
        <div id="cuadros-marco-colors" style="margin: 10px 0;">
            <p style="color: #666; font-style: italic;">Cargando colores de marco disponibles...</p>
        </div>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cuadros_get_models',
                        nonce: '<?php echo wp_create_nonce('cuadros_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data.models) {
                            var models = response.data.models;
                            var savedColors = <?php echo json_encode($marco_colors); ?>;
                            var html = '<table style="width: 100%; border-collapse: collapse;">';
                            
                            models.forEach(function(model) {
                                var slug = model.slug || model.name.toLowerCase().replace(/\s+/g, '_');
                                var name = model.name || model;
                                var savedValue = savedColors[slug] || '#000000';
                                
                                html += '<tr style="border-bottom: 1px solid #ddd;">';
                                html += '<td style="padding: 10px; width: 30%;"><strong>' + name + '</strong><br><small style="color:#888;">slug: ' + slug + '</small></td>';
                                html += '<td style="padding: 10px; width: 70%;">';
                                html += '<input type="text" class="cuadros-color-picker" name="cuadros_settings[marco_colors][' + slug + ']" value="' + savedValue + '">';
                                html += '</td>';
                                html += '</tr>';
                            });
                            
                            html += '</table>';
                            $('#cuadros-marco-colors').html(html);
                            $('#cuadros-marco-colors .cuadros-color-picker').wpColorPicker();
                        } else {
                            $('#cuadros-marco-colors').html('<p style="color: #999;">No hay modelos de marco configurados en WooCommerce (atributo pa_marco).</p>');
                        }
                    },
                    error: function() {
                        $('#cuadros-marco-colors').html('<p style="color: #d32f2f;">Error al cargar los modelos de marco.</p>');
                    }
                });
            });
        </script>
        <?php
    }
    
    public function render_paspartu_section() {
        echo '<p>' . __('Configura los colores disponibles para los paspartús.', 'cuadros') . '</p>';
    }
    
    public function render_paspartu_colors_field() {
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
                                var slug = color.slug || color.name.toLowerCase().replace(/\s+/g, '_');
                                var name = color.name || color;
                                var savedValue = savedColors[slug] || '#ffffff';
                                
                                html += '<tr style="border-bottom: 1px solid #ddd;">';
                                html += '<td style="padding: 10px; width: 30%;"><strong>' + name + '</strong><br><small style="color:#888;">slug: ' + slug + '</small></td>';
                                html += '<td style="padding: 10px; width: 70%;">';
                                html += '<input type="text" class="cuadros-color-picker" name="cuadros_settings[paspartu_colors][' + slug + ']" value="' + savedValue + '">';
                                html += '</td>';
                                html += '</tr>';
                            });
                            
                            html += '</table>';
                            $('#cuadros-paspartu-colors').html(html);
                            $('#cuadros-paspartu-colors .cuadros-color-picker').wpColorPicker();
                        } else {
                            $('#cuadros-paspartu-colors').html('<p style="color: #999;">No hay colores de paspartú configurados en WooCommerce.</p>');
                        }
                    }
                });
            });
        </script>
        <?php
    }
    
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
        </div>
        <?php
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_cuadros-settings') {
            return;
        }
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        wp_enqueue_style(
            'cuadros-admin-style',
            CUADROS_ASSETS_URL . 'css/admin.css',
            array(),
            CUADROS_VERSION
        );
        
        wp_enqueue_script(
            'cuadros-admin-script',
            CUADROS_ASSETS_URL . 'js/admin.js',
            array('jquery', 'wp-color-picker'),
            CUADROS_VERSION,
            true
        );
        
        wp_localize_script('cuadros-admin-script', 'cuadros_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cuadros_admin_nonce')
        ));
    }
}
