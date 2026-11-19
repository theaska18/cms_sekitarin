<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

/** Finance setup/master-data pages; accounting workflows are implemented separately. */
class Admin_finance extends Admin
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('finance_filter_panel');
        $this->load->library('finance_list_table');
    }

    public function index($slug = '')
    {
        if ($slug === 'dashboard') {
            $this->finance_dashboard();
            return;
        }
        if (in_array($slug, array('all_transactions', 'income', 'expenses'), TRUE)) {
            $this->finance_transaction_index($slug);
            return;
        }
        if (in_array($slug, array('journal_entries', 'opening_balances'), TRUE)) {
            $this->finance_journal_index($slug);
            return;
        }
        if (in_array($slug, array('sales_invoices', 'purchase_bills'), TRUE)) {
            $this->finance_document_index($slug);
            return;
        }
        if (in_array($slug, array('customer_payments', 'supplier_payments'), TRUE)) {
            $this->finance_payment_index($slug);
            return;
        }
        if ($slug === 'cash_bank_transfer') {
            $this->finance_transfer_index();
            return;
        }
        if ($slug === 'bank_reconciliation') {
            $this->finance_reconciliation_index();
            return;
        }
        if ($slug === 'cash_flow') {
            $this->finance_cash_flow_statement();
            return;
        }
        if ($slug === 'profit_loss') {
            $this->finance_profit_loss_statement();
            return;
        }
        if ($slug === 'balance_sheet_skontro') {
            $this->finance_balance_sheet_skontro();
            return;
        }
        if ($this->finance_report_menu_code($slug) !== NULL) {
            $this->finance_report_index($slug);
            return;
        }
        if ($slug === 'chart_of_accounts') {
            $this->finance_chart_of_accounts_index();
            return;
        }
        $resource = $this->finance_resource($slug);
        if ($resource === NULL) {
            $this->module('finance', $slug);
            return;
        }
        if (!$this->finance_menu_allowed($resource)) {
            $this->finance_forbidden();
            return;
        }

        $this->load->library('finance_store');
        $store = $this->finance_store;
        $available = $store->available($resource);
        foreach ($resource['columns'] as &$column) {
            $column['label_key'] = isset($column['label_key'])
                ? $column['label_key']
                : 'finance_resource_column_' . $column['name'];
            foreach ($resource['fields'] as $field) {
                if ($field['name'] === $column['name'] && !empty($field['options'])) {
                    $column['value_label_keys'] = array();
                    foreach ($field['options'] as $option => $option_label) {
                        $column['value_label_keys'][$option] = isset($field['option_label_keys'][$option])
                            ? $field['option_label_keys'][$option]
                            : 'finance_resource_option_' . $field['name'] . '_' . $option;
                    }
                    break;
                }
            }
        }
        unset($column);
        $this->render_admin(
            'admin/finance/resource_index',
            $resource['title_key'],
            '/admin/finance/' . $resource['slug'],
            array(
                'finance_resource' => $resource,
                'finance_available' => $available,
                'finance_can_add' => $this->has_admin_access_code($resource['menu_code'] . '_add')
                    && !($slug === 'financial_settings' && $available
                        && $store->count($resource, (int) $this->current_user['tenant_id']) > 0),
                'finance_can_update' => $this->has_admin_access_code($resource['menu_code'] . '_update'),
                'finance_can_export' => $this->has_admin_access_code($resource['menu_code'] . '_export'),
                'finance_can_import' => $this->has_admin_access_code($resource['menu_code'] . '_import'),
                'finance_write_available' => $this->finance_write_available(),
                'finance_status' => (string) $this->session->flashdata('finance_status'),
                'finance_csrf_name' => $this->security->get_csrf_token_name(),
                'finance_csrf_hash' => $this->security->get_csrf_hash(),
                'finance_category_import_errors' => $slug === 'categories' ? $this->session->flashdata('category_import_errors') : NULL,
                'finance_category_import_count' => $slug === 'categories' ? (int) $this->session->flashdata('category_import_count') : 0,
                'finance_tax_import_errors' => $slug === 'tax_configuration' ? $this->session->flashdata('tax_import_errors') : NULL,
                'finance_tax_import_count' => $slug === 'tax_configuration' ? (int) $this->session->flashdata('tax_import_count') : 0,
            )
        );
    }

    private function finance_chart_of_accounts_index()
    {
        $menu = 'fin_chart_of_accounts';
        if (!$this->has_admin_menu_code($menu)) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_store');
        $available = $this->finance_store->available($this->finance_resource('chart_of_accounts'));
        $this->render_admin(
            'admin/finance/chart_of_accounts_index',
            'finance_chart_of_accounts',
            '/admin/finance/chart_of_accounts',
            array(
                'finance_chart_of_accounts_available' => $available,
                'finance_chart_of_accounts_can_add' => $this->has_admin_access_code($menu . '_add'),
                'finance_chart_of_accounts_can_update' => $this->has_admin_access_code($menu . '_update'),
                'finance_chart_of_accounts_can_export' => $this->has_admin_access_code($menu . '_export'),
                'finance_chart_of_accounts_can_import' => $this->has_admin_access_code($menu . '_import'),
                'finance_chart_of_accounts_write_available' => $this->finance_write_available(),
                'finance_status' => (string) $this->session->flashdata('finance_status'),
                'finance_coa_import_errors' => $this->session->flashdata('coa_import_errors'),
                'finance_coa_import_count' => (int) $this->session->flashdata('coa_import_count'),
                'finance_csrf_name' => $this->security->get_csrf_token_name(),
                'finance_csrf_hash' => $this->security->get_csrf_hash(),
            )
        );
    }

    private function finance_chart_of_accounts_data()
    {
        if (!$this->has_admin_menu_code('fin_chart_of_accounts')) {
            $this->output->set_status_header(403);
            $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
            return;
        }
        $rows = $this->db->select('account_id,parent_account_id,account_code,account_name,account_type,normal_balance,active_flag,posting_flag')
            ->from('fin_account')->where('tenant_id', (int) $this->current_user['tenant_id'])
            ->order_by('account_code', 'ASC')->get()->result_array();
        $by_id = array();
        foreach ($rows as $row) {
            $row['account_id'] = (int) $row['account_id'];
            $row['parent_account_id'] = !empty($row['parent_account_id']) ? (int) $row['parent_account_id'] : NULL;
            $by_id[$row['account_id']] = $row;
        }
        $children = array();
        $roots = array();
        foreach ($by_id as $id => $row) {
            $parent = $row['parent_account_id'];
            if ($parent === NULL || !isset($by_id[$parent]) || $parent === $id) {
                $roots[] = $id;
            } else {
                $children[$parent][] = $id;
            }
        }
        $flat = array();
        $visited = array();
        $walk = function ($id, $level, $path) use (&$walk, &$flat, &$visited, $by_id, $children) {
            if (isset($visited[$id])) {
                return;
            }
            $visited[$id] = TRUE;
            $row = $by_id[$id];
            $parent_name = $row['parent_account_id'] !== NULL && isset($by_id[$row['parent_account_id']])
                ? $by_id[$row['parent_account_id']]['account_code'] . ' — ' . $by_id[$row['parent_account_id']]['account_name']
                : '';
            $flat[] = array(
                'account_id' => $row['account_id'],
                'parent_account_id' => $row['parent_account_id'],
                'account_code' => $row['account_code'],
                'account_name' => $row['account_name'],
                'account_type' => $row['account_type'],
                'normal_balance' => $row['normal_balance'],
                'active_flag' => (int) $row['active_flag'],
                'posting_flag' => (int) $row['posting_flag'],
                'parent_name' => $parent_name,
                'level' => $level,
                'has_children' => !empty($children[$id]),
                'path' => array_values($path),
            );
            if (!empty($children[$id])) {
                $next_path = $path;
                $next_path[] = $id;
                foreach ($children[$id] as $child_id) {
                    $walk($child_id, $level + 1, $next_path);
                }
            }
        };
        foreach ($roots as $root_id) {
            $walk($root_id, 0, array());
        }
        $this->output->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($flat, JSON_INVALID_UTF8_SUBSTITUTE));
    }

    public function tax_export()
    {
        if (!$this->has_admin_menu_code('fin_tax_configuration')
            || !$this->has_admin_access_code('fin_tax_configuration_export')) {
            $this->finance_forbidden();
            return;
        }
        $tenant_id = (int) $this->current_user['tenant_id'];
        $rows = $this->db->select('t.tax_code,t.tax_name,t.rate_percent,a.account_code,t.active_flag', FALSE)
            ->from('fin_tax t')
            ->join('fin_account a', 'a.account_id = t.account_id AND a.tenant_id = t.tenant_id', 'left')
            ->where('t.tenant_id', $tenant_id)->order_by('t.tax_code', 'ASC')->get()->result_array();
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, array('tax_code', 'tax_name', 'rate_percent', 'account_code', 'active_flag'));
        foreach ($rows as $row) {
            fputcsv($stream, array($row['tax_code'], $row['tax_name'], $row['rate_percent'], (string) $row['account_code'], (int) $row['active_flag']));
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        $tenant_code = isset($this->current_user['tenant_code']) ? (string) $this->current_user['tenant_code'] : (string) $tenant_id;
        $filename = 'taxes-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $tenant_code) . '-' . date('Ymd') . '.csv';
        $this->output->set_content_type('text/csv', 'utf-8')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_output("\xEF\xBB\xBF" . $csv);
    }

    public function tax_import()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        if (!$this->has_admin_menu_code('fin_tax_configuration')
            || !$this->has_admin_access_code('fin_tax_configuration_import')) {
            $this->finance_forbidden();
            return;
        }
        $errors = array();
        $rows = array();
        $file_codes = array();
        if (empty($_FILES['tax_file']) || !is_array($_FILES['tax_file'])
            || !isset($_FILES['tax_file']['error']) || $_FILES['tax_file']['error'] !== UPLOAD_ERR_OK
            || empty($_FILES['tax_file']['tmp_name']) || !is_uploaded_file($_FILES['tax_file']['tmp_name'])) {
            $errors[] = 'File CSV wajib diunggah.';
        } else {
            $file = $_FILES['tax_file'];
            if (strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'csv') {
                $errors[] = 'Format file harus CSV.';
            }
            if ((int) $file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Ukuran file maksimal 2 MB.';
            }
        }
        $content = '';
        if (!$errors) {
            $content = file_get_contents($_FILES['tax_file']['tmp_name']);
            if ($content === FALSE || $content === '') {
                $errors[] = 'File CSV kosong.';
            }
        }
        if (!$errors) {
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $content);
            rewind($stream);
            $header = fgetcsv($stream);
            if (is_array($header)) {
                $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
                $header = array_map('trim', $header);
            }
            $expected = array('tax_code', 'tax_name', 'rate_percent', 'account_code', 'active_flag');
            if (!is_array($header) || $header !== $expected) {
                $errors[] = 'Header CSV tidak sesuai. Gunakan template export konfigurasi pajak.';
            } else {
                $line_no = 1;
                while (($line = fgetcsv($stream)) !== FALSE) {
                    $line_no++;
                    if ($line === array(NULL) || $line === array('')) {
                        continue;
                    }
                    if (count($rows) >= 1000) {
                        $errors[] = 'Maksimal 1.000 baris pajak.';
                        break;
                    }
                    $line = array_pad($line, 5, '');
                    $line = array_map(function ($value) { return trim((string) $value); }, $line);
                    $code = $line[0];
                    if ($code === '' || strlen($code) > 32) {
                        $errors[] = 'Baris ' . $line_no . ': kode pajak wajib, maksimal 32 karakter.';
                    }
                    if ($line[1] === '' || strlen($line[1]) > 100) {
                        $errors[] = 'Baris ' . $line_no . ': nama pajak wajib, maksimal 100 karakter.';
                    }
                    if (!preg_match('/^\d{1,3}(?:\.\d{1,2})?$/', $line[2]) || (float) $line[2] < 0 || (float) $line[2] > 100) {
                        $errors[] = 'Baris ' . $line_no . ': tarif pajak harus angka 0-100 dengan maksimal 2 desimal.';
                    }
                    if (!in_array($line[4], array('0', '1'), TRUE)) {
                        $errors[] = 'Baris ' . $line_no . ': active_flag harus 0 atau 1.';
                    }
                    if (isset($file_codes[$code])) {
                        $errors[] = 'Baris ' . $line_no . ': kode pajak ' . $code . ' duplikat di file.';
                    }
                    $file_codes[$code] = TRUE;
                    if ((float) $line[2] > 0 && $line[3] === '') {
                        $errors[] = 'Baris ' . $line_no . ': akun pajak wajib untuk tarif lebih dari 0.';
                    }
                    $rows[] = array(
                        'tax_code' => $code,
                        'tax_name' => $line[1],
                        'rate_percent' => (float) $line[2],
                        'account_code' => $line[3],
                        'active_flag' => (int) $line[4],
                    );
                }
            }
            fclose($stream);
        }
        if (!$errors && $rows) {
            $tenant_id = (int) $this->current_user['tenant_id'];
            $existing_rows = $this->db->select('tax_code')->from('fin_tax')->where('tenant_id', $tenant_id)->get()->result_array();
            $existing = array();
            foreach ($existing_rows as $row) {
                $existing[(string) $row['tax_code']] = TRUE;
            }
            $conflicts = array_values(array_intersect(array_keys($file_codes), array_keys($existing)));
            if ($conflicts) {
                $errors[] = 'Import dibatalkan. Kode pajak sudah ada di tenant: ' . implode(', ', $conflicts) . '.';
            }
            $codes = array();
            foreach ($rows as $row) {
                if ($row['account_code'] !== '') {
                    $codes[$row['account_code']] = TRUE;
                }
            }
            $accounts = array();
            if ($codes) {
                $account_rows = $this->db->select('account_id,account_code,account_type,active_flag,posting_flag')
                    ->from('fin_account')->where('tenant_id', $tenant_id)
                    ->where_in('account_code', array_keys($codes))->get()->result_array();
                foreach ($account_rows as $account) {
                    $accounts[(string) $account['account_code']] = $account;
                }
            }
            foreach ($rows as $row) {
                if ($row['account_code'] === '') {
                    continue;
                }
                $account = isset($accounts[$row['account_code']]) ? $accounts[$row['account_code']] : NULL;
                if (!$account) {
                    $errors[] = 'Akun pajak ' . $row['account_code'] . ' tidak ditemukan.';
                    continue;
                }
                if ((int) $account['active_flag'] !== 1 || (int) $account['posting_flag'] !== 1) {
                    $errors[] = 'Akun pajak ' . $row['account_code'] . ' tidak aktif atau bukan akun posting.';
                }
                if (!in_array($account['account_type'], array('asset', 'liability'), TRUE)) {
                    $errors[] = 'Akun pajak ' . $row['account_code'] . ' harus bertipe aset atau liabilitas.';
                }
            }
        }
        if (!$errors && $rows) {
            $tenant_id = (int) $this->current_user['tenant_id'];
            $this->db->trans_begin();
            foreach ($rows as $row) {
                $account_id = NULL;
                if ($row['account_code'] !== '') {
                    $account_id = isset($accounts[$row['account_code']]) ? (int) $accounts[$row['account_code']]['account_id'] : NULL;
                }
                $this->db->insert('fin_tax', array(
                    'tenant_id' => $tenant_id,
                    'account_id' => $account_id,
                    'tax_code' => $row['tax_code'],
                    'tax_name' => $row['tax_name'],
                    'rate_percent' => $row['rate_percent'],
                    'active_flag' => $row['active_flag'],
                    'create_on' => date('Y-m-d H:i:s'),
                    'created_by' => (int) $this->current_user['user_id'],
                ));
                if ($this->db->affected_rows() !== 1) {
                    $errors[] = 'Pajak ' . $row['tax_code'] . ' gagal disimpan.';
                    break;
                }
            }
            if ($errors || $this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
            } else {
                $this->db->trans_commit();
                $this->session->set_flashdata('finance_status', 'tax_imported');
                $this->session->set_flashdata('tax_import_count', count($rows));
                redirect('/admin/finance/tax_configuration', 'location', 303);
                return;
            }
        }
        if (!$rows && !$errors) {
            $errors[] = 'File CSV tidak berisi data pajak.';
        }
        $this->session->set_flashdata('finance_status', 'tax_import_error');
        $this->session->set_flashdata('tax_import_errors', $errors);
        redirect('/admin/finance/tax_configuration', 'location', 303);
    }

    public function category_export()
    {
        if (!$this->has_admin_menu_code('fin_categories')
            || !$this->has_admin_access_code('fin_categories_export')) {
            $this->finance_forbidden();
            return;
        }
        $tenant_id = (int) $this->current_user['tenant_id'];
        $rows = $this->db->select('category_type,category_code,category_name,active_flag')
            ->from('fin_category')->where('tenant_id', $tenant_id)
            ->order_by('category_type', 'ASC')->order_by('category_code', 'ASC')->get()->result_array();
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, array('category_type', 'category_code', 'category_name', 'active_flag'));
        foreach ($rows as $row) {
            fputcsv($stream, array($row['category_type'], $row['category_code'], $row['category_name'], (int) $row['active_flag']));
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        $tenant_code = isset($this->current_user['tenant_code']) ? (string) $this->current_user['tenant_code'] : (string) $tenant_id;
        $filename = 'categories-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $tenant_code) . '-' . date('Ymd') . '.csv';
        $this->output->set_content_type('text/csv', 'utf-8')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_output("\xEF\xBB\xBF" . $csv);
    }

    public function category_import()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        if (!$this->has_admin_menu_code('fin_categories')
            || !$this->has_admin_access_code('fin_categories_import')) {
            $this->finance_forbidden();
            return;
        }
        $errors = array();
        $rows = array();
        $file_keys = array();
        if (empty($_FILES['category_file']) || !is_array($_FILES['category_file'])
            || !isset($_FILES['category_file']['error']) || $_FILES['category_file']['error'] !== UPLOAD_ERR_OK
            || empty($_FILES['category_file']['tmp_name']) || !is_uploaded_file($_FILES['category_file']['tmp_name'])) {
            $errors[] = 'File CSV wajib diunggah.';
        } else {
            $file = $_FILES['category_file'];
            if (strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'csv') {
                $errors[] = 'Format file harus CSV.';
            }
            if ((int) $file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Ukuran file maksimal 2 MB.';
            }
        }
        $content = '';
        if (!$errors) {
            $content = file_get_contents($_FILES['category_file']['tmp_name']);
            if ($content === FALSE || $content === '') {
                $errors[] = 'File CSV kosong.';
            }
        }
        if (!$errors) {
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $content);
            rewind($stream);
            $header = fgetcsv($stream);
            if (is_array($header)) {
                $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
                $header = array_map('trim', $header);
            }
            $expected = array('category_type', 'category_code', 'category_name', 'active_flag');
            if (!is_array($header) || $header !== $expected) {
                $errors[] = 'Header CSV tidak sesuai. Gunakan template export kategori.';
            } else {
                $line_no = 1;
                while (($line = fgetcsv($stream)) !== FALSE) {
                    $line_no++;
                    if ($line === array(NULL) || $line === array('')) {
                        continue;
                    }
                    if (count($rows) >= 1000) {
                        $errors[] = 'Maksimal 1.000 baris kategori.';
                        break;
                    }
                    $line = array_pad($line, 4, '');
                    $line = array_map(function ($value) { return trim((string) $value); }, $line);
                    $type = $line[0];
                    $code = $line[1];
                    if (!in_array($type, array('income', 'expense'), TRUE)) {
                        $errors[] = 'Baris ' . $line_no . ': tipe kategori harus income atau expense.';
                    }
                    if ($code === '' || strlen($code) > 32) {
                        $errors[] = 'Baris ' . $line_no . ': kode kategori wajib, maksimal 32 karakter.';
                    }
                    if ($line[2] === '' || strlen($line[2]) > 100) {
                        $errors[] = 'Baris ' . $line_no . ': nama kategori wajib, maksimal 100 karakter.';
                    }
                    if (!in_array($line[3], array('0', '1'), TRUE)) {
                        $errors[] = 'Baris ' . $line_no . ': active_flag harus 0 atau 1.';
                    }
                    $key = $type . '|' . $code;
                    if (isset($file_keys[$key])) {
                        $errors[] = 'Baris ' . $line_no . ': kategori ' . $key . ' duplikat di file.';
                    }
                    $file_keys[$key] = TRUE;
                    $rows[] = array(
                        'category_type' => $type,
                        'category_code' => $code,
                        'category_name' => $line[2],
                        'active_flag' => (int) $line[3],
                    );
                }
            }
            fclose($stream);
        }
        if (!$errors && $rows) {
            $tenant_id = (int) $this->current_user['tenant_id'];
            $existing_rows = $this->db->select('category_type,category_code')->from('fin_category')
                ->where('tenant_id', $tenant_id)->get()->result_array();
            $existing = array();
            foreach ($existing_rows as $row) {
                $existing[$row['category_type'] . '|' . $row['category_code']] = TRUE;
            }
            $conflicts = array_values(array_intersect(array_keys($file_keys), array_keys($existing)));
            if ($conflicts) {
                $errors[] = 'Import dibatalkan. Kategori sudah ada di tenant: ' . implode(', ', $conflicts) . '.';
            }
        }
        if (!$errors && $rows) {
            $tenant_id = (int) $this->current_user['tenant_id'];
            $user_id = (int) $this->current_user['user_id'];
            $this->db->trans_begin();
            foreach ($rows as $row) {
                $this->db->insert('fin_category', array(
                    'tenant_id' => $tenant_id,
                    'category_type' => $row['category_type'],
                    'category_code' => $row['category_code'],
                    'category_name' => $row['category_name'],
                    'active_flag' => $row['active_flag'],
                    'create_on' => date('Y-m-d H:i:s'),
                ));
                if ($this->db->affected_rows() !== 1) {
                    $errors[] = 'Kategori ' . $row['category_code'] . ' gagal disimpan.';
                    break;
                }
            }
            if ($errors || $this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
            } else {
                $this->db->trans_commit();
                $this->session->set_flashdata('finance_status', 'category_imported');
                $this->session->set_flashdata('category_import_count', count($rows));
                redirect('/admin/finance/categories', 'location', 303);
                return;
            }
        }
        if (!$rows && !$errors) {
            $errors[] = 'File CSV tidak berisi data kategori.';
        }
        $this->session->set_flashdata('finance_status', 'category_import_error');
        $this->session->set_flashdata('category_import_errors', $errors);
        redirect('/admin/finance/categories', 'location', 303);
    }

    public function chart_of_accounts_export()
    {
        if (!$this->has_admin_menu_code('fin_chart_of_accounts')
            || !$this->has_admin_access_code('fin_chart_of_accounts_export')) {
            $this->finance_forbidden();
            return;
        }
        $tenant_id = (int) $this->current_user['tenant_id'];
        $rows = $this->db->select('account_id,parent_account_id,account_code,account_name,account_type,normal_balance,posting_flag,active_flag,description')
            ->from('fin_account')->where('tenant_id', $tenant_id)->order_by('account_code', 'ASC')->get()->result_array();
        $codes = array();
        foreach ($rows as $row) {
            $codes[(int) $row['account_id']] = (string) $row['account_code'];
        }
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, array('account_code', 'account_name', 'account_type', 'normal_balance', 'parent_code', 'posting_flag', 'active_flag', 'description'));
        foreach ($rows as $row) {
            $parent_code = !empty($row['parent_account_id']) && isset($codes[(int) $row['parent_account_id']])
                ? $codes[(int) $row['parent_account_id']] : '';
            fputcsv($stream, array(
                $row['account_code'],
                $row['account_name'],
                $row['account_type'],
                $row['normal_balance'],
                $parent_code,
                (int) $row['posting_flag'],
                (int) $row['active_flag'],
                $row['description'],
            ));
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        $tenant_code = isset($this->current_user['tenant_code']) ? (string) $this->current_user['tenant_code'] : (string) $tenant_id;
        $filename = 'coa-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $tenant_code) . '-' . date('Ymd') . '.csv';
        $this->output->set_content_type('text/csv', 'utf-8')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_output("\xEF\xBB\xBF" . $csv);
    }

    public function chart_of_accounts_import()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        if (!$this->has_admin_menu_code('fin_chart_of_accounts')
            || !$this->has_admin_access_code('fin_chart_of_accounts_import')) {
            $this->finance_forbidden();
            return;
        }
        $errors = array();
        if (empty($_FILES['coa_file']) || !is_array($_FILES['coa_file'])
            || !isset($_FILES['coa_file']['error']) || $_FILES['coa_file']['error'] !== UPLOAD_ERR_OK
            || empty($_FILES['coa_file']['tmp_name']) || !is_uploaded_file($_FILES['coa_file']['tmp_name'])) {
            $errors[] = 'File CSV wajib diunggah.';
        } else {
            $file = $_FILES['coa_file'];
            $name = (string) $file['name'];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                $errors[] = 'Format file harus CSV.';
            }
            if ((int) $file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Ukuran file maksimal 2 MB.';
            }
        }
        if (!$errors) {
            $content = file_get_contents($_FILES['coa_file']['tmp_name']);
            if ($content === FALSE || $content === '') {
                $errors[] = 'File CSV kosong.';
            }
        }
        $rows = array();
        $file_codes = array();
        if (!$errors) {
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $content);
            rewind($stream);
            $header = fgetcsv($stream);
            if (is_array($header)) {
                $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
                $header = array_map('trim', $header);
            }
            $expected = array('account_code', 'account_name', 'account_type', 'normal_balance', 'parent_code', 'posting_flag', 'active_flag', 'description');
            if (!is_array($header) || $header !== $expected) {
                $errors[] = 'Header CSV tidak sesuai. Gunakan template export COA.';
            } else {
                $line_no = 1;
                while (($line = fgetcsv($stream)) !== FALSE) {
                    $line_no++;
                    if ($line === array(NULL) || $line === array('')) {
                        continue;
                    }
                    if (count($rows) >= 1000) {
                        $errors[] = 'Maksimal 1.000 baris akun.';
                        break;
                    }
                    $line = array_pad($line, 8, '');
                    $line = array_map(function ($value) { return trim((string) $value); }, $line);
                    $code = $line[0];
                    $parent_code = $line[4];
                    if ($code === '') {
                        $errors[] = 'Baris ' . $line_no . ': kode akun wajib diisi.';
                        continue;
                    }
                    if (strlen($code) > 32) {
                        $errors[] = 'Baris ' . $line_no . ': kode akun maksimal 32 karakter.';
                    }
                    if (isset($file_codes[$code])) {
                        $errors[] = 'Baris ' . $line_no . ': kode akun ' . $code . ' duplikat di file.';
                    }
                    $file_codes[$code] = TRUE;
                    if ($line[1] === '') {
                        $errors[] = 'Baris ' . $line_no . ': nama akun wajib diisi.';
                    }
                    if (!in_array($line[2], array('asset', 'liability', 'equity', 'revenue', 'expense'), TRUE)) {
                        $errors[] = 'Baris ' . $line_no . ': tipe akun tidak valid.';
                    }
                    if (!in_array($line[3], array('debit', 'credit'), TRUE)) {
                        $errors[] = 'Baris ' . $line_no . ': saldo normal tidak valid.';
                    }
                    if (!in_array($line[5], array('0', '1'), TRUE)) {
                        $errors[] = 'Baris ' . $line_no . ': posting_flag harus 0 atau 1.';
                    }
                    if (!in_array($line[6], array('0', '1'), TRUE)) {
                        $errors[] = 'Baris ' . $line_no . ': active_flag harus 0 atau 1.';
                    }
                    if ($parent_code === $code) {
                        $errors[] = 'Baris ' . $line_no . ': akun tidak boleh menjadi parent dirinya sendiri.';
                    }
                    $rows[] = array(
                        'account_code' => $code,
                        'account_name' => $line[1],
                        'account_type' => $line[2],
                        'normal_balance' => $line[3],
                        'parent_code' => $parent_code,
                        'posting_flag' => (int) $line[5],
                        'active_flag' => (int) $line[6],
                        'description' => $line[7],
                    );
                }
            }
            fclose($stream);
        }
        if (!$errors && $rows) {
            $tenant_id = (int) $this->current_user['tenant_id'];
            $existing = array();
            $existing_rows = $this->db->select('account_code,account_type,posting_flag')->from('fin_account')
                ->where('tenant_id', $tenant_id)->get()->result_array();
            foreach ($existing_rows as $row) {
                $existing[(string) $row['account_code']] = $row;
            }
            $conflicts = array_values(array_intersect(array_keys($file_codes), array_keys($existing)));
            if ($conflicts) {
                $errors[] = 'Import dibatalkan. Kode akun sudah ada di tenant: ' . implode(', ', $conflicts) . '.';
            }
            $parent_map = array();
            foreach ($rows as $row) {
                if ($row['parent_code'] !== '') {
                    if (!isset($file_codes[$row['parent_code']]) && !isset($existing[$row['parent_code']])) {
                        $errors[] = 'Parent ' . $row['parent_code'] . ' untuk akun ' . $row['account_code'] . ' tidak ditemukan.';
                    }
                }
                $parent_map[$row['account_code']] = $row['parent_code'];
            }
            foreach ($rows as $row) {
                $seen = array();
                $current = $row['account_code'];
                while ($current !== '' && isset($parent_map[$current])) {
                    if (isset($seen[$current])) {
                        $errors[] = 'Siklus parent terdeteksi pada akun ' . $row['account_code'] . '.';
                        break 2;
                    }
                    $seen[$current] = TRUE;
                    $current = $parent_map[$current];
                }
            }
        }
        if (!$errors && $rows) {
            $tenant_id = (int) $this->current_user['tenant_id'];
            $user_id = (int) $this->current_user['user_id'];
            $this->db->trans_begin();
            foreach ($rows as $row) {
                $this->db->insert('fin_account', array(
                    'tenant_id' => $tenant_id,
                    'parent_account_id' => NULL,
                    'account_code' => $row['account_code'],
                    'account_name' => $row['account_name'],
                    'account_type' => $row['account_type'],
                    'normal_balance' => $row['normal_balance'],
                    'posting_flag' => $row['posting_flag'],
                    'description' => $row['description'],
                    'active_flag' => $row['active_flag'],
                    'create_on' => date('Y-m-d H:i:s'),
                    'created_by' => $user_id,
                ));
                if ($this->db->affected_rows() !== 1) {
                    $errors[] = 'Baris ' . $row['account_code'] . ' gagal disimpan.';
                    break;
                }
            }
            if (!$errors) {
                $all = $this->db->select('account_id,account_code')->from('fin_account')
                    ->where('tenant_id', $tenant_id)->get()->result_array();
                $ids = array();
                foreach ($all as $row) {
                    $ids[(string) $row['account_code']] = (int) $row['account_id'];
                }
                foreach ($rows as $row) {
                    if ($row['parent_code'] !== '' && isset($ids[$row['parent_code']])) {
                        $this->db->where('tenant_id', $tenant_id)->where('account_code', $row['account_code'])
                            ->update('fin_account', array('parent_account_id' => $ids[$row['parent_code']]));
                    }
                }
            }
            if ($errors || $this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
            } else {
                $this->db->trans_commit();
                $this->session->set_flashdata('finance_status', 'coa_imported');
                $this->session->set_flashdata('coa_import_count', count($rows));
                redirect('/admin/finance/chart_of_accounts', 'location', 303);
                return;
            }
        }
        if (!$rows && !$errors) {
            $errors[] = 'File CSV tidak berisi data akun.';
        }
        $this->session->set_flashdata('finance_status', 'coa_import_error');
        $this->session->set_flashdata('coa_import_errors', $errors);
        redirect('/admin/finance/chart_of_accounts', 'location', 303);
    }

    /** Create an approval-backed cancellation request for a Finance record. */
    public function cancel_request()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            $this->output->set_header('Allow: POST')->set_status_header(405);
            return;
        }
        $source_type = trim((string) $this->input->post('source_type', TRUE));
        $source_id = (int) $this->input->post('source_id', TRUE);
        $menu_code = trim((string) $this->input->post('menu_code', TRUE));
        $reason = trim((string) $this->input->post('reason', TRUE));
        $return_url = trim((string) $this->input->post('return_url', TRUE));
        if ($return_url === '' || strpos($return_url, '/admin/finance/') !== 0) {
            $return_url = '/admin/finance/all_transactions';
        }
        $allowed = array(
            'fin_transaction' => array('fin_income', 'fin_expenses'),
            'fin_sales_invoice' => array('fin_sales_invoices'),
            'fin_purchase_bill' => array('fin_purchase_bills'),
            'fin_customer_payment' => array('fin_customer_payments'),
            'fin_supplier_payment' => array('fin_supplier_payments'),
            'fin_cash_transfer' => array('fin_cash_bank_transfer'),
            'fin_bank_reconciliation' => array('fin_bank_reconciliation'),
            'fin_journal_entry' => array('fin_journal_entries', 'fin_opening_balances'),
        );
        if (!isset($allowed[$source_type]) || !in_array($menu_code, $allowed[$source_type], TRUE)
            || $source_id < 1 || $reason === '') {
            $this->session->set_flashdata('finance_status', 'cancel_invalid');
            redirect($return_url, 'location', 303);
            return;
        }
        if (!$this->has_admin_menu_code($menu_code)
            || (!$this->has_admin_access_code($menu_code . '_update')
                && !$this->has_admin_access_code($menu_code . '_delete'))) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_cancellation_store');
        $result = $this->finance_cancellation_store->request(
            (int) $this->current_user['tenant_id'],
            (int) $this->current_user['user_id'],
            $source_type,
            $source_id,
            $menu_code,
            $reason
        );
        if (!empty($result['ok'])) {
            $status = !empty($result['auto_approved']) ? 'cancel_auto_approved' : 'approval_submitted';
        } else {
            $error = isset($result['error']) ? (string) $result['error'] : 'error';
            $status = $error === 'insufficient_balance' ? 'insufficient_balance' : (in_array($error, array('no_flow', 'no_steps'), TRUE) ? 'approval_steps_missing' : ($error === 'reason' || $error === 'invalid' || $error === 'status' || $error === 'not_found' || $error === 'pending' ? 'not_submittable' : 'approval_error'));
        }
        $this->session->set_flashdata('finance_status', $status);
        redirect($return_url, 'location', 303);
    }

    public function data($slug = '')
    {
        if (in_array($slug, array('all_transactions', 'income', 'expenses'), TRUE)) {
            $this->finance_transaction_data($slug);
            return;
        }
        if (in_array($slug, array('journal_entries', 'opening_balances'), TRUE)) {
            $this->finance_journal_data($slug);
            return;
        }
        if (in_array($slug, array('sales_invoices', 'purchase_bills'), TRUE)) {
            $this->finance_document_data($slug);
            return;
        }
        if (in_array($slug, array('customer_payments', 'supplier_payments'), TRUE)) {
            $this->finance_payment_data($slug);
            return;
        }
        if ($slug === 'cash_bank_transfer') {
            $this->finance_transfer_data();
            return;
        }
        if ($slug === 'bank_reconciliation') {
            $this->finance_reconciliation_data();
            return;
        }
        if ($this->finance_report_menu_code($slug) !== NULL) {
            $this->finance_report_data($slug);
            return;
        }
        if ($slug === 'chart_of_accounts') {
            $this->finance_chart_of_accounts_data();
            return;
        }
        $resource = $this->finance_resource($slug);
        $this->load->library('datatable_server');
        if ($resource === NULL || !$this->finance_menu_allowed($resource)) {
            $this->datatable_server->error(403);
            return;
        }

        $this->load->library('finance_store');
        $store = $this->finance_store;
        if (!$store->available($resource)) {
            $this->datatable_server->error(503);
            return;
        }

        $columns = array();
        $column_offset = $this->has_admin_access_code($resource['menu_code'] . '_update') ? 1 : 0;
        foreach ($resource['columns'] as $index => $column) {
            $columns[$index + $column_offset] = $column['name'];
        }
        $tenant_id = (int) $this->current_user['tenant_id'];
        $filters = $this->finance_list_filters(FALSE, 'none');
        $this->datatable_server->respond(
            $columns,
            function () use ($store, $resource, $tenant_id, $filters) {
                return $store->count($resource, $tenant_id, '', $filters);
            },
            function ($search) use ($store, $resource, $tenant_id, $filters) {
                return $store->count($resource, $tenant_id, $search, $filters);
            },
            function ($search, $length, $start, $order, $direction) use ($store, $resource, $tenant_id, $filters) {
                return $store->page($resource, $tenant_id, $search, $length, $start, $order, $direction, $filters);
            },
            array('max_length' => 50, 'max_search_length' => 120, 'default_order' => $resource['columns'][0]['name'])
        );
    }

    public function new_record($slug = '')
    {
        if (in_array($slug, array('income', 'expenses'), TRUE)) {
            $this->finance_transaction_form($slug, NULL);
            return;
        }
        if (in_array($slug, array('journal_entries', 'opening_balances'), TRUE)) {
            $this->finance_journal_form($slug, NULL);
            return;
        }
        if (in_array($slug, array('sales_invoices', 'purchase_bills'), TRUE)) {
            $this->finance_document_form($slug, NULL);
            return;
        }
        if (in_array($slug, array('customer_payments', 'supplier_payments'), TRUE)) {
            $this->finance_payment_form($slug, NULL);
            return;
        }
        if ($slug === 'cash_bank_transfer') {
            $this->finance_transfer_form(NULL);
            return;
        }
        if ($slug === 'bank_reconciliation') {
            $this->finance_reconciliation_form(NULL);
            return;
        }
        $resource = $this->finance_resource($slug);
        if ($resource === NULL || !$this->finance_menu_allowed($resource)) {
            $this->finance_forbidden();
            return;
        }
        if (!$this->has_admin_access_code($resource['menu_code'] . '_add')) {
            $this->finance_forbidden();
            return;
        }
        $this->finance_form($resource, NULL);
    }

    public function view($slug = '', $id = NULL)
    {
        if (in_array($slug, array('sales_invoices', 'purchase_bills'), TRUE)
            && is_numeric($id) && (int) $id > 0) {
            $this->finance_document_view($slug, (int) $id);
            return;
        }
        show_404();
    }

    public function edit($slug = '', $id = NULL)
    {
        if (in_array($slug, array('income', 'expenses'), TRUE)) {
            $this->finance_transaction_form($slug, $id);
            return;
        }
        if (in_array($slug, array('journal_entries', 'opening_balances'), TRUE)) {
            $this->finance_journal_form($slug, $id);
            return;
        }
        if (in_array($slug, array('sales_invoices', 'purchase_bills'), TRUE)) {
            $this->finance_document_form($slug, $id);
            return;
        }
        if (in_array($slug, array('customer_payments', 'supplier_payments'), TRUE)) {
            $this->finance_payment_form($slug, $id);
            return;
        }
        if ($slug === 'cash_bank_transfer') {
            $this->finance_transfer_form($id);
            return;
        }
        if ($slug === 'bank_reconciliation') {
            $this->finance_reconciliation_form($id);
            return;
        }
        $resource = $this->finance_resource($slug);
        if ($resource === NULL || !$this->finance_menu_allowed($resource)) {
            $this->finance_forbidden();
            return;
        }
        if (!$this->has_admin_access_code($resource['menu_code'] . '_update')
            || !is_numeric($id) || (int) $id < 1) {
            $this->finance_forbidden();
            return;
        }
        $this->finance_form($resource, (int) $id);
    }

    public function post($slug = '', $id = NULL)
    {
        if (in_array($slug, array('sales_invoices', 'purchase_bills'), TRUE)) {
            $this->finance_document_post($slug, $id);
            return;
        }
        if (in_array($slug, array('customer_payments', 'supplier_payments'), TRUE)) {
            $this->finance_payment_post($slug, $id);
            return;
        }
        if ($slug === 'cash_bank_transfer') {
            $this->finance_transfer_post($id);
            return;
        }
        if ($slug === 'bank_reconciliation') {
            $this->finance_reconciliation_complete($id);
            return;
        }
        if (in_array($slug, array('journal_entries', 'opening_balances'), TRUE)) {
            $this->finance_journal_post($slug, $id);
            return;
        }
        if (!in_array($slug, array('income', 'expenses'), TRUE)
            || !is_numeric($id) || (int) $id < 1
            || $this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $menu_code = $slug === 'income' ? 'fin_income' : 'fin_expenses';
        if (!$this->has_admin_menu_code($menu_code) || !$this->has_admin_access_code($menu_code . '_update')) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_transaction_store');
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $result = $this->finance_transaction_store->post(
            (int) $this->current_user['tenant_id'],
            (int) $this->current_user['user_id'],
            (int) $id
        );
        $this->db->db_debug = $previous_db_debug;
        $this->session->set_flashdata('finance_status', $result);
        redirect('/admin/finance/' . $slug, 'location', 303);
    }

    public function submit_approval($slug = '', $id = 0)
    {
        if (!in_array($slug, array('income', 'expenses', 'cash_bank_transfer', 'opening_balances', 'journal_entries', 'sales_invoices', 'purchase_bills', 'customer_payments', 'supplier_payments', 'bank_reconciliation'), TRUE)
            || !is_numeric($id) || (int) $id < 1
            || $this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $menu_code = $slug === 'income' || $slug === 'expenses' ? 'fin_' . $slug
            : ($slug === 'cash_bank_transfer' ? 'fin_cash_bank_transfer'
                : ($slug === 'opening_balances' ? 'fin_opening_balances'
                    : ($slug === 'journal_entries' ? 'fin_journal_entries'
                        : ($slug === 'bank_reconciliation' ? 'fin_bank_reconciliation'
                            : ($slug === 'sales_invoices' || $slug === 'purchase_bills'
                                ? $this->finance_document_menu_code($slug)
                                : $this->finance_payment_menu_code($slug))))));
        if (!$this->has_admin_menu_code($menu_code)
            || !$this->has_admin_access_code($menu_code . '_update')) {
            $this->finance_forbidden();
            return;
        }
        if ($slug === 'cash_bank_transfer') {
            $result = $this->finance_transfer_start_approval((int) $id);
        } elseif ($slug === 'opening_balances') {
            $result = $this->finance_opening_balance_start_approval((int) $id);
        } elseif ($slug === 'journal_entries') {
            $result = $this->finance_manual_journal_start_approval((int) $id);
        } elseif ($slug === 'sales_invoices' || $slug === 'purchase_bills') {
            $result = $this->finance_document_start_approval($slug, (int) $id);
        } elseif ($slug === 'customer_payments' || $slug === 'supplier_payments') {
            $result = $this->finance_payment_start_approval($slug, (int) $id);
        } elseif ($slug === 'bank_reconciliation') {
            $result = $this->finance_reconciliation_start_approval((int) $id);
        } else {
            $result = $this->finance_transaction_start_approval($slug, (int) $id);
        }
        $this->session->set_flashdata('finance_status', $result);
        redirect('/admin/finance/' . $slug, 'location', 303);
    }

    private function finance_approval_amount($amount)
    {
        return number_format((float) $amount, 2, '.', ',');
    }

    private function finance_transaction_start_approval($slug, $id)
    {
        $this->load->library('finance_transaction_store');
        $store = $this->finance_transaction_store;
        $tenant_id = (int) $this->current_user['tenant_id'];
        $record = $store->available() ? $store->find($tenant_id, (int) $id) : FALSE;
        if (!is_array($record)) {
            return 'not_found';
        }
        if (!in_array($record['status'], array('draft', 'revise'), TRUE)) {
            return 'not_submittable';
        }
        $type = $record['transaction_type'] === 'income' ? 'income' : 'expense';
        $flow_code = $type === 'income' ? 'finance_income' : 'finance_expenses';
        $subject = $type === 'income' ? 'Income' : 'Expense';
        $line = is_array($record['line']) ? $record['line'] : array();
        $cash_label = $store->lookup_label($tenant_id, 'cash_account', (int) $record['cash_account_id']);
        $account_kind = $type === 'income' ? 'revenue_account' : 'expense_account';
        $category_kind = $type === 'income' ? 'income_category' : 'expense_category';
        $account_label = !empty($line['account_id']) ? $store->lookup_label($tenant_id, $account_kind, (int) $line['account_id']) : '';
        $category_label = !empty($line['category_id']) ? $store->lookup_label($tenant_id, $category_kind, (int) $line['category_id']) : '';
        $details = 'Nomor: ' . (string) $record['transaction_number']
            . "\n" . 'Jenis: ' . ($type === 'income' ? 'Income' : 'Expense')
            . "\n" . 'Tanggal: ' . (string) $record['transaction_on']
            . "\n" . 'Jumlah: ' . $this->finance_approval_amount($record['total_amount']) . ' ' . (string) $record['currency_code']
            . "\n" . 'Akun Kas/Bank: ' . $cash_label
            . "\n" . 'Akun: ' . $account_label;
        if ($category_label !== '') {
            $details .= "\n" . 'Kategori: ' . $category_label;
        }
        if (!empty($line['description'])) {
            $details .= "\n" . 'Keterangan: ' . (string) $line['description'];
        }
        $previous_status = $record['status'];
        $this->db->where('tenant_id', $tenant_id)->where('transaction_id', (int) $id)
            ->update('fin_transaction', array('status' => 'pending_approval'));
        $this->load->library('approval_workflow_store');
        $approval = $this->approval_workflow_store;
        if (!$approval->available()) {
            $this->db->where('tenant_id', $tenant_id)->where('transaction_id', (int) $id)
                ->update('fin_transaction', array('status' => $previous_status));
            return 'approval_unavailable';
        }
        $result = $approval->submit($flow_code, $subject, $details, 'fin_transaction', (int) $id, (int) $record['created_by']);
        if (!is_array($result) || !isset($result['status'])) {
            $this->db->where('tenant_id', $tenant_id)->where('transaction_id', (int) $id)
                ->update('fin_transaction', array('status' => $previous_status));
            return 'approval_error';
        }
        $request_id = isset($result['request_id']) ? (int) $result['request_id'] : 0;
        $approved = $result['status'] === 'auto_approved';
        $this->db->where('tenant_id', $tenant_id)->where('transaction_id', (int) $id)
            ->update('fin_transaction', array(
                'status' => $approved ? 'approved' : 'pending_approval',
                'approval_request_id' => $request_id > 0 ? $request_id : NULL,
            ));
        return $approved ? 'approval_auto_approved' : 'approval_submitted';
    }

    private function finance_transfer_start_approval($id)
    {
        $this->load->library('finance_transfer_store');
        $store = $this->finance_transfer_store;
        $tenant_id = (int) $this->current_user['tenant_id'];
        $record = $store->available() ? $store->find($tenant_id, (int) $id) : FALSE;
        if (!is_array($record)) {
            return 'not_found';
        }
        if (!in_array($record['status'], array('draft', 'revise'), TRUE)) {
            return 'not_submittable';
        }
        $previous_status = $record['status'];
        $this->db->where('tenant_id', $tenant_id)->where('cash_transfer_id', (int) $id)
            ->update('fin_cash_transfer', array('status' => 'pending_approval'));
        $this->load->library('approval_workflow_store');
        $approval = $this->approval_workflow_store;
        if (!$approval->available()) {
            $this->db->where('tenant_id', $tenant_id)->where('cash_transfer_id', (int) $id)
                ->update('fin_cash_transfer', array('status' => $previous_status));
            return 'approval_unavailable';
        }
        $from_label = $store->cash_label($tenant_id, (int) $record['from_cash_account_id']);
        $to_label = $store->cash_label($tenant_id, (int) $record['to_cash_account_id']);
        $subject = 'Transfer Balance';
        $details = 'Nomor: ' . (string) $record['transfer_number']
            . "\n" . 'Dari: ' . $from_label
            . "\n" . 'Nilai: ' . $this->finance_approval_amount($record['from_amount']) . ' ' . (string) $record['from_currency']
            . "\n" . 'Ke: ' . $to_label
            . "\n" . 'Nilai: ' . $this->finance_approval_amount($record['to_amount']) . ' ' . (string) $record['to_currency'];
        $result = $approval->submit('finance_transfer', $subject, $details, 'fin_cash_transfer', (int) $id, (int) $record['created_by']);
        if (!is_array($result) || !isset($result['status'])) {
            $this->db->where('tenant_id', $tenant_id)->where('cash_transfer_id', (int) $id)
                ->update('fin_cash_transfer', array('status' => $previous_status));
            return 'approval_error';
        }
        $request_id = isset($result['request_id']) ? (int) $result['request_id'] : 0;
        $approved = $result['status'] === 'auto_approved';
        $this->db->where('tenant_id', $tenant_id)->where('cash_transfer_id', (int) $id)
            ->update('fin_cash_transfer', array(
                'status' => $approved ? 'approved' : 'pending_approval',
                'approval_request_id' => $request_id > 0 ? $request_id : NULL,
            ));
        return $approved ? 'approval_auto_approved' : 'approval_submitted';
    }

    private function finance_opening_balance_start_approval($id)
    {
        $this->load->library('finance_journal_store');
        $store = $this->finance_journal_store;
        $tenant_id = (int) $this->current_user['tenant_id'];
        $record = $store->available() ? $store->find($tenant_id, (int) $id, 'opening_balance') : FALSE;
        if (!is_array($record)) {
            return 'not_found';
        }
        if (!in_array($record['status'], array('draft', 'revise'), TRUE)) {
            return 'not_submittable';
        }
        $this->load->library('approval_workflow_store');
        $approval = $this->approval_workflow_store;
        if (!$approval->available()) {
            return 'approval_unavailable';
        }
        $previous_status = $record['status'];
        $this->db->where('tenant_id', $tenant_id)->where('journal_entry_id', (int) $id)
            ->where('source_type', 'opening_balance')
            ->update('fin_journal_entry', array('status' => 'pending_approval'));
        $settings = $this->db->select('base_currency')->where('tenant_id', $tenant_id)->limit(1)->get('fin_settings');
        $base_currency = $settings !== FALSE && $settings->num_rows() ? (string) $settings->row()->base_currency : '';
        $subject = 'Opening Balance';
        $details = 'Opening Balance ' . (string) $record['journal_number'] . ' · ' . $this->finance_approval_amount($record['debit_total']) . ' ' . $base_currency;
        $result = $approval->submit('finance_begining_balance', $subject, $details, 'fin_journal_entry', (int) $id, (int) $record['created_by']);
        if (!is_array($result) || !isset($result['status'])) {
            $this->db->where('tenant_id', $tenant_id)->where('journal_entry_id', (int) $id)
                ->where('source_type', 'opening_balance')
                ->update('fin_journal_entry', array('status' => $previous_status));
            return 'approval_error';
        }
        $request_id = isset($result['request_id']) ? (int) $result['request_id'] : 0;
        $approved = $result['status'] === 'auto_approved';
        $this->db->where('tenant_id', $tenant_id)->where('journal_entry_id', (int) $id)
            ->where('source_type', 'opening_balance')
            ->update('fin_journal_entry', array(
                'status' => $approved ? 'approved' : 'pending_approval',
                'approval_request_id' => $request_id > 0 ? $request_id : NULL,
            ));
        return $approved ? 'approval_auto_approved' : 'approval_submitted';
    }

    private function finance_manual_journal_start_approval($id)
    {
        $this->load->library('finance_journal_store');
        $store = $this->finance_journal_store;
        $tenant_id = (int) $this->current_user['tenant_id'];
        $record = $store->available() ? $store->find($tenant_id, (int) $id, 'manual') : FALSE;
        if (!is_array($record)) return 'not_found';
        if (!in_array($record['status'], array('draft', 'revise'), TRUE)) return 'not_submittable';
        $this->load->library('approval_workflow_store');
        $approval = $this->approval_workflow_store;
        if (!$approval->available()) return 'approval_unavailable';
        $previous_status = $record['status'];
        $this->db->where('tenant_id', $tenant_id)->where('journal_entry_id', (int) $id)
            ->where('source_type', 'manual')->update('fin_journal_entry', array('status' => 'pending_approval'));
        $subject = 'Manual Journal';
        $details = 'Nomor: ' . (string) $record['journal_number']
            . "\n" . 'Tanggal: ' . (string) $record['journal_date']
            . "\n" . 'Total Debit: ' . $this->finance_approval_amount($record['debit_total'])
            . "\n" . 'Total Kredit: ' . $this->finance_approval_amount($record['credit_total'])
            . "\n" . 'Keterangan: ' . (string) $record['description'];
        $result = $approval->submit('finance_manual_journal', $subject, $details, 'fin_journal_entry', (int) $id, (int) $record['created_by']);
        if (!is_array($result) || !isset($result['status'])) {
            $this->db->where('tenant_id', $tenant_id)->where('journal_entry_id', (int) $id)
                ->where('source_type', 'manual')->update('fin_journal_entry', array('status' => $previous_status));
            return 'approval_error';
        }
        $request_id = isset($result['request_id']) ? (int) $result['request_id'] : 0;
        $approved = $result['status'] === 'auto_approved';
        $this->db->where('tenant_id', $tenant_id)->where('journal_entry_id', (int) $id)
            ->where('source_type', 'manual')->update('fin_journal_entry', array(
                'status' => $approved ? 'approved' : 'pending_approval',
                'approval_request_id' => $request_id > 0 ? $request_id : NULL,
            ));
        return $approved ? 'approval_auto_approved' : 'approval_submitted';
    }

    private function finance_document_start_approval($slug, $id)
    {
        $kind = $slug === 'sales_invoices' ? 'sales' : 'purchase';
        $this->load->library('finance_document_store');
        $store = $this->finance_document_store;
        $tenant_id = (int) $this->current_user['tenant_id'];
        $record = $store->available($kind) ? $store->find($tenant_id, $kind, (int) $id) : FALSE;
        if (!is_array($record)) return 'not_found';
        if (!in_array($record['status'], array('draft', 'revise'), TRUE)) return 'not_submittable';
        $flow_code = $kind === 'sales' ? 'finance_sales_invoice' : 'finance_purchase_bill';
        $subject = $kind === 'sales' ? 'Sales Invoice' : 'Purchase Bill';
        $this->load->library('approval_workflow_store');
        $approval = $this->approval_workflow_store;
        if (!$approval->available()) return 'approval_unavailable';
        $spec = $kind === 'sales'
            ? array('number' => 'invoice_number', 'date' => 'invoice_date', 'table' => 'fin_sales_invoice', 'id' => 'sales_invoice_id')
            : array('number' => 'bill_number', 'date' => 'bill_date', 'table' => 'fin_purchase_bill', 'id' => 'purchase_bill_id');
        $previous_status = $record['status'];
        $this->db->where('tenant_id', $tenant_id)->where($spec['id'], (int) $id)
            ->update($spec['table'], array('status' => 'pending_approval'));
        $details = 'Nomor: ' . (string) $record[$spec['number']]
            . "\n" . 'Tanggal: ' . (string) $record[$spec['date']]
            . "\n" . 'Jumlah: ' . $this->finance_approval_amount($record['total_amount']) . ' ' . (string) $record['currency_code']
            . "\n" . 'Jatuh Tempo: ' . (string) $record['due_date']
            . "\n" . 'Keterangan: ' . (string) $record['memo'];
        $result = $approval->submit($flow_code, $subject, $details, $spec['table'], (int) $id, (int) $record['created_by']);
        if (!is_array($result) || !isset($result['status'])) {
            $this->db->where('tenant_id', $tenant_id)->where($spec['id'], (int) $id)
                ->update($spec['table'], array('status' => $previous_status));
            return 'approval_error';
        }
        $request_id = isset($result['request_id']) ? (int) $result['request_id'] : 0;
        $approved = $result['status'] === 'auto_approved';
        $this->db->where('tenant_id', $tenant_id)->where($spec['id'], (int) $id)
            ->update($spec['table'], array(
                'status' => $approved ? 'approved' : 'pending_approval',
                'approval_request_id' => $request_id > 0 ? $request_id : NULL,
            ));
        return $approved ? 'approval_auto_approved' : 'approval_submitted';
    }

    private function finance_payment_start_approval($slug, $id)
    {
        $kind = $slug === 'customer_payments' ? 'customer' : 'supplier';
        $this->load->library('finance_payment_store');
        $store = $this->finance_payment_store;
        $tenant = (int) $this->current_user['tenant_id'];
        $record = $store->available($kind) ? $store->find($tenant, $kind, (int) $id) : FALSE;
        if (!is_array($record)) return 'not_found';
        if (!in_array($record['status'], array('draft', 'revise'), TRUE)) return 'not_submittable';
        $flow_code = $kind === 'customer' ? 'finance_customer_payment' : 'finance_supplier_payment';
        $subject = $kind === 'customer' ? 'Customer Payment' : 'Supplier Payment';
        $this->load->library('approval_workflow_store');
        $approval = $this->approval_workflow_store;
        if (!$approval->available()) return 'approval_unavailable';
        $table = $kind === 'customer' ? 'fin_customer_payment' : 'fin_supplier_payment';
        $id_column = $kind === 'customer' ? 'customer_payment_id' : 'supplier_payment_id';
        $previous_status = $record['status'];
        $this->db->where('tenant_id', $tenant)->where($id_column, (int) $id)
            ->update($table, array('status' => 'pending_approval'));
        $details = 'Nomor: ' . (string) $record['payment_number']
            . "\n" . 'Tanggal: ' . (string) $record['payment_date']
            . "\n" . 'Jumlah: ' . $this->finance_approval_amount($record['amount']) . ' ' . (string) $record['currency_code']
            . "\n" . 'Referensi: ' . (string) $record['reference'];
        $result = $approval->submit($flow_code, $subject, $details, $table, (int) $id, (int) $record['created_by']);
        if (!is_array($result) || !isset($result['status'])) {
            $this->db->where('tenant_id', $tenant)->where($id_column, (int) $id)
                ->update($table, array('status' => $previous_status));
            return 'approval_error';
        }
        $request_id = isset($result['request_id']) ? (int) $result['request_id'] : 0;
        $approved = $result['status'] === 'auto_approved';
        $this->db->where('tenant_id', $tenant)->where($id_column, (int) $id)
            ->update($table, array(
                'status' => $approved ? 'approved' : 'pending_approval',
                'approval_request_id' => $request_id > 0 ? $request_id : NULL,
            ));
        return $approved ? 'approval_auto_approved' : 'approval_submitted';
    }

    private function finance_reconciliation_start_approval($id)
    {
        $this->load->library('finance_reconciliation_store');
        $store = $this->finance_reconciliation_store;
        $tenant = (int) $this->current_user['tenant_id'];
        $record = $store->available() ? $store->find($tenant, (int) $id) : FALSE;
        if (!is_array($record)) return 'not_found';
        if (!in_array($record['status'], array('draft', 'revise'), TRUE)) return 'not_submittable';
        $this->load->library('approval_workflow_store');
        $approval = $this->approval_workflow_store;
        if (!$approval->available()) return 'approval_unavailable';
        $previous_status = $record['status'];
        $this->db->where('tenant_id', $tenant)->where('reconciliation_id', (int) $id)
            ->update('fin_bank_reconciliation', array('status' => 'pending_approval'));
        $details = 'Referensi: ' . (string) $record['statement_reference']
            . "\n" . 'Tanggal Rekening: ' . (string) $record['statement_date']
            . "\n" . 'Saldo Awal: ' . $this->finance_approval_amount($record['opening_balance'])
            . "\n" . 'Saldo Akhir: ' . $this->finance_approval_amount($record['closing_balance']);
        $result = $approval->submit('finance_bank_reconciliation', 'Bank Reconciliation', $details, 'fin_bank_reconciliation', (int) $id, (int) $record['created_by']);
        if (!is_array($result) || !isset($result['status'])) {
            $this->db->where('tenant_id', $tenant)->where('reconciliation_id', (int) $id)
                ->update('fin_bank_reconciliation', array('status' => $previous_status));
            return 'approval_error';
        }
        $request_id = isset($result['request_id']) ? (int) $result['request_id'] : 0;
        $approved = $result['status'] === 'auto_approved';
        $this->db->where('tenant_id', $tenant)->where('reconciliation_id', (int) $id)
            ->update('fin_bank_reconciliation', array(
                'status' => $approved ? 'approved' : 'pending_approval',
                'approval_request_id' => $request_id > 0 ? $request_id : NULL,
            ));
        return $approved ? 'approval_auto_approved' : 'approval_submitted';
    }

    public function lookup($slug = '', $kind = '')
    {
        if ($slug === 'bank_reconciliation') {
            if (!$this->has_admin_menu_code('fin_bank_reconciliation')) {
                $this->output->set_status_header(403);
                $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
                return;
            }
            $term = $this->input->get('term', TRUE);
            $term = is_string($term) && strlen($term) <= 120 ? $term : '';
            if ($kind === 'cash_account') {
                $this->load->library('finance_transfer_store');
                $rows = $this->finance_transfer_store->lookup_cash((int)$this->current_user['tenant_id'], $term);
            } elseif ($kind === 'journal_line') {
                $cash_id = $this->input->get('cash_account_id', TRUE);
                $this->load->library('finance_reconciliation_store');
                $rows = $this->finance_reconciliation_store->lookup_lines((int)$this->current_user['tenant_id'], (int)$cash_id, $term);
            } else {
                $this->output->set_status_header(404);
                $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
                return;
            }
            $this->output->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($rows, JSON_INVALID_UTF8_SUBSTITUTE));
            return;
        }
        if ($slug === 'cash_bank_transfer' && $kind === 'cash_account') {
            if (!$this->has_admin_menu_code('fin_cash_bank_transfer')) {
                $this->output->set_status_header(403);
                $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
                return;
            }
            $term = $this->input->get('term', TRUE);
            $this->load->library('finance_transfer_store');
            $rows = $this->finance_transfer_store->lookup_cash(
                (int) $this->current_user['tenant_id'],
                is_string($term) && strlen($term) <= 120 ? $term : ''
            );
            $this->output->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($rows, JSON_INVALID_UTF8_SUBSTITUTE));
            return;
        }
        if (in_array($slug, array('customer_payments', 'supplier_payments'), TRUE)) {
            $menu_code = $this->finance_payment_menu_code($slug);
            if (!$this->has_admin_menu_code($menu_code) || !in_array($kind, array('cash_account', 'document'), TRUE)) {
                $this->output->set_status_header(403);
                $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
                return;
            }
            $term = $this->input->get('term', TRUE);
            $term = is_string($term) && strlen($term) <= 120 ? $term : '';
            $this->load->library('finance_payment_store');
            $results = $this->finance_payment_store->lookup(
                (int) $this->current_user['tenant_id'],
                $kind === 'cash_account' ? 'cash_account' : ($slug === 'customer_payments' ? 'customer' : 'supplier'),
                $term
            );
            $this->output->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($results, JSON_INVALID_UTF8_SUBSTITUTE));
            return;
        }
        if (in_array($slug, array('sales_invoices', 'purchase_bills'), TRUE)) {
            $menu_code = $this->finance_document_menu_code($slug);
            if (!$this->has_admin_menu_code($menu_code)) {
                $this->output->set_status_header(403);
                $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
                return;
            }
            $allowed = array('party', 'account', 'tax');
            if (!in_array($kind, $allowed, TRUE)) {
                $this->output->set_status_header(404);
                $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
                return;
            }
            $term = $this->input->get('term', TRUE);
            $term = is_string($term) && strlen($term) <= 120 ? $term : '';
            $this->load->library('finance_document_store');
            $results = $this->finance_document_store->lookup(
                (int) $this->current_user['tenant_id'],
                $slug === 'sales_invoices' ? 'sales' : 'purchase',
                $kind,
                $term
            );
            $this->output->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($results, JSON_INVALID_UTF8_SUBSTITUTE));
            return;
        }
        if ($slug === 'accounting_periods' && $kind === 'fiscal_year') {
            if (!$this->has_admin_menu_code('fin_accounting_periods')) {
                $this->output->set_status_header(403);
                $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
                return;
            }
            $term = $this->input->get('term', TRUE);
            $term = is_string($term) && strlen($term) <= 120 ? $term : '';
            $this->db->select('fiscal_year_id,year_code,year_name,start_on,end_on')->from('fin_fiscal_year')
                ->where('tenant_id', (int) $this->current_user['tenant_id']);
            if ($term !== '') {
                $this->db->group_start()->like('year_code', $term)->or_like('year_name', $term)->group_end();
            }
            $rows = $this->db->order_by('year_code', 'ASC')->limit(20)->get()->result_array();
            $results = array();
            foreach ($rows as $row) {
                $label = $row['year_code'] . ' — ' . $row['year_name'] . ' (' . $row['start_on'] . ' s/d ' . $row['end_on'] . ')';
                $results[] = array('id' => (int) $row['fiscal_year_id'], 'label' => $label, 'value' => $label);
            }
            $this->output->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($results, JSON_INVALID_UTF8_SUBSTITUTE));
            return;
        }
        if (in_array($slug, array('tax_configuration', 'cash_accounts', 'bank_accounts', 'chart_of_accounts'), TRUE) && $kind === 'account') {
            $menus = array(
                'tax_configuration' => 'fin_tax_configuration',
                'cash_accounts' => 'fin_cash_accounts',
                'bank_accounts' => 'fin_bank_accounts',
                'chart_of_accounts' => 'fin_chart_of_accounts',
            );
            if (!$this->has_admin_menu_code($menus[$slug])) {
                $this->output->set_status_header(403);
                $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
                return;
            }
            $term = $this->input->get('term', TRUE);
            $term = is_string($term) && strlen($term) <= 120 ? $term : '';
            $exclude_id = (int) $this->input->get('exclude_id', TRUE);
            $this->db->select('account_id,account_code,account_name')->from('fin_account')
                ->where('tenant_id', (int) $this->current_user['tenant_id'])->where('active_flag', 1);
            if (in_array($slug, array('cash_accounts', 'bank_accounts'), TRUE)) {
                $this->db->where('account_type', 'asset')->where('posting_flag', 1);
            } elseif ($slug === 'tax_configuration') {
                $this->db->where_in('account_type', array('asset', 'liability'))->where('posting_flag', 1);
            } elseif ($slug === 'chart_of_accounts') {
                $this->db->where('posting_flag', 0);
            }
            if ($exclude_id > 0) {
                $this->db->where('account_id !=', $exclude_id);
            }
            if ($term !== '') {
                $this->db->group_start()->like('account_code', $term)->or_like('account_name', $term)->group_end();
            }
            $rows = $this->db->order_by('account_code', 'ASC')->limit(20)->get()->result_array();
            $results = array();
            foreach ($rows as $row) {
                $label = $row['account_code'] . ' — ' . $row['account_name'];
                $results[] = array('id' => (int) $row['account_id'], 'label' => $label, 'value' => $label);
            }
            $this->output->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($results, JSON_INVALID_UTF8_SUBSTITUTE));
            return;
        }
        if ($slug === 'financial_settings' && $kind === 'account') {
            if (!$this->has_admin_menu_code('fin_financial_settings')) {
                $this->output->set_status_header(403);
                $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
                return;
            }
            $term = $this->input->get('term', TRUE);
            $term = is_string($term) && strlen($term) <= 120 ? $term : '';
            $this->load->library('finance_journal_store');
            $results = $this->finance_journal_store->lookup_accounts((int) $this->current_user['tenant_id'], $term);
            $this->output->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($results, JSON_INVALID_UTF8_SUBSTITUTE));
            return;
        }
        if (in_array($slug, array('journal_entries', 'opening_balances'), TRUE) && $kind === 'account') {
            $menu_code = $this->finance_journal_menu_code($slug);
            if (!$this->has_admin_menu_code($menu_code)) {
                $this->output->set_status_header(403);
                $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
                return;
            }
            $term = $this->input->get('term', TRUE);
            $term = is_string($term) && strlen($term) <= 120 ? $term : '';
            $this->load->library('finance_journal_store');
            $results = $this->finance_journal_store->lookup_accounts((int) $this->current_user['tenant_id'], $term);
            $this->output->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($results, JSON_INVALID_UTF8_SUBSTITUTE));
            return;
        }
        if (!in_array($slug, array('income', 'expenses'), TRUE)) {
            show_404();
            return;
        }
        $menu_code = $slug === 'income' ? 'fin_income' : 'fin_expenses';
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->output->set_status_header(403);
            $this->output->set_content_type('application/json', 'utf-8')->set_output('[]');
            return;
        }
        $term = $this->input->get('term', TRUE);
        if (!is_string($term) || strlen($term) > 120) {
            $term = '';
        }
        $this->load->library('finance_transaction_store');
        $results = $this->finance_transaction_store->lookup(
            (int) $this->current_user['tenant_id'],
            $kind,
            $term
        );
        $this->output->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($results, JSON_INVALID_UTF8_SUBSTITUTE));
    }

    private function finance_transaction_index($slug)
    {
        $menu_code = $this->finance_transaction_menu_code($slug);
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_transaction_store');
        $store = $this->finance_transaction_store;
        $filter_type = $slug === 'income' ? 'income' : ($slug === 'expenses' ? 'expense' : 'all');
        $this->render_admin(
            'admin/finance/transaction_index',
            'finance_' . $slug,
            '/admin/finance/' . $slug,
            array(
                'finance_transaction_slug' => $slug,
                'finance_transaction_available' => $store->available()
                    && ($slug !== 'all_transactions' || $store->all_available()),
                'finance_transaction_can_add' => $slug !== 'all_transactions'
                    && $this->has_admin_access_code($menu_code . '_add'),
                'finance_transaction_can_update' => $slug !== 'all_transactions'
                    && $this->has_admin_access_code($menu_code . '_update'),
                'finance_transaction_write_available' => $this->finance_write_available(),
                'finance_status' => (string) $this->session->flashdata('finance_status'),
                'finance_filter_config' => $this->finance_transaction_filter_config($filter_type),
            )
        );
    }

    private function finance_transaction_filter_config($type = 'all')
    {
        $tenant = (int) $this->current_user['tenant_id'];
        $currencies = $this->db->distinct()->select('currency_code')->from('fin_transaction')
            ->where('tenant_id', $tenant)->order_by('currency_code', 'ASC')->get();
        $currency_options = array();
        if ($currencies !== FALSE) {
            foreach ($currencies->result_array() as $row) {
                $code = strtoupper((string) $row['currency_code']);
                if ($code !== '') $currency_options[$code] = $code;
            }
        }
        $users = $this->db->select('user_id, full_name, username')->from('app_user')
            ->where('tenant_id', $tenant)->order_by('full_name', 'ASC')->get();
        $user_options = array();
        if ($users !== FALSE) {
            foreach ($users->result_array() as $row) {
                $name = !empty($row['full_name']) ? $row['full_name'] : $row['username'];
                $user_options[(string) $row['user_id']] = $name;
            }
        }
        $type_options = array(
            'income' => isset($this->lang->language['finance_tx_type_income']) ? $this->lang->language['finance_tx_type_income'] : 'Income',
            'expense' => isset($this->lang->language['finance_tx_type_expense']) ? $this->lang->language['finance_tx_type_expense'] : 'Expense',
            'sales_invoice' => 'Sales Invoice',
            'purchase_bill' => 'Purchase Bill',
            'customer_payment' => 'Customer Payment',
            'supplier_payment' => 'Supplier Payment',
            'cash_transfer' => 'Cash Transfer',
            'manual' => 'Manual Journal',
            'opening_balance' => 'Opening Balance',
        );
        $field_filters = array(
            array('type' => 'range', 'name' => 'total_amount', 'label' => isset($this->lang->language['finance_column_total']) ? $this->lang->language['finance_column_total'] : 'Total', 'min_name' => 'amount_min', 'max_name' => 'amount_max'),
            array('type' => 'select', 'name' => 'currency_code', 'label' => isset($this->lang->language['finance_column_currency']) ? $this->lang->language['finance_column_currency'] : 'Currency', 'options' => $currency_options),
            array('type' => 'select', 'name' => 'created_by', 'label' => isset($this->lang->language['finance_created_by']) ? $this->lang->language['finance_created_by'] : 'Created by', 'options' => $user_options),
        );
        if ($type === 'all') {
            array_unshift($field_filters, array('type' => 'select', 'name' => 'transaction_type', 'label' => isset($this->lang->language['finance_tx_column_type']) ? $this->lang->language['finance_tx_column_type'] : 'Type', 'options' => $type_options));
        } else {
            array_unshift($field_filters, array('type' => 'text', 'name' => 'description', 'label' => isset($this->lang->language['finance_field_description']) ? $this->lang->language['finance_field_description'] : 'Description'));
        }
        return $this->finance_filter_panel->config(array(
            'date' => TRUE,
            'date_preset' => 'transaction',
            'status_preset' => 'transaction',
            'field_filters' => $field_filters,
        ));
    }

    private function finance_transaction_data($slug)
    {
        $menu_code = $this->finance_transaction_menu_code($slug);
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->datatable_server->error(403);
            return;
        }
        $this->load->library('finance_transaction_store');
        $store = $this->finance_transaction_store;
        if (!$store->available() || ($slug === 'all_transactions' && !$store->all_available())) {
            $this->datatable_server->error(503);
            return;
        }
        $type = $slug === 'income' ? 'income' : ($slug === 'expenses' ? 'expense' : 'all');
        $tenant_id = (int) $this->current_user['tenant_id'];
        $filters = $this->finance_filter_panel->filters($this->finance_transaction_filter_config($type));
        $columns = array(
            1 => 'transaction_number',
            2 => ($type === 'all' ? 'transaction_type' : 'description'),
            3 => 'transaction_on',
            4 => 'create_on',
            5 => 'total_amount',
            6 => 'currency_code',
            7 => 'created_by_name',
            8 => 'status',
        );
        $this->datatable_server->respond(
            $columns,
            function () use ($store, $tenant_id, $type, $filters) {
                return $store->count($tenant_id, $type, '', $filters);
            },
            function ($search) use ($store, $tenant_id, $type, $filters) {
                return $store->count($tenant_id, $type, $search, $filters);
            },
            function ($search, $length, $start, $order, $direction) use ($store, $tenant_id, $type, $filters) {
                return $store->page($tenant_id, $type, $search, $length, $start, $order, $direction, $filters);
            },
            array('max_length' => 50, 'max_search_length' => 120, 'default_order' => 'create_on')
        );
    }

    private function finance_transaction_form($slug, $id)
    {
        $menu_code = $this->finance_transaction_menu_code($slug);
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $is_new = $id === NULL;
        $required_access = $menu_code . ($is_new ? '_add' : '_update');
        if (!$this->has_admin_access_code($required_access)
            || (!$is_new && (!is_numeric($id) || (int) $id < 1))) {
            $this->finance_forbidden();
            return;
        }

        $type = $slug === 'income' ? 'income' : 'expense';
        $this->load->library('finance_transaction_store');
        $store = $this->finance_transaction_store;
        $available = $store->available();
        $record = array(
            'transaction_on' => date('Y-m-d'),
            'transaction_type' => $type,
            'cash_account_id' => '',
            'account_id' => '',
            'category_id' => '',
            'tax_id' => '',
            'department_id' => '',
            'amount' => '',
            'exchange_rate' => '1',
            'description' => '',
            'status' => 'draft',
        );
        $lookup_labels = array();
        if (!$is_new && $available) {
            $saved = $store->find((int) $this->current_user['tenant_id'], (int) $id);
            if ($saved === NULL) {
                show_404();
                return;
            }
            if ($saved === FALSE || $saved['transaction_type'] !== $type) {
                $available = FALSE;
            } elseif (!in_array($saved['status'], array('draft', 'revise'), TRUE)) {
                show_error('Only draft or revised Finance transactions can be edited.', 409);
                return;
            } else {
                $record = array_merge($record, $saved);
                if (!empty($saved['line'])) {
                    $record['account_id'] = $saved['line']['account_id'];
                    $record['category_id'] = $saved['line']['category_id'];
                    $record['tax_id'] = $saved['line']['tax_id'];
                    $record['department_id'] = $saved['line']['department_id'];
                    $record['amount'] = $saved['line']['line_subtotal'];
                    $record['description'] = $saved['line']['description'];
                }
                $record['exchange_rate'] = $saved['exchange_rate'];
                $lookup_labels = $this->finance_transaction_lookup_labels($store, $record);
            }
        }

        $errors = array();
        $write_available = $available && $this->finance_write_available();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Finance writes are unavailable.', 503);
                return;
            }
            $values = $this->finance_transaction_values();
            $errors = $this->finance_transaction_validate($values);
            if (!$errors) {
                $previous_db_debug = $this->db->db_debug;
                $this->db->db_debug = FALSE;
                $result = $store->save_draft(
                    (int) $this->current_user['tenant_id'],
                    (int) $this->current_user['user_id'],
                    $type,
                    $is_new ? NULL : (int) $id,
                    $values
                );
                $this->db->db_debug = $previous_db_debug;
                if ($result === 'saved') {
                    if (!$is_new && isset($record['status']) && $record['status'] === 'revise') {
                        $this->session->set_flashdata(
                            'finance_status',
                            $this->finance_transaction_start_approval($slug, (int) $id)
                        );
                    } else {
                        $this->session->set_flashdata('finance_status', 'draft_saved');
                    }
                    redirect('/admin/finance/' . $slug, 'location', 303);
                    return;
                }
                $errors[] = 'transaction_' . $result;
            }
            $record = array_merge($record, $values);
            $lookup_labels = $this->finance_transaction_lookup_labels($store, $record);
        }

        $path = '/admin/finance/' . $slug . ($is_new ? '/new' : '/edit/' . (int) $id);
        $this->render_admin(
            'admin/finance/transaction_form',
            'finance_' . $slug,
            $path,
            array(
                'finance_transaction_slug' => $slug,
                'finance_transaction_type' => $type,
                'finance_transaction_record' => $record,
                'finance_transaction_id' => $is_new ? NULL : (int) $id,
                'finance_transaction_errors' => $errors,
                'finance_transaction_available' => $available,
                'finance_transaction_write_available' => $write_available,
                'finance_transaction_lookup_labels' => $lookup_labels,
                'finance_csrf_name' => $this->security->get_csrf_token_name(),
                'finance_csrf_hash' => $this->security->get_csrf_hash(),
            )
        );
    }

    private function finance_transaction_values()
    {
        $values = array();
        foreach (array('transaction_on', 'cash_account_id', 'account_id', 'category_id', 'tax_id', 'department_id', 'amount', 'exchange_rate', 'description') as $key) {
            $raw = $this->input->post($key, TRUE);
            $values[$key] = is_scalar($raw) ? trim((string) $raw) : '';
        }
        foreach (array('category_id', 'tax_id', 'department_id') as $optional_id) {
            $values[$optional_id] = $values[$optional_id] === '' ? NULL : (int) $values[$optional_id];
        }
        $values['cash_account_id'] = (int) $values['cash_account_id'];
        $values['account_id'] = (int) $values['account_id'];
        return $values;
    }

    private function finance_transaction_validate($values)
    {
        $errors = array();
        foreach (array('cash_account_id', 'account_id') as $id) {
            if ($values[$id] < 1) {
                $errors[] = $id . '_required';
            }
        }
        $date = DateTime::createFromFormat('!Y-m-d', $values['transaction_on']);
        if ($date === FALSE || $date->format('Y-m-d') !== $values['transaction_on']) {
            $errors[] = 'transaction_on_invalid';
        }
        if (!preg_match('/^\d{1,14}(?:\.\d{1,2})?$/', $values['amount']) || (float) $values['amount'] <= 0) {
            $errors[] = 'amount_invalid';
        }
        if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $values['exchange_rate']) || (float) $values['exchange_rate'] <= 0) {
            $errors[] = 'exchange_rate_invalid';
        }
        if (strlen($values['description']) > 240) {
            $errors[] = 'description_too_long';
        }
        return array_values(array_unique($errors));
    }

    private function finance_transaction_lookup_labels($store, $record)
    {
        $types = array(
            'cash_account_id' => 'cash_account',
            'account_id' => isset($record['transaction_type']) && $record['transaction_type'] === 'income' ? 'revenue_account' : 'expense_account',
            'category_id' => isset($record['transaction_type']) && $record['transaction_type'] === 'income' ? 'income_category' : 'expense_category',
            'tax_id' => 'tax',
            'department_id' => 'department',
        );
        $labels = array();
        foreach ($types as $field => $kind) {
            $labels[$field] = !empty($record[$field])
                ? $store->lookup_label((int) $this->current_user['tenant_id'], $kind, $record[$field])
                : '';
        }
        return $labels;
    }

    private function finance_transaction_menu_code($slug)
    {
        if ($slug === 'all_transactions') {
            return 'fin_all_transactions';
        }
        return $slug === 'income' ? 'fin_income' : 'fin_expenses';
    }

    private function finance_journal_index($slug)
    {
        $menu_code = $this->finance_journal_menu_code($slug);
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_journal_store');
        $this->render_admin(
            'admin/finance/journal_index',
            $slug === 'opening_balances' ? 'finance_opening_balances' : 'finance_journal_entries',
            '/admin/finance/' . $slug,
            array(
                'finance_journal_slug' => $slug,
                'finance_journal_available' => $this->finance_journal_store->available(),
                'finance_journal_can_add' => $this->has_admin_access_code($menu_code . '_add'),
                'finance_journal_can_update' => $this->has_admin_access_code($menu_code . '_update'),
                'finance_journal_write_available' => $this->finance_write_available(),
                'finance_status' => (string) $this->session->flashdata('finance_status'),
            )
        );
    }

    private function finance_journal_data($slug)
    {
        $menu_code = $this->finance_journal_menu_code($slug);
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->datatable_server->error(403);
            return;
        }
        $this->load->library('finance_journal_store');
        $store = $this->finance_journal_store;
        if (!$store->available()) {
            $this->datatable_server->error(503);
            return;
        }
        $tenant_id = (int) $this->current_user['tenant_id'];
        $source_type = $slug === 'opening_balances' ? 'opening_balance' : 'manual';
        $filters = $this->finance_list_filters(TRUE, 'journal');
        $this->datatable_server->respond(
            array(1 => 'journal_number', 2 => 'journal_date', 3 => 'description', 4 => 'created_by_name', 5 => 'debit_total', 6 => 'status'),
            function () use ($store, $tenant_id, $source_type, $filters) {
                return $store->count($tenant_id, $source_type, '', $filters);
            },
            function ($search) use ($store, $tenant_id, $source_type, $filters) {
                return $store->count($tenant_id, $source_type, $search, $filters);
            },
            function ($search, $length, $offset, $order, $direction) use ($store, $tenant_id, $source_type, $filters) {
                return $store->page($tenant_id, $source_type, $search, $length, $offset, $order, $direction, $filters);
            },
            array('max_length' => 50, 'max_search_length' => 120, 'default_order' => 'journal_date')
        );
    }

    private function finance_journal_post($slug, $id)
    {
        $menu_code = $this->finance_journal_menu_code($slug);
        if ($menu_code === NULL || !is_numeric($id) || (int) $id < 1
            || $this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        if (!$this->has_admin_menu_code($menu_code) || !$this->has_admin_access_code($menu_code . '_update')) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_journal_store');
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $result = $this->finance_journal_store->post(
            (int) $this->current_user['tenant_id'],
            (int) $this->current_user['user_id'],
            (int) $id,
            $slug === 'opening_balances' ? 'opening_balance' : 'manual'
        );
        $this->db->db_debug = $previous_db_debug;
        $this->session->set_flashdata('finance_status', $result);
        redirect('/admin/finance/' . $slug, 'location', 303);
    }

    private function finance_journal_form($slug, $id)
    {
        $menu_code = $this->finance_journal_menu_code($slug);
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $is_new = $id === NULL;
        if (!$this->has_admin_access_code($menu_code . ($is_new ? '_add' : '_update'))
            || (!$is_new && (!is_numeric($id) || (int) $id < 1))) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_journal_store');
        $store = $this->finance_journal_store;
        $tenant_id = (int) $this->current_user['tenant_id'];
        $source_type = $slug === 'opening_balances' ? 'opening_balance' : 'manual';
        $settings = $this->db->select('base_currency')->where('tenant_id', $tenant_id)->limit(1)->get('fin_settings');
        $base_currency = $settings !== FALSE && $settings->num_rows() ? (string) $settings->row()->base_currency : '';
        $record = array(
            'journal_date' => date('Y-m-d'),
            'description' => '',
            'lines' => array(
                array('account_id' => '', 'description' => '', 'currency_code' => $base_currency, 'exchange_rate' => '1', 'debit_amount' => '', 'credit_amount' => ''),
                array('account_id' => '', 'description' => '', 'currency_code' => $base_currency, 'exchange_rate' => '1', 'debit_amount' => '', 'credit_amount' => ''),
            ),
        );
        $account_labels = array();
        $available = $store->available();
        $was_revise = FALSE;
        if (!$is_new && $available) {
            $saved = $store->find($tenant_id, (int) $id, $source_type);
            if ($saved === NULL) {
                show_404();
                return;
            }
            if ($saved === FALSE) {
                $available = FALSE;
            } elseif (!in_array($saved['status'], array('draft', 'revise'), TRUE)) {
                show_error('Only draft or revised journal entries can be edited.', 409);
                return;
            } else {
                $was_revise = $saved['status'] === 'revise';
                $record = array_merge($record, $saved);
                foreach ($record['lines'] as $index => $line) {
                    $account_labels[$index] = !empty($line['account_id'])
                        ? $store->account_label($tenant_id, $line['account_id'])
                        : '';
                }
            }
        }
        $errors = array();
        $write_available = $available && $this->finance_write_available();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Finance writes are unavailable.', 503);
                return;
            }
            $values = $this->finance_journal_values($base_currency);
            $errors = $this->finance_journal_validate($values, $base_currency);
            if (!$errors) {
                $previous_db_debug = $this->db->db_debug;
                $this->db->db_debug = FALSE;
                $result = $store->save_draft(
                    $tenant_id,
                    (int) $this->current_user['user_id'],
                    $source_type,
                    $is_new ? NULL : (int) $id,
                    $values['journal_date'],
                    $values['description'],
                    $values['lines']
                );
                $this->db->db_debug = $previous_db_debug;
                if ($result === 'saved') {
                    $status = 'draft_saved';
                    if ($was_revise && !$is_new) {
                        $status = $source_type === 'opening_balance'
                            ? $this->finance_opening_balance_start_approval((int) $id)
                            : $this->finance_manual_journal_start_approval((int) $id);
                    }
                    $this->session->set_flashdata('finance_status', $status);
                    redirect('/admin/finance/' . $slug, 'location', 303);
                    return;
                }
                $errors[] = 'journal_' . $result;
            }
            $record = array_merge($record, $values);
            foreach ($record['lines'] as $index => $line) {
                $account_labels[$index] = !empty($line['account_id']) ? $store->account_label($tenant_id, $line['account_id']) : '';
            }
        }
        $this->render_admin(
            'admin/finance/journal_form',
            $slug === 'opening_balances' ? 'finance_opening_balances' : 'finance_journal_entries',
            '/admin/finance/' . $slug . ($is_new ? '/new' : '/edit/' . (int) $id),
            array(
                'finance_journal_slug' => $slug,
                'finance_journal_record' => $record,
                'finance_journal_id' => $is_new ? NULL : (int) $id,
                'finance_journal_errors' => $errors,
                'finance_journal_available' => $available,
                'finance_journal_write_available' => $write_available,
                'finance_journal_base_currency' => $base_currency,
                'finance_journal_account_labels' => $account_labels,
                'finance_lookup_mode' => FALSE,
                'finance_csrf_name' => $this->security->get_csrf_token_name(),
                'finance_csrf_hash' => $this->security->get_csrf_hash(),
            )
        );
    }

    private function finance_journal_values($base_currency)
    {
        $date = $this->input->post('journal_date', TRUE);
        $description = $this->input->post('description', TRUE);
        $raw_lines = $this->input->post('lines', TRUE);
        $lines = array();
        if (is_array($raw_lines)) {
            foreach (array_slice($raw_lines, 0, 30) as $line) {
                if (!is_array($line)) {
                    continue;
                }
                $read = function ($key) use ($line) {
                    return isset($line[$key]) && is_scalar($line[$key]) ? trim((string) $line[$key]) : '';
                };
                if ($read('account_id') === '' && $read('debit_amount') === '' && $read('credit_amount') === '') {
                    continue;
                }
                $lines[] = array(
                    'account_id' => (int) $read('account_id'),
                    'description' => $read('description'),
                    'currency_code' => strtoupper($read('currency_code')),
                    'exchange_rate' => $read('exchange_rate') === '' ? '1' : $read('exchange_rate'),
                    'debit_amount' => $read('debit_amount') === '' ? '0' : $read('debit_amount'),
                    'credit_amount' => $read('credit_amount') === '' ? '0' : $read('credit_amount'),
                );
            }
        }
        return array(
            'journal_date' => is_string($date) ? trim($date) : '',
            'description' => is_string($description) ? trim($description) : '',
            'lines' => $lines,
        );
    }

    private function finance_journal_validate($values, $base_currency)
    {
        $errors = array();
        $date = DateTime::createFromFormat('!Y-m-d', $values['journal_date']);
        if ($values['journal_date'] === '') {
            $errors[] = 'journal_date_required';
        } elseif ($date === FALSE || $date->format('Y-m-d') !== $values['journal_date']) {
            $errors[] = 'journal_date_invalid';
        }
        if ($values['description'] === '') {
            $errors[] = 'description_required';
        } elseif (strlen($values['description']) > 240) {
            $errors[] = 'description_too_long';
        }
        if (count($values['lines']) < 2) {
            $errors[] = 'journal_lines_required';
        }
        foreach ($values['lines'] as $index => $line) {
            $prefix = 'journal_line_' . ($index + 1);
            $debit = $line['debit_amount'];
            $credit = $line['credit_amount'];
            $debit_valid = (bool) preg_match('/^\d{1,14}(?:\.\d{1,2})?$/', $debit);
            $credit_valid = (bool) preg_match('/^\d{1,14}(?:\.\d{1,2})?$/', $credit);
            $rate_valid = (bool) preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $line['exchange_rate'])
                && (float) $line['exchange_rate'] > 0;
            if ((int) $line['account_id'] < 1) {
                $errors[] = $prefix . '_account_required';
            }
            if (!preg_match('/^[A-Z]{3}$/', $line['currency_code'])) {
                $errors[] = $prefix . '_currency_invalid';
            }
            if (!$rate_valid) {
                $errors[] = $prefix . '_rate_invalid';
            }
            if (!$debit_valid) {
                $errors[] = $prefix . '_debit_invalid';
            }
            if (!$credit_valid) {
                $errors[] = $prefix . '_credit_invalid';
            }
            if ($debit_valid && $credit_valid && (((float) $debit > 0) === ((float) $credit > 0))) {
                $errors[] = $prefix . '_amount_required';
            }
            if ($rate_valid && $line['currency_code'] === strtoupper($base_currency)
                && abs((float) $line['exchange_rate'] - 1) > 0.0000001) {
                $errors[] = $prefix . '_base_rate_invalid';
            }
            if (strlen($line['description']) > 240) {
                $errors[] = $prefix . '_description_too_long';
            }
        }
        return array_values(array_unique($errors));
    }

    private function finance_journal_menu_code($slug)
    {
        return $slug === 'journal_entries' ? 'fin_journal_entries'
            : ($slug === 'opening_balances' ? 'fin_opening_balances' : NULL);
    }

    private function finance_document_index($slug)
    {
        $menu_code = $this->finance_document_menu_code($slug);
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $kind = $slug === 'sales_invoices' ? 'sales' : 'purchase';
        $this->load->library('finance_document_store');
        $this->render_admin(
            'admin/finance/document_index',
            'finance_' . $slug,
            '/admin/finance/' . $slug,
            array(
                'finance_document_slug' => $slug,
                'finance_document_kind' => $kind,
                'finance_document_available' => $this->finance_document_store->available($kind),
                'finance_document_can_add' => $this->has_admin_access_code($menu_code . '_add'),
                'finance_document_can_update' => $this->has_admin_access_code($menu_code . '_update'),
                'finance_document_write_available' => $this->finance_write_available(),
                'finance_status' => (string) $this->session->flashdata('finance_status'),
            )
        );
    }

    private function finance_document_data($slug)
    {
        $menu_code = $this->finance_document_menu_code($slug);
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->datatable_server->error(403);
            return;
        }
        $kind = $slug === 'sales_invoices' ? 'sales' : 'purchase';
        $this->load->library('finance_document_store');
        $store = $this->finance_document_store;
        if (!$store->available($kind)) {
            $this->datatable_server->error(503);
            return;
        }
        $spec = $kind === 'sales'
            ? array('invoice_number', 'invoice_date', 'due_date')
            : array('bill_number', 'bill_date', 'due_date');
        $number_column = $spec[0];
        $date_column = $spec[1];
        $columns = array(1 => $number_column, 2 => 'integration_number', 3 => 'person_name', 4 => $date_column, 5 => 'due_date', 6 => 'total_amount', 7 => 'currency_code', 8 => 'status');
        $tenant_id = (int) $this->current_user['tenant_id'];
        $filters = $this->finance_list_filters(TRUE, 'document', $date_column);
        $this->datatable_server->respond(
            $columns,
            function () use ($store, $tenant_id, $kind, $filters) { return $store->count($tenant_id, $kind, '', $filters); },
            function ($search) use ($store, $tenant_id, $kind, $filters) { return $store->count($tenant_id, $kind, $search, $filters); },
            function ($search, $length, $offset, $order, $direction) use ($store, $tenant_id, $kind, $filters) {
                return $store->page($tenant_id, $kind, $search, $length, $offset, $order, $direction, $filters);
            },
            array('max_length' => 50, 'max_search_length' => 120, 'default_order' => $date_column)
        );
    }

    private function finance_document_post($slug, $id)
    {
        $menu_code = $this->finance_document_menu_code($slug);
        if ($menu_code === NULL || !is_numeric($id) || (int) $id < 1 || $this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        if (!$this->has_admin_menu_code($menu_code) || !$this->has_admin_access_code($menu_code . '_update')) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_document_store');
        $previous_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $result = $this->finance_document_store->post(
            (int) $this->current_user['tenant_id'],
            (int) $this->current_user['user_id'],
            $slug === 'sales_invoices' ? 'sales' : 'purchase',
            (int) $id
        );
        $this->db->db_debug = $previous_db_debug;
        $this->session->set_flashdata('finance_status', $result);
        redirect('/admin/finance/' . $slug, 'location', 303);
    }

    private function finance_document_view($slug, $id)
    {
        $menu_code = $this->finance_document_menu_code($slug);
        if ($menu_code === NULL || !$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $kind = $slug === 'sales_invoices' ? 'sales' : 'purchase';
        $this->load->library('finance_document_store');
        $store = $this->finance_document_store;
        $available = $store->available($kind);
        if (!$available) {
            show_error('Finance document storage is unavailable.', 503);
            return;
        }
        $saved = $store->find((int) $this->current_user['tenant_id'], $kind, (int) $id);
        if ($saved === NULL) {
            show_404();
            return;
        }
        if ($saved === FALSE) {
            show_error('Finance document storage is unavailable.', 503);
            return;
        }
        $spec = $kind === 'sales'
            ? array('number' => 'invoice_number', 'date' => 'invoice_date')
            : array('number' => 'bill_number', 'date' => 'bill_date');
        $record = $saved;
        $record['document_date'] = $saved[$spec['date']];
        $record['person_id'] = $saved[$kind === 'sales' ? 'customer_id' : 'supplier_id'];
        if (!empty($saved['line'])) {
            $record['account_id'] = $saved['line']['account_id'];
            $record['tax_id'] = $saved['line']['tax_id'];
            $record['department_id'] = $saved['line']['department_id'];
            $record['amount'] = $saved['line']['line_subtotal'];
            $record['description'] = $saved['line']['description'];
        }
        $lookup_labels = array();
        foreach (array('party', 'account', 'tax') as $lookup_kind) {
            $field = $lookup_kind === 'party' ? 'person_id' : ($lookup_kind === 'account' ? 'account_id' : 'tax_id');
            $lookup_labels[$field] = !empty($record[$field])
                ? $store->lookup_label((int) $this->current_user['tenant_id'], $kind, $lookup_kind, $record[$field])
                : '';
        }
        $integration_reference = !empty($record['integration_number']) ? (string) $record['integration_number'] : '';
        $integration_party = '';
        if (!empty($record['integration_party_name'])) {
            $integration_party = (string) $record['integration_party_name'];
            if (!empty($record['integration_party_code'])) {
                $integration_party .= ' (' . (string) $record['integration_party_code'] . ')';
            }
        }
        $this->render_admin(
            'admin/finance/document_view',
            'finance_' . $slug,
            '/admin/finance/' . $slug . '/view/' . (int) $id,
            array(
                'finance_document_slug' => $slug,
                'finance_document_kind' => $kind,
                'finance_document_record' => $record,
                'finance_document_lookup_labels' => $lookup_labels,
                'finance_document_integration_reference' => $integration_reference,
                'finance_document_integration_party' => $integration_party,
                'finance_document_number' => (string) $saved[$spec['number']],
            )
        );
    }

    private function finance_document_form($slug, $id)
    {
        $menu_code = $this->finance_document_menu_code($slug);
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $kind = $slug === 'sales_invoices' ? 'sales' : 'purchase';
        $is_new = $id === NULL;
        if (!$this->has_admin_access_code($menu_code . ($is_new ? '_add' : '_update'))
            || (!$is_new && (!is_numeric($id) || (int) $id < 1))) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_document_store');
        $store = $this->finance_document_store;
        $available = $store->available($kind);
        $was_revise = FALSE;
        $record = array(
            'document_date' => date('Y-m-d'), 'due_date' => '', 'person_id' => '',
            'account_id' => '', 'tax_id' => '', 'department_id' => '',
            'currency_code' => '', 'exchange_rate' => '1', 'amount' => '', 'description' => '', 'memo' => '',
        );
        if ($is_new) {
            $base_settings = $this->db->select('base_currency')
                ->where('tenant_id', (int) $this->current_user['tenant_id'])->limit(1)->get('fin_settings');
            if ($base_settings !== FALSE && $base_settings->num_rows()) {
                $record['currency_code'] = strtoupper((string) $base_settings->row()->base_currency);
            }
        }
        $labels = array();
        if (!$is_new && $available) {
            $saved = $store->find((int) $this->current_user['tenant_id'], $kind, (int) $id);
            if ($saved === NULL) {
                show_404();
                return;
            }
            if ($saved === FALSE) {
                $available = FALSE;
            } elseif (!in_array($saved['status'], array('draft', 'revise'), TRUE)) {
                show_error('Posted Finance documents cannot be edited.', 409);
                return;
            } else {
                $was_revise = $saved['status'] === 'revise';
                $spec = $kind === 'sales'
                    ? array('number' => 'invoice_number', 'date' => 'invoice_date')
                    : array('number' => 'bill_number', 'date' => 'bill_date');
                $record = array_merge($record, $saved);
                $record['person_id'] = $saved[$kind === 'sales' ? 'customer_id' : 'supplier_id'];
                $record['document_date'] = $saved[$spec['date']];
                if (!empty($saved['line'])) {
                    $record['account_id'] = $saved['line']['account_id'];
                    $record['tax_id'] = $saved['line']['tax_id'];
                    $record['department_id'] = $saved['line']['department_id'];
                    $record['amount'] = $saved['line']['line_subtotal'];
                    $record['description'] = $saved['line']['description'];
                }
                foreach (array('party', 'account', 'tax') as $lookup_kind) {
                    $field = $lookup_kind === 'party' ? 'person_id' : ($lookup_kind === 'account' ? 'account_id' : 'tax_id');
                    $labels[$field] = !empty($record[$field])
                        ? $store->lookup_label((int) $this->current_user['tenant_id'], $kind, $lookup_kind, $record[$field])
                        : '';
                }
            }
        }
        $errors = array();
        $write_available = $available && $this->finance_write_available();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Finance writes are unavailable.', 503);
                return;
            }
            $values = $this->finance_document_values();
            $errors = $this->finance_document_validate($values, $kind);
            if (!$errors) {
                $previous_db_debug = $this->db->db_debug;
                $this->db->db_debug = FALSE;
                $result = $store->save_draft(
                    (int) $this->current_user['tenant_id'],
                    (int) $this->current_user['user_id'],
                    $kind,
                    $is_new ? NULL : (int) $id,
                    $values
                );
                $this->db->db_debug = $previous_db_debug;
                if ($result === 'saved') {
                    $status = $was_revise && !$is_new ? $this->finance_document_start_approval($slug, (int) $id) : 'draft_saved';
                    $this->session->set_flashdata('finance_status', $status);
                    redirect('/admin/finance/' . $slug, 'location', 303);
                    return;
                }
                $errors[] = 'document_' . $result;
            }
            $record = array_merge($record, $values);
            foreach (array('party', 'account', 'tax') as $lookup_kind) {
                $field = $lookup_kind === 'party' ? 'person_id' : ($lookup_kind === 'account' ? 'account_id' : 'tax_id');
                $labels[$field] = !empty($record[$field])
                    ? $store->lookup_label((int) $this->current_user['tenant_id'], $kind, $lookup_kind, $record[$field])
                    : '';
            }
        }
        if (isset($record['amount']) && $record['amount'] !== '' && $record['amount'] !== NULL) {
            $record['amount'] = number_format((float) $record['amount'], 2, '.', '');
        }
        if (isset($record['exchange_rate']) && $record['exchange_rate'] !== '' && $record['exchange_rate'] !== NULL) {
            $record['exchange_rate'] = number_format((float) $record['exchange_rate'], 2, '.', '');
        }
        $integration_reference = !empty($record['integration_number']) ? (string) $record['integration_number'] : '';
        $integration_party = '';
        if (!empty($record['integration_party_name'])) {
            $integration_party = (string) $record['integration_party_name'];
            if (!empty($record['integration_party_code'])) {
                $integration_party .= ' (' . (string) $record['integration_party_code'] . ')';
            }
        }
        $this->render_admin(
            'admin/finance/document_form',
            'finance_' . $slug,
            '/admin/finance/' . $slug . ($is_new ? '/new' : '/edit/' . (int) $id),
            array(
                'finance_document_slug' => $slug,
                'finance_document_kind' => $kind,
                'finance_document_record' => $record,
                'finance_document_id' => $is_new ? NULL : (int) $id,
                'finance_document_errors' => $errors,
                'finance_document_available' => $available,
                'finance_document_write_available' => $write_available,
                'finance_document_lookup_labels' => $labels,
                'finance_document_integration_reference' => $integration_reference,
                'finance_document_integration_party' => $integration_party,
                'finance_csrf_name' => $this->security->get_csrf_token_name(),
                'finance_csrf_hash' => $this->security->get_csrf_hash(),
            )
        );
    }

    private function finance_document_values()
    {
        $values = array();
        foreach (array('document_date', 'due_date', 'person_id', 'account_id', 'tax_id', 'department_id', 'currency_code', 'exchange_rate', 'amount', 'description', 'memo') as $name) {
            $raw = $this->input->post($name, TRUE);
            $values[$name] = is_scalar($raw) ? trim((string) $raw) : '';
        }
        foreach (array('tax_id', 'department_id') as $optional) {
            $values[$optional] = $values[$optional] === '' ? NULL : (int) $values[$optional];
        }
        $values['person_id'] = (int) $values['person_id'];
        $values['account_id'] = (int) $values['account_id'];
        $values['currency_code'] = strtoupper($values['currency_code']);
        return $values;
    }

    private function finance_document_validate($values, $kind = 'sales')
    {
        $errors = array();
        foreach (array('person_id', 'account_id') as $id) {
            if ($values[$id] < 1) {
                $errors[] = $id . '_required';
            }
        }
        foreach (array('document_date', 'due_date') as $date_key) {
            if ($values[$date_key] === '' && $date_key === 'due_date') {
                continue;
            }
            $date = DateTime::createFromFormat('!Y-m-d', $values[$date_key]);
            if ($date === FALSE || $date->format('Y-m-d') !== $values[$date_key]) {
                $errors[] = $date_key . '_invalid';
            }
        }
        if ($values['due_date'] !== '' && $values['due_date'] < $values['document_date']) {
            $errors[] = 'due_date_invalid';
        }
        if (!preg_match('/^[A-Z]{3}$/', $values['currency_code'])) {
            $errors[] = 'currency_invalid';
        }
        if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $values['exchange_rate']) || (float) $values['exchange_rate'] <= 0) {
            $errors[] = 'exchange_rate_invalid';
        }
        if (!preg_match('/^\d{1,14}(?:\.\d{1,2})?$/', $values['amount']) || (float) $values['amount'] <= 0) {
            $errors[] = 'amount_invalid';
        }
        if (strlen($values['description']) > 240 || strlen($values['memo']) > 10000) {
            $errors[] = 'text_too_long';
        }
        return array_values(array_unique($errors));
    }

    private function finance_document_menu_code($slug)
    {
        return $slug === 'sales_invoices' ? 'fin_sales_invoices'
            : ($slug === 'purchase_bills' ? 'fin_purchase_bills' : NULL);
    }

    private function finance_payment_index($slug)
    {
        $menu_code = $this->finance_payment_menu_code($slug);
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $kind = $slug === 'customer_payments' ? 'customer' : 'supplier';
        $this->load->library('finance_payment_store');
        $this->render_admin('admin/finance/payment_index', 'finance_' . $slug, '/admin/finance/' . $slug, array(
            'finance_payment_slug' => $slug,
            'finance_payment_kind' => $kind,
            'finance_payment_available' => $this->finance_payment_store->available($kind),
            'finance_payment_can_add' => $this->has_admin_access_code($menu_code . '_add'),
            'finance_payment_can_update' => $this->has_admin_access_code($menu_code . '_update'),
            'finance_payment_write_available' => $this->finance_write_available(),
            'finance_status' => (string) $this->session->flashdata('finance_status'),
        ));
    }

    private function finance_payment_data($slug)
    {
        $menu_code = $this->finance_payment_menu_code($slug);
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->datatable_server->error(403);
            return;
        }
        $kind = $slug === 'customer_payments' ? 'customer' : 'supplier';
        $this->load->library('finance_payment_store');
        $store = $this->finance_payment_store;
        if (!$store->available($kind)) {
            $this->datatable_server->error(503);
            return;
        }
        $tenant = (int) $this->current_user['tenant_id'];
        $filters = $this->finance_list_filters(TRUE, 'payment');
        $this->datatable_server->respond(
            array(1 => 'payment_number', 2 => 'payment_date', 3 => 'amount', 4 => 'currency_code', 5 => 'reference', 6 => 'status'),
            function () use ($store, $tenant, $kind, $filters) { return $store->count($tenant, $kind, '', $filters); },
            function ($search) use ($store, $tenant, $kind, $filters) { return $store->count($tenant, $kind, $search, $filters); },
            function ($search, $length, $offset, $order, $direction) use ($store, $tenant, $kind, $filters) {
                return $store->page($tenant, $kind, $search, $length, $offset, $order, $direction, $filters);
            },
            array('max_length' => 50, 'max_search_length' => 120, 'default_order' => 'payment_date')
        );
    }

    private function finance_payment_form($slug, $id)
    {
        $menu_code = $this->finance_payment_menu_code($slug);
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $kind = $slug === 'customer_payments' ? 'customer' : 'supplier';
        $is_new = $id === NULL;
        if (!$this->has_admin_access_code($menu_code . ($is_new ? '_add' : '_update'))
            || (!$is_new && (!is_numeric($id) || (int) $id < 1))) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_payment_store');
        $store = $this->finance_payment_store;
        $available = $store->available($kind);
        $record = array('payment_date' => date('Y-m-d'), 'document_id' => '', 'cash_account_id' => '', 'amount' => '', 'exchange_rate' => '1', 'reference' => '', 'status' => 'draft');
        $labels = array('document_id' => '', 'cash_account_id' => '');
        $was_revise = FALSE;
        $tenant = (int) $this->current_user['tenant_id'];
        if (!$is_new && $available) {
            $saved = $store->find($tenant, $kind, (int) $id);
            if ($saved === NULL) {
                show_404();
                return;
            }
            if ($saved === FALSE) {
                $available = FALSE;
            } elseif (!in_array($saved['status'], array('draft', 'revise'), TRUE)) {
                show_error('Posted payments cannot be edited.', 409);
                return;
            } else {
                $was_revise = $saved['status'] === 'revise';
                $record = array_merge($record, $saved);
                if (!empty($saved['allocation'])) $record['document_id'] = $saved['allocation'][$kind === 'customer' ? 'sales_invoice_id' : 'purchase_bill_id'];
                $labels['document_id'] = !empty($record['document_id']) ? $store->lookup_label($tenant, $kind, $record['document_id']) : '';
                $labels['cash_account_id'] = !empty($record['cash_account_id']) ? $store->lookup_label($tenant, 'cash_account', $record['cash_account_id']) : '';
            }
        }
        $errors = array();
        $write_available = $available && $this->finance_write_available();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Finance writes are unavailable.', 503);
                return;
            }
            $values = array();
            foreach (array('payment_date','document_id','cash_account_id','amount','exchange_rate','reference') as $key) {
                $raw = $this->input->post($key, TRUE);
                $values[$key] = is_scalar($raw) ? trim((string) $raw) : '';
            }
            $values['document_id'] = (int) $values['document_id'];
            $values['cash_account_id'] = (int) $values['cash_account_id'];
            $errors = $this->finance_payment_validate($values);
            if (!$errors) {
                $previous = $this->db->db_debug;
                $this->db->db_debug = FALSE;
                $result = $store->save_draft($tenant, (int) $this->current_user['user_id'], $kind, $is_new ? NULL : (int) $id, $values);
                $this->db->db_debug = $previous;
                if ($result === 'saved') {
                    $status = $was_revise && !$is_new ? $this->finance_payment_start_approval($slug, (int) $id) : 'draft_saved';
                    $this->session->set_flashdata('finance_status', $status);
                    redirect('/admin/finance/' . $slug, 'location', 303);
                    return;
                }
                $errors[] = 'payment_' . $result;
            }
            $record = array_merge($record, $values);
            $labels['document_id'] = !empty($record['document_id']) ? $store->lookup_label($tenant, $kind, $record['document_id']) : '';
            $labels['cash_account_id'] = !empty($record['cash_account_id']) ? $store->lookup_label($tenant, 'cash_account', $record['cash_account_id']) : '';
        }
        $document_summary = !empty($record['document_id'])
            ? $store->document_financials($tenant, $kind, (int) $record['document_id'])
            : NULL;
        $this->render_admin('admin/finance/payment_form', 'finance_' . $slug,
            '/admin/finance/' . $slug . ($is_new ? '/new' : '/edit/' . (int) $id), array(
                'finance_payment_slug' => $slug,
                'finance_payment_kind' => $kind,
                'finance_payment_record' => $record,
                'finance_payment_id' => $is_new ? NULL : (int) $id,
                'finance_payment_labels' => $labels,
                'finance_payment_document_summary' => $document_summary,
                'finance_payment_errors' => $errors,
                'finance_payment_available' => $available,
                'finance_payment_write_available' => $write_available,
                'finance_csrf_name' => $this->security->get_csrf_token_name(),
                'finance_csrf_hash' => $this->security->get_csrf_hash(),
            )
        );
    }

    private function finance_payment_post($slug, $id)
    {
        $menu_code = $this->finance_payment_menu_code($slug);
        if ($menu_code === NULL || !is_numeric($id) || (int) $id < 1 || $this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        if (!$this->has_admin_menu_code($menu_code) || !$this->has_admin_access_code($menu_code . '_update')) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_payment_store');
        $previous = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $result = $this->finance_payment_store->post(
            (int) $this->current_user['tenant_id'],
            (int) $this->current_user['user_id'],
            $slug === 'customer_payments' ? 'customer' : 'supplier',
            (int) $id
        );
        $this->db->db_debug = $previous;
        $this->session->set_flashdata('finance_status', $result);
        redirect('/admin/finance/' . $slug, 'location', 303);
    }

    private function finance_payment_validate($values)
    {
        $errors = array();
        foreach (array('document_id','cash_account_id') as $id) if ($values[$id] < 1) $errors[] = $id . '_required';
        $date = DateTime::createFromFormat('!Y-m-d', $values['payment_date']);
        if ($date === FALSE || $date->format('Y-m-d') !== $values['payment_date']) $errors[] = 'payment_date_invalid';
        if (!preg_match('/^\d{1,14}(?:\.\d{1,2})?$/', $values['amount']) || (float) $values['amount'] <= 0) $errors[] = 'amount_invalid';
        if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $values['exchange_rate']) || (float) $values['exchange_rate'] <= 0) $errors[] = 'exchange_rate_invalid';
        if (strlen($values['reference']) > 80) $errors[] = 'reference_too_long';
        return array_values(array_unique($errors));
    }

    private function finance_payment_menu_code($slug)
    {
        return $slug === 'customer_payments' ? 'fin_customer_payments'
            : ($slug === 'supplier_payments' ? 'fin_supplier_payments' : NULL);
    }

    private function finance_transfer_index()
    {
        $menu = 'fin_cash_bank_transfer';
        if (!$this->has_admin_menu_code($menu)) { $this->finance_forbidden(); return; }
        $this->load->library('finance_transfer_store');
        $this->render_admin('admin/finance/transfer_index','finance_cash_bank_transfer','/admin/finance/cash_bank_transfer',array(
            'finance_transfer_available'=>$this->finance_transfer_store->available(),
            'finance_transfer_can_add'=>$this->has_admin_access_code($menu.'_add'),
            'finance_transfer_can_update'=>$this->has_admin_access_code($menu.'_update'),
            'finance_transfer_write_available'=>$this->finance_write_available(),
            'finance_status'=>(string)$this->session->flashdata('finance_status'),
        ));
    }

    private function finance_transfer_data()
    {
        $this->load->library('datatable_server');
        if(!$this->has_admin_menu_code('fin_cash_bank_transfer')){$this->datatable_server->error(403);return;}
        $this->load->library('finance_transfer_store');$store=$this->finance_transfer_store;
        if(!$store->available()){$this->datatable_server->error(503);return;}
        $tenant=(int)$this->current_user['tenant_id'];$filters=$this->finance_list_filters(TRUE, 'transfer');
        $this->datatable_server->respond(
            array(1=>'transfer_number',2=>'transfer_date',3=>'create_on',4=>'from_account_name',5=>'to_account_name',6=>'from_amount',7=>'created_by_name',8=>'status'),
            function()use($store,$tenant,$filters){return $store->count($tenant,'',$filters);},
            function($search)use($store,$tenant,$filters){return $store->count($tenant,$search,$filters);},
            function($search,$length,$offset,$order,$direction)use($store,$tenant,$filters){return $store->page($tenant,$search,$length,$offset,$order,$direction,$filters);},
            array('max_length'=>50,'max_search_length'=>120,'default_order'=>'create_on')
        );
    }

    private function finance_transfer_form($id)
    {
        $menu='fin_cash_bank_transfer';$is_new=$id===NULL;
        if(!$this->has_admin_menu_code($menu)||!$this->has_admin_access_code($menu.($is_new?'_add':'_update'))){$this->finance_forbidden();return;}
        $this->load->library('finance_transfer_store');$store=$this->finance_transfer_store;
        $record=array('transfer_date'=>date('Y-m-d'),'from_cash_account_id'=>'','to_cash_account_id'=>'','from_amount'=>'','to_amount'=>'','from_exchange_rate'=>'1','to_exchange_rate'=>'1','memo'=>'','status'=>'draft');
        $labels=array('from_cash_account_id'=>'','to_cash_account_id'=>'');$available=$store->available();
        if(!$is_new&&$available){$saved=$store->find((int)$this->current_user['tenant_id'],(int)$id);if($saved===NULL){show_404();return;}if($saved===FALSE){$available=FALSE;}elseif(!in_array($saved['status'],array('draft','revise'),TRUE)){show_error('Only draft or revised transfers can be edited.',409);return;}else{$record=array_merge($record,$saved);foreach(array('from_cash_account_id','to_cash_account_id') as $f)$labels[$f]=$store->cash_label((int)$this->current_user['tenant_id'],$record[$f]);}}
        $errors=array();$write=$available&&$this->finance_write_available();
        $was_revise=isset($record['status'])&&$record['status']==='revise';
        if($this->input->method(TRUE)==='POST'){
            if(!$write){show_error('Finance writes are unavailable.',503);return;}
            $values=array();foreach(array('transfer_date','from_cash_account_id','to_cash_account_id','from_amount','to_amount','from_exchange_rate','to_exchange_rate','memo') as $key){$raw=$this->input->post($key,TRUE);$values[$key]=is_scalar($raw)?trim((string)$raw):'';}
            $values['from_cash_account_id']=(int)$values['from_cash_account_id'];$values['to_cash_account_id']=(int)$values['to_cash_account_id'];
            $date=DateTime::createFromFormat('!Y-m-d',$values['transfer_date']);
            if($date===FALSE||$date->format('Y-m-d')!==$values['transfer_date'])$errors[]='transfer_date_invalid';
            foreach(array('from_amount','to_amount') as $a)if(!preg_match('/^\d{1,14}(?:\.\d{1,2})?$/',$values[$a])||(float)$values[$a]<=0)$errors[]=$a.'_invalid';
            foreach(array('from_exchange_rate','to_exchange_rate') as $r)if(!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/',$values[$r])||(float)$values[$r]<=0)$errors[]=$r.'_invalid';
            if(!$values['from_cash_account_id']||!$values['to_cash_account_id'])$errors[]='cash_account_required';
            if(strlen($values['memo'])>10000)$errors[]='memo_too_long';
            if(!$errors){$previous=$this->db->db_debug;$this->db->db_debug=FALSE;$result=$store->save_draft((int)$this->current_user['tenant_id'],(int)$this->current_user['user_id'],$is_new?NULL:(int)$id,$values);$this->db->db_debug=$previous;
                if($result==='saved'){$this->session->set_flashdata('finance_status',$was_revise&&!$is_new?$this->finance_transfer_start_approval((int)$id):'draft_saved');redirect('/admin/finance/cash_bank_transfer','location',303);return;}
                $errors[]='transfer_'.$result;
            }
            $record=array_merge($record,$values);foreach(array('from_cash_account_id','to_cash_account_id') as $f)$labels[$f]=$store->cash_label((int)$this->current_user['tenant_id'],$record[$f]);
        }
        $this->render_admin('admin/finance/transfer_form','finance_cash_bank_transfer','/admin/finance/cash_bank_transfer'.($is_new?'/new':'/edit/'.(int)$id),array(
            'finance_transfer_record'=>$record,'finance_transfer_id'=>$is_new?NULL:(int)$id,'finance_transfer_labels'=>$labels,
            'finance_transfer_errors'=>$errors,'finance_transfer_available'=>$available,'finance_transfer_write_available'=>$write,
            'finance_csrf_name'=>$this->security->get_csrf_token_name(),'finance_csrf_hash'=>$this->security->get_csrf_hash(),
        ));
    }

    private function finance_transfer_post($id)
    {
        if(!is_numeric($id)||(int)$id<1||$this->input->method(TRUE)!=='POST'){show_404();return;}
        if(!$this->has_admin_menu_code('fin_cash_bank_transfer')||!$this->has_admin_access_code('fin_cash_bank_transfer_update')){$this->finance_forbidden();return;}
        $this->load->library('finance_transfer_store');$previous=$this->db->db_debug;$this->db->db_debug=FALSE;
        $result=$this->finance_transfer_store->post((int)$this->current_user['tenant_id'],(int)$this->current_user['user_id'],(int)$id);
        $this->db->db_debug=$previous;$this->session->set_flashdata('finance_status',$result);
        redirect('/admin/finance/cash_bank_transfer','location',303);
    }

    private function finance_reconciliation_index()
    {
        $menu='fin_bank_reconciliation';
        if(!$this->has_admin_menu_code($menu)){$this->finance_forbidden();return;}
        $this->load->library('finance_reconciliation_store');$store=$this->finance_reconciliation_store;
        $this->render_admin('admin/finance/reconciliation_index','finance_bank_reconciliation','/admin/finance/bank_reconciliation',array(
            'finance_reconciliation_available'=>$store->available(),
            'finance_reconciliation_can_add'=>$this->has_admin_access_code($menu.'_add'),
            'finance_reconciliation_can_update'=>$this->has_admin_access_code($menu.'_update'),
            'finance_reconciliation_write_available'=>$this->finance_write_available(),
            'finance_status'=>(string)$this->session->flashdata('finance_status'),
        ));
    }

    private function finance_reconciliation_data()
    {
        $this->load->library('datatable_server');
        if(!$this->has_admin_menu_code('fin_bank_reconciliation')){$this->datatable_server->error(403);return;}
        $this->load->library('finance_reconciliation_store');$store=$this->finance_reconciliation_store;
        if(!$store->available()){$this->datatable_server->error(503);return;}
        $tenant=(int)$this->current_user['tenant_id'];$filters=$this->finance_list_filters(TRUE, 'reconciliation');
        $this->datatable_server->respond(
            array(1=>'reconciliation_number',2=>'statement_date',3=>'statement_reference',4=>'closing_balance',5=>'status'),
            function()use($store,$tenant,$filters){return $store->count($tenant,'',$filters);},
            function($search)use($store,$tenant,$filters){return $store->count($tenant,$search,$filters);},
            function($search,$length,$offset,$order,$direction)use($store,$tenant,$filters){return $store->page($tenant,$search,$length,$offset,$order,$direction,$filters);},
            array('max_length'=>50,'max_search_length'=>120,'default_order'=>'statement_date')
        );
    }

    private function finance_reconciliation_form($id)
    {
        $menu='fin_bank_reconciliation';$is_new=$id===NULL;
        if(!$this->has_admin_menu_code($menu)||!$this->has_admin_access_code($menu.($is_new?'_add':'_update'))){$this->finance_forbidden();return;}
        $this->load->library('finance_reconciliation_store');$store=$this->finance_reconciliation_store;
        $record=array('cash_account_id'=>'','statement_date'=>date('Y-m-d'),'statement_reference'=>'','opening_balance'=>'','closing_balance'=>'','lines'=>array());
        $labels=array('cash_account_id'=>'','lines'=>array());$available=$store->available();$was_revise=FALSE;
        if(!$is_new&&$available){
            $saved=$store->find((int)$this->current_user['tenant_id'],(int)$id);
            if($saved===NULL){show_404();return;}
            if($saved===FALSE){$available=FALSE;}
            elseif(!in_array($saved['status'],array('draft','revise'),TRUE)){show_error('Completed reconciliations cannot be edited.',409);return;}
            else{$was_revise=$saved['status']==='revise';$record=array_merge($record,$saved);$this->load->library('finance_transfer_store');$labels['cash_account_id']=$this->finance_transfer_store->cash_label((int)$this->current_user['tenant_id'],$record['cash_account_id']);foreach($record['lines'] as $i=>$line)$labels['lines'][$i]=$store->line_label((int)$this->current_user['tenant_id'],$line['journal_line_id']);}
        }
        $errors=array();$write=$available&&$this->finance_write_available();
        if($this->input->method(TRUE)==='POST'){
            if(!$write){show_error('Finance writes are unavailable.',503);return;}
            $raw=array();foreach(array('cash_account_id','statement_date','statement_reference','opening_balance','closing_balance') as $key){$v=$this->input->post($key,TRUE);$raw[$key]=is_scalar($v)?trim((string)$v):'';}
            $raw['cash_account_id']=(int)$raw['cash_account_id'];$lines=$this->input->post('lines',TRUE);$clean=array();
            if(is_array($lines))foreach(array_slice($lines,0,100) as $line){if(!is_array($line))continue;$read=function($k)use($line){return isset($line[$k])&&is_scalar($line[$k])?trim((string)$line[$k]):'';};if($read('journal_line_id')==='')continue;$clean[]=array('journal_line_id'=>(int)$read('journal_line_id'),'statement_on'=>$read('statement_on'),'description'=>$read('description'),'statement_amount'=>$read('statement_amount'));}
            $d=DateTime::createFromFormat('!Y-m-d',$raw['statement_date']);
            if(!$raw['cash_account_id'])$errors[]='cash_account_required';
            if($d===FALSE||$d->format('Y-m-d')!==$raw['statement_date'])$errors[]='statement_date_invalid';
            foreach(array('opening_balance','closing_balance') as $k)if(!preg_match('/^-?\d{1,14}(?:\.\d{1,2})?$/',$raw[$k]))$errors[]=$k.'_invalid';
            if($raw['statement_reference']===''||strlen($raw['statement_reference'])>80)$errors[]='statement_reference_invalid';
            if(count($clean)<1)$errors[]='statement_lines_required';
            foreach($clean as $line){$ld=DateTime::createFromFormat('!Y-m-d',$line['statement_on']);if($ld===FALSE||$ld->format('Y-m-d')!==$line['statement_on']||$line['statement_on']>$raw['statement_date'])$errors[]='statement_line_date_invalid';if(!preg_match('/^-?\d{1,14}(?:\.\d{1,2})?$/',$line['statement_amount']))$errors[]='statement_line_amount_invalid';}
            $values=array_merge($raw,array('lines'=>$clean));
            if(!$errors){$previous=$this->db->db_debug;$this->db->db_debug=FALSE;$result=$store->save_draft((int)$this->current_user['tenant_id'],(int)$this->current_user['user_id'],$is_new?NULL:(int)$id,$values);$this->db->db_debug=$previous;if($result==='saved'){$status=$was_revise&&!$is_new?$this->finance_reconciliation_start_approval((int)$id):'draft_saved';$this->session->set_flashdata('finance_status',$status);redirect('/admin/finance/bank_reconciliation','location',303);return;}$errors[]='reconciliation_'.$result;}
            $record=array_merge($record,$values);$this->load->library('finance_transfer_store');$labels['cash_account_id']=$store->cash_label((int)$this->current_user['tenant_id'],$record['cash_account_id']);foreach($record['lines'] as $i=>$line)$labels['lines'][$i]=$store->line_label((int)$this->current_user['tenant_id'],$line['journal_line_id']);
        }
        $this->render_admin('admin/finance/reconciliation_form','finance_bank_reconciliation','/admin/finance/bank_reconciliation'.($is_new?'/new':'/edit/'.(int)$id),array(
            'finance_reconciliation_record'=>$record,'finance_reconciliation_id'=>$is_new?NULL:(int)$id,'finance_reconciliation_labels'=>$labels,
            'finance_reconciliation_errors'=>$errors,'finance_reconciliation_available'=>$available,'finance_reconciliation_write_available'=>$write,
            'finance_csrf_name'=>$this->security->get_csrf_token_name(),'finance_csrf_hash'=>$this->security->get_csrf_hash(),
        ));
    }

    private function finance_reconciliation_complete($id)
    {
        if(!is_numeric($id)||(int)$id<1||$this->input->method(TRUE)!=='POST'){show_404();return;}
        if(!$this->has_admin_menu_code('fin_bank_reconciliation')||!$this->has_admin_access_code('fin_bank_reconciliation_update')){$this->finance_forbidden();return;}
        $this->load->library('finance_reconciliation_store');$previous=$this->db->db_debug;$this->db->db_debug=FALSE;
        $result=$this->finance_reconciliation_store->complete((int)$this->current_user['tenant_id'],(int)$this->current_user['user_id'],(int)$id);
        $this->db->db_debug=$previous;$this->session->set_flashdata('finance_status',$result);
        redirect('/admin/finance/bank_reconciliation','location',303);
    }

    public function report_export($slug = '')
    {
        $menu_code = $this->finance_report_menu_code($slug);
        if ($menu_code === NULL) {
            show_404();
            return;
        }
        if (!$this->has_admin_menu_code($menu_code)
            || !$this->has_admin_access_code($menu_code . '_export')) {
            $this->finance_forbidden();
            return;
        }
        if ($slug === 'cash_flow') {
            $this->finance_cash_flow_export();
            return;
        }
        if ($slug === 'profit_loss') {
            $this->finance_profit_loss_export();
            return;
        }
        if ($slug === 'balance_sheet_skontro') {
            $this->finance_balance_sheet_skontro_export();
            return;
        }
        $this->lang->load('finance', $this->resolve_admin_locale());
        $filters = $this->finance_report_dates();
        if (!empty($filters['errors'])) {
            $this->session->set_flashdata('finance_status', 'invalid_range');
            redirect('/admin/finance/' . $slug, 'location', 303);
            return;
        }
        $this->load->library('finance_report_store');
        $store = $this->finance_report_store;
        if (!$store->available($slug)) {
            $this->session->set_flashdata('finance_status', 'unavailable');
            redirect('/admin/finance/' . $slug, 'location', 303);
            return;
        }
        $tenant_id = (int) $this->current_user['tenant_id'];
        $rows = $store->rows($slug, $tenant_id, $filters['start_on'], $filters['end_on']);
        if ($rows === FALSE) {
            $this->session->set_flashdata('finance_status', 'unavailable');
            redirect('/admin/finance/' . $slug, 'location', 303);
            return;
        }
        $columns = $this->finance_report_columns($slug);
        $settings = $this->db->select('base_currency')->where('tenant_id', $tenant_id)->limit(1)->get('fin_settings');
        $base_currency = $settings !== FALSE && $settings->num_rows() ? (string) $settings->row()->base_currency : '';
        $tenant = $this->db->select('tenant_name')->where('tenant_id', $tenant_id)->limit(1)->get('sys_tenant');
        $tenant_name = $tenant !== FALSE && $tenant->num_rows() ? (string) $tenant->row()->tenant_name : (string) $tenant_id;
        $title = $this->lang->line('admin_finance_' . $slug);
        if (!is_string($title) || $title === '') {
            $title = $this->lang->line('finance_' . $slug);
        }
        if (!is_string($title) || $title === '') {
            $title = ucfirst(str_replace('_', ' ', $slug));
        }
        $prepared_by = isset($this->current_user['full_name']) && trim((string) $this->current_user['full_name']) !== ''
            ? (string) $this->current_user['full_name']
            : (isset($this->current_user['username']) ? (string) $this->current_user['username'] : '');
        $column_labels = array();
        foreach ($columns as $column) {
            $label = $this->lang->line('finance_report_column_' . $column['name']);
            $column_labels[] = is_string($label) && $label !== '' ? $label : $column['label'];
        }
        $numeric = array('debit_base_amount','credit_base_amount','debit_total','credit_total','net_debit','cash_in','cash_out','billed','paid','outstanding');
        $totals = array();
        foreach ($columns as $column) {
            if (in_array($column['name'], $numeric, TRUE)) {
                $totals[$column['name']] = 0.0;
            }
        }
        foreach ($rows as $row) {
            foreach ($totals as $name => $value) {
                $totals[$name] += (float) (isset($row[$name]) ? $row[$name] : 0);
            }
        }
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, array('Report Name', $title));
        fputcsv($stream, array('Report Code', $slug));
        fputcsv($stream, array('Tenant', $tenant_name));
        $as_of_report = in_array($slug, array('balance_sheet', 'ar_report', 'ap_report'), TRUE);
        fputcsv($stream, array($as_of_report ? 'As of' : 'Period',
            $as_of_report ? $filters['end_on'] : $filters['start_on'] . ' s/d ' . $filters['end_on']));
        fputcsv($stream, array('Base Currency', $base_currency));
        fputcsv($stream, array('Generated', date('Y-m-d H:i:s')));
        fputcsv($stream, array('Prepared By', $prepared_by));
        fputcsv($stream, array('Source', 'Posted journals'));
        fputcsv($stream, array('Rows', count($rows)));
        fputcsv($stream, array());
        fputcsv($stream, array_merge(array('No'), $column_labels));
        $row_number = 0;
        foreach ($rows as $row) {
            $line = array(++$row_number);
            foreach ($columns as $column) {
                $value = isset($row[$column['name']]) ? $row[$column['name']] : '';
                if (in_array($column['name'], $numeric, TRUE)) {
                    $value = number_format((float) $value, 2, '.', '');
                }
                $line[] = $value;
            }
            fputcsv($stream, $line);
        }
        if ($totals) {
            $line = array();
            foreach (array_merge(array('No'), $columns) as $column) {
                if ($column === 'No') {
                    $line[] = 'TOTAL';
                    continue;
                }
                if (isset($totals[$column['name']])) {
                    $line[] = number_format($totals[$column['name']], 2, '.', '');
                } else {
                    $line[] = '';
                }
            }
            fputcsv($stream, $line);
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        $tenant_code = preg_replace('/[^A-Za-z0-9_-]+/', '-', $tenant_name);
        $report_code = preg_replace('/[^A-Za-z0-9_-]+/', '-', $title);
        if ($report_code === '') {
            $report_code = $slug;
        }
        $filename = trim($report_code, '-') . '-' . trim($tenant_code, '-') . '-'
            . ($as_of_report ? $filters['end_on'] : $filters['start_on'] . '-' . $filters['end_on']) . '.csv';
        $this->output->set_content_type('text/csv', 'utf-8')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_output("\xEF\xBB\xBF" . $csv);
    }

    private function finance_cash_flow_statement()
    {
        if (!$this->has_admin_menu_code('fin_cash_flow')) {
            $this->finance_forbidden();
            return;
        }
        $filters = $this->finance_report_dates();
        $method = strtolower((string) $this->input->get('method', TRUE)) === 'indirect' ? 'indirect' : 'direct';
        $this->load->library('finance_report_store');
        $statement = empty($filters['errors'])
            ? $this->finance_report_store->cash_flow_statement(
                (int) $this->current_user['tenant_id'],
                $filters['start_on'],
                $filters['end_on'],
                $method
            )
            : FALSE;
        $tenant_id = (int) $this->current_user['tenant_id'];
        $settings = $this->db->select('base_currency')->where('tenant_id', $tenant_id)->limit(1)->get('fin_settings');
        $base_currency = $settings !== FALSE && $settings->num_rows() ? (string) $settings->row()->base_currency : '';
        $tenant = $this->db->select('tenant_name')->where('tenant_id', $tenant_id)->limit(1)->get('sys_tenant');
        $tenant_name = $tenant !== FALSE && $tenant->num_rows() ? (string) $tenant->row()->tenant_name : '';
        $this->render_admin(
            'admin/finance/report_cash_flow',
            'admin_finance_cash_flow',
            '/admin/finance/cash_flow',
            array(
                'finance_report_filters' => $filters,
                'finance_report_errors' => isset($filters['errors']) ? $filters['errors'] : array(),
                'finance_cf' => $statement,
                'finance_cf_method' => $method,
                'finance_cf_available' => $this->finance_report_store->available('cash_flow'),
                'finance_cf_currency' => $base_currency,
                'finance_cf_tenant' => $tenant_name,
                'finance_cf_can_export' => $this->has_admin_access_code('fin_cash_flow_export'),
            )
        );
    }

    private function finance_cash_flow_export()
    {
        $this->lang->load('finance', $this->resolve_admin_locale());
        $filters = $this->finance_report_dates();
        $method = strtolower((string) $this->input->get('method', TRUE)) === 'indirect' ? 'indirect' : 'direct';
        if (!empty($filters['errors'])) {
            $this->session->set_flashdata('finance_status', 'invalid_range');
            redirect('/admin/finance/cash_flow?method=' . $method, 'location', 303);
            return;
        }
        $this->load->library('finance_report_store');
        $tenant_id = (int) $this->current_user['tenant_id'];
        $statement = $this->finance_report_store->cash_flow_statement($tenant_id, $filters['start_on'], $filters['end_on'], $method);
        if ($statement === FALSE) {
            $this->session->set_flashdata('finance_status', 'unavailable');
            redirect('/admin/finance/cash_flow?method=' . $method, 'location', 303);
            return;
        }
        $settings = $this->db->select('base_currency')->where('tenant_id', $tenant_id)->limit(1)->get('fin_settings');
        $base_currency = $settings !== FALSE && $settings->num_rows() ? (string) $settings->row()->base_currency : '';
        $tenant = $this->db->select('tenant_name')->where('tenant_id', $tenant_id)->limit(1)->get('sys_tenant');
        $tenant_name = $tenant !== FALSE && $tenant->num_rows() ? (string) $tenant->row()->tenant_name : (string) $tenant_id;
        $title = $this->finance_report_text('finance_cash_flow', 'Cash Flow');
        $method_label = $method === 'indirect'
            ? $this->finance_report_text('finance_cf_indirect', 'Indirect Method')
            : $this->finance_report_text('finance_cf_direct', 'Direct Method');
        $section_labels = array(
            'operating' => $this->finance_report_text('finance_cf_operating', 'Cash flows from operating activities'),
            'investing' => $this->finance_report_text('finance_cf_investing', 'Cash flows from investing activities'),
            'financing' => $this->finance_report_text('finance_cf_financing', 'Cash flows from financing activities'),
        );
        $total_labels = array(
            'operating' => $this->finance_report_text('finance_cf_net_operating', 'Net cash from operating activities'),
            'investing' => $this->finance_report_text('finance_cf_net_investing', 'Net cash from investing activities'),
            'financing' => $this->finance_report_text('finance_cf_net_financing', 'Net cash from financing activities'),
        );
        $prepared_by = isset($this->current_user['full_name']) && trim((string) $this->current_user['full_name']) !== ''
            ? (string) $this->current_user['full_name']
            : (isset($this->current_user['username']) ? (string) $this->current_user['username'] : '');
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, array('Report Name', $title));
        fputcsv($stream, array('Report Code', 'cash_flow'));
        fputcsv($stream, array('Tenant', $tenant_name));
        fputcsv($stream, array('Period', $filters['start_on'] . ' s/d ' . $filters['end_on']));
        fputcsv($stream, array('Method', $method_label));
        fputcsv($stream, array('Base Currency', $base_currency));
        fputcsv($stream, array('Generated', date('Y-m-d H:i:s')));
        fputcsv($stream, array('Prepared By', $prepared_by));
        fputcsv($stream, array('Source', 'Posted journals'));
        fputcsv($stream, array());
        fputcsv($stream, array('No', 'Section', 'Group', 'Code', 'Account', 'Amount'));
        $row_number = 0;
        if ($method === 'direct') {
            foreach ($statement['sections'] as $section_key => $section) {
                foreach ($section['items'] as $item) {
                    fputcsv($stream, array(++$row_number, $section_labels[$section_key], '', $item['code'], $this->finance_cash_flow_item_label($item), number_format((float) $item['amount'], 2, '.', '')));
                }
                fputcsv($stream, array('', $section_labels[$section_key], '', '', $total_labels[$section_key], number_format((float) $section['total'], 2, '.', '')));
                fputcsv($stream, array());
            }
        } else {
            fputcsv($stream, array(++$row_number, $section_labels['operating'], '', '', $this->finance_report_text('finance_cf_net_profit', 'Profit before tax'), number_format((float) $statement['net_profit'], 2, '.', '')));
            fputcsv($stream, array('', $section_labels['operating'], $this->finance_report_text('finance_cf_adjustments', 'Adjustments for:'), '', '', ''));
            foreach ($statement['non_cash'] as $item) {
                fputcsv($stream, array(++$row_number, $section_labels['operating'], $this->finance_report_text('finance_cf_adjustments', 'Adjustments for:'), $item['code'], $item['name'], number_format((float) $item['amount'], 2, '.', '')));
            }
            foreach ($statement['working_capital'] as $item) {
                fputcsv($stream, array(++$row_number, $section_labels['operating'], $this->finance_report_text('finance_cf_working_capital', 'Changes in working capital'), $item['code'], $this->finance_cash_flow_item_label($item, 'working_capital'), number_format((float) $item['amount'], 2, '.', '')));
            }
            fputcsv($stream, array('', $section_labels['operating'], '', '', $total_labels['operating'], number_format((float) $statement['indirect_total'], 2, '.', '')));
            fputcsv($stream, array());
            foreach (array('investing', 'financing') as $section_key) {
                $section = $statement['sections'][$section_key];
                foreach ($section['items'] as $item) {
                    fputcsv($stream, array(++$row_number, $section_labels[$section_key], '', $item['code'], $this->finance_cash_flow_item_label($item), number_format((float) $item['amount'], 2, '.', '')));
                }
                fputcsv($stream, array('', $section_labels[$section_key], '', '', $total_labels[$section_key], number_format((float) $section['total'], 2, '.', '')));
                fputcsv($stream, array());
            }
        }
        fputcsv($stream, array('', '', '', '', $this->finance_report_text('finance_cf_net_change', 'Net increase (decrease) in cash and cash equivalents'), number_format((float) $statement['net_change'], 2, '.', '')));
        fputcsv($stream, array('', '', '', '', $this->finance_report_text('finance_cf_beginning_cash', 'Cash and cash equivalents at beginning of period'), number_format((float) $statement['beginning_cash'], 2, '.', '')));
        fputcsv($stream, array('', '', '', '', $this->finance_report_text('finance_cf_ending_cash', 'Cash and cash equivalents at end of period'), number_format((float) $statement['ending_cash'], 2, '.', '')));
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        $tenant_code = preg_replace('/[^A-Za-z0-9_-]+/', '-', $tenant_name);
        $report_code = preg_replace('/[^A-Za-z0-9_-]+/', '-', $title);
        if ($report_code === '') {
            $report_code = 'Arus-Kas';
        }
        $method_code = $method === 'indirect' ? 'Tidak-Langsung' : 'Langsung';
        $filename = trim($report_code, '-') . '-' . $method_code . '-' . trim($tenant_code, '-') . '-' . $filters['start_on'] . '-' . $filters['end_on'] . '.csv';
        $this->output->set_content_type('text/csv', 'utf-8')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_output("\xEF\xBB\xBF" . $csv);
    }

    private function finance_cash_flow_item_label($item, $flow = 'direct')
    {
        if (isset($item['name_key']) && $item['name_key'] !== '') {
            return $this->finance_report_text($item['name_key'], $item['name_key']);
        }
        $name = isset($item['name']) ? (string) $item['name'] : '';
        $amount = isset($item['amount']) ? (float) $item['amount'] : 0.0;
        if ($flow === 'working_capital') {
            $direction = isset($item['direction']) ? $item['direction'] : '';
            if ($direction === 'increase') {
                return $this->finance_report_text('finance_cf_increase', 'Increase in') . ' ' . $name;
            }
            if ($direction === 'decrease') {
                return $this->finance_report_text('finance_cf_decrease', 'Decrease in') . ' ' . $name;
            }
            return $name;
        }
        $type = isset($item['account_type']) ? $item['account_type'] : '';
        $kind = isset($item['kind']) ? $item['kind'] : '';
        if ($type === 'revenue') {
            return $this->finance_report_text('finance_cf_receipt', 'Receipts from') . ' ' . $name;
        }
        if ($type === 'expense') {
            return $this->finance_report_text('finance_cf_payment', 'Payments for') . ' ' . $name;
        }
        if ($kind === 'investing') {
            return $amount < 0
                ? $this->finance_report_text('finance_cf_purchase', 'Purchase of') . ' ' . $name
                : $this->finance_report_text('finance_cf_sale', 'Sale of') . ' ' . $name;
        }
        if ($type === 'liability') {
            return $amount < 0
                ? $this->finance_report_text('finance_cf_repayment', 'Repayment of') . ' ' . $name
                : $this->finance_report_text('finance_cf_receipt', 'Receipts from') . ' ' . $name;
        }
        if ($type === 'equity') {
            return $amount < 0
                ? $this->finance_report_text('finance_cf_reduction', 'Reduction of') . ' ' . $name
                : $this->finance_report_text('finance_cf_addition', 'Addition to') . ' ' . $name;
        }
        return $name;
    }

    private function finance_profit_loss_statement()
    {
        if (!$this->has_admin_menu_code('fin_profit_loss')) {
            $this->finance_forbidden();
            return;
        }
        $filters = $this->finance_report_dates();
        $this->load->library('finance_report_store');
        $statement = empty($filters['errors'])
            ? $this->finance_report_store->profit_loss_statement(
                (int) $this->current_user['tenant_id'],
                $filters['start_on'],
                $filters['end_on']
            )
            : FALSE;
        $tenant_id = (int) $this->current_user['tenant_id'];
        $settings = $this->db->select('base_currency')->where('tenant_id', $tenant_id)->limit(1)->get('fin_settings');
        $base_currency = $settings !== FALSE && $settings->num_rows() ? (string) $settings->row()->base_currency : '';
        $tenant = $this->db->select('tenant_name')->where('tenant_id', $tenant_id)->limit(1)->get('sys_tenant');
        $tenant_name = $tenant !== FALSE && $tenant->num_rows() ? (string) $tenant->row()->tenant_name : '';
        $this->render_admin(
            'admin/finance/report_profit_loss',
            'admin_finance_profit_loss',
            '/admin/finance/profit_loss',
            array(
                'finance_report_filters' => $filters,
                'finance_report_errors' => isset($filters['errors']) ? $filters['errors'] : array(),
                'finance_pl' => $statement,
                'finance_pl_available' => $this->finance_report_store->available('profit_loss'),
                'finance_pl_currency' => $base_currency,
                'finance_pl_tenant' => $tenant_name,
                'finance_pl_can_export' => $this->has_admin_access_code('fin_profit_loss_export'),
            )
        );
    }

    private function finance_profit_loss_export()
    {
        $this->lang->load('finance', $this->resolve_admin_locale());
        $filters = $this->finance_report_dates();
        if (!empty($filters['errors'])) {
            $this->session->set_flashdata('finance_status', 'invalid_range');
            redirect('/admin/finance/profit_loss', 'location', 303);
            return;
        }
        $this->load->library('finance_report_store');
        $tenant_id = (int) $this->current_user['tenant_id'];
        $statement = $this->finance_report_store->profit_loss_statement($tenant_id, $filters['start_on'], $filters['end_on']);
        if ($statement === FALSE) {
            $this->session->set_flashdata('finance_status', 'unavailable');
            redirect('/admin/finance/profit_loss', 'location', 303);
            return;
        }
        $settings = $this->db->select('base_currency')->where('tenant_id', $tenant_id)->limit(1)->get('fin_settings');
        $base_currency = $settings !== FALSE && $settings->num_rows() ? (string) $settings->row()->base_currency : '';
        $tenant = $this->db->select('tenant_name')->where('tenant_id', $tenant_id)->limit(1)->get('sys_tenant');
        $tenant_name = $tenant !== FALSE && $tenant->num_rows() ? (string) $tenant->row()->tenant_name : (string) $tenant_id;
        $title = $this->finance_report_text('finance_profit_loss', 'Profit and Loss');
        $prepared_by = isset($this->current_user['full_name']) && trim((string) $this->current_user['full_name']) !== ''
            ? (string) $this->current_user['full_name']
            : (isset($this->current_user['username']) ? (string) $this->current_user['username'] : '');
        $labels = array(
            'revenue' => $this->finance_report_text('finance_pl_revenue', 'Revenue'),
            'cogs' => $this->finance_report_text('finance_pl_cogs', 'Cost of goods sold'),
            'gross' => $this->finance_report_text('finance_pl_gross_profit', 'Gross profit'),
            'operating' => $this->finance_report_text('finance_pl_operating_expenses', 'Operating expenses'),
            'total_revenue' => $this->finance_report_text('finance_pl_total_revenue', 'Total revenue'),
            'total_cogs' => $this->finance_report_text('finance_pl_total_cogs', 'Total cost of goods sold'),
            'total_operating' => $this->finance_report_text('finance_pl_total_operating', 'Total operating expenses'),
            'total_expenses' => $this->finance_report_text('finance_pl_total_expenses', 'Total expenses'),
            'net' => $this->finance_report_text('finance_pl_net_profit', 'Net profit'),
            'subtotal' => $this->finance_report_text('finance_pl_subtotal', 'Subtotal'),
        );
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, array('Report Name', $title));
        fputcsv($stream, array('Report Code', 'profit_loss'));
        fputcsv($stream, array('Tenant', $tenant_name));
        fputcsv($stream, array('Period', $filters['start_on'] . ' s/d ' . $filters['end_on']));
        fputcsv($stream, array('Base Currency', $base_currency));
        fputcsv($stream, array('Generated', date('Y-m-d H:i:s')));
        fputcsv($stream, array('Prepared By', $prepared_by));
        fputcsv($stream, array('Source', 'Posted journals'));
        fputcsv($stream, array());
        fputcsv($stream, array('No', 'Section', 'Group', 'Code', 'Account', 'Amount'));
        $row_number = 0;
        foreach ($statement['revenue']['accounts'] as $line) {
            fputcsv($stream, array(++$row_number, $labels['revenue'], '', $line['code'], $line['name'], number_format((float) $line['amount'], 2, '.', '')));
        }
        fputcsv($stream, array('', $labels['revenue'], '', '', $labels['total_revenue'], number_format((float) $statement['revenue']['total'], 2, '.', '')));
        fputcsv($stream, array());
        foreach ($statement['cogs']['accounts'] as $line) {
            fputcsv($stream, array(++$row_number, $labels['cogs'], '', $line['code'], $line['name'], number_format((float) $line['amount'], 2, '.', '')));
        }
        fputcsv($stream, array('', $labels['cogs'], '', '', $labels['total_cogs'], number_format((float) $statement['cogs']['total'], 2, '.', '')));
        fputcsv($stream, array('', '', '', '', $labels['gross'], number_format((float) $statement['gross_profit'], 2, '.', '')));
        fputcsv($stream, array());
        foreach ($statement['operating']['groups'] as $group) {
            foreach ($group['accounts'] as $line) {
                fputcsv($stream, array(++$row_number, $labels['operating'], $group['name'], $line['code'], $line['name'], number_format((float) $line['amount'], 2, '.', '')));
            }
            fputcsv($stream, array('', $labels['operating'], $group['name'], '', $labels['subtotal'], number_format((float) $group['total'], 2, '.', '')));
        }
        fputcsv($stream, array('', $labels['operating'], '', '', $labels['total_operating'], number_format((float) $statement['operating']['total'], 2, '.', '')));
        fputcsv($stream, array('', '', '', '', $labels['total_expenses'], number_format((float) $statement['total_expenses'], 2, '.', '')));
        fputcsv($stream, array('', '', '', '', $labels['net'], number_format((float) $statement['net_profit'], 2, '.', '')));
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        $tenant_code = preg_replace('/[^A-Za-z0-9_-]+/', '-', $tenant_name);
        $report_code = preg_replace('/[^A-Za-z0-9_-]+/', '-', $title);
        if ($report_code === '') {
            $report_code = 'Laba-Rugi';
        }
        $filename = trim($report_code, '-') . '-' . trim($tenant_code, '-') . '-' . $filters['start_on'] . '-' . $filters['end_on'] . '.csv';
        $this->output->set_content_type('text/csv', 'utf-8')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_output("\xEF\xBB\xBF" . $csv);
    }

    private function finance_report_text($key, $fallback)
    {
        $label = $this->lang->line($key);
        return is_string($label) && $label !== '' ? $label : $fallback;
    }

    private function finance_balance_sheet_skontro()
    {
        if (!$this->has_admin_menu_code('fin_balance_sheet')) {
            $this->finance_forbidden();
            return;
        }
        $filters = $this->finance_report_dates();
        $this->load->library('finance_report_store');
        $report = empty($filters['errors'])
            ? $this->finance_report_store->balance_sheet_skontro((int) $this->current_user['tenant_id'], $filters['end_on'])
            : FALSE;
        $tenant_id = (int) $this->current_user['tenant_id'];
        $settings = $this->db->select('base_currency')->where('tenant_id', $tenant_id)->limit(1)->get('fin_settings');
        $base_currency = $settings !== FALSE && $settings->num_rows() ? (string) $settings->row()->base_currency : '';
        $tenant = $this->db->select('tenant_name')->where('tenant_id', $tenant_id)->limit(1)->get('sys_tenant');
        $tenant_name = $tenant !== FALSE && $tenant->num_rows() ? (string) $tenant->row()->tenant_name : '';
        $this->render_admin(
            'admin/finance/report_balance_sheet_skontro',
            'finance_balance_sheet_skontro',
            '/admin/finance/balance_sheet_skontro',
            array(
                'finance_report_filters' => $filters,
                'finance_report_errors' => isset($filters['errors']) ? $filters['errors'] : array(),
                'finance_skontro' => $report,
                'finance_skontro_available' => $this->finance_report_store->available('balance_sheet'),
                'finance_skontro_currency' => $base_currency,
                'finance_skontro_tenant' => $tenant_name,
                'finance_skontro_can_export' => $this->has_admin_access_code('fin_balance_sheet_export'),
            )
        );
    }

    private function finance_balance_sheet_skontro_export()
    {
        $this->lang->load('finance', $this->resolve_admin_locale());
        $filters = $this->finance_report_dates();
        if (!empty($filters['errors'])) {
            $this->session->set_flashdata('finance_status', 'invalid_range');
            redirect('/admin/finance/balance_sheet_skontro', 'location', 303);
            return;
        }
        $this->load->library('finance_report_store');
        $tenant_id = (int) $this->current_user['tenant_id'];
        $report = $this->finance_report_store->balance_sheet_skontro($tenant_id, $filters['end_on']);
        if ($report === FALSE) {
            $this->session->set_flashdata('finance_status', 'unavailable');
            redirect('/admin/finance/balance_sheet_skontro', 'location', 303);
            return;
        }
        $settings = $this->db->select('base_currency')->where('tenant_id', $tenant_id)->limit(1)->get('fin_settings');
        $base_currency = $settings !== FALSE && $settings->num_rows() ? (string) $settings->row()->base_currency : '';
        $tenant = $this->db->select('tenant_name')->where('tenant_id', $tenant_id)->limit(1)->get('sys_tenant');
        $tenant_name = $tenant !== FALSE && $tenant->num_rows() ? (string) $tenant->row()->tenant_name : (string) $tenant_id;
        $title = $this->lang->line('finance_balance_sheet_skontro');
        if (!is_string($title) || $title === '') {
            $title = 'Balance Sheet (Skontro)';
        }
        $section_labels = array(
            'asset' => $this->finance_skontro_label('finance_skontro_assets', 'Assets'),
            'liability' => $this->finance_skontro_label('finance_skontro_liabilities', 'Liabilities'),
            'equity' => $this->finance_skontro_label('finance_skontro_equity', 'Equity'),
        );
        $prepared_by = isset($this->current_user['full_name']) && trim((string) $this->current_user['full_name']) !== ''
            ? (string) $this->current_user['full_name']
            : (isset($this->current_user['username']) ? (string) $this->current_user['username'] : '');
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, array('Report Name', $title));
        fputcsv($stream, array('Report Code', 'balance_sheet_skontro'));
        fputcsv($stream, array('Tenant', $tenant_name));
        fputcsv($stream, array('As of', $filters['end_on']));
        fputcsv($stream, array('Base Currency', $base_currency));
        fputcsv($stream, array('Generated', date('Y-m-d H:i:s')));
        fputcsv($stream, array('Prepared By', $prepared_by));
        fputcsv($stream, array('Source', 'Posted journals'));
        fputcsv($stream, array());
        fputcsv($stream, array('No', 'Section', 'Group', 'Code', 'Account', 'Amount'));
        $row_number = 0;
        foreach ($section_labels as $type => $section_label) {
            foreach ($report['sections'][$type]['groups'] as $group) {
                $group_label = isset($group['name_key'])
                    ? $this->finance_skontro_label($group['name_key'], $section_label)
                    : $group['name'];
                foreach ($group['accounts'] as $line) {
                    fputcsv($stream, array(++$row_number, $section_label, $group_label, $line['code'], $line['name'], number_format((float) $line['amount'], 2, '.', '')));
                }
                fputcsv($stream, array('', $section_label, $group_label, '', $this->finance_skontro_label('finance_skontro_subtotal', 'Subtotal'), number_format((float) $group['total'], 2, '.', '')));
            }
            fputcsv($stream, array('', $section_label, '', '', $type === 'asset'
                ? $this->finance_skontro_label('finance_skontro_total_assets', 'Total assets')
                : ($type === 'liability'
                    ? $this->finance_skontro_label('finance_skontro_total_liabilities', 'Total liabilities')
                    : $this->finance_skontro_label('finance_skontro_total_equity', 'Total equity')),
                number_format((float) $report['sections'][$type]['total'], 2, '.', '')));
            fputcsv($stream, array());
        }
        fputcsv($stream, array('', '', '', '', $this->finance_skontro_label('finance_skontro_total_liabilities_equity', 'Total liabilities and equity'), number_format((float) $report['total_liabilities_equity'], 2, '.', '')));
        fputcsv($stream, array('', '', '', '', $this->finance_skontro_label('finance_skontro_balance_check', 'Balance check'), $report['balanced']
            ? $this->finance_skontro_label('finance_skontro_balanced', 'Balanced')
            : $this->finance_skontro_label('finance_skontro_not_balanced', 'Not balanced')));
        if (!$report['balanced']) {
            fputcsv($stream, array('', '', '', '', $this->finance_skontro_label('finance_skontro_difference', 'Difference'), number_format((float) $report['difference'], 2, '.', '')));
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        $tenant_code = preg_replace('/[^A-Za-z0-9_-]+/', '-', $tenant_name);
        $report_code = preg_replace('/[^A-Za-z0-9_-]+/', '-', $title);
        if ($report_code === '') {
            $report_code = 'Neraca-Skontro';
        }
        $filename = trim($report_code, '-') . '-' . trim($tenant_code, '-') . '-' . $filters['end_on'] . '.csv';
        $this->output->set_content_type('text/csv', 'utf-8')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_output("\xEF\xBB\xBF" . $csv);
    }

    private function finance_skontro_label($key, $fallback)
    {
        $label = $this->lang->line($key);
        return is_string($label) && $label !== '' ? $label : $fallback;
    }

    private function finance_report_index($slug)
    {
        $menu_code = $this->finance_report_menu_code($slug);
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->finance_forbidden();
            return;
        }
        $filters = $this->finance_report_dates();
        $this->load->library('finance_report_store');
        $columns = $this->finance_report_columns($slug);
        foreach ($columns as &$column) {
            $column['label_key'] = 'finance_report_column_' . $column['name'];
        }
        unset($column);
        $title_key = 'finance_' . $slug;
        $summary = empty($filters['errors'])
            ? $this->finance_report_store->summary($slug, (int) $this->current_user['tenant_id'], $filters['start_on'], $filters['end_on'])
            : FALSE;
        $balance_totals = FALSE;
        if ($slug === 'balance_sheet' && empty($filters['errors'])) {
            $balance_totals = $this->finance_report_store->balance_sheet_skontro((int) $this->current_user['tenant_id'], $filters['end_on']);
        }
        $settings = $this->db->select('base_currency')->where('tenant_id', (int) $this->current_user['tenant_id'])->limit(1)->get('fin_settings');
        $base_currency = $settings !== FALSE && $settings->num_rows() ? (string) $settings->row()->base_currency : '';
        $this->render_admin(
            'admin/finance/report_index',
            $title_key,
            '/admin/finance/' . $slug,
            array(
                'finance_report_slug' => $slug,
                'finance_report_columns' => $columns,
                'finance_report_filters' => $filters,
                'finance_report_errors' => isset($filters['errors']) ? $filters['errors'] : array(),
                'finance_report_available' => $this->finance_report_store->available($slug),
                'finance_report_summary' => $summary,
                'finance_report_base_currency' => $base_currency,
                'finance_report_can_export' => $this->has_admin_access_code($menu_code . '_export'),
                'finance_report_balance_totals' => $balance_totals,
            )
        );
    }

    private function finance_dashboard()
    {
        if (!$this->has_admin_menu_code('fin_dashboard')) {
            $this->finance_forbidden();
            return;
        }
        $this->load->library('finance_report_store');
        $summary = $this->finance_report_store->dashboard((int) $this->current_user['tenant_id']);
        $this->render_admin(
            'admin/finance/dashboard',
            'finance_dashboard',
            '/admin/finance/dashboard',
            array('finance_dashboard_data' => $summary)
        );
    }

    private function finance_report_data($slug)
    {
        $menu_code = $this->finance_report_menu_code($slug);
        $this->load->library('datatable_server');
        if (!$this->has_admin_menu_code($menu_code)) {
            $this->datatable_server->error(403);
            return;
        }
        $filters = $this->finance_report_dates();
        if (!empty($filters['errors'])) {
            $this->datatable_server->error(400);
            return;
        }
        $this->load->library('finance_report_store');
        $store = $this->finance_report_store;
        if (!$store->available($slug)) {
            $this->datatable_server->error(503);
            return;
        }
        $columns = array();
        foreach ($this->finance_report_columns($slug) as $index => $column) {
            $columns[$index] = $column['name'];
        }
        $tenant_id = (int) $this->current_user['tenant_id'];
        $start = $filters['start_on'];
        $end = $filters['end_on'];
        $this->datatable_server->respond(
            $columns,
            function () use ($store, $slug, $tenant_id, $start, $end) {
                return $store->count($slug, $tenant_id, $start, $end);
            },
            function ($search) use ($store, $slug, $tenant_id, $start, $end) {
                return $store->count($slug, $tenant_id, $start, $end, $search);
            },
            function ($search, $length, $offset, $order, $direction) use ($store, $slug, $tenant_id, $start, $end) {
                return $store->page($slug, $tenant_id, $start, $end, $search, $length, $offset, $order, $direction);
            },
            array('max_length' => 50, 'max_search_length' => 120, 'default_order' => $this->finance_report_columns($slug)[0]['name'])
        );
    }

    private function finance_list_filters($with_date = TRUE, $date_preset = 'transaction', $primary_date = '')
    {
        return $this->finance_filter_panel->filters($this->finance_filter_panel->config(array(
            'date' => $with_date,
            'date_preset' => $date_preset,
            'primary_date' => $primary_date,
            'status_preset' => $with_date ? 'transaction' : 'active_flag',
            'flag' => !$with_date,
        )));
    }

    private function finance_report_dates()
    {
        $today = date('Y-m-d');
        $default_start = date('Y-m-d', strtotime('-6 days'));
        $start = $this->input->get('start_on', TRUE);
        $end = $this->input->get('end_on', TRUE);
        $start = is_string($start) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) ? $start : $default_start;
        $end = is_string($end) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end) ? $end : $today;
        $errors = array();
        foreach (array($start, $end) as $date_value) {
            $date = DateTime::createFromFormat('!Y-m-d', $date_value);
            if ($date === FALSE || $date->format('Y-m-d') !== $date_value) {
                $errors[] = 'invalid_date';
            }
        }
        if ($start > $end) {
            $errors[] = 'date_range_invalid';
        }
        return array('start_on' => $start, 'end_on' => $end, 'errors' => array_values(array_unique($errors)));
    }

    private function finance_report_menu_code($slug)
    {
        $codes = array(
            'profit_loss' => 'fin_profit_loss',
            'profit_loss_detail' => 'fin_profit_loss',
            'balance_sheet' => 'fin_balance_sheet',
            'balance_sheet_skontro' => 'fin_balance_sheet',
            'cash_flow' => 'fin_cash_flow',
            'cash_flow_detail' => 'fin_cash_flow',
            'trial_balance' => 'fin_trial_balance',
            'ar_report' => 'fin_ar_report',
            'ap_report' => 'fin_ap_report',
            'general_ledger' => 'fin_general_ledger',
        );
        return isset($codes[$slug]) ? $codes[$slug] : NULL;
    }

    private function finance_report_columns($slug)
    {
        if ($slug === 'cash_flow_detail') {
            $slug = 'cash_flow';
        }
        $columns = array(
            'general_ledger' => array(
                array('name' => 'journal_date', 'label' => 'Date'),
                array('name' => 'journal_number', 'label' => 'Journal'),
                array('name' => 'account_code', 'label' => 'Account'),
                array('name' => 'account_name', 'label' => 'Account name'),
                array('name' => 'description', 'label' => 'Description'),
                array('name' => 'currency_code', 'label' => 'Currency'),
                array('name' => 'debit_base_amount', 'label' => 'Debit (base)'),
                array('name' => 'credit_base_amount', 'label' => 'Credit (base)'),
            ),
            'cash_flow' => array(
                array('name' => 'period', 'label' => 'Month'),
                array('name' => 'account_code', 'label' => 'Cash/bank code'),
                array('name' => 'account_name', 'label' => 'Cash/bank account'),
                array('name' => 'cash_in', 'label' => 'Cash in (base)'),
                array('name' => 'cash_out', 'label' => 'Cash out (base)'),
            ),
            'ar_report' => array(
                array('name' => 'invoice_number', 'label' => 'Invoice'),
                array('name' => 'customer_name', 'label' => 'Customer'),
                array('name' => 'invoice_date', 'label' => 'Invoice date'),
                array('name' => 'due_date', 'label' => 'Due date'),
                array('name' => 'currency_code', 'label' => 'Currency'),
                array('name' => 'billed', 'label' => 'Billed (base)'),
                array('name' => 'paid', 'label' => 'Paid (base)'),
                array('name' => 'outstanding', 'label' => 'Outstanding (base)'),
            ),
            'ap_report' => array(
                array('name' => 'bill_number', 'label' => 'Bill'),
                array('name' => 'supplier_name', 'label' => 'Supplier'),
                array('name' => 'bill_date', 'label' => 'Bill date'),
                array('name' => 'due_date', 'label' => 'Due date'),
                array('name' => 'currency_code', 'label' => 'Currency'),
                array('name' => 'billed', 'label' => 'Billed (base)'),
                array('name' => 'paid', 'label' => 'Paid (base)'),
                array('name' => 'outstanding', 'label' => 'Outstanding (base)'),
            ),
        );
        if (isset($columns[$slug])) {
            return $columns[$slug];
        }
        return array(
            array('name' => 'account_code', 'label' => 'Account'),
            array('name' => 'account_name', 'label' => 'Account name'),
            array('name' => 'account_type', 'label' => 'Type'),
            array('name' => 'debit_total', 'label' => 'Debits (base)'),
            array('name' => 'credit_total', 'label' => 'Credits (base)'),
            array('name' => 'net_debit', 'label' => in_array($slug, array('profit_loss', 'profit_loss_detail', 'balance_sheet'), TRUE) ? 'Balance (base)' : 'Net debit (base)'),
        );
    }

    private function finance_fiscal_year_label($tenant_id, $id)
    {
        if ((int) $id < 1) {
            return '';
        }
        $row = $this->db->select('year_code,year_name,start_on,end_on')->from('fin_fiscal_year')
            ->where('tenant_id', (int) $tenant_id)->where('fiscal_year_id', (int) $id)->limit(1)->get()->row_array();
        return $row ? $row['year_code'] . ' — ' . $row['year_name'] . ' (' . $row['start_on'] . ' s/d ' . $row['end_on'] . ')' : '';
    }

    private function finance_form($resource, $id)
    {
        $this->load->library('finance_store');
        $store = $this->finance_store;
        $available = $store->available($resource);
        $record = array();
        foreach ($resource['fields'] as $field) {
            $record[$field['name']] = isset($field['default']) ? $field['default'] : '';
        }
        if ($id === NULL && $resource['table'] !== 'fin_settings') {
            $has_currency = FALSE;
            foreach ($resource['fields'] as $field) {
                if ($field['name'] === 'currency_code') {
                    $has_currency = TRUE;
                    break;
                }
            }
            if ($has_currency) {
                $base_settings = $this->db->select('base_currency')
                    ->where('tenant_id', (int) $this->current_user['tenant_id'])->limit(1)->get('fin_settings');
                if ($base_settings !== FALSE && $base_settings->num_rows()) {
                    $record['currency_code'] = strtoupper((string) $base_settings->row()->base_currency);
                }
            }
        }
        foreach ($resource['columns'] as &$column) {
            if (!isset($column['label_key'])) {
                $column['label_key'] = 'finance_resource_column_' . $column['name'];
            }
        }
        unset($column);
        foreach ($resource['fields'] as &$field) {
            if (!isset($field['label_key'])) {
                $field['label_key'] = 'finance_resource_field_' . $field['name'];
            }
            if (!empty($field['options']) && !isset($field['option_label_keys'])) {
                $field['option_label_keys'] = array();
                foreach ($field['options'] as $option => $option_label) {
                    $field['option_label_keys'][$option] = 'finance_resource_option_' . $field['name'] . '_' . $option;
                }
            }
        }
        unset($field);
        $lookup_labels = array();
        if ($id !== NULL && $available) {
            $saved = $store->find($resource, (int) $this->current_user['tenant_id'], $id);
            if ($saved === NULL) {
                show_404();
                return;
            }
            if ($saved === FALSE) {
                $available = FALSE;
            } else {
                $record = array_merge($record, $saved);
                foreach ($resource['fields'] as $field) {
                    if ($field['type'] === 'account_lookup' && !empty($record[$field['name']])) {
                        $lookup_labels[$field['name']] = $store->account_label(
                            (int) $this->current_user['tenant_id'],
                            $record[$field['name']]
                        );
                    } elseif ($field['type'] === 'fiscal_year_lookup' && !empty($record[$field['name']])) {
                        $lookup_labels[$field['name']] = $this->finance_fiscal_year_label(
                            (int) $this->current_user['tenant_id'],
                            $record[$field['name']]
                        );
                    }
                }
            }
        }

        $errors = array();
        $write_available = $this->finance_write_available() && $available;
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Finance writes are unavailable.', 503);
                return;
            }
            $values = $this->finance_form_values($resource, $id === NULL);
            $errors = $this->finance_validate(
                $resource,
                $values,
                $id === NULL,
                $store,
                (int) $this->current_user['tenant_id'],
                $id === NULL ? NULL : (int) $id
            );
            if (!$errors) {
                $tenant_id = (int) $this->current_user['tenant_id'];
                $user_id = (int) $this->current_user['user_id'];
                $previous_db_debug = $this->db->db_debug;
                $this->db->db_debug = FALSE;
                $saved = $id === NULL
                    ? $store->insert($resource, $tenant_id, $values, $user_id)
                    : $store->update($resource, $tenant_id, $id, $values, $user_id);
                $this->db->db_debug = $previous_db_debug;
                if ($saved === TRUE || $saved === 'updated' || $saved === 'unchanged') {
                    $this->session->set_flashdata('finance_status', $id === NULL ? 'created' : 'saved');
                    redirect('/admin/finance/' . $resource['slug'], 'location', 303);
                    return;
                }
                $errors[] = 'save_error';
            }
            $record = array_merge($record, $values);
            foreach ($resource['fields'] as $field) {
                if ($field['type'] === 'account_lookup' && !empty($record[$field['name']])) {
                    $lookup_labels[$field['name']] = $store->account_label(
                        (int) $this->current_user['tenant_id'],
                        $record[$field['name']]
                    );
                } elseif ($field['type'] === 'fiscal_year_lookup' && !empty($record[$field['name']])) {
                    $lookup_labels[$field['name']] = $this->finance_fiscal_year_label(
                        (int) $this->current_user['tenant_id'],
                        $record[$field['name']]
                    );
                }
            }
        }

        $path = '/admin/finance/' . $resource['slug'] . ($id === NULL ? '/new' : '/edit/' . $id);
        $this->render_admin(
            'admin/finance/resource_form',
            $resource['title_key'],
            $path,
            array(
                'finance_resource' => $resource,
                'finance_record' => $record,
                'finance_errors' => $errors,
                'finance_id' => $id,
                'finance_available' => $available,
                'finance_write_available' => $write_available,
                'finance_lookup_labels' => $lookup_labels,
                'finance_csrf_name' => $this->security->get_csrf_token_name(),
                'finance_csrf_hash' => $this->security->get_csrf_hash(),
            )
        );
    }

    private function finance_form_values($resource, $include_code)
    {
        $values = array();
        foreach ($resource['fields'] as $field) {
            if (!$include_code && !empty($field['immutable_on_update'])) {
                continue;
            }
            $name = $field['name'];
            $raw = $this->input->post($name, TRUE);
            $value = is_scalar($raw) ? trim((string) $raw) : '';
            if ($value === '' && in_array($field['type'], array('number', 'account_lookup'), TRUE) && empty($field['required'])) {
                $values[$name] = NULL;
                continue;
            }
            if (isset($field['normalize']) && $field['normalize'] === 'code') {
                $value = strtolower(preg_replace('/\s+/', '', $value));
            } elseif (isset($field['normalize']) && $field['normalize'] === 'name') {
                $value = $this->finance_normalize_name($value);
            } elseif (isset($field['normalize']) && $field['normalize'] === 'currency') {
                $value = strtoupper($value);
            }
            $values[$name] = $value;
        }
        if (isset($resource['defaults'])) {
            $values = array_merge($resource['defaults'], $values);
        }
        return $values;
    }

    private function finance_validate($resource, $values, $include_code, $store, $tenant_id, $id = NULL)
    {
        $errors = array();
        foreach ($resource['fields'] as $field) {
            $name = $field['name'];
            if (!$include_code && !empty($field['immutable_on_update'])) {
                continue;
            }
            $value = isset($values[$name]) ? (string) $values[$name] : '';
            if (!empty($field['required']) && $value === '') {
                $errors[] = $name . '_required';
                continue;
            }
            if ($value !== '' && isset($field['maxlength']) && strlen($value) > (int) $field['maxlength']) {
                $errors[] = $name . '_too_long';
            }
            if ($value !== '' && $field['type'] === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL) === FALSE) {
                $errors[] = $name . '_invalid';
            }
            if ($value !== '' && $field['type'] === 'currency' && !preg_match('/^[A-Z]{3}$/', $value)) {
                $errors[] = $name . '_invalid';
            }
            if ($value !== '' && $field['type'] === 'decimal'
                && !preg_match('/^\d{1,14}(?:\.\d{1,2})?$/', $value)) {
                $errors[] = $name . '_invalid';
            }
            if ($value !== '' && $field['type'] === 'number' && !preg_match('/^\d+$/', $value)) {
                $errors[] = $name . '_invalid';
            }
            if ($value !== '' && $field['type'] === 'number' && (int) $value < 1) {
                $errors[] = $name . '_invalid';
            }
            if ($value !== '' && $field['type'] === 'account_lookup'
                && (!preg_match('/^\d+$/', $value) || (int) $value < 1)) {
                $errors[] = $name . '_invalid';
            }
            if ($value !== '' && isset($field['max']) && is_numeric($value) && (float) $value > (float) $field['max']) {
                $errors[] = $name . '_invalid';
            }
            if ($value !== '' && isset($field['reference'])
                && !$store->related_exists(
                    $field['reference']['table'],
                    $field['reference']['id'],
                    $tenant_id,
                    $value
                )) {
                $errors[] = $name . '_invalid';
            }
            if ($value !== '' && $field['type'] === 'date') {
                $date = DateTime::createFromFormat('!Y-m-d', $value);
                if ($date === FALSE || $date->format('Y-m-d') !== $value) {
                    $errors[] = $name . '_invalid';
                }
            }
            if ($value !== '' && isset($field['options'])
                && !array_key_exists($value, $field['options'])) {
                $errors[] = $name . '_invalid';
            }
        }
        if ($resource['slug'] === 'chart_of_accounts' && $id !== NULL
            && !empty($values['parent_account_id'])) {
            $current = (int) $values['parent_account_id'];
            $guard = 0;
            while ($current > 0 && $guard < 100) {
                if ($current === (int) $id) {
                    $errors[] = 'parent_account_id_invalid';
                    break;
                }
                $parent = $this->db->select('parent_account_id')->where('tenant_id', (int) $tenant_id)
                    ->where('account_id', $current)->limit(1)->get('fin_account')->row_array();
                $current = $parent && !empty($parent['parent_account_id']) ? (int) $parent['parent_account_id'] : 0;
                $guard++;
            }
        }
        return array_values(array_unique($errors));
    }

    private function finance_normalize_name($value)
    {
        return preg_replace_callback('/(^|\s)(\S)/u', function ($match) {
            $first = function_exists('mb_strtoupper')
                ? mb_strtoupper($match[2], 'UTF-8')
                : strtoupper($match[2]);
            return $match[1] . $first;
        }, $value);
    }

    private function finance_menu_allowed($resource)
    {
        return isset($this->current_user['tenant_id'])
            && $this->has_admin_menu_code($resource['menu_code']);
    }

    private function finance_write_available()
    {
        return $this->current_user !== NULL && (bool) config_item('csrf_protection');
    }

    private function finance_forbidden()
    {
        $this->output->set_status_header(403);
        show_error('You do not have permission to access this Finance page.', 403);
    }

    private function finance_resource($slug)
    {
        if (!is_string($slug)) {
            return NULL;
        }
        $resources = $this->finance_resources();
        return isset($resources[$slug]) ? $resources[$slug] : NULL;
    }

    private function finance_resources()
    {
        $active = array('name' => 'active_flag', 'label' => 'Active', 'type' => 'select', 'default' => '1',
            'options' => array('1' => 'Active', '0' => 'Inactive'));
        $resources = array(
            'chart_of_accounts' => array(
                'slug' => 'chart_of_accounts', 'menu_code' => 'fin_chart_of_accounts', 'table' => 'fin_account',
                'id' => 'account_id', 'title_key' => 'finance_chart_of_accounts',
                'search' => array('account_code', 'account_name'),
                'columns' => array(
                    array('name' => 'account_code', 'label' => 'Code'),
                    array('name' => 'account_name', 'label' => 'Account'),
                    array('name' => 'account_type', 'label' => 'Type'),
                    array('name' => 'normal_balance', 'label' => 'Normal balance'),
                    array('name' => 'active_flag', 'label' => 'Active'),
                ),
                'fields' => array(
                    array('name' => 'account_code', 'label' => 'Code', 'type' => 'text', 'required' => TRUE, 'maxlength' => 32, 'normalize' => 'code'),
                    array('name' => 'account_name', 'label' => 'Account name', 'type' => 'text', 'required' => TRUE, 'maxlength' => 120, 'normalize' => 'name'),
                    array('name' => 'account_type', 'label' => 'Account type', 'type' => 'select', 'required' => TRUE,
                        'options' => array('asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'revenue' => 'Revenue', 'expense' => 'Expense')),
                    array('name' => 'normal_balance', 'label' => 'Normal balance', 'type' => 'select', 'required' => TRUE,
                        'options' => array('debit' => 'Debit', 'credit' => 'Credit')),
                    array('name' => 'parent_account_id', 'label' => 'Parent account', 'type' => 'account_lookup',
                        'reference' => array('table' => 'fin_account', 'id' => 'account_id')),
                    array('name' => 'description', 'label' => 'Description', 'type' => 'textarea'),
                    $active,
                ),
            ),
            'customers' => array(
                'slug' => 'customers', 'menu_code' => 'fin_customers', 'table' => 'fin_customer',
                'id' => 'customer_id', 'title_key' => 'finance_customers',
                'search' => array('customer_code', 'customer_name', 'email_address'),
                'columns' => array(
                    array('name' => 'customer_code', 'label' => 'Code'),
                    array('name' => 'customer_name', 'label' => 'Customer'),
                    array('name' => 'email_address', 'label' => 'Email'),
                    array('name' => 'phone_number', 'label' => 'Phone'),
                    array('name' => 'active_flag', 'label' => 'Active'),
                ),
                'fields' => array(
                    array('name' => 'customer_code', 'label' => 'Code', 'type' => 'text', 'required' => TRUE, 'maxlength' => 32, 'normalize' => 'code'),
                    array('name' => 'customer_name', 'label' => 'Customer name', 'type' => 'text', 'required' => TRUE, 'maxlength' => 160, 'normalize' => 'name'),
                    array('name' => 'email_address', 'label' => 'Email', 'type' => 'email', 'maxlength' => 160),
                    array('name' => 'phone_number', 'label' => 'Phone', 'type' => 'text', 'maxlength' => 32),
                    array('name' => 'tax_number', 'label' => 'Tax number', 'type' => 'text', 'maxlength' => 64),
                    array('name' => 'billing_address', 'label' => 'Billing address', 'type' => 'textarea'),
                    $active,
                ),
            ),
            'suppliers' => array(
                'slug' => 'suppliers', 'menu_code' => 'fin_suppliers', 'table' => 'fin_supplier',
                'id' => 'supplier_id', 'title_key' => 'finance_suppliers',
                'search' => array('supplier_code', 'supplier_name', 'email_address'),
                'columns' => array(
                    array('name' => 'supplier_code', 'label' => 'Code'),
                    array('name' => 'supplier_name', 'label' => 'Supplier'),
                    array('name' => 'email_address', 'label' => 'Email'),
                    array('name' => 'phone_number', 'label' => 'Phone'),
                    array('name' => 'active_flag', 'label' => 'Active'),
                ),
                'fields' => array(
                    array('name' => 'supplier_code', 'label' => 'Code', 'type' => 'text', 'required' => TRUE, 'maxlength' => 32, 'normalize' => 'code'),
                    array('name' => 'supplier_name', 'label' => 'Supplier name', 'type' => 'text', 'required' => TRUE, 'maxlength' => 160, 'normalize' => 'name'),
                    array('name' => 'email_address', 'label' => 'Email', 'type' => 'email', 'maxlength' => 160),
                    array('name' => 'phone_number', 'label' => 'Phone', 'type' => 'text', 'maxlength' => 32),
                    array('name' => 'tax_number', 'label' => 'Tax number', 'type' => 'text', 'maxlength' => 64),
                    array('name' => 'billing_address', 'label' => 'Billing address', 'type' => 'textarea'),
                    $active,
                ),
            ),
            'categories' => array(
                'slug' => 'categories', 'menu_code' => 'fin_categories', 'table' => 'fin_category',
                'id' => 'category_id', 'title_key' => 'finance_categories',
                'search' => array('category_code', 'category_name', 'category_type'),
                'columns' => array(
                    array('name' => 'category_code', 'label' => 'Code'),
                    array('name' => 'category_name', 'label' => 'Category'),
                    array('name' => 'category_type', 'label' => 'Type'),
                    array('name' => 'active_flag', 'label' => 'Active'),
                ),
                'fields' => array(
                    array('name' => 'category_code', 'label' => 'Code', 'type' => 'text', 'required' => TRUE, 'maxlength' => 32, 'normalize' => 'code'),
                    array('name' => 'category_name', 'label' => 'Category name', 'type' => 'text', 'required' => TRUE, 'maxlength' => 100, 'normalize' => 'name'),
                    array('name' => 'category_type', 'label' => 'Category type', 'type' => 'select', 'required' => TRUE,
                        'options' => array('income' => 'Income', 'expense' => 'Expense')),
                    $active,
                ),
            ),
            'departments' => array(
                'slug' => 'departments', 'menu_code' => 'fin_departments', 'table' => 'fin_department',
                'id' => 'department_id', 'title_key' => 'finance_departments',
                'search' => array('department_code', 'department_name'),
                'columns' => array(
                    array('name' => 'department_code', 'label' => 'Code'),
                    array('name' => 'department_name', 'label' => 'Department'),
                    array('name' => 'active_flag', 'label' => 'Active'),
                ),
                'fields' => array(
                    array('name' => 'department_code', 'label' => 'Code', 'type' => 'text', 'required' => TRUE, 'maxlength' => 32, 'normalize' => 'code'),
                    array('name' => 'department_name', 'label' => 'Department name', 'type' => 'text', 'required' => TRUE, 'maxlength' => 100, 'normalize' => 'name'),
                    $active,
                ),
            ),
            'tax_configuration' => array(
                'slug' => 'tax_configuration', 'menu_code' => 'fin_tax_configuration', 'table' => 'fin_tax',
                'id' => 'tax_id', 'title_key' => 'finance_tax_configuration',
                'search' => array('tax_code', 'tax_name'),
                'columns' => array(
                    array('name' => 'tax_code', 'label' => 'Code'),
                    array('name' => 'tax_name', 'label' => 'Tax'),
                    array('name' => 'rate_percent', 'label' => 'Rate %'),
                    array('name' => 'active_flag', 'label' => 'Active'),
                ),
                'fields' => array(
                    array('name' => 'tax_code', 'label' => 'Code', 'type' => 'text', 'required' => TRUE, 'maxlength' => 32, 'normalize' => 'code'),
                    array('name' => 'tax_name', 'label' => 'Tax name', 'type' => 'text', 'required' => TRUE, 'maxlength' => 100, 'normalize' => 'name'),
                    array('name' => 'rate_percent', 'label' => 'Rate percent', 'type' => 'decimal', 'required' => TRUE, 'max' => 100),
                    array('name' => 'account_id', 'label' => 'Tax account', 'type' => 'account_lookup',
                        'reference' => array('table' => 'fin_account', 'id' => 'account_id')),
                    $active,
                ),
            ),
            'fiscal_year' => array(
                'slug' => 'fiscal_year', 'menu_code' => 'fin_fiscal_year', 'table' => 'fin_fiscal_year',
                'id' => 'fiscal_year_id', 'title_key' => 'finance_fiscal_year',
                'search' => array('year_code', 'year_name'),
                'columns' => array(
                    array('name' => 'year_code', 'label' => 'Code'),
                    array('name' => 'year_name', 'label' => 'Fiscal year'),
                    array('name' => 'start_on', 'label' => 'Start'),
                    array('name' => 'end_on', 'label' => 'End'),
                    array('name' => 'status', 'label' => 'Status'),
                ),
                'fields' => array(
                    array('name' => 'year_code', 'label' => 'Code', 'type' => 'text', 'required' => TRUE, 'maxlength' => 32, 'normalize' => 'code'),
                    array('name' => 'year_name', 'label' => 'Fiscal year name', 'type' => 'text', 'required' => TRUE, 'maxlength' => 80, 'normalize' => 'name'),
                    array('name' => 'start_on', 'label' => 'Start date', 'type' => 'date', 'required' => TRUE),
                    array('name' => 'end_on', 'label' => 'End date', 'type' => 'date', 'required' => TRUE),
                    array('name' => 'status', 'label' => 'Status', 'type' => 'select', 'default' => 'open',
                        'options' => array('open' => 'Open', 'closed' => 'Closed')),
                ),
            ),
            'accounting_periods' => array(
                'slug' => 'accounting_periods', 'menu_code' => 'fin_accounting_periods', 'table' => 'fin_accounting_period',
                'id' => 'period_id', 'title_key' => 'finance_accounting_periods',
                'search' => array('period_name', 'status'),
                'columns' => array(
                    array('name' => 'period_number', 'label' => 'Number'),
                    array('name' => 'period_name', 'label' => 'Period'),
                    array('name' => 'start_on', 'label' => 'Start'),
                    array('name' => 'end_on', 'label' => 'End'),
                    array('name' => 'status', 'label' => 'Status'),
                ),
                'fields' => array(
                    array('name' => 'fiscal_year_id', 'label' => 'Fiscal year', 'type' => 'fiscal_year_lookup', 'required' => TRUE,
                        'reference' => array('table' => 'fin_fiscal_year', 'id' => 'fiscal_year_id')),
                    array('name' => 'period_number', 'label' => 'Period number', 'type' => 'number', 'required' => TRUE),
                    array('name' => 'period_name', 'label' => 'Period name', 'type' => 'text', 'required' => TRUE, 'maxlength' => 80, 'normalize' => 'name'),
                    array('name' => 'start_on', 'label' => 'Start date', 'type' => 'date', 'required' => TRUE),
                    array('name' => 'end_on', 'label' => 'End date', 'type' => 'date', 'required' => TRUE),
                    array('name' => 'status', 'label' => 'Status', 'type' => 'select', 'default' => 'open',
                        'options' => array('open' => 'Open', 'closed' => 'Closed')),
                ),
            ),
            'cash_accounts' => array(
                'slug' => 'cash_accounts', 'menu_code' => 'fin_cash_accounts', 'table' => 'fin_cash_account',
                'id' => 'cash_account_id', 'title_key' => 'finance_cash_accounts',
                'search' => array('account_code', 'account_name', 'institution_name'),
                'columns' => array(
                    array('name' => 'account_code', 'label' => 'Code'),
                    array('name' => 'account_name', 'label' => 'Account'),
                    array('name' => 'account_kind', 'label' => 'Kind'),
                    array('name' => 'currency_code', 'label' => 'Currency'),
                    array('name' => 'active_flag', 'label' => 'Active'),
                ),
                'fields' => array(
                    array('name' => 'account_id', 'label' => 'Chart of accounts', 'type' => 'account_lookup', 'required' => TRUE,
                        'reference' => array('table' => 'fin_account', 'id' => 'account_id')),
                    array('name' => 'account_code', 'label' => 'Code', 'type' => 'text', 'required' => TRUE, 'maxlength' => 32, 'normalize' => 'code'),
                    array('name' => 'account_name', 'label' => 'Account name', 'type' => 'text', 'required' => TRUE, 'maxlength' => 120, 'normalize' => 'name'),
                    array('name' => 'account_kind', 'label' => 'Kind', 'type' => 'select', 'required' => TRUE,
                        'options' => array('cash' => 'Cash', 'bank' => 'Bank')),
                    array('name' => 'currency_code', 'label' => 'Currency code', 'type' => 'currency', 'required' => TRUE, 'maxlength' => 3, 'normalize' => 'currency'),
                    array('name' => 'institution_name', 'label' => 'Bank / institution', 'type' => 'text', 'maxlength' => 120),
                    $active,
                ),
            ),
            'financial_settings' => array(
                'slug' => 'financial_settings', 'menu_code' => 'fin_financial_settings', 'table' => 'fin_settings',
                'id' => 'settings_id', 'title_key' => 'finance_financial_settings',
                'search' => array('base_currency'),
                'columns' => array(
                    array('name' => 'base_currency', 'label' => 'Base currency', 'label_key' => 'finance_column_base_currency'),
                    array('name' => 'tax_inclusive_default', 'label' => 'Tax inclusive', 'label_key' => 'finance_column_tax_inclusive'),
                ),
                'fields' => array(
                    array('name' => 'base_currency', 'label' => 'Base currency (ISO code)', 'label_key' => 'finance_field_base_currency', 'type' => 'currency', 'required' => TRUE, 'maxlength' => 3, 'normalize' => 'currency',
                        'hint' => '3-letter ISO code of the accounting currency, e.g. IDR or USD. All journals and reports convert to this currency.',
                        'hint_key' => 'finance_field_base_currency_hint'),
                    array('name' => 'tax_inclusive_default', 'label' => 'Tax inclusive by default', 'label_key' => 'finance_field_tax_inclusive_default', 'type' => 'select', 'default' => '0',
                        'options' => array('0' => 'No — tax exclusive', '1' => 'Yes — tax inclusive'),
                        'option_label_keys' => array('0' => 'finance_option_tax_exclusive', '1' => 'finance_option_tax_inclusive'),
                        'hint' => 'Determines whether sales/purchase document totals include tax by default.',
                        'hint_key' => 'finance_field_tax_inclusive_default_hint'),
                    array('name' => 'receivable_account_id', 'label' => 'Accounts receivable control account', 'label_key' => 'finance_field_receivable_account_id', 'type' => 'account_lookup',
                        'reference' => array('table' => 'fin_account', 'id' => 'account_id'),
                        'hint' => 'Asset account for customer receivable balances. Recommended: 1100 Piutang Usaha.',
                        'hint_key' => 'finance_field_receivable_account_id_hint'),
                    array('name' => 'payable_account_id', 'label' => 'Accounts payable control account', 'label_key' => 'finance_field_payable_account_id', 'type' => 'account_lookup',
                        'reference' => array('table' => 'fin_account', 'id' => 'account_id'),
                        'hint' => 'Liability account for supplier payable balances. Recommended: 2000 Utang Usaha.',
                        'hint_key' => 'finance_field_payable_account_id_hint'),
                    array('name' => 'fx_gain_account_id', 'label' => 'Foreign exchange gain account', 'label_key' => 'finance_field_fx_gain_account_id', 'type' => 'account_lookup',
                        'reference' => array('table' => 'fin_account', 'id' => 'account_id'),
                        'hint' => 'Revenue account for exchange-rate gains. Recommended: 8000 Keuntungan Selisih Kurs.',
                        'hint_key' => 'finance_field_fx_gain_account_id_hint'),
                    array('name' => 'fx_loss_account_id', 'label' => 'Foreign exchange loss account', 'label_key' => 'finance_field_fx_loss_account_id', 'type' => 'account_lookup',
                        'reference' => array('table' => 'fin_account', 'id' => 'account_id'),
                        'hint' => 'Expense account for exchange-rate losses. Recommended: 8100 Kerugian Selisih Kurs.',
                        'hint_key' => 'finance_field_fx_loss_account_id_hint'),
                    array('name' => 'rounding_tolerance', 'label' => 'Payment rounding tolerance', 'label_key' => 'finance_field_rounding_tolerance', 'type' => 'decimal', 'required' => TRUE, 'default' => '1.00',
                        'hint' => 'Maximum overpayment allowed in the document currency and written off as rounding. Recommended: 1.00 for IDR.',
                        'hint_key' => 'finance_field_rounding_tolerance_hint'),
                    array('name' => 'rounding_gain_account_id', 'label' => 'Rounding gain account', 'label_key' => 'finance_field_rounding_gain_account_id', 'type' => 'account_lookup',
                        'reference' => array('table' => 'fin_account', 'id' => 'account_id'),
                        'hint' => 'Revenue/other-income account for small customer overpayment rounding differences.',
                        'hint_key' => 'finance_field_rounding_gain_account_id_hint'),
                    array('name' => 'rounding_loss_account_id', 'label' => 'Rounding loss account', 'label_key' => 'finance_field_rounding_loss_account_id', 'type' => 'account_lookup',
                        'reference' => array('table' => 'fin_account', 'id' => 'account_id'),
                        'hint' => 'Expense account for small supplier overpayment rounding differences.',
                        'hint_key' => 'finance_field_rounding_loss_account_id_hint'),
                ),
            ),
        );

        $cash_fields = array();
        foreach ($resources['cash_accounts']['fields'] as $field) {
            if ($field['name'] !== 'account_kind') {
                $cash_fields[] = $field;
            }
        }
        $resources['cash_accounts']['fields'] = $cash_fields;
        $resources['cash_accounts']['scope'] = array('account_kind' => 'cash');
        $resources['cash_accounts']['defaults'] = array('account_kind' => 'cash');

        $bank = $resources['cash_accounts'];
        $bank['slug'] = 'bank_accounts';
        $bank['menu_code'] = 'fin_bank_accounts';
        $bank['title_key'] = 'finance_bank_accounts';
        $bank['scope'] = array('account_kind' => 'bank');
        $bank['defaults'] = array('account_kind' => 'bank');
        $resources['bank_accounts'] = $bank;
        return $resources;
    }
}
