<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Persistence for externally submitted sales invoices and purchase bills. */
class Finance_integration_store
{
    private $db;
    private $available = array();

    public function __construct($db = NULL)
    {
        if ($db === NULL) {
            $CI =& get_instance();
            if (!isset($CI->db)) {
                $CI->load->database();
            }
            $db = $CI->db;
        }
        $this->db = $db;
    }

    public function available($kind)
    {
        $spec = $this->spec($kind);
        if ($spec === NULL) {
            return FALSE;
        }
        if (array_key_exists($kind, $this->available)) {
            return $this->available[$kind];
        }
        if (!$this->db->table_exists($spec['table'])) {
            return $this->available[$kind] = FALSE;
        }
        $fields = $this->db->list_fields($spec['table']);
        $required = array(
            $spec['id'], 'tenant_id', 'created_by', $spec['number'], $spec['date'],
            'due_date', $spec['party_name'],
            'amount_before_tax', 'tax_percent', 'tax_amount', 'total_amount',
            'description', 'status', 'received_at', 'processed_at', $spec['document_id'],
        );
        return $this->available[$kind] = is_array($fields) && !array_diff($required, $fields);
    }

    public function count($kind, $tenant_id, $search = '', $filters = array())
    {
        $spec = $this->spec($kind);
        if ($spec === NULL || !$this->available($kind)) {
            return 0;
        }
        $this->apply_search($spec, $search);
        $this->apply_filters($spec, $filters);
        $count = $this->db->from($spec['table'])
            ->where('tenant_id', (int) $tenant_id)
            ->count_all_results();
        return $count === FALSE ? 0 : (int) $count;
    }

    public function page($kind, $tenant_id, $search, $limit, $offset, $order, $direction, $filters = array())
    {
        $spec = $this->spec($kind);
        if ($spec === NULL || !$this->available($kind)) {
            return array();
        }
        $limit = min(100, max(1, (int) $limit));
        $offset = max(0, (int) $offset);
        $order_map = array(
            'received_at' => 'received_at',
            $spec['date'] => $spec['date'],
            $spec['number'] => $spec['number'],
            $spec['party_name'] => $spec['party_name'],
            $spec['party_code'] => $spec['party_code'],
            'amount_before_tax' => 'amount_before_tax',
            'tax_percent' => 'tax_percent',
            'total_amount' => 'total_amount',
            'status' => 'status',
        );
        $order_column = isset($order_map[$order]) ? $order_map[$order] : 'received_at';
        $direction = strtoupper((string) $direction) === 'ASC' ? 'ASC' : 'DESC';

        $this->apply_search($spec, $search);
        $this->apply_filters($spec, $filters);
        $document_table = $kind === 'sales' ? 'fin_sales_invoice' : 'fin_purchase_bill';
        $selected = array(
            $spec['id'], 'tenant_id', 'created_by', $spec['number'], $spec['date'],
            'due_date', $spec['party_name'], $spec['party_code'], 'amount_before_tax',
            'tax_percent', 'tax_amount', 'total_amount', 'description', 'status',
            'received_at', 'processed_at', $spec['document_id'],
        );
        if ($this->db->field_exists('cancelled_at', $spec['table'])) {
            $selected[] = 'cancelled_at';
        }
        $select = 't.' . implode(',t.', $selected) . ',d.status AS draft_status';
        $query = $this->db->select($select, FALSE)
            ->from($spec['table'] . ' t')
            ->join($document_table . ' d', 'd.' . $spec['document_id'] . '=t.' . $spec['document_id'] . ' AND d.tenant_id=t.tenant_id', 'left')
            ->where('t.tenant_id', (int) $tenant_id)
            ->order_by($order_column, $direction)
            ->order_by('t.' . $spec['id'], 'DESC')
            ->limit($limit, $offset)
            ->get();
        return $query === FALSE ? array() : $query->result_array();
    }

    public function find($kind, $tenant_id, $inbox_id)
    {
        $spec = $this->spec($kind);
        if ($spec === NULL || !$this->available($kind)) {
            return FALSE;
        }
        $query = $this->db->from($spec['table'])
            ->where('tenant_id', (int) $tenant_id)
            ->where($spec['id'], (int) $inbox_id)
            ->limit(1)
            ->get();
        if ($query === FALSE) {
            return FALSE;
        }
        return $query->num_rows() ? $query->row_array() : NULL;
    }

    public function find_number($kind, $tenant_id, $number)
    {
        $spec = $this->spec($kind);
        if ($spec === NULL || !$this->available($kind)) {
            return NULL;
        }
        $query = $this->db->from($spec['table'])
            ->where('tenant_id', (int) $tenant_id)
            ->where($spec['number'], (string) $number)
            ->order_by("CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END", 'ASC', FALSE)
            ->order_by($spec['id'], 'DESC')
            ->limit(1)
            ->get();
        if ($query === FALSE) {
            return NULL;
        }
        return $query->num_rows() ? $query->row_array() : NULL;
    }

    public function find_active_number($kind, $tenant_id, $number)
    {
        $spec = $this->spec($kind);
        if ($spec === NULL || !$this->available($kind)) {
            return NULL;
        }
        $query = $this->db->from($spec['table'])
            ->where('tenant_id', (int) $tenant_id)
            ->where($spec['number'], (string) $number)
            ->where('status !=', 'cancelled')
            ->order_by($spec['id'], 'DESC')
            ->limit(1)
            ->get();
        if ($query === FALSE) {
            return NULL;
        }
        return $query->num_rows() ? $query->row_array() : NULL;
    }

