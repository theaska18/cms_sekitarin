<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

/** Sequence create controller, using the authenticated admin shell. */
class Admin_sequence extends Admin
{
    public function sequence_new()
    {
        $this->load->database();
        $this->db->db_debug = FALSE;
        $this->load->library('sequence_store');
        $store = $this->sequence_store;
        $backend_available = $store->schema_available();
        $field_specs = $store->field_specs();
        if (!$field_specs) {
            $backend_available = FALSE;
        }
        $repeat_type_options = $store->repeat_type_options();
        if ($repeat_type_options === FALSE) {
            $repeat_type_options = array();
            $backend_available = FALSE;
        }
        $write_available = $backend_available
            && $this->current_user !== NULL
            && (bool) config_item('csrf_protection');
        $record = $this->empty_sequence_record($field_specs);
        $errors = array();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Sequence writes are unavailable.', 503);
                return;
            }
            $record = $this->sequence_form_values();
            $errors = $this->validate_sequence_values($record, $field_specs, $repeat_type_options);
            if (!$errors) {
                $insert_record = $this->sequence_insert_record($record, $field_specs);
                if ($store->insert($insert_record)) {
                    $this->session->set_flashdata('sequence_notice', 'sequence_created');
                    redirect('/admin/system/sequence', 'location', 303);
                    return;
                }
                $errors[] = 'save_error';
            }
        }
        $this->render_admin(
            'admin/sequence/form',
            'sequence_form_new',
            '/admin/system/sequence/new',
            array(
                'sequence_record' => $record,
                'sequence_field_specs' => $field_specs,
                'sequence_repeat_type_options' => $repeat_type_options,
                'sequence_errors' => $errors,
                'sequence_backend_available' => $backend_available,
                'sequence_write_available' => $write_available,
            )
        );
    }

    public function sequence_change($sequence_code)
    {
        $this->load->database();
        $this->db->db_debug = FALSE;
        $this->load->library('sequence_store');
        $store = $this->sequence_store;
        $backend_available = $store->schema_available();
        $field_specs = $store->field_specs();
        $saved_record = $store->find((string) $sequence_code);
        if (!$backend_available || !$field_specs) {
            show_error('Sequence storage is unavailable.', 503);
            return;
        }
        if ($saved_record === FALSE) {
            show_error('Sequence storage is unavailable.', 503);
            return;
        }
        if ($saved_record === NULL) {
            show_404();
            return;
        }
        $repeat_type_options = $store->repeat_type_options();
        if ($repeat_type_options === FALSE) {
            show_error('Sequence options are unavailable.', 503);
            return;
        }
        $write_available = $this->current_user !== NULL && (bool) config_item('csrf_protection');
        $record = $this->sequence_record_for_form($saved_record, $field_specs);
        $errors = array();
        if ($this->input->method(TRUE) === 'POST') {
            if (!$write_available) {
                show_error('Sequence writes are unavailable.', 503);
                return;
            }
            $record = $this->sequence_form_values();
            $errors = $this->validate_sequence_values($record, $field_specs, $repeat_type_options);
            if (!$errors) {
                $update_record = $this->sequence_insert_record($record, $field_specs);
                if ($store->update((string) $sequence_code, $update_record)) {
                    $this->session->set_flashdata('sequence_notice', 'sequence_saved');
                    redirect('/admin/system/sequence', 'location', 303);
                    return;
                }
                $errors[] = 'save_error';
            }
        }
        $this->render_admin(
            'admin/sequence/form',
            'sequence_form_edit',
            '/admin/system/sequence/change/' . rawurlencode((string) $sequence_code),
            array(
                'sequence_record' => $record,
                'sequence_field_specs' => $field_specs,
                'sequence_repeat_type_options' => $repeat_type_options,
                'sequence_errors' => $errors,
                'sequence_backend_available' => $backend_available,
                'sequence_write_available' => $write_available,
                'sequence_is_edit' => TRUE,
                'sequence_original_code' => (string) $sequence_code,
            )
        );
    }

    public function sequence_delete()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed.', 405);
            return;
        }
        if ($this->current_user === NULL || !(bool) config_item('csrf_protection')) {
            show_error('Sequence writes are unavailable.', 503);
            return;
        }
        $sequence_code = $this->input->post('sequence_code', TRUE);
        if (!is_string($sequence_code) || $sequence_code === '') {
            $this->session->set_flashdata('sequence_notice', 'sequence_delete_error');
            redirect('/admin/system/sequence', 'location', 303);
            return;
        }
        $this->load->database();
        $this->db->db_debug = FALSE;
        $this->load->library('sequence_store');
        $result = $this->sequence_store->delete($sequence_code);
        $this->session->set_flashdata('sequence_notice', $result ? 'sequence_deleted' : 'sequence_delete_error');
        redirect('/admin/system/sequence', 'location', 303);
    }

    private function sequence_record_for_form($saved_record, $field_specs)
    {
        $record = $this->empty_sequence_record($field_specs);
        foreach ($record as $name => $unused) {
            if (array_key_exists($name, $saved_record) && $saved_record[$name] !== NULL) {
                $record[$name] = (string) $saved_record[$name];
            }
        }
        if (isset($record['last_sequence']) && $record['last_sequence'] !== '') {
            if ($this->sequence_uses_date_only($field_specs['last_sequence'])) {
                $record['last_sequence'] = substr($record['last_sequence'], 0, 10);
            } elseif (preg_match('/^(\\d{4}-\\d{2}-\\d{2})[ T](\\d{2}:\\d{2})/', $record['last_sequence'], $match)) {
                $record['last_sequence'] = $match[1] . 'T' . $match[2];
            }
        }
        return $record;
    }

    private function empty_sequence_record($field_specs)
    {
        $names = array(
            'sequence_code', 'sequence_name', 'description', 'repeat_type',
            'last_value', 'last_sequence', 'format',
        );
        $record = array();
        foreach ($names as $name) {
            $default = isset($field_specs[$name]) ? $field_specs[$name]['default'] : NULL;
            $record[$name] = is_scalar($default) ? (string) $default : '';
            if ($name === 'last_sequence' && !$this->sequence_uses_date_only(isset($field_specs[$name]) ? $field_specs[$name] : array())
                && preg_match('/^(\\d{4}-\\d{2}-\\d{2})[ T](\\d{2}:\\d{2})/', $record[$name], $match)) {
                $record[$name] = $match[1] . 'T' . $match[2];
            }
        }
        return $record;
    }

    private function sequence_insert_record($record, $field_specs)
    {
        $insert_record = array();
        foreach ($record as $name => $value) {
            if ($name === 'last_sequence' && $value !== '') {
                $date_format = $this->sequence_uses_date_only($field_specs[$name]) ? '!Y-m-d' : '!Y-m-d\\TH:i';
                $date = DateTime::createFromFormat($date_format, $value);
                $value = $this->sequence_uses_date_only($field_specs[$name])
                    ? $date->format('Y-m-d')
                    : $date->format('Y-m-d H:i:s');
            }
            if ($value === '' && isset($field_specs[$name])) {
                if (!empty($field_specs[$name]['nullable'])) {
                    $value = NULL;
                } elseif (empty($field_specs[$name]['required']) && $field_specs[$name]['default'] !== NULL) {
                    continue;
                }
            }
            $insert_record[$name] = $value;
        }
        return $insert_record;
    }

    private function sequence_form_values()
    {
        $names = array(
            'sequence_code', 'sequence_name', 'description', 'repeat_type',
            'last_value', 'last_sequence', 'format',
        );
        $values = array();
        foreach ($names as $name) {
            $value = $this->input->post($name, FALSE);
            $values[$name] = is_string($value) ? trim($value) : '';
        }
        $values['sequence_code'] = strtolower(preg_replace('/\\s+/', '', $values['sequence_code']));
        if (function_exists('mb_convert_case')) {
            $values['sequence_name'] = mb_convert_case(mb_strtolower($values['sequence_name'], 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        } else {
            $values['sequence_name'] = ucwords(strtolower($values['sequence_name']));
        }
        return $values;
    }

    private function validate_sequence_values($record, $field_specs, $repeat_type_options)
    {
        $errors = array();
        foreach ($record as $name => $value) {
            if (isset($field_specs[$name]) && !empty($field_specs[$name]['required']) && $value === '') {
                $errors[] = $name . '_required';
            }
        }
        foreach ($record as $name => $value) {
            if (!isset($field_specs[$name])) {
                continue;
            }
            $max = isset($field_specs[$name]['max_length']) ? (int) $field_specs[$name]['max_length'] : 0;
            $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
            if ($max > 0 && $length > $max) {
                $errors[] = $name . '_too_long';
            }
        }
        if ($record['sequence_code'] !== '' && preg_match('/[\\x00-\\x20\\x7F]/', $record['sequence_code'])) {
            $errors[] = 'sequence_code_invalid';
        }
        if ($record['last_value'] !== '' && !preg_match('/^-?\\d+$/', $record['last_value'])) {
            $errors[] = 'last_value_invalid';
        } elseif ($record['last_value'] !== '' && !$this->within_integer_range($record['last_value'], $field_specs['last_value'])) {
            $errors[] = 'last_value_invalid';
        }
        if ($record['last_sequence'] !== '') {
            $date_format = $this->sequence_uses_date_only($field_specs['last_sequence']) ? '!Y-m-d' : '!Y-m-d\\TH:i';
            $date = DateTime::createFromFormat($date_format, $record['last_sequence']);
            $date_errors = DateTime::getLastErrors();
            if ($date === FALSE || ($date_errors !== FALSE && ($date_errors['warning_count'] || $date_errors['error_count']))) {
                $errors[] = 'last_sequence_invalid';
            }
        }
        $valid_options = array();
        foreach ($repeat_type_options as $option) {
            if (isset($option['option_code'])) {
                $valid_options[] = (string) $option['option_code'];
            }
        }
        if ($record['repeat_type'] !== '' && !in_array($record['repeat_type'], $valid_options, TRUE)) {
            $errors[] = 'repeat_type_invalid';
        }
        return array_values(array_unique($errors));
    }

    private function sequence_uses_date_only($spec)
    {
        $sql_type = isset($spec['sql_type']) ? strtolower($spec['sql_type']) : '';
        return preg_match('/^date(?:\\s|$|\\()/', $sql_type) === 1
            && !preg_match('/^(?:datetime|timestamp)\\b/', $sql_type);
    }

    private function within_integer_range($value, $spec)
    {
        $sql_type = isset($spec['sql_type']) ? strtolower($spec['sql_type']) : '';
        if (!preg_match('/^(tinyint|smallint|mediumint|int|integer|bigint)\\b/', $sql_type, $match)) {
            return TRUE;
        }
        $maxima = array(
            'tinyint' => '127', 'smallint' => '32767', 'mediumint' => '8388607',
            'int' => '2147483647', 'integer' => '2147483647', 'bigint' => '9223372036854775807',
        );
        $max = $maxima[$match[1]];
        $unsigned = strpos($sql_type, 'unsigned') !== FALSE;
        $minimum = $unsigned ? '0' : '-' . (string) ((int) $max + 1);
        if ($match[1] === 'bigint' && !$unsigned) {
            $minimum = '-9223372036854775808';
        }
        if ($unsigned) {
            $max = array(
                'tinyint' => '255', 'smallint' => '65535', 'mediumint' => '16777215',
                'int' => '4294967295', 'integer' => '4294967295', 'bigint' => '18446744073709551615',
            )[$match[1]];
        }
        return $this->compare_integer_strings($value, $minimum) >= 0
            && $this->compare_integer_strings($value, $max) <= 0;
    }

    private function compare_integer_strings($left, $right)
    {
        $left = (string) $left;
        $right = (string) $right;
        $left_negative = isset($left[0]) && $left[0] === '-';
        $right_negative = isset($right[0]) && $right[0] === '-';
        if ($left_negative !== $right_negative) {
            return $left_negative ? -1 : 1;
        }
        $left_digits = ltrim($left_negative ? substr($left, 1) : $left, '0');
        $right_digits = ltrim($right_negative ? substr($right, 1) : $right, '0');
        $left_digits = $left_digits === '' ? '0' : $left_digits;
        $right_digits = $right_digits === '' ? '0' : $right_digits;
        $comparison = strlen($left_digits) === strlen($right_digits)
            ? strcmp($left_digits, $right_digits)
            : (strlen($left_digits) < strlen($right_digits) ? -1 : 1);
        return $left_negative ? -$comparison : $comparison;
    }
}
