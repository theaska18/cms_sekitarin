<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller
{
    protected $current_user;

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
        $this->render_admin('admin/dashboard', 'dashboard', '/admin');
    }

    public function placeholder($section, $slug)
    {
        $sections = array(
            'admin/setting' => 'settings',
            'admin/profile' => 'profile',
            'admin/settings' => 'settings',
            'admin/hr/employee' => 'employee',
        );
        $path = $section . '/' . $slug;
        if (!isset($sections[$path])) {
            show_404();
            return;
        }

        $this->render_admin('admin/placeholder', $sections[$path], '/' . $path);
    }

    public function module($section, $slug)
    {
        $modules = array(
            'system/property' => array('key' => 'property', 'group_key' => 'system', 'description_key' => 'property_description', 'focus_key' => 'property_focus'),
            'system/parameter' => array('key' => 'parameter', 'group_key' => 'system', 'description_key' => 'parameter_description', 'focus_key' => 'parameter_focus'),
            'system/menu' => array('key' => 'menu', 'group_key' => 'system', 'description_key' => 'menu_description', 'focus_key' => 'menu_focus'),
            'system/sequence' => array('key' => 'sequence', 'group_key' => 'system', 'description_key' => 'system_sequence_description', 'focus_key' => 'system_sequence_focus'),
            'app/sequence' => array('key' => 'employee', 'group_key' => 'application', 'description_key' => 'employee_description', 'focus_key' => 'employee_focus'),
            'app/role' => array('key' => 'role', 'group_key' => 'application', 'description_key' => 'role_description', 'focus_key' => 'role_focus'),
            'app/user' => array('key' => 'user', 'group_key' => 'application', 'description_key' => 'user_description', 'focus_key' => 'user_focus'),
        );
        $path = $section . '/' . $slug;
        if (!isset($modules[$path])) {
            show_404();
            return;
        }

        $module = $modules[$path];
        $requested_path = '/' . trim($this->uri->uri_string(), '/');
        if ($path === 'system/sequence') {
            $this->render_sequence_list($module, $requested_path);
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

    private function render_sequence_list($module, $requested_path)
    {
        $this->load->database();
        $this->load->library('sequence_store');
        $search = trim((string) $this->input->get('search', TRUE));
        $search = mb_substr($search, 0, 120, 'UTF-8');
        $per_page = (int) $this->input->get('per_page');
        $per_page = in_array($per_page, array(10, 25, 50), TRUE) ? $per_page : 10;
        $sort = (string) $this->input->get('sort', TRUE);
        $sort_columns = array(
            'sequence_code', 'sequence_name', 'description', 'repeat_type',
            'last_value', 'last_sequence', 'format',
        );
        $sort = in_array($sort, $sort_columns, TRUE) ? $sort : 'sequence_code';
        $direction = strtoupper((string) $this->input->get('direction', TRUE));
        $direction = in_array($direction, array('ASC', 'DESC'), TRUE) ? $direction : 'ASC';

        $total_records = $this->sequence_store->count($search);
        $backend_available = $total_records !== FALSE;
        $page_count = $backend_available ? max(1, (int) ceil($total_records / $per_page)) : 1;
        $page = max(1, (int) $this->input->get('page'));
        $page = min($page, $page_count);
        $records = $backend_available
            ? $this->sequence_store->page($search, $page, $per_page, $sort, $direction)
            : FALSE;
        if ($records === FALSE) {
            $backend_available = FALSE;
            $records = array();
        }

        $this->render_admin(
            'admin/sequence/index',
            $module['key'],
            $requested_path,
            array(
                'module' => $module,
                'backend_available' => $backend_available,
                'records' => $records,
                'total_records' => (int) ($backend_available ? $total_records : 0),
                'search' => $search,
                'page' => $page,
                'page_count' => $page_count,
                'per_page' => $per_page,
                'sort' => $sort,
                'direction' => $direction,
                'write_available' => $this->current_user !== NULL && (bool) config_item('csrf_protection'),
                'sequence_notice' => (string) $this->session->flashdata('sequence_notice'),
            )
        );
    }

    private function resolve_admin_locale()
    {
        $requested_locale = $this->input->get('lang', TRUE);
        if (is_string($requested_locale) && isset($this->admin_languages[$requested_locale])) {
            $this->input->set_cookie('cmsnivo_admin_locale', $requested_locale, 31536000, '', '/', '', NULL, TRUE, 'Lax');
            return $requested_locale;
        }

        $stored_locale = $this->input->cookie('cmsnivo_admin_locale', TRUE);
        if (is_string($stored_locale) && isset($this->admin_languages[$stored_locale])) {
            return $stored_locale;
        }

        $configured_locale = config_item('language');
        return isset($this->admin_languages[$configured_locale]) ? $configured_locale : 'indonesian';
    }

    private function load_admin_menu_groups()
    {
        $this->load->library('admin_menu_manifest');
        $groups = $this->admin_menu_manifest->load(APPPATH . 'views/admin/menu_manifest/manifest.xml');
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
                if (strtoupper(trim($item['code'])) === 'DASHBOARD' || $item['route'] === '/admin') {
                    continue;
                }

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

        return $render_groups;
    }

    protected function render_admin($view, $title_key, $current_path, $extra_data = array())
    {
        $locale = $this->resolve_admin_locale();
        $this->lang->load('admin', $locale);
        $is_property_view = strpos($view, 'admin/property/') === 0;
        $is_tenant_view = strpos($view, 'admin/tenant/') === 0;
        $is_parameter_view = strpos($view, 'admin/parameter/') === 0;
        $is_sequence_view = strpos($view, 'admin/sequence/') === 0;
        $is_role_view = strpos($view, 'admin/role/') === 0;
        $is_user_view = strpos($view, 'admin/user/') === 0;
        if ($is_property_view) {
            $this->lang->load('property', $locale);
        }
        if ($is_tenant_view) {
            $this->lang->load('tenant', $locale);
        }
        if ($is_parameter_view) {
            $this->lang->load('parameter', $locale);
        }
        if ($is_sequence_view) {
            $this->lang->load('sequence', $locale);
        }
        if ($is_role_view) {
            $this->lang->load('role', $locale);
        }
        if ($is_user_view) {
            $this->lang->load('user', $locale);
        }
        $keys = array(
            'dashboard', 'system', 'application', 'property', 'tenant', 'parameter', 'menu', 'sequence',
            'employee', 'role', 'user', 'back_home', 'home_dialog_title', 'home_dialog_description', 'home_dialog_confirm', 'cancel', 'language', 'logout',
            'profile', 'settings', 'welcome_title', 'welcome_body', 'quick_links',
            'placeholder_title', 'placeholder_body', 'admin_area',
            'apply_language', 'topbar_navigation', 'sidebar_navigation', 'open_navigation', 'close_navigation',
            'path', 'not_built', 'logout_unavailable', 'avatar_alt', 'dashboard_sections',
            'module_overview', 'module_status', 'module_read_only', 'module_backend_unavailable',
            'module_focus', 'property_focus', 'parameter_focus', 'menu_focus', 'system_sequence_focus',
            'employee_focus', 'role_focus', 'user_focus',
            'property_description', 'parameter_description', 'menu_description', 'system_sequence_description',
            'employee_description', 'role_description', 'user_description',
            'filter_label', 'filter_placeholder', 'filter_clear', 'filter_empty', 'filter_results',
        );
        $labels = array();
        foreach ($keys as $key) {
            $labels[$key] = $this->lang->line('admin_' . $key);
        }
        if ($is_property_view) {
            $property_keys = array(
                'heading', 'intro', 'search_hint', 'code_label', 'name_label', 'filter_code', 'filter_name',
                'filter_code_hint', 'filter_name_hint', 'no_results', 'no_results_hint',
                'filter', 'reset', 'add', 'edit', 'delete', 'cancel', 'confirm_title', 'confirm_body',
                'confirm_delete', 'empty', 'empty_hint', 'unavailable', 'write_disabled', 'total', 'page',
                'page_of', 'from_data', 'previous', 'next', 'per_page', 'created', 'saved', 'deleted', 'not_found',
                'save_error', 'delete_error', 'invalid', 'name_required', 'form_new', 'form_edit',
                'form_intro', 'description_label', 'value_label', 'code_help', 'back', 'save', 'actions',
                'search_label'
            );
            foreach ($property_keys as $key) {
                $labels['property_' . $key] = $this->lang->line('property_' . $key);
            }
        }
        if ($is_tenant_view) {
            $tenant_keys = array(
                'heading', 'intro', 'search_hint', 'code_label', 'name_label', 'email_label', 'phone_label', 'due_on_label', 'due_flag_label',
                'no_results', 'no_results_hint', 'add', 'edit', 'delete', 'cancel', 'confirm_title', 'confirm_body',
                'confirm_delete', 'empty', 'empty_hint', 'unavailable', 'write_disabled', 'page', 'page_of', 'from_data',
                'previous', 'next', 'per_page', 'created', 'saved', 'deleted', 'not_found', 'save_error', 'delete_error',
                'invalid', 'name_required', 'due_on_required', 'form_new', 'form_edit', 'form_intro', 'code_help', 'back', 'save', 'actions',
                'search_label', 'menu_access'
            );
            foreach ($tenant_keys as $key) {
                $labels['tenant_' . $key] = $this->lang->line('tenant_' . $key);
            }
            $labels['tenant_code_label'] = $this->lang->line('tenant_code_label');
            $labels['tenant_name_label'] = $this->lang->line('tenant_name_label');
            $labels['tenant_address_label'] = $this->lang->line('tenant_address_label');
        }
        if ($is_parameter_view) {
            $parameter_keys = array(
                'heading', 'intro', 'code_label', 'name_label', 'description_label', 'summary_label',
                'search_label', 'search_hint', 'total', 'page', 'page_of', 'from_data', 'previous', 'next',
                'empty', 'empty_hint', 'no_results', 'unavailable', 'pagination', 'per_page',
                'add', 'edit', 'delete', 'actions', 'save', 'back', 'form_new', 'form_edit', 'form_intro',
                'option_code_label', 'option_name_label', 'active_label', 'option_add', 'option_remove',
                'option_minimum', 'option_duplicate', 'code_duplicate', 'option_drag', 'option_move_up', 'option_move_down', 'invalid', 'name_required',
                'save_error', 'delete_error', 'created', 'saved', 'deleted', 'not_found',
                'confirm_title', 'confirm_body', 'confirm_delete', 'write_disabled'
            );
            foreach ($parameter_keys as $key) {
                $labels['parameter_' . $key] = $this->lang->line('parameter_' . $key);
            }
        }

        if ($is_sequence_view) {
            $sequence_keys = array(
                'heading', 'intro', 'search_label', 'search_hint', 'total', 'empty',
                'no_results', 'empty_hint', 'no_results_hint', 'unavailable', 'page', 'page_of', 'previous', 'next',
                'per_page', 'sequence_code', 'sequence_name', 'description', 'repeat_type',
                'last_value', 'last_sequence', 'format', 'form_new', 'form_edit', 'form_intro', 'choose_repeat_type',
                'add', 'actions', 'edit', 'delete', 'confirm_delete', 'created', 'saved', 'deleted', 'delete_error',
                'code_help', 'name_help', 'description_help', 'last_value_help', 'last_sequence_help',
                'format_help', 'back', 'save', 'write_disabled', 'invalid', 'save_error', 'repeat_type_invalid',
                'sequence_code_required', 'sequence_name_required', 'repeat_type_required', 'last_value_required',
                'last_sequence_required', 'format_required', 'sequence_code_too_long', 'sequence_name_too_long',
                'description_too_long', 'repeat_type_too_long', 'last_value_too_long', 'last_sequence_too_long',
                'format_too_long', 'sequence_code_invalid', 'last_value_invalid', 'last_sequence_invalid',
            );
            foreach ($sequence_keys as $key) {
                $labels['sequence_' . $key] = $this->lang->line('sequence_' . $key);
            }
        }

        if ($is_role_view) {
            $role_keys = array(
                'heading', 'intro', 'search_label', 'search_hint', 'code_label', 'name_label',
                'description_label', 'active_label', 'active_yes', 'active_no', 'filter_any', 'filter_apply', 'filter_columns', 'actions', 'add',
                'edit', 'delete', 'confirm_delete', 'empty', 'empty_hint', 'no_results',
                'no_results_hint', 'unavailable', 'write_disabled', 'total', 'pagination',
                'previous', 'next', 'created', 'saved', 'deleted', 'delete_error', 'form_new',
                'form_edit', 'form_intro', 'code_help', 'back', 'cancel', 'save', 'invalid',
                'code_invalid', 'code_too_long', 'code_duplicate', 'name_required', 'name_too_long',
                'description_too_long', 'save_error', 'tenant_label', 'tenant_placeholder', 'tenant_invalid', 'per_page', 'pagination_summary',
                'menu_access', 'menu_heading', 'menu_intro', 'menu_unavailable', 'menu_saved', 'menu_save_error', 'menu_empty'
            );
            foreach ($role_keys as $key) {
                $labels['role_' . $key] = $this->lang->line('role_' . $key);
            }
        }
        if ($is_user_view) {
            $user_keys = array(
                'heading', 'intro', 'search', 'search_hint', 'filter_role', 'filter_block', 'filter_columns', 'apply_filter', 'add', 'code', 'name', 'full_name', 'full_name_help', 'role', 'employee',
                'block_status', 'actions', 'edit', 'change_password', 'block', 'unblock', 'yes', 'no', 'empty',
                'unavailable', 'write_disabled', 'page', 'previous', 'next', 'per_page', 'total', 'created', 'saved',
                'password_changed', 'blocked', 'unblocked', 'confirm_title', 'confirm_body', 'cancel', 'confirm',
                'form_new', 'form_edit', 'form_intro', 'tenant', 'choose_tenant', 'role_choose', 'employee_flag',
                'employee_search', 'employee_help', 'access_api_flag', 'access_api_help', 'api_secret', 'api_generate', 'password', 'retry_password', 'back', 'save', 'invalid',
                'tenant_invalid', 'user_code_required', 'user_code_too_long', 'user_code_duplicate', 'username_required',
                'username_too_long', 'username_duplicate', 'password_required', 'password_too_long', 'password_mismatch',
                'role_invalid', 'employee_invalid', 'save_error'
            );
            foreach ($user_keys as $key) {
                $labels['user_' . $key] = $this->lang->line('user_' . $key);
            }
        }
        $data = array(
            'web_name' => getenv('WEB_NAME') ?: 'CMSNivo',
            'page_title' => $labels[$title_key],
            'current_path' => $current_path,
            'labels' => $labels,
            'admin_languages' => $this->admin_languages,
            'locale' => $locale,
            'html_lang' => $this->admin_languages[$locale]['html_lang'],
            'text_direction' => $this->admin_languages[$locale]['direction'],
            'is_rtl' => $this->admin_languages[$locale]['direction'] === 'rtl',
            'current_user' => $this->current_user,
            'csrf_token_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash(),
            'menu_groups' => $this->load_admin_menu_groups(),
        );
        $data = array_merge($data, $extra_data);
        $this->load->view('admin/header', $data);
        $this->load->view($view, $data);
        $this->load->view('admin/footer', $data);
    }
}