    public function create($kind, $tenant_id, $user_id, $data)
    {
        $spec = $this->spec($kind);
        if ($spec === NULL || !$this->available($kind)) {
            return FALSE;
        }
        $values = array(
            'tenant_id' => (int) $tenant_id,
            'created_by' => (int) $user_id,
            $spec['number'] => (string) $data['number'],
            $spec['date'] => (string) $data['date'],
            'due_date' => $data['due_date'] !== '' ? (string) $data['due_date'] : NULL,
            $spec['party_name'] => (string) $data['party_name'],
            $spec['party_code'] => $data['party_code'] !== '' ? (string) $data['party_code'] : NULL,
            'amount_before_tax' => $data['amount_before_tax'],
            'tax_percent' => $data['tax_percent'],
            'tax_amount' => $data['tax_amount'],
            'total_amount' => $data['total_amount'],
            'description' => (string) $data['description'],
            'status' => 'received',
            'payload_json' => isset($data['payload_json']) ? (string) $data['payload_json'] : NULL,
            'received_at' => date('Y-m-d H:i:s'),
        );
        return $this->db->insert($spec['table'], $values) === TRUE;
    }

    public function mark_processed($kind, $tenant_id, $inbox_id, $document_id)
    {
        $spec = $this->spec($kind);
        if ($spec === NULL || !$this->available($kind)) {
            return FALSE;
        }
        return $this->db->where('tenant_id', (int) $tenant_id)
            ->where($spec['id'], (int) $inbox_id)
            ->update($spec['table'], array(
                'status' => 'processed',
                'error_message' => NULL,
                $spec['document_id'] => (int) $document_id,
                'processed_at' => date('Y-m-d H:i:s'),
            )) === TRUE;
    }

    public function document_status($kind, $tenant_id, $document_id)
    {
        $spec = $this->spec($kind);
        if ($spec === NULL || (int) $document_id < 1) {
            return NULL;
        }
        $table = $kind === 'sales' ? 'fin_sales_invoice' : 'fin_purchase_bill';
        if (!$this->db->table_exists($table)) {
            return NULL;
        }
        $query = $this->db->select('status')
            ->from($table)
            ->where('tenant_id', (int) $tenant_id)
            ->where($spec['document_id'], (int) $document_id)
            ->limit(1)
            ->get();
        if ($query === FALSE || !$query->num_rows()) {
            return NULL;
        }
        return (string) $query->row()->status;
    }

    public function cancel($kind, $tenant_id, $inbox_id, $cancelled_at)
    {
        $spec = $this->spec($kind);
        if ($spec === NULL || !$this->available($kind)
            || !$this->db->field_exists('cancelled_at', $spec['table'])) {
            return FALSE;
        }
        $this->db->where('tenant_id', (int) $tenant_id)
            ->where($spec['id'], (int) $inbox_id)
            ->where('status', 'received')
            ->where($spec['document_id'] . ' IS NULL', NULL, FALSE)
            ->update($spec['table'], array(
                'status' => 'cancelled',
                'cancelled_at' => (string) $cancelled_at,
            ));
        return $this->db->affected_rows() === 1;
    }

    /** Soft-remove an inbox row whose linked draft document is already cancelled. */
    public function cancel_linked_cancelled($kind, $tenant_id, $inbox_id, $cancelled_at)
    {
        $spec = $this->spec($kind);
        if ($spec === NULL || !$this->available($kind)
            || !$this->db->field_exists('cancelled_at', $spec['table'])) {
            return FALSE;
        }
        $row = $this->find($kind, $tenant_id, $inbox_id);
        if (!is_array($row)) {
            return FALSE;
        }
        $document_id = (int) $row[$spec['document_id']];
        if ($document_id < 1
            || $this->document_status($kind, $tenant_id, $document_id) !== 'cancelled') {
            return FALSE;
        }
        $this->db->where('tenant_id', (int) $tenant_id)
            ->where($spec['id'], (int) $inbox_id)
            ->where('status !=', 'cancelled')
            ->update($spec['table'], array(
                'status' => 'cancelled',
                'cancelled_at' => (string) $cancelled_at,
            ));
        return $this->db->affected_rows() === 1;
    }

    public function spec($kind)
    {
        if ($kind === 'sales') {
            return array(
                'table' => 'fin_sales_invoice_inbox',
                'id' => 'sales_invoice_inbox_id',
                'number' => 'invoice_number',
                'date' => 'invoice_date',
                'party_name' => 'customer_name',
                'party_code' => 'customer_code',
                'document_id' => 'sales_invoice_id',
                'document_kind' => 'sales',
                'slug' => 'sales_invoices',
                'menu_code' => 'fin_sales_invoice_inbox',
            );
        }
        if ($kind === 'purchase') {
            return array(
                'table' => 'fin_purchase_bill_inbox',
                'id' => 'purchase_bill_inbox_id',
                'number' => 'bill_number',
                'date' => 'bill_date',
                'party_name' => 'supplier_name',
                'party_code' => 'supplier_code',
                'document_id' => 'purchase_bill_id',
                'document_kind' => 'purchase',
                'slug' => 'purchase_bills',
                'menu_code' => 'fin_purchase_bill_inbox',
            );
        }
        return NULL;
    }

    private function apply_filters($spec, $filters)
    {
        if (!empty($filters['start_on'])) {
            $this->db->where($spec['date'] . ' >=', $filters['start_on']);
        }
        if (!empty($filters['end_on'])) {
            $this->db->where($spec['date'] . ' <=', $filters['end_on']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('status', $filters['status']);
        }
    }

    private function apply_search($spec, $search)
    {
        $search = trim((string) $search);
        if ($search === '') {
            return;
        }
        $this->db->group_start()
            ->like($spec['number'], $search)
            ->or_like($spec['date'], $search)
            ->or_like($spec['party_name'], $search)
            ->or_like($spec['party_code'], $search)
            ->group_end();
    }
}
