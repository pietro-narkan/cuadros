<?php
/**
 * Script de prueba para verificar la funcionalidad de subida del plugin Cuadros
 * 
 * Uso: php test-upload.php
 */

// Simular entorno WordPress
define('ABSPATH', dirname(__FILE__) . '/');
define('WP_CONTENT_DIR', ABSPATH . 'wp-content');

// Incluir funciones necesarias de WordPress
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

// Función para probar la subida
function test_upload() {
    echo "=== Prueba de funcionalidad de subida ===\n\n";
    
    // 1. Verificar que wp_handle_upload existe
    if (!function_exists('wp_handle_upload')) {
        echo "ERROR: La función wp_handle_upload no existe.\n";
        return false;
    }
    echo "✓ La función wp_handle_upload está disponible.\n";
    
    // 2. Verificar directorio de uploads
    $upload_dir = wp_upload_dir();
    echo "Directorio de uploads: " . $upload_dir['path'] . "\n";
    echo "URL de uploads: " . $upload_dir['url'] . "\n";
    
    if (!is_writable($upload_dir['path'])) {
        echo "ADVERTENCIA: El directorio de uploads no tiene permisos de escritura.\n";
    } else {
        echo "✓ El directorio de uploads tiene permisos de escritura.\n";
    }
    
    // 3. Verificar imágenes existentes en el directorio raíz
    echo "\n=== Imágenes encontradas en el directorio raíz ===\n";
    $images = glob("*.png");
    foreach ($images as $image) {
        echo "- " . $image . " (" . filesize($image) . " bytes)\n";
    }
    
    // 4. Probar simulación de subida
    echo "\n=== Simulación de subida ===\n";
    
    // Crear un archivo de prueba temporal
    $test_file = tempnam(sys_get_temp_dir(), 'test_');
    file_put_contents($test_file, "Test PNG content");
    
    $_FILES = array(
        'test_file' => array(
            'name' => 'test-image.png',
            'type' => 'image/png',
            'tmp_name' => $test_file,
            'error' => 0,
            'size' => filesize($test_file)
        )
    );
    
    $upload_overrides = array('test_form' => false);
    $movefile = wp_handle_upload($_FILES['test_file'], $upload_overrides);
    
    if ($movefile && !isset($movefile['error'])) {
        echo "✓ Prueba de subida exitosa.\n";
        echo "  Archivo subido a: " . $movefile['file'] . "\n";
        echo "  URL: " . $movefile['url'] . "\n";
        
        // Limpiar
        @unlink($movefile['file']);
    } else {
        echo "✗ Error en prueba de subida: " . $movefile['error'] . "\n";
    }
    
    // Limpiar archivo temporal
    @unlink($test_file);
    
    echo "\n=== Fin de la prueba ===\n";
    return true;
}

// Ejecutar prueba
test_upload();
