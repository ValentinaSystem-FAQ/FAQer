<?php
// Añadimos la clase wp_list_table de wordpress y pedimos que sea requerido ya que no es publico
// (se recoge de otro enlace dentro del mismo wordpress).

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php'; //ABSPATH es ruta absoluta
}

include_once './categoria.act.php';

//Creamos la clase categoria_list_table que al extender de wp_list_table, cogemos lo que realiza la funcion
//wp_list_table y la personalizamos 
class Categoria_List_Table extends WP_List_Table
{

    //Creamos un constructor con la informacion principal (ajax desactivado por ahora)
    function __construct()
    {
        parent::__construct([
            'singular' => 'categoria',
            'plural' => 'categorias',
            'ajax' => false
        ]);
    }

    function get_total_items()
    {
        global $wpdb;
        $prefijo = $wpdb->prefix . 'fqr_';
        $tabla_categoria = $prefijo . 'categoria';
        return $wpdb->get_var("SELECT COUNT(*) FROM $tabla_categoria WHERE borrado=0");
    }

    //Obtiene los datos de la base de datos 
    function get_categorias($per_page, $page_number)
    {
        global $wpdb;
        $prefijo = $wpdb->prefix . 'fqr_'; // Prefijo para todas las tablas
        $tabla_categoria = $prefijo . 'categoria';
        $offset = ($page_number - 1) * $per_page;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, categoria, descripcion FROM $tabla_categoria WHERE borrado = 0 LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );
    }

    //Cargamos datos en las columnas
    function prepare_items()
    {
        $per_page = 15;
        $current_page = $this->get_pagenum();
        $total_items = $this->get_total_items();

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);

        $this->items = $this->get_categorias($per_page, $current_page);

        $columns = $this->get_columns();  // Obtiene las columnas definidas antes
        $hidden = [];                    // Columnas ocultas (vacío porque mostramos todas)
        $sortable = [];                    // Columnas ordenables (no usamos ordenamiento)
        $this->_column_headers = [$columns, $hidden, $sortable];
    }

    //Creamos nuestras columnas (indicamos el tipo de columna que queremos y despues le ponemos nombre)    
    function get_columns()
    {
        return [
            'cb' => '<input type="checkbox" />',
            'id' => 'ID',
            'categoria' => 'Categoría',
            'descripcion' => 'Descripción',
            'acciones' => 'Acciones'
        ];
    }

    //Agregamos contenido a las columnas
//Generamos hueco para checkbox indicando que el valor de cada checbox es igual a su id
    function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="registro[]" value="%s" />', $item['ID']);
    }

    //Generamos hueco para nombre con enlace externo y le da el efecto cliqueable
    function column_categoria($item)
    {
        $edit_link = '?page=FAQ_Categoria&action=edit&id=' . $item['id'];
        return sprintf('<strong><a href="%s">%s</a></strong>', $edit_link, esc_html($item['categoria']));
    }

    //Generamos hueco para descripcion     
    function column_descripcion($item)
    {
        return esc_html($item['descripcion']);
    }

    // Agrega botones de acción en la columna "Acciones"
    function column_acciones($item)
    {
        $edit_link = '?page=FAQ_Categoria&action=edit&id=' . $item['id'];
        $delete_link = '?page=FAQ_Categoria&action=delete&id=' . $item['id'];

        return sprintf( // Esta f no es mia, es del nombre de la funcion de php
            '<a href="%s">✏️ Editar</a> | <a href="%s" onclick="return confirm(\'¿Estás seguro?\')">❌ Eliminar</a>',
            esc_url($edit_link),
            esc_url($delete_link)
        );
    }

    //Generamos hueco para la id
    function column_id($item)
    {
        return esc_html($item['id']);
    }
}

