<?php

$SECRET = 'sso_secret_key';

$data = array(
    'dni'       => '75504058',
    'email'     => 'rvmusic2107@gmail.com',
    'nombre'    => 'Rodrigo Alonso',
    'apellido'  => 'Vascones Portocarrero',
    'timestamp' => time()
);

$signature = hash_hmac(
    'sha256',
    $data['dni'] . $data['email'] . $data['timestamp'],
    $SECRET
);

$data['signature'] = $signature;

$token = base64_encode(json_encode($data));


$moodle_url = 'https://your-moodle-url.com/sso' . urlencode($token);

// Para debug:
echo "<pre>";
echo "TOKEN:\n" . $token . "\n\n";
echo "URL:\n" . $moodle_url;
echo "</pre>";

