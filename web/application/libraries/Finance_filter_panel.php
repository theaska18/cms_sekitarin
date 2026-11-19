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
            'flag' => FALSE,
            'default_days' => 7,
            'default_start' => '',
            'default_end' => '',
        );
        if (is_array($overrides)) {
            $config = array_merge($config, $overrides);
        }
        $config['default_days'] = max(1, (int) $config['default_days']);
        return $config;
    }

    public function defaults($config)
    {
        $today = date('Y-m-d');
        $start = !empty($config['default_start']) ? (string) $config['default_start'] : date('Y-m-d', strtotime('-' . ($config['default_days'] - 1) . ' days'));
        $end = !empty($config['default_end']) ? (string) $config['default_end'] : $today;
        return array('start_on' => $start, 'end_on' => $end);
    }

    public function filters($config)
    {
        $defaults = $this->defaults($config);
        $start = '';
        $end = '';
        if (!empty($config['date'])) {
            $start = trim((string) $this->CI->input->get('start_on', TRUE));
            $end = trim((string) $this->CI->input->get('end_on', TRUE));
            if (!$this->valid_date($start)) {
                $start = $defaults['start_on'];
            }
            if (!$this->valid_date($end)) {
                $end = $defaults['end_on'];
            }
            if ($start > $end) {
                $start = $defaults['start_on'];
                $end = $defaults['end_on'];
            }
        }

        $status = trim((string) $this->CI->input->get('status', TRUE));
        $allowed = array_keys($this->status_options($config['status_preset'], array()));
        if (!in_array($status, $allowed, TRUE)) {
            $status = '';
        }

        $flag = trim((string) $this->CI->input->get('flag', TRUE));
        if (empty($config['flag']) || !in_array($flag, array('', '0', '1'), TRUE)) {
            $flag = '';
        }

        return array('start_on' => $start, 'end_on' => $end, 'status' => $status, 'flag' => $flag);
    }

    public function render($config, $labels = array())
    {
        $defaults = $this->defaults($config);
        $data = array(
            'finance_filter_date' => !empty($config['date']),
            'finance_filter_target' => (string) $config['target'],
            'finance_filter_default_start' => $defaults['start_on'],
            'finance_filter_default_end' => $defaults['end_on'],
            'finance_filter_status_options' => $this->status_options($config['status_preset'], $labels),
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
            'posted' => isset($labels['finance_status_posted']) ? $labels['finance_status_posted'] : 'Posted',
            'completed' => isset($labels['finance_status_completed']) ? $labels['finance_status_completed'] : 'Completed',
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
