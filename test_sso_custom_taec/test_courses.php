<?php

$SECRET = 'wh_en_in_doubt_use_a_long_and_random_string_as_secret_key';

$data = [
    'dni' => 'csolis@taec.com.mx',
    'timestamp' => time()
];

$data['signature'] = hash_hmac(
    'sha256',
    $data['dni'] . $data['timestamp'],
    $SECRET
);

$url = 'https://devmoodle.preon.com.mx/auth/sso_custom/courses.php';

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

curl_close($ch);

echo $response;