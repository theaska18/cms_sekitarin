<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller
{
    protected $current_user;
    private $admin_menu_groups_loaded = FALSE;
    private $admin_menu_groups_cache = array();

    private $admin_languages = array(
        'english' => array('label' => 'English', 'flag' => '🇬🇧', 'html_lang' => 'en', 'direction' => 'ltr'),
        'indonesian' => array('label' => 'Bahasa Indonesia', 'flag' => '🇮🇩', 'html_lang' => 'id', 'direction' => 'ltr'),
        'japanese' => array('label' => '日本語', 'flag' => '🇯🇵', 'html_lang' => 'ja', 'direction' => 'ltr'),
        'chinese' => array('label' => '中文（简体）', 'flag' => '🇨🇳', 'html_lang' => 'zh-CN', 'direction' => 'ltr'),
        'arabic' => array('label' => 'العربية', 'flag' => '🇸🇦', 'html_lang' => 'ar', 'direction' => 'rtl'),
        'korean' => array('label' => '한국어', 'flag' => '🇰🇷', 'html_lang' => 'ko', 'direction' => 'ltr'),
        'spanish' => array('label' => 'Español', 'flag' => '🇪🇸', 'html_lang' => 'es', 'direction' => 'ltr'),
        'french' => array('label' => 'Français', 'flag' => '🇫🇷', 'html_lang' => 'fr', 'direction' => 'ltr'),
        'german' => array('label' => 'Deutsch', 'flag' => '🇩🇪', 'html_lang' => 'de', 'direction' => 'ltr'),
    );

    public function __construct()
    {
        parent::__construct();
        $this->load->library('admin_identity');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        $this->output->set_header('Pragma: no-cache');
        $this->current_user = $this->admin_identity->current_user();
        if ($this->current_user === NULL) {
            header('Location: /login', TRUE, 303);
            exit;
        }
    }

    public function index()
    {
        $this->render_admin(
            'admin/dashboard',
            'dashboard',
            '/admin',
            array('page_slug' => 'dashboard')
        );
    }

    public function setting()
    {
        $db = $this->load->database('default', TRUE);
        $locale = $this->resolve_admin_locale();
        if ($this->input->method(TRUE) === 'POST') {
            $requested_locale = (string) $this->input->post('locale', TRUE);
            $requested_theme = $this->input->post('theme', TRUE);
            $locale_valid = $requested_locale !== '' && isset($this->admin_languages[$requested_locale]);
            $theme_valid = $requested_theme !== NULL
                && in_array(strtolower(trim((string) $requested_theme)), array('dark', 'light'), TRUE);
            $has_valid_setting = $locale_valid || $theme_valid;
            if ($has_valid_setting && ($requested_locale === '' || $locale_valid) && ($requested_theme === NULL || $theme_valid)) {
                $update = array();
                if ($locale_valid) {
                    $update['language'] = $requested_locale;
                }
                if ($requested_theme !== NULL) {
                    $update['theme'] = strtolower(trim((string) $requested_theme));
                    $requested_theme = $update['theme'];
                }
                if (!empty($update)) {
                    $db->where('user_id', (int) $this->current_user['user_id'])
                        ->where('tenant_id', (int) $this->current_user['tenant_id'])
                        ->update('app_user', $update);
                }
                $db_error = $db->error();
                if ($db->trans_status() === FALSE || (is_array($db_error) && !empty($db_error['code']))) {
                    $this->session->set_flashdata('setting_notice', 'invalid');
                } else {
                    if (isset($update['theme'])) {
                        $this->current_user['theme'] = $update['theme'];
                    }
                    if (isset($update['language'])) {
                        $this->current_user['language'] = $update['language'];
                    }
                    $this->session->set_userdata('admin_user', $this->current_user);
                    $this->session->set_flashdata('setting_notice', 'saved');
                }
            } else {
                $this->session->set_flashdata('setting_notice', 'invalid');
            }
            redirect('/admin/setting');
            return;
        }

        $theme_query = $db->select('theme')->from('app_user')
            ->where('user_id', (int) $this->current_user['user_id'])
            ->where('tenant_id', (int) $this->current_user['tenant_id'])->limit(1)->get();
        $theme = $theme_query !== FALSE && $theme_query->num_rows() === 1
            ? strtolower(trim((string) $theme_query->row()->theme))
            : '';
        if (!in_array($theme, array('dark', 'light'), TRUE)) {
            $theme = 'light';
        }
        $this->current_user['theme'] = $theme;
        $this->current_user['language'] = $locale;
        $this->session->set_userdata('admin_user', $this->current_user);
        $notice = (string) $this->session->flashdata('setting_notice');
        $this->render_admin(
            'admin/setting',
            'settings',
            '/admin/setting',
            array(
                'setting_locale' => $locale,
                'setting_theme' => $theme,
                'admin_languages' => $this->admin_languages,
                'setting_notice' => $notice,
            )
        );
    }

    public function profile()
    {
        $db = $this->load->database('default', TRUE);
        $user_id = (int) $this->current_user['user_id'];
        $tenant_id = (int) $this->current_user['tenant_id'];
        $user_query = $db->select('user_id, tenant_id, username, full_name, password')
            ->from('app_user')->where('user_id', $user_id)->where('tenant_id', $tenant_id)->limit(2)->get();
        if ($user_query === FALSE || $user_query->num_rows() !== 1) {
            show_error('Profile is unavailable.', 503);
            return;
        }
        $record = $user_query->row_array();
        if (!is_array($record)) { show_error('Profile record is unavailable.', 404); return; }
        $record['employee_id'] = isset($this->current_user['employee_id']) ? (int) $this->current_user['employee_id'] : 0;
        $this->load->library('employee_store');
        $employee_store = $this->employee_store;
        $profile_employee = $record['employee_id'] > 0 && $employee_store->schema_available()
            ? $employee_store->find($record['employee_id'], $tenant_id) : NULL;
        if (!is_array($profile_employee)) { $profile_employee = NULL; }
        $has_employee = $profile_employee !== NULL;
        $errors = array();
        $notice = (string) $this->session->flashdata('profile_notice');

        if ($this->input->method(TRUE) === 'POST') {
            if (!$this->config->item('csrf_protection')) { show_error('Profile updates are unavailable.', 503); return; }
            $action = (string) $this->input->post('action', TRUE);
            if ($action === 'profile') {
                $username = trim((string) $this->input->post('username', TRUE));
                $full_name = trim((string) $this->input->post('full_name', TRUE));
                if ($username === '' || strlen($username) > 100 || !preg_match('/\\A[A-Za-z0-9._@+-]+\\z/', $username)) { $errors[] = 'username'; }
                else {
                    $duplicate = $db->from('app_user')->where('username', $username)->where('user_id !=', $user_id)->limit(1)->count_all_results();
                    if ($duplicate > 0) { $errors[] = 'username_taken'; }
                }
                if ($full_name === '' || strlen($full_name) > 150) { $errors[] = 'full_name'; }
                if (!$errors) {
                    $db->where('user_id', $user_id)->where('tenant_id', $tenant_id)
                        ->update('app_user', array('username' => $username, 'full_name' => $full_name, 'update_on' => date('Y-m-d H:i:s')));
                    if ($db->trans_status() !== FALSE) {
                        $this->current_user['username'] = $username;
                        $this->current_user['full_name'] = $full_name;
                        $this->current_user['display_name'] = $full_name;
                        $this->session->set_userdata('admin_user', $this->current_user);
                        $this->session->set_flashdata('profile_notice', 'saved');
                        redirect('/admin/profile', 'location', 303); return;
                    }
                    $errors[] = 'save';
                }
                $record['username'] = $username;
                $record['full_name'] = $full_name;
            } elseif ($action === 'password') {
                $current = $this->input->post('current_password', FALSE);
                $password = $this->input->post('new_password', FALSE);
                $confirm = $this->input->post('confirm_password', FALSE);
                $current = is_string($current) ? $current : '';
                $password = is_string($password) ? $password : '';
                $confirm = is_string($confirm) ? $confirm : '';
                if (!password_verify($current, (string) $record['password'])) { $errors[] = 'current_password'; }
                if (strlen($password) < 8 || strlen($password) > 4096) { $errors[] = 'password'; }
                if ($password !== $confirm) { $errors[] = 'password_mismatch'; }
                if (!$errors) {
                    $db->where('user_id', $user_id)->where('tenant_id', $tenant_id)
                        ->update('app_user', array('password' => password_hash($password, PASSWORD_DEFAULT), 'update_on' => date('Y-m-d H:i:s')));
                    if ($db->trans_status() !== FALSE) {
                        $this->session->set_flashdata('profile_notice', 'password_saved');
                        redirect('/admin/profile', 'location', 303); return;
                    }
                    $errors[] = 'save';
                }
            } elseif ($action === 'photo' && !$has_employee) {
                show_404(); return;
            } elseif ($action === 'photo') {
                $image_dir = APPPATH . 'cache/employee-images';
                if (!is_dir($image_dir) && !@mkdir($image_dir, 0700, TRUE) && !is_dir($image_dir)) { $errors[] = 'photo_unavailable'; }
                else {
                    $this->load->library('upload');
                    $config = array('upload_path' => $image_dir, 'allowed_types' => 'jpg|jpeg|png|webp', 'max_size' => 5120,
                        'encrypt_name' => TRUE, 'file_ext_tolower' => TRUE, 'max_width' => 4096, 'max_height' => 4096);
                    $this->upload->initialize($config, TRUE);
                    if (!$this->upload->do_upload('profile_photo')) { $errors[] = 'photo_invalid'; }
                    else {
                        $upload = $this->upload->data();
                        $filename = basename($upload['file_name']);
                        $employee_record = array(
                            'employee_code' => $profile_employee['employee_code'],
                            'identity_id' => $profile_employee['identity_id'],
                            'employee_name' => $profile_employee['employee_name'],
                            'email_address' => $profile_employee['email_address'],
                            'gender' => $profile_employee['gender'],
                            'birth_date' => $profile_employee['birth_date'],
                            'birth_place' => $profile_employee['birth_place'],
                            'image' => $filename,
                        );
                        $old_filename = isset($profile_employee['image']) ? (string) $profile_employee['image'] : '';
                        $updated = $employee_store->update($record['employee_id'], $tenant_id, $employee_record);
                        if ($updated) {
                            if ($old_filename !== '' && preg_match('/\\A[A-Za-z0-9_-]+\\.(?:jpe?g|png|webp)\\z/i', $old_filename)
                                && is_file($image_dir . DIRECTORY_SEPARATOR . $old_filename)) { @unlink($image_dir . DIRECTORY_SEPARATOR . $old_filename); }
                            $this->session->set_flashdata('profile_notice', 'photo_saved');
                            redirect('/admin/profile', 'location', 303); return;
                        }
                        @unlink($image_dir . DIRECTORY_SEPARATOR . $filename);
                        $errors[] = 'save';
                    }
                }
            } else { $errors[] = 'action'; }
        }

        $filename = $has_employee && isset($profile_employee['image']) ? (string) $profile_employee['image'] : '';
        if ($filename !== '' && preg_match('/\\A[A-Za-z0-9_-]+\\.(?:jpe?g|png|webp)\\z/i', $filename)
            && $employee_store) {
            $photo_path = $employee_store->image_path($filename);
            if ($photo_path && is_file($photo_path)) {
                $profile_image = site_url('admin/hr/employee/profile_photo/' . rawurlencode($filename));
            } else { $profile_image = '/assets/images/avatar-placeholder.svg'; }
        } else { $profile_image = '/assets/images/avatar-placeholder.svg'; }
        $employee_name = $has_employee ? (string) $profile_employee['employee_name'] : NULL;
        $this->render_admin('admin/profile', 'profile', '/admin/profile', array(
            'profile_record' => $record, 'profile_errors' => $errors, 'profile_notice' => $notice,
            'profile_image' => $profile_image,
            'profile_employee_name' => $employee_name,
            'has_employee' => $has_employee, 'profile_employee' => $profile_employee,
            'page_slug' => 'profile',
        ));
    }

    public function placeholder($section, $slug)
    {
        $path = $section . '/' . $slug;
        $title_key = strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', (string) $slug));
        if (!preg_match('/\\A[a-z][a-z0-9_]*\\z/', $title_key)) {
            show_404();
            return;
        }

        $this->render_admin('admin/placeholder', $title_key, '/' . $path);
    }

    public function module($section, $slug = '')
    {
        $requested_path = '/' . trim($this->uri->uri_string(), '/');
        $module = $this->module_from_menu_manifest($requested_path);
        if ($module === NULL) {
            show_404();
            return;
        }

        if (isset($module['menu_code']) && !$this->has_admin_menu_code($module['menu_code'])) {
            $this->output->set_status_header(403);
            show_error('You do not have permission to access this module.', 403);
            return;
        }
        $this->render_admin(
            'admin/module',
            $module['key'],
            $requested_path,
            array(
                'module' => $module,
                'requested_path' => $requested_path,
            )
        );
    }

    /** Resolve generic read-only module pages from the menu manifest. */
    private function module_from_menu_manifest($requested_path)
    {
        $requested_path = '/' . trim((string) $requested_path, '/');
        $admin_path = strpos($requested_path, '/admin/') === 0
            ? $requested_path
            : '/admin' . $requested_path;
        $candidates = array($requested_path, $admin_path);
        if (strpos($admin_path, '/admin/app/') === 0) {
            $candidates[] = '/admin/application/' . substr($admin_path, strlen('/admin/app/'));
        }
        $this->load->library('admin_menu_manifest');
        $groups = $this->admin_menu_manifest->load(APPPATH . 'views/admin/menu_manifest/manifest.xml');
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                if (!in_array($item['route'], $candidates, TRUE) || empty($item['module_key'])) {
                    continue;
                }
                return array(
                    'key' => $item['module_key'],
                    'group_key' => $group['label_key'],
                    'description_key' => $item['module_description_key'],
                    'focus_key' => $item['module_focus_key'],
                    'menu_code' => $item['code'],
                );
            }
        }
        return NULL;
    }



    private function resolve_admin_locale()
    {
        $requested_locale = isset($_POST['locale']) ? trim((string) $_POST['locale']) : '';
        if (isset($_SERVER['REQUEST_METHOD']) && strtoupper((string) $_SERVER['REQUEST_METHOD']) === 'POST' && isset($this->admin_languages[$requested_locale])) {
            return $requested_locale;
        }
        $db = $this->load->database('default', TRUE);
        $query = $db->select('language')->from('app_user')
            ->where('user_id', (int) $this->current_user['user_id'])
            ->where('tenant_id', (int) $this->current_user['tenant_id'])
            ->limit(1)->get();
        if ($query !== FALSE && $query->num_rows() === 1) {
            $language = strtolower(trim((string) $query->row()->language));
            $locale_map = array('en' => 'english', 'id' => 'indonesian');
            if (isset($locale_map[$language])) {
                $language = $locale_map[$language];
            }
            if (isset($this->admin_languages[$language])) {
                return $language;
            }
        }

        $requested_locale = $this->input->post('locale', TRUE);
        if (is_string($requested_locale) && isset($this->admin_languages[$requested_locale])) {
            return $requested_locale;
        }

        $accept_language = strtolower((string) $this->input->server('HTTP_ACCEPT_LANGUAGE'));
        $available_by_browser = array();
        foreach ($this->admin_languages as $code => $language) {
            $available_by_browser[strtolower(substr($language['html_lang'], 0, 2))] = $code;
        }
        foreach (explode(',', $accept_language) as $browser_locale) {
            $browser_locale = substr(trim($browser_locale), 0, 2);
            if ($browser_locale !== '' && isset($available_by_browser[$browser_locale])) {
                return $available_by_browser[$browser_locale];
            }
        }

        return 'english';
    }

    private function load_admin_menu_groups()
    {
        if ($this->admin_menu_groups_loaded) {
            return $this->admin_menu_groups_cache;
        }

        $this->load->library('admin_menu_manifest');
        $groups = $this->admin_menu_manifest->load(APPPATH . 'views/admin/menu_manifest/manifest.xml');
        $this->load->database();
        $this->load->library('tenant_menu_access_store');
        $this->load->library('role_menu_access_store');
        $tenant_state = $this->tenant_menu_access_store->state($this->current_user['tenant_id']);
        $role_state = $this->role_menu_access_store->state($this->current_user['role_id']);
        $permissions_available = is_array($tenant_state) && is_array($role_state);
        $allowed_menus = array();
        $allowed_access = array();
        $render_groups = array();

        foreach ($groups as $group) {
            $group_key = $group['label_key'];
            $group_translation = preg_match('/^admin_[a-z0-9_]+$/', $group_key)
                ? $this->lang->line($group_key)
                : FALSE;
            $group_fallback = $group['name'] !== '' ? $group['name'] : $group['code'];
            $group['label'] = $this->admin_menu_manifest->resolve_label($group_translation, $group_fallback);

            $render_items = array();
            foreach ($group['items'] as $item) {
                $menu_code = (string) $item['code'];
                if (!$permissions_available || !isset($tenant_state[$menu_code], $role_state[$menu_code])) {
                    continue;
                }
                if (strtoupper(trim($menu_code)) === 'DASHBOARD' || $item['route'] === '/admin') {
                    continue;
                }

                $item_accesses = array();
                foreach ($item['accesses'] as $access) {
                    $access_code = (string) $access['access_code'];
                    if (isset($tenant_state[$menu_code][$access_code], $role_state[$menu_code][$access_code])) {
                        $item_accesses[] = $access;
                        $allowed_access[$access_code] = TRUE;
                    }
                }
                $item['accesses'] = $item_accesses;
                $allowed_menus[$menu_code] = TRUE;

                $item_key = $item['label_key'];
                $item_translation = preg_match('/^admin_[a-z0-9_]+$/', $item_key)
                    ? $this->lang->line($item_key)
                    : FALSE;
                $item_fallback = $item['name'] !== '' ? $item['name'] : $item['code'];
                $item['label'] = $this->admin_menu_manifest->resolve_label($item_translation, $item_fallback);
                $render_items[] = $item;
            }

            if (count($render_items) === 0) {
                continue;
            }
            $group['items'] = $render_items;
            $render_groups[] = $group;
        }

        $this->current_user['menu_codes'] = array_keys($allowed_menus);
        $this->current_user['access_codes'] = array_keys($allowed_access);
        $this->session->set_userdata('admin_user', $this->current_user);

        $session_user = $this->session->userdata('admin_user');
        $session_menu_codes = array();
        if (is_array($session_user) && isset($session_user['menu_codes']) && is_array($session_user['menu_codes'])) {
            foreach ($session_user['menu_codes'] as $session_menu_code) {
                if (is_string($session_menu_code) && $session_menu_code !== '') {
                    $session_menu_codes[$session_menu_code] = TRUE;
                }
            }
        }

        $session_render_groups = array();
        foreach ($render_groups as $group) {
            $session_render_items = array();
            foreach ($group['items'] as $item) {
                $menu_code = isset($item['code']) ? (string) $item['code'] : '';
                if ($menu_code !== '' && isset($session_menu_codes[$menu_code])) {
                    $session_render_items[] = $item;
                }
            }
            if (count($session_render_items) > 0) {
                $group['items'] = $session_render_items;
                $session_render_groups[] = $group;
            }
        }

        $render_groups = $session_render_groups;
        $this->admin_menu_groups_cache = $render_groups;
        $this->admin_menu_groups_loaded = TRUE;
        return $render_groups;
    }

    /** Persist a visit only for the exact base route of an authorized menu. */
    protected function record_admin_menu_visit($current_path)
    {
        if (!is_string($current_path) || $current_path === ''
            || $current_path === '/' || $current_path === '/admin') {
            return;
        }
        $this->load->library('admin_menu_analytics');
        $path = $this->admin_menu_analytics->normalize_path($current_path);
        if ($path === '' || $path === '/' || $path === '/admin') {
            return;
        }

        // Resolve the base route from the complete manifest, independently of
        // the filtered menu used for rendering. Permission is still checked
        // against the caller's effective menu_codes before recording.
        $this->load->library('admin_menu_manifest');
        $manifest_groups = $this->admin_menu_manifest->load(APPPATH . 'views/admin/menu_manifest/manifest.xml');
        foreach ($manifest_groups as $group) {
            foreach ($group['items'] as $item) {
                if (!empty($item['route'])
                    && $path === $this->admin_menu_analytics->normalize_path($item['route'])
                    && !empty($item['code']) && $this->has_admin_menu_code($item['code'])) {
                    $this->admin_menu_analytics->record_visit(
                        isset($this->current_user['tenant_id']) ? (int) $this->current_user['tenant_id'] : 0,
                        isset($this->current_user['user_id']) ? (int) $this->current_user['user_id'] : 0,
                        (string) $item['code']
                    );
                    return;
                }
            }
        }
    }

    protected function has_admin_menu_code($menu_code)
    {
        $this->load_admin_menu_groups();
        $menu_codes = isset($this->current_user['menu_codes']) && is_array($this->current_user['menu_codes'])
            ? $this->current_user['menu_codes']
            : array();
        return in_array((string) $menu_code, $menu_codes, TRUE);
    }

    protected function has_admin_access_code($access_code)
    {
        $this->load_admin_menu_groups();
        $access_codes = isset($this->current_user['access_codes']) && is_array($this->current_user['access_codes'])
            ? $this->current_user['access_codes']
            : array();
        return in_array((string) $access_code, $access_codes, TRUE);
    }

    protected function render_admin($view, $title_key, $current_path, $extra_data = array())
    {
        $locale = $this->resolve_admin_locale();
        if ($this->input->get('lang', TRUE) !== NULL) {
            $url_locale = (string) $this->input->get('lang', TRUE);
            if (isset($this->admin_languages[$url_locale])) {
                $current_user = $this->session->userdata('admin_user');
                if (is_array($current_user)) {
                    $current_user['language'] = $url_locale;
                    $this->current_user = $current_user;
                    $this->session->set_userdata('admin_user', $current_user);
                }
                $locale = $url_locale;
            }
        }
        $this->load->language('admin', $locale);
        $this->lang->load('admin', $locale);
        $view_parts = explode('/', trim($view, '/'));
        $view_language_pack = isset($view_parts[1]) ? $view_parts[1] : '';
        if (count($view_parts) === 2 && $view_parts[1] === 'profile') {
            $view_language_pack = 'profile';
        }
        if ($view_language_pack !== '' && preg_match('/^[a-z_]+$/', $view_language_pack)
            && is_file(APPPATH . 'language/' . $locale . '/' . $view_language_pack . '_lang.php')) {
            $this->lang->load($view_language_pack, $locale);
        }
        $this->lang->load('notifications', $locale);
        $labels = array();
        foreach ($this->lang->language as $key => $value) {
            $labels[$key] = $value;
            if (strpos($key, 'admin_') === 0) {
                $labels[substr($key, 6)] = $value;
            }
        }
        if (isset($labels['notifications_heading'])) {
            $labels['admin_notifications_heading'] = $labels['notifications_heading'];
        }
        if (!isset($labels['user_chat_flag']) || $labels['user_chat_flag'] === FALSE || $labels['user_chat_flag'] === '') {
            $labels['user_chat_flag'] = 'Enable chat';
        }
        foreach (array('admin_' . $title_key, 'admin_profile') as $label_key) {
            if (!isset($labels[$label_key])) {
                $labels[$label_key] = ($label_key === 'admin_profile') ? 'Profile' : 'Dashboard';
            }
        }
        if (!isset($labels['admin_profile'])) {
            $labels['admin_profile'] = 'Profile';
        }
        $menu_groups = $this->load_admin_menu_groups();
        $popular_menu_items = array();
        if ($view === 'admin/dashboard') {
            $this->load->library('admin_menu_analytics');
            $menu_lookup = array();
            $allowed_codes = array();
            foreach ($menu_groups as $group) {
                foreach ($group['items'] as $item) {
                    if (isset($item['code']) && isset($item['label']) && isset($item['route'])) {
                        $menu_lookup[$item['code']] = $item;
                        $allowed_codes[] = $item['code'];
                    }
                }
            }
            $visit_rows = $this->admin_menu_analytics->popular_menus(
                isset($this->current_user['tenant_id']) ? (int) $this->current_user['tenant_id'] : 0,
                $allowed_codes,
                5
            );
            foreach ($visit_rows as $visit_row) {
                $code = isset($visit_row['menu_code']) ? (string) $visit_row['menu_code'] : '';
                if (isset($menu_lookup[$code])) {
                    $popular_menu_items[] = array(
                        'label' => $menu_lookup[$code]['label'],
                        'route' => $menu_lookup[$code]['route'],
                        'visit_count' => (int) $visit_row['visit_count'],
                    );
                }
            }
        }
        $notification_count = 0;
        $notification_items = array();
        $notification_user_id = isset($this->current_user['user_id']) ? (int) $this->current_user['user_id'] : 0;
        if ($notification_user_id > 0) {
            $notification_db = $this->load->database('default', TRUE);
            if ($notification_db && $notification_db->table_exists('app_notification')
                && $notification_db->field_exists('user_id', 'app_notification')
                && $notification_db->field_exists('notification_id', 'app_notification')
                && $notification_db->field_exists('title', 'app_notification')
                && $notification_db->field_exists('message', 'app_notification')) {
                if ($notification_db->field_exists('view_flag', 'app_notification')) {
                    $count_result = $notification_db->from('app_notification')
                        ->where('user_id', $notification_user_id)
                        ->where('view_flag', 0)
                        ->count_all_results();
                    if ($count_result !== FALSE) {
                        $notification_count = (int) $count_result;
                    }
                }

                $notification_select = array('notification_id', 'title', 'message');
                $notification_order_field = NULL;
                foreach (array('create_on', 'created_at', 'created_date', 'date_created') as $date_field) {
                    if ($notification_db->field_exists($date_field, 'app_notification')) {
                        $notification_select[] = $date_field;
                        $notification_order_field = $date_field;
                        break;
                    }
                }
                if ($notification_db->field_exists('view_flag', 'app_notification')) {
                    $notification_select[] = 'view_flag';
                }
                $notification_read_field = $notification_db->field_exists('read_flag', 'app_notification');
                if ($notification_read_field) {
                    $notification_select[] = 'read_flag';
                }
                $notification_query = $notification_db->select(implode(', ', $notification_select), FALSE)
                    ->from('app_notification')
                    ->where('user_id', $notification_user_id);
                if ($notification_order_field !== NULL) {
                    $notification_query->order_by($notification_order_field, 'DESC');
                }
                $notification_result = $notification_query->order_by('notification_id', 'DESC')
                    ->limit(5)
                    ->get();
                if ($notification_result !== FALSE) {
                    foreach ($notification_result->result_array() as $notification_row) {
                        $notification_items[] = array(
                            'id' => (int) $notification_row['notification_id'],
                            'title' => (string) $notification_row['title'],
                            'message' => (string) $notification_row['message'],
                            'created_at' => $notification_order_field !== NULL && isset($notification_row[$notification_order_field]) ? (string) $notification_row[$notification_order_field] : '',
                            'view_flag' => isset($notification_row['view_flag']) ? (string) $notification_row['view_flag'] : '1',
                            'read_flag' => $notification_read_field && isset($notification_row['read_flag']) ? (string) $notification_row['read_flag'] : '1',
                        );
                    }
                }
            }
        }
        $this->record_admin_menu_visit($current_path);
        $data = array(
            'web_name' => getenv('WEB_NAME') ?: 'CMSNivo',
            'page_title' => $labels[$title_key],
            'current_path' => $current_path,
            'page_slug' => isset($extra_data['page_slug']) ? $extra_data['page_slug'] : '',
            'labels' => $labels,
            'admin_languages' => $this->admin_languages,
            'locale' => $locale,
            'html_lang' => $this->admin_languages[$locale]['html_lang'],
            'text_direction' => $this->admin_languages[$locale]['direction'],
            'is_rtl' => $this->admin_languages[$locale]['direction'] === 'rtl',
            'current_user' => $this->current_user,
            'notification_count' => $notification_count,
            'notification_items' => $notification_items,
            'csrf_token_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash(),
            'menu_groups' => $menu_groups,
            'popular_menu_items' => $popular_menu_items,
        );
        $data = array_merge($data, $extra_data);
        if ($view_language_pack === 'notifications') {
            foreach (array('heading', 'intro', 'unavailable', 'empty', 'empty_hint', 'pagination', 'previous', 'next', 'back', 'select_prompt', 'select_hint', 'list_label', 'read', 'unread', 'time_just_now', 'time_year', 'time_month', 'time_week', 'time_day', 'time_hour', 'time_minute', 'time_ago') as $key) {
                $data['notifications_' . $key] = $labels['notifications_' . $key];
            }
            foreach (array('actions', 'delete', 'delete_confirm_title', 'delete_confirm_body', 'confirm_delete', 'cancel') as $key) {
                $data['notifications_' . $key] = $labels['notifications_' . $key];
            }
            $data['notifications_breadcrumb_parent'] = $labels['dashboard'];
        }
        $this->load->view('admin/header', $data);
        $this->load->view($view, $data);
        $this->load->view('admin/footer', $data);
    }
}
