<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$menu_access_row = is_array($menu_access_row) ? $menu_access_row : array();
$menu_access_write_available = !empty($menu_access_write_available);
$menu_access_code = isset($menu_access_row['menu_code']) ? (string) $menu_access_row['menu_code'] : '';
$menu_access_name = isset($menu_access_row['menu_name']) ? (string) $menu_access_row['menu_name'] : $menu_access_code;
$menu_access_checked = !empty($menu_access_row['checked']);
$menu_access_items = isset($menu_access_row['accesses']) && is_array($menu_access_row['accesses'])
    ? $menu_access_row['accesses']
    : array();
?>
<div class="flex min-h-10 flex-wrap items-center gap-x-6 gap-y-3" role="group" aria-label="<?= html_escape($menu_access_name) ?>">
    <?php if (!$menu_access_items): ?>
        <span class="text-sm text-slate-400" aria-hidden="true">—</span>
    <?php else: ?>
        <?php foreach ($menu_access_items as $menu_access_item): ?>
            <?php
            $menu_access_item = is_array($menu_access_item) ? $menu_access_item : array();
            $menu_access_item_code = isset($menu_access_item['code']) ? (string) $menu_access_item['code'] : '';
            $menu_access_item_name = isset($menu_access_item['name']) ? (string) $menu_access_item['name'] : $menu_access_item_code;
            $menu_access_item_checked = !empty($menu_access_item['checked']);
            ?>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="access_codes[<?= html_escape($menu_access_code) ?>][]" value="<?= html_escape($menu_access_item_code) ?>" data-menu-access <?= $menu_access_item_checked ? 'checked' : '' ?> <?= (!$menu_access_checked || !$menu_access_write_available) ? 'disabled' : '' ?> class="h-4 w-4 shrink-0 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-50">
                <span><?= html_escape($menu_access_item_name) ?></span>
            </label>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
