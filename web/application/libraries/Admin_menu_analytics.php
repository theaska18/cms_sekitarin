<?php
/**
 * Tenant-scoped menu visit recording and popular-menu reporting.
 */
class Admin_menu_analytics
{
    const TABLE = 'app_menu_visit';

    /** Normalize a request or manifest route to the same absolute path form. */
    public static function normalize_path($path)
    {
        if (!is_string($path) || trim($path) === '') {
            return '';
        }

        $path = parse_url(trim($path), PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '';
        }

        return '/' . trim($path, '/');
    }

    /** Record one successfully rendered menu landing page. */
    public function record_visit($tenant_id, $user_id, $menu_code)
    {
        $CI =& get_instance();
        if (!isset($CI->db) || (int) $tenant_id < 1 || (int) $user_id < 1
            || !is_string($menu_code) || !preg_match('/^[a-zA-Z0-9_-]{1,100}$/', $menu_code)
            || !$CI->db->table_exists(self::TABLE)) {
            return FALSE;
        }

        return $CI->db->insert(self::TABLE, array(
            'tenant_id' => (int) $tenant_id,
            'user_id' => (int) $user_id,
            'menu_code' => $menu_code,
            'visited_at' => app_now(),
        ));
    }

    /** Return popular menus for the current tenant and the past 30 days. */
    public function popular_menus($tenant_id, $allowed_codes, $limit = 5)
    {
        $CI =& get_instance();
        if (!isset($CI->db) || (int) $tenant_id < 1 || !is_array($allowed_codes)
            || empty($allowed_codes) || !$CI->db->table_exists(self::TABLE)) {
            return array();
        }

        $allowed_codes = array_values(array_unique(array_filter($allowed_codes, function ($code) {
            return is_string($code) && preg_match('/^[a-zA-Z0-9_-]{1,100}$/', $code);
        })));
        if (empty($allowed_codes)) {
            return array();
        }
        $limit = max(1, min(10, (int) $limit));
        $query = $CI->db->select('menu_code, COUNT(*) AS visit_count', FALSE)
            ->from(self::TABLE)
            ->where('tenant_id', (int) $tenant_id)
            ->where('visited_at >=', date('Y-m-d H:i:s', time() - 30 * 86400))
            ->where('user_id !=', 0)
            ->where_in('menu_code', $allowed_codes)
            ->group_by('menu_code')
            ->order_by('visit_count', 'DESC')
            ->order_by('menu_code', 'ASC')
            ->limit($limit)
            ->get();

        return $query === FALSE ? array() : $query->result_array();
    }
}
