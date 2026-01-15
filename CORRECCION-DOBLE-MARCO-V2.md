# Corrección del Sistema de Doble Marco - v1.2.1

## Problema Identificado
El doble marco se estaba renderizando **encima** del paspartú en lugar de **ocupar parte de su espacio**, como se requería. El usuario quería que el doble marco reemplazara parte del paspartú, no que se superpusiera.

## Nueva Lógica Implementada

### Concepto Clave
**Antes**: Marco Principal → Paspartú → Doble Marco (encima)  
**Ahora**: Marco Principal → Paspartú Reducido → Doble Marco (ocupa espacio del paspartú)

### Cálculo de Espacios

**Ejemplo con valores:**
- Paspartú configurado: 30px
- Doble marco configurado: 5px
- **Resultado**: Paspartú efectivo 25px + Doble marco 5px = Total 30px

**Fórmula:**
```javascript
var paspartuEfectivo = bordePaspartu - bordeDobleMarco;
var dobleMarcoInicio = bordeMarco + paspartuEfectivo;
```

## Cambios Implementados

### 1. Lógica Frontend (`includes/class-frontend.php`)

**Nueva función de aplicación:**
```javascript
if (hayPaspartu) {
    if (hayDobleMarco) {
        // Paspartú se divide: parte para paspartú + parte para doble marco
        var paspartuEfectivo = bordePaspartu - bordeDobleMarco;
        
        // Paspartú reducido
        $paspartuLayer.css({
            'position': 'absolute',
            'top': bordeMarco + 'px',
            'left': bordeMarco + 'px',
            'right': bordeMarco + 'px',
            'bottom': bordeMarco + 'px',
            'background-color': paspartuColor,
            'z-index': 1
        });
        
        // Doble marco ocupa el espacio restante
        var dobleMarcoInicio = bordeMarco + paspartuEfectivo;
        $dobleMarcoLayer.css({
            'position': 'absolute',
            'top': dobleMarcoInicio + 'px',
            'left': dobleMarcoInicio + 'px',
            'right': dobleMarcoInicio + 'px',
            'bottom': dobleMarcoInicio + 'px',
            'border': bordeDobleMarco + 'px solid ' + dobleMarcoInfo.color,
            'box-sizing': 'border-box',
            'pointer-events': 'none',
            'z-index': 3
        });
    }
}
```

### 2. Lightbox Actualizado
La misma lógica se aplica en el lightbox para mantener consistencia visual.

### 3. Interfaz de Admin Mejorada (`includes/class-admin-settings.php`)

**Descripción clara del funcionamiento:**
- Explicación visual de cómo el doble marco ocupa espacio del paspartú
- Descripciones actualizadas en cada campo
- Sección informativa sobre el comportamiento

**Nuevas descripciones:**
- "Este espacio se toma del paspartú"
- Explicación del cálculo: paspartú efectivo + doble marco = total

## Estructura Visual Resultante

```
┌─────────────────────────────────┐
│ Marco Principal (8px)           │
│ ┌─────────────────────────────┐ │
│ │ Paspartú Efectivo (25px)    │ │ ← Reducido
│ │ ┌─────────────────────────┐ │ │
│ │ │ Doble Marco (5px)       │ │ │ ← Ocupa espacio del paspartú
│ │ │ ┌─────────────────────┐ │ │ │
│ │ │ │ Imagen del Producto │ │ │ │
│ │ │ └─────────────────────┘ │ │ │
│ │ └─────────────────────────┘ │ │
│ └─────────────────────────────┘ │
└─────────────────────────────────┘
```

## Beneficios de la Nueva Implementación

### 1. **Espacio Optimizado**
- El doble marco no añade grosor extra al conjunto
- El espacio total sigue siendo: marco + paspartú configurado

### 2. **Control Granular**
- Cada orientación tiene su propio grosor de doble marco
- Configuración independiente por modelo de marco

### 3. **Efecto Visual Correcto**
- El doble marco actúa como separador entre paspartú e imagen
- Crea profundidad visual sin aumentar el tamaño total

### 4. **Flexibilidad**
- Si doble marco = 0px, funciona como antes
- Si doble marco > paspartú, se ajusta automáticamente

## Configuración Recomendada

### Proporciones Típicas
- **Paspartú**: 20-30px
- **Doble Marco**: 15-25% del paspartú (3-8px)

### Ejemplos Prácticos
1. **Elegante**: Paspartú 30px, Doble marco 5px
2. **Sutil**: Paspartú 25px, Doble marco 3px  
3. **Pronunciado**: Paspartú 35px, Doble marco 8px

## Logging de Debug

El sistema ahora registra:
```
[cuadros] Doble marco aplicado - Paspartú efectivo: 25px, Doble marco inicio: 33px, Grosor: 5px
```

Esto permite verificar que los cálculos sean correctos.

## Compatibilidad

- ✅ **Retrocompatible**: Marcos sin doble marco funcionan igual
- ✅ **Responsive**: Funciona en desktop y mobile
- ✅ **Lightbox**: Misma lógica en zoom
- ✅ **Orientaciones**: Soporte completo para vertical/horizontal/cuadrado

---

**Versión**: 1.2.1  
**Fecha**: Enero 2026  
**Tipo**: Corrección de lógica de posicionamiento  
**Resultado**: Doble marco ocupa espacio del paspartú correctamente