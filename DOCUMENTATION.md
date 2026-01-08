DOCUMENTACIÓN — Plugin Cuadros
=============================

Resumen
-------
Plugin "Cuadros" añade gestión y visualización de marcos y paspartús para productos WooCommerce.

Componentes principales
-----------------------
- PHP: clases en `includes/`
  - `class-admin-settings.php` — panel de administración, registro de settings y render de campos.
  - `class-assets-manager.php` — uploads, listado y borrado de imágenes (endpoints AJAX).
  - `class-frontend.php` — inyecta script en footer que crea y posiciona capas dinámicamente sobre la imagen del producto.
- JS: `assets/js/admin.js` — UI del admin (modal subida, listar/eliminar marcos) y llamadas AJAX.
- CSS: `assets/css/frontend.css`, `assets/css/admin.css` — estilos para frontend y admin.

Option principal
----------------
Nombre: `cuadros_settings` (opción serializada en `wp_options`)
Estructura (PHP array) — ejemplo:

{
  "marco_images": [
    {
      "modelo": "Gold",
      "orientation": "vertical",
      "url": "https://.../3_4-Gold-31.png",
      "path": "/ruta/a/3_4-Gold-31.png",
      "filename": "3_4-Gold-31.png",
      "uploaded": "2026-01-06 15:02:41"
    }
  ],
  "paspartu_colors": {
    "blanco": "#ffffff",
    "negro": "#222222"
  },
  "dimensions": {
    "vertical": {"width": 70, "height": 90},
    "horizontal": {"width": 90, "height": 70}
  }
}

Descripción de campos
- `marco_images`: lista de marcos subidos; cada entrada tiene `modelo` (antes `color`), `orientation`, `url`, `path`, `filename`, `uploaded`.
- `paspartu_colors`: mapa slug->hex con colores para cada término del atributo `pa_paspartu`.
- `dimensions`: porcentajes usados por frontend para dimensionar capas.

Endpoints AJAX (admin)
----------------------
Todos esperan `nonce` (clave `cuadros_admin_nonce`) y permisos `manage_options`.

1) `cuadros_upload_marco` (POST multipart/form-data)
- Parámetros: `nonce`, `modelo` (string), `orientation` (vertical|horizontal), `marco_image` (file PNG)
- Comportamiento: maneja `wp_handle_upload`, añade la entrada en `cuadros_settings['marco_images']`, intenta `update_option` y valida guardado leyendo la BD. Devuelve JSON con `success` y `marcos` (estado local).

2) `cuadros_get_marcos` (POST)
- Parámetros: `nonce`
- Devuelve: `marcos` — lista desde `cuadros_settings` (con fallback a lectura directa de BD si `get_option` devuelve vacío).

3) `cuadros_delete_marco` (POST)
- Parámetros: `nonce`, `filename`
- Comportamiento: borra el archivo físico si existe, elimina la entrada de `marco_images`, reindexa y actualiza la opción. Responde con mensaje y el nuevo listado opcional.

4) `cuadros_get_models` (POST)
- Parámetros: `nonce`
- Comportamiento: obtiene los términos del atributo `pa_marco` (WooCommerce) y devuelve `models` (lista de nombres).

5) `cuadros_get_paspartu_colors` (POST)
- Parámetros: `nonce`
- Comportamiento: obtiene los términos del atributo `pa_paspartu` y devuelve `colors` (lista de nombres). El admin renderiza inputs para cada color y permite asignar un valor hex.

6) `cuadros_debug_status` (POST)
- Parámetros: `nonce`
- Devuelve: `total_marcos`, `total_paspartu_colors`, `marcos`, `paspartu_colors`, `full_settings` — utilidad para debug remoto.

Cambios importantes realizados
-----------------------------
- El sanitizador registrado originalmente con `register_setting` fue removido para evitar que llamadas AJAX borren `marco_images`. En su lugar se añadió un `pre_update_option_cuadros_settings` (método `sanitize_on_admin_form`) que:
  - pasa intacto el payload proveniente de AJAX (cuando contiene sólo `marco_images`);
  - aplica sanitización solo cuando los datos vienen del formulario admin (colores/dimensiones);
  - preserva `marco_images` en casos intermedios.
- Se añadieron fallbacks de lectura cruda desde la tabla `wp_options` cuando `get_option` devolvía vacío.
- Se añadió endpoint `cuadros_debug_status` para inspección rápida.
- Se cambió la llave `color` por `modelo` en las nuevas subidas — `class-frontend.php` fue adaptado para aceptar ambos (compatibilidad).
- JS admin: el modal ahora usa `modelo` (lista tomada desde `pa_marco`) y muestra vista previa del PNG antes de subir.

