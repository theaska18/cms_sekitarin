<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Single configured development admin identity; never provisions credentials.
 */
class Admin_identity
{
    const SESSION_KEY = 'admin_user';
    const SESSION_LIFETIME = 2592000;

    private $CI;

    public function __construct()
    {
        $this->CI = get_instance();
    }

    public function authentication_enabled()
    {
        return defined('ENVIRONMENT') && ENVIRONMENT === 'development';
    }

    public function authenticate($username, $password)
    {
        if (!$this->authentication_enabled()
            || !is_string($username)
            || !is_string($password)
            || $username === ''
            || strlen($username) > 254
            || strlen($password) > 4096
            || strpos($username, "\0") !== FALSE
            || strpos($password, "\0") !== FALSE) {
            return FALSE;
        }

        $configured_username = getenv('CMS_ADMIN_USERNAME');
        $password_hash = getenv('CMS_ADMIN_PASSWORD_HASH');
        if (!is_string($configured_username)
            || $configured_username === ''
            || !is_string($password_hash)
            || $password_hash === ''
            || !hash_equals($configured_username, $username)
            || !password_verify($password, $password_hash)) {
            return FALSE;
        }

        $display_name = getenv('CMS_ADMIN_NAME');
        if (!is_string($display_name) || trim($display_name) === '') {
            $display_name = 'Asep Kamaludin';
        }

        $user = array(
            'username' => $configured_username,
            'display_name' => trim($display_name),
            'credential_fingerprint' => hash('sha256', $password_hash),
        );

        // Rotate before writing any authenticated identity to the session.
        $this->CI->session->sess_regenerate(TRUE);
        $this->CI->session->set_userdata(self::SESSION_KEY, $user);

        return array(
            'username' => $user['username'],
            'display_name' => $user['display_name'],
        );
    }

    public function current_user()
    {
        if (!$this->authentication_enabled()) {
            return NULL;
        }

        $user = $this->CI->session->userdata(self::SESSION_KEY);
        if (!is_array($user)
            || !isset($user['username'], $user['display_name'])
            || !is_string($user['username'])
            || !is_string($user['display_name'])
            || $user['username'] === ''
            || trim($user['display_name']) === '') {
            return NULL;
        }

        $configured_username = getenv('CMS_ADMIN_USERNAME');
        $password_hash = getenv('CMS_ADMIN_PASSWORD_HASH');
        if (!is_string($configured_username) || $configured_username === ''
            || !is_string($password_hash) || $password_hash === ''
            || !hash_equals($configured_username, $user['username'])
            || !isset($user['credential_fingerprint'])
            || !is_string($user['credential_fingerprint'])
            || !hash_equals(hash('sha256', $password_hash), $user['credential_fingerprint'])) {
            return NULL;
        }

        return array(
            'username' => $user['username'],
            'display_name' => $user['display_name'],
        );
    }

    public function logout()
    {
        $cookie_name = config_item('sess_cookie_name');
        $cookie_path = config_item('cookie_path') ?: '/';
        $cookie_domain = config_item('cookie_domain') ?: '';
        $cookie_secure = (bool) config_item('cookie_secure');
        $cookie_same_site = config_item('sess_samesite') ?: 'Lax';

        $this->CI->session->sess_destroy();
        $_SESSION = array();

        if (is_string($cookie_name) && $cookie_name !== '') {
            setcookie($cookie_name, '', array(
                'expires' => time() - 3600,
                'path' => $cookie_path,
                'domain' => $cookie_domain,
                'secure' => $cookie_secure,
                'httponly' => TRUE,
                'samesite' => $cookie_same_site,
            ));
            unset($_COOKIE[$cookie_name]);
        }
    }
}
