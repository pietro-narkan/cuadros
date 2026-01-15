# Implementación de Grosores por Orientación - v1.1.0

## Cambios Realizados

Se ha implementado un sistema de grosores diferenciados por orientación de imagen, permitiendo un control más fino sobre la apariencia de marcos y paspartús según si la imagen es vertical, horizontal o cuadrada.

### 1. Configuración del Admin (`includes/class-admin-settings.php`)

**Nuevos campos de configuración:**
- `grosor_marco_vertical` - Grosor del marco para imágenes verticales
- `grosor_marco_cuadrado` - Grosor del marco para imágenes cuadradas (1:1)  
- `grosor_marco_horizontal` - Grosor del marco para imágenes horizontales
- `grosor_paspartu_vertical` - Grosor del paspartú para imágenes verticales
- `grosor_paspartu_cuadrado` - Grosor del paspartú para imágenes cuadradas (1:1)
- `grosor_paspartu_horizontal` - Grosor del paspartú para imágenes horizontales

**Valores por defecto recomendados:**
- Marco vertical: 8px, cuadrado: 10px, horizontal: 6px
- Paspartú vertical: 30px, cuadrado: 25px, horizontal: 20px

### 2. Lógica Frontend (`includes/class-frontend.php`)

**Nueva función `detectarOrientacion()`:**
- Detecta automáticamente si una imagen es vertical, horizontal o cuadrada
- Usa un margen de tolerancia del 10% para determinar si es cuadrada (ratio ≈ 1:1)

**Actualización de la función `actualizar()`:**
- Obtiene los grosores apropiados según la orientación detectada
- Aplica los valores específicos para marco y paspartú
- Incluye logging para debug de la orientación detectada

**Actualización del lightbox:**
- También detecta orientación para aplicar grosores correctos en el zoom
- Mantiene consistencia visual entre vista normal y lightbox

### 3. Migración de Configuraciones (`cuadros.php`)

**Función `cuadros_migrate_settings()`:**
- Migra automáticamente configuraciones existentes del formato anterior
- Convierte `grosor_marco` único a los 3 nuevos campos por orientación
- Convierte `grosor_paspartu` único a los 3 nuevos campos por orientación
- Aplica valores inteligentes basados en la configuración anterior:
  - Cuadrado: +2px marco, -5px paspartú
  - Horizontal: -2px marco, -10px paspartú
  - Vertical: mantiene valor original

**Ejecución de migración:**
- Se ejecuta en la activación del plugin
- También se ejecuta en cada carga para asegurar compatibilidad
- Elimina campos antiguos después de la migración

### 4. Estructura de Datos

**Nueva estructura en `wp_options.cuadros_settings`:**
```php
[
    'grosor_marco_vertical' => 8,
    'grosor_marco_cuadrado' => 10, 
    'grosor_marco_horizontal' => 6,
    'grosor_paspartu_vertical' => 30,
    'grosor_paspartu_cuadrado' => 25,
    'grosor_paspartu_horizontal' => 20,
    // ... resto de configuraciones
]
```

## Cómo Funciona

1. **Detección Automática**: Al cargar una imagen, el sistema calcula su ratio (ancho/alto)
2. **Clasificación**: 
   - Ratio ≈ 1 (±10%): Cuadrada
   - Ratio > 1: Horizontal  
   - Ratio < 1: Vertical
3. **Aplicación**: Se usan los grosores específicos para esa orientación
4. **Consistencia**: Tanto la vista normal como el lightbox usan la misma lógica

## Beneficios

- **Control Granular**: Diferentes grosores para diferentes orientaciones
- **Mejor Estética**: Marcos más apropiados según el formato de imagen
- **Compatibilidad**: Migración automática de configuraciones existentes
- **Flexibilidad**: Cada orientación se puede ajustar independientemente

## Configuración Recomendada

**Para tienda de arte/fotografía típica:**
- **Vertical** (retratos): Marco 8px, Paspartú 30px
- **Cuadrado** (Instagram): Marco 10px, Paspartú 25px  
- **Horizontal** (paisajes): Marco 6px, Paspartú 20px

**Para productos más pequeños:**
- Reducir todos los valores en 2-4px

**Para productos grandes/premium:**
- Aumentar todos los valores en 2-5px

## Retrocompatibilidad

- ✅ Configuraciones existentes se migran automáticamente
- ✅ No se requiere reconfiguración manual
- ✅ Valores calculados inteligentemente basados en configuración anterior
- ✅ Funciona con instalaciones existentes sin cambios

## Notas Técnicas

- La detección de orientación usa un margen de tolerancia del 10% para cuadrados
- Los logs de debug muestran la orientación detectada y grosores aplicados
- La migración se ejecuta de forma segura sin afectar otras configuraciones
- Los campos antiguos se eliminan automáticamente después de la migración

---

**Versión**: 1.1.0  
**Fecha**: Enero 2026  
**Compatibilidad**: WordPress 5.0+, WooCommerce 5.0+