<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Authenticates an administrator against the tenant-scoped application user table. */
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
        return TRUE;
    }

    public function authenticate($username, $password)
    {
        if (!is_string($username) || !is_string($password)
            || $username === '' || strlen($username) > 254 || strlen($password) > 4096
            || strpos($username, "\0") !== FALSE || strpos($password, "\0") !== FALSE) {
            return FALSE;
        }

        $db = $this->CI->load->database('default', TRUE);
        $select = array('u.user_id', 'u.username', 'u.tenant_id', 'u.full_name', 'u.role_id', 'u.password', 'u.theme');
        if ($db->field_exists('chat_flag', 'app_user')) {
            $select[] = 'u.chat_flag';
        }
        if ($db->field_exists('employee_id', 'app_user')) {
            $select[] = 'u.employee_id';
        }
        $query = $db->select(implode(', ', $select))
            ->from('app_user u')
            ->join('app_role r', 'r.role_id = u.role_id AND r.tenant_id = u.tenant_id', 'inner')
            ->where('u.username', $username)
            ->where('u.block_flag', 0)
            ->where('r.active_flag', 1)
            ->limit(2)
            ->get();
        if ($query === FALSE || $query->num_rows() !== 1) {
            return FALSE;
        }

        $row = $query->row_array();
        if (!isset($row['password'], $row['username'], $row['full_name'], $row['tenant_id'], $row['role_id'])
            || !password_verify($password, (string) $row['password'])) {
            return FALSE;
        }
        $tenant_id = (int) $row['tenant_id'];
        $role_id = (int) $row['role_id'];
        if ($tenant_id < 1 || $role_id < 1 || trim((string) $row['username']) === '' || trim((string) $row['full_name']) === '') {
            return FALSE;
        }

        $theme = isset($row['theme']) ? strtolower(trim((string) $row['theme'])) : '';
        if (!in_array($theme, array('dark', 'light'), TRUE)) {
            $theme = 'light';
        }
        $user = array(
            'username' => (string) $row['username'],
            'user_id' => (int) $row['user_id'],
            'tenant_id' => $tenant_id,
            'role_id' => $role_id,
            'full_name' => trim((string) $row['full_name']),
            'display_name' => trim((string) $row['full_name']),
            'theme' => $theme,
            'chat_flag' => isset($row['chat_flag']) && in_array((string) $row['chat_flag'], array('1', 'true', 'TRUE'), TRUE),
        );
        if (isset($row['employee_id']) && (int) $row['employee_id'] > 0) {
            $user['employee_id'] = (int) $row['employee_id'];
            $image = $this->employee_image($user['employee_id'], $tenant_id);
            if ($image !== NULL) {
                $user['profile_image'] = $image;
            }
        }

        $this->CI->session->sess_regenerate(TRUE);
        $this->CI->session->set_userdata(self::SESSION_KEY, $user);

        return $user;
    }

    public function current_user()
    {
        $user = $this->CI->session->userdata(self::SESSION_KEY);
        if (!is_array($user)
            || !isset($user['username'], $user['user_id'], $user['tenant_id'], $user['role_id'], $user['full_name'])
            || !is_string($user['username']) || !is_string($user['full_name'])
            || !is_numeric($user['user_id']) || (int) $user['user_id'] < 1
            || $user['username'] === '' || trim($user['full_name']) === ''
            || !is_numeric($user['tenant_id']) || (int) $user['tenant_id'] < 1
            || !is_numeric($user['role_id']) || (int) $user['role_id'] < 1) {
            return NULL;
        }
        $user['display_name'] = $user['full_name'];
        return $user;
    }

    private function employee_image($employee_id, $tenant_id)
    {
        $db = $this->CI->load->database('default', TRUE);
        $fields = $db->field_data('hr_employee');
        $has_image = FALSE;
        foreach ((array) $fields as $field) {
            if (isset($field->name) && $field->name === 'image') {
                $has_image = TRUE;
                break;
            }
        }
        if (!$has_image) {
            return NULL;
        }
        $query = $db->select('image')->from('hr_employee')
            ->where('employee_id', (int) $employee_id)->where('tenant_id', (int) $tenant_id)
            ->limit(2)->get();
        if ($query === FALSE || $query->num_rows() !== 1) {
            return NULL;
        }
        $filename = trim((string) $query->row()->image);
        if (!preg_match('/\\A[A-Za-z0-9_-]+\\.(?:jpe?g|png|gif|webp)\\z/i', $filename)) {
            return NULL;
        }

        // Employee uploads are private files under application/cache, not the public web root.
        $image_directory = realpath(APPPATH . 'cache/employee-images');
        $file = realpath(APPPATH . 'cache/employee-images/' . $filename);
        if ($image_directory === FALSE || $file === FALSE || !is_file($file)
            || strpos($file, $image_directory . DIRECTORY_SEPARATOR) !== 0) {
            return NULL;
        }

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($extension, array('jpg', 'jpeg', 'png', 'gif', 'webp'), TRUE)) {
            return NULL;
        }
        return site_url('admin/hr/employee/profile_photo/' . rawurlencode($filename));
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
