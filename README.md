## Script Cek mutasi BRI include dengan captcha solver


### REQUIREMENTS
2 tools ini harus sudah terinstall didalam host anda

1. Tesseract OCR
2. ImageMagick

Cara pakai. 

		require_once 'src/CekBRI.php';
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

eksekusi contoh diatas menggunakan php-cli agar lebih mudah.
``` $ php example.php```

lebih lengkap lihat example.php

[Butuh bantuan lain? hubungi saya via telegram](https://t.me/galihazizif)

30 Romadhan 1440