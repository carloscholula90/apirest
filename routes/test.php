<?php
// URL de la ruta en Laravel
$url = 'http://localhost/generate-report';

// Datos que enviarás en la solicitud
$data = [
    'report_path' => 'reporteBaseXXX.jasper',
    'params' => [
        'param1' => 'value1',
        'param2' => 'value2'
    ],
    'data' => [
        // Los datos externos para el reporte
    ],
    'format' => 'pdf'
];

// Inicializa cURL
$ch = curl_init($url);

// Configura las opciones de cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

// Ejecuta la solicitud y captura la respuesta
$response = curl_exec($ch);

// Verifica si hubo algún error
if(curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
    echo $response;
}

// Cierra la sesión de cURL
curl_close($ch);
?>
