<?php
function dbMarkAsDeletedCategoria($id) {
    global $wpdb;
    $prefijo = $wpdb->prefix . 'fqr_';
    $tabla_categoria = $prefijo . 'categoria';

    $sql_query = "SET @disable_trigger = 0;
        UPDATE $tabla_categoria 
        SET borrado = 1 
        WHERE id = $id;";

    dbDelta($sql_query);
}

function dbMarkAsDeleted($id, $tabla) {
    $sql_query = "SET @disable_trigger = 0;
        UPDATE $tabla
        SET borrado = 1 
        WHERE id = $id;";

    dbDelta($sql_query);
}

function dbMarkAsDeletedFAQ($id) {
    global $wpdb;
    $prefijo = $wpdb->prefix . 'fqr_';
    $tabla_faq = $prefijo . 'faq';

    $sql_query = "SET @disable_trigger = 0;
        UPDATE $tabla_faq
        SET borrado = 1
        WHERE id = $id OR FK_idpadre = $id;";

    dbDelta($sql_query);
}

function dbMarkAsDeletedContact($id) {
    global $wpdb;
    $prefijo = $wpdb->prefix . 'fqr_';
    $tabla_contacto = $prefijo . 'contacto';

    $sql_query = "SET @disable_trigger = 0;
        UPDATE $tabla_contacto
        SET borrado = 1
        WHERE id = $id;";

    dbDelta($sql_query);
}


function dbMarkAsNotDeleted() {
    
}