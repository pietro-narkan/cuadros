# Implementación de Orientación 1:1 (Cuadrado)

## Cambios Realizados

Se ha agregado soporte para marcos con orientación cuadrada (1:1) al plugin Cuadros de WordPress.

### 1. Modal de Admin (`assets/js/admin.js`)
- Agregada opción "Cuadrado (1:1)" al selector de orientación en el modal de subida de marcos

### 2. Detección de Orientación (`includes/class-frontend.php`)
- Modificada función `obtenerOrientacion()` para detectar cuando ancho === alto y retornar '1:1'
- El sistema ahora reconoce automáticamente productos con dimensiones cuadradas (ej: 800x800)

### 3. Configuración de Admin (`includes/class-admin-settings.php`)
- Agregado campo de configuración para dimensiones cuadradas en el panel de administración
- Agregada sanitización para los valores de dimensiones 1:1
- Valores por defecto: 80% ancho x 80% alto

### 4. Inicialización (`cuadros.php`)
- Agregadas dimensiones por defecto para orientación 1:1 en la activación del plugin

## Cómo Funciona

1. **Subida de Marco**: El administrador puede subir marcos seleccionando orientación "Cuadrado (1:1)"
2. **Detección Automática**: Cuando un producto tiene dimensiones cuadradas (ej: 500x500), el sistema detecta automáticamente orientación '1:1'
3. **Búsqueda de Marco**: El sistema busca marcos que coincidan con el modelo del producto y orientación '1:1'
4. **Aplicación**: Se aplican las dimensiones configuradas para formato cuadrado (por defecto 80% x 80%)

## Configuración

Las dimensiones para marcos cuadrados se pueden configurar en:
**WordPress Admin > Cuadros > Dimensiones Cuadradas (1:1)**

Valores recomendados: 80% ancho x 80% alto