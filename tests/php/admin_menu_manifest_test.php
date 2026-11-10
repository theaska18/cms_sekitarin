<?php
if (!isset($argv[1], $argv[2])) {
    fwrite(STDERR, "usage: admin_menu_manifest_test.php <library> <manifest-dir>\n");
    exit(2);
}

define('BASEPATH', __DIR__);
$GLOBALS['admin_menu_test_diagnostics'] = array();
function log_message($level, $message)
{
    $GLOBALS['admin_menu_test_diagnostics'][] = array('level' => $level, 'message' => $message);
}

$library = $argv[1];
$manifest_dir = $argv[2];
if (!is_file($library)) {
    fwrite(STDERR, "Admin_menu_manifest library missing\n");
    exit(1);
}
require_once $library;
if (!class_exists('Admin_menu_manifest')) {
    fwrite(STDERR, "Admin_menu_manifest class missing\n");
    exit(1);
}

$checks = 0;
function expect_same($expected, $actual, $message)
{
    global $checks;
    $checks++;
    if ($expected !== $actual) {
        fwrite(STDERR, $message . "\nexpected: " . var_export($expected, TRUE) . "\nactual: " . var_export($actual, TRUE) . "\n");
        exit(1);
    }
}

function source_xml($path)
{
    $contents = file_get_contents($path);
    return simplexml_load_string(ltrim($contents), 'SimpleXMLElement', LIBXML_NONET);
}

$loader = new Admin_menu_manifest();
$groups = $loader->load($manifest_dir . '/manifest.xml');
$real_manifest = source_xml($manifest_dir . '/manifest.xml');
$checks++;
if (count($groups) !== count($real_manifest->group)) {
    fwrite(STDERR, "real manifest groups were not loaded\n");
    exit(1);
}
$expected_codes = array();
$expected_routes = array();
foreach ($real_manifest->group as $group_source) {
    $group_file = (string) $group_source['file'];
    $submenu = source_xml($manifest_dir . '/' . $group_file);
    $code = (string) $group_source['code'];
    $expected_codes[] = $code;
    $expected_items = array();
    $position = 0;
    foreach ($submenu->menu as $item) {
        $status = (string) $item['status'];
        if ($status === '' || strtolower($status) === 'active') {
            $expected_items[] = array(
                'sort_order' => (int) $item['sort_order'],
                'position' => $position,
                'route' => (string) $item['route'],
            );
        }
        $position++;
    }
    usort($expected_items, function ($left, $right) {
        return ($left['sort_order'] <=> $right['sort_order']) ?: ($left['position'] <=> $right['position']);
    });
    $expected_routes[$code] = array_column($expected_items, 'route');
}
$actual_codes = array_map(function ($group) { return $group['code']; }, $groups);
expect_same($expected_codes, $actual_codes, 'manifest group order differs from source sort order');
foreach ($groups as $group) {
    $routes = array_map(function ($item) { return $item['route']; }, $group['items']);
    expect_same($expected_routes[$group['code']], $routes, 'submenu routes differ from XML source');
}

$fixture = sys_get_temp_dir() . '/admin-menu-manifest-' . uniqid('', TRUE);
mkdir($fixture, 0700, TRUE);
function fixture_group($dir, $code, $root_sort, $item_xml = '', $extra_attrs = '')
{
    $safe_code = strtolower($code);
    $sort = $root_sort === NULL ? '' : ' sort_order="' . $root_sort . '"';
    file_put_contents($dir . '/' . $safe_code . '.xml',
        '<menu-group code="' . $code . '" name="' . $code . ' label" label_key=""' . $sort . '>' . $item_xml . '</menu-group>');
    return '<group code="' . $code . '" file="' . $safe_code . '.xml"' . $extra_attrs . ($root_sort === NULL ? '' : ' sort_order="' . $root_sort . '"') . '/>';
}
$groups_xml = array();
$groups_xml[] = fixture_group($fixture, 'LATE', '4');
$groups_xml[] = fixture_group($fixture, 'TIE_A', '1', '', ' required="true"');
$groups_xml[] = fixture_group($fixture, 'TIE_B', '01', '', ' required="false"');
$groups_xml[] = fixture_group($fixture, 'MISSING', NULL, '', ' required="false"');
$groups_xml[] = fixture_group($fixture, 'INVALID', 'bad', '', ' required="false"');
$groups_xml[] = fixture_group($fixture, 'INACTIVE', '0', '', ' status="inactive" required="true"');
$items = '<menu code="TEN" name="Ten" icon="users" route="/ten" sort_order="10" status="active" />'
    . '<menu code="TWO" name="Two" icon="menu" route="/two" sort_order="2" status="active" />'
    . '<menu code="TWO_TIE" name="Two tie" icon="menu" route="/two-tie" sort_order="2" status="active" />'
    . '<menu code="MISSING" name="Missing" icon="menu" route="/missing" status="active" />'
    . '<menu code="INVALID" name="Invalid" icon="menu" route="/invalid" sort_order="bad" status="active" />'
    . '<menu code="OFF" name="Off" icon="menu" route="/off" sort_order="0" status="inactive" />'
    . '<menu code="DASHBOARD" name="Dashboard" icon="dashboard" route="/admin" sort_order="0" status="active" />'
    . '<menu code="BAD_ROUTE" name="Bad route" icon="menu" route="//evil.example" sort_order="1" status="active" />';