//Muestra la tabla en la pagina con los datos que agregamos anteriormente
function FAQuality_categoria_page()
{
    global $wpdb;
    $prefijo = $wpdb->prefix . 'fqr_'; // Prefijo para todas las tablas
    $tabla_categoria = $prefijo . 'categoria';

    function FAQuality_selection_categoria_page()
    {
        require_once 'categoria.act.php';
        require_once 'bbdd.actions.php';

        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            FAQuality_edit_categoria_page();

            if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['paged'])) {
                wp_redirect(admin_url('admin.php?page=FAQ_Categoria&paged=2'));
            }

        } else if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            deleteCategoria();
        }
    }

    FAQuality_selection_categoria_page();

    echo '<div class="wrap"><div class="title-container"><h1 style="width: min-content;">Categorías</h1>';
    echo '<a class="button nuevo" href="?page=FAQ_New_Categoria">Nueva categoría</a></div>';
    $categoria_table = new Categoria_List_Table();
    $categoria_table->prepare_items();
    $categoria_table->display();
    echo '</div>';

    $categorias = $wpdb->get_results("SELECT id, categoria FROM $tabla_categoria WHERE borrado=0");
    ?>
    <!-- Creamos la lista con las categorias que queremos seleccionar -->
    <div class="wrap">
        <h1 style="margin-bottom: 12px;">Generar shortcode</h1>
        <!-- Lista dinamica -->
        <label for="id_cat" class="shortcode"><strong>Categorias:</strong> </label>
        <select name="id_cat" id="id_cat">
            <option value="" disabled selected>Selecciona categoría</option>
            <?php
            //Comprueba si existe categoria alguna
            if ($categorias) {
                //Reproduce en bucle las categorias existentes
                foreach ($categorias as $categoria) {
                    echo '<option value="' . esc_attr($categoria->id) . '">' . esc_html($categoria->categoria) . '</option>';
                }
            } else {
                echo '<option value="">No hay categorías disponibles</option>';
            }
            ?>
        </select><br>
        <!-- Contenedor de etiquetas, actualmente vacio ya que no se han agregado ninguna -->
        <div id="tagContainer" style="margin-top: 6px;"></div>

        <!-- Shortcode dinámico -->
        <!-- Contenedor del shortcode y botón -->
        <div style="display: flex; align-items: center; gap: 10px; margin-top: 0px;">
            <p class="shortcode"><strong>Shortcode final: </strong><span id="shortcode">[FAQuality categorias=""]</span>
            </p>
            <button onclick="copiarAlPortapapeles()" class="button nuevo">Copiar</button>
            <!-- Este span se mostrará después de copiar el texto -->
            <span id="copiadoMensaje" style="display: none; color: green;">¡Copiado!</span>
        </div>
        <script>
            function copiarAlPortapapeles() {
                actualizarShortcode(); // Asegura que el shortcode esté actualizado antes de copiar

                let texto = document.getElementById("shortcode").innerText; // Obtiene el shortcode dinámico
                navigator.clipboard.writeText(texto) // Copia al portapapeles
                    .then(() => { //El metodo writeText manda un promise (una funcion) que si se realiza ejecuta .then
                        // Mostrar el mensaje de "Copiado" al lado del botón
                        let mensaje = document.getElementById("copiadoMensaje");
                        mensaje.style.display = 'inline'; // Muestra el mensaje
                        setTimeout(() => {
                            mensaje.style.display = 'none'; // Oculta el mensaje después de 2 segundos
                        }, 2000);
                    }) //Si ocurre cualquier error, manda mensaje de error
                    .catch(err => console.error("Error al copiar: ", err)); // Manejo de errores
            }
        </script>
    </div>
    <script>
        let categoriasSeleccionadas = []; // Array que almacena los IDs de las categorías seleccionadas

        window.onload = function () {
            actualizarSelect();
        };

        function actualizarShortcode() {
            let shortcodeText = categoriasSeleccionadas.length > 0
                ? '[FAQuality categorias="' + categoriasSeleccionadas.join(",") + '"]'
                : '[FAQuality categorias=""]';
            document.getElementById("shortcode").innerText = shortcodeText;
        }

        function agregarCategoria() {
            let select = document.getElementById("id_cat");
            let categoriaID = select.value;
            let categoriaTexto = select.options[select.selectedIndex].text;

            if (categoriaID === "") {
                return; // No hacer nada si se selecciona "Selecciona categoría"
            }

            if (!categoriasSeleccionadas.includes(categoriaID)) {
                categoriasSeleccionadas.push(categoriaID);

                // Crear etiqueta visual
                let tagContainer = document.getElementById("tagContainer");
                let tag = document.createElement("span");
                tag.className = "tag";
                tag.style.cssText = "display: inline-block; background: #0073aa; color: white; padding: 5px 10px; margin: 5px; border-radius: 5px;";
                tag.innerHTML = categoriaTexto + ' <button onclick="eliminarCategoria(\'' + categoriaID + '\')" style="background: red; border: none; color: white; padding: 2px 5px; cursor: pointer;">X</button>';
                tag.setAttribute("data-id", categoriaID);
                tagContainer.appendChild(tag);

                actualizarShortcode();
                actualizarSelect();
            }

            // Resetear el select a "Selecciona categoría"
            select.selectedIndex = 0;
        }


        function eliminarCategoria(id) {
            categoriasSeleccionadas = categoriasSeleccionadas.filter(categoria => categoria !== id);

            let tagContainer = document.getElementById("tagContainer");
            let tags = tagContainer.getElementsByClassName("tag");
            for (let tag of tags) {
                if (tag.getAttribute("data-id") === id) {
                    tag.remove();
                    break;
                }
            }

            actualizarShortcode();
            actualizarSelect();
        }

        function actualizarSelect() {
            let select = document.getElementById("id_cat");
            let options = select.getElementsByTagName('option');

            for (let option of options) {
                if (option.value === "") {
                    option.style.display = '';
                } else if (categoriasSeleccionadas.includes(option.value)) {
                    option.style.display = 'none';
                } else {
                    option.style.display = '';
                }
            }

            select.selectedIndex = 0; // Siempre volver a "Selecciona categoría"
        }




        function marcarComoSelected(valorDeseado) {
            var select = document.getElementById('id_cat');
            var options = select.getElementsByTagName('option');

            for (var i = 0; i < options.length; i++) {
                if (options[i].value === valorDeseado) {
                    options[i].selected = true;
                    options[i].setAttribute('selected', 'selected');
                } else {
                    options[i].selected = false;
                    options[i].removeAttribute('selected');
                }
            }
        }
        // Evento para detectar cambios en el <select>
        document.getElementById("id_cat").addEventListener("change", agregarCategoria);
    </script>
    <?php
}




