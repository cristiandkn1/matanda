<?php
require_once __DIR__ . '/src/ApiClient.php';
require_once __DIR__ . '/src/HttpCurlClient.php';

// Usamos el namespace definido
use libredte\api_client\ApiClient;
use libredte\api_client\HttpCurlClient;

// ⚙️ Credenciales de test
$empresa_rut = '20152517-9';
$apikey = 'WDpUaWxpY0VFVTd0Uk04NXVsWVpqYjA4bDdMeW5IV1J0SA==';

$client = new ApiClient($empresa_rut); // HttpCurlClient se crea por defecto en constructor

// 📋 Consulta de boletas emitidas
$response = $client->get('/dte/dte_emitido/listar', [
    'tipo' => 39, // boleta
    'rut_receptor' => '',
    'estado' => '',
    'fecha_emision_d' => '2024-01-01',
    'fecha_emision_h' => date('Y-m-d')
]);

if ($response && $response['status'] === true) {
    echo "✅ Documentos encontrados:\n";
    foreach ($response['datos'] as $dte) {
        echo "- Folio: {$dte['folio']} | Fecha: {$dte['fch']} | Monto: {$dte['mnt_total']}\n";
    }
} else {
    echo "❌ Error al consultar: " . ($response['mensaje'] ?? 'Desconocido') . "\n";
}
