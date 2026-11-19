<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Flattens the admin menu manifest into rows suitable for the menu-access
 * tables, then filters, orders, and slices them for DataTables requests.
 *
 * Tenant pages pass the full manifest; role pages pass the tenant-enabled
 * subset through $allowed_state so the same code path stays tenant-scoped.
 */
class Menu_access_catalog
{
    public function rows($groups, $state, $allowed_state = NULL)
    {
        $groups = is_array($groups) ? $groups : array();
        $state = is_array($state) ? $state : array();
        $rows = array();
        $group_order = 0;
        foreach ($groups as $group) {
            if (!is_array($group) || !isset($group['items']) || !is_array($group['items'])) {
                continue;
            }
            $group_code = isset($group['code']) ? (string) $group['code'] : '';
            $group_label = isset($group['label']) && $group['label'] !== ''
                ? (string) $group['label']
                : (isset($group['name']) ? (string) $group['name'] : $group_code);
            $menu_order = 0;
            foreach ($group['items'] as $item) {
                if (!is_array($item) || !isset($item['code']) || !is_string($item['code']) || $item['code'] === '') {
                    continue;
                }
                $menu_code = (string) $item['code'];
                if (is_array($allowed_state) && !isset($allowed_state[$menu_code])) {
                    continue;
                }
                $accesses = array();
                $item_accesses = isset($item['accesses']) && is_array($item['accesses']) ? $item['accesses'] : array();
                foreach ($item_accesses as $access) {
                    if (!is_array($access) || !isset($access['access_code'])) {
                        continue;
                    }
                    $access_code = (string) $access['access_code'];
                    if ($access_code === '') {
                        continue;
                    }
                    if (is_array($allowed_state) && !isset($allowed_state[$menu_code][$access_code])) {
                        continue;
                    }
                    $accesses[] = array(
                        'code' => $access_code,
                        'name' => isset($access['access_name']) ? (string) $access['access_name'] : $access_code,
                        'checked' => isset($state[$menu_code][$access_code]),
                    );
                }
                $rows[] = array(
                    'menu_code' => $menu_code,
                    'menu_name' => isset($item['name']) ? (string) $item['name'] : $menu_code,
                    'group_code' => $group_code,
                    'group_label' => $group_label,
                    'group_order' => $group_order,
                    'menu_order' => $menu_order,
                    'checked' => isset($state[$menu_code]),
                    'accesses' => $accesses,
                );
                $menu_order++;
            }
            $group_order++;
        }

        return $rows;
    }

    public function filter($rows, $search, $fields = array())
    {
        $rows = is_array($rows) ? $rows : array();
        $needle = $this->lower(trim((string) $search));
        $fields = is_array($fields) ? $fields : array();
        $group_needle = isset($fields['group']) ? $this->lower(trim((string) $fields['group'])) : '';
        $menu_needle = isset($fields['menu']) ? $this->lower(trim((string) $fields['menu'])) : '';
        $access_needle = isset($fields['access']) ? $this->lower(trim((string) $fields['access'])) : '';
        if ($needle === '' && $group_needle === '' && $menu_needle === '' && $access_needle === '') {
            return $rows;
        }
        $filtered = array();
        foreach ($rows as $row) {
            $haystack = array(
                isset($row['menu_name']) ? $row['menu_name'] : '',
                isset($row['menu_code']) ? $row['menu_code'] : '',
                isset($row['group_label']) ? $row['group_label'] : '',
                isset($row['group_code']) ? $row['group_code'] : '',
            );
            $access_haystack = array();
            if (isset($row['accesses']) && is_array($row['accesses'])) {
                foreach ($row['accesses'] as $access) {
                    $access_name = isset($access['name']) ? $access['name'] : '';
                    $access_code = isset($access['code']) ? $access['code'] : '';
                    $access_haystack[] = $access_name;
                    $access_haystack[] = $access_code;
                    $haystack[] = $access_name;
                    $haystack[] = $access_code;
                }
            }
            if ($needle !== '' && !$this->matches_any($haystack, $needle)) {
                continue;
            }
            if ($group_needle !== '' && !$this->matches_any(array(
                isset($row['group_label']) ? $row['group_label'] : '',
                isset($row['group_code']) ? $row['group_code'] : '',
            ), $group_needle)) {
                continue;
            }
            if ($menu_needle !== '' && !$this->matches_any(array(
                isset($row['menu_name']) ? $row['menu_name'] : '',
                isset($row['menu_code']) ? $row['menu_code'] : '',
            ), $menu_needle)) {
                continue;
            }
            if ($access_needle !== '' && !$this->matches_any($access_haystack, $access_needle)) {
                continue;
            }
            $filtered[] = $row;
        }

        return $filtered;
    }

    private function matches_any($values, $needle)
    {
        foreach ($values as $value) {
            if (strpos($this->lower((string) $value), $needle) !== FALSE) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public function sort($rows, $order, $direction)
    {
        $rows = is_array($rows) ? $rows : array();
        $order = in_array($order, array('menu', 'group'), TRUE) ? $order : 'group';
        $descending = is_string($direction) && strtoupper($direction) === 'DESC';
        usort($rows, function ($left, $right) use ($order, $descending) {
            if ($order === 'menu') {
                $comparison = $this->compare_text(
                    isset($left['menu_name']) ? $left['menu_name'] : '',
                    isset($right['menu_name']) ? $right['menu_name'] : ''
                );
                if ($comparison !== 0) {
                    return $descending ? -$comparison : $comparison;
                }
            } else {
                $comparison = (int) $left['group_order'] - (int) $right['group_order'];
                if ($comparison !== 0) {
                    return $descending ? -$comparison : $comparison;
                }
            }
            $group_comparison = (int) $left['group_order'] - (int) $right['group_order'];
            if ($group_comparison !== 0) {
                return $group_comparison;
            }

            return (int) $left['menu_order'] - (int) $right['menu_order'];
        });

        return $rows;
    }

    public function slice($rows, $length, $start)
    {
        $rows = is_array($rows) ? $rows : array();
        $length = max(0, (int) $length);
        $start = max(0, (int) $start);

        return array_slice($rows, $start, $length);
    }

    private function compare_text($left, $right)
    {
        $comparison = strnatcasecmp((string) $left, (string) $right);
        if ($comparison === 0) {
            return 0;
        }

        return $comparison < 0 ? -1 : 1;
    }

    private function lower($value)
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
    }
}
