=== Cuadros - Visualizador de Marcos y Paspartús ===

Contributors: tu_nombre
Tags: woocommerce, frames, mat, picture frames, product visualization, ecommerce
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.4
WC requires at least: 5.0
WC tested up to: 8.0
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin para visualizar dinámicamente marcos y paspartús sobre imágenes de productos WooCommerce.

== Descripción ==

Cuadros es un plugin de WordPress que permite a los clientes visualizar cómo quedarían diferentes marcos y paspartús sobre las imágenes de productos en tu tienda WooCommerce.

**Características principales:**
cz
*   **Visualización en tiempo real**: Los clientes pueden ver los marcos y paspartús aplicados a la imagen del producto inmediatamente.
*   **Detección automática de orientación**: El plugin detecta automáticamente si el cuadro es vertical u horizontal basándose en las dimensiones seleccionadas.
*   **Gestión de imágenes de marcos**: Sube imágenes PNG transparentes para diferentes colores de marco (oro, negro, blanco) y orientaciones.
*   **Configuración de colores de paspartú**: Personaliza los colores disponibles para los paspartús desde el panel de administración.
*   **Compatibilidad total**: Funciona con el tema estándar de WooCommerce y con Elementor.
*   **Fácil configuración**: Interfaz intuitiva en el panel de administración de WordPress.

== Instalación ==

1. Sube la carpeta `cuadros` al directorio `/wp-content/plugins/`.
2. Activa el plugin a través del menú 'Plugins' en WordPress.
3. Ve a 'Cuadros > Configuración' en el panel de administración para configurar los colores de paspartú y subir imágenes de marcos.
4. Asegúrate de que tus productos WooCommerce tengan los atributos `pa_marco` y `pa_paspartu` configurados.

== Uso ==

**Configuración inicial:**

1. Después de activar el plugin, ve a 'Cuadros > Configuración' en el panel de administración de WordPress.
2. En la pestaña 'Colores de Paspartú', configura los colores hexadecimales para los paspartús disponibles.
3. En 'Imágenes de Marcos', sube imágenes PNG transparentes para cada combinación de color y orientación.
4. En 'Dimensiones de Visualización', ajusta los porcentajes de ancho y alto para las orientaciones vertical y horizontal.

**En la tienda:**

1. Los clientes podrán seleccionar marcos y paspartús en la página de producto si el producto tiene los atributos correspondientes.
2. Al seleccionar un tamaño de variación (ej: "30x40"), el plugin detectará automáticamente la orientación.
3. Las capas de marco y paspartú se superpondrán a la imagen del producto en tiempo real.

== Preguntas frecuentes ==

= ¿El plugin funciona con cualquier tema de WordPress? =

Sí, el plugin está diseñado para funcionar con cualquier tema compatible con WooCommerce. También es compatible con Elementor.

= ¿Qué formatos de imagen son compatibles para los marcos? =

Solo se admiten imágenes PNG con transparencia para los marcos.

= ¿Puedo agregar más colores de marco además de oro, negro y blanco? =

Sí, puedes subir imágenes para cualquier color de marco a través del panel de administración.

= ¿El plugin afecta el rendimiento del sitio? =

El plugin está optimizado para no afectar el rendimiento. Las imágenes se cargan de forma asíncrona y solo en páginas de producto.

= ¿Qué pasa si no subo imágenes para alguna combinación color/orientación? =

El plugin usará imágenes placeholder por defecto o simplemente no mostrará el marco si no hay imagen disponible.

== Changelog ==

= 1.0.0 =
* Lanzamiento inicial del plugin
* Funcionalidad completa de visualización de marcos y paspartús
* Panel de administración para configuración
* Compatibilidad con WooCommerce y Elementor
* Sistema de subida de imágenes de marcos

== Actualización ==

Para actualizar el plugin:

1. Desactiva el plugin actual.
2. Sube la nueva versión sobrescribiendo los archivos existentes.
3. Reactiva el plugin.
4. Verifica que la configuración se mantenga intacta.

== Soporte ==

Para soporte técnico, reporte de bugs o solicitudes de características, por favor visita nuestro [sitio de soporte](https://ejemplo.com/soporte).

== Contribuir ==

¿Te gustaría contribuir al desarrollo de este plugin? Visita nuestro [repositorio en GitHub](https://github.com/tuusuario/cuadros).

== Licencia ==

Este plugin está licenciado bajo la GPL v2 o posterior.