$groups_xml[0] = fixture_group($fixture, 'LATE', '4', $items);
file_put_contents($fixture . '/manifest.xml', "<?xml version=\"1.0\"?><menu-manifest>" . implode('', $groups_xml) . '</menu-manifest>');
$groups = $loader->load($fixture . '/manifest.xml');
$actual_codes = array_map(function ($group) { return $group['code']; }, $groups);
expect_same(array('TIE_A', 'TIE_B', 'LATE', 'MISSING', 'INVALID'), $actual_codes, 'numeric sorting, stable ties, missing and invalid order policy failed');
expect_same(TRUE, $groups[0]['required'], 'required attribute was not preserved');
expect_same(FALSE, $groups[1]['required'], 'optional required attribute was not preserved');
$late = $groups[2];
$late_routes = array_map(function ($item) { return $item['route']; }, $late['items']);
expect_same(array('/admin', '/two', '/two-tie', '/ten', '/missing', '/invalid'), $late_routes, 'item numeric sorting, stable ties, invalid/missing order, status or route safety failed');
expect_same('users', $late['items'][3]['icon'], 'item icon attribute was not preserved');
expect_same('active', $late['items'][3]['status'], 'item status attribute was not preserved');
expect_same('LATE label', $late['name'], 'group name fallback metadata was not preserved');

$label = $loader->resolve_label('Localized label', 'XML fallback');
expect_same('Localized label', $label, 'localized label did not take precedence');
$label = $loader->resolve_label(FALSE, 'XML fallback');
expect_same('XML fallback', $label, 'missing localization did not fall back to XML name');
$label = $loader->resolve_label('', 'XML fallback');
expect_same('XML fallback', $label, 'empty localization did not fall back to XML name');

$before = count($GLOBALS['admin_menu_test_diagnostics']);
expect_same(array(), $loader->load($fixture . '/missing.xml'), 'missing manifest did not fail closed');
$diagnostics = array_slice($GLOBALS['admin_menu_test_diagnostics'], $before);
$checks++;
if (count($diagnostics) !== 1 || $diagnostics[0]['level'] !== 'error' || strpos($diagnostics[0]['message'], $fixture) !== FALSE) {
    fwrite(STDERR, "missing-file diagnostics are absent or disclose the path\n");
    exit(1);
}
$before = count($GLOBALS['admin_menu_test_diagnostics']);
file_put_contents($fixture . '/invalid.xml', '<menu-manifest><group>');
expect_same(array(), $loader->load($fixture . '/invalid.xml'), 'invalid XML did not fail closed');
$diagnostics = array_slice($GLOBALS['admin_menu_test_diagnostics'], $before);
$checks++;
if (count($diagnostics) !== 1 || $diagnostics[0]['level'] !== 'error' || strpos($diagnostics[0]['message'], $fixture) !== FALSE) {
    fwrite(STDERR, "invalid-file diagnostics are absent or disclose the path\n");
    exit(1);
}
$before = count($GLOBALS['admin_menu_test_diagnostics']);
file_put_contents($fixture . '/missing_child.xml', '<menu-manifest><group code="MISSING_CHILD" file="not-here.xml" required="true" sort_order="0" /></menu-manifest>');
expect_same(array(), $loader->load($fixture . '/missing_child.xml'), 'missing submenu did not fail closed');
$diagnostics = array_slice($GLOBALS['admin_menu_test_diagnostics'], $before);
$checks++;
if (count($diagnostics) !== 1 || $diagnostics[0]['level'] !== 'error' || strpos($diagnostics[0]['message'], $fixture) !== FALSE) {
    fwrite(STDERR, "missing-submenu diagnostics are absent or disclose the path\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fixture, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($iterator as $entry) {
    $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
}
rmdir($fixture);
echo 'PHP menu-manifest checks: ' . $checks . " passed\n";
