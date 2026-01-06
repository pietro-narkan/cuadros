<?php
/**
 * Clase para la gestión de assets del plugin Cuadros
 */
class Cuadros_Assets_Manager {
    
    public function __construct() {
        // Acciones AJAX para usuarios autenticados (administradores)
        add_action('wp_ajax_cuadros_upload_marco', array($this, 'handle_marco_upload'));
        add_action('wp_ajax_cuadros_get_marcos', array($this, 'get_marcos_list'));
        add_action('wp_ajax_cuadros_delete_marco', array($this, 'delete_marco'));
        
        // También registrar para usuarios no autenticados (pero con verificación de permisos en cada función)
        add_action('wp_ajax_nopriv_cuadros_upload_marco', array($this, 'handle_marco_upload'));
        add_action('wp_ajax_nopriv_cuadros_get_marcos', array($this, 'get_marcos_list'));
        add_action('wp_ajax_nopriv_cuadros_delete_marco', array($this, 'delete_marco'));
    }
    
    /**
     * Manejar la subida de imágenes de marcos
     */
    public function handle_marco_upload() {
        // Verificar nonce
        if (empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cuadros_admin_nonce')) {
            wp_send_json_error(array('message' => 'Error de seguridad: Nonce verification failed'));
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes para realizar esta acción.'));
        }
        
        // Verificar que se hayan enviado los datos necesarios
        if (empty($_POST['color']) || empty($_POST['orientation'])) {
            wp_send_json_error(array('message' => __('Faltan datos requeridos (color u orientación).', 'cuadros')));
        }
        
        // Verificar que se haya subido un archivo
        if (!isset($_FILES['marco_image']) || $_FILES['marco_image']['error'] !== UPLOAD_ERR_OK) {
            $error_message = $this->get_upload_error_message($_FILES['marco_image']['error'] ?? UPLOAD_ERR_NO_FILE);
            wp_send_json_error(array('message' => __('Error en la subida del archivo:', 'cuadros') . ' ' . $error_message));
        }
        
        $color = sanitize_text_field($_POST['color']);
        $orientation = sanitize_text_field($_POST['orientation']);
        
        // Verificar tipo de archivo (solo PNG)
        $file_type = wp_check_filetype($_FILES['marco_image']['name']);
        if ($file_type['ext'] !== 'png') {
            wp_send_json_error(array('message' => __('Solo se permiten archivos PNG.', 'cuadros')));
        }
        
        // Manejar la subida del archivo
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $uploadedfile = $_FILES['marco_image'];
        $upload_overrides = array(
            'test_form' => false,
            'test_type' => true,
            'mimes' => array('png' => 'image/png')
        );
        
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            // Guardar la información en las opciones del plugin
            $settings = get_option('cuadros_settings', array());

            // Asegurar que $settings sea un array
            if (!is_array($settings)) {
                $settings = array();
            }

            if (!isset($settings['marco_images']) || !is_array($settings['marco_images'])) {
                $settings['marco_images'] = array();
            }
            
            // Nota: ya no eliminamos la imagen anterior para la misma combinación
            // de color/orientación; permitimos almacenar múltiples imágenes.
            
            // Agregar nueva imagen
            $settings['marco_images'][] = array(
                'color' => $color,
                'orientation' => $orientation,
                'url' => $movefile['url'],
                'path' => $movefile['file'],
                'filename' => basename($movefile['file']),
                'uploaded' => current_time('mysql')
            );
            
            // Guardar la opción usando update_option sin pasar por filtros de serialización
            error_log('[cuadros] Attempting to save settings: ' . print_r($settings, true));
            
            // Limpiar caché antes de guardar
            wp_cache_delete('cuadros_settings', 'options');
            
            // Usar update_option que es más confiable que add_option
            $result = update_option('cuadros_settings', $settings);
            error_log('[cuadros] update_option returned: ' . var_export($result, true));
            
            // Limpiar caché después de guardar
            wp_cache_delete('cuadros_settings', 'options');
            
            // Verificar de forma cruda desde la BD
            global $wpdb;
            $raw_value = $wpdb->get_var($wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
                'cuadros_settings'
            ));
            error_log('[cuadros] Raw DB value: ' . var_export($raw_value, true));
            
            $verified = maybe_unserialize($raw_value);
            error_log('[cuadros] After save, DB value unserialized: ' . print_r($verified, true));

            if (empty($verified) || !isset($verified['marco_images'])) {
                wp_send_json_error(array(
                    'message' => __('Error al guardar la configuración del plugin. Revisa los logs.', 'cuadros'),
                    'debug' => 'option_save_failed'
                ));
            }
            
