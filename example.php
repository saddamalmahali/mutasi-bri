<?php  

require_once('src/CekBRI.php');

//Contoh Penggunaan
$config = [
    'api' => [
        'username' => 'xxxxxxxxxx',
        'password' => 'yyyyyyyyyy'
    ],
    'nomor_rekening' => '00xxxxxxxxxxxxx',
    'range' => [
        'tgl_akhir_obj' => DateTime::createFromFormat('Y-m-d','2018-01-31'),
    ]
];

$bri = new CekBRI(
    $config
);

//dump hasil
print_r($bri->toArray());

?>