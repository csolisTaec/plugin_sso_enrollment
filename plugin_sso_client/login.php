<?php

require('../../config.php');
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->dirroot.'/user/lib.php');

global $DB, $CFG;

function safe_string($value, $default = '') {
    return is_string($value) ? trim($value) : $default;
}

$SECRET = $CFG->sso_secret;

if (empty($SECRET)) {
    die('SSO no configurado');
}

$token = $_GET['token'] ?? null;

if (!$token) {
    die('Token requerido');
}

$payload = json_decode(base64_decode($token), true);

if (!is_array($payload)) {
    die('Token inválido');
}

if (empty($payload['timestamp']) || (time() - $payload['timestamp'] > 120)) {
    die('Token expirado');
}

$expected = hash_hmac(
    'sha256',
    ($payload['dni'] ?? '') . ($payload['email'] ?? '') . ($payload['timestamp'] ?? ''),
    $SECRET
);

if (!isset($payload['signature']) || !hash_equals($expected, $payload['signature'])) {
    die('Firma inválida');
}

$username = safe_string($payload['dni'], '');
$email = safe_string($payload['email'], '');

if (empty($username) || empty($email)) {
    die('Datos incompletos');
}

$user = $DB->get_record('user', ['username' => $username]);

if ($user && ($user->deleted || $user->suspended)) {
    die('Usuario no válido');
}

try {

    if (!$user) {

        $user = new stdClass();
        $user->auth = 'sso_custom';
        $user->confirmed = 1;
        $user->mnethostid = $CFG->mnet_localhost_id;

        $user->username = $username;
        $user->password = hash_internal_user_password('Temp1234*');

        $user->firstname = safe_string($payload['nombre'], 'Nombre');
        $user->lastname  = safe_string($payload['apellido'], 'Apellido');
        $user->email     = $email;

        $user->lang = 'es';
        $user->timezone = 'America/Mexico_City';
        $user->country = 'MX';

        $user->timecreated = time();
        $user->timemodified = time();

        $user->id = user_create_user($user, false, false);

    } else {

        if ($user->auth === 'sso_custom') {

            $user->firstname = safe_string($payload['nombre'], $user->firstname);
            $user->lastname = safe_string($payload['apellido'], $user->lastname);
            $user->email = $email;

            $user->timemodified = time();

            $update = new stdClass();
            $update->id = $user->id;

            $update->firstname = safe_string($payload['nombre'], $user->firstname);
            $update->lastname  = safe_string($payload['apellido'], $user->lastname);
            $update->email     = $email;

            user_update_user($update, false, false);
        }
    }

} catch (Exception $e) {
    echo 'Error usuario: ' . $e->getMessage();
    die();
}

$user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);

require_once($CFG->dirroot.'/user/lib.php');

if ($user->auth === 'manual') {

    set_user_preference('auth_forcepasswordchange', 0, $user);

    $update = new stdClass();
    $update->id = $user->id;
    $update->forcepasswordchange = 0;

    user_update_user($update, false, false);

    $DB->set_field('user', 'timemodified', time(), ['id' => $user->id]);
}

complete_user_login($user);

redirect(new moodle_url('/my'));