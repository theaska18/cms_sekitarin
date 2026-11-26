<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Shared Finance filter panel configuration, validation, rendering, and query helpers. */
class Finance_filter_panel
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function config($overrides = array())
    {
        $config = array(
            'key' => 'finance',
            'target' => '',
            'date' => TRUE,
            'status_preset' => 'transaction',
            'status_default' => '',
            'flag' => FALSE,
            'default_days' => 7,
            'default_start' => '',
            'default_end' => '',
            'date_preset' => 'single',
            'primary_date' => '',
            'date_fields' => array(),
            'field_filters' => array(),
            'add_action' => array(),
            'export' => TRUE,
            'page_size_default' => 50,
            'page_size_options' => array(50, 100, 200),
        );
        if (is_array($overrides)) {
            $config = array_merge($config, $overrides);
        }
        $config['default_days'] = max(1, (int) $config['default_days']);
        return $config;
    }

    public function defaults($config)
    {
        $config = $this->config($config);
        $today = date('Y-m-d');
        $start = !empty($config['default_start']) ? (string) $config['default_start'] : date('Y-m-d', strtotime('-' . ($config['default_days'] - 1) . ' days'));
        $end = !empty($config['default_end']) ? (string) $config['default_end'] : $today;
        return array('start_on' => $start, 'end_on' => $end);
    }

    public function filters($config)
    {
        $config = $this->config($config);
        $defaults = $this->defaults($config);
        $date_ranges = array();
        $legacy = array('start_on' => '', 'end_on' => '');
        if (!empty($config['date'])) {
            $fields = $this->date_fields($config);
            foreach ($fields as $index => $field) {
                $start_name = !empty($field['start_name']) ? $field['start_name'] : $field['key'] . '_start';
                $end_name = !empty($field['end_name']) ? $field['end_name'] : $field['key'] . '_end';
                $start = trim((string) $this->CI->input->get($start_name, TRUE));
                $end = trim((string) $this->CI->input->get($end_name, TRUE));
                $is_default = !empty($field['default']);
                if ($is_default && $start === '' && $end === '') {
                    $legacy_start = trim((string) $this->CI->input->get('start_on', TRUE));
                    $legacy_end = trim((string) $this->CI->input->get('end_on', TRUE));
                    if ($this->valid_date($legacy_start)) $start = $legacy_start;
                    if ($this->valid_date($legacy_end)) $end = $legacy_end;
                }
                if (!$this->valid_date($start)) $start = $is_default ? $defaults['start_on'] : '';
                if (!$this->valid_date($end)) $end = $is_default ? $defaults['end_on'] : '';
                if ($start !== '' && $end !== '' && $start > $end) {
                    $start = $is_default ? $defaults['start_on'] : '';
                    $end = $is_default ? $defaults['end_on'] : '';
                }
                if ($start === '' && $end === '') continue;
                $date_ranges[$field['key']] = array('start' => $start, 'end' => $end);
                if ($index === 0) {
                    $legacy['start_on'] = $start;
                    $legacy['end_on'] = $end;
                }
            }
            if (!empty($config['date_preset']) && $config['date_preset'] === 'report') {
                $start_name = 'start_on';
                $end_name = 'end_on';
                $start = trim((string) $this->CI->input->get($start_name, TRUE));
                $end = trim((string) $this->CI->input->get($end_name, TRUE));
                if (!$this->valid_date($start)) $start = $defaults['start_on'];
                if (!$this->valid_date($end)) $end = $defaults['end_on'];
                if ($start > $end) { $start = $defaults['start_on']; $end = $defaults['end_on']; }
                $legacy['start_on'] = $start;
                $legacy['end_on'] = $end;
            }
        }

        $status_raw = $this->CI->input->get('status', TRUE);
        $status = trim((string) $status_raw);
        $allowed = array_keys($this->status_options($config['status_preset'], array()));
        if (!in_array($status, $allowed, TRUE)) {
            $status = '';
        }
        if ($status_raw === NULL && $config['status_default'] !== ''
            && in_array($config['status_default'], $allowed, TRUE)) {
            $status = $config['status_default'];
        }

        $flag = trim((string) $this->CI->input->get('flag', TRUE));
        if (empty($config['flag']) || !in_array($flag, array('', '0', '1'), TRUE)) {
            $flag = '';
        }

        $fields = array();
        foreach ((array) $config['field_filters'] as $field) {
            if (empty($field['name']) || empty($field['type'])) continue;
            if ($field['type'] === 'range') {
                $min_name = !empty($field['min_name']) ? $field['min_name'] : $field['name'] . '_min';
                $max_name = !empty($field['max_name']) ? $field['max_name'] : $field['name'] . '_max';
                $min = trim((string) $this->CI->input->get($min_name, TRUE));
                $max = trim((string) $this->CI->input->get($max_name, TRUE));
                if ($min !== '' && !preg_match('/^-?\d{1,20}(?:\.\d{1,2})?$/', $min)) $min = '';
                if ($max !== '' && !preg_match('/^-?\d{1,20}(?:\.\d{1,2})?$/', $max)) $max = '';
                $fields[$field['name']] = array('min' => $min, 'max' => $max);
            } else {
                $value = trim((string) $this->CI->input->get($field['name'], TRUE));
                if (isset($field['options']) && is_array($field['options']) && !array_key_exists($value, $field['options'])) $value = '';
                if (!isset($field['options']) && strlen($value) > 160) $value = '';
                $fields[$field['name']] = $value;
            }
        }

        return array(
            'start_on' => $legacy['start_on'],
            'end_on' => $legacy['end_on'],
            'date_ranges' => $date_ranges,
            'status' => $status,
            'flag' => $flag,
            'fields' => $fields,
        );
    }

    public function date_fields($config)
    {
        $config = $this->config($config);
        if (!empty($config['date_fields']) && is_array($config['date_fields'])) {
            return $config['date_fields'];
        }
        $primary = !empty($config['primary_date']) ? $config['primary_date'] : '';
        switch ($config['date_preset']) {
            case 'transaction':
                return array(
                    array('key' => 'transaction_on', 'label_key' => 'finance_transaction_date', 'default' => TRUE),
                    array('key' => 'create_on', 'label_key' => 'finance_created_at'),
                );
            case 'transfer':
                return array(array('key' => 'transfer_date', 'label_key' => 'finance_transfer_date', 'default' => TRUE));
            case 'document':
                return array(array('key' => $primary !== '' ? $primary : 'invoice_date', 'label_key' => 'finance_report_column_invoice_date', 'default' => FALSE));
            case 'integration':
                return array(array('key' => $primary !== '' ? $primary : 'invoice_date', 'label_key' => 'finance_integration_document_date', 'default' => FALSE));
            case 'journal':
                return array(array('key' => 'journal_date', 'label_key' => 'finance_journal_date', 'default' => TRUE));
            case 'payment':
                return array(array('key' => 'payment_date', 'label_key' => 'finance_payment_date', 'default' => TRUE));
            case 'reconciliation':
                return array(array('key' => 'statement_date', 'label_key' => 'finance_statement_date', 'default' => TRUE));
            case 'report':
                return array(array('key' => 'period', 'start_name' => 'start_on', 'end_name' => 'end_on', 'label_key' => 'finance_filter_period', 'default' => TRUE));
            case 'none':
                return array();
            default:
                return $primary !== '' ? array(array('key' => $primary, 'label_key' => 'finance_column_date', 'default' => TRUE)) : array();
        }
    }

    public function render($config, $labels = array())
    {
        $config = $this->config($config);
        $defaults = $this->defaults($config);
        $date_fields = !empty($config['date']) ? $this->date_fields($config) : array();
        foreach ($date_fields as $index => $field) {
            $label_key = isset($field['label_key']) ? $field['label_key'] : '';
            $date_fields[$index]['label'] = $label_key !== '' && isset($labels[$label_key])
                ? $labels[$label_key] : (isset($labels['finance_column_date']) ? $labels['finance_column_date'] : 'Date');
            $date_fields[$index]['start_name'] = !empty($field['start_name']) ? $field['start_name'] : $field['key'] . '_start';
            $date_fields[$index]['end_name'] = !empty($field['end_name']) ? $field['end_name'] : $field['key'] . '_end';
            $is_default = !empty($field['default']);
            $date_fields[$index]['start_value'] = ($index === 0 && $is_default) ? $defaults['start_on'] : '';
            $date_fields[$index]['end_value'] = ($index === 0 && $is_default) ? $defaults['end_on'] : '';
        }
        $field_filters = array();
        foreach ((array) $config['field_filters'] as $field) {
            if (empty($field['label']) && !empty($field['label_key'])) {
                $field['label'] = isset($labels[$field['label_key']]) ? $labels[$field['label_key']] : $field['label_key'];
            }
            if (!isset($field['label'])) {
                $field['label'] = '';
            }
            $field_filters[] = $field;
        }
        $status_options = $this->status_options($config['status_preset'], $labels);
        $status_raw = $this->CI->input->get('status', TRUE);
        $selected_status = $status_raw === NULL ? (string) $config['status_default'] : trim((string) $status_raw);
        if (!array_key_exists($selected_status, $status_options)) {
            $selected_status = '';
        }
        $data = array(
            'finance_filter_date' => !empty($config['date']),
            'finance_filter_date_fields' => $date_fields,
            'finance_filter_field_filters' => $field_filters,
            'finance_filter_add_action' => (array) $config['add_action'],
            'finance_filter_export_enabled' => !empty($config['export']),
            'finance_filter_target' => (string) $config['target'],
            'finance_filter_default_start' => $defaults['start_on'],
            'finance_filter_default_end' => $defaults['end_on'],
            'finance_filter_default_status' => (string) $config['status_default'],
            'finance_filter_page_size_default' => (int) $config['page_size_default'],
            'finance_filter_page_size_options' => (array) $config['page_size_options'],
            'finance_filter_status_selected' => $selected_status,
            'finance_filter_status_options' => $status_options,
            'finance_filter_flag_options' => !empty($config['flag'])
                ? array(
                    '1' => isset($labels['finance_filter_active']) ? $labels['finance_filter_active'] : 'Active',
                    '0' => isset($labels['finance_filter_inactive']) ? $labels['finance_filter_inactive'] : 'Inactive',
                )
                : array(),
            'labels' => $labels,
        );
        return $this->CI->load->view('admin/finance/partials/filter_panel', $data, TRUE);
    }

    public function status_options($preset, $labels = array())
    {
        if ($preset === 'integration') {
            return array(
                'received' => isset($labels['finance_integration_received']) ? $labels['finance_integration_received'] : 'Received',
                'processed' => isset($labels['finance_integration_processed']) ? $labels['finance_integration_processed'] : 'Processed',
                'cancelled' => isset($labels['finance_integration_cancelled']) ? $labels['finance_integration_cancelled'] : 'Cancelled',
            );
        }
        if ($preset === 'none' || $preset === 'active_flag') {
            return array();
        }
        return array(
            'draft' => isset($labels['finance_tx_state_draft']) ? $labels['finance_tx_state_draft'] : 'Draft',
            'pending_approval' => isset($labels['finance_tx_state_pending_approval']) ? $labels['finance_tx_state_pending_approval'] : 'Pending',
            'approved' => isset($labels['finance_tx_state_approved']) ? $labels['finance_tx_state_approved'] : 'Approved',
            'revise' => isset($labels['finance_tx_state_revise']) ? $labels['finance_tx_state_revise'] : 'Revise',
            'rejected' => isset($labels['finance_tx_state_rejected']) ? $labels['finance_tx_state_rejected'] : 'Rejected',
            'cancel_pending' => isset($labels['finance_tx_state_cancel_pending']) ? $labels['finance_tx_state_cancel_pending'] : 'Cancellation pending',
            'revision_pending' => isset($labels['finance_tx_state_revision_pending']) ? $labels['finance_tx_state_revision_pending'] : 'Revision pending',
            'posted' => isset($labels['finance_status_posted']) ? $labels['finance_status_posted'] : 'Posted',
            'completed' => isset($labels['finance_status_completed']) ? $labels['finance_status_completed'] : 'Completed',
            'cancelled' => isset($labels['finance_status_cancelled']) ? $labels['finance_status_cancelled'] : 'Cancelled',
            'revised' => isset($labels['finance_tx_state_revised']) ? $labels['finance_tx_state_revised'] : 'Revised',
        );
    }

    public function apply($db, $filters, $alias = '', $date_column = '', $status_column = '', $flag_column = '')
    {
        if (!empty($filters['start_on']) && $date_column !== '') {
            $db->where($alias . $date_column . ' >=', $filters['start_on']);
        }
        if (!empty($filters['end_on']) && $date_column !== '') {
            $db->where($alias . $date_column . ' <=', $filters['end_on']);
        }
        if (!empty($filters['status']) && $status_column !== '') {
            $db->where($alias . $status_column, $filters['status']);
        }
        if ($filters['flag'] !== '' && $flag_column !== '') {
            $db->where($alias . $flag_column, (int) $filters['flag']);
        }
    }

    private function valid_date($value)
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return FALSE;
        }
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return $date !== FALSE && $date->format('Y-m-d') === $value;
    }
}
