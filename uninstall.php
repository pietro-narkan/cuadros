<?php
/**
 * Fichero de desinstalación del plugin Cuadros
 *
 * Este fichero se ejecuta cuando el plugin es eliminado desde el administrador de WordPress.
 * Elimina todas las opciones y datos creados por el plugin.
 */

// Si no se llama desde WordPress, salir
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Eliminar opciones de la base de datos
delete_option('cuadros_settings');

// Eliminar opciones en modo multisite
if (is_multisite()) {
    $sites = get_sites();
    foreach ($sites as $site) {
        switch_to_blog($site->blog_id);
        delete_option('cuadros_settings');
        restore_current_blog();
    }
    
    // Eliminar opción de red
    delete_site_option('cuadros_settings');
}

// Eliminar directorio de imágenes subidas
$upload_dir = wp_upload_dir();
$cuadros_dir = $upload_dir['basedir'] . '/cuadros/';

if (file_exists($cuadros_dir)) {
    // Función recursiva para eliminar directorio
    function cuadros_rmdir_recursive($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                cuadros_rmdir_recursive($path);
            } else {
                @unlink($path);
            }
        }
        
        @rmdir($dir);
    }
    
    cuadros_rmdir_recursive($cuadros_dir);
}

// Limpiar cache de transients relacionados
$transients = array(
    'cuadros_default_images_copied',
    'cuadros_activation_time',
    'cuadros_version'
);

foreach ($transients as $transient) {
    delete_transient($transient);
}

// Nota: No eliminamos las imágenes por defecto del plugin porque están en la carpeta del plugin
// que será eliminada por WordPress al desinstalar.
