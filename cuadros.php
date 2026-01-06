<?php
/**
 * Plugin Name: Cuadros - Visualizador de Marcos y Paspartús
 * Plugin URI: https://ejemplo.com/cuadros
 * Description: Plugin para visualizar dinámicamente marcos y paspartús sobre imágenes de productos WooCommerce.
 * Version: 1.0.0
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
define('CUADROS_VERSION', '1.0.0');
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

// Cargar clases del plugin
function cuadros_init() {
    if (!cuadros_check_woocommerce()) {
        return;
    }

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
    // Crear opciones por defecto
    $default_options = array(
        'paspartu_colors' => array(
            'blanco' => '#ffffff',
            'negro' => '#222222',
            'crema' => '#f5f5dc',
            'rojo' => '#a83232'
        ),
        'marco_images' => array(),
        'dimensions' => array(
            'vertical' => array('width' => 70, 'height' => 90),
            'horizontal' => array('width' => 90, 'height' => 70)
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
