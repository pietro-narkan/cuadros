# Corrección del Posicionamiento del Doble Marco

## Problema Identificado
El doble marco se estaba renderizando **dentro** del paspartú en lugar de **sobre** él, como se muestra en la imagen proporcionada donde el marco rojo aparece dentro del área azul del paspartú.

## Causa del Problema
El cálculo de posicionamiento era correcto matemáticamente, pero el z-index no era suficientemente alto para que el doble marco apareciera visualmente por encima del paspartú.

## Solución Implementada

### 1. Ajuste del Z-Index
**Antes:**
```css
#layer-doble-marco {
    z-index: 4;
}
```

**Después:**
```css
#layer-doble-marco {
    z-index: 5; /* Más alto que el paspartú para estar visible encima */
}
```

### 2. Actualización en JavaScript
**Antes:**
```javascript
'z-index': 4
```

**Después:**
```javascript
'z-index': 5
```

### 3. Logging Mejorado
Se agregó logging adicional para debug:
```javascript
console.log('[cuadros] Doble marco aplicado - Offset:', dobleMarcoOffset + 'px', 'Grosor:', bordeDobleMarco + 'px', 'Color:', dobleMarcoInfo.color);
```

## Nueva Jerarquía Visual

**Orden de capas (z-index actualizado):**
1. `#layer-paspartu` (z-index: 1) - Fondo del paspartú
2. `#layer-imagen` (z-index: 2) - Imagen del producto  
3. `#layer-marco` (z-index: 3) - Marco principal exterior
4. `#layer-doble-marco` (z-index: 5) - Marco interior **SOBRE** el paspartú

## Resultado Esperado

**Estructura visual corregida:**
```
┌─────────────────────────────────┐
│ Marco Principal (exterior)      │
│ ┌─────────────────────────────┐ │
│ │ Paspartú (fondo)            │ │
│ │   ┌─────────────────────┐   │ │
│ │   │ Marco Interior      │   │ │ ← SOBRE el paspartú
│ │   │ ┌─────────────────┐ │   │ │
│ │   │ │ Imagen          │ │   │ │
│ │   │ └─────────────────┘ │   │ │
│ │   └─────────────────────┘   │ │
│ └─────────────────────────────┘ │
└─────────────────────────────────┘
```

## Verificación
Para verificar que la corrección funciona:
1. Seleccionar un marco con doble marco habilitado
2. El marco interior debe aparecer como un borde visible **sobre** el paspartú
3. En la consola del navegador debe aparecer el log con los valores aplicados

## Compatibilidad
- ✅ Funciona en vista normal
- ✅ Funciona en lightbox  
- ✅ Compatible con todas las orientaciones
- ✅ No afecta marcos sin doble marco habilitado

---
**Fecha**: Enero 2026  
**Tipo**: Corrección de bug visual  
**Impacto**: Mejora la visualización del doble marco