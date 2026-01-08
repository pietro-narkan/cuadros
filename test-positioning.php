<?php
/**
 * Archivo de prueba para verificar el posicionamiento de marcos
 * Ejecutar desde el navegador: /wp-content/plugins/cuadros/test-positioning.php
 */

// Cargar WordPress
require_once('../../../wp-load.php');

// Verificar si el usuario tiene permisos
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para acceder a esta página.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Posicionamiento Marcos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-container { 
            position: relative; 
            width: 400px; 
            height: 300px; 
            border: 2px solid #ccc; 
            margin: 20px 0;
            overflow: hidden;
        }
        .test-image { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }
        .test-marco { 
            position: absolute; 
            background-image: url('assets/images/marcos/oro-horizontal.png');
            background-size: 100% 100%;
            background-repeat: no-repeat;
            border: 2px solid red;
            opacity: 0.8;
        }
        .debug-info { 
            background: #f0f0f0; 
            padding: 10px; 
            margin: 10px 0; 
            font-family: monospace; 
        }
    </style>
</head>
<body>
    <h1>Test de Posicionamiento de Marcos</h1>
    
    <div class="debug-info">
        <h3>Configuración actual del plugin:</h3>
        <?php
        $settings = get_option('cuadros_settings', array());
        echo '<pre>';
        print_r($settings);
        echo '</pre>';
        ?>
    </div>
    
    <h3>Test Visual:</h3>
    <div class="test-container" id="test1">
        <img src="https://via.placeholder.com/400x300/0066cc/ffffff?text=Imagen+de+Prueba" class="test-image" alt="Test">
        <div class="test-marco" id="marco1"></div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Simular el cálculo de posicionamiento
            var $container = $('#test1');
            var $image = $container.find('img');
            var $marco = $('#marco1');
            
            function posicionarMarco() {
                var imgWidth = $image.width();
                var imgHeight = $image.height();
                
                // Usar las mismas dimensiones que el plugin (80% horizontal)
                var marcoWidthPercent = 80;
                var marcoHeightPercent = 60;
                
                var marcoWidth = (imgWidth * marcoWidthPercent) / 100;
                var marcoHeight = (imgHeight * marcoHeightPercent) / 100;
                
                var marcoLeft = (imgWidth - marcoWidth) / 2;
                var marcoTop = (imgHeight - marcoHeight) / 2;
                
                $marco.css({
                    'width': marcoWidth + 'px',
                    'height': marcoHeight + 'px',
                    'left': marcoLeft + 'px',
                    'top': marcoTop + 'px'
                });
                
                console.log('Posicionamiento calculado:', {
                    imgWidth: imgWidth,
                    imgHeight: imgHeight,
                    marcoWidth: marcoWidth,
                    marcoHeight: marcoHeight,
                    marcoLeft: marcoLeft,
                    marcoTop: marcoTop
                });
            }
            
            // Posicionar cuando la imagen se cargue
            $image.on('load', posicionarMarco);
            
            // Si ya está cargada
            if ($image[0].complete) {
                posicionarMarco();
            }
            
            // Reposicionar al cambiar tamaño
            $(window).on('resize', posicionarMarco);
        });
    </script>
</body>
</html>