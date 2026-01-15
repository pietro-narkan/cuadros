DOCUMENTACIÓN — Plugin Cuadros v1.1
=============================

Resumen
-------
Plugin "Cuadros" añade visualización dinámica de marcos y paspartús para productos WooCommerce variables. Permite a los clientes ver en tiempo real cómo se vería una obra de arte con diferentes marcos y paspartús antes de comprar.

## Características Principales

- **Visualización en tiempo real**: Los marcos y paspartús se muestran dinámicamente al seleccionar variaciones
- **Lightbox personalizado**: Zoom con navegación entre imágenes manteniendo el marco/paspartú
- **Responsive**: Optimizado para desktop y mobile
- **Configuración flexible**: Grosores y colores personalizables desde el admin
- **Uso de slugs**: Sistema robusto que maneja caracteres especiales (tildes, ñ, etc.)

Componentes principales
-----------------------

### PHP Backend
- `cuadros.php` — Archivo principal del plugin, inicialización y hooks
- `includes/class-admin-settings.php` — Panel de administración y configuración
- `includes/class-assets-manager.php` — Endpoints AJAX y gestión de datos
- `includes/class-frontend.php` — Lógica frontend y generación de JavaScript

### Frontend Assets
- `assets/css/frontend.css` — Estilos para la visualización de marcos/paspartús
- `assets/css/admin.css` — Estilos del panel de administración
- `assets/js/admin.js` — JavaScript del panel de administración

Configuración de WordPress
--------------------------

### Opción Principal: `cuadros_settings`
Estructura almacenada en `wp_options`:

```php
[
    'grosor_marco' => 10,           // Grosor del marco en píxeles
    'grosor_paspartu' => 35,        // Grosor del paspartú en píxeles
    'marco_colors' => [
        'plata' => '#C0C0C0',       // slug => color hex
        'madera-de-manio' => '#D2B48C',
        'negro' => '#000000'
    ],
    'paspartu_colors' => [
        'blanco' => '#FFFFFF',      // slug => color hex
        'turquesa' => '#40E0D0',
        'beige' => '#F5F5DC'
    ]
]
```

**Importante**: Las keys son los **slugs** de los términos de WooCommerce, no los nombres. Esto garantiza compatibilidad con caracteres especiales.

Atributos de WooCommerce Requeridos
-----------------------------------

El plugin requiere que el producto tenga estos atributos configurados:

1. **`pa_marco`** (Marco)
   - Tipo: Atributo de producto
   - Usado para: Variaciones
   - Términos: Los diferentes tipos de marco (ej: "Plata", "Madera de mañío", "Negro")

2. **`pa_paspartu`** (Paspartú) 
   - Tipo: Atributo de producto
   - Usado para: Variaciones
   - Términos: Los diferentes colores de paspartú (ej: "Blanco", "Turquesa", "Sin paspartú")

### Configuración de "Sin Paspartú"
Para ofrecer la opción sin paspartú, crear un término que contenga "sin" en el nombre o slug (ej: "Sin paspartú", "Ninguno").

Endpoints AJAX
--------------

Todos requieren nonce `cuadros_admin_nonce` y permisos `manage_options`.

### `cuadros_get_models`
- **Propósito**: Obtener términos del atributo `pa_marco`
- **Respuesta**: 
```json
{
    "success": true,
    "data": {
        "models": [
            {"name": "Madera de mañío", "slug": "madera-de-manio"},
            {"name": "Plata", "slug": "plata"}
        ]
    }
}
```

### `cuadros_get_paspartu_colors`
- **Propósito**: Obtener términos del atributo `pa_paspartu`
- **Respuesta**:
```json
{
    "success": true,
    "data": {
        "colors": [
            {"name": "Turquesa", "slug": "turquesa"},
            {"name": "Sin paspartú", "slug": "sin-paspartu"}
        ]
    }
}
```

Funcionamiento Frontend
-----------------------

### Inicialización
1. **Detección**: Busca productos variables con atributos `pa_marco` o `pa_paspartu`
2. **Estructura DOM**: Crea la siguiente jerarquía:
```html
<div id="cuadros-fondo">          <!-- Fondo gris, tamaño fijo -->
    <div id="cuadros-wrapper">    <!-- Contenedor del cuadro -->
        <div id="layer-marco"></div>     <!-- Borde del marco -->
        <div id="layer-paspartu"></div>  <!-- Fondo del paspartú -->
        <div id="layer-imagen">          <!-- Contenedor de imagen -->
            <img src="...">              <!-- Imagen del producto -->
        </div>
    </div>
</div>
```

### Cálculo de Dimensiones
1. **Fondo**: Mantiene el tamaño original del contenedor de la galería
2. **Factor de escala**: El cuadro ocupa el 85% del fondo disponible
3. **Proporciones**: Se mantiene la proporción original de la imagen
4. **Centrado**: El cuadro se centra usando flexbox
5. **Redondeo**: Todos los valores se redondean para evitar líneas de subpíxeles

### Búsqueda de Colores
El sistema busca colores usando los **slugs** de los términos:

```javascript
// El valor del select es el slug del término
var slug = $('#pa_marco').val(); // ej: "madera-de-manio"

// Búsqueda directa en el objeto de colores
var color = coloresMarco[slug]; // ej: "#D2B48C"
```

