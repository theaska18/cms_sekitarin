<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared CodeIgniter adapter for DataTables server-side requests.
 *
 * Feature controllers remain responsible for access checks and provide
 * callbacks that keep their queries schema-checked and appropriately scoped.
 */
class Datatable_server
{
    /**
     * Parse a DataTables GET request, invoke feature-specific query callbacks,
     * and write the standard DataTables JSON response.
     *
     * $columns maps DataTables column indexes to allow-listed database fields.
     * $count_total is called without arguments; $count_filtered gets the
     * normalized search term; $fetch gets search, length, start, order, dir.
     */
    public function respond($columns, $count_total, $count_filtered, $fetch, $options = array())
    {
        $CI =& get_instance();
        $columns = is_array($columns) ? $columns : array();
        $max_length = isset($options['max_length']) ? max(1, min(500, (int) $options['max_length'])) : 50;
        $max_search_length = isset($options['max_search_length']) ? max(1, min(1000, (int) $options['max_search_length'])) : 120;
        $draw = $this->bounded_integer($CI->input->get('draw', TRUE), 0, 2147483647, 0);
        $start = $this->bounded_integer($CI->input->get('start', TRUE), 0, 2147483647, 0);
        $requested_length = $CI->input->get('length', TRUE);
        $length = $this->is_integer_value($requested_length)
            ? (int) $requested_length
            : $max_length;
        $length = $length <= 0 ? $max_length : min($max_length, $length);

        $search_input = $CI->input->get('search', TRUE);
        $search_value = is_array($search_input) && isset($search_input['value'])
            ? $search_input['value']
            : '';
        $search = is_string($search_value)
            ? substr(trim($search_value), 0, $max_search_length)
            : '';

        $default_order = isset($options['default_order']) && is_string($options['default_order'])
            ? $options['default_order']
            : (count($columns) ? reset($columns) : '');
        if (!in_array($default_order, $columns, TRUE)) {
            $default_order = count($columns) ? reset($columns) : '';
        }
        $order_input = $CI->input->get('order', TRUE);
        $order_index = is_array($order_input) && isset($order_input[0]['column'])
            ? $this->bounded_integer($order_input[0]['column'], -1, 2147483647, -1)
            : -1;
        $order = $order_index >= 0 && isset($columns[$order_index])
            ? $columns[$order_index]
            : $default_order;
        if (!in_array($order, $columns, TRUE)) {
            $order = $default_order;
        }
        $direction_value = is_array($order_input) && isset($order_input[0]['dir'])
            ? $order_input[0]['dir']
            : '';
        $direction = is_string($direction_value) && strtoupper($direction_value) === 'DESC'
            ? 'DESC'
            : 'ASC';

        if (!is_callable($count_total) || !is_callable($count_filtered) || !is_callable($fetch)) {
            $this->error(500, $draw);
            return;
        }

        $records_total = call_user_func($count_total);
        $records_filtered = $search === '' ? $records_total : call_user_func($count_filtered, $search);
        if (!is_numeric($records_total) || !is_numeric($records_filtered)
            || (int) $records_total < 0 || (int) $records_filtered < 0) {
            $this->error(503, $draw);
            return;
        }

        $records = $start >= (int) $records_filtered
            ? array()
            : call_user_func($fetch, $search, $length, $start, $order, $direction);
        if (!is_array($records)) {
            $this->error(503, $draw);
            return;
        }

        $this->output(200, array(
            'draw' => $draw,
            'recordsTotal' => (int) $records_total,
            'recordsFiltered' => (int) $records_filtered,
            'data' => $records,
        ));
    }

    public function error($status_code = 503, $draw = 0)
    {
        $this->output((int) $status_code, array(
            'draw' => max(0, (int) $draw),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => array(),
        ));
    }

    private function output($status_code, $payload)
    {
        $CI =& get_instance();
        $CI->output->set_status_header((int) $status_code);
        $CI->output->set_content_type('application/json', 'utf-8');
        $json = json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE);
        $CI->output->set_output($json === FALSE ? '{"draw":0,"recordsTotal":0,"recordsFiltered":0,"data":[]}' : $json);
    }

    private function is_integer_value($value)
    {
        return is_scalar($value) && preg_match('/\A-?\d{1,10}\z/', (string) $value);
    }

    private function bounded_integer($value, $minimum, $maximum, $default)
    {
        if (!$this->is_integer_value($value)) {
            return $default;
        }
        return min($maximum, max($minimum, (int) $value));
    }
}
