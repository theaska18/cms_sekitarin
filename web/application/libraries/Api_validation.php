<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Minimal rule-based validation for the JSON API. */
class Api_validation
{
    /**
     * Rules use "required|email|min:8|max:190|regex:/.../" strings.
     * Returns a map of field => localized message.
     */
    public function check($data, $rules)
    {
        $errors = array();
        foreach ($rules as $field => $rule_string) {
            $value = $this->value($data, $field);
            $rules_for_field = explode('|', (string) $rule_string);
            $label = $field;

            foreach ($rules_for_field as $rule) {
                $rule = trim($rule);
                if ($rule === '') {
                    continue;
                }
                $name = $rule;
                $arg = NULL;
                if (strpos($rule, ':') !== FALSE) {
                    list($name, $arg) = explode(':', $rule, 2);
                }

                if ($name === 'required') {
                    if ($value === NULL || (is_string($value) && trim($value) === '') || (is_array($value) && !$value)) {
                        $errors[$field] = 'Wajib diisi.';
                        break;
                    }
                    continue;
                }
                if ($value === NULL || (is_string($value) && trim($value) === '')) {
                    continue;
                }
                if (!is_scalar($value)) {
                    $errors[$field] = 'Nilai tidak valid.';
                    break;
                }
                $text = (string) $value;

                if ($name === 'email' && !filter_var($text, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = 'Format email tidak valid.';
                    break;
                }
                if ($name === 'min' && mb_strlen($text) < (int) $arg) {
                    $errors[$field] = 'Minimal ' . (int) $arg . ' karakter.';
                    break;
                }
                if ($name === 'max' && mb_strlen($text) > (int) $arg) {
                    $errors[$field] = 'Maksimal ' . (int) $arg . ' karakter.';
                    break;
                }
                if ($name === 'regex' && @preg_match($arg, '') !== FALSE && !preg_match($arg, $text)) {
                    $errors[$field] = 'Format tidak valid.';
                    break;
                }
                if ($name === 'in' && !in_array($text, explode(',', (string) $arg), TRUE)) {
                    $errors[$field] = 'Pilihan tidak valid.';
                    break;
                }
            }
        }
        return $errors;
    }

    /** Read a dotted path such as "device.platform" from an array. */
    public function value($data, $path)
    {
        $cursor = $data;
        foreach (explode('.', (string) $path) as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return NULL;
            }
            $cursor = $cursor[$segment];
        }
        return $cursor;
    }

    public function bool_value($value)
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_string($value)) {
            return in_array(strtolower((string) $value), array('1', 'true', 'yes', 'on'), TRUE);
        }
        return FALSE;
    }
}
