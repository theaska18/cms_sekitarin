<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Tenant-scoped persistence for Finance setup/master-data resources. */
class Finance_store
{
    private $db;
    private $checked = array();

    public function __construct($db = NULL)
    {
        if ($db === NULL) {
            $CI =& get_instance();
            $db = $CI->db;
        }
        $this->db = $db;
    }

    public function available($resource)
    {
        if (isset($this->checked[$resource['slug']])) {
            return $this->checked[$resource['slug']];
        }
        if (!$this->db->table_exists($resource['table'])) {
            return $this->checked[$resource['slug']] = FALSE;
        }
        $field_data = $this->db->field_data($resource['table']);
        if (!is_array($field_data) || !$field_data) {
            return $this->checked[$resource['slug']] = FALSE;
        }
        $columns = array();
        foreach ($field_data as $field) {
            if (isset($field->name)) {
                $columns[$field->name] = $field;
            }
        }
        $required = array_merge(
            array('tenant_id', $resource['id']),
            array_column($resource['fields'], 'name'),
            array_column($resource['columns'], 'name')
        );
        foreach (array_unique($required) as $column) {
            if (!isset($columns[$column])) {
                return $this->checked[$resource['slug']] = FALSE;
            }
        }
        return $this->checked[$resource['slug']] = isset($columns[$resource['id']]->primary_key)
            && (bool) $columns[$resource['id']]->primary_key;
    }

    public function count($resource, $tenant_id, $search = '')
    {
        if (!$this->available($resource)) {
            return FALSE;
        }
        $this->db->from($resource['table'])->where('tenant_id', (int) $tenant_id);
        $this->apply_scope($resource);
        $this->apply_search($resource, $search);
        $count = $this->db->count_all_results();
        return $count === FALSE ? FALSE : (int) $count;
    }

    public function page($resource, $tenant_id, $search, $limit, $offset, $order, $direction)
    {
        if (!$this->available($resource)) {
            return FALSE;
        }
        $select = array($resource['id']);
        foreach ($resource['columns'] as $column) {
            $select[] = $column['name'];
        }
        $this->db->select(implode(',', $select))->from($resource['table'])
            ->where('tenant_id', (int) $tenant_id);
        $this->apply_scope($resource);
        $this->apply_search($resource, $search);
        $allowed_order = array_column($resource['columns'], 'name');
        $order = in_array($order, $allowed_order, TRUE) ? $order : $allowed_order[0];
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $query = $this->db->order_by($order, $direction)
            ->limit(min(50, max(1, (int) $limit)), max(0, (int) $offset))
            ->get();
        return $query === FALSE ? FALSE : $query->result_array();
    }

    public function find($resource, $tenant_id, $id)
    {
        if (!$this->available($resource)) {
            return FALSE;
        }
        $query = $this->db->where('tenant_id', (int) $tenant_id)
            ->where($resource['id'], (int) $id)
            ->limit(1);
        $this->apply_scope($resource);
        $query = $query->get($resource['table']);
        if ($query === FALSE) {
            return FALSE;
        }
        return $query->num_rows() === 1 ? $query->row_array() : NULL;
    }

    public function related_exists($table, $id_column, $tenant_id, $id)
    {
        if (!preg_match('/\Afin_[a-z_]+\z/', (string) $table)
            || !preg_match('/\A[a-z_]+\z/', (string) $id_column)
            || (int) $id < 1) {
            return FALSE;
        }
        $query = $this->db->where('tenant_id', (int) $tenant_id)
            ->where($id_column, (int) $id)
            ->limit(1)
            ->get($table);
        return $query !== FALSE && $query->num_rows() === 1;
    }

    public function account_label($tenant_id, $id)
    {
        if (!$this->related_exists('fin_account', 'account_id', $tenant_id, $id)) {
            return '';
        }
        $query = $this->db->select('account_code,account_name')->where('tenant_id', (int) $tenant_id)
            ->where('account_id', (int) $id)->limit(1)->get('fin_account');
        if ($query === FALSE || !$query->num_rows()) {
            return '';
        }
        $row = $query->row_array();
        return $row['account_code'] . ' — ' . $row['account_name'];
    }

    public function insert($resource, $tenant_id, $values, $user_id)
    {
        if (!$this->available($resource)) {
            return FALSE;
        }
        $values = array_merge(isset($resource['defaults']) ? $resource['defaults'] : array(), $values);
        $values['tenant_id'] = (int) $tenant_id;
        $this->stamp($resource['table'], $values, 'create_on', 'created_by', $user_id);
        return $this->db->insert($resource['table'], $values) === TRUE;
    }

    public function update($resource, $tenant_id, $id, $values, $user_id)
    {
        if (!$this->available($resource) || !$values) {
            return 'error';
        }
        $this->stamp($resource['table'], $values, 'update_on', 'updated_by', $user_id);
        $this->db->where('tenant_id', (int) $tenant_id)
            ->where($resource['id'], (int) $id);
        $this->apply_scope($resource);
        $ok = $this->db->update($resource['table'], $values);
        if ($ok !== TRUE) {
            return 'error';
        }
        if ((int) $this->db->affected_rows() > 0) {
            return 'updated';
        }
        $existing = $this->find($resource, $tenant_id, $id);
        if ($existing === FALSE) {
            return 'error';
        }
        return $existing === NULL ? 'not_found' : 'unchanged';
    }

    private function apply_search($resource, $search)
    {
        if (!is_string($search) || trim($search) === '') {
            return;
        }
        $this->db->group_start();
        foreach ($resource['search'] as $index => $column) {
            if ($index === 0) {
                $this->db->like($column, $search);
            } else {
                $this->db->or_like($column, $search);
            }
        }
        $this->db->group_end();
    }

    private function apply_scope($resource)
    {
        if (isset($resource['scope']) && is_array($resource['scope'])) {
            foreach ($resource['scope'] as $column => $value) {
                $this->db->where($column, $value);
            }
        }
    }

    private function stamp($table, &$values, $date_column, $user_column, $user_id)
    {
        if ($this->db->field_exists($date_column, $table)) {
            $values[$date_column] = date('Y-m-d H:i:s');
        }
        if ($this->db->field_exists($user_column, $table)) {
            $values[$user_column] = (int) $user_id;
        }
    }
}
