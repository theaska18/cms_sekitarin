<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_menu_manifest
{
    public function load($manifest_file)
    {
        $manifest = $this->read_xml($manifest_file, 'menu-manifest', 'manifest');
        if ($manifest === NULL) {
            return array();
        }

        $groups = array();
        foreach ($manifest->group as $group_source) {
            $file = $this->attribute($group_source, 'file');
            $submenu_file = $this->resolve_submenu_file($manifest_file, $file);
            if ($submenu_file === NULL) {
                $this->diagnostic('Menu manifest contains an unsafe or missing submenu reference.');
                continue;
            }

            $submenu = $this->read_xml($submenu_file, 'menu-group', 'submenu');
            if ($submenu === NULL) {
                continue;
            }

            $group_status = $this->attribute($group_source, 'status');
            if ($group_status === '') {
                $group_status = $this->attribute($submenu, 'status', 'active');
            }
            if (strtolower(trim($group_status)) !== 'active') {
                continue;
            }

            $group_code = $this->attribute($group_source, 'code');
            if ($group_code === '') {
                $group_code = $this->attribute($submenu, 'code');
            }
            $group_name = $this->attribute($submenu, 'name');
            if ($group_name === '') {
                $group_name = $this->attribute($group_source, 'name');
            }
            $group_sort_order = $this->has_attribute($group_source, 'sort_order')
                ? $this->attribute($group_source, 'sort_order')
                : $this->attribute($submenu, 'sort_order');
            $group_label_key = $this->attribute($submenu, 'label_key');
            if ($group_label_key === '') {
                $group_label_key = $this->attribute($group_source, 'label_key');
            }
            $group_icon = $this->attribute($submenu, 'icon');
            if ($group_icon === '') {
                $group_icon = $this->attribute($group_source, 'icon');
            }

            $items = array();
            foreach ($submenu->menu as $item_source) {
                $item_status = $this->attribute($item_source, 'status', 'active');
                if (strtolower(trim($item_status)) !== 'active') {
                    continue;
                }

                $route = $this->attribute($item_source, 'route');
                if (!$this->is_safe_route($route)) {
                    $this->diagnostic('Menu manifest contains an unsafe route; the entry was skipped.');
                    continue;
                }

                $accesses = array();
                foreach ($item_source->access as $access_source) {
                    $access_code = $this->attribute($access_source, 'code');
                    if ($access_code !== '') {
                        $accesses[] = array(
                            'access_code' => $access_code,
                            'access_name' => $this->attribute($access_source, 'name'),
                        );
                    }
                }

                $items[] = array(
                    'code' => $this->attribute($item_source, 'code'),
                    'name' => $this->attribute($item_source, 'name'),
                    'label_key' => $this->attribute($item_source, 'label_key'),
                    'icon' => $this->attribute($item_source, 'icon'),
                    'route' => $route,
                    'module_key' => $this->attribute($item_source, 'module_key'),
                    'module_description_key' => $this->attribute($item_source, 'module_description_key'),
                    'module_focus_key' => $this->attribute($item_source, 'module_focus_key'),
                    'sort_order' => $this->attribute($item_source, 'sort_order'),
                    'status' => $item_status,
                    'required' => $this->boolean_attribute($item_source, 'required'),
                    'version' => $this->attribute($item_source, 'version'),
                    'accesses' => $accesses,
                );
            }

            $groups[] = array(
                'code' => $group_code,
                'name' => $group_name,
                'label_key' => $group_label_key,
                'icon' => $group_icon,
                'file' => $file,
                'sort_order' => $group_sort_order,
                'status' => $group_status,
                'required' => $this->boolean_attribute($group_source, 'required'),
                'version' => $this->attribute($group_source, 'version', $this->attribute($submenu, 'version')),
                'items' => $this->sort_by_order($items),
            );
        }

        return $this->sort_by_order($groups);
    }

    public function resolve_label($translated, $fallback_name)
    {
        if (is_string($translated) && trim($translated) !== '') {
            return $translated;
        }
        if (is_string($fallback_name) && trim($fallback_name) !== '') {
            return $fallback_name;
        }

        return '';
    }

    private function read_xml($file, $root_name, $kind)
    {
        if (!is_string($file) || !is_file($file) || !is_readable($file)) {
            $this->diagnostic($kind === 'manifest'
                ? 'Unable to read the admin menu manifest XML.'
                : 'Unable to read an admin menu submenu XML file.');
            return NULL;
        }

        $contents = file_get_contents($file);
        if ($contents === FALSE) {
            $this->diagnostic($kind === 'manifest'
                ? 'Unable to read the admin menu manifest XML.'
                : 'Unable to read an admin menu submenu XML file.');
            return NULL;
        }
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        $contents = ltrim($contents);

        $previous_errors = libxml_use_internal_errors(TRUE);
        $document = simplexml_load_string($contents, 'SimpleXMLElement', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous_errors);

        if (!($document instanceof SimpleXMLElement)) {
            $this->diagnostic($kind === 'manifest'
                ? 'The admin menu manifest XML is invalid.'
                : 'An admin menu submenu XML file is invalid.');
            return NULL;
        }
        if ($document->getName() !== $root_name) {
            $this->diagnostic($kind === 'manifest'
                ? 'The admin menu manifest has an unexpected document root.'
                : 'An admin menu submenu has an unexpected document root.');
            return NULL;
        }

        return $document;
    }

    private function resolve_submenu_file($manifest_file, $relative_file)
    {
        if (!is_string($relative_file) || $relative_file === '' || strpos($relative_file, "\0") !== FALSE
            || preg_match('#(^|[\\\\/])\.\.?([\\\\/]|$)#', $relative_file)
            || strpos($relative_file, ':') !== FALSE
            || strtolower(pathinfo($relative_file, PATHINFO_EXTENSION)) !== 'xml') {
            return NULL;
        }

        $base = realpath(dirname($manifest_file));
        if ($base === FALSE) {
            return NULL;
        }
        $resolved = realpath($base . DIRECTORY_SEPARATOR . $relative_file);
        if ($resolved === FALSE || !is_file($resolved)
            || strpos($resolved, $base . DIRECTORY_SEPARATOR) !== 0) {
            return NULL;
        }

        return $resolved;
    }

    private function is_safe_route($route)
    {
        return is_string($route)
            && $route !== ''
            && $route[0] === '/'
            && strpos($route, '//') !== 0
            && !preg_match('/[\x00-\x20\x7f]/', $route);
    }

    private function sort_by_order($entries)
    {
        $ordered = array();
        foreach ($entries as $index => $entry) {
            $entry['_source_index'] = $index;
            $entry['_order_number'] = $this->integer_order($entry['sort_order']);
            $ordered[] = $entry;
        }

        usort($ordered, function ($left, $right) {
            $left_order = $left['_order_number'];
            $right_order = $right['_order_number'];
            if ($left_order !== FALSE && $right_order === FALSE) {
                return -1;
            }
            if ($left_order === FALSE && $right_order !== FALSE) {
                return 1;
            }
            if ($left_order !== FALSE && $right_order !== FALSE) {
                $comparison = $this->compare_integer_orders($left_order, $right_order);
                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return $left['_source_index'] - $right['_source_index'];
        });

        foreach ($ordered as &$entry) {
            unset($entry['_source_index'], $entry['_order_number']);
        }
        unset($entry);

        return $ordered;
    }

    private function integer_order($value)
    {
        $value = trim((string) $value);
        if (!preg_match('/^[+-]?\d+$/D', $value)) {
            return FALSE;
        }

        $negative = isset($value[0]) && $value[0] === '-';
        $digits = ltrim($value, '+-');
        $digits = ltrim($digits, '0');
        if ($digits === '') {
            $digits = '0';
            $negative = FALSE;
        }

        return array('negative' => $negative, 'digits' => $digits);
    }

    private function compare_integer_orders($left, $right)
    {
        if ($left['negative'] !== $right['negative']) {
            return $left['negative'] ? -1 : 1;
        }

        $length_comparison = strlen($left['digits']) - strlen($right['digits']);
        if ($length_comparison !== 0) {
            return $left['negative'] ? -$length_comparison : $length_comparison;
        }

        $comparison = strcmp($left['digits'], $right['digits']);
        if ($comparison === 0) {
            return 0;
        }

        if ($left['negative']) {
            return $comparison < 0 ? 1 : -1;
        }

        return $comparison < 0 ? -1 : 1;
    }

    private function has_attribute($element, $name)
    {
        foreach ($element->attributes() as $attribute_name => $value) {
            if ((string) $attribute_name === $name) {
                return TRUE;
            }
        }

        return FALSE;
    }

    private function attribute($element, $name, $default = '')
    {
        $attributes = $element->attributes();
        return isset($attributes[$name]) ? (string) $attributes[$name] : $default;
    }

    private function boolean_attribute($element, $name)
    {
        return in_array(strtolower(trim($this->attribute($element, $name))), array('true', '1', 'yes'), TRUE);
    }

    private function diagnostic($message)
    {
        if (function_exists('log_message')) {
            log_message('error', 'Admin menu manifest: ' . $message);
            return;
        }

        error_log('Admin menu manifest: ' . $message);
    }
}
