<?php
// Este archivo contiene la lógica AJAX para el plugin.

// Función AJAX para cargar las preguntas hijas
function fqr_cargar_hijas_callback() {
    global $wpdb;
    $prefijo = $wpdb->prefix . 'fqr_';
    $tabla_faq = $prefijo . 'faq';
    
    $id_padre = intval($_POST['id_padre']);
    $hijas = $wpdb->get_results($wpdb->prepare("SELECT id, pregunta, respuesta FROM $tabla_faq WHERE FK_idpadre = %d AND borrado = 0 ORDER BY prioridad desc", $id_padre));
    
    $respuesta = '';
    

    if (!empty($hijas)) {
        foreach ($hijas as $hija) {
            $respuesta_html = apply_filters('the_content', $hija->respuesta);
            $respuesta .= '<li class="faq-item" data-padre="' . esc_attr($id_padre) . '">';
            $respuesta .= '<strong class="faq-question" style="cursor:pointer;" data-id="' . esc_attr($hija->id) . '">' . esc_html($hija->pregunta) . '</strong><br>';
            $respuesta .= '<div class="faq-answer" style="display:none">' . $respuesta_html . '</div>';
            $respuesta .= '</li>';
        }
    } else {
        // Insertar formulario directamente sin verificar BD
        $nonce = wp_create_nonce('fqr_form_nonce');
        $plugin_url = plugin_dir_url(__FILE__) .'';
        $respuesta .= <<<EOD
        <div class="formulario-base show" data-padre-form="{$id_padre}">
            <form method="post" class="fqr-form">
                <input type="hidden" name="action" value="fqr_submit_form">
                <input type="hidden" name="id_pregunta" value="{$id_padre}">
                <input type="hidden" name="fqr_nonce" value="{$nonce}">
                <label>Nombre: <input type="text" name="nombre" required></label>
                <label>Email: <input type="email" name="email" required></label>
                <label>Mensaje: <input type="text" name="mensaje" required></label>
                <label for="captcha">Introduce el texto de la imagen:</label>
                <div class="captcha">
                    <img src="{$plugin_url}captcha.php" alt="CAPTCHA" id="captcha-img">
                    <button class="captcha-button" type="button" onclick="document.getElementById('captcha-img').src='{$plugin_url}captcha.php?' + Math.random();">Recargar captcha</button>
                </div>
                <input type="text" name="captcha" required>
                <button type="submit" name="enviar_formulario">Enviar</button>
            </form>
        </div>
    EOD;
    }
    
    echo $respuesta;
    wp_die();
}

add_action('wp_ajax_actualizar_prioridad_faq', function() {
    check_ajax_referer('faq_priority_nonce', 'security');
    
    global $wpdb;
    $table = $wpdb->prefix . 'fqr_faq';
    
    $wpdb->update(
        $table,
        ['prioridad' => $_POST['prioridad']],
        ['id' => $_POST['id']],
        ['%d'],
        ['%d']
    );
    
    wp_die();
});

add_action('wp_ajax_fqr_cargar_hijas', 'fqr_cargar_hijas_callback');
add_action('wp_ajax_nopriv_fqr_cargar_hijas', 'fqr_cargar_hijas_callback');

function fqr_submit_form_callback() {
    check_ajax_referer('fqr_form_nonce', 'fqr_nonce');

    session_start();
    global $wpdb;
    $prefijo = $wpdb->prefix . 'fqr_';
    $tabla_contacto = $prefijo . 'contacto';

    $response = ['success' => false, 'message' => ''];

    if ($_POST['captcha'] == $_SESSION['captcha']) {
        $nombre = sanitize_text_field($_POST["nombre"]);
        $email = sanitize_email($_POST["email"]);
        $mensaje = sanitize_text_field($_POST["mensaje"]);
        $id_pregunta = isset($_POST["id_pregunta"]) ? intval($_POST["id_pregunta"]) : 0;

        $wpdb->insert($tabla_contacto, [
            "nombre" => $nombre,
            "email" => $email,
            "mensaje" => $mensaje,
            "FK_idfaq" => $id_pregunta
        ]);

        $response['success'] = true;
        $response['message'] = "Gracias, <strong>" . esc_html($nombre) . "</strong>. Hemos recibido tu mensaje ✅";
    } else {
        $response['message'] = "Captcha incorrecto ❌, intenta de nuevo.";
    }

    wp_send_json($response);
}

add_action('wp_ajax_fqr_submit_form', 'fqr_submit_form_callback');
add_action('wp_ajax_nopriv_fqr_submit_form', 'fqr_submit_form_callback');
?>