SOLUCIÓN AL PROBLEMA DE POSICIONAMIENTO (Enero 2026)
====================================================

Problema Original
-----------------
El marco se mostraba desalineado, apareciendo en posiciones incorrectas (esquina superior izquierda, cubriendo el menú del sitio) en lugar de estar centrado sobre la imagen del producto.

Diagnóstico
-----------
1. **Creación incorrecta de capas**: Las capas se creaban en PHP usando `woocommerce_before_single_product` hook, lo que las colocaba FUERA de la galería de imágenes.
2. **Contenedor incorrecto**: El JavaScript intentaba mover las capas a contenedores que no eran los apropiados.
3. **Cálculos de posición erróneos**: Se usaban métodos como `offset()` y `getBoundingClientRect()` que daban coordenadas absolutas incorrectas.
4. **Z-index invertido**: El marco aparecía ENCIMA de la imagen en lugar de DETRÁS.

Solución Implementada
--------------------

### 1. Eliminación del Hook PHP
**ANTES**: Las capas se creaban en PHP con `add_action('woocommerce_before_single_product', 'maybe_add_overlay_layers')`
**DESPUÉS**: Se eliminó completamente este hook. Las capas ahora se crean dinámicamente con JavaScript.

### 2. Creación Dinámica de Capas con JavaScript
```javascript
// Buscar la imagen del producto
var $productImage = $('.woocommerce-product-gallery__image').first().find('img').first();

// Buscar el contenedor padre (enlace <a> que envuelve la imagen)
var $imageLink = $productImage.closest('a');
var $container = $imageLink.length ? $imageLink : $productImage.parent();

// Crear las capas DENTRO del contenedor de la imagen
if ($('#layer-marco').length === 0) {
    $container.prepend('<div id="layer-marco" class="custom-overlay-layer"></div>');
}
if ($('#layer-paspartu').length === 0) {
    $container.prepend('<div id="layer-paspartu" class="custom-overlay-layer"></div>');
}
```

### 3. Posicionamiento Correcto
**Contenedor**: El enlace `<a>` que envuelve la imagen se configura con `position: relative`
**Capas**: Se posicionan con `position: absolute` relativas al contenedor
**Cálculo**: Las posiciones se calculan desde (0,0) porque las capas y la imagen están en el mismo contenedor

```javascript
// Calcular posiciones centradas (relativas al contenedor)
var marcoLeft = (imgWidth - marcoWidth) / 2;
var marcoTop = (imgHeight - marcoHeight) / 2;
```

### 4. Z-index Correcto
- **Paspartú**: `z-index: 1` (más atrás)
- **Marco**: `z-index: 2` (encima del paspartú, detrás de la imagen)
- **Imagen**: `z-index: 10` (encima de todo)

### 5. Dimensiones del Paspartú
**Problema**: El paspartú no rellenaba completamente el marco
**Solución**: Se ajustó para que sea ligeramente más pequeño que el marco (2% menos) para quedar perfectamente dentro del borde

```javascript
// El paspartú debe ser ligeramente más pequeño que el marco para quedar dentro
var paspartuWidthPercent = marcoWidthPercent - 2;
var paspartuHeightPercent = marcoHeightPercent - 2;
```

Resultado Final
---------------
- El marco aparece perfectamente centrado sobre la imagen del producto
- La imagen del producto se ve POR ENCIMA del marco (efecto realista)
- El paspartú rellena completamente el interior del marco
- El sistema funciona con diferentes temas de WooCommerce
- Las capas se reposicionan automáticamente al cambiar variaciones o redimensionar la ventana

Archivos Modificados en la Solución
-----------------------------------
1. `includes/class-frontend.php`:
   - Eliminado hook `woocommerce_before_single_product`
   - Reescrito completamente el JavaScript para crear capas dinámicamente
   - Implementado posicionamiento relativo correcto
   - Ajustadas dimensiones del paspartú

2. `assets/css/frontend.css`:
   - Eliminadas posiciones fijas (`top: 0`, `left: 0`)
   - Corregidos z-index para el orden correcto de capas
   - Configurado `overflow: visible` para permitir marcos más grandes que la imagen

Cómo Probar la Solución
-----------------------
1. Ir a una página de producto con atributos `pa_marco` y `pa_paspartu`
2. Seleccionar un marco y paspartú en las variaciones
3. Verificar que:
   - El marco aparece centrado sobre la imagen del producto
   - La imagen se ve por encima del marco
   - El paspartú rellena el interior del marco
   - Al cambiar variaciones, las capas se actualizan correctamente

