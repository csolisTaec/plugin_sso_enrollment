<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');

class auth_plugin_sso_custom extends auth_plugin_base {

    public function __construct() {
        $this->authtype = 'sso_custom';
        $this->config = get_config('auth_sso_custom');
    }

    public function loginpage_hook() {
        global $DB;

        $payload = [
            'dni' => '12345678',
            'email' => 'test@mail.com',
            'nombre' => 'Juan',
            'apellido' => 'Perez'
        ];

        $username = $payload['dni'];

        $user = $DB->get_record('user', ['username' => $username]);

        if (!$user) {
            $user = new stdClass();
            $user->username = $username;
            $user->firstname = $payload['nombre'];
            $user->lastname = $payload['apellido'];
            $user->email = $payload['email'];
            $user->auth = 'sso_custom';
            $user->confirmed = 1;
            $user->mnethostid = 1;

            $user->id = user_create_user($user, false, false);
        } else {
            $user->firstname = $payload['nombre'];
            $user->lastname = $payload['apellido'];
            $user->email = $payload['email'];

            user_update_user($user, false, false);
        }

        complete_user_login($user);
        redirect(new moodle_url('/my'));
    }
}