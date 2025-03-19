<?php
/*

Plugin Name: FAQer PreRelease0.9
Description: Pua que maquinas somos
Author: Fernando y Raul

*/

require_once plugin_dir_path(__FILE__) . 'includes/fqr-functions.php'; // Importar los menús del plugin
require_once plugin_dir_path(__FILE__) . 'init-faqer.php'; // Importar las funciones que se deben de ejecutar al iniciarse el plugin
require_once plugin_dir_path(__FILE__) . 'includes/fqr-shortcode.php';
include_once plugin_dir_path(__FILE__) . 'includes/fqr-ajax.php';
register_activation_hook(__FILE__,"activation"); // Función que se ejecuta al activar el plugin

add_shortcode('mi_shortcode', 'frontend_shortcode');

// Evita el acceso por ruta relativa 
if (!defined('ABSPATH')) {
    exit;
}

function fqr_enqueue_scripts() {
    wp_enqueue_script('fqr-js', plugin_dir_url(__FILE__) . 'assets/fqr-faq-query.js', array('jquery'), '1.0', true); // Añade el js antes de </body> con su dependencia (jquery) e indica la version de mi script para que los navegadores, al actualizar el plugin, vuelvan a descargarlo
    wp_localize_script('fqr-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}

add_action('admin_enqueue_scripts', function() {
    wp_enqueue_style('faqer-backend', plugin_dir_url(__FILE__) . 'assets/faqer-backend.css');
});

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('faqer-frontend', plugin_dir_url(__FILE__) . 'assets/faqer-frontend.css');
});

add_action('wp_enqueue_scripts', 'fqr_enqueue_scripts');

add_action('admin_enqueue_scripts', function($hook) {
    if (!isset($_GET['page']) || $_GET['page'] !== 'FAQ') {
        return;
    } // Ajusta según el slug de tu página
    
    wp_enqueue_script(
        'faq-admin-js', 
        plugin_dir_url(__FILE__) . 'assets/faqer-admin.js', 
        ['jquery'], 
        '1.0', 
        true
    );
    
    wp_localize_script('faq-admin-js', 'faqVars', [
        'nonce' => wp_create_nonce('faq_priority_nonce')
    ]);
});

// https://github.com/orgs/ValentinaSystem-FAQ/projects/1/views/1