            wp_send_json_success(array(
                'message' => __('Imagen subida correctamente.', 'cuadros'),
                'url' => $movefile['url'],
                'color' => $color,
                'orientation' => $orientation,
                'marcos' => $settings
            ));
        } else {
            $debug_info = array(
                'movefile' => $movefile,
                'files_marco_image' => isset($_FILES['marco_image']) ? $_FILES['marco_image'] : null,
                'upload_dir' => wp_upload_dir(),
                'php_last_error' => error_get_last()
            );

            // Registrar en el log de PHP/WordPress para depuración
            error_log('[cuadros] Upload failed: ' . print_r($debug_info, true));

            $server_msg = is_array($movefile) && isset($movefile['error']) ? $movefile['error'] : __('Error desconocido en wp_handle_upload.', 'cuadros');

            wp_send_json_error(array(
                'message' => __('Error al subir la imagen:', 'cuadros') . ' ' . $server_msg,
                'debug' => $server_msg
            ));
        }
    }
    
    /**
     * Obtener lista de imágenes de marcos
     */
    public function get_marcos_list() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cuadros_admin_nonce')) {
            wp_die('Nonce verification failed');
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $settings = get_option('cuadros_settings', array());
        // Si get_option devuelve vacío inesperadamente, leer directamente desde la BD como fallback
        if (empty($settings)) {
            global $wpdb;
            $option_name = 'cuadros_settings';
            $row = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $option_name));
            if ($row !== null) {
                $maybe = maybe_unserialize($row);
                if (is_array($maybe) && !empty($maybe)) {
                    $settings = $maybe;
                    error_log('[cuadros] Fallback read from DB returned settings: ' . print_r($settings, true));
                }
            }
        }

        $marcos = isset($settings['marco_images']) ? $settings['marco_images'] : array();
        
        wp_send_json_success(array('marcos' => $marcos));
    }
    
    /**
     * Eliminar una imagen de marco
     */
    public function delete_marco() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cuadros_admin_nonce')) {
            wp_die('Nonce verification failed');
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        if (empty($_POST['filename'])) {
            wp_send_json_error(array('message' => __('Nombre de archivo no proporcionado.', 'cuadros')));
        }
        
        $filename = sanitize_text_field($_POST['filename']);
        $settings = get_option('cuadros_settings', array());
        
        if (!isset($settings['marco_images'])) {
            wp_send_json_error(array('message' => __('No hay imágenes para eliminar.', 'cuadros')));
        }
        
        $found = false;
        foreach ($settings['marco_images'] as $key => $marco) {
            if ($marco['filename'] === $filename) {
                // Eliminar archivo físico
                if (isset($marco['path']) && file_exists($marco['path'])) {
                    @unlink($marco['path']);
                }
                
                // Eliminar de la lista
                unset($settings['marco_images'][$key]);
                $found = true;
                break;
            }
        }
        
        if ($found) {
            // Reindexar array
            $settings['marco_images'] = array_values($settings['marco_images']);
            update_option('cuadros_settings', $settings);
            
            wp_send_json_success(array('message' => __('Imagen eliminada correctamente.', 'cuadros')));
        } else {
            wp_send_json_error(array('message' => __('Imagen no encontrada.', 'cuadros')));
        }
    }
    
    /**
     * Obtener URL de imagen de marco por defecto
     */
    public static function get_default_marco_url($color, $orientation) {
        $default_path = CUADROS_PLUGIN_DIR . 'assets/images/marcos/' . $color . '-' . $orientation . '.png';
        $default_url = CUADROS_ASSETS_URL . 'images/marcos/' . $color . '-' . $orientation . '.png';
        
        if (file_exists($default_path)) {
            return $default_url;
        }
        
        // Si no existe la imagen por defecto, devolver placeholder
        return CUADROS_ASSETS_URL . 'images/placeholder.png';
    }
    
    /**
     * Copiar imágenes por defecto al directorio de uploads
     */
    public static function copy_default_images() {
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/cuadros/default/';
        
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }
        
        $default_images = array(
            'oro-vertical.png',
            'oro-horizontal.png',
            'negro-vertical.png',
            'negro-horizontal.png',
            'blanco-vertical.png',
            'blanco-horizontal.png'
        );
        
        $copied = array();
        
        foreach ($default_images as $image) {
            $source = CUADROS_PLUGIN_DIR . 'assets/images/marcos/' . $image;
            $target = $target_dir . $image;
            
            if (file_exists($source) && !file_exists($target)) {
                copy($source, $target);
                $copied[] = $image;
            }
        }
        
        return $copied;
    }
    
    /**
     * Obtener mensaje de error legible para errores de subida
     */
    private function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return __('El archivo excede el tamaño máximo permitido por el servidor.', 'cuadros');
            case UPLOAD_ERR_FORM_SIZE:
                return __('El archivo excede el tamaño máximo permitido por el formulario.', 'cuadros');
            case UPLOAD_ERR_PARTIAL:
                return __('El archivo fue solo parcialmente subido.', 'cuadros');
            case UPLOAD_ERR_NO_FILE:
                return __('No se seleccionó ningún archivo para subir.', 'cuadros');
            case UPLOAD_ERR_NO_TMP_DIR:
                return __('Falta el directorio temporal.', 'cuadros');
            case UPLOAD_ERR_CANT_WRITE:
                return __('No se pudo escribir el archivo en el disco.', 'cuadros');
            case UPLOAD_ERR_EXTENSION:
                return __('Una extensión de PHP detuvo la subida del archivo.', 'cuadros');
            default:
                return sprintf(__('Error desconocido (código: %d).', 'cuadros'), $error_code);
        }
    }
}
