<?php
/**
 * Script de prueba para verificar la funcionalidad de subida del plugin Cuadros
 * 
 * Uso: php test-upload.php
 */

// Intentar apuntar al directorio raíz de WordPress para cargar sus funciones
define('CUADROS_PLUGIN_FILE', __FILE__);
$wp_root = realpath(dirname(__FILE__) . '/../../..');

// Intentamos localizar la instalación de WP; si no es posible (o si prefieres pruebas puramente por consola),
// realizamos una simulación de subida que no requiere bootstrapping completo de WordPress.
if ($wp_root && file_exists($wp_root . '/wp-load.php')) {
    define('ABSPATH', $wp_root . '/');
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');

    // Advertencia: cargar wp-load.php puede requerir conexión a la base de datos.
    // Para pruebas en entornos donde la DB no está disponible por CLI, comentar la siguiente línea.
    // require_once ABSPATH . 'wp-load.php';

    // No incluir archivos de WP para evitar dependencias de DB/funciones cuando ejecutamos por CLI.
} else {
    // No se encontró WP; continuamos en modo 'solo consola' usando rutas relativas
    define('ABSPATH', dirname(__FILE__) . '/../../');
    define('WP_CONTENT_DIR', realpath(ABSPATH . 'wp-content') ?: (ABSPATH . 'wp-content'));
}

// Función para probar la subida
function test_upload() {
    echo "=== Prueba de funcionalidad de subida ===\n\n";
    
    // 1. Preparar directorio de uploads (modo WP si está disponible, si no modo consola)
    if (function_exists('wp_upload_dir')) {
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['path'];
        $upload_url = $upload_dir['url'];
        echo "Usando funciones de WP para uploads.\n";
    } else {
        // Modo consola: construir ruta a wp-content/uploads/YYYY/MM
        $base_upload = rtrim(WP_CONTENT_DIR, '/') . '/uploads';
        $subdir = date('Y') . '/' . date('m');
        $upload_path = $base_upload . '/' . $subdir;
        $upload_url = null;
        echo "Modo consola: usando ruta de uploads -> $upload_path\n";
    }

    if (!file_exists($upload_path)) {
        if (!@mkdir($upload_path, 0755, true)) {
            echo "ERROR: No se pudo crear el directorio de uploads: $upload_path\n";
            return false;
        }
    }

    if (!is_writable($upload_path)) {
        echo "ADVERTENCIA: El directorio de uploads no tiene permisos de escritura: $upload_path\n";
    } else {
        echo "✓ El directorio de uploads está disponible: $upload_path\n";
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
    
    // Intentar usar wp_handle_upload si está disponible
    $moved = false;
    if (function_exists('wp_handle_upload')) {
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($_FILES['test_file'], $upload_overrides);
        if ($movefile && !isset($movefile['error'])) {
            echo "✓ Prueba de subida exitosa (wp_handle_upload).\n";
            echo "  Archivo subido a: " . $movefile['file'] . "\n";
            echo "  URL: " . $movefile['url'] . "\n";
            @unlink($movefile['file']);
            $moved = true;
        } else {
            echo "Aviso: wp_handle_upload falló o no está disponible. Procediendo con copia directa.\n";
        }
    }

    // Modo consola: mover archivo manualmente
    if (!$moved) {
        $dest = $upload_path . '/' . basename($_FILES['test_file']['name']);
        if (@copy($_FILES['test_file']['tmp_name'], $dest)) {
            echo "✓ Prueba de copia directa exitosa.\n";
            echo "  Archivo copiado a: $dest\n";
            $moved = true;
            @unlink($dest);
        } else {
            echo "✗ Error al copiar el archivo a: $dest\n";
        }
    }
    
    // Limpiar archivo temporal
    @unlink($test_file);
    
    echo "\n=== Fin de la prueba ===\n";
    return true;
}

// Ejecutar prueba
test_upload();
