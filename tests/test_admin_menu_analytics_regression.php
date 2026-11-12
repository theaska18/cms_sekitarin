<?php
/** Regression test for persisting a rendered Property menu visit. */
function &get_instance()
{
    return $GLOBALS['CI'];
}

define('BASEPATH', '/var/www/html/system/');
define('APPPATH', '/var/www/html/application/');
$GLOBALS['CI'] = (object) array(
    'db' => new class {
        public $inserted = NULL;
        public function table_exists($table) { return $table === 'app_menu_visit'; }
        public function insert($table, $data) { $this->inserted = array($table, $data); return TRUE; }
    },
);
require_once APPPATH . 'libraries/Admin_menu_analytics.php';
$property_path = Admin_menu_analytics::normalize_path('/admin/system/property');
$property_manifest_route = Admin_menu_analytics::normalize_path('admin/system/property');
if ($property_path !== '/admin/system/property' || $property_path !== $property_manifest_route) {
    fwrite(STDERR, "admin menu route normalization mismatch\n");
    exit(3);
}
$analytics = new Admin_menu_analytics();
if (!$analytics->record_visit(7, 11, 'sys_property')) {
    fwrite(STDERR, "record_visit failed\n");
    exit(1);
}
$inserted = $GLOBALS['CI']->db->inserted;
if ($inserted[0] !== 'app_menu_visit' || $inserted[1]['menu_code'] !== 'sys_property'
    || $inserted[1]['tenant_id'] !== 7 || $inserted[1]['user_id'] !== 11) {
    fwrite(STDERR, "unexpected insert payload\n");
    exit(2);
}
echo "PASS: analytics library produces expected property visit insert\n";
