<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller
{
    private $admin_languages = array(
        'english' => array('html_lang' => 'en', 'direction' => 'ltr'),
        'chinese' => array('html_lang' => 'zh-CN', 'direction' => 'ltr'),
        'french' => array('html_lang' => 'fr', 'direction' => 'ltr'),
        'german' => array('html_lang' => 'de', 'direction' => 'ltr'),
        'indonesian' => array('html_lang' => 'id', 'direction' => 'ltr'),
        'japanese' => array('html_lang' => 'ja', 'direction' => 'ltr'),
        'korean' => array('html_lang' => 'ko', 'direction' => 'ltr'),
        'spanish' => array('html_lang' => 'es', 'direction' => 'ltr'),
        'arabic' => array('html_lang' => 'ar', 'direction' => 'rtl'),
    );

    public function __construct()
    {
        parent::__construct();
        $this->load->library('admin_identity');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        $this->output->set_header('Pragma: no-cache');
    }

    public function index()
    {
        if ($this->admin_identity->current_user() !== NULL) {
            $this->redirect_local('/admin');
            return;
        }

        $locale = $this->resolve_locale();
        $this->lang->load('admin', $locale);
        $keys = array(
            'auth_title', 'auth_intro', 'auth_username', 'auth_password', 'auth_submit',
            'auth_invalid', 'auth_home', 'auth_password_show', 'auth_password_hide',
            'auth_method_not_allowed', 'auth_session_required', 'auth_language',
        );
        $labels = array();
        foreach ($keys as $key) {
            $labels[$key] = $this->lang->line('admin_' . $key);
        }

        $error = NULL;
        if ($this->input->method(TRUE) === 'POST') {
            $username = $this->input->post('username', TRUE);
            $password = $this->input->post('password', FALSE);
            $username = is_string($username) ? trim($username) : '';
            $valid_username = $username !== ''
                && strlen($username) <= 254
                && preg_match('/\A[\p{L}\p{N}._@+\-]+\z/u', $username);
            $valid_password = is_string($password)
                && $password !== ''
                && strlen($password) <= 4096
                && strpos($password, "\0") === FALSE;

            if ($valid_username && $valid_password
                && $this->admin_identity->authenticate($username, $password) !== FALSE) {
                $this->redirect_local('/admin');
                return;
            }

            $this->output->set_status_header(401);
            $error = $labels['auth_invalid'];
        }

        $this->load->library('property_store');
        $this->load->view('auth/login', array(
            'web_name' => $this->property_store->web_name('CMSNivo'),
            'labels' => $labels,
            'error_message' => $error,
            'locale' => $locale,
            'html_lang' => $this->admin_languages[$locale]['html_lang'],
            'text_direction' => $this->admin_languages[$locale]['direction'],
            'csrf_token_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash(),
        ));
    }

    public function logout()
    {
        if ($this->admin_identity->current_user() === NULL) {
            $this->output->set_status_header(401);
            $this->output->set_output($this->localized_message('auth_session_required'));
            return;
        }

        if ($this->input->method(TRUE) !== 'POST') {
            $this->output->set_header('Allow: POST');
            $this->output->set_status_header(405);
            $this->output->set_output($this->localized_message('auth_method_not_allowed'));
            return;
        }

        $this->admin_identity->logout();
        $this->redirect_local('/');
    }

    private function redirect_local($path)
    {
        if (!in_array($path, array('/', '/admin'), TRUE)) {
            $path = '/';
        }
        header('Location: ' . $path, TRUE, 303);
        exit;
    }

    private function resolve_locale()
    {
        $requested_locale = $this->input->get('lang', TRUE);
        if (is_string($requested_locale) && isset($this->admin_languages[$requested_locale])) {
            $this->input->set_cookie(
                'cmsnivo_admin_locale',
                $requested_locale,
                31536000,
                '',
                '/',
                '',
                (bool) config_item('cookie_secure'),
                TRUE,
                'Lax'
            );
            return $requested_locale;
        }

        $stored_locale = $this->input->cookie('cmsnivo_admin_locale', TRUE);
        if (is_string($stored_locale) && isset($this->admin_languages[$stored_locale])) {
            return $stored_locale;
        }

        $configured_locale = config_item('language');
        return isset($this->admin_languages[$configured_locale]) ? $configured_locale : 'indonesian';
    }

    private function localized_message($key)
    {
        $locale = $this->resolve_locale();
        $this->lang->load('admin', $locale);
        return $this->lang->line('admin_' . $key);
    }
}
