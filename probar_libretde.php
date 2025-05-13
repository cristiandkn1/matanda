<?php
// LibreDTE - ejemplo real con demo
$api_key = 'demo';
$rut_emisor = '76088228-5';
$url = 'https://demo.libredte.cl/api/dte/documento';

// Armar datos de prueba
$data = [
    'dte' => [
        'Encabezado' => [
            'IdDoc' => [ 'TipoDTE' => 39 ],
            'Emisor' => [ 'RUTEmisor' => $rut_emisor ],
            'Receptor' => [
                'RUTRecep' => '66666666-6',
                'RznSocRecep' => 'Consumidor Final',
                'DirRecep' => 'Sin dirección',
                'CmnaRecep' => 'Sin comuna'
            ]
        ],
        'Detalle' => [
            [
                'NmbItem' => 'Producto de prueba',
                'QtyItem' => 1,
                'PrcItem' => 1000,
                'DescuentoMonto' => 0
            ]
        ]
    ]
];

// Enviar petición con cURL real y explícito
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $api_key,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
// Solo para prueba: ignorar validación de SSL
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "❌ Error cURL: " . curl_error($ch);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Mostrar respuesta
echo "<pre>";
print_r(json_decode($response, true));
echo "</pre>";
