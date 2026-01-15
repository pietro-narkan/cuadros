<?php
/**
 * Plugin Name: Cuadros - Visualizador de Marcos y Paspartús
 * Plugin URI: https://ejemplo.com/cuadros
 * Description: Plugin para visualizar dinámicamente marcos y paspartús sobre imágenes de productos WooCommerce.
 * Version: 1.2.0
 * Author: Tu Nombre
 * License: GPL v2 or later
 * Text Domain: cuadros
 * Domain Path: /languages
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('CUADROS_VERSION', '1.2.0');
define('CUADROS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CUADROS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CUADROS_ASSETS_URL', CUADROS_PLUGIN_URL . 'assets/');

// Verificar si WooCommerce está activo
function cuadros_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php _e('El plugin <strong>Cuadros</strong> requiere WooCommerce para funcionar. Por favor, instala y activa WooCommerce.', 'cuadros'); ?></p>
            </div>
            <?php
        });
        return false;
    }
    return true;
}

// Declarar compatibilidad con WooCommerce
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('product_block_editor', __FILE__, true);
    }
});

// Cargar clases del plugin
function cuadros_init() {
    if (!cuadros_check_woocommerce()) {
        return;
    }

    // Asegurar migración de configuraciones en cada carga
    cuadros_migrate_settings();

    // Cargar archivos de clases
    require_once CUADROS_PLUGIN_DIR . 'includes/class-admin-settings.php';
    require_once CUADROS_PLUGIN_DIR . 'includes/class-frontend.php';
    require_once CUADROS_PLUGIN_DIR . 'includes/class-assets-manager.php';

    // Inicializar clases
    new Cuadros_Admin_Settings();
    new Cuadros_Frontend();
    new Cuadros_Assets_Manager();
}

add_action('plugins_loaded', 'cuadros_init');

// Registrar shortcode globalmente
function cuadros_register_shortcode() {
    if (class_exists('Cuadros_Frontend')) {
        $frontend = new Cuadros_Frontend();
        add_shortcode('cuadros_visualizador', array($frontend, 'render_visualizador_shortcode'));
    }
}
add_action('init', 'cuadros_register_shortcode');

// Hook de activación
register_activation_hook(__FILE__, 'cuadros_activate');
function cuadros_activate() {
    // Migrar configuraciones existentes si es necesario
    cuadros_migrate_settings();
    
    // Crear opciones por defecto
    $default_options = array(
        'grosor_marco_vertical' => 8,
        'grosor_marco_cuadrado' => 10,
        'grosor_marco_horizontal' => 6,
        'grosor_paspartu_vertical' => 30,
        'grosor_paspartu_cuadrado' => 25,
        'grosor_paspartu_horizontal' => 20,
        'grosor_doble_marco_vertical' => 3,
        'grosor_doble_marco_cuadrado' => 4,
        'grosor_doble_marco_horizontal' => 2,
        'paspartu_colors' => array(
            'blanco' => '#ffffff',
            'negro' => '#222222',
            'crema' => '#f5f5dc',
            'rojo' => '#a83232'
        ),
        'marco_colors' => array(),
        'doble_marco_enabled' => array(),
        'doble_marco_colors' => array(),
        'doble_marco_grosores' => array(),
        'marco_images' => array(),
        'dimensions' => array(
            'vertical' => array('width' => 70, 'height' => 90),
            'horizontal' => array('width' => 90, 'height' => 70),
            '1:1' => array('width' => 80, 'height' => 80)
        )
    );

    if (!get_option('cuadros_settings')) {
        update_option('cuadros_settings', $default_options);
    }

    // Crear directorio para imágenes subidas
    $upload_dir = wp_upload_dir();
    $cuadros_dir = $upload_dir['basedir'] . '/cuadros/';
    if (!file_exists($cuadros_dir)) {
        wp_mkdir_p($cuadros_dir);
    }
}

// Función de migración para configuraciones existentes
function cuadros_migrate_settings() {
    $settings = get_option('cuadros_settings', array());
    $needs_update = false;
    
    // Migrar grosor_marco antiguo a los nuevos campos
    if (isset($settings['grosor_marco']) && !isset($settings['grosor_marco_vertical'])) {
        $old_grosor_marco = intval($settings['grosor_marco']);
        $settings['grosor_marco_vertical'] = $old_grosor_marco;
        $settings['grosor_marco_cuadrado'] = max(6, $old_grosor_marco + 2); // Un poco más grueso para cuadrados
        $settings['grosor_marco_horizontal'] = max(4, $old_grosor_marco - 2); // Un poco más delgado para horizontales
        unset($settings['grosor_marco']); // Eliminar el campo antiguo
        $needs_update = true;
    }
    
    // Migrar grosor_paspartu antiguo a los nuevos campos
    if (isset($settings['grosor_paspartu']) && !isset($settings['grosor_paspartu_vertical'])) {
        $old_grosor_paspartu = intval($settings['grosor_paspartu']);
        $settings['grosor_paspartu_vertical'] = $old_grosor_paspartu;
        $settings['grosor_paspartu_cuadrado'] = max(15, $old_grosor_paspartu - 5); // Un poco menos para cuadrados
        $settings['grosor_paspartu_horizontal'] = max(10, $old_grosor_paspartu - 10); // Menos para horizontales
        unset($settings['grosor_paspartu']); // Eliminar el campo antiguo
        $needs_update = true;
    }
    
    if ($needs_update) {
        update_option('cuadros_settings', $settings);
        error_log('[cuadros] Settings migrated to new orientation-based structure');
    }
}

// Hook de desactivación
register_deactivation_hook(__FILE__, 'cuadros_deactivate');
function cuadros_deactivate() {
    // Limpiar cache si es necesario
}

// Cargar traducciones
function cuadros_load_textdomain() {
    load_plugin_textdomain('cuadros', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'cuadros_load_textdomain');
