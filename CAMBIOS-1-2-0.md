# Implementación de Sistema de Doble Marco - v1.2.0

## Cambios Realizados

Se ha implementado un sistema completo de **doble marco** que permite agregar un marco interior (más delgado) sobre el paspartú, creando un efecto visual más elegante y profesional.

### 1. Configuración del Admin (`includes/class-admin-settings.php`)

**Nuevos campos en la sección de Colores de Marco:**
- Checkbox "¿Doble marco?" para cada modelo de marco
- Selector de color para el marco interior cuando está habilitado
- Interfaz intuitiva que muestra/oculta opciones según el checkbox

**Nuevos campos de grosor:**
- `grosor_doble_marco_vertical` - Grosor del marco interior para imágenes verticales (3px)
- `grosor_doble_marco_cuadrado` - Grosor del marco interior para imágenes cuadradas (4px)  
- `grosor_doble_marco_horizontal` - Grosor del marco interior para imágenes horizontales (2px)

**Estructura de datos:**
```php
'doble_marco_enabled' => [
    'slug-del-marco' => true/false
],
'doble_marco_colors' => [
    'slug-del-marco' => '#8B4513'  // Color por defecto: marrón
]
```

### 2. Lógica Frontend (`includes/class-frontend.php`)

**Nueva función `buscarDobleMarco()`:**
- Determina si el doble marco está habilitado para el modelo seleccionado
- Retorna el color configurado para el marco interior
- Maneja búsquedas por slug exacto y parcial

**Actualización de la estructura DOM:**
- Nuevo layer `#layer-doble-marco` con z-index 4 (sobre el paspartú)
- Posicionamiento dinámico basado en los grosores de marco y paspartú

**Cálculo de posicionamiento:**
```javascript
var dobleMarcoPos = bordeMarco + bordePaspartu - bordeDobleMarco;
```

**Integración con lightbox:**
- El doble marco también se muestra en el zoom
- Mantiene consistencia visual entre vista normal y lightbox
- Solo se aplica en la primera imagen de la galería

### 3. Estilos CSS (`assets/css/frontend.css`)

**Nuevo layer para doble marco:**
```css
#layer-doble-marco {
    position: absolute;
    box-sizing: border-box;
    pointer-events: none;
    z-index: 4;
}
```

### 4. Jerarquía Visual

**Orden de capas (z-index):**
1. `#layer-paspartu` (z-index: 1) - Fondo del paspartú
2. `#layer-imagen` (z-index: 2) - Imagen del producto
3. `#layer-marco` (z-index: 3) - Marco principal exterior
4. `#layer-doble-marco` (z-index: 4) - Marco interior sobre paspartú

**Estructura visual:**
```
┌─────────────────────────────────┐
│ Marco Principal (exterior)      │
│ ┌─────────────────────────────┐ │
│ │ Paspartú                    │ │
│ │ ┌─────────────────────────┐ │ │
│ │ │ Marco Interior (doble)  │ │ │
│ │ │ ┌─────────────────────┐ │ │ │
│ │ │ │ Imagen del Producto │ │ │ │
│ │ │ └─────────────────────┘ │ │ │
│ │ └─────────────────────────┘ │ │
│ └─────────────────────────────┘ │
└─────────────────────────────────┘
```

## Cómo Funciona

### 1. Configuración en el Admin
1. Ve a **WordPress Admin > Cuadros**
2. En la sección "Colores de Marco", cada modelo tiene:
   - Campo de color principal (marco exterior)
   - Checkbox "¿Doble marco?"
   - Campo de color del marco interior (aparece al marcar el checkbox)
3. En "Grosores", configura los grosores del doble marco por orientación

### 2. Aplicación Automática
1. Cuando un cliente selecciona un marco con doble marco habilitado
2. El sistema aplica automáticamente:
   - Marco principal (exterior) con el color y grosor configurado
   - Paspartú (si está seleccionado)
   - Marco interior sobre el paspartú con color y grosor específicos

### 3. Detección de Orientación
- **Vertical**: Doble marco de 3px (más visible en retratos)
- **Cuadrado**: Doble marco de 4px (equilibrado para formato 1:1)
- **Horizontal**: Doble marco de 2px (sutil para paisajes)

## Configuración Recomendada

### Grosores Típicos
- **Marco Principal**: 6-10px según orientación
- **Paspartú**: 20-30px según orientación  
- **Doble Marco**: 2-4px según orientación

### Combinaciones de Colores Efectivas
- **Marco dorado + doble marco marrón**: Elegante para arte clásico
- **Marco negro + doble marco plata**: Moderno para fotografía
- **Marco blanco + doble marco gris**: Minimalista para arte contemporáneo

### Cuándo Usar Doble Marco
- ✅ Productos premium o de alta gama
- ✅ Arte clásico o fotografía profesional
- ✅ Cuando se quiere crear más profundidad visual
- ❌ Productos muy pequeños (puede saturar visualmente)
- ❌ Estilos muy minimalistas

## Beneficios del Sistema

### Para el Cliente
- **Visualización Premium**: Efecto más sofisticado y profesional
- **Mejor Percepción de Valor**: Los marcos dobles se asocian con calidad superior
- **Diferenciación Visual**: Fácil distinción entre opciones básicas y premium

### Para el Vendedor
- **Upselling Natural**: Los marcos dobles justifican precios más altos
- **Diferenciación de Productos**: Distintos niveles de marcos (simple/doble)
- **Flexibilidad**: Cada modelo puede tener o no doble marco

### Técnicos
- **Configuración Granular**: Control independiente por modelo y orientación
- **Integración Perfecta**: Funciona con todas las características existentes
- **Rendimiento Optimizado**: Sin impacto en velocidad de carga

## Compatibilidad

- ✅ **Retrocompatible**: Marcos existentes siguen funcionando igual
- ✅ **Migración Automática**: No requiere reconfiguración
- ✅ **Responsive**: Funciona en desktop y mobile
- ✅ **Lightbox**: Doble marco también en zoom
- ✅ **Orientaciones**: Soporte completo para vertical/horizontal/cuadrado

## Notas Técnicas

### Posicionamiento
El doble marco se posiciona usando la fórmula:
```
posición = grosor_marco_principal + grosor_paspartú - grosor_doble_marco
```

### Z-Index
- Marco principal: 3
- Doble marco: 4 (siempre visible sobre el paspartú)

### Colores por Defecto
- Si no se especifica color: `#8B4513` (marrón saddlebrown)
- Recomendado usar colores que contrasten con el paspartú

### Logging
El sistema registra en consola:
```
[cuadros] Orientación detectada: vertical Marco: 8px Paspartú: 4px Doble marco: 3px habilitado: true
```

---

**Versión**: 1.2.0  
**Fecha**: Enero 2026  
**Compatibilidad**: WordPress 5.0+, WooCommerce 5.0+  
**Nuevas Características**: Sistema completo de doble marco con configuración granular