Logs de Depuración
------------------
El sistema incluye logs detallados en la consola del navegador con prefijo `[cuadros]`:
- Detección de imagen y contenedor
- Dimensiones calculadas
- Posiciones aplicadas
- Marcos y paspartús encontrados/aplicados

Frontend (producto)
-------------------
- `class-frontend.php` crea dinámicamente las capas `#layer-marco` y `#layer-paspartu` dentro del contenedor de la imagen del producto.
- JavaScript detecta cambios en los selects de variaciones (`#pa_marco`, `#pa_paspartu`) para aplicar marcos y colores.
- Lógica para orientación: detecta texto tipo `800x1200` en opciones de variación y decide `vertical`/`horizontal`.
- Busca marcos de forma flexible (coincidencia exacta o case-insensitive) y muestra `background-image` sobre el `#layer-marco`; para paspartú aplica `background-color` desde `paspartu_colors`.

Cómo probar (quick start)
-------------------------
1) En admin → Cuadros: abrir panel de marcos
2) Click "Agregar Nuevo Marco" → seleccionar `Modelo` (dropdown), `Orientación`, subir PNG transparente → Subir
3) Verás en la respuesta del upload que el marco aparece. Para confirmar persistencia:
   - Abrir Console y ejecutar:
```javascript
jQuery.ajax({
  url: ajaxurl,
  type: 'POST',
  data: { action: 'cuadros_debug_status', nonce: cuadros_admin.nonce },
  success: function(r){ console.log(r); }
});
```
   - También revisa la BD: `SELECT option_value FROM wp_options WHERE option_name = 'cuadros_settings';` y busca `marco_images` (serializado).
4) En un producto con atributos `pa_marco` y `pa_paspartu`, selecciona variaciones y verifica que capas se muestran correctamente centradas sobre la imagen.

Depuración (pasos rápidos)
--------------------------
- Si tras subir el marco aparece en la respuesta pero desaparece al recargar:
  1. Ejecuta `cuadros_debug_status` (arriba) y compara `marcos` con lo que devolvió la subida.
  2. Revisa `wp-content/debug.log` para líneas que empiecen con `[cuadros]` (subidas, update_option returned, add_option returned, Raw DB value, etc.).
     - Ejemplo: `tail -n 200 wp-content/debug.log | grep "\[cuadros\]" -n`
  3. Comprueba si `update_option` devolvió `false` y qué hay en BD (`a:0:{}` indica array vacío).
  4. Verifica que no haya otro código que llame a `update_option('cuadros_settings', ...)` con datos vacíos (busca en código si hay escrituras manuales).
- Si eliminar un marco no desaparece de la UI:
  1. Borra desde la UI y ejecuta `cuadros_debug_status` para confirmar si `marco_images` se actualizó.
  2. Revisa `debug.log` para trazas añadidas en `delete_marco` (se registran `delete_marco: ...`).
- Si el marco no aparece centrado:
  1. Abre las herramientas de desarrollador (F12) y busca logs con prefijo `[cuadros]`
  2. Verifica que se detecte correctamente la imagen y el contenedor
  3. Revisa las dimensiones y posiciones calculadas en los logs

Notas para producción
---------------------
- Nonces: asegúrate de que `cuadros_admin_nonce` se genera y se pasa correctamente cuando el script admin es enqueueado.
- Permisos: los endpoints requieren `manage_options`; para entornos multisite o roles personalizados revisa la política.
- Caché de objetos: el plugin usa `wp_cache_delete('cuadros_settings','options')` en puntos críticos. En entornos con objetos cache (Redis, Memcached) asegúrate que el flush se propaga.
- Migración: si antes usabas `color` como key, el frontend sigue soportándolo; las nuevas subidas usan `modelo`.
- Compatibilidad: La solución funciona con temas estándar de WooCommerce, Elementor y diferentes estructuras de galería.

Archivos modificados (resumen)
-----------------------------
- `includes/class-assets-manager.php` — upload, get, delete, modelos, paspartu colors, debug status
- `includes/class-admin-settings.php` — registro settings, pre-update filter, render dinámico de paspartu colors
- `includes/class-frontend.php` — **REESCRITO COMPLETAMENTE** para crear capas dinámicamente y posicionarlas correctamente
- `assets/js/admin.js` — modal, preview, load models, delete flow
- `assets/css/frontend.css` — corregidos z-index y eliminadas posiciones fijas

--
Documento actualizado automáticamente por el asistente de desarrollo. Guarda este archivo en `wp-content/plugins/cuadros/DOCUMENTATION.md`.
