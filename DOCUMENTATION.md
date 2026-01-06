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
  - `class-frontend.php` — inyecta capas en la página de producto y el JS frontend que aplica marcos/paspartús.
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

Frontend (producto)
-------------------
- `class-frontend.php` inyecta dos capas: `#layer-marco` y `#layer-paspartu` en la galería de producto.
- JavaScript inyectado usa los selects de variaciones (`#pa_marco`, `#pa_paspartu`) para elegir qué marco y color aplicar.
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
4) En un producto con atributos `pa_marco` y `pa_paspartu`, selecciona variaciones y verifica que capas se muestran y se actualiza la imagen/paspartú.

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

Notas para producción
---------------------
- Nonces: asegúrate de que `cuadros_admin_nonce` se genera y se pasa correctamente cuando el script admin es enqueueado.
- Permisos: los endpoints requieren `manage_options`; para entornos multisite o roles personalizados revisa la política.
- Caché de objetos: el plugin usa `wp_cache_delete('cuadros_settings','options')` en puntos críticos. En entornos con objetos cache (Redis, Memcached) asegúrate que el flush se propaga.
- Migración: si antes usabas `color` como key, el frontend sigue soportándolo; las nuevas subidas usan `modelo`.

Archivos modificados (resumen)
-----------------------------
- `includes/class-assets-manager.php` — upload, get, delete, modelos, paspartu colors, debug status
- `includes/class-admin-settings.php` — registro settings, pre-update filter, render dinámico de paspartu colors
- `includes/class-frontend.php` — adaptación a `modelo` y mejoras de logging y matching
- `assets/js/admin.js` — modal, preview, load models, delete flow

Si quieres que genere además:
- Un changelog con diffs (extracto de los parches aplicados), o
- Un README en formato más formal (en `docs/CHANGELOG.md` o `README.md`) — dime cuál prefieres y lo creo.

--
Documento generado automáticamente por el asistente de desarrollo. Guarda este archivo en `wp-content/plugins/cuadros/DOCUMENTATION.md`.