### Lightbox Personalizado
- **Navegación**: Flechas izquierda/derecha, contador de imágenes
- **Marco/Paspartú**: Solo se muestra en la primera imagen
- **Responsive**: Tamaño adaptado para mobile (80% pantalla)
- **Teclado**: Soporte para ESC (cerrar) y flechas (navegar)

Configuración del Admin
-----------------------

### Panel de Configuración
Ubicación: **WordPress Admin > Cuadros**

#### Sección Grosores
- **Grosor del Marco**: 1-50px (recomendado: 5-15px)
- **Grosor del Paspartú**: 1-100px (recomendado: 15-40px)

#### Sección Colores de Marco
- Carga automáticamente los términos de `pa_marco`
- Muestra nombre del término y slug para referencia
- Color picker para cada término
- Se guarda usando el slug como key

#### Sección Colores de Paspartú  
- Carga automáticamente los términos de `pa_paspartu`
- Muestra nombre del término y slug para referencia
- Color picker para cada término
- Detección automática de "sin paspartú"

Solución de Problemas
--------------------

### Problema: Colores no se aplican
**Causa**: Mismatch entre slugs guardados y slugs de términos
**Solución**: 
1. Ir al admin de Cuadros
2. Verificar que los slugs mostrados coincidan
3. Reconfigurar colores y guardar

### Problema: Líneas blancas finas
**Causa**: Problemas de renderizado de subpíxeles
**Solución**: El plugin usa redondeo inteligente y color de fondo del wrapper para eliminar gaps

### Problema: Imagen no se ve
**Causa**: Conflicto con tema o plugin de caché
**Solución**:
1. Limpiar caché del navegador (Ctrl+Shift+R)
2. Limpiar caché de WordPress
3. Verificar consola del navegador para errores

### Problema: "Sin paspartú" muestra color
**Causa**: El término no se detecta como "sin paspartú"
**Solución**: Asegurar que el slug contenga "sin" (ej: "sin-paspartu")

Logs de Debug
-------------

El plugin genera logs en la consola del navegador con prefijo `[cuadros]`:

```javascript
[cuadros] Dimensiones del contenedor: 600 x 600
[cuadros] Buscando marco: plata -> encontrado: #C0C0C0
[cuadros] Sin paspartú detectado: sin-paspartu
```

Para habilitar logs adicionales, modificar las funciones de búsqueda en `class-frontend.php`.

Compatibilidad
--------------

### WordPress/WooCommerce
- **WordPress**: 5.0+
- **WooCommerce**: 5.0+
- **PHP**: 7.4+

### Navegadores
- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

### Temas
- Compatible con temas estándar de WooCommerce
- Funciona con Elementor y constructores de páginas
- Adaptable a diferentes estructuras de galería

Personalización Avanzada
------------------------

### Modificar Factor de Escala
En `class-frontend.php`, línea ~52:
```javascript
var FACTOR_ESCALA = 0.85; // Cambiar a 0.75 para cuadros más pequeños
```

### Añadir Nuevos Colores por Defecto
En `cuadros.php`, función `cuadros_activate()`:
```php
'paspartu_colors' => array(
    'blanco' => '#ffffff',
    'negro' => '#222222',
    'nuevo_color' => '#FF5733' // Añadir aquí
)
```

### Personalizar Detección de "Sin Paspartú"
En `class-frontend.php`, función `buscarColorPaspartu()`:
```javascript
if (slug.includes('sin') || 
    slug.includes('ninguno') || 
    slug.includes('custom_term')) { // Añadir términos personalizados
    return null;
}
```

Historial de Cambios
-------------------

### v1.1 (Enero 2026)
- **Nuevo**: Sistema basado en slugs para mejor compatibilidad con caracteres especiales
- **Mejorado**: Eliminación de líneas blancas de subpíxeles
- **Mejorado**: Lightbox responsive optimizado para mobile
- **Mejorado**: Detección robusta de "sin paspartú"
- **Corregido**: Problemas de centrado en diferentes temas
- **Corregido**: Cálculo de dimensiones para imágenes cuadradas y verticales

### v1.0 (Enero 2026)
- Lanzamiento inicial
- Visualización básica de marcos y paspartús
- Panel de administración
- Lightbox básico

Notas para Desarrolladores
-------------------------

### Hooks Disponibles
El plugin no expone hooks personalizados actualmente, pero se pueden añadir según necesidades.

### Estructura de Archivos
```
cuadros/
├── cuadros.php                 # Archivo principal
├── includes/
│   ├── class-admin-settings.php
│   ├── class-assets-manager.php
│   └── class-frontend.php
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   └── js/
│       └── admin.js
├── DOCUMENTATION.md
└── README.txt
```

### Consideraciones de Rendimiento
- JavaScript se carga solo en páginas de producto
- CSS se minimiza y cachea
- AJAX endpoints optimizados con verificación de permisos
- Uso eficiente de jQuery sin conflictos

---

**Última actualización**: Enero 2026  
**Versión del plugin**: 1.1  
**Autor**: Desarrollado con asistencia de Kiro AI