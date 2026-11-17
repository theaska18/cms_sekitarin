<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$menu_access_row = is_array($menu_access_row) ? $menu_access_row : array();
$menu_access_write_available = !empty($menu_access_write_available);
$menu_access_code = isset($menu_access_row['menu_code']) ? (string) $menu_access_row['menu_code'] : '';
$menu_access_name = isset($menu_access_row['menu_name']) ? (string) $menu_access_row['menu_name'] : $menu_access_code;
$menu_access_checked = !empty($menu_access_row['checked']);
?>
<label class="flex min-h-10 items-center gap-3 text-sm font-semibold text-slate-800">
    <input type="checkbox" name="menu_codes[]" value="<?= html_escape($menu_access_code) ?>" data-menu-toggle <?= $menu_access_checked ? 'checked' : '' ?> <?= $menu_access_write_available ? '' : 'disabled' ?> class="h-4 w-4 shrink-0 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-60">
    <span><?= html_escape($menu_access_name) ?></span>
</label>
